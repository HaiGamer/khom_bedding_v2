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
<footer class="bg-light text-center p-4 mt-5">
   <div class="container">
      <p>&copy; <?php echo date('Y'); ?> Khóm Bedding. All Rights Reserved.</p>
   </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
<script src="/assets/js/variant-selector.js"></script>
<script src="/assets/js/carousel-swipe.js"></script>
</body>

</html>