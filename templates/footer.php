</main>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
   <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
         <i class="bi bi-check-circle-fill text-success me-2"></i>
         <strong class="me-auto" id="toast-title">Thành công</strong>
         <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toast-body">
         Sản phẩm đã được thêm vào giỏ hàng!

      </div>
   </div>
</div>
<div class="modal fade" id="quick-add-modal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Tùy chọn sản phẩm</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="quick-add-modal-body">
            <div class="text-center">
               <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>


<div class="fab-container">
   <div class="fab-options">
      <a href="tel:0388105502" class="fab-option" title="Gọi điện">
         <i class="bi bi-telephone-fill"></i>
      </a>
      <a href="https://zalo.me/0388105502" target="_blank" class="fab-option" title="Zalo">
         <img src="/assets/images/zalo-icon.png" alt="Zalo">
      </a>
      <a href="https://m.me/khom.bedding23" target="_blank" class="fab-option" title="Messenger">
         <i class="bi bi-messenger"></i>
      </a>
   </div>
   <div class="fab-main" id="fab-main-btn" title="Liên hệ">
      <i class="bi bi-headset icon-main"></i>
      <i class="bi bi-x icon-close"></i>
   </div>
</div>


<footer class="site-footer">
   <div class="container">
      <div class="row">
         <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="footer-widget">
               <img src="/assets/images/logo.png" alt="Logo Khóm Bedding" class="footer-logo mb-3">
               <p>Khóm Bedding - Nâng tầm phòng ngủ, giá yêu thương cho mọi nhà. Chuyên cung cấp các sản phẩm chăn ga
                  gối đệm chất lượng cao.</p>
               <div class="footer-social">
                  <a href="https://www.facebook.com/khom.bedding23/" title="Facebook"><i class="bi bi-facebook"></i></a>
                  <a href="https://www.tiktok.com/@khom.bedding23" title="Tiktok"><i class="bi bi-tiktok"></i></a>
               </div>
            </div>
         </div>

         <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
            <div class="footer-widget">
               <h5 class="widget-title">Khám phá</h5>
               <ul class="list-unstyled footer-links">
                  <li><a href="/#homepage-collections">Bộ sưu tập</a></li>
                  <li><a href="/products.html">Sản phẩm</a></li>
                  <li><a href="/blog.html">Blog</a></li>
                  <li><a href="/contact.html">Liên hệ</a></li>
               </ul>
            </div>
         </div>

         <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="footer-widget">
               <h5 class="widget-title">Hỗ trợ khách hàng</h5>
               <ul class="list-unstyled footer-links">
                  <li><a href="#">Chính sách đổi trả</a></li>
                  <li><a href="#">Chính sách bảo mật</a></li>
                  <li><a href="#">Điều khoản dịch vụ</a></li>
                  <li><a href="#">Hướng dẫn mua hàng</a></li>
               </ul>
            </div>
         </div>

         <div class="col-lg-3 col-md-6">
            <div class="footer-widget">
               <h5 class="widget-title">Kết nối với chúng tôi</h5>
               <p>Theo dõi Khóm Bedding trên các trang mạng xã hội để không bỏ lỡ các sản phẩm mới và ưu đãi hấp dẫn!
               </p>
               <div class="footer-social-large">
                  <a href="https://www.facebook.com/khom.bedding23/" class="facebook" title="Facebook"
                     target="_blank"><i class="bi bi-facebook" style="font-size: 35px;"></i></a>
                  <a href="https://www.tiktok.com/@khom.bedding23" class="tiktok" title="Tiktok" target="_blank"><i
                        class="bi bi-tiktok"></i></a>
                  <a href="https://shopee.vn/khom.bedding23" class="shopee" title="Shopee" target="_blank"><img
                        src="/assets/images/shopee-logo.png" alt="Shopee"
                        style="width: 45px; height: 45px; vertical-align: middle;"></a>
               </div>
            </div>
         </div>
      </div>
   </div>
   <div class="footer-bottom">
      <div class="container">
         <p class="mb-0 text-center">&copy; <?php echo date('Y'); ?> Khóm Bedding. All Rights Reserved.</p>
      </div>
   </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/variant-selector.js"></script>
<script src="/assets/js/main.js"></script>
<script src="/assets/js/carousel-swipe.js"></script>
</body>

</html>