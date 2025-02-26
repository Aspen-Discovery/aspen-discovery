AspenDiscovery.Series = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		editAction: function (seriesId){
			window.location.href = "/Series/AdministerSeries?objectAction=edit&id=" + seriesId;
			return false;
		},
		emailAction: function (seriesId) {
			var urlToDisplay = Globals.path + '/Series/AJAX';
			AspenDiscovery.loadingMessage();
			$.getJSON(urlToDisplay, {
					method  : 'getEmailSeriesForm',
					seriesId : seriesId
				},
				function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},
		sendEmail: function () {
			var url = Globals.path + "/Series/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					seriesId   : $('#emailSeriesForm input[name="seriesId"]').val()
					,to      : $('#emailSeriesForm input[name="to"]').val()
					,from    : $('#emailSeriesForm input[name="from"]').val()
					,message : $('#emailSeriesForm textarea[name="message"]').val()
					,method  : 'sendEmail'
				},
				function(data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			);
		},
		printAction: function (){
			window.print();
			return false;
		}

	};
}(AspenDiscovery.Series || {}));