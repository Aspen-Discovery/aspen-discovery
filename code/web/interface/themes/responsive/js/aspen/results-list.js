AspenDiscovery.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		addIdToSeriesList: function(isbn){
			this.seriesList[this.seriesList.length] = isbn;
		},

		initializeDescriptions: function(){
			$(".descriptionTrigger").each(function(){
				var descElement = $(this);
				var descriptionContentClass = descElement.data("content_class");
				options = {
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
