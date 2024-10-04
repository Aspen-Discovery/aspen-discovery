AspenDiscovery.InterLibraryLoan = (function(){
	return {
		getInnReachResults: function(innReachNumTitlesToLoad, innReachSavedSearchId){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getInnReachResults&innReachNumTitlesToLoad=" + encodeURIComponent(innReachNumTitlesToLoad) + "&innReachSavedSearchId=" + encodeURIComponent(innReachSavedSearchId);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				success: function(data) {
					if (data.numTitles === 0){
						$("#innReachSearchResultsPlaceholder").hide();
					}else{
						$("#innReachSearchResultsPlaceholder").html(data.formattedData);
					}
				}
			});
		},

		loadRelatedInnReachTitles: function (id) {
			var url;
			url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=getInnReachInfo";
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl, function(data) {
				if (data.numTitles === 0){
					$("#innReachPanel").hide();
				}else{
					$("#inInnReachPlaceholder").html(data.formattedData);
				}
			});
		},

		getShareItResults: function(shareItNumTitlesToLoad, shareItSavedSearchId){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getShareItResults&shareItNumTitlesToLoad=" + encodeURIComponent(shareItNumTitlesToLoad) + "&shareItSavedSearchId=" + encodeURIComponent(shareItSavedSearchId);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				success: function(data) {
					if (data.numTitles === 0){
						$("#shareItSearchResultsPlaceholder").hide();
					}else{
						$("#shareItSearchResultsPlaceholder").html(data.formattedData);
					}
				}
			});
		},

		loadRelatedShareItTitles: function (id) {
			var url;
			url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=getShareItInfo";
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl, function(data) {
				if (data.numTitles === 0){
					$("#shareItPanel").hide();
				}else{
					$("#inShareItPlaceholder").html(data.formattedData);
				}
			});
		},

		removeBlankThumbnail: function(imgElem, elemToHide, isForceRemove) {
			var $img = $(imgElem);
			//when the content providers cannot find a bookjacket, they return a 1x1 pixel
			//remove the wrapping div, for consistent spacing with other results
			if ($img.height() === 1 && $img.width() === 1 || isForceRemove) {
				$(elemToHide).remove();
			}
		}
	}
}(AspenDiscovery.InterLibraryLoan || {}));