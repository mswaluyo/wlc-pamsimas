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

// --- Plugin Garis Horizontal Statis ---
const horizontalLinesPlugin = {
    id: 'horizontalLines',
    afterDatasetsDraw(chart, args, options) {
        const { ctx, chartArea: { top, right, bottom, left, width, height }, scales: { x, y } } = chart;
        
        if (!options || !Array.isArray(options.lines)) return;

        ctx.save();
        options.lines.forEach(line => {
            const yValue = line.value;
            const yPos = y.getPixelForValue(yValue);

            // Hanya gambar jika garis berada dalam area chart (berguna saat di-zoom)
            if (yPos >= top && yPos <= bottom) {
                ctx.beginPath();
                ctx.strokeStyle = line.color || 'rgba(0,0,0,0.5)';
                ctx.lineWidth = line.width || 1;
                if (line.dash) ctx.setLineDash(line.dash);
                else ctx.setLineDash([]);
                
                ctx.moveTo(left, yPos);
                ctx.lineTo(right, yPos);
                ctx.stroke();

                if (line.text) {
                    ctx.fillStyle = line.color || 'rgba(0,0,0,0.5)';
                    ctx.font = 'bold 11px sans-serif';
                    ctx.fillText(line.text, left + 8, yPos - 6);
                }
            }
        });
        ctx.restore();
    }
};

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
    const triggerLevel = parseFloat(canvas.getAttribute('data-trigger')) || 70; // Ambil nilai trigger
    
    // Gradien Biru Transparan
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(52, 152, 219, 0.5)');
    gradient.addColorStop(1, 'rgba(52, 152, 219, 0.0)');

    waterChart = new Chart(ctx, {
        type: 'line',
        plugins: [horizontalLinesPlugin], // Register plugin lokal
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
                        label: (context) => {
                            if (context.dataset.type === 'bar') {
                                const status = context.raw > 0 ? (context.element.options.backgroundColor === '#27ae60' ? 'ON' : 'OFF') : 'OFF';
                                let label = `Pompa: ${status}`;
                                const duration = context.dataset.pumpTooltips ? context.dataset.pumpTooltips[context.dataIndex] : null;
                                if (duration) label += ` (${duration})`;
                                return label;
                            }
                            return context.parsed.y + '%';
                        }
                    },
                },
                // Konfigurasi garis batas
                horizontalLines: {
                    lines: [
                        { value: 100, color: '#27ae60', width: 2, dash: [5, 5], text: 'BATAS ATAS (100%)' },
                        { value: triggerLevel, color: '#e67e22', width: 2, dash: [5, 5], text: `BATAS BAWAH (${triggerLevel}%)` }
                    ]
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
        
        fetchChartData(5); // Ubah rentang live menjadi 5 menit agar lebih fokus
        liveInterval = setInterval(() => fetchChartData(5), 1000);
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
    const pumpTooltips = []; // Array untuk menyimpan data durasi
    let pumpIndex = 0;
    let currentStatus = 'OFF'; // Default status awal
    let lastEventTime = null;

    sensorData.forEach(item => {
        const time = new Date(item.record_time).getTime();
        // Cari status pompa yang relevan dengan waktu sensor saat ini
        while(pumpIndex < pumpData.length && new Date(pumpData[pumpIndex].record_time).getTime() <= time) {
            currentStatus = pumpData[pumpIndex].status;
            pumpIndex++;
            lastEventTime = new Date(pumpData[pumpIndex - 1].record_time).getTime();
        }
        pumpStatusData.push(4); // Tinggi strip indikator
        
        // Cek status (handle string 'ON'/'OFF' atau angka 1/0)
        const isOn = (String(currentStatus).toUpperCase() === 'ON' || currentStatus == 1);
        pumpColors.push(isOn ? '#27ae60' : '#e74c3c'); // Hijau jika ON, Merah jika OFF
    });
    
    // Reset index untuk loop kedua (penghitungan durasi)
    pumpIndex = 0;
    lastEventTime = null;
    
    sensorData.forEach(item => {
        const time = new Date(item.record_time).getTime();
        while(pumpIndex < pumpData.length && new Date(pumpData[pumpIndex].record_time).getTime() <= time) {
            lastEventTime = new Date(pumpData[pumpIndex].record_time).getTime();
            pumpIndex++;
        }
        
        let durationStr = '';
        // Jika ada event berikutnya, durasi status saat ini tersimpan di event tersebut
        if (pumpIndex < pumpData.length) {
            const nextEvent = pumpData[pumpIndex];
            if (nextEvent.duration_seconds) {
                durationStr = formatDuration(nextEvent.duration_seconds);
            }
        } else if (lastEventTime) {
            // Jika ini status terakhir (sedang berjalan), hitung selisih waktu
            const elapsed = Math.floor((time - lastEventTime) / 1000);
            if (elapsed >= 0) durationStr = formatDuration(elapsed);
        }
        pumpTooltips.push(durationStr);
    });

    // 4. Update Grafik
    waterChart.data.labels = labels;
    waterChart.data.datasets[0].data = values;
    waterChart.data.datasets[1].data = pumpStatusData;
    waterChart.data.datasets[1].backgroundColor = pumpColors;
    waterChart.data.datasets[1].pumpTooltips = pumpTooltips; // Simpan ke dataset
    
    waterChart.update();
}

function formatDuration(seconds) {
    if (!seconds) return '';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    
    let parts = [];
    if (h > 0) parts.push(`${h}j`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}d`);
    
    return parts.join(' ');
}

/**
 * Mengubah mode skala Y antara Auto (Zoom) dan Fixed (0-100%)
 */
function toggleAutoScale(isAuto) {
    if (!waterChart) return;
    
    if (isAuto) {
        delete waterChart.options.scales.y.min;
        delete waterChart.options.scales.y.max;
        waterChart.options.scales.y.grace = '5%';
    } else {
        waterChart.options.scales.y.min = 0;
        waterChart.options.scales.y.max = 100;
        delete waterChart.options.scales.y.grace;
    }
    waterChart.update();
}
