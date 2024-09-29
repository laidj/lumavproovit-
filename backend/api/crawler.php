<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once(__DIR__ . '/../config/config.php');

/*$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}*/

function crawlPage($url) {
    $html = @file_get_contents($url);
    if ($html === FALSE) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $products = [];

    $productNodes = $xpath->query("//div[contains(@class, 'product')]");

    foreach ($productNodes as $product) {
        $nameNode = $xpath->query(".//h2 | .//span[contains(@class, 'product-title')]", $product)->item(0);
        $priceNode = $xpath->query(".//span[contains(@class, 'price')]", $product)->item(0);
        $categoryNode = $xpath->query(".//a[contains(@class, 'category')]", $product)->item(0);

        if ($nameNode && $priceNode) {
            $products[] = [
                'name' => trim($nameNode->nodeValue),
                'price' => trim($priceNode->nodeValue),
                'category' => $categoryNode ? trim($categoryNode->nodeValue) : 'N/A'
            ];
        }
    }

    return $products;
}

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

$categories = [];
foreach ($allProducts as $product) {
    if (!isset($categories[$product['category']])) {
        $categories[$product['category']] = 0;
    }
    $categories[$product['category']]++;
}

arsort($categories);

echo json_encode([
    'products' => $allProducts,
    'categories' => $categories
]);
?>
