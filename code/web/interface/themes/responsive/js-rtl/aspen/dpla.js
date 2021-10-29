AspenDiscovery.DPLA = (function(){
	return {
		getDPLAResults: function(searchTerm){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getDplaResults&searchTerm=" + encodeURIComponent(searchTerm);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				dataType:"json",
				success: function(data) {
					var searchResults = data.formattedResults;
					if (searchResults) {
						if (searchResults.length > 0){
							$("#dplaSearchResultsPlaceholder").html(searchResults);
						}
					}
				}
			});
		}
	}
}(AspenDiscovery.DPLA || {}));