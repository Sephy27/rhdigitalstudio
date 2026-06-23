// assets/admin/dashboard.js
import Chart from 'chart.js';

console.log('[Dashboard] fichier dashboard.js chargé');

document.addEventListener('DOMContentLoaded', () => {
    console.log('[Dashboard] DOMContentLoaded');

    const labelsEl = document.getElementById('chartLabels');
    const valuesEl = document.getElementById('chartValues');
    const canvas   = document.getElementById('contactsChart');

    if (!labelsEl || !valuesEl) {
        console.warn('[Dashboard] Pas trouvé chartLabels/chartValues dans le DOM');
        return;
    }
    if (!canvas) {
        console.warn('[Dashboard] Pas trouvé le canvas contactsChart');
        return;
    }

    const labels = JSON.parse(labelsEl.textContent);
    const values = JSON.parse(valuesEl.textContent);

    console.log('[Dashboard] labels =', labels);
    console.log('[Dashboard] values =', values);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Messages reçus',
                data: values,
                borderColor: '#0b6a3e',
                backgroundColor: 'rgba(11,106,62,0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: '#0b6a3e',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { font: { size: 11 } } },
                y: { beginAtZero: true }
            }
        }
    });
});


