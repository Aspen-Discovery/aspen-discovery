AspenDiscovery.Websites = (function () {
	return {
		trackUsage: function (id) {
			var ajaxUrl = Globals.path + "/Websites/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.Websites || {}));