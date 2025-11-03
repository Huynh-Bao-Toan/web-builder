## Component Spec — Identity, Events, Data Pattern, Naming, Implementation

Mục tiêu: Tài liệu duy nhất, tổng hợp và sắp xếp khoa học các chuẩn tích hợp giữa Builder (JS output/Angular) và Website chính (PHP/host): định danh component, quy ước đặt tên event, pattern sự kiện chi tiết, hợp đồng dữ liệu & render, cùng kế hoạch triển khai thực tế.

---

### 1) Component Identity — Summary (Core)

Các field nhận diện dùng xuyên suốt giữa builder và website chính. Identity core không phụ thuộc UI (có/không có tab) và có thể mở rộng.

- type (required)
  - Mô tả: Loại component. Ví dụ: `product`, `voucher`, `banner`, `collection`, `review`.
  - Ràng buộc: Chuỗi không rỗng, viết thường theo convention.

- componentId (optional, recommended)
  - Mô tả: Định danh duy nhất của instance component trên trang. Ví dụ: `home-products-1`.
  - Lợi ích: Phân biệt nhiều instance cùng type; dùng cho cache/analytics granular.

- schemaVersion (required)
  - Mô tả: Phiên bản hợp đồng dữ liệu (data contract). Ví dụ: `1.0.0` (SemVer).
  - Lợi ích: Cho phép thay đổi breaking có kiểm soát, đảm bảo tương thích ngược, hỗ trợ A/B/rollback.

- context (optional, extensible object)
  - Mô tả: Ngữ cảnh linh hoạt phục vụ mapping UI ↔ dữ liệu. Không bắt buộc có tab.
  - Ví dụ keys: `tabId`, `brand`, `category`, `filters` (vd: `{ priceRange: [100000,300000], sort: 'best_seller' }`).
  - Quy tắc: Tự do mở rộng; không ghi đè field core.

Không thuộc Identity nhưng thường có trong events:
- apiEndpoint (string): Đường dẫn tương đối để gọi API (resolve với `API_BASE_URL`).
- timestamp (ISO string): Thời điểm phát sự kiện.

JSON mẫu (không tab):
```json
{
  "type": "product",
  "componentId": "home-products-1",
  "schemaVersion": "1.0.0"
}
```

JSON mẫu (có tab qua context):
```json
{
  "type": "product",
  "componentId": "home-products-1",
  "schemaVersion": "1.0.0",
  "context": { "tabId": "merries" }
}
```

---

### 2) Event Naming Conventions — Builder ↔ Host

Quy tắc chung:
- lowerCamelCase: `componentRendered`, `addToCart`.
- Tên mô tả đúng ngữ nghĩa (hành động/mốc vòng đời), nhất quán.
- Tránh vendor prefix trong tên event. Nếu cần namespace, dùng trong `detail`, không đổi tên chính.
- Events nên `cancelable: true` (trừ khi purely-informational).
- Payload luôn kèm identity: `type`, `schemaVersion`, `componentId?`, `context?`.

Nhóm ngữ nghĩa và mẫu đặt tên:
- Lifecycle (đã xảy ra): `componentRendered`, `componentInitialized`, `componentDestroyed`.
- Activation/selection: `tabActivated` (hoặc `componentActivated`), `filterChanged`.
- User actions: `addToCart`, `voucherCopy`, `itemClick`, `wishlistToggle`, `ctaClick`.
- Render request: `render<PluralType>Requested` (vd: `renderProductsRequested`, `renderVouchersRequested`).

Mở rộng & versioning:
- Không đổi nghĩa tên cũ; thay đổi breaking ở payload → tăng `schemaVersion` (SemVer).
- Trường hợp bất khả kháng có thể tách tạm `...V2`, kèm kế hoạch deprecate, tránh lạm dụng.

Ví dụ tiêu chuẩn:
```js
// Lifecycle
document.dispatchEvent(new CustomEvent('componentRendered', {
  detail: { type: 'product', data, componentId, schemaVersion, context, timestamp: new Date().toISOString() },
  bubbles: true, cancelable: true
}));

// Activation
document.dispatchEvent(new CustomEvent('tabActivated', {
  detail: { type: 'product', apiEndpoint, componentId, schemaVersion, context: { tabId }, timestamp: new Date().toISOString() },
  bubbles: true, cancelable: true
}));

// Interaction
document.dispatchEvent(new CustomEvent('addToCart', {
  detail: { type: 'product', productId, productData, componentId, context, timestamp: new Date().toISOString() },
  bubbles: true, cancelable: true
}));

document.dispatchEvent(new CustomEvent('voucherCopy', {
  detail: { type: 'voucher', code, componentId, context, timestamp: new Date().toISOString() },
  bubbles: true, cancelable: true
}));

// Render request
const ev = new CustomEvent('renderProductsRequested', {
  detail: { type: 'product', data, targetElement, componentId, schemaVersion, context, timestamp: new Date().toISOString() },
  bubbles: true, cancelable: true
});
document.dispatchEvent(ev);
if (!ev.defaultPrevented) {
  // fallback default render
}
```

Checklist đặt tên:
- lowerCamelCase, ngắn gọn, đúng ý nghĩa.
- Ngữ cảnh UI để trong `detail.context` (không nhúng vào tên).
- `cancelable: true` để host can thiệp khi cần.
- Payload có `type`, `schemaVersion`, `componentId?`, `context?`, `timestamp`.
- Backward compatibility: đổi schema → tăng `schemaVersion`.

---

### 3) Component Events — Detailed Pattern

Identity đính kèm (tối thiểu) trong mọi event:
- `type` (required), `schemaVersion` (required), `componentId?`, `context?`.

Outbound (Builder → Host):
- tabActivated (hoặc activation on init nếu không có tab)
  - Khi: user click tab hoặc init cần kích hoạt.
  - Mục đích: Báo host gọi API tương ứng.
  - cancelable: true
  - detail:
  ```json
  {
    "type": "product|voucher|...",
    "apiEndpoint": "/api/products/merries",
    "componentId": "home-products-1",
    "schemaVersion": "1.0.0",
    "context": { "tabId": "merries" },
    "timestamp": "ISO_8601"
  }
  ```

- componentRendered
  - Khi: Sau khi render xong (full/item-level/fallback).
  - cancelable: true
  - detail chứa `data` (full API response: items, paging, meta, ...).

- addToCart (product)
  - Khi: Click "Thêm vào giỏ"; Builder không xử lý nghiệp vụ.
  - cancelable: true
  - detail: `{ type: 'product', productId, productData, componentId?, context?, timestamp }`.

- voucherCopy (voucher)
  - Khi: Click "Sao chép"; Builder copy clipboard nếu browser hỗ trợ, sau đó emit.
  - cancelable: true
  - detail: `{ type: 'voucher', code, componentId?, context?, timestamp }`.

- renderProductsRequested / renderVouchersRequested
  - Khi: Host không cung cấp template (full/item-level), Builder mời host render.
  - cancelable: true — Host `event.preventDefault()` để tự render; nếu không → fallback default render.
  - detail: `{ type, data, targetElement, componentId?, schemaVersion, context?, timestamp }`.

Inbound (Host → Builder):
- window.updateLandingData(type, data)
  - Khi: Host fetch API xong theo `tabActivated`/activation.
  - Tác dụng: Đưa data vào Builder; Builder tự chọn full/item-level/fallback render.

Thứ tự ưu tiên render:
1) Full render (host): `window.renderProducts|Vouchers(data, el)`.
2) Item-level (host): `window.renderProductItem|renderVoucherItem(item)`.
3) Không có template → emit `render*Requested`; nếu host KHÔNG `preventDefault()` → default render fallback.

Cache key & context (khuyến nghị phía host):
- Công thức: ``${type}-${componentId || 'default'}-${contextHash?}`` (hash từ `context`).

Versioning:
- Event payload nên kèm `schemaVersion`. Breaking-change → tăng SemVer (major), host fallback hợp lý.

---

### 4) Data & Event Mapping Pattern — Overview

Endpoint API (Resolvable Endpoint):
- `apiBaseUrl`: Set trên host vào `window.API_BASE_URL` trước khi load `api-handler.js`.
- `endpoint`: Chuỗi tương đối builder cấu hình trong HTML (`data-api-endpoint`).
- Quy tắc build URL:
  - Nếu `API_BASE_URL` chứa `mock-api.php`: dùng query `mock-api.php?endpoint=/api/...`.
  - Ngược lại: `API_BASE_URL + endpoint`.

Data Contract (khuyến nghị, có thể mở rộng):
- Khung dữ liệu chung:
```json
{
  "schemaVersion": "1.0.0",
  "items": [ /* theo từng type */ ],
  "paging": { "page": 1, "pageSize": 20, "total": 120 },
  "filters": { /* optional */ },
  "meta": { /* optional */ }
}
```

- Product item (tối thiểu): `id`, `title`, `image`, `price` (các field khác tận dụng khi có)
```json
{
  "id": "string|number",
  "title": "string",
  "image": "url",
  "price": 123456,
  "originalPrice": 150000,
  "discount": true,
  "rating": 4.5,
  "reviewCount": 120,
  "sales": 2300,
  "attributes": { "brand": "Merries", "size": "L", "variants": [] },
  "metadata": { "badges": ["bestseller"], "tracking": { "sku": "SKU-123" } }
}
```

- Voucher item (tối thiểu): `id`, `title`, `code`, `value`, `expiryDate`
```json
{
  "id": "string|number",
  "title": "string",
  "code": "string",
  "description": "string",
  "value": "string",
  "expiryDate": "ISO_8601_string",
  "conditions": { "minOrder": 200000, "appliesTo": "all|brand|category" },
  "metadata": { "tracking": { "campaign": "NOV-2025" } }
}
```

Template Contract (host cung cấp, builder tự nhận diện):
- Full render: `window.renderProducts(data, targetElement)` / `window.renderVouchers(data, targetElement)`.
- Item-level: `window.renderProductItem(item)` / `window.renderVoucherItem(item)`.
- Không có template → emit `render*Requested`; nếu không `preventDefault()` → default render fallback.

Mở rộng type mới (vd: `review`):
- Thêm `data-type="review"`, `data-api-endpoint` tương ứng.
- API trả về data theo schema bạn định nghĩa (giữ `schemaVersion`).
- Template: `window.renderReviews(data, el)` hoặc `window.renderReviewItem(item)`.
- Sự kiện riêng (nếu cần), vd: `reviewSubmit`.

Versioning & tương thích:
- Tăng `schemaVersion` khi có breaking-change; host kiểm tra để fallback hợp lý.
- Ưu tiên thêm field mới trong `attributes/metadata` thay vì thay field cũ.

Ví dụ end-to-end (không tab):
```json
{
  "type": "product",
  "apiEndpoint": "/api/products/all",
  "componentId": "home-products-1",
  "schemaVersion": "1.0.0",
  "timestamp": "2025-11-03T10:00:00.000Z"
}
```

Biến thể có tab (context.tabId):
```json
{
  "type": "product",
  "apiEndpoint": "/api/products/merries",
  "componentId": "home-products-1",
  "schemaVersion": "1.0.0",
  "context": { "tabId": "merries" },
  "timestamp": "2025-11-03T10:00:00.000Z"
}
```

---

### 5) Implementation Plan — Web Builder (Angular) & Website Chính (PHP)

0) Prerequisites (vì sao):
- Chốt `type` giúp thống nhất ngôn ngữ giữa các bên.
- Hợp đồng dữ liệu tối thiểu đảm bảo render default và mở rộng an toàn.
- `schemaVersion` hỗ trợ thay đổi cấu trúc có kiểm soát, A/B, rollback.

1) Web Builder (Angular) — xuất HTML/CSS/JS phục vụ render
- UI regions: slider, `products-grid`, `vouchers-grid`, ... (không nhúng nghiệp vụ).
- Event lifecycle: emit `tabActivated` (nếu có tab) hoặc activation on init; emit `componentRendered`; emit interaction (`addToCart`, `voucherCopy`).
- Mọi event kèm `type`, `componentId?`, `schemaVersion`, `context?`.
- Template detection & fallback: ưu tiên template của host; nếu không có → `render*Requested` → fallback nếu không `preventDefault()`.
- Data entrypoint: `window.updateLandingData(type, data)`.
- Build output: `output/landing-page.html|css|js`; minify + `bundle/bundle.json`.
- Versioning & publish: gắn version vào manifest; quy trình CDN/DB.

Definition of Done (Builder):
- Event flow hoạt động, không phụ thuộc tab.
- Nhận template nếu có, fallback ổn định.
- `updateLandingData` render đúng với data mẫu.
- Output & minified bundle có manifest.

2) Website Chính (PHP) — UI component + nghiệp vụ + API calls
- Include CSS/HTML/JS theo README hoặc loader DB/CDN.
- Set `window.API_BASE_URL` trước `api-handler.js` (mock: `./mock-api.php`; real: domain API thật).
- Cung cấp Template Functions (khuyến nghị): item-level hoặc full render; đảm bảo data-attributes để builder auto attach listeners.
- Listen & handle events: `componentRendered`, `addToCart`, `voucherCopy`, (optional) `render*Requested` (gọi `preventDefault()` nếu tự render).
- API Handler: include đúng thứ tự; backend trả JSON theo hợp đồng; mock dùng `mock-api.php` với `endpoint` query.
- Caching & lỗi: xét cache key ``${type}-${componentId || 'default'}-${contextHash?}``; hiển thị loading/error (đã có sẵn trong `api-handler.js`).
- Analytics & quan sát: dùng `LandingPageComponents.logInteraction` hoặc hệ thống riêng.

Definition of Done (PHP):
- Template functions hoạt động, render đúng data mẫu.
- Events được lắng nghe và xử lý đầy đủ.
- API thật/mocked đúng hợp đồng.
- Tích hợp ổn định (loading/error/caching ok).

3) Data Contract & Context (tại sao `context`):
- `context` bao trùm tab/brand/category/filters, tổng quát cho mọi component mà không đổi hợp đồng sự kiện.

4) Environments (tại sao tách):
- Dev: mock nhanh & log đầy đủ; Staging: kiểm thử tích hợp/thông lượng; Prod: CDN/DB published + monitoring.

5) Milestones đề xuất (vì sao):
- P0: khung event + default render + mock end-to-end (giá trị sớm).
- P1: template thật + addToCart + analytics.
- P2: minify/bundle + publish pipeline (CDN/DB) + versioning.
- P3: tối ưu performance + quan sát.

6) Hướng dẫn thực hiện (Hands-on)
- Builder:
  - Implement lifecycle & `updateLandingData` theo mẫu `output/landing-page.js`.
  - Xuất `output/*`; chạy `node minify-bundle.js` để tạo `bundle/bundle.json`.
- PHP (mock nhanh):
  - Set `window.API_BASE_URL = './mock-api.php'` trước khi load JS.
  - Định nghĩa `window.renderProductItem`/`window.renderVoucherItem` trước khi load `landing-page.js`.
  - Include theo thứ tự: CSS → HTML → `landing-page.js` → `api-handler.js`.
  - Listen `addToCart`, `componentRendered`, `voucherCopy` để nối nghiệp vụ.
  - Chạy thử: `php -S 0.0.0.0:8080` và mở `example.php`.
- Chuyển sang API thật: đổi `API_BASE_URL`, đảm bảo JSON hợp đồng, xét lại cache key theo `componentId/context`.
- Kiểm thử nhanh: activation → API → `updateLandingData` → render → `componentRendered`; thử `addToCart`/`voucherCopy`.

---

Kết luận: Tài liệu này hợp nhất toàn bộ chuẩn Identity, Naming, Events, Data Pattern và Implementation thành một nguồn tham chiếu duy nhất. Áp dụng đúng giúp tách bạch UI khung (builder) và nghiệp vụ (host), đảm bảo mở rộng, tái sử dụng, và vận hành an toàn theo thời gian.


