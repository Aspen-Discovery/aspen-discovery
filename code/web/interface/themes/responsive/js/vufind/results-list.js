VuFind.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		addIdToSeriesList: function(isbn){
			this.seriesList[this.seriesList.length] = isbn;
		},

		addIdToStatusList: function(id, type, useUnscopedHoldingsSummary) {
			if (type == undefined){
				type = 'VuFind';
			}
			var idVal = [];
			idVal['id'] = id;
			idVal['useUnscopedHoldingsSummary'] = useUnscopedHoldingsSummary;
			idVal['type'] = type;
			this.statusList[this.statusList.length] = idVal;
		},

		initializeDescriptions: function(){
			$(".descriptionTrigger").each(function(){
				var descElement = $(this);
				var descriptionContentClass = descElement.data("content_class");
				options = {
					html: true,
					trigger: 'hover',
					title: 'Description',
					content: VuFind.ResultsList.loadDescription(descriptionContentClass)
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
			VuFind.showMessage(title, $("#moreFacetPopup_" + name).html());
		},

		toggleFacetVisibility: function(){
			$facetsSection = $("#collapse-side-facets");
		},

		toggleRelatedManifestations: function(manifestationId){
			$('#relatedRecordPopup_' + manifestationId).toggleClass('hidden');
			var manifestationToggle = $('#manifestation-toggle-' + manifestationId);
			manifestationToggle.toggleClass('collapsed');
			if (manifestationToggle.hasClass('collapsed')){
				manifestationToggle.html('+');
			}else{
				manifestationToggle.html('-');
			}
			return false;

		}

	};
}(VuFind.ResultsList || {}));
