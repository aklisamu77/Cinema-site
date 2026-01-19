<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class ElCinemaSearchService
{
    public function search(string $q)
{
    $cookieJar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'elcinema_cookiejar.txt';

    $commonOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_ENCODING       => '',

        // Persist cookies across requests
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,

        // Browser-like headers
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ar,en-US;q=0.9,en;q=0.8',
            'Connection: keep-alive',
        ],
    ];

    // 1) Warm up: visit homepage to get cookies/session
    $ch = curl_init('https://elcinema.com/');
    curl_setopt_array($ch, $commonOpts);
    $home = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return "cURL Error (warmup): $err";
    }

    // 2) Now call search with same cookies + referer
    $url = 'https://elcinema.com/search/?q=' . rawurlencode($q);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($commonOpts[CURLOPT_HTTPHEADER], [
        'Referer: https://elcinema.com/',
        'Upgrade-Insecure-Requests: 1',
    ]));

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return "cURL Error (search): $error";
    }

    if (($info['http_code'] ?? 0) >= 400) {
        return "HTTP {$info['http_code']} from {$info['url']}\n\n" . $response;
    }

    //return ($response);
    return $this->parseElcinemaResults($response);
}

public function parseElcinemaResults(string $html): array
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    // Get all main <li> inside the results grid
    $items = $xpath->query(
        "//ul[contains(@class,'small-block-grid-2') 
            and contains(@class,'medium-block-grid-3') 
            and contains(@class,'large-block-grid-6')]/li"
    );

    $results = [];

    foreach ($items as $li) {

        // image (data-src OR src)
        $imgNode = $xpath->query(".//div[contains(@class,'thumbnail-wrapper')]//img", $li)->item(0);
        $img = null;

        if ($imgNode) {
            $img = $imgNode->getAttribute("data-src") ?: $imgNode->getAttribute("src");
        }

        // title + link (from the text-center link)
        $titleNode = $xpath->query(".//ul[contains(@class,'text-center')]//a", $li)->item(0);

        $title = $titleNode ? trim($titleNode->textContent) : null;
        $link  = $titleNode ? $titleNode->getAttribute("href") : null;

        // make link full URL
        if ($link && str_starts_with($link, "/")) {
            $link = "https://elcinema.com" . $link;
        }

        // ensure image is full url (it already is usually)
        if ($img && str_starts_with($img, "/")) {
            $img = "https://elcinema.com" . $img;
        }

        if ($title && $link) {
            $results[] = [
                "title" => $title,
                "link"  => $link,
                "image" => $img,
            ];
        }
    }

    return $results;
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
