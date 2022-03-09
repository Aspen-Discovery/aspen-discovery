AspenDiscovery.CourseReserves = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		addToHomePage: function(reserveId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/CourseReserves/AJAX?method=getAddBrowseCategoryFromCourseReservesForm&reserveId=' + reserveId, true);
			return false;
		},

		editAction: function (){
			$('#listDescription,#listTitle,#FavEdit,.listViewButton').hide();
			$('#listEditControls,#FavSave,.listEditButton').show();
			return false;
		},

		cancelEditAction: function (){
			$('#listDescription,#listTitle,#FavEdit,.listViewButton').show();
			$('#listEditControls,#FavSave,.listEditButton').hide();
			return false;
		},

		emailAction: function (reserveId) {
			var urlToDisplay = Globals.path + '/CourseReserves/AJAX';
			AspenDiscovery.loadingMessage();
			$.getJSON(urlToDisplay, {
					method  : 'getEmailCourseReserveForm'
					,reserveId : reserveId
				},
				function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		sendEmail: function () {
			var url = Globals.path + "/CourseReserves/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					reserveId   : $('#emailCourseReserveForm input[name="reserveId"]').val()
					,to      : $('#emailCourseReserveForm input[name="to"]').val()
					,from    : $('#emailCourseReserveForm input[name="from"]').val()
					,message : $('#emailCourseReserveForm textarea[name="message"]').val()
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

		citeAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/CourseReserves/AJAX?method=getCitationFormatsForm&reserveId=' + id, false);
		},

		processCiteForm: function(){
			$("#citeListForm").submit();
		},

		printAction: function (){
			window.print();
			return false;
		},

		getUploadListCoverForm: function (id){
			var url = Globals.path + '/CourseReserves/AJAX?id=' + id + '&method=getUploadListCoverForm';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadListCover: function (id){
			var url = Globals.path + '/CourseReserves/AJAX?id=' + id + '&method=uploadListCover';
			var uploadCoverData = new FormData($("#uploadListCoverForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		getUploadListCoverFormByURL: function (id){
			var url = Globals.path + '/CourseReserves/AJAX?id=' + id + '&method=getUploadListCoverFormByURL';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadListCoverByURL: function (id){
			var url = Globals.path + '/CourseReserves/AJAX?id=' + id + '&method=uploadListCoverByURL';
			var uploadCoverData = new FormData($("#uploadListCoverFormByURL")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		}
	};
}(AspenDiscovery.CourseReserves || {}));