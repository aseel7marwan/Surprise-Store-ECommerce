/**
 * Surprise! Store - Cart System with Internal Checkout
 * No Telegram redirect - Internal confirmation flow
 */

const Cart = {
  items: [],
  isProcessing: false, // Prevent double submissions

  init() {
    // Initialize global variables to prevent collisions with HTML IDs
    window.appliedDiscountAmount = 0;
    this.load();
    this.updateUI();
    this.bindEvents();
  },

  load() {
    try {
      const saved = localStorage.getItem("surprise_cart");
      if (saved) {
        this.items = JSON.parse(saved) || [];
      }
    } catch (e) {
      console.warn("Cart load error:", e);
      this.items = [];
    }
  },

  save() {
    localStorage.setItem("surprise_cart", JSON.stringify(this.items));
  },

  add(product) {
    // Check if same product with same options exists
    const optionsKey = JSON.stringify(product.selectedOptions || {});

    const existing = this.items.find(
      (item) =>
        item.id === product.id &&
        !item.hasCustomImage &&
        !product.hasCustomImage &&
        JSON.stringify(item.selectedOptions || {}) === optionsKey,
    );

    if (existing) {
      existing.quantity++;
    } else {
      // تنظيف السعر من الفواصل أو الرموز إذا وجدت لضمان أنه رقم صحيح
      const cleanPrice =
        typeof product.price === "string"
          ? parseInt(product.price.replace(/[^\d]/g, ""))
          : parseInt(product.price);

      this.items.push({
        id: product.id,
        name: product.name,
        price: isNaN(cleanPrice) ? 0 : cleanPrice,
        image: product.image,
        quantity: 1,
        hasCustomImage: product.hasCustomImage || false,
        customImage: product.customImage || null,
        customImages: product.customImages || [],
        selectedOptions: product.selectedOptions || {},
        packagingSelected: product.selectedOptions
          ? !!product.selectedOptions.packaging_selected
          : false,
        packagingPrice: product.selectedOptions
          ? parseInt(product.selectedOptions.packaging_price) || 0
          : 0,
        boxSelected: product.selectedOptions
          ? !!product.selectedOptions.box_selected
          : false,
        boxPrice: product.selectedOptions
          ? parseInt(product.selectedOptions.box_price) || 0
          : 0,
      });
    }

    this.save();
    this.updateUI();
    showNotification("✓ تمت الإضافة للسلة", "success", true);
  },

  remove(id) {
    this.items = this.items.filter((item) => item.id !== id);
    this.save();
    this.updateUI();
    this.renderCart();
  },

  removeByIndex(index) {
    if (index >= 0 && index < this.items.length) {
      this.items.splice(index, 1);
      this.save();
      this.updateUI();
      this.renderCart();
    }
  },

  updateQuantity(id, delta) {
    const item = this.items.find((item) => item.id === id);
    if (item) {
      item.quantity += delta;
      if (item.quantity <= 0) {
        this.remove(id);
      } else {
        this.save();
        this.updateUI();
        this.renderCart();
      }
    }
  },

  updateQuantityByIndex(index, delta) {
    if (index >= 0 && index < this.items.length) {
      this.items[index].quantity += delta;
      if (this.items[index].quantity <= 0) {
        this.removeByIndex(index);
      } else {
        this.save();
        this.updateUI();
        this.renderCart();
      }
    }
  },

  getSubtotal() {
    return this.items.reduce((sum, item) => {
      const price = parseFloat(item.price) || 0;
      const qty = parseInt(item.quantity) || 0;
      return sum + price * qty;
    }, 0);
  },

  getPackagingTotal() {
    return this.items.reduce((sum, item) => {
      const packagingPrice = item.packagingSelected
        ? parseFloat(item.packagingPrice) || 0
        : 0;
      const qty = parseInt(item.quantity) || 0;
      return sum + packagingPrice * qty;
    }, 0);
  },

  getCount() {
    return this.items.reduce((sum, item) => sum + item.quantity, 0);
  },

  updateUI() {
    const countEl = document.getElementById("cartCount");
    if (countEl) {
      countEl.textContent = this.getCount();
      countEl.style.display = this.getCount() > 0 ? "flex" : "none";
    }
  },

  bindEvents() {
    document.querySelectorAll(".add-to-cart").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();

        // Validate product options if they exist
        if (
          typeof validateProductOptions === "function" &&
          window.productOptionsConfig
        ) {
          if (!validateProductOptions()) {
            return; // Stop if options not valid
          }
        }

        // Get selected options if function exists
        let selectedOptions = {};
        if (typeof getSelectedProductOptions === "function") {
          selectedOptions = getSelectedProductOptions();
        }

        const product = {
          id: btn.dataset.id,
          name: btn.dataset.name,
          price: btn.dataset.price,
          image: btn.dataset.image,
          hasCustomImage: btn.dataset.customimage === "true",
          selectedOptions: selectedOptions,
        };
        this.add(product);

        btn.textContent = "✓ تمت الإضافة";
        btn.disabled = true;
        setTimeout(() => {
          btn.textContent = "🛒 أضف للسلة";
          btn.disabled = false;
        }, 1500);
      });
    });
  },

  // Unified function to build options HTML for Cart and Review
  formatItemOptionsHTML(item, isReview = false) {
    let parts = [];
    const opts = item.selectedOptions || {};

    // 1. Packaging (Primary)
    if (opts.packaging_selected) {
      let descStr = opts.packaging_description
        ? ` (${opts.packaging_description})`
        : "";
      parts.push(
        `<div class="opt-row" style="color: #9c27b0; font-weight: 700;">🎁 التغليف: نعم${descStr} | +${formatPrice(item.packagingPrice)}</div>`,
      );
    } else if (isReview && !opts.box_selected) {
      // Only show "No Packaging" if no special box is selected either
      parts.push(
        `<div class="opt-row" style="color: #666;">🎁 التغليف: لا</div>`,
      );
    }

    // 1.5 Box Options
    if (opts.box_selected) {
      parts.push(
        `<div class="opt-row" style="color: #9c27b0; font-weight: 700;">📦 الصندوق: ${opts.box_name} | +${formatPrice(opts.box_price)}</div>`,
      );
    }

    // 2. Standard Options (Colors, Sizes, Ages)
    if (opts.color)
      parts.push(`<div class="opt-row">🎨 اللون: ${opts.color}</div>`);
    if (opts.size)
      parts.push(`<div class="opt-row">📐 الحجم: ${opts.size}</div>`);
    if (opts.age)
      parts.push(`<div class="opt-row">👶 العمر: ${opts.age}</div>`);

    // 3. Grouped Options
    if (opts.color_groups) {
      for (const [label, value] of Object.entries(opts.color_groups)) {
        parts.push(`<div class="opt-row">🎨 ${label}: ${value}</div>`);
      }
    }
    if (opts.size_groups) {
      for (const [label, value] of Object.entries(opts.size_groups)) {
        parts.push(`<div class="opt-row">📐 ${label}: ${value}</div>`);
      }
    }
    if (opts.age_groups) {
      for (const [label, value] of Object.entries(opts.age_groups)) {
        parts.push(`<div class="opt-row">👶 ${label}: ${value}</div>`);
      }
    }

    // 4. Custom Text
    if (opts.custom_text) {
      parts.push(`<div class="opt-row">✏️ النص: ${opts.custom_text}</div>`);
    }

    // 5. Extra Fields
    if (opts.extra_fields && Array.isArray(opts.extra_fields)) {
      opts.extra_fields.forEach((field) => {
        if (field.label && field.value) {
          let icon = field.type === "text" ? "✏️" : "📋";
          parts.push(
            `<div class="opt-row">${icon} ${field.label}: ${field.value}</div>`,
          );
        }
      });
    }

    // 6. Gift Cards (New multi-card system)
    if (opts.gift_cards && Array.isArray(opts.gift_cards)) {
      opts.gift_cards.forEach((card) => {
        if (card.label && card.message) {
          const escapedMsg = card.message
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
          parts.push(
            `<div class="opt-row" style="color: #e91e8c; font-weight: 700;">🎁 ${card.label}:</div>`,
          );
          parts.push(
            `<div class="opt-row" style="color: #e91e8c; font-weight: 600; background: rgba(233, 30, 140, 0.05); padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; border-right: 3px solid #e91e8c; white-space: pre-wrap;">📝 "${escapedMsg}"</div>`,
          );
        }
      });
    } else if (opts.gift_card_enabled !== undefined) {
      // Compatibility with old/single gift card format
      parts.push(
        `<div class="opt-row">🎁 بطاقة رسالة: ${opts.gift_card_enabled ? "نعم" : "لا"}</div>`,
      );
      if (opts.gift_card_enabled && opts.gift_card_message) {
        const escapedMsg = opts.gift_card_message
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;");
        parts.push(
          `<div class="opt-row" style="color: #e91e8c; font-weight: 600; background: rgba(233, 30, 140, 0.05); padding: 8px 12px; border-radius: 8px; margin-top: 4px; border-right: 3px solid #e91e8c; white-space: pre-wrap;">📝 الرسالة: "${escapedMsg}"</div>`,
        );
      }
    } else if (opts.gift_card_message) {
      // Fallback for older cart items if only message exists
      const escapedMsg = opts.gift_card_message
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
      parts.push(
        `<div class="opt-row" style="color: #e91e8c; font-weight: 600; background: rgba(233, 30, 140, 0.05); padding: 8px 12px; border-radius: 8px; margin-top: 4px; border-right: 3px solid #e91e8c; white-space: pre-wrap;">💌 بطاقة رسالة: "${escapedMsg}"</div>`,
      );
    }

    // 7. Custom Images
    if (item.hasCustomImage) {
      const images =
        item.customImages && item.customImages.length > 0
          ? item.customImages
          : item.customImage
            ? [item.customImage]
            : [];

      if (images.length > 0) {
        const imgSize = isReview ? "45px" : "40px";
        const imgsHtml = images
          .map(
            (img) =>
              `<img src="images/uploads/${img}" style="width:${imgSize};height:${imgSize};object-fit:cover;border-radius:4px;border:1px solid #ddd;" onerror="this.style.display='none'">`,
          )
          .join("");

        parts.push(`
                    <div class="opt-row" style="margin-top: 5px;">
                        <span style="color: #E91E8C; font-weight: 600;">📷 صور التخصيص (${images.length}):</span>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">${imgsHtml}</div>
                    </div>
                `);
      }
    }

    if (parts.length === 0) return "";

    const bg = isReview ? "rgba(0,0,0,0.03)" : "rgba(233, 30, 140, 0.05)";
    const border = isReview ? "1px solid #eee" : "none";

    return `
            <div class="unified-options" style="font-size: 0.8rem; line-height: 1.6; margin-top: 8px; padding: 10px; background: ${bg}; border-radius: 10px; border: ${border}; direction: rtl; text-align: right;">
                ${parts.join("")}
            </div>
        `;
  },

  renderCart() {
    const container = document.getElementById("cartItems");
    const totalEl = document.getElementById("cartTotal");
    const subtotalEl = document.getElementById("cartSubtotal");
    const emptyEl = document.getElementById("cartEmpty");
    const actionsEl = document.getElementById("cartActions");
    const formEl = document.getElementById("checkoutForm");

    if (!container) return;

    if (this.items.length === 0) {
      container.style.display = "none";
      if (actionsEl) actionsEl.style.display = "none";
      if (formEl) formEl.style.display = "none";
      if (emptyEl) emptyEl.style.display = "block";
      return;
    }

    container.style.display = "block";
    if (actionsEl) actionsEl.style.display = "block";
    if (formEl) formEl.style.display = "block";
    if (emptyEl) emptyEl.style.display = "none";

    container.innerHTML = this.items
      .map((item, index) => {
        const optionsHtml = this.formatItemOptionsHTML(item);

        return `
                <div class="cart-item" data-id="${item.id}" data-index="${index}">
                    <div class="cart-item-image">
                        <img src="images/${item.image}" alt="${item.name}" onerror="this.src='images/logo.png'">
                    </div>
                    <div class="cart-item-info">
                        <h3 class="cart-item-name">${item.name}</h3>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <p class="cart-item-price">${formatPrice(item.price)}</p>
                            ${
                              item.packagingSelected
                                ? `
                                <span style="font-size: 0.85rem; color: #9c27b0; font-weight: 700; background: rgba(156, 39, 176, 0.05); padding: 2px 8px; border-radius: 20px; border: 1px solid rgba(156, 39, 176, 0.1);">
                                    🎁 +${formatPrice(item.packagingPrice)} تغليف
                                </span>`
                                : ""
                            }
                        </div>
                        
                        ${optionsHtml}
                        
                        <div class="cart-item-quantity">
                            <button class="qty-btn" onclick="Cart.updateQuantityByIndex(${index}, -1)" aria-label="تقليل">−</button>
                            <span class="qty-value">${item.quantity}</span>
                            <button class="qty-btn" onclick="Cart.updateQuantityByIndex(${index}, 1)" aria-label="زيادة">+</button>
                            <button class="cart-item-remove" onclick="Cart.removeByIndex(${index})" aria-label="حذف">🗑️</button>
                        </div>
                    </div>
                </div>
            `;
      })
      .join("");

    const subtotal = this.getSubtotal();
    if (subtotalEl) subtotalEl.textContent = formatPrice(subtotal);
    if (totalEl) this.updateTotal();
  },

  updateTotal() {
    const totalEl = document.getElementById("cartTotal");
    if (!totalEl) return;

    const subtotal = this.getSubtotal() || 0;
    const packagingTotal = this.getPackagingTotal() || 0;
    const deliveryFee = 5000;
    const discount =
      typeof window.appliedDiscountAmount === "number"
        ? window.appliedDiscountAmount
        : 0;

    const total = subtotal + packagingTotal - discount + deliveryFee;

    totalEl.textContent = formatPrice(isNaN(total) ? 0 : total);

    const subtotalEl = document.getElementById("cartSubtotal");
    if (subtotalEl) subtotalEl.textContent = formatPrice(subtotal);

    const packagingRow = document.getElementById("packagingRow");
    const packagingTotalEl = document.getElementById("packagingTotal");
    if (packagingRow && packagingTotalEl) {
      if (packagingTotal > 0) {
        packagingRow.style.display = "flex";
        packagingTotalEl.textContent = formatPrice(packagingTotal);
      } else {
        packagingRow.style.display = "none";
      }
    }

    const deliveryEl = document.getElementById("deliveryFee");
    if (deliveryEl) deliveryEl.textContent = formatPrice(deliveryFee);

    // Update discount row visibility
    const discountRow = document.getElementById("discountRow");
    const discountAmountEl = document.getElementById("discountAmount");
    if (discountRow && discountAmountEl) {
      if (discount > 0) {
        discountRow.style.display = "flex";
        discountAmountEl.textContent = "-" + formatPrice(discount);
      } else {
        discountRow.style.display = "none";
      }
    }
  },

  async applyCoupon() {
    const couponInput = document.getElementById("couponCode");
    const couponBtn = document.getElementById("applyCouponBtn");
    const couponMessage = document.getElementById("couponMessage");

    if (!couponInput) return;

    const code = couponInput.value.trim().toUpperCase();
    if (!code) {
      showNotification("يرجى إدخال كود الخصم", "error");
      return;
    }

    if (couponBtn) {
      couponBtn.disabled = true;
      couponBtn.innerHTML =
        '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span>';
    }

    try {
      const response = await fetch("api/validate-coupon.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          code: code,
          subtotal: this.getSubtotal(),
        }),
      });

      const result = await response.json();

      if (couponBtn) {
        couponBtn.disabled = false;
        couponBtn.textContent = "تطبيق";
      }

      if (result.success) {
        window.appliedDiscountAmount = parseFloat(result.discount) || 0;
        window.appliedCouponCode = code;

        if (couponMessage) {
          couponMessage.textContent = result.message;
          couponMessage.style.color = "#4CAF50";
          couponMessage.style.display = "block";
        }

        this.updateTotal();
        showNotification("✓ تم تطبيق الخصم بنجاح", "success");
      } else {
        window.appliedDiscountAmount = 0;
        window.appliedCouponCode = null;

        if (couponMessage) {
          couponMessage.textContent = result.error;
          couponMessage.style.color = "#f44336";
          couponMessage.style.display = "block";
        }

        this.updateTotal();
        showNotification(result.error || "كود الخصم غير صالح", "error");
      }
    } catch (error) {
      console.error("Coupon error:", error);
      if (couponBtn) {
        couponBtn.disabled = false;
        couponBtn.textContent = "تطبيق";
      }
      showNotification("حدث خطأ في الاتصال", "error");
    }
  },

  // Show confirmation modal with full order review
  showConfirmation() {
    if (this.items.length === 0) {
      showNotification("السلة فارغة!", "error");
      return;
    }

    // Validate form first
    if (!this.validateForm()) return;

    // Build review content
    const customerName = document.getElementById("customerName")?.value || "";
    const customerPhone = document.getElementById("customerPhone")?.value || "";
    const customerCity = document.getElementById("customerCity")?.value || "";
    const customerArea = document.getElementById("customerArea")?.value || "";
    const customerAddress =
      document.getElementById("customerAddress")?.value || "";
    const notes = document.getElementById("orderNotes")?.value || "";
    const paymentMethod =
      document
        .querySelector('input[name="paymentMethod"]:checked')
        ?.parentElement?.textContent?.trim() || "الدفع عند الاستلام";

    const subtotal = this.getSubtotal() || 0;
    const packagingTotal = this.getPackagingTotal() || 0;
    const deliveryFee = 5000;
    const discount =
      typeof window.appliedDiscountAmount === "number"
        ? window.appliedDiscountAmount
        : 0;
    const total = subtotal + packagingTotal - discount + deliveryFee;

    // Build location string (city + area)
    const fullLocation = customerArea
      ? `${customerCity} - ${customerArea}`
      : customerCity;

    // Build products HTML
    let productsHtml = this.items
      .map((item) => {
        const optionsHtml = this.formatItemOptionsHTML(item, true);
        const itemPriceWithPackaging =
          item.price + (item.packagingSelected ? item.packagingPrice || 0 : 0);

        return `
                <div class="review-product-item">
                    <img src="images/${item.image}" alt="${item.name}" class="review-product-image" onerror="this.src='images/logo.png'">
                    <div class="review-product-info">
                        <div class="review-product-name">${item.name}</div>
                        <div class="review-product-meta">${formatPrice(itemPriceWithPackaging)} × ${item.quantity} = ${formatPrice(itemPriceWithPackaging * item.quantity)}</div>
                        ${optionsHtml}
                    </div>
                </div>
            `;
      })
      .join("");

    // Update modal content
    const modal = document.getElementById("confirmModal");
    const modalContent = modal.querySelector(".order-modal-content");

    modalContent.innerHTML = `
            <div class="order-modal-icon">📋</div>
            <h2 class="order-modal-title">مراجعة الطلب</h2>
            <p class="order-modal-text" style="margin-bottom: 20px;">تأكد من صحة المعلومات قبل إرسال الطلب</p>
            
            <!-- Customer Info Section -->
            <div class="review-section">
                <div class="review-section-title">👤 معلومات التوصيل</div>
                <div class="review-info-row">
                    <span class="review-info-label">الاسم:</span>
                    <span class="review-info-value">${customerName}</span>
                </div>
                <div class="review-info-row">
                    <span class="review-info-label">📞 الهاتف:</span>
                    <span class="review-info-value">${customerPhone}</span>
                </div>
                <div class="review-info-row">
                    <span class="review-info-label">🏙️ المحافظة:</span>
                    <span class="review-info-value">${customerCity}</span>
                </div>
                ${
                  customerArea && customerArea !== "أخرى"
                    ? `
                <div class="review-info-row">
                    <span class="review-info-label">📍 المنطقة/الحي:</span>
                    <span class="review-info-value" style="direction: rtl; text-align: right;">${customerArea}</span>
                </div>
                `
                    : ""
                }
                <div class="review-info-row">
                    <span class="review-info-label">🏠 العنوان التفصيلي:</span>
                    <span class="review-info-value" style="direction: rtl; text-align: right;">${customerAddress}</span>
                </div>
                ${
                  notes
                    ? `
                <div class="review-info-row">
                    <span class="review-info-label">ملاحظات:</span>
                    <span class="review-info-value" style="direction: rtl; text-align: right;">${notes}</span>
                </div>
                `
                    : ""
                }
                <div class="review-info-row">
                    <span class="review-info-label">طريقة الدفع:</span>
                    <span class="review-info-value" style="direction: rtl; text-align: right;">${paymentMethod}</span>
                </div>
                ${
                  typeof getContactData === "function" && getContactData().valid
                    ? `
                <div class="review-info-row" style="background: linear-gradient(135deg, #e3f2fd, #f3e5f5); padding: 8px 12px; border-radius: 8px; margin-top: 5px;">
                    <span class="review-info-label">📱 وسيلة التواصل:</span>
                    <span class="review-info-value" style="direction: ltr; text-align: left;">${getContactData().methodLabel}: ${getContactData().value}</span>
                </div>
                `
                    : ""
                }
            </div>
            
            <!-- Products Section -->
            <div class="review-section">
                <div class="review-section-title">🛒 المنتجات (${this.items.length})</div>
                ${productsHtml}
            </div>
            
            <!-- Summary Section -->
            <div class="review-section review-summary">
                <div class="review-section-title">💰 ملخص الطلب</div>
                <div class="review-info-row">
                    <span class="review-info-label">المجموع الفرعي:</span>
                    <span class="review-info-value">${formatPrice(subtotal)}</span>
                </div>
                ${
                  packagingTotal > 0
                    ? `
                <div class="review-info-row" style="color: #9c27b0;">
                    <span class="review-info-label">🎁 كلفة التغليف:</span>
                    <span class="review-info-value">${formatPrice(packagingTotal)}</span>
                </div>
                `
                    : ""
                }
                ${
                  discount > 0
                    ? `
                <div class="review-info-row" style="color: #4CAF50;">
                    <span class="review-info-label">🎁 الخصم:</span>
                    <span class="review-info-value">-${formatPrice(discount)}</span>
                </div>
                `
                    : ""
                }
                <div class="review-info-row">
                    <span class="review-info-label">🚚 التوصيل:</span>
                    <span class="review-info-value">${formatPrice(deliveryFee)}</span>
                </div>
                <div class="review-info-row review-total-row">
                    <span class="review-info-label">الإجمالي النهائي:</span>
                    <span class="review-info-value">${formatPrice(total)}</span>
                </div>
            </div>
            
            <div class="order-modal-buttons" style="margin-top: 25px;">
                <button class="btn-confirm" id="confirmOrderBtn">✅ تأكيد الطلب الآن</button>
                <button class="btn-cancel" onclick="closeConfirmModal()">↩️ رجوع للتعديل</button>
            </div>
        `;

    // Re-attach event listener for confirm button
    document
      .getElementById("confirmOrderBtn")
      .addEventListener("click", (e) => {
        e.preventDefault();
        this.processOrder();
      });

    modal.style.display = "flex";
  },

  validateForm() {
    const customerName = document.getElementById("customerName")?.value?.trim();
    const customerPhone = document
      .getElementById("customerPhone")
      ?.value?.trim();
    const customerCity = document.getElementById("customerCity")?.value;
    const customerAddress = document
      .getElementById("customerAddress")
      ?.value?.trim();

    if (!customerName) {
      showNotification("يرجى إدخال الاسم الكامل", "error");
      document.getElementById("customerName")?.focus();
      return false;
    }

    if (!customerPhone) {
      showNotification("يرجى إدخال رقم الهاتف", "error");
      document.getElementById("customerPhone")?.focus();
      return false;
    }

    if (!customerCity) {
      showNotification("يرجى اختيار المحافظة", "error");
      document.getElementById("customerCity")?.focus();
      return false;
    }

    // التحقق من المنطقة/الحي (إجباري إذا لم تكن المحافظة "أخرى")
    const customerArea = document.getElementById("customerArea")?.value?.trim();
    if (customerCity && customerCity !== "أخرى" && !customerArea) {
      showNotification("يرجى اختيار المنطقة / الحي من القائمة", "error");
      document.getElementById("areaCombobox")?.focus();
      return false;
    }

    if (!customerAddress) {
      showNotification("يرجى إدخال العنوان التفصيلي", "error");
      document.getElementById("customerAddress")?.focus();
      return false;
    }

    // التحقق من وسيلة التواصل (إجباري)
    if (typeof getContactData === "function") {
      const contactData = getContactData();
      if (!contactData.valid) {
        showNotification(contactData.error, "error");
        return false;
      }
    }

    // التحقق من الموافقة على الشروط والخصوصية (إجباري)
    const termsConsent = document.getElementById("termsConsent");
    if (termsConsent && !termsConsent.checked) {
      showNotification(
        "يجب الموافقة على الشروط والأحكام وسياسة الخصوصية",
        "error",
      );
      termsConsent.focus();
      return false;
    }

    return true;
  },

  // Process order after confirmation
  async processOrder() {
    if (this.isProcessing) return;
    this.isProcessing = true;

    const confirmBtn = document.getElementById("confirmOrderBtn");
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML =
        '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-left:8px;"></span> جاري الإرسال...';
    }

    // Get form data
    const customerName = document.getElementById("customerName")?.value || "";
    const customerPhone = document.getElementById("customerPhone")?.value || "";
    const customerCity = document.getElementById("customerCity")?.value || "";
    const customerArea = document.getElementById("customerArea")?.value || "";
    const customerAddress =
      document.getElementById("customerAddress")?.value || "";
    const notes = document.getElementById("orderNotes")?.value || "";

    const subtotal = this.getSubtotal() || 0;
    const packagingTotal = this.getPackagingTotal() || 0;
    const deliveryFee = 5000;
    const discount =
      typeof window.appliedDiscountAmount === "number"
        ? window.appliedDiscountAmount
        : 0;
    const total = subtotal + packagingTotal - discount + deliveryFee;

    // collect images
    const uploadedImages = [];
    this.items.forEach((item) => {
      if (item.hasCustomImage) {
        const images =
          item.customImages && item.customImages.length > 0
            ? item.customImages
            : item.customImage
              ? [item.customImage]
              : [];
        images.forEach((img) => {
          if (img && !uploadedImages.includes(img)) uploadedImages.push(img);
        });
      }
    });

    // contact data
    let contactMethod = "";
    let contactValue = "";
    if (typeof getContactData === "function") {
      const contactData = getContactData();
      if (contactData.valid) {
        contactMethod = contactData.method;
        contactValue = contactData.value;
      }
    }

    const orderData = {
      customer_name: customerName,
      customer_phone: customerPhone,
      customer_city: customerCity,
      customer_area: customerArea,
      customer_address: customerAddress,
      contact_method: contactMethod,
      contact_value: contactValue,
      items: this.items,
      subtotal: subtotal,
      packaging_total: packagingTotal,
      delivery_fee: deliveryFee,
      discount: window.appliedDiscountAmount || 0,
      coupon_code: window.appliedCouponCode || null,
      total: total,
      notes: notes,
      uploaded_images: uploadedImages,
      terms_consent: true,
      consent_timestamp: new Date().toISOString(),
    };

    try {
      const response = await fetch("api/submit-order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(orderData),
      });

      const result = await response.json();

      if (result.success) {
        document.getElementById("confirmModal").style.display = "none";
        document.getElementById("orderNumberDisplay").textContent =
          result.order_number;
        document.getElementById("successModal").style.display = "flex";
        this.clear();
      } else {
        showNotification(result.error || "حدث خطأ في إرسال الطلب", "error");
        this.resetConfirmButton();
      }
    } catch (error) {
      console.error("Checkout error:", error);
      showNotification("حدث خطأ في الاتصال. حاول مرة أخرى.", "error");
      this.resetConfirmButton();
    }
  },

  resetConfirmButton() {
    this.isProcessing = false;
    const confirmBtn = document.getElementById("confirmOrderBtn");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = "✅ تأكيد الطلب";
    }
  },

  clear() {
    this.items = [];
    this.save();
    this.updateUI();
    this.renderCart();
    this.isProcessing = false;
  },
};

// Modal functions
function closeConfirmModal() {
  document.getElementById("confirmModal").style.display = "none";
  Cart.isProcessing = false;
}

function copyOrderNumber() {
  const orderNumber = document.getElementById("orderNumberDisplay").textContent;
  navigator.clipboard.writeText(orderNumber).then(() => {
    showNotification("✓ تم نسخ رقم الطلب!", "success");
  });
}

function formatPrice(price) {
  return new Intl.NumberFormat("en-US").format(price) + " د.ع";
}

// Initializations
document.addEventListener("DOMContentLoaded", () => {
  Cart.init();
  if (document.getElementById("cartItems")) {
    Cart.renderCart();
    const citySelect = document.getElementById("customerCity");
    if (citySelect)
      citySelect.addEventListener("change", () => Cart.updateTotal());
  }

  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", (e) => {
      e.preventDefault();
      Cart.showConfirmation();
    });
  }

  const clearBtn = document.getElementById("clearCart");
  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      Cart.clear();
      showNotification("✓ تم تفريغ السلة", "success");
    });
  }

  // Bind coupon button
  const applyCouponBtn = document.getElementById("applyCouponBtn");
  if (applyCouponBtn) {
    applyCouponBtn.addEventListener("click", () => Cart.applyCoupon());
  }

  // Bind enter key on coupon input
  const couponCodeInput = document.getElementById("couponCode");
  if (couponCodeInput) {
    couponCodeInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        Cart.applyCoupon();
      }
    });
  }
});
