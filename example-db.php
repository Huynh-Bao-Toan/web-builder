<?php
/**
 * Example PHP file showing how to load builder assets from DATABASE
 * Demonstrates hybrid approach: DB for preview, CDN for production
 */

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'your_username',
    'password' => 'your_password'
];

// Load database loader
require_once './bundle/db-loader.php';

// Initialize database connection
try {
    $db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Configuration
$apiBaseUrl = ''; // Use relative path for mock API
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'production'; // 'preview' or 'production'
$version = isset($_GET['version']) ? $_GET['version'] : null; // Specific version for preview
$cdnBaseUrl = 'https://cdn.example.com/assets/'; // Your CDN base URL

// Initialize bundle loader
$bundleLoader = new BuilderBundleLoader($db, 'landing-page', $mode, $cdnBaseUrl);

// Load bundle from database
$bundle = $bundleLoader->loadBundle($version);

if (!$bundle) {
    die("Error: Bundle not found in database");
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - Database Load Example</title>
    
    <!-- Load CSS from DB or CDN -->
    <?php if ($bundle['mode'] === 'cdn'): ?>
        <!-- Production: Load from CDN -->
        <link rel="stylesheet" href="<?php echo htmlspecialchars($bundle['css']); ?>">
    <?php else: ?>
        <!-- Preview: Inline CSS from database -->
        <style><?php echo $bundle['css']; ?></style>
    <?php endif; ?>
    
    <style>
        /* Additional PHP page styles if needed */
        body {
            background-color: #f5f5f5;
        }
        
        .php-wrapper {
            background: #fff;
            min-height: 100vh;
        }

        /* Preview mode indicator */
        <?php if ($mode === 'preview'): ?>
        body::before {
            content: "PREVIEW MODE - Version: <?php echo htmlspecialchars($bundle['version']); ?>";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ff6b9d;
            color: white;
            padding: 5px;
            text-align: center;
            font-size: 12px;
            z-index: 10000;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="php-wrapper">
        <!-- Load HTML from DB or CDN -->
        <?php if ($bundle['mode'] === 'cdn'): ?>
            <!-- Production: Include from CDN (if HTML is external) -->
            <!-- Or use iframe/server-side include -->
            <!-- For this example, we'll use the DB content even in CDN mode for HTML -->
            <?php echo $bundle['html']; ?>
        <?php else: ?>
            <!-- Preview: Output HTML directly from database -->
            <?php echo $bundle['html']; ?>
        <?php endif; ?>
    </div>

    <!-- Set API base URL for api-handler.js -->
    <script>
        window.API_BASE_URL = '<?php echo $apiBaseUrl; ?>mock-api.php';
        
        // Bundle metadata (for debugging/analytics)
        window.BUNDLE_METADATA = {
            version: '<?php echo htmlspecialchars($bundle['version']); ?>',
            mode: '<?php echo htmlspecialchars($bundle['mode']); ?>',
            manifest: <?php echo json_encode($bundle['manifest']); ?>
        };

        // ============================================
        // Render Template Functions - Website chính cung cấp
        // ============================================

        window.renderProductItem = function(product) {
            const discountPercent = product.discount
                ? Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)
                : 0;

            const stars = "★".repeat(Math.floor(product.rating || 5));

            const productDataJson = JSON.stringify(product)
                .replace(/&/g, "&amp;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            const formatPrice = (price) => {
                return new Intl.NumberFormat("vi-VN", {
                    style: "currency",
                    currency: "VND",
                }).format(price);
            };

            const formatSales = (sales) => {
                if (sales >= 1000000) {
                    return (sales / 1000000).toFixed(1) + "M+";
                } else if (sales >= 1000) {
                    return (sales / 1000).toFixed(0) + "K+";
                }
                return sales + "+";
            };

            return `
                <div class="product-card" 
                     data-component-type="product" 
                     data-product-id="${product.id}" 
                     data-product-data='${productDataJson}'>
                    <img src="${product.image}" alt="${product.title}" class="product-image" />
                    <div class="product-info">
                        <h3 class="product-title">${product.title}</h3>
                        <div class="product-rating">
                            <span class="product-rating-stars">${stars}</span>
                            <span class="product-rating-text">(${product.reviewCount || 0})</span>
                        </div>
                        <div class="product-sales">Đã bán ${formatSales(product.sales)}</div>
                        <div class="product-price">${formatPrice(product.price)}</div>
                        ${discountPercent > 0 ? `<span class="product-discount">-${discountPercent}%</span>` : ""}
                        <div class="product-actions">
                            <button class="add-to-cart" data-product-id="${product.id}">
                                Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
            `;
        };

        window.renderVoucherItem = function(voucher) {
            const formatDate = (dateString) => {
                const date = new Date(dateString);
                return date.toLocaleDateString("vi-VN");
            };

            return `
                <div class="voucher-card" 
                     data-component-type="voucher" 
                     data-voucher-id="${voucher.id}">
                    <div class="voucher-header">
                        <div class="voucher-title">${voucher.title}</div>
                        <div class="voucher-code">${voucher.code}</div>
                    </div>
                    <div class="voucher-description">${voucher.description}</div>
                    <div class="voucher-value">${voucher.value}</div>
                    <div class="voucher-footer">
                        <div class="voucher-validity">
                            <span>📅</span>
                            <span>HSD: ${formatDate(voucher.expiryDate)}</span>
                        </div>
                        <button class="voucher-copy-btn" data-voucher-code="${voucher.code}">
                            Sao chép
                        </button>
                    </div>
                </div>
            `;
        };
    </script>

    <!-- Load JS from DB or CDN -->
    <?php if ($bundle['mode'] === 'cdn'): ?>
        <!-- Production: Load from CDN -->
        <script src="<?php echo htmlspecialchars($bundle['js']); ?>"></script>
    <?php else: ?>
        <!-- Preview: Inline JS from database -->
        <script><?php echo $bundle['js']; ?></script>
    <?php endif; ?>
    
    <!-- Include API handler -->
    <script src="api-handler.js"></script>

    <!-- Event handlers (same as example.php) -->
    <script>
        document.addEventListener('componentRendered', function(event) {
            const { type, data } = event.detail;
            console.log('Website chính: Component rendered', {
                type: type,
                itemCount: data?.items?.length || 0,
                bundleVersion: window.BUNDLE_METADATA.version
            });
        });

        document.addEventListener('addToCart', function(event) {
            const { productId, productData } = event.detail;
            console.log('Website chính: Add to cart', {
                productId: productId,
                productData: productData
            });
            // Implement your add to cart logic here
        });

        document.addEventListener('voucherCopy', function(event) {
            const { code } = event.detail;
            console.log('Website chính: Voucher copied', { code: code });
        });
    </script>
</body>
</html>

