AspenDiscovery.DPLA = (function(){
	return {
		getDPLAResults: function(searchTerm){
			let url = Globals.path + "/Search/AJAX";
			let params = "method=getDplaResults&searchTerm=" + encodeURIComponent(searchTerm);
			let fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				dataType:"json",
				success: function(data) {
					let searchResults = data.formattedResults;
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