<?php
// crawler.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../config/config.php';

// Kontrolli API võtit
$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Funktsioon veebilehe kaapimiseks
function crawlPage($url) {
    $html = @file_get_contents($url);
    if ($html === FALSE) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Näited e-poodide struktuurist. Tuleb kohandada vastavalt sihtlehele.
    // Oletame, et tooted on <div class="product">, nimi on <h2>, hind on <span class="price"> ja kategooria on <a class="category">

    $products = [];
    $productNodes = $xpath->query("//div[contains(@class, 'product')]");
    foreach ($productNodes as $product) {
        $nameNode = $xpath->query(".//h2", $product)->item(0);
        $priceNode = $xpath->query(".//span[contains(@class, 'price')]", $product)->item(0);
        $categoryNode = $xpath->query(".//a[contains(@class, 'category')]", $product)->item(0);

        $products[] = [
            'name' => $nameNode ? trim($nameNode->nodeValue) : 'N/A',
            'price' => $priceNode ? trim($priceNode->nodeValue) : 'N/A',
            'category' => $categoryNode ? trim($categoryNode->nodeValue) : 'N/A'
        ];
    }

    return $products;
}

// Loe URL-id failist
$urls = file(URLS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$urls) {
    echo json_encode(['error' => 'No URLs found']);
    exit();
}

$allProducts = [];
foreach ($urls as $url) {
    $products = crawlPage($url);
    if ($products !== null) {
        $allProducts = array_merge($allProducts, $products);
    }
}

// Analüüsi kategooriaid
$categories = [];
foreach ($allProducts as $product) {
    if (!isset($categories[$product['category']])) {
        $categories[$product['category']] = 0;
    }
    $categories[$product['category']]++;
}

// Sorteeri populaarsuse järgi
arsort($categories);

// Tagasta JSON
echo json_encode([
    'products' => $allProducts,
    'categories' => $categories
]);
?>
