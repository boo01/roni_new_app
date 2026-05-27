// Roni5 catalog crawler.
//
// Drives system Chrome via playwright-core to:
//   1. read the full Wix "Categories" tree (incl. subcategories) from a category
//      page's server-rendered warmup data,
//   2. capture the storefront's own `getFilteredProducts` GraphQL request once
//      (so we reuse its exact whitelisted query + auth headers), then
//   3. replay it per category id with paging to pull every product with full
//      detail + the complete image gallery,
//   4. union products across categories (dedup by id) and record which
//      categories each product belongs to.
//
// Output: database/seeders/data/roni5-catalog/catalog.json (data only; images
// are fetched separately by download-images.mjs).
import { chromium } from 'playwright-core';
import { mkdirSync, writeFileSync } from 'node:fs';

const UA =
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
const ORIGIN = 'https://www.roni5.ge';
const BOOTSTRAP = `${ORIGIN}/category/საკანცელარიო`; // a category with >20 products (has load-more)
const OUT_DIR = new URL('../../database/seeders/data/roni5-catalog/', import.meta.url).pathname;
const PAGE_SIZE = 100;
const LIMIT_PER_CAT = process.env.LIMIT ? Number(process.env.LIMIT) : null; // for quick validation runs

mkdirSync(OUT_DIR, { recursive: true });
const log = (...a) => console.log(...a);

// Extract every balanced {...} JSON object containing `key`, JSON.parse each.
function extractObjects(html, key) {
  const out = [];
  const needle = `"${key}"`;
  let i = 0;
  while (true) {
    const k = html.indexOf(needle, i);
    if (k === -1) break;
    const s = html.lastIndexOf('{', k);
    let depth = 0, end = -1, inStr = false, esc = false;
    for (let j = s; j < html.length; j++) {
      const c = html[j];
      if (esc) { esc = false; continue; }
      if (c === '\\') { esc = true; continue; }
      if (c === '"') { inStr = !inStr; continue; }
      if (inStr) continue;
      if (c === '{') depth++;
      else if (c === '}') { depth--; if (depth === 0) { end = j; break; } }
    }
    if (end !== -1) { try { out.push(JSON.parse(html.slice(s, end + 1))); } catch {} i = end + 1; }
    else i = k + needle.length;
  }
  return out;
}

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const ctx = await browser.newContext({ userAgent: UA, locale: 'ka-GE' });
const page = await ctx.newPage();

// Capture the storefront's getFilteredProducts request to reuse verbatim.
let template = null;
page.on('request', (req) => {
  if (template) return;
  if (/wix-ecommerce-storefront-web\/api/.test(req.url()) && req.method() === 'POST' && /getFilteredProducts/.test(req.postData() || '')) {
    template = { url: req.url(), headers: { ...req.headers() }, postData: req.postData() };
  }
});

// ---- 1. category tree + header menu ----
log('-> homepage (header menu)');
await page.goto(`${ORIGIN}/`, { waitUntil: 'domcontentloaded', timeout: 90000 });
await page.waitForTimeout(4000);
const headerCats = await page.evaluate(() => {
  const hdr = document.querySelector('header') || document.body;
  return [...hdr.querySelectorAll('a[href*="/category/"]')].map((a, idx) => ({
    label: (a.textContent || '').trim(),
    slug: decodeURIComponent(a.getAttribute('href').split('/category/')[1] || '').replace(/[/?#].*$/, ''),
    order: idx,
  }));
});
const headerSlugs = new Map(headerCats.filter((c) => c.slug).map((c) => [c.slug, c]));
log('   header categories:', [...headerSlugs.keys()].join(', '));

log('-> bootstrap category page (tree + request template)');
await page.goto(BOOTSTRAP, { waitUntil: 'domcontentloaded', timeout: 90000 });
await page.waitForSelector('a[href*="/product-page/"]', { timeout: 60000 }).catch(() => {});
await page.waitForTimeout(2500);
const html = await page.content();
const rawCats = extractObjects(html, 'parentCategoryId').filter((o) => o.id && o.slug);
const catMap = new Map();
for (const c of rawCats) {
  if (catMap.has(c.id)) continue;
  catMap.set(c.id, {
    id: c.id,
    name: (c.name || '').trim(),
    slug: c.slug,
    parentId: c.parentCategoryId || null,
    sortOrder: c.parentCategoryIndex ?? 0,
    visible: c.visible !== false,
  });
}
log(`   category tree: ${catMap.size} nodes (${[...catMap.values()].filter((c) => !c.parentId).length} top-level)`);

// Trigger load-more so the request template is captured.
for (let s = 0; s < 4 && !template; s++) { await page.mouse.wheel(0, 5000); await page.waitForTimeout(1000); }
const moreBtn = page.locator('[data-hook="load-more-button"]').first();
if (await moreBtn.count().catch(() => 0)) { await moreBtn.scrollIntoViewIfNeeded().catch(() => {}); await moreBtn.click().catch(() => {}); }
for (let i = 0; i < 40 && !template; i++) await page.waitForTimeout(500);
if (!template) { log('FATAL: could not capture getFilteredProducts template'); await browser.close(); process.exit(1); }
log('   captured request template ✓');
const hdrs = { ...template.headers };
delete hdrs['content-length']; delete hdrs['host']; delete hdrs[':authority'];

// ---- 2/3. pull products per category ----
async function fetchCategory(catId) {
  const items = [];
  let offset = 0, total = Infinity;
  while (offset < total) {
    const body = JSON.parse(template.postData);
    body.variables.mainCollectionId = catId;
    body.variables.offset = offset;
    body.variables.limit = PAGE_SIZE;
    const res = await ctx.request.post(template.url, { headers: hdrs, data: body });
    if (res.status() !== 200) { log(`     ! HTTP ${res.status()} for ${catId} @${offset}`); break; }
    const j = await res.json().catch(() => null);
    const md = j?.data?.catalog?.category?.productsWithMetaData;
    if (!md) { if (j?.errors) log('     ! gql errors', JSON.stringify(j.errors).slice(0, 160)); break; }
    total = md.totalCount;
    for (const p of md.list) items.push(p);
    offset += PAGE_SIZE;
    if (LIMIT_PER_CAT && items.length >= LIMIT_PER_CAT) break;
    await new Promise((r) => setTimeout(r, 150)); // be polite
  }
  return { total, items };
}

const products = new Map(); // productId -> normalized product
const cats = [...catMap.values()];
log(`\n-> fetching products for ${cats.length} categories...`);
let ci = 0;
for (const c of cats) {
  ci++;
  const { total, items } = await fetchCategory(c.id);
  if (items.length) log(`   [${ci}/${cats.length}] ${c.slug.padEnd(28).slice(0, 28)} ${items.length}/${total}`);
  for (const p of items) {
    let prod = products.get(p.id);
    if (!prod) {
      prod = {
        wixId: p.id,
        sku: p.sku || null,
        name_ka: (p.name || '').trim(),
        slug: p.urlPart || null,
        retail_price: typeof p.price === 'number' ? p.price : Number(p.price) || 0,
        compare_price: p.comparePrice || 0,
        formatted_price: p.formattedPrice || null,
        currency: p.currency || 'GEL',
        ribbon: p.ribbon || '',
        in_stock: p.isInStock !== false,
        inventory_status: p.inventory?.status || null,
        track_inventory: !!p.isTrackingInventory,
        product_type: p.productType || 'physical',
        url: `${ORIGIN}/product-page/${p.urlPart}`,
        images: (p.media || [])
          .filter((m) => m && m.url)
          .map((m, idx) => ({
            file: m.url, // e.g. 8a61cf_xxx~mv2.jpeg
            src: `https://static.wixstatic.com/media/${m.url}`, // high-res original
            width: m.width || null,
            height: m.height || null,
            order: idx,
          })),
        categorySlugs: [],
        categoryIds: [],
      };
      products.set(p.id, prod);
    }
    if (!prod.categoryIds.includes(c.id)) { prod.categoryIds.push(c.id); prod.categorySlugs.push(c.slug); }
  }
}

await browser.close();

// ---- 4. assemble catalog.json ----
const categoriesOut = cats.map((c) => ({
  source_id: c.id,
  name_ka: c.name,
  slug: c.slug,
  parent_source_id: c.parentId,
  sort_order: c.sortOrder,
  is_active: c.visible,
  in_header: headerSlugs.has(c.slug),
  header_order: headerSlugs.get(c.slug)?.order ?? null,
  header_label: headerSlugs.get(c.slug)?.label ?? null,
}));

const productsOut = [...products.values()].map((p) => ({
  ...p,
  // primary category = first header category it belongs to, else first category
  primary_category_id: p.categoryIds.find((id) => {
    const c = catMap.get(id); return c && headerSlugs.has(c.slug);
  }) || p.categoryIds[0] || null,
}));

const imageCount = productsOut.reduce((n, p) => n + p.images.length, 0);
const catalog = {
  source: 'https://www.roni5.ge',
  scraped_at: new Date().toISOString(),
  stats: {
    categories: categoriesOut.length,
    top_level_categories: categoriesOut.filter((c) => !c.parent_source_id).length,
    header_categories: categoriesOut.filter((c) => c.in_header).length,
    products: productsOut.length,
    images: imageCount,
    products_without_sku: productsOut.filter((p) => !p.sku).length,
    products_without_images: productsOut.filter((p) => !p.images.length).length,
  },
  categories: categoriesOut,
  products: productsOut,
};
writeFileSync(`${OUT_DIR}catalog.json`, JSON.stringify(catalog, null, 2));
log('\n=== DONE ===');
log(JSON.stringify(catalog.stats, null, 2));
log('-> wrote', `${OUT_DIR}catalog.json`);
