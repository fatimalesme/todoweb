document.addEventListener('DOMContentLoaded', function() {
    if (!window.datosProgreso) return;
    const ctx = document.getElementById('grafica-progreso');
    if (!ctx) return;

    const data = {
        labels: ['Completadas', 'Pendientes'],
        datasets: [{
            label: 'Tareas',
            data: [window.datosProgreso.completadas, window.datosProgreso.pendientes],
            backgroundColor: [
                'rgba(34, 197, 94, 0.6)',
                'rgba(239, 68, 68, 0.6)'
            ],
            borderColor: [
                'rgba(34, 197, 94, 1)',
                'rgba(239, 68, 68, 1)'
            ],
            borderWidth: 2
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: 'rgba(203, 213, 225, 0.8)',
                        font: { size: 13 }
                    }
                },
                title: {
                    display: true,
                    text: 'Progreso de tareas',
                    color: 'rgba(241, 245, 249, 0.9)',
                    font: { size: 14 }
                }
            }
        }
        // Tamaño controlado por CSS (.chart-wrapper)
    });
});
