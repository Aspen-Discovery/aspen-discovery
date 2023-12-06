AspenDiscovery.PalaceProject = (function () {
	return {

		getStaffView: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		showPreview: function (palaceProjectId) {
			var url = Globals.path + "/PalaceProject/" + palaceProjectId + "/AJAX?method=getPreview";
			$.getJSON(url, function (data){
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage('Error', data.message);
				}
			});
		},

		getLargeCover: function (id){
			var url = Globals.path + '/PalaceProject/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},
	}
}(AspenDiscovery.PalaceProject || {}));