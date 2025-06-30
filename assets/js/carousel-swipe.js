document.addEventListener('DOMContentLoaded', function () {
    const carouselElement = document.getElementById('heroCarousel');
    if (!carouselElement) return;

    // Lấy đối tượng carousel của Bootstrap để điều khiển
    const bootstrapCarousel = new bootstrap.Carousel(carouselElement, {
        ride: 'carousel', // Cho phép tự động trượt
        pause: 'hover',   // Tạm dừng khi di chuột vào
        interval: 5000    // Thời gian chuyển slide
    });

    let isPointerDown = false;
    let startX;
    
    // Sự kiện khi người dùng nhấn chuột xuống
    carouselElement.addEventListener('mousedown', (e) => {
        // Ngăn chặn các hành vi mặc định như kéo-thả ảnh
        e.preventDefault(); 
        isPointerDown = true;
        // Ghi lại vị trí bắt đầu nhấn chuột
        startX = e.pageX;
        // Thêm class để đổi kiểu con trỏ chuột
        carouselElement.classList.add('active-swipe');
    });

    // Sự kiện khi người dùng nhả chuột ra
    carouselElement.addEventListener('mouseup', (e) => {
        if (!isPointerDown) return;
        isPointerDown = false;
        carouselElement.classList.remove('active-swipe');

        const endX = e.pageX;
        const walk = endX - startX; // Tính toán khoảng cách đã kéo

        // Nếu khoảng cách kéo sang phải lớn hơn 50px, chuyển slide trước
        if (walk > 50) {
            bootstrapCarousel.prev();
        }
        // Nếu khoảng cách kéo sang trái lớn hơn 50px, chuyển slide tiếp theo
        else if (walk < -50) {
            bootstrapCarousel.next();
        }
    });

    // Sự kiện khi con trỏ chuột rời khỏi khu vực slider
    carouselElement.addEventListener('mouseleave', () => {
        isPointerDown = false;
        carouselElement.classList.remove('active-swipe');
    });

    // Ngăn chặn sự kiện click được kích hoạt sau khi kéo-thả
    carouselElement.addEventListener('click', (e) => {
        // Nếu người dùng chỉ kéo một khoảng cách ngắn, vẫn cho phép hành động click (ví dụ: click vào link)
        // Tuy nhiên, để đơn giản, chúng ta có thể tạm thời chặn tất cả click khi đang kéo
        // Nếu bạn muốn logic phức tạp hơn, chúng ta sẽ thêm sau.
    }, true);

});