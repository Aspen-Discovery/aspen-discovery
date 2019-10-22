AspenDiscovery.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		addIdToSeriesList: function(isbn){
			this.seriesList[this.seriesList.length] = isbn;
		},

		initializeDescriptions: function(){
			$(".descriptionTrigger").each(function(){
				let descElement = $(this);
				let descriptionContentClass = descElement.data("content_class");
				let options = {
					html: true,
					trigger: 'hover',
					title: 'Description',
					content: AspenDiscovery.ResultsList.loadDescription(descriptionContentClass)
				};
				descElement.popover(options);
			});
		},

		lessFacets: function(name){
			document.getElementById("more" + name).style.display="block";
			document.getElementById("narrowGroupHidden_" + name).style.display="none";
		},

		loadDescription: function(descriptionContentClass){
			var contentHolder = $(descriptionContentClass);
			return contentHolder[0].innerHTML;
		},

		moreFacets: function(name){
			document.getElementById("more" + name).style.display="none";
			document.getElementById("narrowGroupHidden_" + name).style.display="block";
		},

		moreFacetPopup: function(title, name){
			AspenDiscovery.showMessage(title, $("#moreFacetPopup_" + name).html());
		},

		multiSelectMoreFacetPopup: function(title, name){
			let button = "<a class='btn btn-primary' onclick='$(\"#facetPopup_" + name + "\").submit();'>Apply Filters</a>";
			AspenDiscovery.showMessageWithButtons(title, $("#moreFacetPopup_" + name).html(), button);
		},

		processMultiSelectMoreFacetForm: function(formId, fieldName){
			let newUrl = location.origin + location.pathname + "?";
			//Remove existing parameters for the facet from the url
			let existingQuery = location.search.substr(1);
			let firstTerm = true;
			if(existingQuery !== undefined){
				existingQuery = existingQuery.split('&');
				for(let i = 0; i < existingQuery.length; i++){
					let queryTerm = existingQuery[i].split('=');
					if (queryTerm[0] === 'filter[]'){
						//Check to see if we should include or not
						if (!queryTerm[1].startsWith(fieldName)){
							if (!firstTerm) {
								newUrl += "&";
							}else{
								firstTerm = false;
							}
							newUrl += existingQuery[i];
						}
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
				let name = $(this).attr('name');
				let value = $(this).attr('value');
				newUrl += (name + '=' + value);
			});

			document.location.href = newUrl;
			return false;
		},

		toggleRelatedManifestations: function(manifestationId){
			let relatedRecordPopup = $('#relatedRecordPopup_' + manifestationId);
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
