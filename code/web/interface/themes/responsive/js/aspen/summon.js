AspenDiscovery.Summon = (function(){
	return {
		trackSummonUsage: function(id) {
			var ajaxUrl = Globals.path + "/Summon/JSON?method=trackSummonUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.Summon || {}));