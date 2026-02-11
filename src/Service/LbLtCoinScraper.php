<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LbLtCoinScraper
{
    private const BASE_URL = 'https://www.lb.lt';
    private const LIST_URL = self::BASE_URL . '/lt/kolekcines-ir-progines-monetos-sarasas';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Scrape all coin listing pages and return an array of coin detail URLs.
     *
     * @return list<array{name: string, url: string, imageUrl: string|null}>
     */
    public function scrapeListings(): array
    {
        $coins = [];
        $page = 1;

        while (true) {
            $url = self::LIST_URL . ($page > 1 ? '?page=' . $page : '');
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $items = $crawler->filter('.item.coin_item');

            if ($items->count() === 0) {
                break;
            }

            $items->each(function (Crawler $node) use (&$coins): void {
                $link = $node->filter('a.full_window');
                if ($link->count() === 0) {
                    return;
                }

                $name = trim($node->attr('title') ?? '');
                $href = $link->attr('href') ?? '';

                if ($href === '' || $name === '') {
                    return;
                }

                $fullUrl = $this->normalizeUrl($href);

                $imageUrl = null;
                $img = $node->filter('img.coin_front');
                if ($img->count() > 0) {
                    $src = $img->attr('src') ?? '';
                    if ($src !== '') {
                        $imageUrl = $this->normalizeUrl($src);
                    }
                }

                $coins[] = [
                    'name' => $name,
                    'url' => $fullUrl,
                    'imageUrl' => $imageUrl,
                ];
            });

            $page++;
        }

        return $coins;
    }

    /**
     * Scrape a single coin detail page.
     *
     * @return array{
     *     description: string|null,
     *     denomination: string|null,
     *     metal: string|null,
     *     diameterMm: float|null,
     *     weightGrams: float|null,
     *     mintage: int|null,
     *     year: int|null,
     *     imageUrl: string|null
     * }
     */
    public function scrapeDetail(string $url): array
    {
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $result = [
            'description' => null,
            'denomination' => null,
            'metal' => null,
            'diameterMm' => null,
            'weightGrams' => null,
            'mintage' => null,
            'year' => null,
            'imageUrl' => null,
        ];

        // High-res image from detail page
        $detailImg = $crawler->filter('.coin-left .coin_pic img');
        if ($detailImg->count() > 0) {
            $src = $detailImg->first()->attr('src') ?? '';
            if ($src !== '') {
                $result['imageUrl'] = $this->normalizeUrl($src);
            }
        }

        // Description from .coin-content .text
        $descBlock = $crawler->filter('.container-fluid.coin-content .text');
        if ($descBlock->count() > 0) {
            $paragraphs = $descBlock->filter('p');
            $descParts = [];
            $paragraphs->each(function (Crawler $p) use (&$descParts): void {
                $text = trim($p->text('', true));
                if ($text !== '' && $text !== "\u{00A0}") {
                    $descParts[] = $text;
                }
            });
            if ($descParts !== []) {
                $result['description'] = implode("\n\n", $descParts);
            }
        }

        // Metadata from .info.col-xs-12 .text
        $infoBlock = $crawler->filter('.info.col-xs-12 .text');
        if ($infoBlock->count() > 0) {
            $infoBlock->filter('div')->each(function (Crawler $div) use (&$result): void {
                $fullText = trim($div->text('', true));
                $valueNode = $div->filter('.value strong');

                if ($valueNode->count() === 0) {
                    return;
                }

                $value = trim($valueNode->text('', true));

                if (str_starts_with($fullText, 'Nominalas')) {
                    $result['denomination'] = $value;
                } elseif (str_starts_with($fullText, 'Metalas')) {
                    $result['metal'] = $value;
                } elseif (str_starts_with($fullText, 'Skersmuo')) {
                    $result['diameterMm'] = $this->parseFloat($value);
                } elseif (str_starts_with($fullText, 'Masė')) {
                    $result['weightGrams'] = $this->parseFloat($value);
                } elseif (str_starts_with($fullText, 'Tiražas')) {
                    $result['mintage'] = $this->parseMintage($value);
                } elseif (str_starts_with($fullText, 'Išleidimo data')) {
                    $result['year'] = $this->parseYear($value);
                }
            });
        }

        return $result;
    }

    private function fetchHtml(string $url): string
    {
        $response = $this->httpClient->request('GET', $url);

        return $response->getContent();
    }

    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL . $url;
        }

        return $url;
    }

    private function parseFloat(string $value): ?float
    {
        // Replace comma with dot for decimal, remove non-numeric chars except dot
        $cleaned = str_replace(',', '.', $value);
        $cleaned = (string) preg_replace('/[^0-9.]/', '', $cleaned);

        if ($cleaned === '') {
            return null;
        }

        return (float) $cleaned;
    }

    private function parseMintage(string $value): ?int
    {
        // Try to extract the first number from mintage text
        // e.g. "Bendras tiražas 0,5 mln. vnt." -> 500000
        // e.g. "3 000 vnt." -> 3000
        $lower = mb_strtolower($value);

        if (preg_match('/(\d[\d\s,.]*)\s*mln/u', $lower, $matches) === 1) {
            $num = str_replace([' ', ','], ['', '.'], $matches[1]);
            return (int) ((float) $num * 1_000_000);
        }

        if (preg_match('/(\d[\d\s,.]*)\s*tūkst/u', $lower, $matches) === 1) {
            $num = str_replace([' ', ','], ['', '.'], $matches[1]);
            return (int) ((float) $num * 1_000);
        }

        if (preg_match('/(\d[\d\s]*)\s*vnt/u', $lower, $matches) === 1) {
            $num = str_replace(' ', '', $matches[1]);
            return (int) $num;
        }

        // Fallback: try to extract any number
        if (preg_match('/(\d[\d\s]*)/u', $value, $matches) === 1) {
            $num = str_replace(' ', '', $matches[1]);
            if ($num !== '' && $num !== '0') {
                return (int) $num;
            }
        }

        return null;
    }

    private function parseYear(string $value): ?int
    {
        // e.g. "2025-12-22" -> 2025
        if (preg_match('/(\d{4})/', $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
