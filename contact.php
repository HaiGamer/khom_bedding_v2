<?php
$page_title = "Liên Hệ - Khóm Bedding";
$page_description = "Liên hệ với Khóm Bedding để được tư vấn về sản phẩm, chính sách mua hàng hoặc các vấn đề khác. Chúng tôi luôn sẵn lòng hỗ trợ bạn.";
include 'templates/header.php';
?>

<div class="container my-5">
   <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
         <h1 class="mb-3">Liên Hệ Với Chúng Tôi</h1>
         <p class="lead mb-5">Nếu bạn có bất kỳ câu hỏi hay góp ý nào, đừng ngần ngại gửi tin nhắn cho chúng tôi. Khóm
            Bedding luôn sẵn lòng lắng nghe!</p>
      </div>
   </div>
   <div class="row">
      <div class="col-lg-4">
         <div class="contact-info-wrapper">
            <h4>Thông tin liên hệ</h4>
            <div class="info-item">
               <i class="bi bi-geo-alt-fill"></i>
               <p>123 Đường ABC, TP. Thủ Dầu Một, Bình Dương</p>
            </div>
            <div class="info-item">
               <i class="bi bi-telephone-fill"></i>
               <p>(1900) 1234</p>
            </div>
            <div class="info-item">
               <i class="bi bi-envelope-fill"></i>
               <p>hotro@khombedding.com</p>
            </div>
         </div>
      </div>
      <div class="col-lg-8">
         <div class="card p-4 contact-form-card">
            <form id="contact-form">
               <div id="contact-alert" class="alert d-none" role="alert"></div>
               <div class="row">
                  <div class="col-12 mb-3">
                     <label for="contact-name" class="form-label">Họ và Tên</label>
                     <input type="text" class="form-control" id="contact-name" name="name" required>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label for="contact-email" class="form-label">Email</label>
                     <input type="email" class="form-control" id="contact-email" name="email" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label for="contact-phone" class="form-label">Số điện thoại (tùy chọn)</label>
                     <input type="tel" class="form-control" id="contact-phone" name="phone">
                  </div>
               </div>
               <div class="mb-3"><label for="contact-subject" class="form-label">Chủ đề</label><input type="text"
                     class="form-control" id="contact-subject" name="subject" required></div>
               <div class="mb-3"><label for="contact-message" class="form-label">Nội dung tin nhắn</label><textarea
                     class="form-control" id="contact-message" name="message" rows="5" required></textarea></div>
               <div class="mb-3 d-flex justify-content-center">
                  <div class="h-captcha" data-sitekey="32f4ae2a-3f62-4624-8b84-d4ca3cc6072e"></div>
               </div>
               <button type="submit" class="btn btn-primary btn-lg w-100">Gửi Tin Nhắn</button>
            </form>
         </div>
      </div>
   </div>
</div>

<script src="/assets/js/contact.js"></script>
<?php include 'templates/footer.php'; ?>