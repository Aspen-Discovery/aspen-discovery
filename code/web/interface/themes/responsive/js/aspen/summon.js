AspenDiscovery.Summon = (function(){
	return {
		getSummonResults: function(searchTerm){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getSummonResults&searchTerm=" + encodeURIComponent(searchTerm);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				dataType:"json",
				success: function(data) {
					var searchResults = data.formattedResults;
					if (searchResults) {
						if (searchResults.length > 0){
							$("#summonSearchResultsPlaceholder").html(searchResults);
						}
					}
				}
			});
		}
	}
}(AspenDiscovery.Summon || {}));