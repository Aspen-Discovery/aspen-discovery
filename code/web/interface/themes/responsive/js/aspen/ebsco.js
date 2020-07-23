AspenDiscovery.EBSCO = (function () {
	return {
		trackEdsUsage: function (id) {
			let ajaxUrl = Globals.path + "/EBSCO/JSON?method=trackEdsUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.EBSCO || {}));