AspenDiscovery.Searches = (function(){
	$(document).ready(function(){
		AspenDiscovery.Searches.initAutoComplete({});

		// Add Browser-stored showCovers setting to the search form if there is a stored value set, and
		// this is not a OPAC Machine, and the user is not logged in, and there is not a hidden value
		// already set in the search form.
		// This allows a preset showCovers setting to be sent back with the first search without requiring login or
		// a page reload on the search results page.
		if (!Globals.opac && !Globals.loggedIn && AspenDiscovery.hasLocalStorage() && $('input[name="showCovers"]').length === 0){
			var showCovers = window.localStorage.getItem('showCovers') || false;
			if (showCovers.length > 0) {
				$("<input>").attr({
					type: 'hidden',
					name: 'showCovers',
					value: showCovers
				}).appendTo('#searchForm');
			}
		}

		// talpa loading indicator, shown inline in #lookfor
		$('#searchForm').on('submit', function(ev)
		{
			var searchTypeElement = $("#searchSource");
			if(searchTypeElement.val() == 'talpa') {
				$('#lookfor').addClass('talpa_search_loading');
			}
		})
	});
	return {
		_advFacetCache: {},
		searchGroups: [],
		curPage: 1,
		displayMode: 'list', // default display Mode for results
		displayModeClasses: { // browse mode to css class correspondence
			covers:'results-covers-view',
			list:''
		},
		colcade: null,

		getCombinedResults: function(fullId, shortId, source, searchTerm, searchType, numberOfResults){
			var url = Globals.path + '/Union/AJAX';
			var params = '?method=getCombinedResults&source=' + source + '&numberOfResults=' + numberOfResults + "&id=" + fullId + "&searchTerm=" + searchTerm + "&searchType=" + searchType;
			if ($('#hideCovers').is(':checked')){
				params += "&showCovers=off";
			}else{
				params += "&showCovers=on";
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					AspenDiscovery.showMessage("Error loading results", data.error);
				}else{
					$('#combined-results-section-results-' + shortId).html(data.results);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		combinedResultsDefinedOrder: [],
		reorderCombinedResults: function () {
			if ($('#combined-results-column-0').is(':visible')) {
				if ($('.combined-results-column-0', '#combined-results-column-0').length === 0){
					$('.combined-results-column-0').detach().appendTo('#combined-results-column-0');
					$('.combined-results-column-1').detach().appendTo('#combined-results-column-1');
				}
			} else {
				if ($('.combined-results-section', '#combined-results-all-column').length === 0) {
					$.each(AspenDiscovery.Searches.combinedResultsDefinedOrder, function (i, id) {
						el = $(id).parents('.combined-results-section').detach().appendTo('#combined-results-all-column');
					});
				}
			}
			return false;
		},

		changeDropDownFacet: function (facetId) {
			var selectedFacetDropdown = $('#' + facetId + ' option:selected');
			window.location = selectedFacetDropdown.data('destination');
			return false;
		},

		getPreferredDisplayMode: function(){
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
				temp = window.localStorage.getItem('searchResultsDisplayMode');
				if (AspenDiscovery.Searches.displayModeClasses.hasOwnProperty(temp)) {
					AspenDiscovery.Searches.displayMode = temp; // if stored value is empty or a bad value, fall back on default setting ("null" is returned from local storage when not set)
					$('input[name="view"]','#searchForm').val(AspenDiscovery.Searches.displayMode); // set the user's preferred search view mode on the search box.
				}
			}
		},

		toggleDisplayMode : function(selectedMode){
			var mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode, // check that selected mode is a valid option
					searchBoxView = $('input[name="view"]','#searchForm'), // display mode variable associated with the search box
					paramString = AspenDiscovery.replaceQueryParam('page', '', AspenDiscovery.replaceQueryParam('view',mode)); // set view in url and unset page variable
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (searchBoxView) searchBoxView.val(this.displayMode); // set value in search form, if present
			if (!Globals.opac && AspenDiscovery.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('searchResultsDisplayMode', this.displayMode);
			}
			if (mode === 'list') {
				$('#hideSearchCoversSwitch').show();
				$('#hideSearchCoversSwitchModal').show();
			} else {
				$('#hideSearchCoversSwitch').hide();
				$('#hideSearchCoversSwitchModal').hide();
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		getMoreResults: function(){
			var url = Globals.path + '/Search/AJAX',
					params = AspenDiscovery.replaceQueryParam('page', this.curPage+1)+'&method=getMoreSearchResults',
					divClass = this.displayModeClasses[this.displayMode];
			params = AspenDiscovery.replaceQueryParam('view', this.displayMode, params); // set the view url parameter just in case.
			if (params.search(/[?;&]replacementTerm=/) !== -1) {
				var searchTerm = location.search.split('replacementTerm=')[1].split('&')[0];
				params = AspenDiscovery.replaceQueryParam('lookfor', searchTerm, params);
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					AspenDiscovery.showMessage("Error loading search information", "Sorry, we were not able to retrieve additional results.");
				}else{
					if (AspenDiscovery.Browse.browseStyle === 'masonry') {
						AspenDiscovery.Searches.colcade = new Colcade('#home-page-browse-results .grid', {
							columns: '.grid-col',
							items: '.grid-item'
						});
						AspenDiscovery.Searches.colcade.append($(data.records));
					} else {
						var newDiv = $(data.records).hide();
						$('.'+divClass).filter(':last').after(newDiv);
						newDiv.fadeIn('slow');
					}
					if (data.lastPage) $('#more-browse-results').hide(); // hide the load more results
					else AspenDiscovery.Searches.curPage++;
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initAutoComplete: function(parameters){
			var URLSearchParameters = new URLSearchParams(window.location.search);
			var searchTermSelector = (parameters.searchTermSelector) ? '#' + parameters.searchTermSelector : "#lookfor";
			try {
				if ($(searchTermSelector).length) {
					$(searchTermSelector).autocomplete({
						source: function (request, response) {
							var searchIndexSelected = $(searchTermSelector).closest('form').find('select#searchIndex option:selected').val();
							var searchIndex = (parameters.searchIndex) ? parameters.searchIndex : (searchIndexSelected) ? searchIndexSelected : (URLSearchParameters.get('searchIndex')) ? URLSearchParameters.get('searchIndex') : '';
							var searchSourceSelected = $(searchTermSelector).closest('form').find('select#searchSource option:selected').val();
							var searchSource = (parameters.searchSource) ? parameters.searchSource : (searchSourceSelected) ? searchSourceSelected : (URLSearchParameters.get('searchSource')) ? URLSearchParameters.get('searchSource') : '';
							var url = Globals.path + "/Search/AJAX?method=getAutoSuggestList&searchTerm=" + $(searchTermSelector).val() + "&searchIndex=" + searchIndex + "&searchSource=" + searchSource;
							$.ajax({
								url: url,
								dataType: "json",
								success: function (data) {
									response(data.suggestions);
								}
							});
						},
						position: {
							my: "left top",
							at: "left bottom",
							of: $(searchTermSelector),
							collision: "none"
						},
						minLength: 4,
						delay: 600,
						select: function (event, ui) {
							var form = $(searchTermSelector).closest('form');
							$(searchTermSelector).val(ui.item.value);
							if (form.attr('id') === 'searchForm') { form.trigger('submit'); }
							return false;
						}
					}).data('ui-autocomplete')._renderItem = function (ul, item) {
						return $("<li></li>")
							.data("ui-autocomplete-item", item.value)
							.append('<a>' + item.label + '</a>')
							.appendTo(ul);
					};
				}
			} catch (e) {
				alert("error during autocomplete setup:\n" + e);
			}
		},

		sendEmail: function(){
			if (Globals.loggedIn){
				var from = $('#from').val();
				var to = $('#to').val();
				var message = $('#message').val();
				var sourceUrl = window.location.href;

				var url = Globals.path + "/Search/AJAX";
				$.getJSON(url,
						{ // pass parameters as data
							method     : 'sendEmail'
							,from      : from
							,to        : to
							,message   : message
							,sourceUrl : sourceUrl
						},
						function(data) {
							if (data.result) {
								AspenDiscovery.showMessage("Success", data.message);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		loadSearchTypes: function(){
			var searchTypeElement = $("#searchSource");
			var catalogType = "catalog";
			var hasAdvancedSearch = false;
			var advancedSearchLabel = "Advanced Search";
			var advancedSearchUrl = "/Search/Advanced";
			if (searchTypeElement){
				var selectedSearchType = $(searchTypeElement.find(":selected"));
				if (selectedSearchType){
					catalogType = selectedSearchType.data("catalog_type");
					hasAdvancedSearch = selectedSearchType.data("advanced_search");
					advancedSearchLabel = selectedSearchType.data("advanced_search_label");

					if(searchTypeElement.val() == 'talpa'){
						var searchBox = $("#lookfor");
					}
				}
			}
			var url = "/Search/AJAX";
			$.getJSON(url,
				{ // pass parameters as data
					method : 'getSearchIndexes',
					searchSource : catalogType
				},
				function(data) {
					if (data.success) {
						var searchIndexElement = $("#searchIndex");
						if (searchIndexElement) {
							//Clear the existing options and load with the new ones
							searchIndexElement.empty();
							for(var searchIndex in data.searchIndexes) {
								var selected = "";
								if (searchIndex === data.selectedIndex){
									selected = " selected"
								}
								var defaultSearch = "";
								if (searchIndex === data.defaultSearchIndex){
									defaultSearch = " id='default_search_type'";
								}
								searchIndexElement.append("<option value='" + searchIndex + "'" + selected + defaultSearch + ">" + data.searchIndexes[searchIndex] + "</option>")
							}
							if (hasAdvancedSearch){
								searchIndexElement.append("<option value='advanced'>" + advancedSearchLabel + "</option>");
							}
						}
					}
				}
			);
		},

		loadExploreMoreBar: function(section, searchTerm){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=loadExploreMoreBar&section=" + encodeURIComponent(section);
			params += "&searchTerm=" + encodeURIComponent(searchTerm);
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#explore-more-bar-placeholder").html(data.exploreMoreBar);
						AspenDiscovery.initCarousels();
					}
				}
			);
		},

		lockFacet: function (clusterName) {
			event.stopPropagation();
			var url = Globals.path + "/Search/AJAX";
			var params = "method=lockFacet&facet=" + encodeURIComponent(clusterName);
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#facetLock_lockIcon_" + clusterName).hide();
						$("#facetLock_unlockIcon_" + clusterName).show();
						AspenDiscovery.Searches.updateAppliedFilterBadges(clusterName, true);
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},

		unlockFacet: function (clusterName) {
			event.stopPropagation();
			var url = Globals.path + "/Search/AJAX";
			var params = "method=unlockFacet&facet=" + encodeURIComponent(clusterName);
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#facetLock_lockIcon_" + clusterName).show();
						$("#facetLock_unlockIcon_" + clusterName).hide();
						AspenDiscovery.Searches.updateAppliedFilterBadges(clusterName, false);
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},

		updateAppliedFilterBadges: function (clusterName, isLocked) {
			var $badges = $(".applied-filters .facetValueBadge").filter(function() {
				var $badge = $(this);
				return $badge.data("filter-field") === clusterName || $badge.data("filter-unscoped") === clusterName;
			});
			$badges.each(function() {
				var $badge = $(this);
				var removalUrl = $badge.data("removal-url");
				var display = $badge.data("filter-display");
				var value = $badge.data("filter-value");
				$badge.off("click.locked");
				if (isLocked) {
					$badge.attr("aria-label", "Unlock and remove Filter");
					$badge.on("click.locked", function(e){
						e.preventDefault();
						AspenDiscovery.Searches.unlockFacetAndRemove(clusterName, removalUrl, value);
					});
					$badge.html('<i class="fas fa-lock fa-lg fa-fw" style="display:inline; vertical-align: middle"></i> ' + display);
				} else {
					$badge.attr("aria-label", "Remove Filter");
					$badge.html('<i class="fas fa-xmark text-danger remove-filter-icon" style="display:inline; vertical-align: middle"></i> ' + display);
				}
			});
		},

		unlockFacetAndRemove: function (clusterName, removalUrl, facetValue) {
			event.stopPropagation();
			var url = Globals.path + "/Search/AJAX";
			var params;
			if (facetValue !== undefined && facetValue !== null && facetValue !== "") {
				params = "method=unlockFacet&facet=" + encodeURIComponent(clusterName) + "&value=" + encodeURIComponent(facetValue);
			} else {
				params = "method=unlockFacet&facet=" + encodeURIComponent(clusterName);
			}
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						window.location = removalUrl;
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},

		clearAllFiltersAndUnlock: function (removeAllFiltersUrl) {
			event.stopPropagation();
			var url = Globals.path + "/Search/AJAX";
			var params = "method=clearAllLockedFacets";
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						window.location = removeAllFiltersUrl;
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},

		showSearchFacetPopup: function (searchId, facetName) {
			var url = Globals.path + '/Search/AJAX?method=getSearchFacetPopup&searchId=' + searchId + '&facetName=' + facetName;
			$.getJSON(url, function(data){
				if (data.success === true){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.buttons);
				}else{
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		searchFacetValuesKeyDown: function (e) {
			if (e.keyCode === 9) {
				AspenDiscovery.Searches.searchFacetValues();
			}else if (e.keyCode === 10 || e.keyCode === 13) {
				e.preventDefault();
				AspenDiscovery.Searches.searchFacetValues();
			}
			return false;
		},

		searchFacetValues: function () {
			$("#facetSearchResultsPopularHelp").hide();
			$("#facetSearchResultsLoading").show();
			$("#facetSearchResults").html("");
			var searchForm = $("#searchFacetValuesForm");
			var searchId = searchForm.find("#searchId").val();
			var facetName = searchForm.find("#facetName").val();
			var facetSearchTerm = searchForm.find("#facetSearchTerm").val();
			var url = Globals.path + '/Search/AJAX';
			var params = {
				'method': 'searchFacetTerms',
				'searchId': searchId,
				'facetName': facetName,
				'searchTerm': facetSearchTerm
			}
			$.getJSON(url, params, function(data){
				$("#facetSearchResultsLoading").hide();
				if (data.success === true){
					$("#facetSearchResults").html(data.facetResults);
				}else{
					$("#facetSearchResults").html(data.message);
				}
			});
		},

		showAdvancedSearchFacetPopup: function (facetName) {
			var cache = AspenDiscovery.Searches._advFacetCache;
			var show = function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.buttons);
				// Pre-check the checkbox matching the currently selected value
				var currentVal = aspenJQ('#facet-select-' + facetName).val();
				if (currentVal) {
					aspenJQ('.advFacetCheckbox').filter(function () {
						return aspenJQ(this).attr('data-filter') === currentVal;
					}).prop('checked', true);
				}
				// Enforce single-select: checking one unchecks all others.
				// If the user clicks the already-checked item, keep it checked so Apply always has a valid selection.
				aspenJQ('#modalDialog').off('change.advFacet').on('change.advFacet', '.advFacetCheckbox', function () {
					if (aspenJQ(this).prop('checked')) {
						aspenJQ('.advFacetCheckbox').not(this).prop('checked', false);
					} else {
						aspenJQ(this).prop('checked', true);
					}
				});
				// Bind Apply button via event delegation (avoids inline onclick issues)
				aspenJQ('#modalDialog').off('click.advFacetApply').on('click.advFacetApply', '.adv-facet-apply-btn', function () {
					AspenDiscovery.Searches.applyAdvancedFacetSelections();
				});
			};
			if (cache[facetName]) {
				show(cache[facetName]);
				return false;
			}
			var url = Globals.path + '/Search/AJAX?method=getAdvancedSearchFacetPopup&facetName=' + encodeURIComponent(facetName);
			$.getJSON(url, function (data) {
				if (data.success === true) {
					cache[facetName] = data;
					show(data);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		onAdvancedFacetSelectChange: function (selectEl) {
			var $select = aspenJQ(selectEl);
			var val     = $select.val();
			if (val === '__browse__') {
				$select.val($select.data('prevVal') || '');
				var facetName = selectEl.id.replace('facet-select-', '');
				AspenDiscovery.Searches.showAdvancedSearchFacetPopup(facetName);
			} else {
				$select.data('prevVal', val);
			}
		},

		searchAdvancedFacetValuesKeyDown: function (e) {
			if (e.keyCode === 9) {
				AspenDiscovery.Searches.searchAdvancedFacetValues();
			} else if (e.keyCode === 10 || e.keyCode === 13) {
				e.preventDefault();
				AspenDiscovery.Searches.searchAdvancedFacetValues();
			}
			return false;
		},

		searchAdvancedFacetValues: function () {
			$("#advFacetSearchResultsPopularHelp").hide();
			$("#advFacetSearchResultsLoading").show();
			$("#advFacetSearchResults").html("");
			var facetName   = $("#advFacetName").val();
			var searchTerm  = $("#advFacetSearchTerm").val();
			var url = Globals.path + '/Search/AJAX';
			var params = {
				'method':     'searchAdvancedFacetTerms',
				'facetName':  facetName,
				'searchTerm': searchTerm
			};
			$.getJSON(url, params, function (data) {
				$("#advFacetSearchResultsLoading").hide();
				if (data.success === true) {
					$("#advFacetSearchResults").html(data.facetResults);
				} else {
					$("#advFacetSearchResults").html(data.message);
				}
			});
		},

		applyAdvancedFacetSelections: function () {
			var $checked = aspenJQ('.advFacetCheckbox:checked').first();
			if ($checked.length === 0) {
				aspenJQ('#modalDialog').modal('hide');
				return;
			}
			var filterValue = $checked.attr('data-filter');
			var displayText = aspenJQ('<div/>').html($checked.attr('data-display')).text();
			var facetName   = $checked.attr('data-facet');
			var $select = aspenJQ('#facet-select-' + facetName);
			if ($select.length === 0) {
				aspenJQ('#modalDialog').modal('hide');
				return;
			}
			$select.find('option.adv-facet-dynamic').remove();
			$select.val(filterValue);
			if ($select.val() !== filterValue) {
				$select.append(aspenJQ('<option>', {value: filterValue, text: displayText, 'class': 'adv-facet-dynamic'}));
				$select.val(filterValue);
			}
			$select.data('prevVal', filterValue);
			aspenJQ('#modalDialog').modal('hide');
		},

		setAdvancedSearchFacetValue: function (el) {
			var $el         = aspenJQ(el);
			var filterValue = $el.attr('data-filter');
			var displayText = $el.attr('data-display');
			var facetName   = $el.attr('data-facet');
			var $select = aspenJQ('#facet-select-' + facetName);
			if ($select.length === 0) {
				aspenJQ('#modalDialog').modal('hide');
				return;
			}
			$select.val(filterValue);
			if ($select.val() !== filterValue) {
				$select.append(aspenJQ('<option>', {value: filterValue, text: displayText}));
				$select.val(filterValue);
			}
			$select.data('prevVal', filterValue);
			aspenJQ('#modalDialog').modal('hide');
		}
	}
}(AspenDiscovery.Searches || {}));
