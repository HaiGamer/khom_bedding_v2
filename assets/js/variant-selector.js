/**
 * Lớp VariantSelector quản lý toàn bộ logic
 * chọn phiên bản, cập nhật giá, SKU, ảnh và trạng thái các nút.
 */
class VariantSelector {
    constructor(container, variantsData) {
        this.container = container;
        this.variantsData = variantsData;

        if (!this.container || !this.variantsData || this.variantsData.length === 0) {
            console.error("Thiếu container hoặc dữ liệu phiên bản để khởi tạo VariantSelector.");
            return;
        }

        // Tìm các phần tử con bên trong container
        this.optionsContainer = this.container.querySelector('.variant-options-container');
        this.mainImage = this.container.querySelector('.main-product-image');
        this.skuElement = this.container.querySelector('.product-sku');
        this.priceElement = this.container.querySelector('.product-price .price-sale');
        this.originalPriceElement = this.container.querySelector('.product-price .price-original');
        this.addToCartButton = this.container.querySelector('.btn-add-to-cart-submit');
        this.quantityInput = this.container.querySelector('.quantity-input input');
        
        // Trạng thái
        this.selectedOptions = {};
        this.primaryGroupName = null;

        this.initialize();
    }

    initialize() {
        this.renderOptionButtons();
        this.addEventListeners();
        
        const defaultVariant = this.variantsData.find(v => v.is_default) || this.variantsData[0];
        if (defaultVariant) {
            this.selectedOptions = { ...defaultVariant.attributes };
        }
        
        this.updateView();
    }
    
    addEventListeners() {
        this.optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(button => {
            button.addEventListener('click', this.handleOptionClick.bind(this));
        });

        if (this.addToCartButton) {
            this.addToCartButton.addEventListener('click', this.handleAddToCart.bind(this));
        }
    }

    handleAddToCart() {
        const selectedVariant = this.findMatchingVariant(this.selectedOptions);
        if (selectedVariant) {
            const quantity = this.quantityInput ? parseInt(this.quantityInput.value, 10) : 1;
            if (quantity > 0) {
                window.addToCart(selectedVariant.id, quantity); // Gọi hàm toàn cục
            }
        } else {
            window.showToast('Lỗi', 'Vui lòng chọn đầy đủ phiên bản sản phẩm.', false);
        }
    }

    handleOptionClick(event) {
        const clickedButton = event.target;
        const { attributeGroup, attributeValue } = clickedButton.dataset;
        this.selectedOptions[attributeGroup] = attributeValue;
        this.validateAndAdjustSelections(attributeGroup);
        this.updateView();
    }

    validateAndAdjustSelections(changedGroup) {
        const compatibleVariants = this.variantsData.filter(v => v.attributes[changedGroup] === this.selectedOptions[changedGroup]);
        for (const group in this.selectedOptions) {
            if (group === changedGroup) continue;
            const currentGroupValue = this.selectedOptions[group];
            const isStillValid = compatibleVariants.some(v => v.attributes[group] === currentGroupValue);
            if (!isStillValid && compatibleVariants.length > 0) {
                this.selectedOptions[group] = compatibleVariants[0].attributes[group];
            }
        }
    }

    updateView() {
        const currentVariant = this.findMatchingVariant(this.selectedOptions);
        this.updateProductDetails(currentVariant);
        this.updateActiveButtons(this.selectedOptions);
        this.updateDisabledStates(this.selectedOptions);
    }
    
    updateProductDetails(variant) {
        if (variant) {
            if(this.priceElement) this.priceElement.textContent = this.formatCurrency(variant.price);
            if(this.originalPriceElement) this.originalPriceElement.textContent = variant.original_price ? this.formatCurrency(variant.original_price) : '';
            if(this.skuElement) this.skuElement.textContent = `SKU: ${variant.sku || 'N/A'}`;
            if(this.mainImage && variant.image_url) this.mainImage.src = variant.image_url;
        }
    }

    updateActiveButtons(currentSelection) {
        this.optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(btn => {
            const { attributeGroup, attributeValue } = btn.dataset;
            const isActive = currentSelection[attributeGroup] === attributeValue;
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-secondary', isActive);
            btn.classList.toggle('btn-outline-secondary', !isActive);
        });
    }

    updateDisabledStates(currentSelection) {
        this.optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(btn => {
            const { attributeGroup, attributeValue } = btn.dataset;
            if (attributeGroup === this.primaryGroupName) {
                btn.disabled = false;
                return;
            }
            const testCondition = {};
            for (const group in currentSelection) {
                if (group !== attributeGroup) testCondition[group] = currentSelection[group];
            }
            testCondition[attributeGroup] = attributeValue;
            btn.disabled = !this.findMatchingVariant(testCondition);
        });
    }

    renderOptionButtons() {
        const attributeGroups = {};
        this.variantsData.forEach(variant => {
            for (const group in variant.attributes) {
                if (!attributeGroups[group]) attributeGroups[group] = new Set();
                attributeGroups[group].add(variant.attributes[group]);
            }
        });

        this.optionsContainer.innerHTML = '';
        let isFirstGroup = true;
        for (const group in attributeGroups) {
            if (isFirstGroup) {
                this.primaryGroupName = group;
                isFirstGroup = false;
            }
            const groupContainer = document.createElement('div');
            groupContainer.className = 'variant-group mb-3';
            groupContainer.innerHTML = `
                <label class="form-label fw-bold">${group}:</label>
                <div class="d-flex flex-wrap gap-2">
                    ${[...attributeGroups[group]].map(value => `
                        <button class="btn btn-outline-secondary" data-attribute-group="${group}" data-attribute-value="${value}">
                            ${value}
                        </button>
                    `).join('')}
                </div>
            `;
            this.optionsContainer.appendChild(groupContainer);
        }
    }

    findMatchingVariant(options) {
        const optionKeys = Object.keys(options);
        if (optionKeys.length === 0) return null;
        return this.variantsData.find(variant => {
            return optionKeys.every(key => variant.attributes[key] === options[key]);
        });
    }

    formatCurrency(number) {
        if (isNaN(parseFloat(number))) return '';
        return parseFloat(number).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
    }
}