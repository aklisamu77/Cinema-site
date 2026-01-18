<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class ElCinemaSearchService
{
    public function search(string $q): array
    {
        $url = 'https://elcinema.com/search/?q=' . urlencode($q);

        [$status, $html] = $this->curlGet($url);
        dd([$status, $html] );
        // If blocked, return empty (or throw)
        if ($status !== 200 || !$html) {
            // You can log this instead of throwing:
            // logger()->warning('ElCinema blocked request', ['status' => $status, 'url' => $url, 'body' => mb_substr($html ?? '', 0, 300)]);
            return [];
            // or:
            // throw new RuntimeException("ElCinema request failed. HTTP: {$status}");
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $query = "//ul[contains(@class,'small-block-grid-2') and contains(@class,'medium-block-grid-3') and contains(@class,'large-block-grid-6')]//li//ul//li//a";
        $links = $xpath->query($query);

        $data = [];

        foreach ($links as $a) {
            $title = trim($a->textContent);
            $href  = trim($a->getAttribute('href'));
            if ($href === '') continue;

            if (!str_starts_with($href, 'http')) {
                $href = 'https://elcinema.com' . $href;
            }

            $data[] = [
                'title' => $title,
                'url'   => $href,
            ];
        }

        return $data;
    }

    /**
     * @return array{0:int,1:string|null}
     */
    private function curlGet(string $url): array
    {
        $cookieFile = storage_path('app/elcinema_cookie.txt');
        if (!is_dir(dirname($cookieFile))) {
            @mkdir(dirname($cookieFile), 0755, true);
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 25,

            // IMPORTANT: keep SSL verification ON
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,

            // Handle gzip/deflate automatically
            CURLOPT_ENCODING => '',

            // Cookies (some sites require them)
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,

            // Browser-ish headers
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,ar;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],

            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            CURLOPT_REFERER => 'https://elcinema.com/',
        ]);

        $html = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Optional: see real error
        if ($html === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return [0, "cURL error: " . $err];
        }

        curl_close($ch);

        return [$status, $html];
    }
}
