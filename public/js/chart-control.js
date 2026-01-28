/**
 * Script untuk menangani Grafik Level Air (Chart.js)
 * Mendukung mode Live dan History
 */

let waterChart = null;
let liveInterval = null;
let currentControllerId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Ambil ID controller dari elemen tersembunyi atau URL
    const urlParams = new URLSearchParams(window.location.search);
    // Fallback: mencoba mencari input hidden jika ada, atau parsing dari URL path jika menggunakan pretty URL
    // Asumsi: controller_id tersedia di variabel global atau atribut data
    const chartCanvas = document.getElementById('waterLevelChart');
    
    if (chartCanvas) {
        currentControllerId = chartCanvas.getAttribute('data-controller-id');
        initChart(chartCanvas);
        
        // Load default (Live Mode / 1 Jam terakhir)
        setChartRange('live');
    }
});

function initChart(canvas) {
    const ctx = canvas.getContext('2d');
    
    // Gradien untuk area di bawah grafik
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(52, 152, 219, 0.5)'); // Biru transparan
    gradient.addColorStop(1, 'rgba(52, 152, 219, 0.0)');

    waterChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Level Air (%)',
                data: [],
                borderColor: '#3498db',
                backgroundColor: gradient,
                borderWidth: 2,
                pointRadius: 0, // Hilangkan titik agar garis halus (muncul saat hover)
                pointHoverRadius: 4,
                fill: true,
                tension: 0.4 // Garis melengkung halus
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: { maxTicksLimit: 8 } // Batasi label sumbu X
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Persentase (%)' }
                }
            },
            animation: {
                duration: 0 // Matikan animasi saat update live agar tidak berat
            }
        }
    });
}

function setChartRange(range) {
    // Update UI tombol aktif
    document.querySelectorAll('.chart-btn').forEach(btn => btn.classList.remove('active', 'btn-primary'));
    document.querySelectorAll('.chart-btn').forEach(btn => btn.classList.add('btn-secondary')); // Reset ke abu-abu
    
    const activeBtn = document.querySelector(`.chart-btn[data-range="${range}"]`);
    if(activeBtn) {
        activeBtn.classList.remove('btn-secondary');
        activeBtn.classList.add('active', 'btn-primary');
    }

    // Hentikan interval live jika ada
    if (liveInterval) {
        clearInterval(liveInterval);
        liveInterval = null;
    }

    if (range === 'live') {
        fetchChartData(60); // Ambil 60 menit terakhir untuk inisialisasi
        // Set interval untuk update setiap 5 detik
        liveInterval = setInterval(() => fetchChartData(60), 5000);
    } else {
        fetchChartData(range);
    }
}

function fetchChartData(minutes) {
    if (!currentControllerId) return;

    fetch(`${BASE_URL}api/device/history?id=${currentControllerId}&range=${minutes}`)
        .then(response => response.json())
        .then(data => {
            updateChart(data);
        })
        .catch(err => console.error('Gagal memuat data grafik:', err));
}

function updateChart(data) {
    if (!waterChart) return;

    const labels = data.map(item => {
        // Format waktu: Ambil Jam:Menit saja
        const date = new Date(item.record_time);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    });
    
    const values = data.map(item => item.water_percentage);

    waterChart.data.labels = labels;
    waterChart.data.datasets[0].data = values;
    waterChart.update();
}