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
		if (!Globals.opac && !Globals.loggedIn && VuFind.hasLocalStorage() && $('input[name="showCovers"]').length == 0){
			var showCovers = window.localStorage.getItem('showCovers') || false;
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
			var url = Globals.path + '/Union/AJAX';
			var params = '?method=getCombinedResults&source=' + source + '&numberOfResults=' + numberOfResults + "&id=" + fullId + "&searchTerm=" + searchTerm + "&searchType=" + searchType;
			if ($('#hideCovers').is(':checked')){
				params += "&showCovers=off";
			}else{
				params += "&showCovers=on";
			}
			$.getJSON(url+params, function(data){
				if (data.success == false){
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
			var mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode, // check that selected mode is a valid option
					searchBoxView = $('input[name="view"]','#searchForm'), // display mode variable associated with the search box
					paramString = VuFind.replaceQueryParam('page', '', VuFind.replaceQueryParam('view',mode)); // set view in url and unset page variable
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (searchBoxView) searchBoxView.val(this.displayMode); // set value in search form, if present
			if (!Globals.opac && VuFind.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('searchResultsDisplayMode', this.displayMode);
			}
			if (mode == 'list') $('#hideSearchCoversSwitch').show(); else $('#hideSearchCoversSwitch').hide();
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		getMoreResults: function(){
			var url = Globals.path + '/Search/AJAX',
					params = VuFind.replaceQueryParam('page', this.curPage+1)+'&method=getMoreSearchResults',
					divClass = this.displayModeClasses[this.displayMode];
			params = VuFind.replaceQueryParam('view', this.displayMode, params); // set the view url parameter just in case.
			if (params.search(/[?;&]replacementTerm=/) != -1) {
				var searchTerm = location.search.split('replacementTerm=')[1].split('&')[0];
				params = VuFind.replaceQueryParam('lookfor', searchTerm, params);
			}
			$.getJSON(url+params, function(data){
				if (data.success == false){
					VuFind.showMessage("Error loading search information", "Sorry, we were not able to retrieve additional results.");
				}else{
					var newDiv = $(data.records).hide();
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
						var url=Globals.path+"/Search/AJAX?method=GetAutoSuggestList&searchTerm=" + $("#lookfor").val();
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
				});
			}catch(e){
				alert("error during autocomplete setup"+e);
			}
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		addAdvancedGroup: function(button){
			var currentRow;
			if (button == undefined){
				currentRow = $(".advancedRow").last();
			}else{
				currentRow = $(button).closest(".advancedRow");
			}

			//Clone the current row and reset data and ids as needed.
			var clonedData = currentRow.clone();
			clonedData.find(".btn").removeClass('active');
			clonedData.find('.lookfor').val("");
			clonedData.insertAfter(currentRow);

			VuFind.Searches.resetAdvancedRowIds();
			return false;
		},

		deleteAdvancedGroup: function(button){
			var currentRow = $(button).closest(".advancedRow");
			currentRow.remove();

			VuFind.Searches.resetAdvancedRowIds();
			return false;
		},
*/
		sendEmail: function(){
			if (Globals.loggedIn){
				var from = $('#from').val();
				var to = $('#to').val();
				var message = $('#message').val();
				var related_record = $('#related_record').val();
				//var sourceUrl = encodeURIComponent(window.location.href);
				var sourceUrl = window.location.href;

				var url = Globals.path + "/Search/AJAX";
				//var params = "method=sendEmail&from=" + encodeURIComponent(from) + "&to=" + encodeURIComponent(to) + "&message=" + encodeURIComponent(message) + "&url=" + sourceUrl;
				//passing through getJSON() data array instead
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

		enableSearchTypes: function(){
			var searchTypeElement = $("#searchSource");
			var catalogType = "catalog";
			if (searchTypeElement){
				var selectedSearchType = $(searchTypeElement.find(":selected"));
				if (selectedSearchType){
					catalogType = selectedSearchType.data("catalog_type");
				}
			}
			if (catalogType == 'islandora'){
				$(".islandoraType").show();
				$(".catalogType,.genealogyType,.ebscoType").hide();
			}else if (catalogType == 'genealogy'){
				$(".genealogyType").show();
				$(".catalogType,.islandoraType,.ebscoType").hide();
			}else if (catalogType == 'ebsco'){
				$(".ebscoType").show();
				$(".catalogType,.islandoraType,.genealogyType").hide();
			}else { // default catalog
				$(".catalogType").show();
				$(".genealogyType,.islandoraType,.ebscoType").hide();
			}
		},

		lastSpellingTimer: undefined,
		getSpellingSuggestion: function(query, process, isAdvanced){
			if (VuFind.Searches.lastSpellingTimer != undefined){
				clearTimeout(VuFind.Searches.lastSpellingTimer);
				VuFind.Searches.lastSpellingTimer = undefined;
			}

			var url = Globals.path + "/Search/AJAX?method=GetAutoSuggestList&searchTerm=" + query;
			//Get the search source
			if (isAdvanced){
				//Add the search type
			}
			VuFind.Searches.lastSpellingTimer = setTimeout(
					function(){
						$.get(url,
								function(data){
									process(data);
								},
								'json'
						)
					},
					500
			);
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		loadSearchGroups: function(){
			var searchGroups = VuFind.Searches.searchGroups;
			for (var i = 0; i < searchGroups.length; i++){
				if (i > 0){
					VuFind.Searches.addAdvancedGroup();
				}
				var searchGroup = searchGroups[i];
				var groupIndex = i+1;
				var searchGroupElement = $("#group" + groupIndex);
				searchGroupElement.find(".groupStartInput").val(searchGroup.groupStart);
				if (searchGroup.groupStart == 1){
					searchGroupElement.find(".groupStartButton").addClass("active");
				}
				searchGroupElement.find(".searchType").val(searchGroup.searchType);
				searchGroupElement.find(".lookfor").val(searchGroup.lookfor);
				searchGroupElement.find(".groupEndInput").val(searchGroup.groupEnd);
				if (searchGroup.groupEnd == 1){
					searchGroupElement.find(".groupEndButton").addClass("active");
				}
				searchGroupElement.find(".joinOption").val(searchGroup.join);
			}
			if (searchGroups.length == 0){
				VuFind.Searches.resetAdvancedRowIds();
			}
		},
*/

		processSearchForm: function(){
		// Check for Set Display Mode
		//	this.getPreferredDisplayMode();

			//Get the selected search type submit the form
			var searchSource = $("#searchSource");
			if (searchSource.val() == 'existing'){
				$(".existingFilter").prop('checked', true);
				var originalSearchSource = $("#existing_search_option").data('original_type');
				searchSource.val(originalSearchSource);
			}
		},

		/* Advanced Popup has been turned off. plb 10-22-2015
		resetAdvancedRowIds: function(){
			var searchRows = $(".advancedRow");
			searchRows.each(function(index, element){
				var indexVal = index + 1;
				var curRow = $(element);
				curRow.attr("id", "group" + indexVal);
				curRow.find(".groupStartInput")
						.prop("name", "groupStart[" + indexVal + "]")
						.attr("id", "groupStart" + indexVal + "Input");

				curRow.find(".groupStartButton")
						.data("hidden_element", "groupStart" + indexVal + "Input")
						.attr("id", "groupStart" + indexVal);

				curRow.find(".searchType")
						.attr("name", "searchType[" + indexVal + "]");

				curRow.find(".lookfor")
						.attr("name", "lookfor[" + indexVal + "]");

				curRow.find(".groupEndInput")
						.prop("name", "groupEnd[" + indexVal + "]")
						.attr("id", "groupEnd" + indexVal + "Input");

				curRow.find(".groupEndButton")
						.data("hidden_element", "groupEnd" + indexVal + "Input")
						.attr("id", "groupEnd" + indexVal);

				curRow.find(".joinOption")
						.attr("name", "join[" + indexVal + "]");
			});
			if (searchRows.length == 1){
				$(".deleteCriteria").hide();
				$(".groupStartButton").hide();
				$(".groupEndButton").hide();
			}else{
				$(".deleteCriteria").show();
				$(".groupStartButton").show();
				$(".groupEndButton").show();
			}
			var joinOptions = $(".joinOption");
			joinOptions.show();
			joinOptions.last().hide();
		},
*/
		resetSearchType: function(){
			if ($("#lookfor").val() == ""){
				$("#searchSource").val($("#default_search_type").val());
			}
			return true;
		},

		updateSearchTypes: function(catalogType, searchType, searchFormId){
			if (catalogType == 'catalog') {
				$("#basicType").val(searchType);
				$("#genealogyType").remove();
				$("#islandoraType").remove();
				$("#ebscoType").remove();
			}else if (catalogType == 'archive') {
				$("#islandoraType").val(searchType);
				$("#genealogyType").remove();
				$("#ebscoType").remove();
				$("#basicType").remove();
			}else if (catalogType == 'ebsco') {
				$("#ebscoType").val(searchType);
				$("#genealogyType").remove();
				$("#islandoraType").remove();
				$("#basicType").remove();
			}else{
				$("#genealogyType").val(searchType);
				$("#basicType").remove();
				$("#islandoraType").remove();
				$("#ebscoType").remove();
			}
			$(searchFormId).submit();
			return false;
		},

		filterAll: function(){
			// Go through all elements
			$(".existingFilter").prop('checked', true);
		},

		loadExploreMoreBar: function(section, searchTerm){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=loadExploreMoreBar&section=" + encodeURIComponent(section);
			params += "&searchTerm=" + encodeURIComponent(searchTerm);
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success == true){
						$("#explore-more-bar-placeholder").html(data.exploreMoreBar);
						VuFind.initCarousels();
					}
				}
			);
		}

/* Advanced Popup has been turned off. plb 10-22-2015
		submitAdvancedSearch: function(){
			$('#advancedPopup').submit();
			return false;
		}
*/
	}
}(VuFind.Searches || {}));