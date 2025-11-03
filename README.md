# <span style="color: #FF6B6B;">Web Builder Output Files</span>

Mô phỏng các file output từ Angular Builder để nhúng vào website PHP.

## <span style="color: #4A70A9;">1. Cấu trúc Files</span>

### <span style="color: #8FABD4;">1.1 Output Files (từ Builder)</span>
- `output/landing-page.html` - Cấu trúc HTML với tabs, slider, voucher, product sections
- `output/landing-page.css` - Styling cho tất cả components
- `output/landing-page.js` - Logic quản lý tabs, slider, và render components

### <span style="color: #8FABD4;">1.2 Integration Files (cho PHP)</span>
- `api-handler.js` - Xử lý API calls khi nhận events từ builder JS
- `example.php` - File PHP mẫu để nhúng các file output từ file system
- `example-db.php` - File PHP mẫu để load assets từ database (minified)
- `mock-api.php` - Mock API endpoint để test (có thể dùng để reference cấu trúc API response)

### <span style="color: #8FABD4;">1.3 Minified Bundle Files (cho Database Storage)</span>
- `bundle/` - Thư mục chứa minified bundles
  - `landing-page.css.min.txt` - CSS minified (single-line TEXT)
  - `landing-page.html.min.txt` - HTML minified (single-line TEXT)
  - `landing-page.js.min.txt` - JS minified (single-line TEXT)
  - `bundle.json` - Bundle manifest với metadata (hash, size, version)
- `bundle/database-schema.sql` - SQL schema cho lưu bundles vào database
- `bundle/db-loader.php` - PHP class để load bundles từ database
- `bundle/save-to-db.php` - Script để import bundle.json vào database
- `minify-bundle.js` - Node.js script để minify files và tạo bundle.json

## <span style="color: #4A70A9;">2. Cách hoạt động</span>

### <span style="color: #8FABD4;">2.1 Event Flow</span>

```
User clicks tab 
  → landing-page.js detects active tab
  → Dispatches custom event 'tabActivated' với {type, apiEndpoint, tabId}
  → api-handler.js listens và gọi API tương ứng
  → API response được parse và render vào component
```

### <span style="color: #8FABD4;">2.2 Custom Event Structure</span>

Khi tab được active, `landing-page.js` sẽ dispatch event:

```javascript
{
  type: 'product' | 'voucher',
  apiEndpoint: '/api/products/sweety',
  tabId: 'sweety',
  timestamp: '2024-01-01T00:00:00.000Z'
}
```

### <span style="color: #8FABD4;">2.3 API Response Format</span>

API phải trả về JSON với format chuẩn:

#### <span style="color: #F4991A;">2.3.1 Product API Response:</span>
```json
{
  "items": [
    {
      "id": 1,
      "title": "Product title",
      "image": "https://...",
      "price": 278000,
      "originalPrice": 310000,
      "discount": true,
      "rating": 4.5,
      "reviewCount": 1250,
      "sales": 50000
    }
  ],
  "pagination": {
    "page": 1,
    "total": 100,
    "perPage": 20
  }
}
```

#### <span style="color: #F4991A;">2.3.2 Voucher API Response:</span>
```json
{
  "items": [
    {
      "id": 1,
      "title": "Giảm giá 20%",
      "code": "SAVE20",
      "description": "Áp dụng cho đơn hàng từ 500k",
      "value": "Giảm 20%",
      "expiryDate": "2024-02-01"
    }
  ],
  "pagination": {
    "page": 1,
    "total": 10,
    "perPage": 20
  }
}
```

**Note**: Xem `mock-api.php` để tham khảo implementation đầy đủ.

## <span style="color: #4A70A9;">3. Cách sử dụng trong PHP</span>

1. **Include files trong PHP:**
```php
<link rel="stylesheet" href="./output/landing-page.css">
<!-- HTML content -->
<script src="./output/landing-page.js"></script>
<script src="api-handler.js"></script>
```

2. **Set API base URL (trước khi load api-handler.js):**
```javascript
// Cho mock API (development)
window.API_BASE_URL = './mock-api.php';

// Hoặc cho real API (production)
window.API_BASE_URL = 'https://your-api-domain.com';
```

3. **Listen to events và implement handlers (xem `example.php`):**
```javascript
// Listen to componentRendered event
document.addEventListener('componentRendered', function(event) {
    // Handle based on component type
});

// Listen to addToCart event
document.addEventListener('addToCart', function(event) {
    // Implement add to cart logic
});
```

4. **Xem ví dụ đầy đủ trong `example.php`**

## <span style="color: #4A70A9;">3.1. Minified Bundle & Database Storage</span>

### <span style="color: #8FABD4;">3.1.1 Tạo Minified Bundle</span>

Để minify các file và tạo bundle JSON:

```bash
node minify-bundle.js
```

Script sẽ:
- Minify CSS, HTML, JS thành single-line text
- Tạo file `bundle/bundle.json` với manifest (hash, size, version)
- Lưu minified content vào `bundle/*.min.txt`

**Output:**
```
bundle/
  ├── landing-page.css.min.txt    (minified CSS - TEXT/CLOB)
  ├── landing-page.html.min.txt   (minified HTML - TEXT/CLOB)
  ├── landing-page.js.min.txt      (minified JS - TEXT/CLOB)
  └── bundle.json                  (manifest với metadata)
```

### <span style="color: #8FABD4;">3.1.2 Lưu Bundle vào Database</span>

**Bước 1: Tạo database schema**
```sql
-- Chạy script SQL
source bundle/database-schema.sql
```

**Bước 2: Import bundle vào database**
```bash
php bundle/save-to-db.php [version] [status]
# Ví dụ:
php bundle/save-to-db.php 1.0.0 draft
php bundle/save-to-db.php 1.0.0 published
```

Hoặc trong PHP code:
```php
require_once './bundle/db-loader.php';

$db = new PDO(/* your db config */);
$loader = new BuilderBundleLoader($db, 'landing-page');

// Save from bundle.json
$loader->saveBundleFromJson('./bundle/bundle.json', '1.0.0', 'published');

// Publish bundle
$loader->publishBundle('1.0.0');
```

### <span style="color: #8FABD4;">3.1.3 Load Bundle từ Database</span>

Xem `example-db.php` để xem implementation đầy đủ:

```php
require_once './bundle/db-loader.php';

$db = new PDO(/* your db config */);
$loader = new BuilderBundleLoader($db, 'landing-page', 'preview', $cdnBaseUrl);

// Load published bundle (production)
$bundle = $loader->loadBundle(null, 'published');

// Load specific version (preview)
$bundle = $loader->loadBundle('1.0.0');

// $bundle contains:
// - css, html, js: content hoặc CDN URL
// - mode: 'db' hoặc 'cdn'
// - version, manifest, hashes, sizes
```

**Usage trong PHP:**
```php
<?php if ($bundle['mode'] === 'cdn'): ?>
    <!-- Production: Load from CDN -->
    <link rel="stylesheet" href="<?php echo $bundle['css']; ?>">
    <script src="<?php echo $bundle['js']; ?>"></script>
<?php else: ?>
    <!-- Preview: Inline from database -->
    <style><?php echo $bundle['css']; ?></style>
    <script><?php echo $bundle['js']; ?></script>
<?php endif; ?>
```

## <span style="color: #4A70A9;">3.2. Hybrid Architecture (DB + CDN) - Khuyến nghị</span>

### <span style="color: #8FABD4;">3.2.1 Phương án lai (Recommended)</span>

**Kiến trúc:**
- **Database**: Lưu bản chuẩn (text thuần) + manifest JSON (hash, size, version)
- **CI/CD**: Khi "Publish" → job build & ghi file ra thư mục version → đẩy lên CDN
- **Runtime**:
  - **Production**: PHP chỉ nhúng URL asset tĩnh (có hash) từ CDN
  - **Preview**: PHP serve trực tiếp từ DB (endpoint `/preview/...`) cho người soạn nội dung

### <span style="color: #8FABD4;">3.2.2 Workflow</span>

```
1. Builder tạo output → minify → bundle.json
2. Lưu vào DB với status='draft'
3. Preview: Load từ DB (inline CSS/JS)
4. Publish: 
   - Build files → /assets/v1.0.0/
   - Upload lên CDN (S3/CloudFront)
   - Update DB: status='published', cdn_css_url, cdn_js_url
5. Production: Load từ CDN URLs
```

### <span style="color: #8FABD4;">3.2.3 Quy tắc nhanh để quyết định</span>

| Tình huống | Nên chọn |
|------------|----------|
| Traffic thật, tối ưu tốc độ/chi phí | Ghi file tĩnh + CDN |
| Preview/draft nội bộ | Load từ DB |
| Cần rollback nhanh | Dùng versioning + symlink hoặc CDN version path |
| Thay đổi theo người dùng (personalization runtime) | Load từ API/DB + hydrate, nhưng asset tĩnh vẫn nên ở CDN |

### <span style="color: #8FABD4;">3.2.4 Database Schema</span>

```sql
CREATE TABLE builder_bundles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'landing-page',
    -- Asset content stored as TEXT/CLOB
    css_content TEXT NOT NULL,
    html_content TEXT NOT NULL,
    js_content TEXT NOT NULL,
    -- Manifest metadata (JSON)
    manifest JSON,
    -- Hash for cache invalidation
    css_hash VARCHAR(32),
    html_hash VARCHAR(32),
    js_hash VARCHAR(32),
    -- File sizes
    css_size INT,
    html_size INT,
    js_size INT,
    -- CDN paths (for production)
    cdn_css_url VARCHAR(500) NULL,
    cdn_html_url VARCHAR(500) NULL,
    cdn_js_url VARCHAR(500) NULL,
    -- Status: draft, published, archived
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL
);
```

### <span style="color: #8FABD4;">3.2.5 Example: CI/CD Pipeline</span>

```bash
# 1. Build & minify
node minify-bundle.js

# 2. Save to DB (draft)
php bundle/save-to-db.php $VERSION draft

# 3. Build static files
mkdir -p assets/$VERSION
cp bundle/*.min.txt assets/$VERSION/

# 4. Upload to CDN
aws s3 sync assets/$VERSION s3://cdn.example.com/assets/$VERSION

# 5. Update CDN URLs in DB & publish
php -r "
require 'bundle/db-loader.php';
\$loader = new BuilderBundleLoader(\$db, 'landing-page');
\$loader->updateCdnUrls(
    '$VERSION',
    'https://cdn.example.com/assets/$VERSION/landing-page.css.min.txt',
    'https://cdn.example.com/assets/$VERSION/landing-page.html.min.txt',
    'https://cdn.example.com/assets/$VERSION/landing-page.js.min.txt'
);
\$loader->publishBundle('$VERSION');
"
```

## <span style="color: #4A70A9;">4. Components</span>

### <span style="color: #8FABD4;">4.1 Slider/Carousel</span>
- Auto-play mỗi 5 giây
- Navigation bằng arrows và dots
- Hỗ trợ touch/swipe (cần thêm logic)

### <span style="color: #8FABD4;">4.2 Product Tabs</span>
- Mỗi tab đại diện cho một thương hiệu
- Khi active, gọi API để lấy danh sách sản phẩm
- Render product cards với: image, title, rating, price, discount

### <span style="color: #8FABD4;">4.3 Voucher Tabs</span>
- Tương tự product tabs
- Render voucher cards với: title, code, description, value, expiry date

## <span style="color: #4A70A9;">5. Events System</span>

### <span style="color: #8FABD4;">5.1 Custom Events được emit từ Builder Output</span>

Builder output emit các events sau để website chính có thể xử lý:

#### <span style="color: #F4991A;">5.1.1 `componentRendered` Event</span>
Được emit khi component được render xong. Website chính có thể nhận biết component type và load giao diện tương ứng.

```javascript
document.addEventListener('componentRendered', function(event) {
    const { type, data } = event.detail;
    // type: 'product' | 'voucher'
    // data: Full API response data với items array
    
    // Website chính có thể:
    // - Load custom UI templates dựa trên type
    // - Apply custom styling
    // - Initialize features cho component type
});
```

#### <span style="color: #F4991A;">5.1.2 `addToCart` Event</span>
Được emit khi user click nút "Thêm vào giỏ". Website chính xử lý logic add to cart.

```javascript
document.addEventListener('addToCart', function(event) {
    const { productId, productData, type, timestamp } = event.detail;
    // productId: ID của sản phẩm
    // productData: Full product object với tất cả thông tin (id, title, image, price, etc.)
    // type: "product" (always)
    // timestamp: ISO timestamp của khi event được dispatch
    
    // Website chính xử lý ở đây:
    // - Gọi API add to cart
    // - Update cart UI
    // - Show notification
    // - etc.
});
```

#### <span style="color: #F4991A;">5.1.3 `voucherCopy` Event</span>
Được emit khi user click nút "Sao chép" voucher.

```javascript
document.addEventListener('voucherCopy', function(event) {
    const { code, type, timestamp } = event.detail;
    // code: Voucher code đã được copy
    // type: "voucher" (always)
    // timestamp: ISO timestamp của khi event được dispatch
    
    // Website chính có thể:
    // - Track analytics
    // - Show custom notification
    // - etc.
    
    // Note: Code đã được tự động copy vào clipboard (nếu browser hỗ trợ)
});
```

### <span style="color: #8FABD4;">5.2 Component Type Identification</span>

Mỗi component item có `data-component-type` attribute để website chính nhận biết:

```html
<!-- Product item -->
<div class="product-card" 
     data-component-type="product" 
     data-product-id="123"
     data-product-data='{"id":123,"title":"...","price":278000,...}'>
    <!-- ... -->
</div>

<!-- Voucher item -->
<div class="voucher-card" 
     data-component-type="voucher" 
     data-voucher-id="456">
    <!-- ... -->
</div>
```

**Data Attributes:**
- `data-component-type`: Loại component ("product" hoặc "voucher")
- `data-product-id`: ID của sản phẩm (chỉ có ở product items)
- `data-voucher-id`: ID của voucher (chỉ có ở voucher items)
- `data-product-data`: Full product object được encode JSON (chỉ có ở product items, HTML-safe encoded)
- `data-voucher-code`: Voucher code (chỉ có ở voucher copy button)

Website chính có thể query và xử lý dựa trên type:

```javascript
// Find all product components
const productCards = document.querySelectorAll('[data-component-type="product"]');

// Find all voucher components
const voucherCards = document.querySelectorAll('[data-component-type="voucher"]');
```

## <span style="color: #4A70A9;">6. Render Templates (Website chính cung cấp)</span>

**Lưu ý quan trọng**: Builder output **KHÔNG tự render** giao diện. Website chính **PHẢI cung cấp** template render functions.

### <span style="color: #8FABD4;">6.1 Template Functions bắt buộc</span>

Builder output sẽ tìm kiếm và sử dụng các functions sau từ website chính (phải được định nghĩa **TRƯỚC KHI** load `landing-page.js`):

#### <span style="color: #F4991A;">6.1.1 Item-level Template Functions (Khuyến nghị)</span>

```javascript
/**
 * Render một product item
 * @param {Object} product - Product object từ API
 * @returns {string} HTML string của product card
 */
window.renderProductItem = function(product) {
    // Your custom template here
    return `<div class="product-card">...</div>`;
};

/**
 * Render một voucher item
 * @param {Object} voucher - Voucher object từ API
 * @returns {string} HTML string của voucher card
 */
window.renderVoucherItem = function(voucher) {
    // Your custom template here
    return `<div class="voucher-card">...</div>`;
};
```

**Cách hoạt động:**
- Builder output sẽ gọi `window.renderProductItem()` cho mỗi product
- Builder output tự động map qua tất cả items và join HTML
- Builder output tự động attach event listeners

#### <span style="color: #F4991A;">6.1.2 Full Render Functions (Advanced)</span>

Nếu bạn muốn kiểm soát hoàn toàn việc render (bao gồm cả grid container):

```javascript
/**
 * Render toàn bộ products grid
 * @param {Object} data - Full data object với items array
 * @param {HTMLElement} targetElement - Grid container element (#products-grid)
 */
window.renderProducts = function(data, targetElement) {
    // Your custom rendering logic
    // Full control over container and items
    targetElement.innerHTML = data.items.map(item => {
        // Your template
    }).join('');
};

/**
 * Render toàn bộ vouchers grid
 * @param {Object} data - Full data object với items array
 * @param {HTMLElement} targetElement - Grid container element (#vouchers-grid)
 */
window.renderVouchers = function(data, targetElement) {
    // Your custom rendering logic
};
```

**Priority Order:**
1. Nếu có `window.renderProducts` → sử dụng full render function
2. Nếu có `window.renderProductItem` → sử dụng item-level function
3. Nếu không có → emit `renderProductsRequested` event và fallback về default render

### <span style="color: #8FABD4;">6.2 Requirements cho Template Functions</span>

Template functions **PHẢI** đảm bảo:

1. **Product Items:**
   - Có `data-component-type="product"` attribute
   - Có `data-product-id="${product.id}"` attribute
   - Có `data-product-data='...'` attribute (HTML-safe JSON encoded) - để extract product data cho add to cart event
   - Có button với class `add-to-cart` và `data-product-id` attribute

2. **Voucher Items:**
   - Có `data-component-type="voucher"` attribute
   - Có `data-voucher-id="${voucher.id}"` attribute
   - Có button với class `voucher-copy-btn` và `data-voucher-code` attribute

3. **Event Listeners:**
   - Builder output sẽ tự động attach listeners cho buttons nếu dùng `renderProductItem` hoặc `renderVoucherItem`
   - Nếu dùng full render functions, bạn có thể tự attach hoặc để builder output xử lý qua events

### <span style="color: #8FABD4;">6.3 Fallback & Events</span>

Nếu website chính không provide template functions:

- Builder output sẽ emit `renderProductsRequested` hoặc `renderVouchersRequested` event
- Website chính có thể listen và tự render
- Hoặc builder output sẽ sử dụng default render (for backward compatibility)

```javascript
// Listen to render request events
document.addEventListener('renderProductsRequested', function(event) {
    const { data, targetElement } = event.detail;
    // Your custom render logic
    event.preventDefault(); // Prevent default render
});
```

Xem `example.php` để xem implementation đầy đủ.

## <span style="color: #4A70A9;">7. Functions có sẵn</span>

### <span style="color: #8FABD4;">7.1 Global Functions (từ landing-page.js)</span>
- `window.updateLandingData(type, data)` - Cập nhật UI với data từ API
  - `type`: "product" hoặc "voucher"
  - `data`: Object với format `{items: [...], pagination: {...}}`
  - **Note**: Function này sẽ gọi template functions từ website chính

### <span style="color: #8FABD4;">7.2 Global Functions (từ api-handler.js)</span>
- `window.clearApiCache()` - Xóa cache API responses (useful khi cần refresh data)
- `window.triggerApiCall(type, endpoint, tabId)` - Trigger API call thủ công
  - `type`: "product" hoặc "voucher"
  - `endpoint`: API endpoint path (e.g., "/api/products/sweety")
  - `tabId`: Tab identifier (e.g., "sweety")

### <span style="color: #8FABD4;">7.3 Optional Objects (từ example.php)</span>
- `window.LandingPageComponents` - Object optional để quản lý components
  - `logInteraction(componentType, action, data)` - Log component interactions cho analytics

## <span style="color: #4A70A9;">8. Website chính Integration</span>

### <span style="color: #8FABD4;">8.1 Step-by-step Integration</span>

1. **Define Render Template Functions** (PHẢI làm trước khi load landing-page.js):
   ```javascript
   // Define BEFORE loading landing-page.js
   window.renderProductItem = function(product) { /* ... */ };
   window.renderVoucherItem = function(voucher) { /* ... */ };
   ```

2. **Set API Base URL**:
   ```javascript
   window.API_BASE_URL = './mock-api.php'; // or your API URL
   ```

3. **Load Builder Files**:
   ```html
   <script src="./output/landing-page.js"></script>
   <script src="api-handler.js"></script>
   ```

4. **Listen to Events**:
   - `componentRendered` - Nhận biết component type và load UI
   - `addToCart` - Xử lý logic thêm vào giỏ hàng
   - `voucherCopy` - Xử lý logic sao chép voucher
   - `renderProductsRequested` / `renderVouchersRequested` - Nếu không provide template functions

5. **Query components by type** - Sử dụng `data-component-type` attribute

Xem `example.php` để xem implementation đầy đủ.

## <span style="color: #4A70A9;">9. Notes</span>

### <span style="color: #8FABD4;">9.1 Architecture</span>
- Builder JS không tự gọi API, chỉ dispatch events
- **Builder output KHÔNG tự render UI, website chính PHẢI cung cấp template functions**
- Builder output không xử lý add to cart, chỉ emit event để website chính xử lý
- PHP/API handler phải listen events và gọi API
- Cache được implement trong api-handler.js (sử dụng cache key: `${type}-${tabId}`)

### <span style="color: #8FABD4;">9.2 Render Template System</span>
- Builder output tìm kiếm `window.renderProductItem` hoặc `window.renderProducts` để render products
- Builder output tìm kiếm `window.renderVoucherItem` hoặc `window.renderVouchers` để render vouchers
- Nếu không tìm thấy template functions → emit render request events
- Website chính có thể listen events và tự render, hoặc để builder output dùng default fallback
- Template functions PHẢI được define TRƯỚC KHI load `landing-page.js`

### <span style="color: #8FABD4;">9.3 Component Identification</span>
- Component items có `data-component-type` attribute để nhận biết type
- Product items có `data-product-data` attribute chứa full product data (JSON encoded, HTML-safe)
- Product data trong `data-product-data` có thể được extract bằng cách decode HTML entities và parse JSON

### <span style="color: #8FABD4;">9.4 API Handling</span>
- `api-handler.js` tự động detect mock API (nếu URL chứa "mock-api.php") và sử dụng query parameter
- Với real API, endpoint sẽ được append trực tiếp vào `API_BASE_URL`
- API responses được cache theo `${type}-${tabId}` key

### <span style="color: #8FABD4;">9.5 UI/UX</span>
- Responsive design hỗ trợ mobile
- Slider auto-play mỗi 5 giây
- Loading states được hiển thị khi đang fetch data
- Error states được hiển thị nếu API call fails

## <span style="color: #4A70A9;">10. Customization</span>

Để customize:
1. Sửa HTML structure trong `landing-page.html`
2. Update styles trong `landing-page.css`
3. Modify tab logic và render functions trong `landing-page.js`
4. Adjust API handling trong `api-handler.js`

