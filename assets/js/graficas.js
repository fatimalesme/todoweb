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
				'rgba(72, 187, 120, 0.7)', // verde
				'rgba(252, 129, 129, 0.7)'  // rojo
			],
			borderColor: [
				'rgba(72, 187, 120, 1)',
				'rgba(252, 129, 129, 1)'
			],
			borderWidth: 2
		}]
	};
	new Chart(ctx, {
		type: 'doughnut',
		data: data,
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom',
				},
				title: {
					display: true,
					text: 'Progreso de tareas'
				}
			}
		}
	});
});
