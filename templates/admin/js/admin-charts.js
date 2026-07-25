(function () {
	if (!window.__dashboardCharts || typeof Chart === 'undefined') {
		return;
	}

	var fullDaily = window.__dashboardCharts.daily || [];
	var dailyEl = document.getElementById('chartDaily');
	var chartInstance = null;
	var i18n = window.__adminI18n || {};

	function sliceDaily(range) {
		var n = parseInt(range, 10) || 7;
		if (n >= fullDaily.length) {
			return fullDaily.slice();
		}
		return fullDaily.slice(Math.max(0, fullDaily.length - n));
	}

	function buildChart(daily) {
		if (!dailyEl) {
			return;
		}
		if (chartInstance) {
			chartInstance.destroy();
		}

		chartInstance = new Chart(dailyEl, {
			type: 'line',
			data: {
				labels: daily.map(function (d) { return d.label_short || d.label; }),
				datasets: [
					{
						label: i18n.thisPeriod || 'Period',
						data: daily.map(function (d) { return d.revenue; }),
						borderColor: '#2563EB',
						backgroundColor: 'rgba(37, 99, 235, 0.08)',
						fill: true,
						tension: 0.35,
						pointRadius: 3,
						pointHoverRadius: 5,
						pointBackgroundColor: '#2563EB',
						borderWidth: 2.5
					},
					{
						label: i18n.previous || 'Previous',
						data: daily.map(function (d) { return d.revenue_prev; }),
						borderColor: '#CBD5E1',
						backgroundColor: 'transparent',
						fill: false,
						tension: 0.35,
						pointRadius: 0,
						borderWidth: 2,
						borderDash: [5, 5]
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							boxWidth: 10,
							padding: 18,
							font: { size: 12, family: 'Inter, sans-serif', weight: '500' },
							color: '#64748B'
						}
					},
					tooltip: {
						backgroundColor: '#111827',
						titleFont: { family: 'Inter, sans-serif', weight: '600' },
						bodyFont: { family: 'Inter, sans-serif' },
						padding: 12,
						cornerRadius: 10,
						callbacks: {
							label: function (ctx) {
								var val = ctx.parsed.y || 0;
								return ctx.dataset.label + ': ₺' + val.toLocaleString('tr-TR', {
									minimumFractionDigits: 2,
									maximumFractionDigits: 2
								});
							}
						}
					}
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: { font: { size: 11, family: 'Inter, sans-serif' }, color: '#94A3B8', maxRotation: 0 }
					},
					y: {
						beginAtZero: true,
						border: { display: false },
						grid: { color: '#F1F5F9' },
						ticks: {
							font: { size: 11, family: 'Inter, sans-serif' },
							color: '#94A3B8',
							callback: function (value) {
								return '₺' + value.toLocaleString('tr-TR');
							}
						}
					}
				}
			}
		});
	}

	buildChart(sliceDaily(7));

	var filters = document.getElementById('dashChartFilters');
	if (filters) {
		filters.addEventListener('click', function (e) {
			var btn = e.target.closest('.dash-chart-filter');
			if (!btn) {
				return;
			}
			filters.querySelectorAll('.dash-chart-filter').forEach(function (el) {
				el.classList.remove('is-active');
			});
			btn.classList.add('is-active');
			buildChart(sliceDaily(btn.getAttribute('data-range')));
		});
	}
})();
