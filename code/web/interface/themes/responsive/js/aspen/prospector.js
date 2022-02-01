AspenDiscovery.Prospector = (function(){
	return {
		getProspectorResults: function(prospectorNumTitlesToLoad, prospectorSavedSearchId){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getProspectorResults&prospectorNumTitlesToLoad=" + encodeURIComponent(prospectorNumTitlesToLoad) + "&prospectorSavedSearchId=" + encodeURIComponent(prospectorSavedSearchId);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				success: function(data) {
					if (data.numTitles == 0){
						$("#prospectorSearchResultsPlaceholder").hide();
					}else{
						$("#prospectorSearchResultsPlaceholder").html(data.formattedData);
					}
				}
			});
		},

		loadRelatedProspectorTitles: function (id) {
			var url;
			url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=getProspectorInfo";
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl, function(data) {
				if (data.numTitles == 0){
					$("#prospectorPanel").hide();
				}else{
					$("#inProspectorPlaceholder").html(data.formattedData);
				}
			});
		},

		removeBlankThumbnail: function(imgElem, elemToHide, isForceRemove) {
			var $img = $(imgElem);
			//when the content providers cannot find a bookjacket, they return a 1x1 pixel
			//remove the wrapping div, for consistent spacing with other results
			if ($img.height() == 1 && $img.width() == 1 || isForceRemove) {
				$(elemToHide).remove();
			}
		}
	}
}(AspenDiscovery.Prospector || {}));