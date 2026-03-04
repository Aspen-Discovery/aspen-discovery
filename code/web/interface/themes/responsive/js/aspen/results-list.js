AspenDiscovery.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		lessFacets: function(name){
			document.getElementById("more" + name).style.display="block";
			document.getElementById("narrowGroupHidden_" + name).style.display="none";
		},

		moreFacets: function(name){
			document.getElementById("more" + name).style.display="none";
			document.getElementById("narrowGroupHidden_" + name).style.display="block";
		},

		moreFacetPopup: function(title, name){
			AspenDiscovery.showMessage(title, $("#moreFacetPopup_" + name).html());
		},

		multiSelectMoreFacetPopup: function(title, name, buttonName){
			var button = "<a class='btn btn-primary' onclick='$(\"#facetPopup_" + name + "\").trigger(\"submit\");'>"+buttonName+"</a>";
			AspenDiscovery.showMessageWithButtons(title, $("#moreFacetPopup_" + name).html(), button);
		},

		processMultiSelectMoreFacetForm: function(formId, fieldName){
			var newUrl = location.origin + location.pathname + "?";
			var unlockRequests = [];
			var unlockValues = [];
			var unlockUrl = Globals.path + "/Search/AJAX";

			function extractFacetValue(filterValue, fieldName) {
				if (!filterValue) {
					return null;
				}
				var decoded = decodeURIComponent(filterValue.substring(fieldName.length + 1));
				if (decoded === '("")') {
					return "";
				}
				return (decoded.length >= 2 && decoded[0] === '"' && decoded[decoded.length - 1] === '"')
					? decoded.substring(1, decoded.length - 1) : decoded;
			}

			$(".modal-body " + formId + " input[type=checkbox][data-locked='1']").each(function () {
				if (!$(this).is(":checked")) {
					var value = extractFacetValue($(this).attr('value'), fieldName);
					if (value !== null) {
						unlockValues.push(value);
					}
				}
			});
			//Remove existing parameters for the facet from the url
			var existingQuery = location.search.substr(1);
			var firstTerm = true;
			if(existingQuery !== undefined){
				existingQuery = existingQuery.split('&');
				for(var i = 0; i < existingQuery.length; i++){
					var queryTerm = existingQuery[i].split('=');
					if (queryTerm[0] === 'filter[]') {
						//Check to see if we should include or not
						if (!queryTerm[1].startsWith(fieldName)) {
							if (!firstTerm) {
								newUrl += "&";
							} else {
								firstTerm = false;
							}
							newUrl += existingQuery[i];
						}
					}else if(queryTerm[0] === 'page') {
						//Reset the page to the first page by omitting this term
					}else{
						if (!firstTerm){
							newUrl += "&";
						}else{
							firstTerm = false;
						}
						newUrl += existingQuery[i];
					}
				}
			}
			$(".modal-body " + formId + " input[type=checkbox]:checked").each(function() {
				if (!firstTerm) {
					newUrl += "&";
				} else {
					firstTerm = false;
				}
				var name = $(this).attr('name');
				var value = $(this).attr('value');
				newUrl += (name + '=' + value);
			});

			if (unlockValues.length > 0) {
				for (var i = 0; i < unlockValues.length; i++) {
					var params = "method=unlockFacet&facet=" + encodeURIComponent(fieldName) + "&value=" + encodeURIComponent(unlockValues[i]);
					unlockRequests.push($.getJSON(unlockUrl + "?" + params));
				}
				$.when.apply($, unlockRequests).always(function () {
					document.location.href = newUrl;
				});
			} else {
				document.location.href = newUrl;
			}
			return false;
		},

		toggleRelatedManifestations: function(manifestationId){
			var relatedRecordPopup = $('#relatedRecordPopup_' + manifestationId);
			if (relatedRecordPopup.is(":visible")){
				relatedRecordPopup.slideUp();
			}else{
				relatedRecordPopup.slideDown();
			}
			//relatedRecordPopup.toggleClass('hidden');
			return false;

		},

		showRelatedManifestations: function(workId, format, variationId) {
			var url = Globals.path + "/GroupedWork/AJAX?method=getRelatedManifestations&id=" + workId + "&format=" + format + "&variationId=" + variationId;
			var relatedRecordPopup = $('#relatedRecordPopup_' + workId + '_' + format + '_' + variationId);
			var relatedRecordBtn = $('#manifestation-toggle-text-' + workId + '_' + format + '_' + variationId + ' .fa-spinner');

			if(relatedRecordPopup.is(":visible")) {
				relatedRecordPopup.slideUp();
			} else {
				$(relatedRecordBtn).removeClass('hidden');
				$.getJSON(url, function (data) {
					if (data.success) {
						$(relatedRecordPopup).html(data.body);
						relatedRecordPopup.slideDown();
					}
					$(relatedRecordBtn).addClass('hidden');
				});
			}

			return false;
		}
	};
}(AspenDiscovery.ResultsList || {}));