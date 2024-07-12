AspenDiscovery.Browse = (function(){
	return {
		colcade: null,
		curPage: 1,
		curCategory: '',
		curSubCategory : '',
		browseMode: 'covers',
		browseModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			grid:'home-page-browse-grid'
		},
		changingDisplay: false,
		browseStyle: 'masonry',
		accessibleMode: false,
		patronId: null,

		addToHomePage: function(searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getAddBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		getUpdateBrowseCategoryForm: function(searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getUpdateBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		getNewBrowseCategoryForm: function(searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getNewBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		initializeBrowseCategory: function(){
			var checkIfAccessible = document.querySelector('#browse-category-feed');
			if(checkIfAccessible) {
				AspenDiscovery.Browse.accessibleMode = true;
				return;
			}

			var checkBrowseStyle = document.querySelector('#home-page-browse-results');
			if(checkBrowseStyle) {
				if (!checkBrowseStyle.classList.contains('home-page-browse-results-grid-masonry')) {
					AspenDiscovery.Browse.browseStyle = 'grid';
				}
			}

			if (AspenDiscovery.Browse.browseStyle === 'masonry') {
				if (!$('#home-page-browse-results .grid').length) {
					return;
				}
				AspenDiscovery.Browse.colcade = new Colcade('#home-page-browse-results .grid', {
					columns: '.grid-col',
					items: '.grid-item'
				});
			} else {
				if (!$('#home-page-browse-results').length) {
					return;
				}
			}

			// wrapper for setting events and connecting w/ AspenDiscovery.initCarousels() in base.js

			var browseCategoryCarousel = $("#browse-category-carousel");

			// connect the browse catalog functions to the jcarousel controls
			browseCategoryCarousel.on('jcarousel:targetin', 'li', function(){
				var categoryId = $(this).data('category-id');
				AspenDiscovery.Browse.changeBrowseCategory(categoryId);
			});

			// allow categories to update on keypress
			browseCategoryCarousel.on('keypress', 'li', function(){
				var categoryId = $(this).data('category-id');
				AspenDiscovery.Browse.changeBrowseCategory(categoryId);
			});

			if ($('#browse-category-picker .jcarousel-control-prev').css('display') !== 'none') {
				// only enable if the carousel features are being used.
				// as of now, basalt & vail are not. plb 12-1-2014
				// TODO: when disabling the carousel feature is turned into an option, change this code to check that setting.

				// attach jcarousel navigation to clicking on a category
				browseCategoryCarousel.find('li').click(function(){
					$("#browse-category-carousel").jcarousel('scroll', $(this));
				});

				// attach jcarouselswipe to add nice swipe functionality
				(function($) {
					$(function() {
						$('.jcarousel')
							.jcarousel()
							.jcarouselSwipe();

						$('.jcarousel-control-prev')
							.on('jcarouselcontrol:active', function() {
								$(this).removeClass('inactive');
							})
							.on('jcarouselcontrol:inactive', function() {
								$(this).addClass('inactive');
							})
							.jcarouselControl({
								target: '-=1'
							});

						$('.jcarousel-control-next')
							.on('jcarouselcontrol:active', function() {
								$(this).removeClass('inactive');
							})
							.on('jcarouselcontrol:inactive', function() {
								$(this).addClass('inactive');
							})
							.jcarouselControl({
								target: '+=1'
							});

						$('.jcarousel-pagination')
							.on('jcarouselpagination:active', 'a', function() {
								$(this).addClass('active');
							})
							.on('jcarouselpagination:inactive', 'a', function() {
								$(this).removeClass('active');
							})
							.jcarouselPagination();
					});
				})(jQuery);

				// implements functions for libraries not using the carousel functionality
			} else {
				// bypass jcarousel navigation on a category click
				browseCategoryCarousel.find('li').click(function(){
					$(this).trigger('jcarousel:targetin');
				});
			}

		},

		toggleBrowseMode : function(selectedMode){
			if(!AspenDiscovery.Browse.accessibleMode) {
				var mode = this.browseModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.browseMode; // check that selected mode is a valid option
				var categoryTextId = this.curCategory || $('#browse-category-carousel .selected').data('category-id');
				var subCategoryTextId = this.curSubCategory || $('#browse-sub-category-menu .selected').data('sub-category-id');
				this.browseMode = mode; // set the mode officially
				if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
					window.localStorage.setItem('browseMode', this.browseMode);
				}
				// re-load the browse category
				if (subCategoryTextId) {
					return this.changeBrowseSubCategory(subCategoryTextId);
				} else {
					return this.changeBrowseCategory(categoryTextId);
				}
			}
		},

		resetBrowseResults : function(){
			// var classes = (function(){ // return list of all associated css classes (class list can be expanded without changing this code.)
			// 	var str = '', object = AspenDiscovery.Browse.browseModeClasses;
			// 	for (property in object) { str += object[property]+' ' }
			// 	return str;
			// })();
			// var selectedClass = this.browseModeClasses[this.browseMode];

			if (AspenDiscovery.Browse.browseStyle === 'masonry') {
				// hide current results while fetching new results
				if (AspenDiscovery.Browse.colcade !== undefined && AspenDiscovery.Browse.colcade !== null) {
					AspenDiscovery.Browse.colcade.destroy();
				}
				$('.grid-item').fadeOut().remove();

				AspenDiscovery.Browse.colcade = new Colcade('#home-page-browse-results .grid', {
					columns: '.grid-col',
					items: '.grid-item'
				});
			} else {
				$('.grid-item').fadeOut().remove();
			}

		},

		changeBrowseCategory: function(categoryTextId, addToHistory) {
			if (addToHistory === undefined) {
				addToHistory  = true;
			}
			if (AspenDiscovery.Browse.changingDisplay){
				return;
			}
			AspenDiscovery.Browse.changingDisplay = true;
			var url = Globals.path + '/Browse/AJAX';
			var params = {
				method: 'getBrowseCategoryInfo'
				, textId: categoryTextId || AspenDiscovery.Browse.curCategory
				, browseMode: this.browseMode
			};
			// Set selected Carousel
			$('.browse-category').removeClass('selected');
			// the carousel clones these divs sometimes, so grab only the text from the first one.
			var loadingID = 'initial';
			var newLabel = "";
			if (categoryTextId !== undefined){
				newLabel = $('#browse-category-' + categoryTextId + ' div').first().text(); // get label from corresponding li div
				loadingID = categoryTextId;
				$('#browse-category-' + categoryTextId).addClass('selected');
			}

			$('#selected-browse-search-link').attr('href', '#'); // clear the search results link so that

			// Set the new browse category labels (below the carousel)
			$('.selected-browse-label-search-text,.selected-browse-sub-category-label-search-text').fadeOut(function(){
				$('.selected-browse-label-search-text').html(newLabel).fadeIn()
			});

			// Hide current sub-categories while fetching new ones
			$('#browse-sub-category-menu').children().fadeOut(function(){
				$(this).remove() // delete sub-category buttons
			});

			// Hide current results while fetching new results
			this.resetBrowseResults();

			// Set a flag for the results we are currently loading
			//   so that if the user moves onto another category before we get results, we won't do anything
			this.loadingCategory = loadingID;
			$.getJSON(url, params, function(data){
				if (AspenDiscovery.Browse.loadingCategory === loadingID) {
					if (data.success === false) {
						if (data.message) {
							AspenDiscovery.showMessage("Error loading browse information", data.message);
						} else {
							AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
						}
					} else {
						var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'browseCategory', categoryTextId);
						categoryTextId = data.textId;
						var stateObj = {
							page: 'Browse',
							selectedBrowseCategory: categoryTextId
						};
						if (document.location.href && addToHistory){
							var label = 'Browse Catalog - ' + data.label;
							history.pushState(stateObj, label, newUrl);
						}

						$('#browse-category-' + categoryTextId).addClass('selected');
						$('.selected-browse-label-search-text').html(data.label); // update label

						var dismissButton = $('.selected-browse-dismiss');
						dismissButton.removeAttr('onclick');
						var thisCategoryToDismiss = data.subCategoryTextId || categoryTextId;
						dismissButton.attr('onclick', 'AspenDiscovery.Account.dismissBrowseCategory("'+data.patronId+'","'+ thisCategoryToDismiss +'")');
						dismissButton.attr('title', data.hideButtonLabel);

						AspenDiscovery.Browse.curPage = 1;
						AspenDiscovery.Browse.curCategory = data.textId;
						AspenDiscovery.Browse.curSubCategory = data.subCategoryTextId || '';
						// should be the first div only

						var resultsPanel = $('#home-page-browse-results');
						resultsPanel.fadeOut('fast', function () {
							$('.grid-item').remove();
							if (AspenDiscovery.Browse.browseStyle === 'masonry') {
								AspenDiscovery.Browse.colcade.append($(data.records));
							} else {
								var resultsPanelGrid = $('#home-page-browse-results');
								resultsPanelGrid.append($(data.records));
							}
							resultsPanel.fadeIn('slow');
							AspenDiscovery.Ratings.initializeRaters();
						});

						$('#selected-browse-search-link').attr('href', data.searchUrl); // set the Label's link

						// scroll to the correct category
						$("#browse-category-carousel").jcarousel('scroll', $("#browse-category-" + data.textId));

						// Display Sub-Categories
						if (data.subcategories) {
							$('#browse-sub-category-menu').html(data.subcategories).fadeIn();
							if (data.subCategoryTextId) { // selected sub category
								// Set and Show sub-category label
								$('#browse-sub-category-' + data.subCategoryTextId).addClass('selected');
								$('.selected-browse-sub-category-label-search-text')
									.html(data.subCategoryLabel)
									.fadeIn()
								dismissButton.attr('title', data.subCategoryHideButtonLabel);
							}
						}
						if (data.lastPage){
							$('#more-browse-results').hide(); // hide the load more results
						} else {
							$('#more-browse-results').show();
						}
					}
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$('#home-page-browse-results div').html('').show(); // should be first div
				//$('.home-page-browse-thumbnails').html('').show();
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function() {
				AspenDiscovery.Browse.loadingCategory = null;  // done loading category, empty flag
				AspenDiscovery.Browse.changingDisplay = false;
			});
			return false;
		},

		initializeBrowseCategorySwiper: function(categoryTextId) {
			AspenDiscovery.Browse.changingDisplay = true;
			var url = Globals.path + '/Browse/AJAX';
			var params = {
				method: 'getBrowseCategoryInfo'
				, textId: categoryTextId
				, browseMode: this.browseMode
			};
			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else {
					var resultsTabPanel = document.getElementById('swiper-browse-category-' + categoryTextId) ;
					resultsTabPanel.innerHTML = "";
					var browseSwiper = new Swiper('.swiper-browse-category-' + categoryTextId, {
						slidesPerView: 5,
						spaceBetween: 20,
						direction: 'horizontal',

						// Accessibility
						a11y: {
							enabled: true
						},

						// Navigation arrows
						navigation: {
							nextEl: '.swiper-button-next',
							prevEl: '.swiper-button-prev'
						},

						virtual: {
							enabled: true,
							slides: Object.values(data.records)
						}
					});
					// Fix keyboard navigation
					$("#browse-category-feed .swiper-wrapper > .swiper-slide:not(.swiper-slide-visible) a").prop("tabindex", "-1");
					$("#browse-category-feed .swiper-wrapper > .swiper-slide-visible a").removeProp("tabindex");
					browseSwiper.on('slideChangeTransitionEnd', function () {
						$("#browse-category-feed .swiper-wrapper > .swiper-slide:not(.swiper-slide-visible) a").prop("tabindex", "-1");
						$("#browse-category-feed .swiper-wrapper > .swiper-slide-visible a").removeProp("tabindex");
					});

					// update links for more results
					$('#browse-search-link-' + categoryTextId).attr('href', data.searchUrl);
					AspenDiscovery.Browse.patronId = data.patronId;

					// Prevent accidental cover selection when the user clicks too fast
					$(".swiper").on("mousedown", function (e) {
						e.preventDefault();
					});
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function(){
				AspenDiscovery.Browse.changingDisplay = false;
			});
		},

		changeBrowseSubCategory: function (subCategoryTextId, categoryId, addToHistory ) {
			if (AspenDiscovery.Browse.changingDisplay){
				return;
			}
			if (addToHistory === undefined) {
				addToHistory = true;
			}
			AspenDiscovery.Browse.changingDisplay = true;
			var url = Globals.path + '/Browse/AJAX';
			if (categoryId === undefined){
				categoryId = AspenDiscovery.Browse.curCategory;
			}
			var params = {
				method : 'getBrowseSubCategoryInfo'
				,textId : categoryId
				,subCategoryTextId : subCategoryTextId
				,browseMode : this.browseMode
			};
			// clear previous selections
			$('#browse-sub-category-menu button').removeClass('selected');
			$('.selected-browse-sub-category-label-search-text').fadeOut();
			if (categoryId !== undefined && categoryId !== AspenDiscovery.Browse.curCategory){
				$('.browse-category').removeClass('selected');

				var newLabel = $('#browse-category-' + categoryId + ' div').first().text(); // get label from corresponding li div
				$('#browse-category-' + categoryId).addClass('selected');

				$('#selected-browse-search-link').attr('href', '#'); // clear the search results link so that

				// Set the new browse category labels (below the carousel)
				$('.selected-browse-label-search-text,.selected-browse-sub-category-label-search-text').fadeOut(function(){
					$('.selected-browse-label-search-text').html(newLabel).fadeIn()
				});

				// Hide current sub-categories while fetching new ones
				$('#browse-sub-category-menu').children().fadeOut(function(){
					$(this).remove() // delete sub-category buttons
				});

				$("#browse-category-carousel").jcarousel('scroll', $("#browse-category-" + categoryId));
			}

			// Hide current results while fetching new results
			this.resetBrowseResults();

			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'browseCategory', AspenDiscovery.Browse.curCategory);
					newUrl += "&subCategory=" + subCategoryTextId;
					var stateObj = {
						page: 'Browse',
						selectedBrowseCategory: data.textId,
						subBrowseCategory: subCategoryTextId
					};

					var label = 'Browse Catalog - ';
					if (data.label) {
						label += data.label;
						$('.selected-browse-label-search-text').html(data.label);
					} // update label // needed when sub-category is specified via URL
					if (data.subCategoryLabel) {
						label += ' - ' + data.subCategoryLabel;
						$('.selected-browse-sub-category-label-search-text').html(data.subCategoryLabel);
					} else {
						$('.selected-browse-sub-category-label-search-text').fadeOut(); // Hide if no sub-category
					}
					if (document.location.href && addToHistory){
						history.pushState(stateObj, label, newUrl);
					}

					// Display Sub-Categories
					if (data.subcategories) {
						$('#browse-sub-category-menu').html(data.subcategories).fadeIn();
					}

					var dismissButton = $('.selected-browse-dismiss');
					dismissButton.removeAttr('onclick');
					if(data.textId === "system_user_lists" || data.textId === "system_saved_searches") {
						dismissButton.attr('onclick', 'AspenDiscovery.Account.dismissBrowseCategory("'+data.patronId+'","'+ data.textId + "_" + subCategoryTextId+'")');
					} else {
						dismissButton.attr('onclick', 'AspenDiscovery.Account.dismissBrowseCategory("'+data.patronId+'","'+subCategoryTextId+'")');
					}

					dismissButton.attr('title', data.subCategoryHideButtonLabel);

					var newSubCategoryLabel = data.subCategoryLabel; // get label from corresponding button
					// Set the new browse category label (below the carousel)


					if (data.subCategoryTextId) { // selected sub category
						// Set and Show sub-category label
						$('.selected-browse-sub-category-label-search-text')
							.html($('#browse-sub-category-' + data.subCategoryTextId).addClass('selected').text())
							.html(newSubCategoryLabel)
							.fadeIn();
					}

					AspenDiscovery.Browse.curPage = 1;
					if (data.textId) AspenDiscovery.Browse.curCategory = data.textId;
					if (data.subCategoryTextId) AspenDiscovery.Browse.curSubCategory = data.subCategoryTextId || '';

					var resultsPanel = $('#home-page-browse-results');
					resultsPanel.fadeOut('fast', function () {
						$('.grid-item').remove();
						if (AspenDiscovery.Browse.browseStyle === 'masonry') {
							AspenDiscovery.Browse.colcade.append($(data.records));
						} else {
							var resultsPanelGrid = $('#home-page-browse-results');
							resultsPanelGrid.append($(data.records));
						}
						resultsPanel.fadeIn('slow');
						AspenDiscovery.Ratings.initializeRaters();
					});
					
					$('#selected-browse-search-link').attr('href', data.searchUrl); // update the search link

					if (data.lastPage){
						$('#more-browse-results').hide(); // hide the load more results
					} else {
						$('#more-browse-results').show();
					}
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$('#home-page-browse-results div.row').html('').show(); // should be first div
				$('.selected-browse-sub-category-label-search-text').fadeOut(); // hide sub-category Label
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function(){
				AspenDiscovery.Browse.changingDisplay = false;
			});
			return false;
		},

		getMoreSubCategoryResultsLink: function (subCategoryTextId, categoryId) {
			var url = Globals.path + '/Browse/AJAX';
			var params = {
				method : 'getMoreBrowseSubCategoryResultsLink'
				,textId : categoryId
				,subCategoryTextId : subCategoryTextId
			};

			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					window.location = data.searchUrl;
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
			});
		},

		changeBrowseSubCategoryTab: function (subCategoryTextId, categoryId) {
			AspenDiscovery.Browse.changingDisplay = true;
			var url = Globals.path + '/Browse/AJAX';
			var params = {
				method : 'getBrowseSubCategoryInfo'
				,textId : categoryId
				,subCategoryTextId : subCategoryTextId
				,browseMode : this.browseMode
			};

			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					var resultsTabPanel = document.getElementById('swiper-sub-browse-category-' + subCategoryTextId) ;
					resultsTabPanel.innerHTML = "";
					var browseSwiper = new Swiper('.swiper-sub-browse-category-' + subCategoryTextId, {
						slidesPerView: 5,
						spaceBetween: 20,
						direction: 'horizontal',

						// Accessibility
						a11y: {
							enabled: true
						},

						// Navigation arrows
						navigation: {
							nextEl: '.swiper-button-next',
							prevEl: '.swiper-button-prev'
						},

						virtual: {
							enabled: true,
							slides: Object.values(data.records)
						}
					});
					// Fix keyboard navigation
					$("#browse-category-feed .swiper-wrapper > .swiper-slide:not(.swiper-slide-visible) a").prop("tabindex", "-1");
					$("#browse-category-feed .swiper-wrapper > .swiper-slide-visible a").removeProp("tabindex");
					browseSwiper.on('slideChangeTransitionEnd', function () {
						$("#browse-category-feed .swiper-wrapper > .swiper-slide:not(.swiper-slide-visible) a").prop("tabindex", "-1");
						$("#browse-category-feed .swiper-wrapper > .swiper-slide-visible a").removeProp("tabindex");
					});

				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function(){
				AspenDiscovery.Browse.changingDisplay = false;
			});
		},

		updateBrowseCategory: function(){
			var url = Globals.path + "/Browse/AJAX";
			var	params = {
				method:'updateBrowseCategory'
				,categoryName:$('#updateBrowseCategorySelect').val()
			};
			var searchId = $("#searchId");
			if (searchId){
				params['searchId'] = searchId.val()
			}
			var listId = $("#listId");
			if (listId){
				params['listId'] = listId.val()
			}
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessage(data.title, data.message, data.success);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		createBrowseCategory: function(){
			var url = Globals.path + "/Browse/AJAX";
			var	params = {
				method:'createBrowseCategory'
				,categoryName:$('#categoryName').val()
				,addAsSubCategoryOf:$('#addAsSubCategoryOfSelect').val()
			};
			var searchId = $("#searchId");
			if (searchId){
				params['searchId'] = searchId.val()
			}
			var listId = $("#listId");
			if (listId){
				params['listId'] = listId.val()
			}
			var reserveId = $("#reserveId");
			if (reserveId){
				params['reserveId'] = reserveId.val()
			}
			var addToHomePage = $("#addToHomePage");
			if (addToHomePage){
				params['addToHomePage'] = addToHomePage.prop('checked');
			}
			$.getJSON(url, params, function (data) {
				if (data.success === false) {
					AspenDiscovery.showMessage("Unable to create category", data.message);
				} else {
					AspenDiscovery.showMessage("Successfully added", data.message, true);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		getMoreResults: function(){
			//Increment the current page in case the button is clicked rapidly
			this.curPage += 1;
			var url = Globals.path + '/Browse/AJAX',
					params = {
						method : 'getMoreBrowseResults'
						,textId :  this.curSubCategory || this.curCategory
						  // if sub-category is currently selected fetch that, otherwise fetch the main category
						,pageToLoad : this.curPage
						,browseMode : this.browseMode
					},
					divClass = this.browseModeClasses[this.browseMode]; //|| this.browseModeClasses[Object.keys(this.browseModeClasses)[0]]; // if browseMode isn't set grab the first class
			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					if(!AspenDiscovery.Browse.accessibleMode) {
						if (AspenDiscovery.Browse.browseStyle === 'masonry') {
							AspenDiscovery.Browse.colcade.append($(data.records));
						} else {
							var resultsPanelGrid = $('#home-page-browse-results');
							resultsPanelGrid.append($(data.records));
						}
						AspenDiscovery.Ratings.initializeRaters();
						if (data.lastPage) {
							$('#more-browse-results').hide(); // hide the load more results TODO: implement server side
						}
					} else {
						// Accessible mode enabled
					}
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}

	}
}(AspenDiscovery.Browse || {}));