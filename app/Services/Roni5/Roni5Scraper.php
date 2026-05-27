<?php

namespace App\Services\Roni5;

use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class Roni5Scraper
{
    public function __construct(
        private readonly int $waitMs = 4000,
    ) {}

    /**
     * Fetch a page rendered with JavaScript and return a DomCrawler.
     */
    public function fetch(string $url): Crawler
    {
        $browser = Browsershot::url($url)
            ->setNodeBinary(trim(shell_exec('which node')) ?: '/opt/homebrew/bin/node')
            ->setNpmBinary(trim(shell_exec('which npm')) ?: '/opt/homebrew/bin/npm')
            ->windowSize(1440, 900)
            ->waitUntilNetworkIdle()
            ->dismissDialogs()
            ->timeout(60);

        $systemChrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
        if (is_executable($systemChrome)) {
            $browser->setChromePath($systemChrome);
        }

        $html = $browser->bodyHtml();

        return new Crawler($html);
    }

    /**
     * Extract product cards from a Wix category page.
     *
     * @return array<int, array{title:string, price:float, image:?string, url:?string}>
     */
    public function scrapeCategory(string $url): array
    {
        $crawler = $this->fetch($url);

        $products = [];

        // Wix Stores product cards: try common selectors used by the
        // Wix gallery widget. Fall back to anything that looks like
        // a product link with a price nearby.
        $candidates = [
            '[data-hook="product-list-grid-item"]',
            '[data-hook="product-list-item"]',
            '[data-hook="ProductsGalleryItem"]',
            '.gallery-tile, [class*="GalleryItem"]',
            'a[href*="/product-page/"]',
        ];

        foreach ($candidates as $sel) {
            $nodes = $crawler->filter($sel);
            if ($nodes->count() === 0) {
                continue;
            }
            $nodes->each(function (Crawler $node) use (&$products) {
                $product = $this->extractProduct($node);
                if ($product !== null) {
                    $products[] = $product;
                }
            });
            if ($products !== []) {
                break;
            }
        }

        return $products;
    }

    private function extractProduct(Crawler $node): ?array
    {
        $titleEl = $node->filter('[data-hook="product-item-name"], [data-hook="product-name"], h3, h4, [class*="ProductName"]')->first();
        $title = $titleEl->count() ? trim($titleEl->text()) : '';

        if ($title === '') {
            $linkEl = $node->filter('a[href*="/product-page/"]')->first();
            if ($linkEl->count() && $linkEl->attr('title')) {
                $title = trim($linkEl->attr('title'));
            }
        }

        if ($title === '') {
            return null;
        }

        $priceEl = $node->filter('[data-hook="product-item-price-to-pay"], [data-hook="price-range-from"], [data-hook="formatted-primary-price"], [class*="Price"], [class*="price"]')->first();
        $price = 0.0;
        if ($priceEl->count()) {
            $price = $this->parsePrice($priceEl->text());
        }

        $imageEl = $node->filter('img')->first();
        $image = $imageEl->count() ? ($imageEl->attr('src') ?: $imageEl->attr('data-src')) : null;

        $linkEl = $node->filter('a[href*="/product-page/"]')->first();
        $url = $linkEl->count() ? $linkEl->attr('href') : null;

        return [
            'title' => $title,
            'price' => $price,
            'image' => $image,
            'url' => $url,
        ];
    }

    private function parsePrice(string $raw): float
    {
        if (preg_match('/(\d+(?:[\.,]\d{1,2})?)/', $raw, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        return 0.0;
    }

    /**
     * Download an image and return its temp path (caller is responsible for cleanup
     * — passing the path to medialibrary's addMedia($path) will move/copy it).
     */
    public function downloadImage(string $url): ?string
    {
        try {
            $url = $this->normalizeImageUrl($url);
            $content = @file_get_contents($url);
            if ($content === false || strlen($content) < 200) {
                return null;
            }
            $ext = $this->guessExtension($url, $content);
            $path = tempnam(sys_get_temp_dir(), 'roni5-scrape-');
            $finalPath = $path . '.' . $ext;
            rename($path, $finalPath);
            file_put_contents($finalPath, $content);
            return $finalPath;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Wix's gallery widget hands us a transformed thumbnail URL
     * (https://static.wixstatic.com/media/{file}.jpg/v1/fill/w_277,h_277,...).
     * Strip the /v1/... segment so we download the high-res original
     * the CDN serves at the base path.
     */
    private function normalizeImageUrl(string $url): string
    {
        return preg_replace(
            '#(https?://static\.wixstatic\.com/media/[^/]+)/v1/.+$#',
            '$1',
            $url,
        ) ?? $url;
    }

    private function guessExtension(string $url, string $content): string
    {
        if (preg_match('/\.(jpe?g|png|webp|gif)(?:[?#]|$)/i', $url, $m)) {
            return strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        }
        $info = @getimagesizefromstring($content);
        return match ($info['mime'] ?? '') {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}

