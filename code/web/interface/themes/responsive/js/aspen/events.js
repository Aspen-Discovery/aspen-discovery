AspenDiscovery.Events = (function(){
	return {
		trackUsage: function (id) {
			let ajaxUrl = Globals.path + "/Events/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.Events || {}));