/**
 * Admin Dashboard JavaScript
 * Mobile-friendly sidebar toggle and other admin functions
 */

document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.getElementById("adminMenuToggle");
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const sidebarClose = document.getElementById("sidebarClose");

  // Toggle sidebar
  function toggleSidebar() {
    sidebar.classList.toggle("active");
    menuToggle.classList.toggle("active");
    if (sidebarOverlay) {
      sidebarOverlay.classList.toggle("active");
    }
    document.body.style.overflow = sidebar.classList.contains("active")
      ? "hidden"
      : "";
  }

  // Close sidebar
  function closeSidebar() {
    sidebar.classList.remove("active");
    menuToggle.classList.remove("active");
    if (sidebarOverlay) {
      sidebarOverlay.classList.remove("active");
    }
    document.body.style.overflow = "";
  }

  // Event listeners
  if (menuToggle) {
    menuToggle.addEventListener("click", toggleSidebar);
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
  }

  if (sidebarClose) {
    sidebarClose.addEventListener("click", closeSidebar);
  }

  // Close sidebar on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && sidebar && sidebar.classList.contains("active")) {
      closeSidebar();
    }
  });

  // Close sidebar when clicking a nav link (on mobile)
  const navLinks = document.querySelectorAll(".admin-nav a");
  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (window.innerWidth <= 768) {
        closeSidebar();
      }
    });
  });

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      closeSidebar();
    }
  });

  // ═══════════════════════════════════════════════════════════════
  // UNIFIED DELETE SYSTEM
  // ═══════════════════════════════════════════════════════════════

  // Create and inject delete modal if not exists
  if (!document.getElementById("deleteModalOverlay")) {
    const modalHTML = `
            <div class="delete-modal-overlay" id="deleteModalOverlay">
                <div class="delete-modal">
                    <div class="delete-modal-icon">🗑️</div>
                    <h3 class="delete-modal-title">تأكيد الحذف</h3>
                    <p class="delete-modal-message">هل أنت متأكد من حذف هذا العنصر؟ لا يمكن التراجع عن هذا الإجراء.</p>
                    <div class="delete-modal-item" id="deleteItemName"></div>
                    <div class="delete-modal-actions">
                        <button class="delete-modal-btn delete-modal-cancel" id="deleteCancelBtn">إلغاء</button>
                        <button class="delete-modal-btn delete-modal-confirm" id="deleteConfirmBtn">🗑️ حذف</button>
                    </div>
                </div>
            </div>
        `;
    document.body.insertAdjacentHTML("beforeend", modalHTML);
  }

  // Delete modal elements
  const deleteModal = document.getElementById("deleteModalOverlay");
  const deleteItemName = document.getElementById("deleteItemName");
  const deleteCancelBtn = document.getElementById("deleteCancelBtn");
  const deleteConfirmBtn = document.getElementById("deleteConfirmBtn");

  let currentDeleteData = null;

  // Initialize delete buttons
  initDeleteButtons();

  function initDeleteButtons() {
    // Find all delete buttons/links
    document
      .querySelectorAll(
        ".action-btn-delete, .btn-delete, .btn-delete-full, [data-delete]",
      )
      .forEach((btn) => {
        // Skip if already initialized
        if (btn.dataset.deleteInit) return;
        btn.dataset.deleteInit = "true";

        btn.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();

          // Get delete info from data attributes or href
          const type =
            this.dataset.deleteType || getDeleteTypeFromUrl(this.href);
          const id = this.dataset.deleteId || getDeleteIdFromUrl(this.href);
          const name = this.dataset.deleteName || "";
          const token =
            this.dataset.deleteToken ||
            getTokenFromUrl(this.href) ||
            window.csrfToken ||
            "";

          if (!type || !id) {
            console.error("Missing delete type or id");
            return;
          }

          // Show confirmation modal
          showDeleteModal(type, id, name, token, this);
        });
      });
  }

  // Show delete modal
  function showDeleteModal(type, id, name, token, triggerElement) {
    currentDeleteData = { type, id, name, token, triggerElement };

    const typeLabels = {
      product: "المنتج",
      order: "الطلب",
      coupon: "الكوبون",
      banner: "البانر",
      backup: "النسخة الاحتياطية",
    };

    const typeIcons = {
      product: "📦",
      order: "📋",
      coupon: "🎟️",
      banner: "🖼️",
      backup: "💾",
    };

    deleteItemName.innerHTML = `${typeIcons[type] || "📁"} ${name || typeLabels[type] || "العنصر"}`;
    deleteConfirmBtn.textContent = "🗑️ حذف";
    deleteConfirmBtn.classList.remove("loading");
    deleteConfirmBtn.disabled = false;

    deleteModal.classList.add("active");
    document.body.style.overflow = "hidden";
  }

  // Close delete modal
  function closeDeleteModal() {
    deleteModal.classList.remove("active");
    document.body.style.overflow = "";
    currentDeleteData = null;
  }

  // Cancel button
  if (deleteCancelBtn) {
    deleteCancelBtn.addEventListener("click", closeDeleteModal);
  }

  // Overlay click to close
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) {
        closeDeleteModal();
      }
    });
  }

  // Escape key to close
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && deleteModal.classList.contains("active")) {
      closeDeleteModal();
    }
  });

  // Confirm delete
  if (deleteConfirmBtn) {
    deleteConfirmBtn.addEventListener("click", async function () {
      if (!currentDeleteData) return;

      const { type, id, token, triggerElement } = currentDeleteData;

      // Show loading state
      this.classList.add("loading");
      this.textContent = "جاري الحذف...";
      this.disabled = true;

      try {
        const response = await fetch("../api/delete.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ type, id, token }),
        });

        const result = await response.json();

        if (result.success) {
          // Show success message
          showNotification(result.message || "تم الحذف بنجاح", "success");

          // Remove the row/card from DOM
          removeDeletedElement(triggerElement);

          // Close modal
          closeDeleteModal();
        } else {
          showNotification(result.error || "فشل في الحذف", "error");
          this.classList.remove("loading");
          this.textContent = "🗑️ حذف";
          this.disabled = false;
        }
      } catch (error) {
        console.error("Delete error:", error);
        showNotification("حدث خطأ في الاتصال", "error");
        this.classList.remove("loading");
        this.textContent = "🗑️ حذف";
        this.disabled = false;
      }
    });
  }

  // Remove deleted element from DOM with animation
  function removeDeletedElement(triggerElement) {
    if (!triggerElement) return;

    // Find parent row/card
    const row = triggerElement.closest("tr");
    const card = triggerElement.closest(
      '.mobile-order-card, .mobile-product-card, .product-card-mobile, .product-card, .admin-card, .coupon-card, .banner-card, [class*="card"]',
    );
    const element = row || card;

    if (element) {
      element.style.transition = "all 0.3s ease";
      element.style.opacity = "0";
      element.style.transform = "translateX(20px)";

      setTimeout(() => {
        element.remove();
        // Update counts if needed
        updateCounts();
      }, 300);
    }
  }

  // Update counts after deletion
  function updateCounts() {
    // Recalculate and update any count displays
    const countElements = document.querySelectorAll("[data-count]");
    countElements.forEach((el) => {
      const currentCount = parseInt(el.textContent) || 0;
      if (currentCount > 0) {
        el.textContent = currentCount - 1;
      }
    });
  }

  // Helper functions
  function getDeleteTypeFromUrl(url) {
    if (!url) return "";
    if (url.includes("products")) return "product";
    if (url.includes("orders")) return "order";
    if (url.includes("coupons")) return "coupon";
    if (url.includes("banners")) return "banner";
    if (url.includes("backup")) return "backup";
    return "";
  }

  function getDeleteIdFromUrl(url) {
    if (!url) return "";
    const match = url.match(/[?&]delete=([^&]+)/);
    return match ? match[1] : "";
  }

  function getTokenFromUrl(url) {
    if (!url) return "";
    const match = url.match(/[?&]token=([^&]+)/);
    return match ? match[1] : "";
  }

  // Show notification
  window.showNotification = function (message, type = "success") {
    // Remove existing notifications
    document.querySelectorAll(".admin-notification").forEach((n) => n.remove());

    const notification = document.createElement("div");
    notification.className = `admin-notification ${type}`;
    notification.innerHTML = `
            <span>${type === "success" ? "✓" : "✕"}</span>
            <span>${message}</span>
        `;
    notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === "success" ? "linear-gradient(135deg, #4CAF50, #388E3C)" : "linear-gradient(135deg, #F44336, #D32F2F)"};
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 10001;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
      notification.style.animation = "slideUp 0.3s ease";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  };

  // Add animation keyframes
  if (!document.getElementById("adminNotificationStyles")) {
    const style = document.createElement("style");
    style.id = "adminNotificationStyles";
    style.textContent = `
            @keyframes slideDown {
                from { opacity: 0; transform: translate(-50%, -20px); }
                to { opacity: 1; transform: translate(-50%, 0); }
            }
        `;
    document.head.appendChild(style);
  }

  // Re-init delete buttons after AJAX content load
  window.initDeleteButtons = initDeleteButtons;

  // ═══════════════════════════════════════════════════════════════
  // PJAX SYSTEM - Internal Page Loading
  // ═══════════════════════════════════════════════════════════════

  const adminMain = document.querySelector(".admin-main");

  // Initialize PJAX links
  initPjaxLinks();

  function initPjaxLinks() {
    document.querySelectorAll("a[data-pjax]").forEach((link) => {
      // Skip if already initialized
      if (link.dataset.pjaxInit) return;
      link.dataset.pjaxInit = "true";

      link.addEventListener("click", function (e) {
        e.preventDefault();
        const url = this.href;

        // Don't process if it's the current page
        if (url === window.location.href) return;

        // Load the page via PJAX
        loadPageViaPjax(url, this);
      });
    });
  }

  async function loadPageViaPjax(url, clickedLink) {
    // Show loading indicator
    showLoadingOverlay();

    // Update active state in sidebar
    document
      .querySelectorAll(".admin-nav a")
      .forEach((a) => a.classList.remove("active"));
    if (clickedLink) clickedLink.classList.add("active");

    // Close mobile sidebar
    if (window.innerWidth <= 768 && sidebar) {
      sidebar.classList.remove("active");
      if (menuToggle) menuToggle.classList.remove("active");
      if (sidebarOverlay) sidebarOverlay.classList.remove("active");
      document.body.style.overflow = "";
    }

    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error("Network error");

      const html = await response.text();

      // Parse the HTML
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      // Get the new content
      const newMain = doc.querySelector(".admin-main");
      const newTitle =
        doc.querySelector("title")?.textContent || document.title;
      const newStyles = doc.querySelectorAll("style");

      if (newMain && adminMain) {
        // Fade out current content
        adminMain.style.opacity = "0";
        adminMain.style.transform = "translateY(10px)";

        setTimeout(() => {
          // Replace main content
          adminMain.innerHTML = newMain.innerHTML;

          // Update page title
          document.title = newTitle;

          // Update URL in browser
          history.pushState({ pjax: true, url: url }, newTitle, url);

          // Copy inline styles from new page
          const existingInlineStyles = document.querySelectorAll(
            "style[data-pjax-style]",
          );
          existingInlineStyles.forEach((s) => s.remove());

          newStyles.forEach((style) => {
            const newStyle = document.createElement("style");
            newStyle.setAttribute("data-pjax-style", "true");
            newStyle.textContent = style.textContent;
            document.head.appendChild(newStyle);
          });

          // Fade in new content
          adminMain.style.transition = "opacity 0.3s ease, transform 0.3s ease";
          adminMain.style.opacity = "1";
          adminMain.style.transform = "translateY(0)";

          // Re-initialize components
          reinitializeComponents();

          // Hide loading
          hideLoadingOverlay();
        }, 150);
      } else {
        // Fallback: full page load
        window.location.href = url;
      }
    } catch (error) {
      console.error("PJAX Error:", error);
      // Fallback to regular navigation
      window.location.href = url;
    }
  }

  function reinitializeComponents() {
    // Re-init delete buttons
    if (window.initDeleteButtons) {
      window.initDeleteButtons();
    }

    // Re-init any page-specific scripts
    // Execute inline scripts in the new content
    const scripts = adminMain.querySelectorAll("script");
    scripts.forEach((script) => {
      if (script.src) {
        // External script - skip (already loaded)
      } else {
        // Inline script - execute
        try {
          eval(script.textContent);
        } catch (e) {
          console.error("Script execution error:", e);
        }
      }
    });
  }

  // Handle browser back/forward buttons
  window.addEventListener("popstate", function (e) {
    if (e.state && e.state.pjax) {
      loadPageViaPjax(e.state.url);
    } else if (e.state === null) {
      // Initial page load
      loadPageViaPjax(window.location.href);
    }
  });

  // Loading overlay
  function showLoadingOverlay() {
    let overlay = document.getElementById("pjaxLoadingOverlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "pjaxLoadingOverlay";
      overlay.innerHTML = `
                <div class="pjax-spinner"></div>
            `;
      overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9998;
                opacity: 0;
                transition: opacity 0.2s ease;
                pointer-events: none;
            `;

      const spinnerStyle = document.createElement("style");
      spinnerStyle.textContent = `
                .pjax-spinner {
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f0f0f0;
                    border-top-color: #E91E8C;
                    border-radius: 50%;
                    animation: pjaxSpin 0.8s linear infinite;
                }
                @keyframes pjaxSpin {
                    to { transform: rotate(360deg); }
                }
            `;
      document.head.appendChild(spinnerStyle);
      document.body.appendChild(overlay);
    }

    requestAnimationFrame(() => {
      overlay.style.opacity = "1";
      overlay.style.pointerEvents = "auto";
    });
  }

  function hideLoadingOverlay() {
    const overlay = document.getElementById("pjaxLoadingOverlay");
    if (overlay) {
      overlay.style.opacity = "0";
      overlay.style.pointerEvents = "none";
    }
  }

  // Expose PJAX functions globally
  window.loadPageViaPjax = loadPageViaPjax;
  window.initPjaxLinks = initPjaxLinks;
});
