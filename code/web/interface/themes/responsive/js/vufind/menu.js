/**
 * Created by pbrammeier on 12/16/2015.
 */

VuFind.Menu = (function(){
	$(function(){
		// Page Initializations
		VuFind.Menu.AllSideBarSelectors =
				VuFind.Menu.SearchBoxSelectors + ',' +
				VuFind.Menu.SideBarSearchSelectors + ',' +
				VuFind.Menu.SideBarAccountSelectors + ',' +
				VuFind.Menu.SideBarMenuSelectors + ',' +
				VuFind.Menu.ExploreMoreSelectors;

		// Set up Sticky Menus
		VuFind.Menu.stickyMenu('#horizontal-menu-bar-container', 'sticky-menu-bar');
		VuFind.Menu.stickyMenu('#vertical-menu-bar', 'sticky-sidebar');

		var mobileMode = false;

		if ($('#horizontal-menu-bar-container').is(':visible')) {
			VuFind.Menu.hideAllFast();
			mobileMode = true;
		}

		// Trigger mode on resize between horizontal menu & vertical menu
		$(window).resize(function(){
			if (mobileMode) {
				// Entered Sidebar Mode
				if (!$('#horizontal-menu-bar-container').is(':visible')) { // this depends on horizontal menu always being present
				//	console.log('Entered SideBar Mode');
					mobileMode = false;

					if ($('#vertical-menu-bar').length) { // Sidebar Menu is in use
						//console.log('SideBar Menu is on');

						//Always show horizontal search bar if it being used when not in mobile menu
						$('#horizontal-search-box').show();

						// Un-select any sidebar option previously selected
						$('.menu-bar-option').removeClass('menu-icon-selected'); // Remove from any selected

						// Hide SideBar Content
						VuFind.Menu.hideAllFast();

						// Select the sidebar menu that was selected in the mobile menu, if any
						if ($('#mobile-menu-search-icon', '#horizontal-menu-bar-container').is('.menu-icon-selected')) {

							// Reset Refine Search Button
							VuFind.Menu.Mobile.resetRefineSearch();

							VuFind.Menu.SideBar.showSearch('.menu-bar-option:nth-child(1)>a')
						}
						else if ($('#mobile-menu-account-icon', '#horizontal-menu-bar-container').is('.menu-icon-selected')) {
							VuFind.Menu.SideBar.showAccount('.menu-bar-option:nth-child(2)>a')
						}
						else if ($('#mobile-menu-menu-icon', '#horizontal-menu-bar-container').is('.menu-icon-selected')) {
							VuFind.Menu.SideBar.showMenu('.menu-bar-option:nth-child(3)>a')
						}
						else if ($('#mobile-menu-explore-more-icon', '#horizontal-menu-bar-container').is('.menu-icon-selected')) {
							VuFind.Menu.SideBar.showExploreMore('.menu-bar-option:nth-child(4)>a')
						} else {
							// if nothing selected, Collapse sidebar
							if ($(VuFind.Menu.AllSideBarSelectors).filter(':visible').length == 0) {
								VuFind.Menu.collapseSideBar();
							}
						}
					} else {
						//console.log('No Sidebar Menu. Side bar content being displayed');
						// Show All Sidebar Stuff when Sidebar menu is not in use.
						$(VuFind.Menu.AllSideBarSelectors).show();
					}
				}
			} else {
				// Entered Mobile Mode
				if ($('#horizontal-menu-bar-container').is(':visible')) {
					//console.log('Entered Mobile Mode');
					mobileMode = true;

					// Un-select any horizontal option that might have been selected previously
					$('.menu-icon-selected', '#horizontal-menu-bar-container').removeClass('menu-icon-selected');

					// Hide SideBar Content
					VuFind.Menu.hideAllFast();

					// Select the corresponding menu option if one was selected in the sidebar menu
					if ($('.menu-bar-option:nth-child(1)', '#vertical-menu-bar').is('.menu-icon-selected')) {
						VuFind.Menu.Mobile.showSearch('#mobile-menu-search-icon')
					}
					else if ($('.menu-bar-option:nth-child(2)', '#vertical-menu-bar').is('.menu-icon-selected')) {
						VuFind.Menu.Mobile.showAccount('#mobile-menu-account-icon')
					}
					else if ($('.menu-bar-option:nth-child(3)', '#vertical-menu-bar').is('.menu-icon-selected')) {
						VuFind.Menu.Mobile.showMenu('#mobile-menu-menu-icon')
					}
					else if ($('.menu-bar-option:nth-child(4)', '#vertical-menu-bar').is('.menu-icon-selected')) {
						VuFind.Menu.Mobile.showExploreMore('#mobile-menu-explore-more-icon')
					}
				}
			}
		});


	});

	// Private Function for Menu.js functions only
	var reloadRelatedTitles = function() {
		if ($(VuFind.Menu.ExploreMoreSelectors).is(':visible')) {
			$('.jcarousel').jcarousel('reload')
		}
	};

	return {
		SearchBoxSelectors:      '#home-page-search',
		SideBarSearchSelectors:  '#narrow-search-label,#facet-accordion,#results-sort-label,#results-sort-label+div.row,#remove-search-label,#remove-search-label+.applied-filters,#similar-authors',
		SideBarAccountSelectors: '#home-page-login,#home-account-links',
		SideBarMenuSelectors:    '#home-page-login,#home-page-library-section',
		ExploreMoreSelectors:    '#explore-more-header,#explore-more-body',
		AllSideBarSelectors:     '', // Set above

		stickyMenu: function(menuContainerSelector, stickyMenuClass){
			var menu = $(menuContainerSelector),
					viewportHeight = $(window).height(),
					switchPosition, // Meant to remain constant for the event handler below
					// masqueradeMode = $('#masquerade-header').is(':visible'),
					switchPositionAdjustment = $('#masquerade-header').height();
			// if (menu.is(':visible')) {
			// 	switchPosition = menu.offset().top - switchPositionAdjustment;
			// 	// console.log('Initial offset : ' + menu.offset().top, 'switch position : ' + switchPosition);
			//
			// }
			$(window).resize(function(){
				viewportHeight = $(this).height()
			})
			.scroll(function(){
				if (menu.is(':visible') && viewportHeight < $('#main-content-with-sidebar').height()) { // only do this if the menu is visible & the page is larger than the viewport
					if (typeof switchPosition == 'undefined') {
						switchPosition = menu.offset().top - switchPositionAdjustment;
						// console.log('Initial offset after becoming visible : ' + menu.offset().top, 'switch position : ' + switchPosition);
					}
					var fixedOffset = menu.offset().top,
							notFixedScrolledPosition = $(this).scrollTop();
					// console.log('Selector :', menuContainerSelector, 'fixedOffset : ', fixedOffset, ' notFixedScrolledPosition : ', notFixedScrolledPosition, 'switch position : ', switchPosition, 'offset : ' + menu.offset().top);

					// Toggle into an embedded mode
					if (menu.is('.' + stickyMenuClass) && fixedOffset <= switchPosition) {
						menu.removeClass(stickyMenuClass)
					}
					// Toggle into a fixed mode
					if (!menu.is('.' + stickyMenuClass) && notFixedScrolledPosition >= switchPosition) {
						menu.addClass(stickyMenuClass);
						if (switchPositionAdjustment) {
							menu.css('top', switchPositionAdjustment);
						}
					}
				}
			}).scroll();
		},

		// This version is for hiding content without using an animation.
		// This is important for the initial page load, putting content in the necessary state with out being distracting
		hideAllFast: function(){
			return $(VuFind.Menu.AllSideBarSelectors).filter(':visible').hide() // return of object is needed for $when(VuFind.Menu.hideAll()).done() calls
		},

		collapseSideBar: function(){
			$('#side-bar,#vertical-menu-bar-container').addClass('collapsedSidebar');
			$('#main-content-with-sidebar').addClass('mainContentWhenSiderbarCollapsed');
			$('#main-content-with-sidebar .jcarousel').jcarousel('reload'); // resize carousels in the main content sections
		},

		openSideBar: function(){
			$('#main-content-with-sidebar').removeClass('mainContentWhenSiderbarCollapsed');
			$('#side-bar,#vertical-menu-bar-container').removeClass('collapsedSidebar');
			$('#main-content-with-sidebar .jcarousel').jcarousel('reload'); // resize carousels in the main content sections
		},

		reloadRelatedTitles: function() {
			if ($(VuFind.Menu.ExploreMoreSelectors).is(':visible')) {
				$('.jcarousel').jcarousel('reload')
			}
		},

		// Functions for the Vertical Sidebar Menu
		SideBar: {
			hideAll: function(){
				return $(VuFind.Menu.AllSideBarSelectors).filter(':visible').animate({width:'toggle'},350); // slide left to right
			},

			showMenuSection: function(sectionSelector, clickedElement, afterAnimationAction){
				$.when( this.hideAll() ).done(function(){
					var elem = $(clickedElement),
							parent = elem.parent('.menu-bar-option'); // For Vertical Menu Bar only
					if (parent.length > 0) {

						// Un-select Menu option
						if (parent.is('.menu-icon-selected')) {
							parent.removeClass('menu-icon-selected');
						}

						// Select a Menu Option
						else {
							$('.menu-bar-option').removeClass('menu-icon-selected'); // Remove from any selected
							parent.addClass('menu-icon-selected');
							VuFind.Menu.openSideBar();
							$.when( $(sectionSelector).animate({width:'toggle'},350) ).done(afterAnimationAction); // slide left to right
						}
					}
				})

				// Collapse side bar when no content is visible in it
				//   Sometimes a Selected Menu option may be empty any way (ie search menu w/ horizontal search box on home page)
				.done(function(){
					if ($(VuFind.Menu.AllSideBarSelectors).filter(':visible').length == 0) {
						VuFind.Menu.collapseSideBar();
					}
				})
			},

			showSearch: function(clickedElement){
				if ($('#horizontal-search-box').is(':visible')) { // horizontal search box is being used
					this.showMenuSection(VuFind.Menu.SideBarSearchSelectors, clickedElement);
				} else { // sidebar search box is being used
					this.showMenuSection('#home-page-search,' + VuFind.Menu.SideBarSearchSelectors, clickedElement);
				}
			},

			showMenu: function(clickedElement){
				this.showMenuSection(VuFind.Menu.SideBarMenuSelectors, clickedElement)
			},

			showAccount: function(clickedElement){
				this.showMenuSection(VuFind.Menu.SideBarAccountSelectors, clickedElement)
			},

			showExploreMore: function(clickedElement){
				this.showMenuSection(VuFind.Menu.ExploreMoreSelectors, clickedElement, reloadRelatedTitles)
			},

		},

		// Functions for the Mobile/Horizontal Menu
		Mobile: {
			hideAll: function(){
				return $(VuFind.Menu.AllSideBarSelectors).filter(':visible').slideUp() // return of object is needed for $.when(VuFind.Menu.hideAll()).done() calls
			},

			showMenuSection: function(sectionSelector, clickedElement){
				return $.when(this.hideAll() ).done(function(){
					var elem = $(clickedElement);
						VuFind.Menu.openSideBar();  // covers the case when view has switched from sidebar mode to mobile mode
						if ( elem.is('.menu-icon-selected')){
							elem.removeClass('menu-icon-selected');

							// Show MyAccount Mini Menu
							$('#mobileHeader').show();  // If the mobileHeader is present, show when no menu option is selected.
							$(sectionSelector).slideUp()

						}else { // selecting an option
							$('.menu-icon-selected', '#horizontal-menu-bar-container').removeClass('menu-icon-selected');
							elem.addClass('menu-icon-selected');

							// Hide MyAccount Mini Menu
							$('#mobileHeader').hide();  // If the mobileHeader section is present, hide when a menu option is selected
									// May need an exception for selecting search icon, when horizontal search is used. plb 2-12-2016  (Maybe even sidebar search box)

							$(sectionSelector).slideDown()
						}

				})
			},

			showSearch: function(clickedElement){
				this.showMenuSection('#home-page-search', clickedElement);
				this.resetRefineSearch();
			},

			showMenu: function(clickedElement){
				this.showMenuSection(VuFind.Menu.SideBarMenuSelectors, clickedElement)
			},

			showAccount: function(clickedElement){
				this.showMenuSection(VuFind.Menu.SideBarAccountSelectors, clickedElement);
				$('#myAccountPanel').filter(':not(.in)').collapse('show');  // Open up the MyAccount Section, if it is not open. (.collapse('show') acts like a toggle instead of always showing. plb 2-12-2016)
			},

			showExploreMore: function(clickedElement){
				this.showMenuSection(VuFind.Menu.ExploreMoreSelectors, clickedElement)
						.done(reloadRelatedTitles)
			},

			showSearchFacets: function(){
				//// If using the horizontal SearchBox, ensure Search menu is selected
				//if ($('#horizontal-search-box').is(':visible') && !$('#mobile-menu-search-icon').is('.menu-icon-selected')) {
				if (!$('#mobile-menu-search-icon').is('.menu-icon-selected')) {
					if ($('#horizontal-search-box').is(':visible')) {
						// always shows, so refine button can be clicked while other menus are open
						this.showSearch('#mobile-menu-search-icon');
					}
					else {
						// make selected so that sidebar mode will open correctly on resize
						$('#mobile-menu-search-icon').addClass('menu-icon-selected')
					}
				}
				var btn = $('#refineSearchButton'),
						text = btn.text();
				if (text == 'Refine Search') {
					$(VuFind.Menu.SideBarSearchSelectors).slideDown();
					btn.text('Hide Refine Search');
				}
				if (text == 'Hide Refine Search') {
					$(VuFind.Menu.SideBarSearchSelectors).slideUp();
					btn.text('Refine Search');
				}

				//btn.text( text == 'Refine Search' ? 'Hide Refine Search' : 'Refine Search' );
				//$('#refineSearch,'+VuFind.Menu.SideBarSearchSelectors).toggle();
			},

			resetRefineSearch: function(){
				$(VuFind.Menu.SideBarSearchSelectors).hide();
				$('#refineSearchButton').text('Refine Search');
			}

		}
	}
}(VuFind.Menu || {}));