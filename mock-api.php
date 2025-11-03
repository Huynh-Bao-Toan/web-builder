<?php
/**
 * Mock API Endpoint
 * Serves fake data for testing without external API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request path - handle both direct calls and calls with path appended
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Check if endpoint is in query string (fallback method)
$endpoint = $_GET['endpoint'] ?? null;

if (!$endpoint) {
    // Parse from URL path
    // Handle cases like: /mock-api.php/api/products/huggies
    $pathParts = explode('/', trim($path, '/'));
    
    // Find 'api' in the path
    $apiIndex = array_search('api', $pathParts);
    if ($apiIndex !== false && isset($pathParts[$apiIndex + 1])) {
        $resource = $pathParts[$apiIndex + 1]; // 'products' or 'vouchers'
        $identifier = $pathParts[$apiIndex + 2] ?? 'all';
    } else {
        // Try alternative parsing
        $pathParts = array_filter($pathParts);
        $pathParts = array_values($pathParts);
        if (count($pathParts) >= 3) {
            $resource = $pathParts[1];
            $identifier = $pathParts[2];
        } else {
            $resource = null;
            $identifier = 'all';
        }
    }
} else {
    // Parse from query parameter
    $endpointParts = explode('/', trim($endpoint, '/'));
    $resource = $endpointParts[1] ?? null;
    $identifier = $endpointParts[2] ?? 'all';
}

// Mock data for products
function getMockProducts($brand = 'all') {
    $products = [];
    
    $brandProducts = [
        'all' => ['Sweety', 'Moony Natural', 'Huggies', 'Merries'],
        'sweety' => ['Sweety'],
        'moony' => ['Moony Natural'],
        'huggies' => ['Huggies'],
        'merries' => ['Merries']
    ];
    
    $brandNames = $brandProducts[$brand] ?? $brandProducts['all'];
    
    $productTemplates = [
        ['Tã quần', 'XL', 40, 278000, 310000],
        ['Tã dán', 'L', 54, 320000, 360000],
        ['Tã quần', 'M', 64, 290000, 330000],
        ['Tã dán', 'XL', 48, 350000, 390000],
        ['Combo 2 Tã quần', 'XL', 40, 540000, 620000],
        ['Tã quần', 'L', 48, 310000, 350000],
        ['Tã dán', 'M', 72, 270000, 300000],
        ['Combo 3 Tã quần', 'L', 48, 900000, 1050000],
        ['Tã quần', 'XL', 52, 380000, 420000],
        ['Tã dán', 'XL', 44, 340000, 380000],
        ['Tã quần', 'L', 56, 330000, 370000],
        ['Tã dán', 'M', 68, 280000, 320000],
        ['Combo 2 Tã dán', 'L', 54, 600000, 720000],
        ['Tã quần', 'XL', 46, 360000, 400000],
        ['Tã dán', 'XL', 50, 370000, 410000],
        ['Tã quần', 'M', 70, 260000, 290000],
        ['Combo 2 Tã quần', 'L', 48, 580000, 660000],
        ['Tã dán', 'XL', 42, 345000, 385000],
        ['Tã quần', 'L', 58, 340000, 380000],
        ['Tã dán', 'M', 74, 275000, 310000]
    ];
    
    foreach ($brandNames as $brandName) {
        foreach ($productTemplates as $index => $template) {
            $id = (array_search($brandName, $brandProducts['all']) * 20) + $index + 1;
            $title = $template[0] . ' ' . $brandName . ' cỡ ' . $template[1] . ' ' . $template[2] . ' miếng';
            
            $products[] = [
                'id' => $id,
                'title' => $title,
                'image' => 'https://via.placeholder.com/300x300?text=' . urlencode($brandName . ' ' . ($index + 1)),
                'price' => $template[3],
                'originalPrice' => $template[4],
                'discount' => true,
                'rating' => round(3.5 + (rand(0, 15) / 10), 1),
                'reviewCount' => rand(500, 5000),
                'sales' => rand(10000, 100000)
            ];
        }
    }
    
    // Randomize and limit to 20 items
    shuffle($products);
    return array_slice($products, 0, 20);
}

// Mock data for vouchers
function getMockVouchers($type = 'all') {
    $vouchers = [];
    
    $voucherTypes = [
        'all' => ['free-ship', 'discount', 'gift'],
        'free-ship' => ['free-ship'],
        'discount' => ['discount'],
        'gift' => ['gift']
    ];
    
    $types = $voucherTypes[$type] ?? $voucherTypes['all'];
    
    $voucherTemplates = [
        ['free-ship' => [
            ['title' => 'Miễn phí vận chuyển', 'code' => 'FREESHIP', 'value' => 'Miễn phí ship', 'desc' => 'Áp dụng cho đơn hàng từ 200k'],
            ['title' => 'Freeship toàn quốc', 'code' => 'SHIP0D', 'value' => 'Freeship 0đ', 'desc' => 'Áp dụng cho tất cả đơn hàng'],
            ['title' => 'Miễn phí vận chuyển nhanh', 'code' => 'FASTSHIP', 'value' => 'Freeship nhanh', 'desc' => 'Áp dụng cho đơn hàng từ 500k'],
        ]],
        ['discount' => [
            ['title' => 'Giảm giá 10%', 'code' => 'SAVE10', 'value' => 'Giảm 10%', 'desc' => 'Áp dụng cho đơn hàng từ 300k'],
            ['title' => 'Giảm giá 20%', 'code' => 'SAVE20', 'value' => 'Giảm 20%', 'desc' => 'Áp dụng cho đơn hàng từ 500k'],
            ['title' => 'Giảm giá 15%', 'code' => 'SAVE15', 'value' => 'Giảm 15%', 'desc' => 'Áp dụng cho đơn hàng từ 400k'],
            ['title' => 'Giảm giá 30%', 'code' => 'SAVE30', 'value' => 'Giảm 30%', 'desc' => 'Áp dụng cho đơn hàng từ 800k'],
        ]],
        ['gift' => [
            ['title' => 'Tặng khăn ướt', 'code' => 'GIFT01', 'value' => 'Quà tặng', 'desc' => 'Tặng 1 gói khăn ướt khi mua từ 500k'],
            ['title' => 'Tặng bỉm size S', 'code' => 'GIFT02', 'value' => 'Quà tặng', 'desc' => 'Tặng 1 gói bỉm size S khi mua từ 1 triệu'],
        ]]
    ];
    
    $id = 1;
    foreach ($types as $vType) {
        foreach ($voucherTemplates as $group) {
            if (isset($group[$vType])) {
                foreach ($group[$vType] as $voucher) {
                    $vouchers[] = [
                        'id' => $id++,
                        'title' => $voucher['title'],
                        'code' => $voucher['code'],
                        'description' => $voucher['desc'],
                        'value' => $voucher['value'],
                        'expiryDate' => date('Y-m-d', strtotime('+' . rand(7, 60) . ' days'))
                    ];
                }
            }
        }
    }
    
    return $vouchers;
}

// Route handling
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;

if ($resource === 'products') {
    $items = getMockProducts($identifier);
    $response = [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'total' => count($items) * 5, // Fake total pages
            'perPage' => $perPage
        ]
    ];
} elseif ($resource === 'vouchers') {
    $items = getMockVouchers($identifier);
    $response = [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'total' => count($items),
            'perPage' => $perPage
        ]
    ];
} else {
    http_response_code(404);
    $response = [
        'error' => 'Invalid API endpoint',
        'debug' => [
            'path' => $path,
            'resource' => $resource ?? 'null',
            'identifier' => $identifier ?? 'null',
            'endpoint_param' => $_GET['endpoint'] ?? 'not set'
        ]
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

