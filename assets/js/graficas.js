document.addEventListener('DOMContentLoaded', function() {

    // ── GRÁFICA DONA: completadas vs pendientes ───────────────────────────
    const ctxDona = document.getElementById('grafica-progreso');
    if (ctxDona && window.datosProgreso) {
        new Chart(ctxDona, {
            type: 'doughnut',
            data: {
                labels: ['Completadas', 'Pendientes'],
                datasets: [{
                    data: [
                        window.datosProgreso.completadas,
                        window.datosProgreso.pendientes
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(239, 68, 68, 0.6)'
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(216,180,254,0.65)',
                            font: { size: 12 },
                            boxWidth: 12,
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(42,16,64,0.95)',
                        titleColor: '#e9d5ff',
                        bodyColor: 'rgba(216,180,254,0.7)',
                        borderColor: 'rgba(192,132,252,0.2)',
                        borderWidth: 1
                    }
                }
            }
        });
    }

    // ── GRÁFICA DE BARRAS: tareas completadas por día esta semana ─────────
    // window.datosSemana viene de PHP: array [lun, mar, mie, jue, vie, sab, dom]
    const ctxBarras = document.getElementById('grafica-semana');
    if (ctxBarras && window.datosSemana) {

        const dias   = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        // Calculamos qué índice es hoy (0=lun ... 6=dom)
        const hoyJS  = new Date().getDay();           // 0=dom en JS
        const hoyIdx = hoyJS === 0 ? 6 : hoyJS - 1;  // convertimos a 0=lun

        // El día de hoy sale más brillante, el resto más apagado
        const colores = window.datosSemana.map((_, i) =>
            i === hoyIdx
                ? 'rgba(192,132,252,0.85)'
                : 'rgba(192,132,252,0.25)'
        );
        const bordes = window.datosSemana.map((_, i) =>
            i === hoyIdx
                ? 'rgba(192,132,252,1)'
                : 'rgba(192,132,252,0.45)'
        );

        new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: dias,
                datasets: [{
                    label: 'Completadas',
                    data: window.datosSemana,
                    backgroundColor: colores,
                    borderColor: bordes,
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.raw} tarea${ctx.raw !== 1 ? 's' : ''} completada${ctx.raw !== 1 ? 's' : ''}`
                        },
                        backgroundColor: 'rgba(42,16,64,0.95)',
                        titleColor: '#e9d5ff',
                        bodyColor: 'rgba(216,180,254,0.7)',
                        borderColor: 'rgba(192,132,252,0.2)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: 'rgba(216,180,254,0.5)',
                            font: { size: 12 }
                        },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Solo números enteros en el eje Y
                            stepSize: 1,
                            color: 'rgba(216,180,254,0.35)',
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(192,132,252,0.07)'
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }

});