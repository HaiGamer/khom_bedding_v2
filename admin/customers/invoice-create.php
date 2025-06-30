<?php
// Đảm bảo đường dẫn chính xác để đi ngược ra 2 cấp thư mục (từ /admin/customers/ về thư mục gốc)
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';

$page_title = "Tạo Hóa Đơn Mới";
// Đảm bảo đường dẫn chính xác để đi ngược ra 1 cấp thư mục (từ /admin/customers/ về /admin/)
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
?>

<div class="p-4">
   <h1 class="mb-4">Tạo Hóa Đơn Bán Hàng</h1>
   <form id="invoice-form">
      <input type="hidden" name="action" value="add">
      <div class="row">
         <div class="col-lg-8">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin khách hàng</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="customer-search" class="form-label">Tìm kiếm khách hàng (theo Tên hoặc SĐT)</label>
                     <div class="position-relative">
                        <input type="text" class="form-control" id="customer-search" autocomplete="off"
                           placeholder="Gõ để tìm khách hàng đã lưu...">
                        <div id="customer-search-results" class="list-group position-absolute w-100"
                           style="z-index: 1000;"></div>
                     </div>
                  </div>
                  <hr>
                  <input type="hidden" name="customer_id" id="customer_id">
                  <div class="row">
                     <div class="col-md-6 mb-3"><label class="form-label">Tên khách hàng</label><input type="text"
                           class="form-control" name="customer_name" id="customer_name" required></div>
                     <div class="col-md-6 mb-3"><label class="form-label">Số điện thoại</label><input type="tel"
                           class="form-control" name="customer_phone" id="customer_phone"></div>
                  </div>
                  <div class="mb-3"><label class="form-label">Địa chỉ</label><textarea class="form-control"
                        name="customer_address" id="customer_address" rows="2"></textarea></div>
               </div>
            </div>

            <div class="card">
               <div class="card-header"><strong>Chi tiết hóa đơn</strong></div>
               <div class="card-body table-responsive">
                  <table class="table">
                     <thead class="table-light">
                        <tr>
                           <th>Sản phẩm</th>
                           <th style="width: 120px;">Số lượng</th>
                           <th style="width: 170px;">Đơn giá</th>
                           <th class="text-end">Thành tiền</th>
                           <th></th>
                        </tr>
                     </thead>
                     <tbody id="invoice-items-body">
                        <tr class="placeholder-row">
                           <td colspan="5" class="text-center text-muted">Thêm sản phẩm từ ô tìm kiếm bên dưới</td>
                        </tr>
                     </tbody>
                     <tfoot>
                        <tr>
                           <td colspan="3" class="text-end border-0"><strong>Tổng cộng</strong></td>
                           <td class="text-end h5 fw-bold border-0" id="invoice-total">0đ</td>
                           <td class="border-0"></td>
                        </tr>
                     </tfoot>
                  </table>
               </div>
            </div>
         </div>

         <div class="col-lg-4">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin hóa đơn</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label class="form-label">Mã hóa đơn</label>
                     <input type="text" class="form-control" value="Tự động tạo" disabled>
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Trạng thái</label>
                     <select name="status" class="form-select">
                        <option value="paid" selected>Đã thanh toán</option>
                        <option value="unpaid">Chưa thanh toán</option>
                        <option value="debt">Công nợ</option>
                     </select>
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Ghi chú</label>
                     <textarea class="form-control" name="note" rows="2"></textarea>
                  </div>
               </div>
            </div>

            <div class="card">
               <div class="card-header"><strong>Thêm sản phẩm vào hóa đơn</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="product-search" class="form-label">Tìm kiếm sản phẩm</label>
                     <div class="position-relative">
                        <input type="text" class="form-control" id="product-search" autocomplete="off"
                           placeholder="Gõ tên hoặc SKU...">
                        <div id="product-search-results" class="list-group position-absolute w-100"
                           style="z-index: 1000;"></div>
                     </div>
                  </div>
                  <div class="row">
                     <div class="col-6"><label class="form-label">Số lượng</label><input type="number"
                           id="item-quantity" class="form-control" value="1" min="1"></div>
                     <div class="col-6"><label class="form-label">Đơn giá</label><input type="number" id="item-price"
                           class="form-control" placeholder="Giá bán"></div>
                  </div>
                  <div class="d-grid mt-3">
                     <button type="button" class="btn btn-primary" id="btn-add-item">Thêm vào hóa đơn</button>
                  </div>
               </div>
            </div>

            <div class="d-grid mt-4">
               <button type="submit" class="btn btn-success btn-lg">Tạo & Lưu Hóa Đơn</button>
            </div>
         </div>
      </div>
   </form>
</div>

<script src="/admin/assets/js/admin-invoice-create.js"></script>
<?php include __DIR__ . '/../templates/footer.php'; ?>