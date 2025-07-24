document.getElementById('exportExcel').addEventListener('click', function() {
    try {
        // Lấy thông tin về khoảng thời gian được chọn
        const timePeriod = document.getElementById('timePeriod').value;
        
        // Chuẩn bị dữ liệu để gửi đến server
        let params = {
            timePeriod: timePeriod
        };
        
        // Thêm các tham số phụ thuộc vào khoảng thời gian được chọn
        switch (timePeriod) {
            case 'day':
                const selectedMonth = document.getElementById('selectMonth').value;
                const selectedDay = document.getElementById('selectDay').value;
                const selectedYear = document.getElementById('selectYearForDay') ? 
                    document.getElementById('selectYearForDay').value : 
                    new Date().getFullYear();
                
                // Tạo selectedDate theo format Y-m-d như PHP mong đợi
                params.selectedDate = `${selectedYear}-${selectedMonth.padStart(2, '0')}-${selectedDay.padStart(2, '0')}`;
                break;
                
            case 'month':
                params.selectedMonth = document.getElementById('selectMonthOnly').value;
                params.selectedYear = document.getElementById('selectYearForMonth').value;
                break;
                
            case 'quarter':
                params.selectedQuarter = document.getElementById('selectQuarter').value;
                params.selectedYear = document.getElementById('selectYearForQuarter').value;
                break;
                
            case 'year':
                params.selectedYear = document.getElementById('selectYear').value;
                break;
        }
        
        // Tạo URL với các tham số
        const queryString = Object.keys(params)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
        
        const url = `../../app/controllers/ExcelController.php?${queryString}`;
        
        console.log('Attempting to download from:', url);
        
        // Thử phương pháp 1: Sử dụng fetch để kiểm tra response
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Kiểm tra content type
                const contentType = response.headers.get('Content-Type');
                console.log('Content-Type:', contentType);
                
                return response.blob();
            })
            .then(blob => {
                // Tạo URL cho blob
                const blobUrl = window.URL.createObjectURL(blob);
                
                // Tạo link download
                const downloadLink = document.createElement('a');
                downloadLink.href = blobUrl;
                downloadLink.download = `Thong_ke_don_hang_${timePeriod}_${new Date().toISOString().slice(0,10)}.xlsx`;
                downloadLink.style.display = 'none';
                
                // Thêm vào DOM, click và cleanup
                document.body.appendChild(downloadLink);
                downloadLink.click();
                
                // Cleanup
                setTimeout(() => {
                    document.body.removeChild(downloadLink);
                    window.URL.revokeObjectURL(blobUrl);
                }, 100);
            })
            .catch(error => {
                console.error('Download error:', error);
                alert('Có lỗi xảy ra khi tải file Excel: ' + error.message);
                
                // Fallback: Thử mở trực tiếp trong tab mới
                window.open(url, '_blank');
            });
            
    } catch (error) {
        console.error('Export error:', error);
        alert('Có lỗi xảy ra: ' + error.message);
    }
});