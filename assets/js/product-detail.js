document.addEventListener('DOMContentLoaded', () => {
    // === KIỂM TRA DỮ LIỆU ĐẦU VÀO ===
    if (typeof productVariantsData === 'undefined' || productVariantsData.length === 0) {
        return;
    }

    // === LẤY CÁC PHẦN TỬ DOM ===
    // Lấy phần tử container chính của khu vực chi tiết sản phẩm
    const productDetailsContainer = document.querySelector('.product-details');
    const optionsContainer = document.getElementById('variant-options-container');
    const mainImage = document.getElementById('main-product-image');
    const skuElement = document.getElementById('product-sku');
    const priceElement = document.getElementById('product-price');
    const originalPriceElement = document.getElementById('product-original-price');
    const thumbnails = document.querySelectorAll('.thumbnail-image');
    
    // === BIẾN TRẠNG THÁI TRUNG TÂM ===
    let selectedOptions = {};
    let primaryGroupName = null; // Biến mới để lưu tên nhóm chính

     // ===================================================================
    // === LOGIC MỚI: XỬ LÝ NÚT "THÊM VÀO GIỎ" CỦA TRANG CHI TIẾT ===
    // ===================================================================
    const detailAddToCartBtn = document.getElementById('btn-add-to-cart-detail');
    if (detailAddToCartBtn) {
        detailAddToCartBtn.addEventListener('click', function() {
            // Lấy phiên bản đang được chọn
            const selectedVariant = findMatchingVariant(selectedOptions);
            
            if (selectedVariant) {
                const quantityInput = document.querySelector('.quantity-input input');
                const quantity = parseInt(quantityInput.value, 10);

                if (quantity > 0) {
                    // Gọi hàm addToCart toàn cục (sẽ tạo ở dưới)
                    window.addToCart(selectedVariant.id, quantity);
                } else {
                    window.showToast('Lỗi', 'Số lượng phải lớn hơn 0.', false);
                }
            } else {
                window.showToast('Lỗi', 'Vui lòng chọn đầy đủ phiên bản sản phẩm.', false);
            }
        });
    }

    // Tăng giảm số lượng
    const quantityInputContainer = document.querySelector('.quantity-input');
    if(quantityInputContainer) {
        const quantityInput = quantityInputContainer.querySelector('input');
        const btnMinus = quantityInputContainer.querySelector('button:first-child');
        const btnPlus = quantityInputContainer.querySelector('button:last-child');

        btnMinus.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value, 10);
            if(currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        btnPlus.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value, 10);
            quantityInput.value = currentValue + 1;
        });
    }


    // ===================================================================
    // === HÀM KHỞI TẠO CHÍNH ===
    // ===================================================================
    function initialize() {
        renderOptionButtons();

        optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(button => {
            button.addEventListener('click', handleOptionClick);
        });

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                mainImage.src = thumbnail.src;
                document.querySelector('.thumbnail-image.active')?.classList.remove('active');
                thumbnail.classList.add('active');
            });
        });

        const defaultVariant = productVariantsData.find(v => v.is_default) || productVariantsData[0];
        if (defaultVariant) {
            selectedOptions = { ...defaultVariant.attributes };
        }
        
        updateView();
    }


    // ===================================================================
    // === CÁC HÀM XỬ LÝ SỰ KIỆN VÀ LOGIC ===
    // ===================================================================

    function handleOptionClick(event) {
        const clickedButton = event.target;
        const { attributeGroup, attributeValue } = clickedButton.dataset;

        selectedOptions[attributeGroup] = attributeValue;
        validateAndAdjustSelections(attributeGroup);
        updateView();
    }

    function validateAndAdjustSelections(changedGroup) {
        const compatibleVariants = productVariantsData.filter(v => v.attributes[changedGroup] === selectedOptions[changedGroup]);
        for (const group in selectedOptions) {
            if (group === changedGroup) continue;
            const currentGroupValue = selectedOptions[group];
            const isStillValid = compatibleVariants.some(v => v.attributes[group] === currentGroupValue);
            if (!isStillValid && compatibleVariants.length > 0) {
                selectedOptions[group] = compatibleVariants[0].attributes[group];
            }
        }
    }


    // ===================================================================
    // === CÁC HÀM CẬP NHẬT GIAO DIỆN (UI) ===
    // ===================================================================

    function updateView() {
        const currentVariant = findMatchingVariant(selectedOptions);
        updateProductDetails(currentVariant);
        updateActiveButtons(selectedOptions);
        updateDisabledStates(selectedOptions);
    }
    
    function updateProductDetails(variant) {
        if (variant) {
            priceElement.textContent = formatCurrency(variant.price);
            originalPriceElement.textContent = variant.original_price ? formatCurrency(variant.original_price) : '';
            skuElement.textContent = `SKU: ${variant.sku || 'N/A'}`;
            if (variant.image_url) {
                mainImage.src = variant.image_url;
            }
        }
    }

    function updateActiveButtons(currentSelection) {
        optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(btn => {
            const { attributeGroup, attributeValue } = btn.dataset;
            const isActive = currentSelection[attributeGroup] === attributeValue;
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-secondary', isActive);
            btn.classList.toggle('btn-outline-secondary', !isActive);
        });
    }

    /**
     * Cập nhật trạng thái 'disabled' cho các nút không hợp lệ.
     * ĐÃ NÂNG CẤP: Không vô hiệu hóa các nút của nhóm chính.
     */
    function updateDisabledStates(currentSelection) {
        optionsContainer.querySelectorAll('button[data-attribute-group]').forEach(btn => {
            const { attributeGroup, attributeValue } = btn.dataset;

            // --- LOGIC MỚI ---
            // Nếu nút này thuộc nhóm thuộc tính chính, không bao giờ vô hiệu hóa nó.
            if (attributeGroup === primaryGroupName) {
                btn.disabled = false;
                return; // Bỏ qua và chuyển sang nút tiếp theo
            }
            // --- KẾT THÚC LOGIC MỚI ---

            const testCondition = {};
            for (const group in currentSelection) {
                if (group !== attributeGroup) {
                    testCondition[group] = currentSelection[group];
                }
            }
            testCondition[attributeGroup] = attributeValue;
            
            btn.disabled = !findMatchingVariant(testCondition);
        });
    }


    // ===================================================================
    // === CÁC HÀM TIỆN ÍCH VÀ KHỞI TẠO ===
    // ===================================================================

    /**
     * Dựng HTML và xác định nhóm thuộc tính chính.
     */
    function renderOptionButtons() {
        const attributeGroups = {};
        productVariantsData.forEach(variant => {
            for (const group in variant.attributes) {
                if (!attributeGroups[group]) attributeGroups[group] = new Set();
                attributeGroups[group].add(variant.attributes[group]);
            }
        });

        optionsContainer.innerHTML = '';
        let isFirstGroup = true; // Cờ để xác định nhóm đầu tiên
        for (const group in attributeGroups) {
            // --- LOGIC MỚI ---
            // Gán tên của nhóm đầu tiên vào biến primaryGroupName
            if (isFirstGroup) {
                primaryGroupName = group;
                isFirstGroup = false;
            }
            // --- KẾT THÚC LOGIC MỚI ---

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
            optionsContainer.appendChild(groupContainer);
        }
    }

    function findMatchingVariant(options) {
        const optionKeys = Object.keys(options);
        if (optionKeys.length === 0) return null;
        return productVariantsData.find(variant => {
            return optionKeys.every(key => variant.attributes[key] === options[key]);
        });
    }

    function formatCurrency(number) {
        if (isNaN(parseFloat(number))) return '';
        return parseFloat(number).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
    }

    // --- BẮT ĐẦU CHẠY SCRIPT ---
    initialize();



   // ===================================================================
    // === LOGIC MỚI: TAB ĐÁNH GIÁ ===
    // ===================================================================

    // 1. Vẽ các ngôi sao cho các đánh giá đã có
    document.querySelectorAll('.review-stars').forEach(starContainer => {
        const rating = parseFloat(starContainer.dataset.rating);
        if (isNaN(rating)) return;
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) html += '<i class="bi bi-star-fill"></i>';
            else if (i - 0.5 <= rating) html += '<i class="bi bi-star-half"></i>';
            else html += '<i class="bi bi-star"></i>';
        }
        starContainer.innerHTML = html;
    });

    // 2. Xử lý hiệu ứng cho form nhập đánh giá
    const ratingStars = document.querySelectorAll('.star-rating-input > i');
    const ratingValueInput = document.getElementById('rating-value');
    if(ratingStars.length > 0) {
        const resetStars = () => ratingStars.forEach(s => s.classList.remove('hover'));
        ratingStars.forEach(star => {
            star.addEventListener('mouseover', () => {
                resetStars();
                const hoverValue = star.dataset.value;
                ratingStars.forEach(s => { if(s.dataset.value <= hoverValue) s.classList.add('hover'); });
            });
            star.addEventListener('mouseout', resetStars);
            star.addEventListener('click', () => {
                const clickedValue = star.dataset.value;
                ratingValueInput.value = clickedValue;
                ratingStars.forEach(s => s.classList.toggle('active', s.dataset.value <= clickedValue));
            });
        });
    }

    // 3. Xử lý xem trước ảnh khi upload
    const imageInputElement = document.getElementById('review-images');
    const previewContainer = document.getElementById('image-preview-container');
    if (imageInputElement) {
        imageInputElement.addEventListener('change', function(event) {
            previewContainer.innerHTML = ''; // Xóa ảnh cũ
            const files = Array.from(event.target.files);
            if (files.length > 5) {
                alert('Bạn chỉ có thể tải lên tối đa 5 ảnh.');
                this.value = ''; // Xóa file đã chọn
                return;
            }
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'img-preview-wrapper';
                    wrapper.innerHTML = `
                        <img src="${e.target.result}" class="preview-img">
                        <button type="button" class="remove-img-btn" title="Xóa ảnh">&times;</button>
                    `;
                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // 4. Xử lý gửi form đánh giá bằng AJAX
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('submit-review-btn');
            const alertBox = document.getElementById('review-form-alert');
            
            // Tạo đối tượng FormData để gửi cả file
            const formData = new FormData(reviewForm);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang gửi...';

            fetch('/api/submit-review.php', {
                method: 'POST',
                body: formData // Gửi FormData, không cần set Content-Type
            })
            .then(response => response.json())
            .then(data => {
                alertBox.classList.remove('d-none', 'alert-danger', 'alert-success');
                alertBox.classList.add(data.success ? 'alert-success' : 'alert-danger');
                alertBox.textContent = data.message;

                if(data.success) {
                    reviewForm.reset();
                    previewContainer.innerHTML = '';
                    reviewForm.style.display = 'none'; // Ẩn form sau khi gửi thành công
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertBox.classList.remove('d-none', 'alert-success');
                alertBox.classList.add('alert-danger');
                alertBox.textContent = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Gửi đánh giá';
            });
        });
    }

});