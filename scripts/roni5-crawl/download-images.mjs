// Download every product image referenced by catalog.json into
// database/seeders/data/roni5-catalog/images/, then write the local relative
// path back into each image record. Resumable: existing files are skipped.
import { readFileSync, writeFileSync, mkdirSync, existsSync, statSync } from 'node:fs';

const DIR = new URL('../../database/seeders/data/roni5-catalog/', import.meta.url).pathname;
const IMG_DIR = `${DIR}images/`;
mkdirSync(IMG_DIR, { recursive: true });

const catalog = JSON.parse(readFileSync(`${DIR}catalog.json`, 'utf8'));
const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
const CONCURRENCY = 10;

// filename = sanitized Wix file id (unique, ASCII): "8a61cf_x~mv2.jpg" -> "8a61cf_x_mv2.jpg"
const fileNameFor = (wixFile) => wixFile.replace(/[~/]/g, '_');

// Build a flat, de-duplicated job list (same image can appear on >1 product).
const jobs = new Map(); // localName -> src
for (const p of catalog.products) {
  for (const img of p.images) {
    const name = fileNameFor(img.file);
    img.local = `images/${name}`;
    if (!jobs.has(name)) jobs.set(name, img.src);
  }
}
const list = [...jobs.entries()];
console.log(`${catalog.products.length} products, ${list.length} unique images to ensure`);

let ok = 0, skip = 0, fail = 0, done = 0;
const failed = [];

async function fetchOne([name, src]) {
  const dest = IMG_DIR + name;
  if (existsSync(dest) && statSync(dest).size > 200) { skip++; return; }
  try {
    const res = await fetch(src, { headers: { 'User-Agent': UA, Referer: 'https://www.roni5.ge/' } });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const buf = Buffer.from(await res.arrayBuffer());
    if (buf.length < 200) throw new Error('too small');
    writeFileSync(dest, buf);
    ok++;
  } catch (e) {
    fail++; failed.push({ name, src, err: e.message });
  }
}

// simple promise pool
let idx = 0;
async function worker() {
  while (idx < list.length) {
    const job = list[idx++];
    await fetchOne(job);
    if (++done % 100 === 0) console.log(`  ${done}/${list.length} (ok=${ok} skip=${skip} fail=${fail})`);
  }
}
await Promise.all(Array.from({ length: CONCURRENCY }, worker));

// Persist the local paths back into catalog.json.
writeFileSync(`${DIR}catalog.json`, JSON.stringify(catalog, null, 2));
console.log(`\nDone. downloaded=${ok} skipped=${skip} failed=${fail}`);
if (failed.length) {
  writeFileSync(`${DIR}image-failures.json`, JSON.stringify(failed, null, 2));
  console.log('  failures -> image-failures.json (first 3:', JSON.stringify(failed.slice(0, 3)), ')');
}
