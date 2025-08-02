var aspenJQ = $;
var AspenDiscovery = (function(){
	
	// This provides a check to interrupt AjaxFail Calls on page redirects;
	 window.onbeforeunload = function(){
		Globals.LeavingPage = true;
	};

	aspenJQ(document).ready(function(){
		AspenDiscovery.initializeModalDialogs();
		AspenDiscovery.setupFieldSetToggles();
		AspenDiscovery.initCarousels();
		AspenDiscovery.toggleMenu();
		AspenDiscovery.autoOpenPanel();
		AspenDiscovery.scrollToTopPage();

		aspenJQ("#modalDialog").modal({show:false});
		aspenJQ('[data-toggle="tooltip"]').tooltip();
		aspenJQ('[data-toggle="popover"]').popover();

		aspenJQ('.panel')
				.on('show.bs.collapse', function () {
					aspenJQ(this).addClass('active');
				})
				.on('hide.bs.collapse', function () {
					aspenJQ(this).removeClass('active');
				});

		aspenJQ(window).on("popstate", function () {
			// if the state is the page you expect, pull the name and load it.
			if (history.state && history.state.page === "Checkouts") {
				var selector1 = '#checkoutsTab a[href="#' + history.state.source + '"]';
				aspenJQ(selector1).tab('show');
			}else if (history.state && history.state.page === "Holds") {
				var selector2 = '#holdsTab a[href="#' + history.state.source + '"]';
				aspenJQ(selector2).tab('show');
			}else if (history.state && history.state.page === "ReadingHistory") {
				AspenDiscovery.Account.loadReadingHistory(history.state.selectedUser, history.state.sort, history.state.pageNumber, history.state.showCovers, history.state.filter);
			}else if (history.state && history.state.page === "Browse") {
				if (history.state.subBrowseCategory){
					AspenDiscovery.Browse.changeBrowseSubCategory(history.state.subBrowseCategory, history.state.selectedBrowseCategory, false);
				}else{
					AspenDiscovery.Browse.changeBrowseCategory(history.state.selectedBrowseCategory, false);
				}
			}
		});

		// Handle search box clear button visibility.
		const $lookfor = aspenJQ("#lookfor");
		const $clearAddon = $lookfor.siblings('.clear-search');

		$lookfor.on("input", function() {
			if (aspenJQ(this).val().length > 0) {
				$clearAddon.css('display', 'block');
			} else {
				$clearAddon.css('display', 'none');
			}
		});
		// Set initial visibility.
		$lookfor.trigger("input");
		AspenDiscovery.FormFields.initializeCharacterCounters();
	});

	return {
		buildUrl: function(base, key, value) {
			var sep = (base.indexOf('?') > -1) ? '&' : '?';
			return base + sep + key + '=' + value;
		},

		changePageSize: function(){
			var url = window.location.href;
			if (url.match(/[&?]page=\d+/)) {
				url = url.replace(/page=\d+/, "page=1");
			}
			if (url.match(/[&?]pageSize=\d+/)) {
				url = url.replace(/pageSize=\d+/, "pageSize=" + aspenJQ("#pageSize").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&pageSize=" + aspenJQ("#pageSize").val();
				}else{
					url = url+ "?pageSize=" + aspenJQ("#pageSize").val();
				}
			}
			window.location.href = url;
		},

		changePage: function(){
			var url = window.location.href;
			if (url.match(/[&?]page=\d+/)) {
				url = url.replace(/page=\d+/, "page=" + aspenJQ("#page").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&page=" + aspenJQ("#page").val();
				}else{
					url = url+ "?page=" + aspenJQ("#page").val();
				}
			}
			window.location.href = url;
			return false;
		},

		changeSort: function(){
			var url = window.location.href;
			if (url.match(/[&?]sort=([A-Za-z_+]|%20)+/)) {
				url = url.replace(/sort=([A-Za-z_+]|%20)+/, "sort=" + aspenJQ("#sort").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&sort=" + aspenJQ("#sort").val();
				}else{
					url = url+ "?sort=" + aspenJQ("#sort").val();
				}
			}
			if (url.match(/[&?]page=(\d)+/)) {
				url = url.replace(/page=(\d)+/, "page=1");
			}
			window.location.href = url;
			return false;
		},

		closeLightbox: function(callback){
			if (AspenDiscovery._autoCloseTimer) {
				clearTimeout(AspenDiscovery._autoCloseTimer);
				AspenDiscovery._autoCloseTimer = null;
			}
			
			var modalDialog = aspenJQ("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
				aspenJQ('.modal-body').html("Loading...");
				aspenJQ(".modal-title").text("Loading...");

				if (callback !== undefined){
					modalDialog.on('hidden.bs.modal', function (e) {
						modalDialog.off('hidden.bs.modal');
						callback();
					});
				}
			}else{
				if (callback !== undefined){
					callback();
				}
			}
		},

		goToAnchor: function(anchorName) {
			aspenJQ('html,body').animate({scrollTop: aspenJQ("#" + anchorName).offset().top},'slow');
		},

		initCarousels: function(carouselClass){
			carouselClass = carouselClass || '.jcarousel';
			var jcarousel = aspenJQ(carouselClass);
			var wrapper   = jcarousel.parents('.jcarousel-wrapper');
			// console.log('init Carousels called for ', jcarousel);

			jcarousel.on('jcarousel:reload jcarousel:create', function() {

				var Carousel	   = aspenJQ(this);
				var width		  = Carousel.innerWidth();
				var liTags		 = Carousel.find('li');
				if (liTags == null ||liTags.length === 0){
					return;
				}
				var leftMargin	 = +liTags.css('margin-left').replace('px', '');
				var rightMargin	= +liTags.css('margin-right').replace('px', '');
				var numCategories  = Carousel.jcarousel('items').length || 1;
				var numItemsToShow = 1;

				// Adjust Browse Category Carousels
				if (jcarousel.is('#browse-category-carousel')){

					// set the number of categories to show; if there aren't enough categories, show all the categories instead
					if (width > 1000) {
						numItemsToShow = Math.min(5, numCategories);
					} else if (width > 700) {
						numItemsToShow = Math.min(4, numCategories);
					} else if (width > 500) {
						numItemsToShow = Math.min(3, numCategories);
					} else if (width > 400) {
						numItemsToShow = Math.min(2, numCategories);
					}

				}

				// Default Generic Carousel;
				else {
					if (width >= 800) {
						numItemsToShow = Math.min(5, numCategories);
					} else if (width >= 600) {
						numItemsToShow = Math.min(4, numCategories);
					} else if (width >= 400) {
						numItemsToShow = Math.min(3, numCategories);
					} else if (width >= 300) {
						numItemsToShow = Math.min(2, numCategories);
					}
				}

				// Set the width of each item in the carousel
				var calcWidth = (width - numItemsToShow*(leftMargin + rightMargin))/numItemsToShow;
				Carousel.jcarousel('items').css('width', Math.floor(calcWidth) + 'px');// Set Width

				if (numItemsToShow >= numCategories){
					aspenJQ(this).offsetParent().children('.jcarousel-control-prev').hide();
					aspenJQ(this).offsetParent().children('.jcarousel-control-next').hide();
				}else{
					aspenJQ(this).offsetParent().children('.jcarousel-control-prev').show();
					aspenJQ(this).offsetParent().children('.jcarousel-control-next').show();
				}

			})
			.jcarousel({
				wrap: 'circular'
			});

			// These Controls could possibly be replaced with data-api attributes
			aspenJQ('.jcarousel-control-prev', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '-=1'
					});

			aspenJQ('.jcarousel-control-next', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '+=1'
					});

			aspenJQ('.jcarousel-pagination', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.on('jcarouselpagination:active', 'a', function() {
						aspenJQ(this).addClass('active');
					})
					.on('jcarouselpagination:inactive', 'a', function() {
						aspenJQ(this).removeClass('active');
					})
					.on('click', function(e) {
						e.preventDefault();
					})
					.jcarouselPagination({
						perPage: 1,
						item: function(page) {
							return '<a href="#' + page + '" role="button" tabindex="0">' + page + '</a>';
						}
					});

			// If Browse Category js is set, initialize those functions
			if (typeof AspenDiscovery.Browse.initializeBrowseCategory == 'function') {
				AspenDiscovery.Browse.initializeBrowseCategory();
			}
		},

		initializeModalDialogs: function() {
			aspenJQ(".modalDialogTrigger").each(function(){
				aspenJQ(this).click(function(){
					var trigger = aspenJQ(this);
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					var dialogDestination = trigger.attr("href");
					aspenJQ("#myModalLabel").text(dialogTitle);
					aspenJQ(".modal-body").html('Loading.').load(dialogDestination);
					aspenJQ(".extraModalButton").hide();
					aspenJQ("#modalDialog").modal("show");
					return false;
				});
			});
		},

		getQuerystringParameters: function(){
			var vars = [];
			var q = location.search.substr(1);
			if(q !== undefined){
				q = q.split('&');
				for(var i = 0; i < q.length; i++){
					var hash = q[i].split('=');
					vars[hash[0]] = hash[1];
				}
			}
			return vars;
		},

		//// Quick Way to get a single URL parameter value (parameterName must be in the url query string)
		// getQueryParameterValue: function (parameterName) {
		// 	return location.search.split(parameterName + '=')[1].split('&')[0];
		// },

		replaceQueryParam : function (param, newValue, search) {
			if (typeof search == 'undefined') search = location.search;
			var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
			var query = search.replace(regex, "$1").replace(/&$/, '');
			return newValue ? (query.length > 2 ? query + "&" : "?") + param + "=" + newValue : query;
		},

		getSelectedTitles: function(promptForProcessingAll){
			if (promptForProcessingAll === undefined) {
				promptForProcessingAll = true;
			}
			var selectedTitles = aspenJQ("input.titleSelect:checked ").map(function() {
				return aspenJQ(this).attr('name') + "=" + aspenJQ(this).val();
			}).get().join("&");
			if (selectedTitles.length === 0 && promptForProcessingAll){
				var ret = confirm('You have not selected any items, process all items?');
				if (ret === true){
					AspenDiscovery.selectAllTitles();
					selectedTitles = titleSelect.map(function() {
						return aspenJQ(this).attr('name') + "=" + aspenJQ(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
		},
		selectAllTitles: function (){
			var titleSelect = aspenJQ("input.titleSelect");
			titleSelect.attr('checked', 'checked');
		},
		getSelectedLists: function(){
			var selectedLists = aspenJQ("input.listSelect:checked ").map(function() {
				return aspenJQ(this).attr('name') + "=" + aspenJQ(this).val();
			}).get().join("&");
			if (selectedLists.length === 0){
				var ret = confirm('No lists selected');
			}
			return selectedLists;
		},
		getSelectedBrowseCategories: function(){
			var selectedCategories = aspenJQ("input.categorySelect:checked ").map(function() {
				return aspenJQ(this).attr('name') + "=" + aspenJQ(this).val();
			}).get().join("&");
			if (selectedCategories.length === 0){
				var ret = confirm('No browse categories were selected');
			}
			return selectedCategories;
		},
		getSelectedAspenSites: function(){
			var selectedSites = aspenJQ("input.siteSelect:checked ").map(function() {
				return aspenJQ(this).attr('name');
			}).get().join(",");
			if (selectedSites.length === 0){
				AspenDiscovery.showMessage("Error", "Please select at least one site to update");
				return false;
			}
			return selectedSites;
		},

		pwdToText: function(fieldId){
			var elem = document.getElementById(fieldId);
			var input = document.createElement('input');
			input.id = elem.id;
			input.name = elem.name;
			input.value = elem.value;
			input.size = elem.size;
			input.onfocus = elem.onfocus;
			input.onblur = elem.onblur;
			input.className = elem.className;
			input.maxLength = elem.maxLength;
			if (elem.type === 'text' ){
				input.type = 'password';
			} else {
				input.type = 'text';
			}

			elem.parentNode.replaceChild(input, elem);
			return input;
		},

		setupFieldSetToggles: function (){
			aspenJQ('legend.collapsible').each(function(){
				aspenJQ(this).siblings().hide()
				.addClass("collapsed")
				.click(function() {
					aspenJQ(this).toggleClass("expanded collapsed")
					.siblings().slideToggle();
					return false;
				});
			});

			aspenJQ('fieldset.fieldset-collapsible').each(function() {
				var collapsible = aspenJQ(this);
				var legend = collapsible.find('legend:first');
				legend.addClass('fieldset-collapsible-label').bind('click', {collapsible: collapsible}, function(event) {
					var collapsible = event.data.collapsible;
					if (collapsible.hasClass('fieldset-collapsed')) {
						collapsible.removeClass('fieldset-collapsed');
					} else {
						collapsible.addClass('fieldset-collapsed');
					}
				});
				// Init.
				collapsible.addClass('fieldset-collapsed');
			});
		},

		showMessage: function(title, body, autoClose, refreshAfterClose, largeModal, hideTitle){
			if (largeModal === undefined || largeModal === false) {
				aspenJQ('#modalDialog').removeClass('modal-dialog-large');
			} else{
				aspenJQ('#modalDialog').addClass('modal-dialog-large');
			}
			if (hideTitle !== undefined && hideTitle === true) {
				aspenJQ('.modal-header').hide();
			} else{
				aspenJQ('.modal-header').show();
			}

			// Clear any existing auto-close timer to prevent it from closing new modals.
			if (AspenDiscovery._autoCloseTimer) {
				clearTimeout(AspenDiscovery._autoCloseTimer);
				AspenDiscovery._autoCloseTimer = null;
			}

			// autoClose can be a boolean (true = 3 seconds) or a number (custom timeout in milliseconds).
			// if refreshAfterClose is set but not autoClose, the page will reload when the box is closed by the user.
			if (autoClose === undefined) {
				autoClose = false;
			}
			if (refreshAfterClose === undefined) {
				refreshAfterClose = false;
			}
			aspenJQ("#myModalLabel").html(title);
			aspenJQ(".modal-body").html(body);
			aspenJQ('.modal-buttons').html('');
			const modalDialog = aspenJQ("#modalDialog");
			modalDialog.removeClass('image-popup');
			modalDialog.modal('show');
			
			if (autoClose) {
				// Determine timeout duration
				let timeoutDuration;
				if (typeof autoClose === 'number') {
					timeoutDuration = autoClose;
				} else {
					timeoutDuration = 3000;
				}
				
				AspenDiscovery._autoCloseTimer = setTimeout(function(){
					// Only close if this timer hasn't been cleared (prevents closing new modals).
					if (AspenDiscovery._autoCloseTimer) {
						AspenDiscovery._autoCloseTimer = null;
						if (refreshAfterClose) {
							location.reload();
						} else {
							AspenDiscovery.closeLightbox();
						}
					}
				}, timeoutDuration);
			} else if (refreshAfterClose) {
				modalDialog.on('hide.bs.modal', function(){
					location.reload();
				})
			}
		},

		showMessageWithButtons: function(title, body, buttons, refreshAfterClose, closeDestination, largeModal, hideTitle, hideCloseButton){
			if (AspenDiscovery._autoCloseTimer) {
				clearTimeout(AspenDiscovery._autoCloseTimer);
				AspenDiscovery._autoCloseTimer = null;
			}
			
			if (largeModal === undefined || largeModal === false) {
				aspenJQ('.modal-dialog').removeClass('modal-dialog-large');
			}else{
				aspenJQ('.modal-dialog').addClass('modal-dialog-large');
			}
			if (hideTitle !== undefined && hideTitle === true) {
				aspenJQ('.modal-header').hide();
			}else{
				aspenJQ('.modal-header').show();
			}
			if (hideCloseButton !== undefined && hideCloseButton === true) {
				aspenJQ('#modalCloseButton').hide();
			}else{
				aspenJQ('#modalCloseButton').show();
			}
			if (refreshAfterClose === undefined){
				refreshAfterClose = false;
			}
			aspenJQ("#myModalLabel").html(title);
			aspenJQ(".modal-body").html(body);
			aspenJQ('.modal-body [data-toggle="tooltip"]').tooltip();
			aspenJQ('.modal-buttons').html(buttons);
			if (closeDestination !== undefined) {
				Globals.modalCloseDestination = closeDestination;
				aspenJQ(".modalClose").click(function () {
					if (Globals.modalCloseDestination.length > 0) {
						document.location.href = Globals.modalCloseDestination
						return false;
					}
				});
			} else {
				Globals.modalCloseDestination = '';
			}
			aspenJQ("#modalDialog").modal('show');
			if (refreshAfterClose) {
				aspenJQ("#modalDialog").on('hide.bs.modal', function(){
					location.reload();
				})
			}
		},

		confirm: function(messageTitle, messageBody, okButtonLabel, cancelButtonLabel, translate, confirmFunctionAsString, confirmStyle) {
			if (confirmStyle === undefined) {
				confirmStyle = 'btn-primary';
			}
			if (okButtonLabel === undefined) {
				okButtonLabel = 'Ok';
			}
			if (cancelButtonLabel === undefined) {
				cancelButtonLabel = 'Cancel';
			}
			if (translate === true) {
				var language = Globals.language;
				$.getJSON(Globals.path + '/API/SystemAPI?method=getBulkTranslations&terms[1]=' + encodeURI(messageTitle) + '&terms[2]=' + encodeURI(messageBody) + '&terms[3]=' + okButtonLabel + '&terms[4]=' + cancelButtonLabel + '&language=' + language, function (data) {
					if (data.result.success) {
						if (data[language]) {
							messageTitle = data[language][1];
							messageBody = data[language][2];
							okButtonLabel = data[language][3];
							cancelButtonLabel = data[language][4];
						}
					}
				}).then(function () {
					var buttons = "<button id='confirmOkBtn' class='tool btn " + confirmStyle + "' onclick='" + confirmFunctionAsString + "'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i> " + okButtonLabel + "</button>";
					buttons += "<button id='confirmCancelBtn' class='tool btn btn-default' onclick='AspenDiscovery.closeLightbox()'>" + cancelButtonLabel + "</button>";
					AspenDiscovery.showMessageWithButtons(messageTitle, messageBody, buttons, false, '', false, messageTitle.length === 0,true);
				});
			}

		},

		// common loading message for lightbox while waiting for AJAX processes to complete.
		loadingMessage: function() {
			AspenDiscovery.showMessage(Globals.loadingTitle, Globals.loadingBody)
		},

		// common message for when an AJAX call has failed.
		ajaxFail: function() {
			if (!Globals.LeavingPage) AspenDiscovery.showMessage(Globals.requestFailedTitle, Globals.requestFailedBody);
		},

		showElementInPopup: function(title, elementId, buttonsElementId){
			// buttonsElementId is optional
			var modalDialog = aspenJQ("#modalDialog");
			if (modalDialog.is(":visible")){
				AspenDiscovery.closeLightbox(function(){AspenDiscovery.showElementInPopup(title, elementId)});
			}else{
				aspenJQ(".modal-title").html(title);
				var elementText = aspenJQ(elementId).html();
				var elementButtons = buttonsElementId ? aspenJQ(buttonsElementId).html() : '';
				aspenJQ(".modal-body").html(elementText);
				aspenJQ('.modal-buttons').html(elementButtons);

				modalDialog.removeClass('image-popup')
				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(embedLocation){
			if (embedLocation === undefined) {
				embedLocation = "";
			}
			var selectedId = aspenJQ("#selectLibraryHours" + embedLocation).find(":selected").val();
			aspenJQ(".locationInfo" + embedLocation).hide();
			aspenJQ("#locationAddress" + embedLocation + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			var toggle = aspenJQ(toggleSelector);
			var value = toggle.prop('checked');
			aspenJQ(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode === 13){
				aspenJQ(formToSubmit).submit();
			}
		},

		changeTranslationMode: function(start){
			var url = window.location.href;
			url = url.replace(/[&?](start|stop)TranslationMode=(true)?/, '');
			if (start) {
				url = this.buildUrl(url,'startTranslationMode', 'true');
			}else{
				url = this.buildUrl(url,'stopTranslationMode', 'true');
			}
			window.location.href = url;
		},

		hasLocalStorage: function () {
			// arguments.callee.haslocalStorage is the function's "static" variable for whether or not we have tested the
			// that the localStorage system is available to us.

			//console.log(typeof arguments.callee.haslocalStorage);
			if(typeof arguments.callee.haslocalStorage == "undefined") {
				if ("localStorage" in window) {
					try {
						window.localStorage.setItem('_tmptest', 'temp');
						arguments.callee.haslocalStorage = (window.localStorage.getItem('_tmptest') === 'temp');
						// if we get the same info back, we are good. Otherwise, we don't have localStorage.
						window.localStorage.removeItem('_tmptest');
					} catch(error) { // something failed, so we don't have localStorage available.
						arguments.callee.haslocalStorage = false;
					}
				} else arguments.callee.haslocalStorage = false;
			}
			return arguments.callee.haslocalStorage;
		},

		saveLanguagePreferences:function(){
			var preference = aspenJQ("#searchPreferenceLanguage option:selected").val();
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'saveLanguagePreference',
				searchPreferenceLanguage : preference
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						if (data.message.length > 0){
							//User was logged in, show a message about how to update
							AspenDiscovery.showMessage('Success', data.message, true, true);
						}else{
							//Refresh the page
							// noinspection SillyAssignmentJS
							window.location.href = window.location.href;
						}
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		setLanguage: function(selectedLanguage) {
			//Update the user interface with the selected language
			if (selectedLanguage === undefined) {
				selectedLanguage = aspenJQ("#selected-language option:selected").val();
			}
			var curLocation = window.location.href;
			var newParam = 'myLang=' + selectedLanguage;
			if (curLocation.indexOf(newParam) === -1){
				var newLocation = curLocation.replace(new RegExp('([?&])myLang=(.*?)(?:&|$)'), '$1' + newParam);
				if (newLocation === curLocation){
					newLocation = AspenDiscovery.buildUrl(curLocation, 'myLang', selectedLanguage);
				}
				window.location.href = newLocation;
			}

			return false;
		},

		showTranslateForm: function(termId) {
			var url = Globals.path + "/AJAX/JSON?method=getTranslationForm&termId=" + termId;
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		saveTranslation: function(){
			var termId = aspenJQ("#termId").val();
			var translationId = aspenJQ("#translationId").val();
			var translation = aspenJQ("#translation").val();
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'saveTranslation',
				translationId : translationId,
				translation : translation
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						aspenJQ(".term_" + termId ).html(translation);
						aspenJQ(".translation_id_" + translationId ).removeClass('not_translated').addClass("translated");
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},
		deleteTranslationTerm: function(termId) {
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'deleteTranslationTerm',
				termId : termId
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						aspenJQ("#term_" + termId ).hide();
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		toggleMenu: function() {
			// fixed bootstrap account-menu toggle
			aspenJQ('div.dropdown.menuToggleButton.accountMenu a').on('click', function (event) {
				aspenJQ(this).parent().toggleClass('open');
			});
			aspenJQ('div.dropdown.menuToggleButton.accountMenu').on('keyup', function (event) {
				aspenJQ(this).addClass('open');
			});
			aspenJQ(document).on('click', function (e) {
				var $trigger = aspenJQ("div.dropdown.menuToggleButton.accountMenu");
				if($trigger !== event.target && !$trigger.has(event.target).length){
					aspenJQ('div.dropdown.menuToggleButton.accountMenu').removeClass('open');
				}
			});
			aspenJQ(document).on('keyup', function (e) {
				var $trigger = aspenJQ("div.dropdown.menuToggleButton.accountMenu");
				if($trigger !== event.target && !$trigger.has(event.target).length){
					aspenJQ('div.dropdown.menuToggleButton.accountMenu').removeClass('open');
				}
			});
			// fixed bootstrap header-menu toggle
			aspenJQ('div.dropdown.menuToggleButton.headerMenu a').on('click', function (event) {
				aspenJQ(this).parent().toggleClass('open');
			});

			aspenJQ('div.dropdown.menuToggleButton.headerMenu').on('keyup', function (event) {
				aspenJQ(this).addClass('open');
			});

			aspenJQ(document).on('click', function (e) {
				var $trigger = aspenJQ("div.dropdown.menuToggleButton.headerMenu");
				if($trigger !== event.target && !$trigger.has(event.target).length){
					aspenJQ('div.dropdown.menuToggleButton.headerMenu').removeClass('open');
				}
			});

			aspenJQ(document).on('keyup', function (e) {
				var $trigger = aspenJQ("div.dropdown.menuToggleButton.headerMenu");
				if($trigger !== event.target && !$trigger.has(event.target).length){
					aspenJQ('div.dropdown.menuToggleButton.headerMenu').removeClass('open');
				}
			});
			return false;
		},
		closeMenu: function(){
			var headerMenu = aspenJQ('#header-menu');
			var menuButton = aspenJQ('#menuToggleButton');
			var menuButtonIcon = aspenJQ('#menuToggleButton > i');
			headerMenu.slideUp('slow');
			menuButtonIcon.addClass('fa-bars');
			menuButtonIcon.removeClass('fa-times');
			menuButton.removeClass('selected');
		},
		toggleMenuSection: function(categoryName) {
			var menuSectionHeaderIcon = aspenJQ('#' + categoryName + "MenuSection > i");
			var menuSectionBody = aspenJQ('#' + categoryName + "MenuSectionBody");
			if (menuSectionBody.is(':visible')){
				menuSectionBody.slideUp();
				menuSectionHeaderIcon.addClass('fa-caret-right');
				menuSectionHeaderIcon.removeClass('fa-caret-down');
			}else{
				menuSectionBody.slideDown();
				menuSectionHeaderIcon.removeClass('fa-caret-right');
				menuSectionHeaderIcon.addClass('fa-caret-down');
			}

			return false;
		},
		showCustomMenu: function (menuName) {
			// fixed bootstrap custom menu toggles
			aspenJQ('div.dropdown.menuToggleButton.' + menuName + 'Menu a').on('click', function (event) {
				aspenJQ(this).parent().toggleClass('open');
			});
			aspenJQ(document).on('click', function (e) {
				var trigger = aspenJQ('div.dropdown.menuToggleButton.' + menuName + 'Menu');
				if(trigger !== event.target && !trigger.has(event.target).length){
					aspenJQ('div.dropdown.menuToggleButton.' + menuName + 'Menu').removeClass('open');
				}
			});
		},
		formatCurrency: function(currencyValue, elementToUpdate){
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'formatCurrency',
				currencyValue : currencyValue
			};
			$.getJSON(url, params,
				function(data) {
					if (data.result.success) {
						aspenJQ(elementToUpdate).text(data.result.formattedValue);
					} else {
						aspenJQ(elementToUpdate).text('Unable to format currency');
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		resetSearchBox: function() {
			const $lookfor = aspenJQ("#lookfor");
			$lookfor.val("");
			$lookfor.trigger("focus");
			$lookfor.siblings('.clear-search').css('display', 'none');
			return false;
		},
		autoOpenPanel: function() {
			var hash = window.location.hash.substr(1);
			if (hash) {
				var requestedPanel = hash;
				var element = '#'.concat(requestedPanel);
				aspenJQ(element).addClass('active');
				var element2 = '#'.concat(requestedPanel, "Body");
				aspenJQ(element2).removeClass('collapse');
				aspenJQ(element2).addClass('in');
				aspenJQ(element2).css("height","auto");
			}
		},
		scrollToTopPage: function() {
			// Set a variable for our button element.
			var scrollToTopButton = document.getElementById('js-top');
			if (scrollToTopButton) {
				window.addEventListener("scroll", AspenDiscovery.scrollFunc);

				// When the button is clicked, run our ScrollToTop function
				scrollToTopButton.onclick = function (e) {
					e.preventDefault();
					AspenDiscovery.scrollToTop();
				}
			}
		},

		// Let's set up a function that shows our scroll-to-top button if we scroll beyond the height of the initial window.
		scrollFunc: function () {
			var scrollToTopButton = document.getElementById('js-top');
			// Get the current scroll value
			var y = window.scrollY;

			// If the scroll value is greater than the window height, let's add a class to the scroll-to-top button to show it!
			if (y > 0) {
				scrollToTopButton.className = "top-link show hidden-xs hidden-sm";
			} else {
				scrollToTopButton.className = "top-link hide";
			}
		},

		scrollToTop: function () {
			// Let's set a variable for the number of pixels we are from the top of the document.
			var c = document.documentElement.scrollTop || document.body.scrollTop;

			// If that number is greater than 0, we'll scroll back to 0, or the top of the document.
			// We'll also animate that scroll with requestAnimationFrame:
			// https://developer.mozilla.org/en-US/docs/Web/API/window/requestAnimationFrame
			if (c > 0) {
				window.requestAnimationFrame(AspenDiscovery.scrollToTop);
				// ScrollTo takes an x and a y coordinate.
				// Increase the '10' value to get a smoother/slower scroll!
				window.scrollTo(0, c - c / 10);
			}
		},

		showDisplaySettings: function () {
			var url = Globals.path + "/AJAX/JSON?method=getDisplaySettingsForm";
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		updateDisplaySettings: function () {
			var preferredLanguage = aspenJQ("#preferredLanguage option:selected").val();
			var preferredTheme = aspenJQ("#preferredTheme option:selected").val();
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'updateDisplaySettings',
				preferredLanguage : preferredLanguage,
				preferredTheme: preferredTheme
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						if (data.message.length > 0){
							//User was logged in, show a message about how to update
							AspenDiscovery.showMessage('Success', data.message, true, true);
						}else{
							//Refresh the page
							// noinspection SillyAssignmentJS
							window.location.href = window.location.href;
						}
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		limitMarkdownField: function (mdeControl, characterLimit) {
			characterCount = mdeControl.value().length

			if (characterCount > characterLimit) {
				mdeControl.value(mdeControl.value().substring(0, characterLimit))
			}
		}
	}

}(AspenDiscovery || {}));

jQuery.validator.addMethod("multiemail", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	var emails = value.split(/[,;]/);
	var valid = true;
	for (var i = 0, limit = emails.length; i < limit; i++) {
		value = emails[i];
		valid = valid && jQuery.validator.methods.email.call(this, value, element);
	}
	return valid;
}, "Invalid email format: please use a comma to separate multiple email addresses.");

jQuery.validator.addMethod("email2", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	var emailToMatch = aspenJQ("#email").val();
	return value === emailToMatch;
}, "Email addresses must match.");

$.validator.addMethod('repeat', function(value, element){
	if(element.id.lastIndexOf('Repeat') === element.id.length - 6) {
		var idOriginal = element.id.slice(0,-6);
		var valueOriginal = aspenJQ('#' + idOriginal).val();
		return value === valueOriginal;
	}
}, "Repeat fields must match.");

jQuery.validator.addMethod("pinConfirmation", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	var pinToMatch = aspenJQ("#pin").val();
	return value === pinToMatch;
}, "PINs must match.");

if (!String.prototype.startsWith) {
	Object.defineProperty(String.prototype, 'startsWith', {
		value: function(search, rawPos) {
			var pos = rawPos > 0 ? rawPos|0 : 0;
			return this.substring(pos, pos + search.length) === search;
		}
	});
}

jQuery.validator.addMethod("strongPassword", function(value, element) {
	// Return true early if field is empty and not required.
	if (value.length === 0 && !$(element).hasClass('required')) {
		return true;
	}

	const uppercaseValid = /[A-Z]/.test(value);
	const lowercaseValid = /[a-z]/.test(value);
	const numberValid = /[0-9]/.test(value);
	const specialValid = /[-_~!@#$%^&*.+]/.test(value);

	$(element).data('pwdUpperValid', uppercaseValid);
	$(element).data('pwdLowerValid', lowercaseValid);
	$(element).data('pwdNumberValid', numberValid);
	$(element).data('pwdSpecialValid', specialValid);

	return uppercaseValid && lowercaseValid && numberValid && specialValid;
}, function(params, element) {
	const errors = [];
	if (!$(element).data('pwdUpperValid')) {
		errors.push('At least one uppercase letter is required.');
	}
	if (!$(element).data('pwdLowerValid')) {
		errors.push('At least one lowercase letter is required.');
	}
	if (!$(element).data('pwdNumberValid')) {
		errors.push('At least one number is required.');
	}
	if (!$(element).data('pwdSpecialValid')) {
		errors.push('At least one special character (-_~!@#$%^&*.+) is required.');
	}

	return '<ul class="password-error-list" style="margin-top:5px; margin-bottom:0; padding-left:1.25em; list-style-type:disc"><li>' + errors.join('</li><li>') + '</li></ul>';
});