/**
 * Script untuk menangani Grafik Level Air (Chart.js)
 * Fitur: Progressive Line Animation + Robust Data Handling
 */
console.log("Memuat chart-control.js versi Progressive Line v2 (CACHE BUSTING)");

let waterChart = null;
let liveInterval = null;
let currentControllerId = null;

// --- Konfigurasi Animasi Progressive (Diadaptasi dari Chart.js Samples) ---
const totalDuration = 1500; // Durasi total animasi (1.5 detik)
const delayBetweenPoints = totalDuration / 100; // Delay antar titik data
const previousY = (ctx) => ctx.index === 0 ? ctx.chart.scales.y.getPixelForValue(100) : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;

const progressiveAnimation = {
    x: {
        type: 'number',
        easing: 'linear',
        duration: delayBetweenPoints,
        from: NaN, // Titik awal di-skip agar muncul bertahap
        delay(ctx) {
            if (ctx.type !== 'data' || ctx.xStarted) return 0;
            ctx.xStarted = true;
            return ctx.index * delayBetweenPoints;
        }
    },
    y: {
        type: 'number',
        easing: 'linear',
        duration: delayBetweenPoints,
        from: previousY,
        delay(ctx) {
            if (ctx.type !== 'data' || ctx.yStarted) return 0;
            ctx.yStarted = true;
            return ctx.index * delayBetweenPoints;
        }
    }
};
// -----------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('waterLevelChart');
    if (chartCanvas) {
        currentControllerId = chartCanvas.getAttribute('data-controller-id');
        initChart(chartCanvas);
        setChartRange('live'); // Default ke Live Mode
    }
});

function initChart(canvas) {
    const ctx = canvas.getContext('2d');
    
    // Gradien Biru Transparan
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(52, 152, 219, 0.5)');
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
                pointRadius: 0, // Titik disembunyikan agar garis mulus
                pointHoverRadius: 4,
                fill: true,
                tension: 0.2, // Sedikit melengkung agar terlihat alami
                spanGaps: true
            }, {
                label: 'Status Pompa',
                type: 'bar',
                data: [],
                backgroundColor: [],
                barThickness: 5, // Strip tipis di bawah
                order: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        // Custom tooltip untuk status pompa
                        label: (context) => context.dataset.type === 'bar' ? 
                            (context.raw > 0 ? (context.element.options.backgroundColor === '#27ae60' ? 'Pompa: ON' : 'Pompa: OFF') : '') : 
                            context.parsed.y + '%'
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: { maxTicksLimit: 8 }
                },
                y: {
                    beginAtZero: false,
                    grace: '5%', // Ruang kosong 5% di atas/bawah
                    title: { display: true, text: 'Persentase (%)' }
                }
            },
            animation: false // Default mati (untuk Live), akan dihidupkan di History
        }
    });
}

function setChartRange(range) {
    // Update tampilan tombol aktif
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.classList.remove('active', 'btn-primary');
        btn.classList.add('btn-secondary');
    });
    const activeBtn = document.querySelector(`.chart-btn[data-range="${range}"]`);
    if(activeBtn) {
        activeBtn.classList.remove('btn-secondary');
        activeBtn.classList.add('active', 'btn-primary');
    }

    // Reset interval live
    if (liveInterval) {
        clearInterval(liveInterval);
        liveInterval = null;
    }

    if (range === 'live') {
        // --- MODE LIVE ---
        // Matikan animasi progressive agar update tiap detik lancar
        if(waterChart) waterChart.options.animation = false;
        
        fetchChartData(60);
        liveInterval = setInterval(() => fetchChartData(60), 1000);
    } else {
        // --- MODE HISTORY ---
        // Hidupkan animasi progressive untuk efek visual yang keren
        if(waterChart) {
            waterChart.options.animation = progressiveAnimation;
            // Kosongkan data dulu agar animasi mulai dari kiri ke kanan
            waterChart.data.datasets.forEach(dataset => dataset.data = []);
            waterChart.update('none'); 
        }
        fetchChartData(range);
    }
}

function fetchChartData(minutes) {
    if (!currentControllerId) return;

    // Tambahkan timestamp (_t) untuk mencegah cache browser
    fetch(`${BASE_URL}api/device/history?id=${currentControllerId}&range=${minutes}&_t=${new Date().getTime()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Server Error:', data.error);
                return;
            }
            updateChart(data);
        })
        .catch(err => console.error('Gagal memuat data grafik:', err));
}

function updateChart(data) {
    if (!waterChart || !data) return;

    // 1. Validasi & Normalisasi Data (Pencegahan Error "map is not a function")
    let sensorData = [];
    if (Array.isArray(data.sensors)) {
        sensorData = data.sensors;
    } else if (Array.isArray(data)) {
        sensorData = data;
    }
    
    // Jika data kosong atau rusak, hentikan proses agar tidak error
    if (!Array.isArray(sensorData)) return;

    const pumpData = Array.isArray(data.pumps) ? data.pumps : [];

    // 2. Proses Data Sensor
    const labels = sensorData.map(item => {
        const date = new Date(item.record_time);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    });
    
    const values = sensorData.map(item => item.water_percentage);

    // 3. Proses Data Status Pompa (Sinkronisasi Waktu)
    const pumpStatusData = [];
    const pumpColors = [];
    let pumpIndex = 0;
    let currentStatus = 0;

    sensorData.forEach(item => {
        const time = new Date(item.record_time).getTime();
        // Cari status pompa yang relevan dengan waktu sensor saat ini
        while(pumpIndex < pumpData.length && new Date(pumpData[pumpIndex].record_time).getTime() <= time) {
            currentStatus = parseInt(pumpData[pumpIndex].status);
            pumpIndex++;
        }
        pumpStatusData.push(4); // Tinggi strip indikator
        pumpColors.push(currentStatus == 1 ? '#27ae60' : '#e74c3c'); // Hijau/Merah
    });

    // 4. Update Grafik
    waterChart.data.labels = labels;
    waterChart.data.datasets[0].data = values;
    waterChart.data.datasets[1].data = pumpStatusData;
    waterChart.data.datasets[1].backgroundColor = pumpColors;
    
    waterChart.update();
}
