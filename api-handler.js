/**
 * API Handler for Landing Page
 * Listens to tabActivated events and makes API calls
 * This file should be included in the PHP page
 */

(function () {
  "use strict";

  // API base URL - should be set by PHP
  const API_BASE_URL = window.API_BASE_URL || "";

  // Cache for API responses
  const cache = {};

  /**
   * Listen for tabActivated events from builder JS
   */
  document.addEventListener("tabActivated", function (event) {
    const { type, apiEndpoint, tabId } = event.detail;

    console.log("API Handler received tabActivated event:", {
      type,
      apiEndpoint,
      tabId,
    });

    // Call API based on the event
    handleApiCall(type, apiEndpoint, tabId);
  });

  /**
   * Handle API call for activated tab
   */
  function handleApiCall(type, endpoint, tabId) {
    // Check cache first
    const cacheKey = `${type}-${tabId}`;
    if (cache[cacheKey]) {
      console.log("Using cached data for:", cacheKey);
      window.updateLandingData(type, cache[cacheKey]);
      return;
    }

    // Show loading state
    showLoadingState(type);

    // Construct full API URL
    // If API_BASE_URL contains mock-api.php, append endpoint as query param for better compatibility
    let apiUrl;
    if (API_BASE_URL.includes('mock-api.php')) {
      // Use query parameter approach for mock API
      const separator = API_BASE_URL.includes('?') ? '&' : '?';
      apiUrl = API_BASE_URL + separator + 'endpoint=' + encodeURIComponent(endpoint);
    } else {
      apiUrl = API_BASE_URL + endpoint;
    }

    // Make API call
    fetch(apiUrl, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        // Add any authentication headers here if needed
        // 'Authorization': 'Bearer ' + token
      },
      mode: 'cors', // Allow CORS
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        // Validate response structure
        if (!data || typeof data !== "object") {
          throw new Error("Invalid API response format");
        }

        // Cache the response
        cache[cacheKey] = data;

        // Update landing page with data
        if (window.updateLandingData) {
          window.updateLandingData(type, data);
        } else {
          console.error("updateLandingData function not found");
        }

        console.log("API call successful:", { type, endpoint, data });
      })
      .catch((error) => {
        console.error("API call failed:", error);
        showErrorState(type, error.message);
      });
  }

  /**
   * Show loading state
   */
  function showLoadingState(type) {
    if (type === "product") {
      const productsGrid = document.getElementById("products-grid");
      if (productsGrid) {
        productsGrid.innerHTML =
          '<div class="products-loading">Đang tải sản phẩm...</div>';
      }
    } else if (type === "voucher") {
      const vouchersGrid = document.getElementById("vouchers-grid");
      if (vouchersGrid) {
        vouchersGrid.innerHTML =
          '<div class="vouchers-loading">Đang tải voucher...</div>';
      }
    }
  }

  /**
   * Show error state
   */
  function showErrorState(type, errorMessage) {
    if (type === "product") {
      const productsGrid = document.getElementById("products-grid");
      if (productsGrid) {
        productsGrid.innerHTML = `
          <div class="products-loading" style="color: #ff6b9d;">
            Có lỗi xảy ra khi tải sản phẩm. Vui lòng thử lại sau.<br>
            <small>${errorMessage}</small>
          </div>
        `;
      }
    } else if (type === "voucher") {
      const vouchersGrid = document.getElementById("vouchers-grid");
      if (vouchersGrid) {
        vouchersGrid.innerHTML = `
          <div class="vouchers-loading" style="color: #ff6b9d;">
            Có lỗi xảy ra khi tải voucher. Vui lòng thử lại sau.<br>
            <small>${errorMessage}</small>
          </div>
        `;
      }
    }
  }

  /**
   * Clear cache (useful for refresh)
   */
  window.clearApiCache = function () {
    Object.keys(cache).forEach((key) => delete cache[key]);
    console.log("API cache cleared");
  };

  /**
   * Manually trigger API call (for testing or manual refresh)
   */
  window.triggerApiCall = function (type, endpoint, tabId) {
    handleApiCall(type, endpoint, tabId);
  };

  console.log("API Handler initialized");
})();
