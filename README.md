# Web Builder Output Files

Mô phỏng các file output từ Angular Builder để nhúng vào website PHP.

## Cấu trúc Files

### Output Files (từ Builder)
- `output/landing-page.html` - Cấu trúc HTML với tabs, slider, voucher, product sections
- `output/landing-page.css` - Styling cho tất cả components
- `output/landing-page.js` - Logic quản lý tabs, slider, và render components

### Integration Files (cho PHP)
- `api-handler.js` - Xử lý API calls khi nhận events từ builder JS
- `example.php` - File PHP mẫu để nhúng các file output

## Cách hoạt động

### 1. Event Flow

```
User clicks tab 
  → landing-page.js detects active tab
  → Dispatches custom event 'tabActivated' với {type, apiEndpoint, tabId}
  → api-handler.js listens và gọi API tương ứng
  → API response được parse và render vào component
```

### 2. Custom Event Structure

Khi tab được active, `landing-page.js` sẽ dispatch event:

```javascript
{
  type: 'product' | 'voucher',
  apiEndpoint: '/api/products/sweety',
  tabId: 'sweety',
  timestamp: '2024-01-01T00:00:00.000Z'
}
```

### 3. API Response Format

API phải trả về JSON với format chuẩn:

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

## Cách sử dụng trong PHP

1. **Include files trong PHP:**
```php
<link rel="stylesheet" href="./output/landing-page.css">
<!-- HTML content -->
<script src="./output/landing-page.js"></script>
<script src="api-handler.js"></script>
```

2. **Set API base URL:**
```javascript
window.API_BASE_URL = 'https://your-api-domain.com';
```

3. **Xem ví dụ đầy đủ trong `example.php`**

## Components

### Slider/Carousel
- Auto-play mỗi 5 giây
- Navigation bằng arrows và dots
- Hỗ trợ touch/swipe (cần thêm logic)

### Product Tabs
- Mỗi tab đại diện cho một thương hiệu
- Khi active, gọi API để lấy danh sách sản phẩm
- Render product cards với: image, title, rating, price, discount

### Voucher Tabs
- Tương tự product tabs
- Render voucher cards với: title, code, description, value, expiry date

## Events System

### Custom Events được emit từ Builder Output

Builder output emit các events sau để website chính có thể xử lý:

#### 1. `componentRendered` Event
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

#### 2. `addToCart` Event
Được emit khi user click nút "Thêm vào giỏ". Website chính xử lý logic add to cart.

```javascript
document.addEventListener('addToCart', function(event) {
    const { productId, productData } = event.detail;
    // productId: ID của sản phẩm
    // productData: Full product object với tất cả thông tin
    
    // Website chính xử lý ở đây:
    // - Gọi API add to cart
    // - Update cart UI
    // - Show notification
    // - etc.
});
```

#### 3. `voucherCopy` Event
Được emit khi user click nút "Sao chép" voucher.

```javascript
document.addEventListener('voucherCopy', function(event) {
    const { code } = event.detail;
    // code: Voucher code đã được copy
    
    // Website chính có thể:
    // - Track analytics
    // - Show custom notification
    // - etc.
});
```

### Component Type Identification

Mỗi component item có `data-component-type` attribute để website chính nhận biết:

```html
<!-- Product item -->
<div class="product-card" data-component-type="product" data-product-id="123">
    <!-- ... -->
</div>

<!-- Voucher item -->
<div class="voucher-card" data-component-type="voucher" data-voucher-id="456">
    <!-- ... -->
</div>
```

Website chính có thể query và xử lý dựa trên type:

```javascript
// Find all product components
const productCards = document.querySelectorAll('[data-component-type="product"]');

// Find all voucher components
const voucherCards = document.querySelectorAll('[data-component-type="voucher"]');
```

## Functions có sẵn

### Global Functions (từ landing-page.js)
- `window.updateLandingData(type, data)` - Cập nhật UI với data từ API

### Global Functions (từ api-handler.js)
- `window.clearApiCache()` - Xóa cache API responses
- `window.triggerApiCall(type, endpoint, tabId)` - Trigger API call thủ công

## Website chính Integration

Xem `example.php` để biết cách:

1. **Listen to componentRendered event** - Nhận biết component type và load UI
2. **Listen to addToCart event** - Xử lý logic thêm vào giỏ hàng
3. **Listen to voucherCopy event** - Xử lý logic sao chép voucher
4. **Query components by type** - Sử dụng `data-component-type` attribute

## Notes

- Builder JS không tự gọi API, chỉ dispatch events
- Builder output không xử lý add to cart, chỉ emit event để website chính xử lý
- PHP/API handler phải listen events và gọi API
- Cache được implement trong api-handler.js
- Responsive design hỗ trợ mobile
- Component items có `data-component-type` attribute để nhận biết type
- Product items có `data-product-data` attribute chứa full product data (JSON encoded)

## Customization

Để customize:
1. Sửa HTML structure trong `landing-page.html`
2. Update styles trong `landing-page.css`
3. Modify tab logic và render functions trong `landing-page.js`
4. Adjust API handling trong `api-handler.js`

