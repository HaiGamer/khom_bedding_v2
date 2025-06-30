document.addEventListener('DOMContentLoaded', function() {
    // Tự động tạo slug
    const titleInput = document.getElementById('post-title');
    const slugInput = document.getElementById('post-slug');
    if (titleInput && slugInput) {
        titleInput.addEventListener('keyup', () => slugInput.value = generateSlug(titleInput.value));
    }

    function generateSlug(text) { /* ... copy hàm generateSlug từ file js trước ... */ }
});

// Khởi tạo CKEditor sau khi trang tải xong
window.addEventListener('load', () => {
    const contentTextarea = document.getElementById('post-content');
    if (contentTextarea && typeof ClassicEditor !== 'undefined') {
        ClassicEditor.create(contentTextarea).catch(err => console.error(err));
    }
});