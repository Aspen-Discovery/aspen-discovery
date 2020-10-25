AspenDiscovery.CollectionSpotlights = (function(){
	return {
		createSpotlightFromList: function (listId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToSpotlightForm&source=list&id=' + listId, true);
			return false;
		},
		createSpotlightFromSearch: function (searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToSpotlightForm&source=search&id=' + searchId, true);
			return false;
		},
		loadCarousel: function (spotlightListId, titlesUrl){
			$.getJSON(titlesUrl, function (data) {
				if (data.success) {
					//Create an unordered list for display
					let html = '<ul>';

					$.each(data.titles, function() {
						html += '<li class="carouselTitleWrapper">' + this.formattedTitle + '</li>';
					});

					html += '</ul>';

					let carouselElement = $('#collectionSpotlightCarousel' + spotlightListId);
					carouselElement.html(html);
					let jCarousel = carouselElement.jcarousel();

					// Reload carousel
					jCarousel.jcarousel('reload');
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		}
	};
}(AspenDiscovery.CollectionSpotlights || {}));