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
			var button = "<a class='btn btn-primary' onclick='$(\"#facetPopup_" + name + "\").submit();'>"+buttonName+"</a>";
			AspenDiscovery.showMessageWithButtons(title, $("#moreFacetPopup_" + name).html(), button);
		},

		processMultiSelectMoreFacetForm: function(formId, fieldName){
			var newUrl = location.origin + location.pathname + "?";
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

			document.location.href = newUrl;
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

		}

	};
}(AspenDiscovery.ResultsList || {}));
