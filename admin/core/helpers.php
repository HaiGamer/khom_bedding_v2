<?php

if (!function_exists('get_status_badge')) {
    /**
     * Chuyển đổi trạng thái đơn hàng (text) thành class và text của Bootstrap badge.
     * @param string $status Trạng thái đơn hàng từ CSDL.
     * @return array Mảng chứa 'class' và 'text' cho badge.
     */
    function get_status_badge($status) {
        switch ($status) {
            case 'pending':
                return ['class' => 'bg-warning text-dark', 'text' => 'Chờ xử lý'];
            case 'processing':
                return ['class' => 'bg-info text-dark', 'text' => 'Đang xử lý'];
            case 'shipped':
                return ['class' => 'bg-primary', 'text' => 'Đang giao'];
            case 'completed':
                return ['class' => 'bg-success', 'text' => 'Đã hoàn thành'];
            case 'cancelled':
                return ['class' => 'bg-danger', 'text' => 'Đã hủy'];
            default:
                return ['class' => 'bg-secondary', 'text' => 'Không xác định'];
        }
    }
}

// Trong tương lai, các hàm tiện ích khác của trang admin sẽ được thêm vào đây...