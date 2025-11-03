<?php
/**
 * Example PHP file showing how to embed the builder output files
 * and handle component management with API calls
 */

// Configuration
$apiBaseUrl = ''; // Use relative path for mock API (empty string = same domain)
$builderOutputPath = './output/'; // Path to builder output files

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - PHP Integration Example</title>
    
    <!-- Include builder output CSS -->
    <link rel="stylesheet" href="<?php echo $builderOutputPath; ?>landing-page.css">
    
    <style>
        /* Additional PHP page styles if needed */
        body {
            background-color: #f5f5f5;
        }
        
        /* PHP wrapper styles */
        .php-wrapper {
            background: #fff;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="php-wrapper">
        <!-- 
            Embed the HTML structure from builder output
            In production, you might want to include just the body content
        -->
        <?php include $builderOutputPath . 'landing-page.html'; ?>
    </div>

    <!-- 
        Set API base URL for api-handler.js
        This should be set before api-handler.js is loaded
    -->
    <script>
        // Set API base URL for api-handler (using mock API)
        window.API_BASE_URL = '<?php echo $apiBaseUrl; ?>mock-api.php';
        
        // Optional: Configure component endpoints mapping
        window.API_ENDPOINTS = {
            products: {
                all: '/api/products/all',
                sweety: '/api/products/sweety',
                moony: '/api/products/moony',
                huggies: '/api/products/huggies',
                merries: '/api/products/merries'
            },
            vouchers: {
                all: '/api/vouchers/all',
                'free-ship': '/api/vouchers/free-ship',
                discount: '/api/vouchers/discount',
                gift: '/api/vouchers/gift'
            }
        };

        // Optional: Component management functions
        window.LandingPageComponents = {
            /**
             * Get component data from PHP/Server
             * This can be used as alternative to API calls
             */
            getComponentData: function(type, endpoint) {
                // Example: Call PHP endpoint instead of external API
                return fetch('<?php echo $apiBaseUrl; ?>' + endpoint)
                    .then(response => response.json());
            },

            /**
             * Log component interactions (for analytics)
             */
            logInteraction: function(componentType, action, data) {
                // Send to your analytics/backend
                console.log('Component interaction:', {
                    component: componentType,
                    action: action,
                    data: data,
                    timestamp: new Date().toISOString()
                });
                
                // Example: Send to PHP endpoint
                // fetch('/api/analytics/log', {
                //     method: 'POST',
                //     headers: {'Content-Type': 'application/json'},
                //     body: JSON.stringify({component: componentType, action: action, data: data})
                // });
            }
        };
    </script>

    <!-- 
        Include builder output JS (contains tab management and render functions)
        Must be loaded before api-handler.js
    -->
    <script src="<?php echo $builderOutputPath; ?>landing-page.js"></script>
    
    <!-- 
        Include API handler JS (listens to events and calls APIs)
        Must be loaded after landing-page.js
    -->
    <script src="api-handler.js"></script>

    <!-- 
        Optional: Additional PHP-specific JavaScript
        You can add more custom logic here
    -->
    <script>
        /**
         * Website chính Event Handlers
         * These handlers receive events from builder output and handle them appropriately
         */

        // 1. Listen to componentRendered event
        // Website chính nhận biết component type và load giao diện tương ứng
        document.addEventListener('componentRendered', function(event) {
            const { type, data } = event.detail;
            
            console.log('Website chính: Component rendered', {
                type: type,
                itemCount: data?.items?.length || 0
            });

            // Website chính có thể:
            // - Load custom UI templates based on component type
            // - Apply custom styling based on type
            // - Initialize additional features for specific component types
            // - Track analytics
            
            switch(type) {
                case 'product':
                    handleProductComponentRendered(data);
                    break;
                case 'voucher':
                    handleVoucherComponentRendered(data);
                    break;
                default:
                    console.log('Unknown component type:', type);
            }
        });

        // 2. Listen to addToCart event
        // Website chính xử lý logic thêm vào giỏ hàng
        document.addEventListener('addToCart', function(event) {
            const { productId, productData } = event.detail;
            
            console.log('Website chính: Add to cart event received', {
                productId: productId,
                productData: productData
            });

            // Website chính xử lý add to cart logic ở đây
            // Ví dụ: Gọi API, update cart UI, show notification, etc.
            handleAddToCart(productId, productData);
        });

        // 3. Listen to voucherCopy event (optional)
        document.addEventListener('voucherCopy', function(event) {
            const { code } = event.detail;
            
            console.log('Website chính: Voucher copy event received', {
                code: code
            });

            // Website chính có thể xử lý thêm logic khi copy voucher
            // Ví dụ: Track analytics, show custom notification, etc.
            handleVoucherCopy(code);
        });

        // ============================================
        // Handler Functions - Website chính Implementation
        // ============================================

        /**
         * Handle product component rendered
         * Website chính có thể load custom UI, styling, hoặc features
         */
        function handleProductComponentRendered(data) {
            // Example: Apply custom styling based on product type
            const productCards = document.querySelectorAll('[data-component-type="product"]');
            
            // Example: Initialize lazy loading for images
            if (typeof LazyLoad !== 'undefined') {
                new LazyLoad({
                    elements_selector: '.product-image'
                });
            }

            // Example: Track analytics
            if (window.LandingPageComponents) {
                window.LandingPageComponents.logInteraction('product', 'component_rendered', {
                    itemCount: data?.items?.length || 0
                });
            }

            // Website chính có thể load custom templates từ PHP/Server dựa trên type
            // loadCustomProductTemplate('product', data);
        }

        /**
         * Handle voucher component rendered
         */
        function handleVoucherComponentRendered(data) {
            // Example: Apply custom styling or features
            const voucherCards = document.querySelectorAll('[data-component-type="voucher"]');
            
            // Example: Track analytics
            if (window.LandingPageComponents) {
                window.LandingPageComponents.logInteraction('voucher', 'component_rendered', {
                    itemCount: data?.items?.length || 0
                });
            }
        }

        /**
         * Handle add to cart - Website chính Implementation
         * Đây là nơi website chính xử lý logic thêm vào giỏ hàng
         */
        function handleAddToCart(productId, productData) {
            // Example 1: Gọi API của website chính
            fetch('/api/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // 'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({
                    productId: productId,
                    quantity: 1,
                    productData: productData
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Update cart UI
                    updateCartUI(result.cart);
                    
                    // Show success notification
                    showNotification('Sản phẩm đã được thêm vào giỏ hàng!', 'success');
                } else {
                    showNotification('Có lỗi xảy ra: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Add to cart error:', error);
                showNotification('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
            });

            // Example 2: Hoặc sử dụng cart system của website chính
            // if (window.CartSystem) {
            //     window.CartSystem.add(productId, productData);
            // }

            // Example 3: Hoặc chỉ track analytics
            // trackEvent('add_to_cart', { productId, productData });
        }

        /**
         * Handle voucher copy - Website chính Implementation (optional)
         */
        function handleVoucherCopy(code) {
            // Website chính có thể:
            // - Show custom notification
            // - Track analytics
            // - Apply voucher automatically
            // - etc.
            
            if (window.LandingPageComponents) {
                window.LandingPageComponents.logInteraction('voucher', 'copy', {
                    code: code
                });
            }

            // Example: Show custom notification
            // showNotification('Đã sao chép mã voucher: ' + code, 'success');
        }

        /**
         * Helper: Update cart UI (example)
         */
        function updateCartUI(cartData) {
            // Update cart count, price, etc.
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = cartData.totalItems || 0;
            }
        }

        /**
         * Helper: Show notification (example)
         */
        function showNotification(message, type) {
            // Use your existing notification system
            // Example: toast notification, alert, etc.
            console.log(`[${type.toUpperCase()}] ${message}`);
            // alert(message); // Simple fallback
        }

        // ============================================
        // Tab Activation Handler (existing)
        // ============================================
        
        // Example: Listen to tab activations for custom PHP-side handling
        document.addEventListener('tabActivated', function(event) {
            const { type, apiEndpoint, tabId } = event.detail;
            
            // Log to PHP backend if needed
            console.log('PHP: Tab activated', {
                type: type,
                endpoint: apiEndpoint,
                tabId: tabId
            });
            
            // Example: Update page URL without reload
            // if (window.history && window.history.pushState) {
            //     const newUrl = window.location.pathname + '?tab=' + tabId;
            //     window.history.pushState({tab: tabId}, '', newUrl);
            // }
        });

        // Example: Handle page load with URL parameters
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                // Activate specific tab if provided in URL
                const tabElement = document.querySelector(`[data-tab-id="${tabParam}"]`);
                if (tabElement) {
                    tabElement.click();
                }
            }
        });
    </script>

    <?php
    /**
     * Example PHP function to get component data
     * This shows how PHP can manage component data
     */
    function getComponentData($type, $endpoint) {
        // Example: Fetch from database or external API
        // This is just a mockup
        
        if ($type === 'product') {
            // In real implementation, query database or call external API
            return [
                'items' => [
                    [
                        'id' => 1,
                        'title' => 'Combo 2 Tã quần Sweety cỡ XL 40 miếng',
                        'image' => 'https://via.placeholder.com/300x300',
                        'price' => 278000,
                        'originalPrice' => 310000,
                        'discount' => true,
                        'rating' => 4.5,
                        'reviewCount' => 1250,
                        'sales' => 50000
                    ],
                    // ... more products
                ],
                'pagination' => [
                    'page' => 1,
                    'total' => 100,
                    'perPage' => 20
                ]
            ];
        } elseif ($type === 'voucher') {
            return [
                'items' => [
                    [
                        'id' => 1,
                        'title' => 'Giảm giá 20%',
                        'code' => 'SAVE20',
                        'description' => 'Áp dụng cho đơn hàng từ 500k',
                        'value' => 'Giảm 20%',
                        'expiryDate' => date('Y-m-d', strtotime('+30 days'))
                    ],
                    // ... more vouchers
                ],
                'pagination' => [
                    'page' => 1,
                    'total' => 10,
                    'perPage' => 10
                ]
            ];
        }
        
        return ['items' => [], 'pagination' => []];
    }

    /**
     * Example: Output initial component data as JSON
     * This can be used for server-side rendering or initial load
     */
    // Uncomment to output initial data
    // $initialProductData = getComponentData('product', '/api/products/all');
    // $initialVoucherData = getComponentData('voucher', '/api/vouchers/all');
    ?>
</body>
</html>
