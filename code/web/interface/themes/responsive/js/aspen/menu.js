AspenDiscovery.Menu = (function(){
	$(function(){
		// Page Initializations
		AspenDiscovery.Menu.AllSideBarSelectors =
				AspenDiscovery.Menu.SearchBoxSelectors + ',' +
				AspenDiscovery.Menu.SideBarSearchSelectors + ',' +
				AspenDiscovery.Menu.SideBarAccountSelectors + ',' +
				AspenDiscovery.Menu.SideBarMenuSelectors + ',' +
				AspenDiscovery.Menu.ExploreMoreSelectors;

		// Set up Sticky Menus
		AspenDiscovery.Menu.stickyMenu('#horizontal-menu-bar-wrapper', 'sticky-menu-bar');
	});

	// Private Function for Menu.js functions only
	var reloadRelatedTitles = function() {
		if ($(AspenDiscovery.Menu.ExploreMoreSelectors).is(':visible')) {
			$('.jcarousel').jcarousel('reload')
		}
	};

	return {
		SearchBoxSelectors:      '#home-page-search',
		SideBarSearchSelectors:  '#narrow-search-label,#facet-accordion,#remove-search-label,#remove-search-label+.applied-filters,#similar-authors',
		SideBarAccountSelectors: '#home-page-login,#home-account-links',
		SideBarMenuSelectors:    '#home-page-login,#home-page-library-section',
		ExploreMoreSelectors:    '#explore-more-header,#explore-more-body',
		AllSideBarSelectors:     '', // Set above

		stickyMenu: function(menuContainerSelector, stickyMenuClass){
			let menu = $(menuContainerSelector);
			let viewportHeight = $(window).height();
			let switchPosition; // Meant to remain constant for the event handler below
			let switchPositionAdjustment = $('#masquerade-header').height();
			// if (menu.is(':visible')) {
			// 	switchPosition = menu.offset().top - switchPositionAdjustment;
			// 	// console.log('Initial offset : ' + menu.offset().top, 'switch position : ' + switchPosition);
			//
			// }
			$(window).resize(function(){
				viewportHeight = $(this).height()
			})
			.scroll(function(){
				if (menu.is(':visible') && viewportHeight < $('#content-container ').height()) { // only do this if the menu is visible & the page is larger than the viewport
					if (typeof switchPosition == 'undefined') {
						switchPosition = menu.offset().top - switchPositionAdjustment;
					}
					let fixedOffset = menu.offset().top;
					let notFixedScrolledPosition = $(this).scrollTop();

					// Toggle into an embedded mode
					if (menu.is('.' + stickyMenuClass) && fixedOffset <= switchPosition) {
						menu.removeClass(stickyMenuClass);
						$('#horizontal-search-box').show();
					}
					// Toggle into a fixed mode
					if (!menu.is('.' + stickyMenuClass) && notFixedScrolledPosition >= switchPosition) {
						menu.addClass(stickyMenuClass);
						$('#horizontal-search-box').hide();
						if (switchPositionAdjustment) {
							menu.css('top', switchPositionAdjustment);
						}
					}
				}
			}).scroll();
		},

		// Functions for the Mobile/Horizontal Menu
		Mobile: {
			hideAll: function(){
				return $(AspenDiscovery.Menu.AllSideBarSelectors).filter(':visible').slideUp() // return of object is needed for $.when(AspenDiscovery.Menu.hideAll()).done() calls
			},
		}
	}
}(AspenDiscovery.Menu || {}));