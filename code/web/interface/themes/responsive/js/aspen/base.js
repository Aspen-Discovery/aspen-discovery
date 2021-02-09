var AspenDiscovery = (function(){

	// This provides a check to interrupt AjaxFail Calls on page redirects;
	 window.onbeforeunload = function(){
		Globals.LeavingPage = true
	};

	$(document).ready(function(){
		AspenDiscovery.initializeModalDialogs();
		AspenDiscovery.setupFieldSetToggles();
		AspenDiscovery.initCarousels();

		$("#modalDialog").modal({show:false});
		$('[data-toggle="tooltip"]').tooltip();

		$('.panel')
				.on('show.bs.collapse', function () {
					$(this).addClass('active');
				})
				.on('hide.bs.collapse', function () {
					$(this).removeClass('active');
				});

		$(window).on("popstate", function () {
			// if the state is the page you expect, pull the name and load it.
			if (history.state && history.state.page === "MapExhibit") {
				AspenDiscovery.Archive.handleMapClick(history.state.marker, history.state.exhibitPid, history.state.placePid, history.state.label, false, history.state.showTimeline);
			}else if (history.state && history.state.page === "Book") {
				AspenDiscovery.Archive.handleBookClick(history.state.bookPid, history.state.pagePid, history.state.viewer);
			}else if (history.state && history.state.page === "Checkouts") {
				var selector1 = '#checkoutsTab a[href="#' + history.state.source + '"]';
				$(selecto1r).tab('show');
			}else if (history.state && history.state.page === "Holds") {
				var selector2 = '#holdsTab a[href="#' + history.state.source + '"]';
				$(selector2).tab('show');
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
	});

	return {
		buildUrl: function(base, key, value) {
			var sep = (base.indexOf('?') > -1) ? '&' : '?';
			return base + sep + key + '=' + value;
		},

		changePageSize: function(){
			var url = window.location.href;
			if (url.match(/[&?]pageSize=\d+/)) {
				url = url.replace(/pageSize=\d+/, "pageSize=" + $("#pageSize").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&pageSize=" + $("#pageSize").val();
				}else{
					url = url+ "?pageSize=" + $("#pageSize").val();
				}
			}
			window.location.href = url;
		},

		changePage: function(){
			var url = window.location.href;
			if (url.match(/[&?]page=\d+/)) {
				url = url.replace(/page=\d+/, "page=" + $("#page").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&page=" + $("#page").val();
				}else{
					url = url+ "?page=" + $("#page").val();
				}
			}
			window.location.href = url;
			return false;
		},

		changeSort: function(){
			var url = window.location.href;
			if (url.match(/[&?]sort=([A-Za-z_]|%20)+/)) {
				url = url.replace(/sort=([A-Za-z_]|%20)+/, "sort=" + $("#sort").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&sort=" + $("#sort").val();
				}else{
					url = url+ "?sort=" + $("#sort").val();
				}
			}
			window.location.href = url;
			return false;
		},

		closeLightbox: function(callback){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
				if (callback !== undefined){
					modalDialog.on('hidden.bs.modal', function (e) {
						modalDialog.off('hidden.bs.modal');
						callback();
					});
				}
			}
		},

		goToAnchor: function(anchorName) {
			$('html,body').animate({scrollTop: $("#" + anchorName).offset().top},'slow');
		},

		initCarousels: function(carouselClass){
			carouselClass = carouselClass || '.jcarousel';
			var jcarousel = $(carouselClass);
			var wrapper   = jcarousel.parents('.jcarousel-wrapper');
			// console.log('init Carousels called for ', jcarousel);

			jcarousel.on('jcarousel:reload jcarousel:create', function() {

				var Carousel	   = $(this);
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
					$(this).offsetParent().children('.jcarousel-control-prev').hide();
					$(this).offsetParent().children('.jcarousel-control-next').hide();
				}else{
					$(this).offsetParent().children('.jcarousel-control-prev').show();
					$(this).offsetParent().children('.jcarousel-control-next').show();
				}

			})
			.jcarousel({
				wrap: 'circular'
			});

			// These Controls could possibly be replaced with data-api attributes
			$('.jcarousel-control-prev', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '-=1'
					});

			$('.jcarousel-control-next', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '+=1'
					});

			$('.jcarousel-pagination', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.on('jcarouselpagination:active', 'a', function() {
						$(this).addClass('active');
					})
					.on('jcarouselpagination:inactive', 'a', function() {
						$(this).removeClass('active');
					})
					.on('click', function(e) {
						e.preventDefault();
					})
					.jcarouselPagination({
						perPage: 1,
						item: function(page) {
							return '<a href="#' + page + '">' + page + '</a>';
						}
					});

			// If Browse Category js is set, initialize those functions
			if (typeof AspenDiscovery.Browse.initializeBrowseCategory == 'function') {
				AspenDiscovery.Browse.initializeBrowseCategory();
			}
		},

		initializeModalDialogs: function() {
			$(".modalDialogTrigger").each(function(){
				$(this).click(function(){
					var trigger = $(this);
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					var dialogDestination = trigger.attr("href");
					$("#myModalLabel").text(dialogTitle);
					$(".modal-body").html('Loading.').load(dialogDestination);
					$(".extraModalButton").hide();
					$("#modalDialog").modal("show");
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
		//getQueryParameterValue: function (parameterName) {
		//	return location.search.split(parameterName + '=')[1].split('&')[0]
		//},

		replaceQueryParam : function (param, newValue, search) {
			if (typeof search == 'undefined') search = location.search;
			var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
			var query = search.replace(regex, "$1").replace(/&$/, '');
			return newValue ? (query.length > 2 ? query + "&" : "?") + param + "=" + newValue : query;
		},

		getSelectedTitles: function(){
			var selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length === 0){
				var ret = confirm('You have not selected any items, process all items?');
				if (ret === true){
					var titleSelect = $("input.titleSelect");
					titleSelect.attr('checked', 'checked');
					selectedTitles = titleSelect.map(function() {
						return $(this).attr('name') + "=" + $(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
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
			$('legend.collapsible').each(function(){
				$(this).siblings().hide()
				.addClass("collapsed")
				.click(function() {
					$(this).toggleClass("expanded collapsed")
					.siblings().slideToggle();
					return false;
				});
			});

			$('fieldset.fieldset-collapsible').each(function() {
				var collapsible = $(this);
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

		showMessage: function(title, body, autoClose, refreshAfterClose){
			// if autoclose is set as number greater than 1 autoClose will be the custom timeout interval in milliseconds, otherwise
			//	 autoclose is treated as an on/off switch. Default timeout interval of 3 seconds.
			// if refreshAfterClose is set but not autoClose, the page will reload when the box is closed by the user.
			if (autoClose === undefined){
				autoClose = false;
			}
			if (refreshAfterClose === undefined){
				refreshAfterClose = false;
			}
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html('');
			var modalDialog = $("#modalDialog");
			modalDialog.removeClass('image-popup')
			modalDialog.modal('show');
			if (autoClose) {
				setTimeout(function(){
					if (refreshAfterClose) location.reload(true);
					else AspenDiscovery.closeLightbox();
				}, autoClose > 1 ? autoClose : 3000);
			}else if (refreshAfterClose) {
				modalDialog.on('hide.bs.modal', function(){
					location.reload(true)
				})
			}
		},

		showMessageWithButtons: function(title, body, buttons){
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html(buttons);
			$("#modalDialog").modal('show');
		},

		// common loading message for lightbox while waiting for AJAX processes to complete.
		loadingMessage: function() {
			AspenDiscovery.showMessage('Loading', 'Loading, please wait.')
		},

		// common message for when an AJAX call has failed.
		ajaxFail: function() {
			if (!Globals.LeavingPage) AspenDiscovery.showMessage('Request Failed', 'There was an error with this AJAX Request.');
		},

		showElementInPopup: function(title, elementId, buttonsElementId){
			// buttonsElementId is optional
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				AspenDiscovery.closeLightbox(function(){AspenDiscovery.showElementInPopup(title, elementId)});
			}else{
				$(".modal-title").html(title);
				var elementText = $(elementId).html();
				var elementButtons = buttonsElementId ? $(buttonsElementId).html() : '';
				$(".modal-body").html(elementText);
				$('.modal-buttons').html(elementButtons);

				modalDialog.removeClass('image-popup')
				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(){
			var selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			var toggle = $(toggleSelector);
			var value = toggle.prop('checked');
			$(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode === 13){
				$(formToSubmit).submit();
			}
		},

		changeTranslationMode: function(start){
			var url = window.location.href;
			url = url.replace(/[&?](start|stop)TranslationMode=true/, '');
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
			var preference = $("#searchPreferenceLanguage option:selected").val();
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
				selectedLanguage = $("#selected-language option:selected").val();
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
			var termId = $("#termId").val();
			var translationId = $("#translationId").val();
			var translation = $("#translation").val();
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'saveTranslation',
				translationId : translationId,
				translation : translation
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						$(".term_" + termId ).html(translation);
						$(".translation_id_" + translationId ).removeClass('not_translated').addClass("translated");
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
						$("#term_" + termId ).hide();
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		toggleMenu: function() {
			var headerMenu = $('#header-menu');
			var menuButton = $('#menuToggleButton');
			var menuButtonIcon = $('#menuToggleButton > i');
			if (headerMenu.is(':visible')){
				this.closeMenu();
			}else{
				this.closeAccountMenu();
				$('.dropdownMenu').slideUp('slow');
				var menuButtonPosition = menuButton.position();
				headerMenu.css('left', menuButtonPosition.left + menuButton.outerWidth() - headerMenu.outerWidth() + 5);
				headerMenu.css('top', menuButtonPosition.top + menuButton.outerHeight());
				menuButton.addClass('selected');
				headerMenu.slideDown('slow');
				menuButtonIcon.removeClass('fa-bars');
				menuButtonIcon.addClass('fa-times');
			}
			return false;
		},
		closeMenu: function(){
			var headerMenu = $('#header-menu');
			var menuButton = $('#menuToggleButton');
			var menuButtonIcon = $('#menuToggleButton > i');
			headerMenu.slideUp('slow');
			menuButtonIcon.addClass('fa-bars');
			menuButtonIcon.removeClass('fa-times');
			menuButton.removeClass('selected');
		},
		toggleMenuSection: function(categoryName) {
			var menuSectionHeaderIcon = $('#' + categoryName + "MenuSection > i");
			var menuSectionBody = $('#' + categoryName + "MenuSectionBody");
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
		toggleAccountMenu: function() {
			var accountMenu = $('#account-menu');
			var accountMenuButton = $('#accountMenuToggleButton');
			if (accountMenu.is(':visible')){
				this.closeAccountMenu();
			}else{
				this.closeMenu();
				$('.dropdownMenu').slideUp('slow');
				var accountMenuButtonPosition = accountMenuButton.position();
				accountMenu.css('left', accountMenuButtonPosition.left + accountMenuButton.outerWidth() - accountMenu.outerWidth() + 4);
				accountMenu.css('top', accountMenuButtonPosition.top + accountMenuButton.outerHeight());
				accountMenuButton.addClass('selected');
				accountMenu.slideDown('slow');
			}
			return false;
		},
		closeAccountMenu: function(){
			var accountMenu = $('#account-menu');
			var accountMenuButton = $('#accountMenuToggleButton');
			accountMenu.slideUp('slow');
			accountMenuButton.removeClass('selected');
		},
		showCustomMenu: function (menuName) {
			this.closeMenu();
			this.closeAccountMenu();
			var customMenu = $('#' + menuName + '-menu');
			if (customMenu.is(':visible')){
				customMenu.slideUp('slow');
			}else{
				$('.dropdownMenu').slideUp('slow');
				var customMenuTrigger = $('#' + menuName + '-menu-trigger')
				var customMenuTriggerPosition = customMenuTrigger.position();
				customMenu.css('left', customMenuTriggerPosition.left);
				customMenu.css('top', customMenuTriggerPosition.top + customMenuTrigger.outerHeight());
				customMenu.slideDown('slow');
			}

			return false;
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
						$(elementToUpdate).text(data.result.formattedValue);
					} else {
						$(elementToUpdate).text('Unable to format currency');
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
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

/**
 *  Modified from above code, for Aspen Discovery self registration form.
 *
 * Return true, if the value is a valid date, also making this formal check mm-dd-yyyy.
 *
 * @example jQuery.validator.methods.date("01-01-1900")
 * @result true
 *
 * @example jQuery.validator.methods.date("01-13-1990")
 * @result false
 *
 * @example jQuery.validator.methods.date("01.01.1900")
 * @result false
 *
 * @example <input name="pippo" class="{dateAspen:true}" />
 * @desc Declares an optional input element whose value must be a valid date.
 *
 * @name jQuery.validator.methods.dateAspen
 * @type Boolean
 * @cat Plugins/Validate/Methods
 */
jQuery.validator.addMethod(
	"dateAspen",
	function(value, element) {
		var check = false;
		var re = /^\d{1,2}(-)\d{1,2}(-)\d{4}$/;
		if( re.test(value)){
			var adata = value.split('-');
			var mm = parseInt(adata[0],10);
			var dd = parseInt(adata[1],10);
			var aaaa = parseInt(adata[2],10);
			var xdata = new Date(aaaa,mm-1,dd);
			if ( ( xdata.getFullYear() == aaaa ) && ( xdata.getMonth () == mm - 1 ) && ( xdata.getDate() == dd ) )
				check = true;
			else
				check = false;
		} else
			check = false;
		return this.optional(element) || check;
	},
	"Please enter a correct date"
);

$.validator.addMethod('repeat', function(value, element){
	if(element.id.lastIndexOf('Repeat') === element.id.length - 6) {
		var idOriginal = element.id.slice(0,-6);
		var valueOriginal = $('#' + idOriginal).val();
		return value === valueOriginal;
	}
}, "Repeat fields do not match");

if (!String.prototype.startsWith) {
	Object.defineProperty(String.prototype, 'startsWith', {
		value: function(search, rawPos) {
			var pos = rawPos > 0 ? rawPos|0 : 0;
			return this.substring(pos, pos + search.length) === search;
		}
	});
}