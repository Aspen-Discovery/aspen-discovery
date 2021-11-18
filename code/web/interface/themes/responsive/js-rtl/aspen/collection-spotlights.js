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
					var html = '<ul>';

					$.each(data.titles, function() {
						html += '<li class="carouselTitleWrapper">' + this.formattedTitle + '</li>';
					});

					html += '</ul>';

					var carouselElement = $('#collectionSpotlightCarousel' + spotlightListId);
					carouselElement.html(html);
					var jCarousel = carouselElement.jcarousel();

					// Reload carousel
					jCarousel.jcarousel('reload');
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},
		updateSpotlightFields: function () {
			var collectionSpotlightId = $('#collectionSpotlightId').val();
			if (collectionSpotlightId > '0') {
				$("#replaceExistingRadios").show();
			}else{
				$("#replaceExistingRadios").hide();
				$("#newSpotlightName").show();
			}

			document.getElementById('collectionSpotlightId').addEventListener('change', function() {
				document.getElementById("replaceExisting").checked = false;
				$("#existingSpotlightName").hide();
			});

			var listCount = 0;

			Array.from(document.querySelector("#collectionSpotlightListId").options).forEach(function(option_element) {
				var collectionSpotlightId = $('#collectionSpotlightId').val();
				var option_values = option_element.value;
				var option_value = option_values.split(".");
				var spotlightId = option_value[0];
				listCount++;

				if(spotlightId == collectionSpotlightId) {
					document.querySelector('#collectionSpotlightListId option[value="'+option_values+'"]').hidden = false;
				} else {
					if(spotlightId == '-1') {
						document.querySelector('#collectionSpotlightListId option[value="'+option_values+'"]').hidden = false;
						listCount--;
					} else {
						document.querySelector('#collectionSpotlightListId option[value="'+option_values+'"]').hidden = true;
						listCount--;
					}
				}

				var replaceExisting = $('#replaceExisting');
				$(replaceExisting).click(function() {
					if((replaceExisting.is(":checked")) && (listCount != 1)){
						$("#existingSpotlightName").show();
					}else{
						$("#existingSpotlightName").hide();
					}
				});
				document.getElementById("collectionSpotlightListId").value = "-1.0";
			});

		},
	};
}(AspenDiscovery.CollectionSpotlights || {}));