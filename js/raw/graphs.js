// When the ticker comes back every 5 seconds wrap the adding of asensor
// reading with the timestamp from now.
var addSensorReading = function(arr, sensor, value) {
	return addSensorReadingWithDate(arr, sensor, value, new Date());
};

// Add a sensor reading in the scatter graph
var addSensorReadingWithDate = function(arr, sensor, value, dte) {
	found = false;
	if (value == undefined) {
		return false;
	}

	angular.forEach(arr, function(item, index) {
		if (item.name == sensor.name) {
			found = true;
			item.data.push({
				t : dte,
				y : value
			});

			item.data.sort(function(a, b) {
				if (a.t < b.t) {
					return -1;
				}
				if (a.t > b.t) {
					return 1;
				}
				return 0;
			});
		}
	});

	if (!found) {
		rgb = hexToRgb(sensor.colour);
		arr.push({
			name : sensor.name,
			label : sensor.label,
			backgroundColor : "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 0.2)",
			borderColor : "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 1)",
			borderWidth : 1,
			fill : false,
			data : [ {
				t : new Date(),
				y : value
			} ]
		});
	}

	return true;
};

// Creates the graphs used for the model that cover a year with each item being
// a day
var createDayGraph = function(id, arr, title, ind, xlabels, yhours) {
	var ctx = $(id);
	var myChart = new Chart(ctx, {
		type : 'line',
		fill : false,
		data : {
			datasets : arr,
			labels : xlabels
		},
		options : {
			responsive : true,
			aspectRatio : 2,
			legend : {
				display : true
			},
			title : {
				display : true,
				fontSize : 18,
				text : title
			},
			scales : {
				yAxes : [ {
					ticks : {
						// max : ((yhours) ? (24) : (undefined)),
						stepSize : ((yhours) ? (1) : (undefined)),
						callback : function(value, index, values) {
							return yhours ? (hours2Hm(value)) : (value + ind);
						}
					}
				} ],
				xAxes : [ {
					type : 'time',
					ticks : {
						callback : function(value, index, values) {
							d = new Date(value);
							if (d.getDate() == 1 || (d.getDate() == 31 && d.getMonth() == 11)) {
								return value;
							}
							return undefined; // don't show this label

						},
						source : 'labels'
					},
					time : {
						unit : 'day',
						displayFormats : {
							day : 'MMM DD'
						}
					},
				} ]
			},
			tooltips : {
				callbacks : {
					title : function(tooltipItem, data) {
						return moment(tooltipItem[0].label).format("MMMM D");
					},
					label : function(tooltipItem, data) {
						var label = data.datasets[tooltipItem.datasetIndex].label || '';

						if (label) {
							label += ': ';
						}
						if (yhours) {
							h = tooltipItem.value;
							m = (tooltipItem.value - Math.floor(tooltipItem.value)) * 60;
							hh = lpad("" + Math.floor(h), 2, "0");
							mm = lpad("" + Math.floor(m), 2, "0");
							label += hh + ":" + mm;
						} else {
							label += (Math.round(tooltipItem.value * 100) / 100) + ind;
						}
						return label;
					}
				}
			}
		}
	});
	return myChart;
};

// Used for graphs that are driven from the server tick
var createMinuteGraph = function(id, arr, title, ind) {
	var ctx = $(id);
	var myChart = new Chart(ctx, {
		type : 'line',
		fill : false,
		data : {
			datasets : arr,
		},
		options : {
			responsive : true,
			aspectRatio : 2,
			legend : {
				display : true
			},
			title : {
				display : true,
				fontSize : 18,
				text : title
			},
			scales : {
				yAxes : [ {
					ticks : {
						callback : function(value, index, values) {
							return value + ind;
						}
					}
				} ],
				xAxes : [ {
					type : 'time',
					distribution : 'linear',
					time : {
						unit : 'minute',
						displayFormats : {
							minute : "HH:mm"
						}
					},
				} ]
			},
			tooltips : {
				callbacks : {
					title : function(tooltipItem, data) {
						label = data.datasets[tooltipItem[0].datasetIndex].data[tooltipItem[0].index].t;
						return moment(label).format("MMMM Do, HH:mm:ss");
					},
					label : function(tooltipItem, data) {
						var label = data.datasets[tooltipItem.datasetIndex].label || '';

						if (label) {
							label += ': ';
						}
						label += (Math.round(tooltipItem.value * 100) / 100) + ind;
						return label;
					}
				}

			}
		}
	});
	return myChart;
};
