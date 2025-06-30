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

// =======================================================
// === THAY THẾ TOÀN BỘ HÀM DOCSO CŨ BẰNG HÀM MỚI NÀY ===
// =======================================================
if (!function_exists('docso')) {
    /**
     * Chuyển đổi số thành chữ tiếng Việt.
     * @param float $number Số cần chuyển đổi.
     * @return string Chuỗi ký tự tiếng Việt.
     */
    function docso($number) {
        $hyphen = ' ';
        $conjunction = ' ';
        $separator = ' ';
        $negative = 'âm ';
        $decimal = '  ';
        $dictionary = [
            0 => '',
            1 => 'một',
            2 => 'hai',
            3 => 'ba',
            4 => 'bốn',
            5 => 'năm',
            6 => 'sáu',
            7 => 'bảy',
            8 => 'tám',
            9 => 'chín',
            10 => 'mười',
            11 => 'mười một',
            12 => 'mười hai',
            13 => 'mười ba',
            14 => 'mười bốn',
            15 => 'mười lăm',
            16 => 'mười sáu',
            17 => 'mười bảy',
            18 => 'mười tám',
            19 => 'mười chín',
            20 => 'hai mươi',
            30 => 'ba mươi',
            40 => 'bốn mươi',
            50 => 'năm mươi',
            60 => 'sáu mươi',
            70 => 'bảy mươi',
            80 => 'tám mươi',
            90 => 'chín mươi',
            100 => 'trăm',
            1000 => 'nghìn',
            1000000 => 'triệu',
            1000000000 => 'tỷ',
            1000000000000 => 'nghìn tỷ',
            1000000000000000 => 'triệu tỷ',
            1000000000000000000 => 'tỷ tỷ'
        ];

        if (!is_numeric($number)) {
            return false;
        }

        if ($number < 0) {
            return $negative . docso(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    // Xử lý "mươi mốt" và "mươi lăm"
                    if ($units == 1) {
                        $string .= $hyphen . 'mốt';
                    } else if ($units == 5) {
                        $string .= $hyphen . 'lăm';
                    } else {
                        $string .= $hyphen . $dictionary[$units];
                    }
                }
                break;
            case $number < 1000:
                $hundreds = floor($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    // Xử lý "trăm linh"
                    if ($remainder < 10) {
                        $string .= $conjunction . 'linh ' . $dictionary[$remainder];
                    } else {
                        $string .= $conjunction . docso($remainder);
                    }
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = docso($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    // Nếu phần dư có số chữ số nhỏ hơn phần đơn vị chính, thêm "không trăm"
                    if ($remainder < ($baseUnit / 10)) {
                        // Logic này có thể cần điều chỉnh cho các trường hợp phức tạp hơn
                    }
                    $string .= $remainder < 100 ? $conjunction . 'không ' : $separator;
                    $string .= docso($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string)$fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
}

// Trong tương lai, các hàm tiện ích khác của trang admin sẽ được thêm vào đây...