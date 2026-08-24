(function () {
	if (!window.__dashboardCharts || typeof Chart === 'undefined') {
		return;
	}

	Chart.defaults.font.family = '"Inter", system-ui, -apple-system, sans-serif';
	Chart.defaults.color = '#64748b';
	Chart.defaults.borderColor = '#e5e7eb';

	function formatMoney(v) {
		return Number(v || 0).toLocaleString('tr-TR', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		}) + ' ₺';
	}

	function formatInt(v) {
		return Number(v || 0).toLocaleString('tr-TR');
	}

	// —— Daily sales (store + marketplace) ——
	(function renderDailySales() {
		var chartData = window.__dashboardCharts.dailySales || null;
		var el = document.getElementById('chartDailySales');

		if (!chartData || !el || !chartData.labels || !chartData.labels.length) {
			return;
		}

		var storeValues = (chartData.data || []).map(function (v) {
			return Number(v) || 0;
		});
		var mpValues = (chartData.marketplace_data || []).map(function (v) {
			return Number(v) || 0;
		});

		new Chart(el, {
			type: 'bar',
			data: {
				labels: chartData.labels,
				datasets: [
					{
						label: chartData.store_label || 'Store sales',
						data: storeValues,
						backgroundColor: '#0D9488',
						borderRadius: 6,
						borderSkipped: false,
						barPercentage: 0.85,
						categoryPercentage: 0.7
					},
					{
						label: chartData.marketplace_label || 'Marketplace sales',
						data: mpValues,
						backgroundColor: chartData.marketplace_color || '#F97316',
						borderRadius: 6,
						borderSkipped: false,
						barPercentage: 0.85,
						categoryPercentage: 0.7
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: true,
						position: 'bottom',
						labels: {
							boxWidth: 12,
							boxHeight: 12,
							borderRadius: 3,
							useBorderRadius: true,
							padding: 16,
							font: { size: 12, weight: '500', family: '"Inter", system-ui, sans-serif' },
							color: '#64748b'
						}
					},
					tooltip: {
						backgroundColor: '#0f172a',
						padding: 12,
						cornerRadius: 10,
						callbacks: {
							label: function (ctx) {
								var name = ctx.dataset.label || '';
								return ' ' + name + ': ₺' + Number(ctx.raw || 0).toLocaleString('tr-TR', {
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
						ticks: {
							font: { size: 11, weight: '500', family: '"Inter", system-ui, sans-serif' },
							color: '#64748B',
							maxRotation: 45,
							minRotation: 0,
							autoSkip: true,
							maxTicksLimit: 14
						}
					},
					y: {
						beginAtZero: true,
						border: { display: false },
						grid: { color: '#F1F5F9' },
						ticks: {
							font: { size: 11, weight: '500', family: '"Inter", system-ui, sans-serif' },
							color: '#94A3B8',
							callback: function (value) {
								return '₺' + Number(value).toLocaleString('tr-TR', {
									maximumFractionDigits: 0
								});
							}
						}
					}
				}
			}
		});
	})();

	// —— Marketplace sales waterfall (floating horizontal bars) ——
	(function renderMpSales() {
		var mp = window.__dashboardCharts.mpSales || null;
		var canvas = document.getElementById('chartMpSales');
		if (!mp || !canvas) {
			return;
		}

		var items = mp.items || [];
		if (!items.length) {
			return;
		}

		var labels = [];
		var ranges = [];
		var colors = [];
		var meta = [];
		var running = 0;

		items.forEach(function (item) {
			var start = running;
			var end = running + (parseFloat(item.revenue) || 0);
			labels.push(item.name || item.key || '');
			ranges.push([start, end]);
			colors.push(item.color || '#64748b');
			meta.push({
				orders: item.orders || 0,
				revenue: parseFloat(item.revenue) || 0,
				isTotal: false
			});
			running = end;
		});

		labels.push('Toplam');
		ranges.push([0, parseFloat(mp.total) || running]);
		colors.push('#94a3b8');
		meta.push({
			orders: 0,
			revenue: parseFloat(mp.total) || running,
			isTotal: true
		});

		var barLabelPlugin = {
			id: 'mpBarLabels',
			afterDatasetsDraw: function (chartInstance) {
				var ctx2 = chartInstance.ctx;
				var meta0 = chartInstance.getDatasetMeta(0);
				if (!meta0 || !meta0.data) {
					return;
				}

				ctx2.save();
				ctx2.font = '600 12px Inter, system-ui, sans-serif';
				ctx2.textBaseline = 'middle';

				meta0.data.forEach(function (bar, i) {
					var info = meta[i];
					if (!info) {
						return;
					}
					var text = info.isTotal
						? formatMoney(info.revenue)
						: (formatInt(info.orders) + ' sipariş, ' + formatMoney(info.revenue));

					var x = Math.max(bar.x, bar.base) + 10;
					var y = bar.y;
					var chartRight = chartInstance.chartArea.right - 8;
					var textWidth = ctx2.measureText(text).width;
					var barWidth = Math.abs(bar.x - bar.base);

					if (!info.isTotal && barWidth > textWidth + 24) {
						ctx2.fillStyle = '#ffffff';
						ctx2.fillText(text, Math.min(bar.base, bar.x) + 12, y);
					} else {
						ctx2.fillStyle = '#0f172a';
						if (x + textWidth > chartRight) {
							x = chartRight - textWidth;
						}
						ctx2.fillText(text, x, y);
					}
				});
				ctx2.restore();
			}
		};

		var rowH = 44;
		if (canvas.parentElement) {
			canvas.parentElement.style.height = Math.max(220, (labels.length * rowH) + 56) + 'px';
		}

		new Chart(canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					data: ranges,
					backgroundColor: colors,
					borderSkipped: false,
					borderRadius: 6,
					barThickness: 28,
					maxBarThickness: 32
				}]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#0f172a',
						padding: 12,
						cornerRadius: 10,
						callbacks: {
							label: function (ctx) {
								var info = meta[ctx.dataIndex];
								if (!info) {
									return '';
								}
								if (info.isTotal) {
									return ' Toplam: ' + formatMoney(info.revenue);
								}
								return ' ' + formatInt(info.orders) + ' sipariş · ' + formatMoney(info.revenue);
							}
						}
					}
				},
				scales: {
					x: {
						beginAtZero: true,
						grid: { color: 'rgba(229, 231, 235, 0.95)', drawBorder: false },
						border: { display: false },
						ticks: {
							callback: function (v) {
								return Number(v).toLocaleString('tr-TR', {
									minimumFractionDigits: 2,
									maximumFractionDigits: 2
								}) + ' ₺';
							},
							maxTicksLimit: 5,
							font: { size: 11, weight: '500' },
							color: '#94A3B8'
						}
					},
					y: {
						grid: { display: false },
						border: { display: false },
						ticks: {
							font: { weight: '600', size: 13 },
							color: '#0f172a'
						}
					}
				}
			},
			plugins: [barLabelPlugin]
		});
	})();
})();
