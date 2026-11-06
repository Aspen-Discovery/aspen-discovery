AspenDiscovery.Omeka = (function () {
	return {

		getStaffView: function (id) {
			var url = Globals.path + "/Omeka/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
			return false;
		},

		getLargeCover: function (id) {
			var url = Globals.path + "/Omeka/" + id + "/AJAX?method=getLargeCover";
			$.getJSON(url, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		}
	}
}(AspenDiscovery.Omeka || {}));
