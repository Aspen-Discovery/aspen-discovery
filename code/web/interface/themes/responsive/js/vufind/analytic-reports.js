VuFind.AnalyticReports = (function(){
	return {
		filterParams: "",
		showFilterValues: function(control){
			//Show options for this
			var activeFilter = $(control);
			var selectedOption = activeFilter.find(":selected").val();
			var curIndex = activeFilter.data("filter-index");
			activeFilter.parent().find(".filterValues").remove();
			var filterValueSelection = "<select class='filterValues' name='filterValue[" + curIndex + "]'>";
			for (var index in filterValues[selectedOption]){
				filterValueSelection += "<option value='" + index + "'>" + filterValues[selectedOption][index] + "</option>";
			}
			filterValueSelection += "</select>";
			activeFilter.after(filterValueSelection);
		},

		getFilterParams: function() {
			return this.filterParams;
		},

		getPieChartData: function(reportName, chartVar){
			var filterParms = this.getFilterParams();
			$.getJSON(Globals.path + "/Report/AJAX?method=" + reportName + "&forGraph=true" + filterParms,
				function(data) {
					$.each(data, function(i, val){
						chartVar.series[0].addPoint(val, true, false);
					});
				}
			);
		},

		setupPieChart: function(divToRenderTo, reportDataName, title, seriesLabel){
			var chartVariable = new Highcharts.Chart({
				chart : {
					renderTo : divToRenderTo,
					type: 'pie'
				},
				legend : {
					enabled: false
				},
				title: {
					text: title
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: false
						},
						showInLegend: false
					}
				},

				series: [{
					name: seriesLabel,
					data: []
				}]
			});
			this.getPieChartData(reportDataName, chartVariable);
			return chartVariable;
		},

		getBarChartData: function(reportDataName, chartVariable){
			var filterParms = this.getFilterParams();
			$.getJSON(Globals.path + "/Report/AJAX?method=" + reportDataName + "&forGraph=true" + filterParms,
				function(data) {
					var categories = [];
					$.each(data, function(i, val){
						chartVariable.series[0].addPoint(val, true, false);
						categories.push( val[0]);
					});
					chartVariable.xAxis[0].setCategories(categories);
				}
			);
		},

		setupBarChart: function(divToRenderTo, reportDataName, title, xAxisLabel, yAxisLabel){
			var chartVariable = new Highcharts.Chart({
				chart : {
					renderTo : divToRenderTo,
					type: 'bar'
				},
				legend : {
					enabled: false
				},
				title: {
					text: title
				},
				xAxis: {
					title: {
						text: xAxisLabel
					}
				},

				yAxis: {
					title: {
						text: yAxisLabel
					},
					allowDecimals: false,
					min: 0
				},
				series: [{
					name: yAxisLabel,
					data: []
				}]
			});
			getBarChartData(reportDataName, chartVariable);
		},

		setupInteractiveChart: function(divToRenderTo, title, xAxisLabel, yAxisLabel){
			return new Highcharts.Chart({
				chart : {
					renderTo : divToRenderTo,
					type: 'column'
				},
				legend : {
					enabled: false
				},
				title: {
					text: title
				},
				xAxis: {
					title: {
						text: xAxisLabel
					}
				},

				yAxis: {
					title: {
						text: yAxisLabel
					},
					allowDecimals: false,
					min: 0
				},
				series: [{name:title, data:[0,0,0,0,0,0,0,0,0,0,
				                                  0,0,0,0,0,0,0,0,0,0,
				                                  0,0,0,0,0,0,0,0,0,0,
				                                  0,0,0,0,0,0,0,0,0,0,
				                                  0,0,0,0,0,0,0,0,0,0,
				                                  0,0,0,0,0,0,0,0,0,0
				                                  ]}
				         ]

			});
		},

		getRecentActivity: function(){
			var filterParams = getFilterParams();
			$.getJSON(Globals.path + "/Report/AJAX?method=getRecentActivity&interval=5" + filterParams,
				function(data) {
					activePageViewChart.series[0].addPoint(parseInt(data.pageViews), true, true);
					recentUsersChart.series[0].addPoint(parseInt(data.activeUsers), true, true);
					recentSearchesChart.series[0].addPoint(parseInt(data.searches), true, true);
					recentEventsChart.series[0].addPoint(parseInt(data.events), true, true);
					setTimeout("getRecentActivity()", 5000);
				}
			);
		},


		holdsByResultChart: null,
		setupHoldsByResultChart: function() {
			holdsByResultChart = new Highcharts.Chart({
				chart : {
					renderTo : 'holdsByResultChart',
					type: 'pie',
					events: {
						load: getHoldsByResultData
					}
				},
				legend : {
					enabled: false
				},
				title: {
					text: 'Holds By Result'
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: false
						},
						showInLegend: false
					}
				},
				xAxis: {
					title: {
						text: 'Holds'
					}
				},

				yAxis: {
					title: {
						text: 'Result %'
					},
					allowDecimals: false,
					min: 0
				},
				series: [{
					name: 'Holds By Result',
					data: []
				}]
			});
		},

		getHoldsByResultData: function(){
			var filterParms = getFilterParams();
			$.getJSON(Globals.path + "/Report/AJAX?method=getHoldsByResultData&forGraph=true" + filterParms,
				function(data) {
					var categories = [];
					$.each(data, function(i, val){
						holdsByResultChart.series[0].addPoint(val, true, false);
						categories.push( val[0]);
					});
					holdsByResultChart.xAxis[0].setCategories(categories);
				}
			);
		}
	};
}(VuFind.AnalyticReports || {}));
