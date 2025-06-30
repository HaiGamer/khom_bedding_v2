document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    const filterForm = document.getElementById('revenue-filter-form');

    // KIỂM TRA AN TOÀN: Chỉ chạy code nếu các element cần thiết tồn tại
    if (!ctx || !filterForm) {
        console.error('Không tìm thấy Canvas hoặc Form lọc cho biểu đồ. Script sẽ không chạy.');
        return;
    }

    let revenueChartInstance = null; // Biến để lưu trữ đối tượng biểu đồ

    // Hàm để vẽ hoặc cập nhật biểu đồ
    function renderOrUpdateChart(chartLabels, chartValues) {
        const chartData = {
            labels: chartLabels,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: chartValues,
                fill: true,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.2
            }]
        };
        
        // Các tùy chọn cho biểu đồ
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: value => new Intl.NumberFormat('vi-VN').format(value) + ' đ' }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => `${context.dataset.label || ''}: ${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed.y)}`
                    }
                }
            }
        };

        if (revenueChartInstance) {
            revenueChartInstance.data.labels = chartLabels;
            revenueChartInstance.data.datasets[0].data = chartValues;
            revenueChartInstance.update();
        } else {
            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: chartOptions
            });
        }
    }

    // Hàm để lấy dữ liệu từ API
    function fetchChartData(startDate, endDate) {
        const url = `/admin/api/dashboard/get-chart-data.php?start=${startDate}&end=${endDate}`;
        
        fetch(url)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Lỗi mạng: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    renderOrUpdateChart(data.labels, data.values);
                } else {
                    alert('Không thể tải dữ liệu biểu đồ.');
                }
            })
            .catch(err => {
                console.error("Lỗi khi fetch dữ liệu biểu đồ:", err);
                ctx.parentElement.innerHTML = '<p class="text-center text-danger">Không thể tải biểu đồ.</p>';
            });
    }

    // Xử lý form lọc
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const startDate = this.elements.start.value;
        const endDate = this.elements.end.value;
        if (startDate && endDate && startDate <= endDate) {
            fetchChartData(startDate, endDate);
        } else {
            alert('Vui lòng chọn khoảng ngày hợp lệ.');
        }
    });

    // Tải dữ liệu biểu đồ lần đầu với giá trị mặc định
    const initialStartDate = filterForm.elements.start.value;
    const initialEndDate = filterForm.elements.end.value;
    if (initialStartDate && initialEndDate) {
        fetchChartData(initialStartDate, initialEndDate);
    }
});