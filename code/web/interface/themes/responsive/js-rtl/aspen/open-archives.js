AspenDiscovery.OpenArchives = (function () {
	return {
		trackUsage: function (id) {
			var ajaxUrl = Globals.path + "/OpenArchives/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.OpenArchives || {}));