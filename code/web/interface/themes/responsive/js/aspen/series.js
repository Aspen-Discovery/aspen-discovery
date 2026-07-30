AspenDiscovery.Series = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		editAction: function (seriesId){
			window.location.href = "/Series/AdministerSeries?objectAction=edit&id=" + seriesId;
			return false;
		},
		getGroupSeriesSearchForm: function (trigger, id, searchId, page) {
			AspenDiscovery.loadingMessage();
			var url = Globals.path + "/Series/" + id + "/AJAX?method=getGroupSeriesSearchForm&searchId=" + searchId + "&page=" + page;
			$.getJSON(url, function(data){
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage("An error occurred", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		processGroupSeriesForm: function() {
			var id = $('#id').val();
			var groupSeriesId = $('#seriesToGroupWithId').val().trim();
			var url = Globals.path + "/Series/" + id + "/AJAX?method=processGroupSeriesForm&groupSeriesId=" + groupSeriesId;
			//AspenDiscovery.closeLightbox();
			$.getJSON(url, function(data){
				if (data.success){
					AspenDiscovery.showMessage("Success", data.message, true, false);
				}else{
					AspenDiscovery.showMessage("An error occurred", data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
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
		},

		ungroupSeries(id, groupedWithSeriesId) {
			var url = Globals.path + "/Series/" + id + "/AJAX?method=ungroupSeries&groupedWithSeriesId=" + groupedWithSeriesId;
			//AspenDiscovery.closeLightbox();
			$.getJSON(url, function(data){
				if (data.success){
					AspenDiscovery.showMessage("Success", data.message, false, true);
				}else{
					AspenDiscovery.showMessage("An error occurred", data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		}
	};
}(AspenDiscovery.Series || {}));