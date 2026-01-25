/**
 * Inisialisasi gauge saat kartu pertama kali dibuat.
 * @param {HTMLElement} cardElement - Elemen .gauge-card yang baru dibuat.
 */
function initGauge(cardElement) {
    const gaugeElement = cardElement.querySelector('.gauge');
    if (gaugeElement) {
        $(gaugeElement).dxCircularGauge({
            scale: { startValue: 0, endValue: 100, tickInterval: 25 },
            value: 0, // Nilai awal
            title: { text: `0%`, font: { size: 28 } },
            valueIndicator: {
                type: 'rangebar',
                color: '#ccc' // Warna awal
            },
            rangeContainer: {
                ranges: [
                    { startValue: 0, endValue: 100, color: '#E0E0E0' }
                ]
            }
        });
    }
}

/**
 * Memperbarui nilai dan warna gauge yang sudah ada.
 * @param {HTMLElement} cardElement - Elemen .gauge-card yang akan diperbarui.
 * @param {number} waterLevel - Nilai level air baru (0-100).
 * @param {string} fillColor - Warna baru untuk indikator.
 */
function updateGauge(cardElement, waterLevel, fillColor) {
    const gaugeElement = cardElement.querySelector('.gauge');
    if (gaugeElement) {
        const gaugeInstance = $(gaugeElement).dxCircularGauge('instance');
        gaugeInstance.value(waterLevel);
        gaugeInstance.option('title.text', `${Math.round(waterLevel)}%`);
        gaugeInstance.option('valueIndicator.color', fillColor);
    }
}