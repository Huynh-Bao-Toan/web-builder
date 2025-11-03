/**
 * Landing Page Builder JS Output
 * Handles tab switching, slider, and renders components
 */

(function () {
  "use strict";

  // State management
  const state = {
    currentSlide: 0,
    slides: [],
    activeProductTab: null,
    activeVoucherTab: null,
  };

  /**
   * Initialize Slider
   */
  function initSlider() {
    const slides = document.querySelectorAll(".slider-item");
    const dots = document.querySelectorAll(".dot");
    const prevBtn = document.getElementById("slider-prev");
    const nextBtn = document.getElementById("slider-next");

    state.slides = Array.from(slides);

    // Arrow navigation
    prevBtn.addEventListener("click", () => {
      goToSlide(state.currentSlide - 1);
    });

    nextBtn.addEventListener("click", () => {
      goToSlide(state.currentSlide + 1);
    });

    // Dot navigation
    dots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        goToSlide(index);
      });
    });

    // Auto-play slider
    setInterval(() => {
      goToSlide(state.currentSlide + 1);
    }, 5000);
  }

  /**
   * Go to specific slide
   */
  function goToSlide(index) {
    const slides = state.slides;
    const dots = document.querySelectorAll(".dot");

    if (index < 0) {
      index = slides.length - 1;
    } else if (index >= slides.length) {
      index = 0;
    }

    state.currentSlide = index;

    // Update slides
    slides.forEach((slide, i) => {
      slide.classList.toggle("active", i === index);
    });

    // Update dots
    dots.forEach((dot, i) => {
      dot.classList.toggle("active", i === index);
    });
  }

  /**
   * Tab Management - Product Tabs
   */
  function initProductTabs() {
    const productTabs = document.querySelectorAll(
      "#product-tabs-nav .tab-item"
    );
    const firstTab = productTabs[0];

    // Set first tab as active on load
    if (firstTab) {
      const type = firstTab.getAttribute("data-type");
      const endpoint = firstTab.getAttribute("data-api-endpoint");
      const tabId = firstTab.getAttribute("data-tab-id");

      state.activeProductTab = tabId;
      dispatchTabActivatedEvent(type, endpoint, tabId);
    }

    // Tab click handlers
    productTabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        // Remove active class from all tabs in this group
        productTabs.forEach((t) => t.classList.remove("active"));

        // Add active class to clicked tab
        this.classList.add("active");

        // Get tab data
        const type = this.getAttribute("data-type");
        const endpoint = this.getAttribute("data-api-endpoint");
        const tabId = this.getAttribute("data-tab-id");

        // Update state
        state.activeProductTab = tabId;

        // Dispatch event for PHP to handle API call
        dispatchTabActivatedEvent(type, endpoint, tabId);
      });
    });
  }

  /**
   * Tab Management - Voucher Tabs
   */
  function initVoucherTabs() {
    const voucherTabs = document.querySelectorAll(
      "#voucher-tabs-nav .tab-item"
    );
    const firstTab = voucherTabs[0];

    // Set first tab as active on load
    if (firstTab) {
      const type = firstTab.getAttribute("data-type");
      const endpoint = firstTab.getAttribute("data-api-endpoint");
      const tabId = firstTab.getAttribute("data-tab-id");

      state.activeVoucherTab = tabId;
      dispatchTabActivatedEvent(type, endpoint, tabId);
    }

    // Tab click handlers
    voucherTabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        // Remove active class from all tabs in this group
        voucherTabs.forEach((t) => t.classList.remove("active"));

        // Add active class to clicked tab
        this.classList.add("active");

        // Get tab data
        const type = this.getAttribute("data-type");
        const endpoint = this.getAttribute("data-api-endpoint");
        const tabId = this.getAttribute("data-tab-id");

        // Update state
        state.activeVoucherTab = tabId;

        // Dispatch event for PHP to handle API call
        dispatchTabActivatedEvent(type, endpoint, tabId);
      });
    });
  }

  /**
   * Dispatch custom event when tab is activated
   * PHP/API handler will listen to this event
   */
  function dispatchTabActivatedEvent(type, apiEndpoint, tabId) {
    const event = new CustomEvent("tabActivated", {
      detail: {
        type: type, // 'product' or 'voucher'
        apiEndpoint: apiEndpoint,
        tabId: tabId,
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);
    console.log("Tab activated event dispatched:", event.detail);
  }

  /**
   * Render Product Items from API response
   * Sử dụng template từ website chính nếu có (window.renderProductItem hoặc window.renderProducts)
   */
  function renderProducts(data) {
    const productsGrid = document.getElementById("products-grid");

    if (!data || !data.items || data.items.length === 0) {
      productsGrid.innerHTML = '<div class="products-loading">Không có sản phẩm nào</div>';
      return;
    }

    // Check if website chính provides custom render template
    if (typeof window.renderProducts === 'function') {
      // Website chính provides full render function
      window.renderProducts(data, productsGrid);
      dispatchComponentRenderedEvent("product", data);
      return;
    }

    if (typeof window.renderProductItem === 'function') {
      // Website chính provides item-level render function
      const productsHTML = data.items
        .map((product) => window.renderProductItem(product))
        .join("");

      productsGrid.innerHTML = productsHTML;
      
      // Attach event listeners to add-to-cart buttons
      attachAddToCartListeners();
      
      // Emit component rendered event for website chính to handle
      dispatchComponentRenderedEvent("product", data);
      return;
    }

    // Fallback: Default render (for backward compatibility or testing)
    // Emit event để website chính biết cần tự render
    const event = new CustomEvent("renderProductsRequested", {
      detail: {
        type: "product",
        data: data,
        targetElement: productsGrid,
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);

    // Nếu event không bị preventDefault, sử dụng default render
    if (!event.defaultPrevented) {
      renderProductsDefault(data);
    }
  }

  /**
   * Default product render (fallback, chỉ dùng khi website chính không provide template)
   */
  function renderProductsDefault(data) {
    const productsGrid = document.getElementById("products-grid");

    const productsHTML = data.items
      .map((product) => {
        const discountPercent = product.discount
          ? Math.round(
              ((product.originalPrice - product.price) /
                product.originalPrice) *
                100
            )
          : 0;

        const stars = "★".repeat(Math.floor(product.rating || 5));

        // Store full product data in data attribute for easy extraction
        const productDataJson = JSON.stringify(product)
          .replace(/&/g, "&amp;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#39;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;");

        return `
          <div class="product-card" data-component-type="product" data-product-id="${product.id}" data-product-data='${productDataJson}'>
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
      })
      .join("");

    productsGrid.innerHTML = productsHTML;
    attachAddToCartListeners();
    dispatchComponentRenderedEvent("product", data);
  }

  /**
   * Render Voucher Items from API response
   * Sử dụng template từ website chính nếu có (window.renderVoucherItem hoặc window.renderVouchers)
   */
  function renderVouchers(data) {
    const vouchersGrid = document.getElementById("vouchers-grid");

    if (!data || !data.items || data.items.length === 0) {
      vouchersGrid.innerHTML = '<div class="vouchers-loading">Không có voucher nào</div>';
      return;
    }

    // Check if website chính provides custom render template
    if (typeof window.renderVouchers === 'function') {
      // Website chính provides full render function
      window.renderVouchers(data, vouchersGrid);
      dispatchComponentRenderedEvent("voucher", data);
      return;
    }

    if (typeof window.renderVoucherItem === 'function') {
      // Website chính provides item-level render function
      const vouchersHTML = data.items
        .map((voucher) => window.renderVoucherItem(voucher))
        .join("");

      vouchersGrid.innerHTML = vouchersHTML;
      
      // Attach event listeners to voucher copy buttons
      attachVoucherCopyListeners();
      
      // Emit component rendered event for website chính to handle
      dispatchComponentRenderedEvent("voucher", data);
      return;
    }

    // Fallback: Emit event để website chính biết cần tự render
    const event = new CustomEvent("renderVouchersRequested", {
      detail: {
        type: "voucher",
        data: data,
        targetElement: vouchersGrid,
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);

    // Nếu event không bị preventDefault, sử dụng default render
    if (!event.defaultPrevented) {
      renderVouchersDefault(data);
    }
  }

  /**
   * Default voucher render (fallback, chỉ dùng khi website chính không provide template)
   */
  function renderVouchersDefault(data) {
    const vouchersGrid = document.getElementById("vouchers-grid");

    const vouchersHTML = data.items
      .map((voucher) => {
        return `
          <div class="voucher-card" data-component-type="voucher" data-voucher-id="${voucher.id}">
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
      })
      .join("");

    vouchersGrid.innerHTML = vouchersHTML;
    attachVoucherCopyListeners();
    dispatchComponentRenderedEvent("voucher", data);
  }

  /**
   * Handle data update from API response
   * Called by api-handler.js after API call
   */
  window.updateLandingData = function (type, data) {
    if (type === "product") {
      renderProducts(data);
    } else if (type === "voucher") {
      renderVouchers(data);
    }
  };

  /**
   * Utility: Format price
   */
  function formatPrice(price) {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(price);
  }

  /**
   * Utility: Format sales number
   */
  function formatSales(sales) {
    if (sales >= 1000000) {
      return (sales / 1000000).toFixed(1) + "M+";
    } else if (sales >= 1000) {
      return (sales / 1000).toFixed(0) + "K+";
    }
    return sales + "+";
  }

  /**
   * Utility: Format date
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("vi-VN");
  }

  /**
   * Attach event listeners to all add-to-cart buttons
   */
  function attachAddToCartListeners() {
    const addToCartButtons = document.querySelectorAll(".add-to-cart");
    addToCartButtons.forEach((button) => {
      // Remove existing listeners to avoid duplicates
      button.replaceWith(button.cloneNode(true));
    });

    // Re-query after clone
    document.querySelectorAll(".add-to-cart").forEach((button) => {
      button.addEventListener("click", function () {
        const productId = this.getAttribute("data-product-id");
        const productCard = this.closest(".product-card");
        
        // Get product data from the card
        const productData = extractProductData(productCard);

        // Emit add to cart event instead of handling directly
        dispatchAddToCartEvent(productId, productData);
      });
    });
  }

  /**
   * Extract product data from product card element
   */
  function extractProductData(productCard) {
    if (!productCard) return null;

    // Try to get full product data from data attribute first
    const productDataJson = productCard.getAttribute("data-product-data");
    if (productDataJson) {
      try {
        // Decode HTML entities back to JSON
        const decodedJson = productDataJson
          .replace(/&quot;/g, '"')
          .replace(/&#39;/g, "'")
          .replace(/&lt;/g, "<")
          .replace(/&gt;/g, ">")
          .replace(/&amp;/g, "&");
        const productData = JSON.parse(decodedJson);
        return productData;
      } catch (e) {
        console.warn("Failed to parse product data from attribute:", e);
      }
    }

    // Fallback: extract basic data from DOM
    const productId = productCard.getAttribute("data-product-id");
    const title = productCard.querySelector(".product-title")?.textContent || "";
    const image = productCard.querySelector(".product-image")?.getAttribute("src") || "";
    const priceText = productCard.querySelector(".product-price")?.textContent || "";
    
    // Try to extract numeric price from formatted price text
    const priceMatch = priceText.replace(/[^\d]/g, "");
    const price = priceMatch ? parseInt(priceMatch, 10) : 0;

    return {
      id: productId,
      title: title,
      image: image,
      price: price,
    };
  }

  /**
   * Attach event listeners to voucher copy buttons
   */
  function attachVoucherCopyListeners() {
    const copyButtons = document.querySelectorAll(".voucher-copy-btn");
    copyButtons.forEach((button) => {
      // Remove existing listeners to avoid duplicates
      button.replaceWith(button.cloneNode(true));
    });

    // Re-query after clone
    document.querySelectorAll(".voucher-copy-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const voucherCode = this.getAttribute("data-voucher-code");
        dispatchVoucherCopyEvent(voucherCode);
      });
    });
  }

  /**
   * Dispatch component rendered event
   * Website chính can listen to this to know component type and load appropriate UI
   */
  function dispatchComponentRenderedEvent(componentType, data) {
    const event = new CustomEvent("componentRendered", {
      detail: {
        type: componentType, // 'product' or 'voucher'
        data: data,
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);
    console.log("Component rendered event dispatched:", {
      type: componentType,
      itemCount: data?.items?.length || 0,
    });
  }

  /**
   * Dispatch add to cart event
   * Website chính should listen to this event to handle add to cart logic
   */
  function dispatchAddToCartEvent(productId, productData) {
    const event = new CustomEvent("addToCart", {
      detail: {
        productId: productId,
        productData: productData,
        type: "product",
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);
    console.log("Add to cart event dispatched:", {
      productId: productId,
      productData: productData,
    });
  }

  /**
   * Dispatch voucher copy event
   * Website chính can listen to this if needed
   */
  function dispatchVoucherCopyEvent(voucherCode) {
    // Try to copy to clipboard first (basic functionality)
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard
        .writeText(voucherCode)
        .then(() => {
          console.log("Voucher code copied to clipboard:", voucherCode);
        })
        .catch((err) => {
          console.error("Failed to copy voucher code:", err);
        });
    }

    // Emit event for website chính to handle
    const event = new CustomEvent("voucherCopy", {
      detail: {
        code: voucherCode,
        type: "voucher",
        timestamp: new Date().toISOString(),
      },
      bubbles: true,
      cancelable: true,
    });

    document.dispatchEvent(event);
    console.log("Voucher copy event dispatched:", { code: voucherCode });
  }

  /**
   * Initialize when DOM is ready
   */
  function init() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", init);
    } else {
      initSlider();
      initProductTabs();
      initVoucherTabs();
      console.log("Landing page builder JS initialized");
    }
  }

  // Start initialization
  init();
})();
