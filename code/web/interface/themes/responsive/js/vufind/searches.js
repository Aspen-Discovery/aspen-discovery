VuFind.Searches = (function(){
	$(function(){
		VuFind.Searches.enableSearchTypes();
		VuFind.Searches.initAutoComplete();

		//console.log(
		//		'Not opac', !Globals.opac,
		//		'Not Logged In', !Globals.loggedIn,
		//		'Local Storage', VuFind.hasLocalStorage(),
		//		'No showCovers Hidden Input', ($('input[name="showCovers"]').length == 0)
		//);

		// Add Browser-stored showCovers setting to the search form if there is a stored value set, and
		// this is not a OPAC Machine, and the user is not logged in, and there is not a hidden value
		// already set in the search form.
		// This allows a preset showCovers setting to be sent back with the first search without requiring login or
		// a page reload on the search results page.
		if (!Globals.opac && !Globals.loggedIn && VuFind.hasLocalStorage() && $('input[name="showCovers"]').length === 0){
			let showCovers = window.localStorage.getItem('showCovers') || false;
			//console.log('Show Covers Value : ', showCovers);
			if (showCovers.length > 0) {
				//console.log('Add showCovers value', showCovers);
				$("<input>").attr({
					type: 'hidden',
					name: 'showCovers',
					value: showCovers
				}).appendTo('#searchForm');
			}
		}
	});
	return{
		searchGroups: [],
		curPage: 1,
		displayMode: 'list', // default display Mode for results
		displayModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			list:''
		},

		getCombinedResults: function(fullId, shortId, source, searchTerm, searchType, numberOfResults){
			let url = Globals.path + '/Union/AJAX';
			let params = '?method=getCombinedResults&source=' + source + '&numberOfResults=' + numberOfResults + "&id=" + fullId + "&searchTerm=" + searchTerm + "&searchType=" + searchType;
			if ($('#hideCovers').is(':checked')){
				params += "&showCovers=off";
			}else{
				params += "&showCovers=on";
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					VuFind.showMessage("Error loading results", data.error);
				}else{
					$('#combined-results-section-results-' + shortId).html(data.results);
				}
			}).fail(VuFind.ajaxFail);
			return false;
		},

		combinedResultsDefinedOrder: [],
		reorderCombinedResults: function () {
			if ($('#combined-results-column-0').is(':visible')) {
				if ($('.combined-results-column-0', '#combined-results-column-0').length == 0){
					$('.combined-results-column-0').detach().appendTo('#combined-results-column-0');
					$('.combined-results-column-1').detach().appendTo('#combined-results-column-1');
				}
			} else {
				if ($('.combined-results-section', '#combined-results-all-column').length == 0) {
					$.each(VuFind.Searches.combinedResultsDefinedOrder, function (i, id) {
						el = $(id).parents('.combined-results-section').detach().appendTo('#combined-results-all-column');
					});
				}
			}
			return false;
		},

		getPreferredDisplayMode: function(){
			if (!Globals.opac && VuFind.hasLocalStorage()){
				temp = window.localStorage.getItem('searchResultsDisplayMode');
				if (VuFind.Searches.displayModeClasses.hasOwnProperty(temp)) {
					VuFind.Searches.displayMode = temp; // if stored value is empty or a bad value, fall back on default setting ("null" is returned from local storage when not set)
					$('input[name="view"]','#searchForm').val(VuFind.Searches.displayMode); // set the user's preferred search view mode on the search box.
				}
			}
		},

		toggleDisplayMode : function(selectedMode){
			let mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode, // check that selected mode is a valid option
					searchBoxView = $('input[name="view"]','#searchForm'), // display mode variable associated with the search box
					paramString = VuFind.replaceQueryParam('page', '', VuFind.replaceQueryParam('view',mode)); // set view in url and unset page variable
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (searchBoxView) searchBoxView.val(this.displayMode); // set value in search form, if present
			if (!Globals.opac && VuFind.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('searchResultsDisplayMode', this.displayMode);
			}
			if (mode === 'list') $('#hideSearchCoversSwitch').show(); else $('#hideSearchCoversSwitch').hide();
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		getMoreResults: function(){
			let url = Globals.path + '/Search/AJAX',
					params = VuFind.replaceQueryParam('page', this.curPage+1)+'&method=getMoreSearchResults',
					divClass = this.displayModeClasses[this.displayMode];
			params = VuFind.replaceQueryParam('view', this.displayMode, params); // set the view url parameter just in case.
			if (params.search(/[?;&]replacementTerm=/) != -1) {
				let searchTerm = location.search.split('replacementTerm=')[1].split('&')[0];
				params = VuFind.replaceQueryParam('lookfor', searchTerm, params);
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					VuFind.showMessage("Error loading search information", "Sorry, we were not able to retrieve additional results.");
				}else{
					let newDiv = $(data.records).hide();
					$('.'+divClass).filter(':last').after(newDiv);
					newDiv.fadeIn('slow');
					if (data.lastPage) $('#more-browse-results').hide(); // hide the load more results
					else VuFind.Searches.curPage++;
				}
			}).fail(VuFind.ajaxFail);
			return false;
		},

		initAutoComplete: function(){
			try{
				$("#lookfor").autocomplete({
					source:function(request,response){
						let url=Globals.path+"/Search/AJAX?method=getAutoSuggestList&searchTerm=" + $("#lookfor").val() + "&searchIndex=" + $("#searchIndex").val() + "&searchSource=" + $("#searchSource").val();
						$.ajax({
							url:url,
							dataType:"json",
							success:function(data){
								response(data);
							}
						});
					},
					position:{
						my:"left top",
						at:"left bottom",
						of:"#lookfor",
						collision:"none"
					},
					minLength:4,
					delay:600
				}).data('ui-autocomplete')._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "ui-autocomplete-item", item.value )
						.append( '<a>' + item.label + '</a>' )
						.appendTo( ul );
				};
			}catch(e){
				alert("error during autocomplete setup"+e);
			}
		},

		sendEmail: function(){
			if (Globals.loggedIn){
				let from = $('#from').val();
				let to = $('#to').val();
				let message = $('#message').val();
				let related_record = $('#related_record').val();
				let sourceUrl = window.location.href;

				let url = Globals.path + "/Search/AJAX";
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
								VuFind.showMessage("Success", data.message);
							} else {
								VuFind.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		initialSearchLoaded: false,
		enableSearchTypes: function(){
			let searchTypeElement = $("#searchSource");
			let catalogType = "catalog";
			if (searchTypeElement){
				let selectedSearchType = $(searchTypeElement.find(":selected"));
				if (selectedSearchType){
					catalogType = selectedSearchType.data("catalog_type");
				}
			}

			let searchIndexElement = $("#searchIndex");
			if (searchIndexElement) {
				let searchOptions = searchIndexElement.find("option");
				let firstVisible = true;
				if (VuFind.Searches.initialSearchLoaded === true) {
					$.each(searchOptions, function() {
						let searchOption = $(this);
						searchOption.prop('selected', false);
					});
				}
				$.each(searchOptions, function() {
					let searchOption = $(this);
					if (searchOption.data("search_source") === catalogType) {
						searchOption.show();
						if (VuFind.Searches.initialSearchLoaded === true && firstVisible) {
							searchOption.prop('selected', true);
							firstVisible = false;
						}
					}else{
						searchOption.hide();
					}
				});
			}
			VuFind.Searches.initialSearchLoaded = true;
		},

		processSearchForm: function(){
			//Get the selected search type submit the form
			let searchSource = $("#searchSource");
			if (searchSource.val() === 'existing'){
				$(".existingFilter").prop('checked', true);
				let originalSearchSource = $("#existing_search_option").data('original_type');
				searchSource.val(originalSearchSource);
			}
		},

		resetSearchType: function(){
			if ($("#lookfor").val() === ""){
				$("#searchSource").val($("#default_search_type").val());
			}
			return true;
		},

		filterAll: function(){
			// Go through all elements
			$(".existingFilter").prop('checked', true);
		},

		loadExploreMoreBar: function(section, searchTerm){
			let url = Globals.path + "/Search/AJAX";
			let params = "method=loadExploreMoreBar&section=" + encodeURIComponent(section);
			params += "&searchTerm=" + encodeURIComponent(searchTerm);
			let fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success == true){
						$("#explore-more-bar-placeholder").html(data.exploreMoreBar);
						VuFind.initCarousels();
					}
				}
			);
		}
	}
}(VuFind.Searches || {}));