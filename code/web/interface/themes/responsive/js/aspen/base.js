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
				let selector = '#checkoutsTab a[href="#' + history.state.source + '"]';
				$(selector).tab('show');
			}else if (history.state && history.state.page === "Holds") {
				let selector = '#holdsTab a[href="#' + history.state.source + '"]';
				$(selector).tab('show');
			}else if (history.state && history.state.page === "ReadingHistory") {
				AspenDiscovery.Account.loadReadingHistory(history.state.selectedUser, history.state.sort, history.state.pageNumber, history.state.showCovers, history.state.filter);
			}
		});
	});

	return {
		buildUrl: function(base, key, value) {
			let sep = (base.indexOf('?') > -1) ? '&' : '?';
			return base + sep + key + '=' + value;
		},

		changePageSize: function(){
			let url = window.location.href;
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

		closeLightbox: function(callback){
			let modalDialog = $("#modalDialog");
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
			let wrapper   = jcarousel.parents('.jcarousel-wrapper');
			// console.log('init Carousels called for ', jcarousel);

			jcarousel.on('jcarousel:reload jcarousel:create', function() {

				var Carousel	   = $(this);
				var width		  = Carousel.innerWidth();
				var liTags		 = Carousel.find('li');
				if (liTags == null ||liTags.length === 0){
					return;
				}
				var leftMargin	 = +liTags.css('margin-left').replace('px', ''),
						rightMargin	= +liTags.css('margin-right').replace('px', ''),
						numCategories  = Carousel.jcarousel('items').length || 1,
						numItemsToShow = 1;

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
					var trigger = $(this),
							dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title"),
							dialogDestination = trigger.attr("href");
					$("#myModalLabel").text(dialogTitle);
					$(".modal-body").html('Loading.').load(dialogDestination);
					$(".extraModalButton").hide();
					$("#modalDialog").modal("show");
					return false;
				});
			});
		},

		getQuerystringParameters: function(){
			let vars = [];
			let q = location.search.substr(1);
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
			let regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
			let query = search.replace(regex, "$1").replace(/&$/, '');
			return newValue ? (query.length > 2 ? query + "&" : "?") + param + "=" + newValue : query;
		},

		getSelectedTitles: function(){
			let selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length === 0){
				let ret = confirm('You have not selected any items, process all items?');
				if (ret === true){
					let titleSelect = $("input.titleSelect");
					titleSelect.attr('checked', 'checked');
					selectedTitles = titleSelect.map(function() {
						return $(this).attr('name') + "=" + $(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
		},

		pwdToText: function(fieldId){
			let elem = document.getElementById(fieldId);
			let input = document.createElement('input');
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
				let collapsible = $(this);
				let legend = collapsible.find('legend:first');
				legend.addClass('fieldset-collapsible-label').bind('click', {collapsible: collapsible}, function(event) {
					let collapsible = event.data.collapsible;
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
			let modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				AspenDiscovery.closeLightbox(function(){AspenDiscovery.showElementInPopup(title, elementId)});
			}else{
				$(".modal-title").html(title);
				let elementText = $(elementId).html();
				let elementButtons = buttonsElementId ? $(buttonsElementId).html() : '';
				$(".modal-body").html(elementText);
				$('.modal-buttons').html(elementButtons);

				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(){
			let selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			let toggle = $(toggleSelector);
			let value = toggle.prop('checked');
			$(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode === 13){
				$(formToSubmit).submit();
			}
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
			let preference = $("#searchPreferenceLanguage option:selected").val();
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
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

		setLanguage: function() {
			//Update the user interface with the selected language
			let newLanguage = $("#selected-language option:selected").val();
			let curLocation = window.location.href;
			let newParam = 'myLang=' + newLanguage;
			if (curLocation.indexOf(newParam) === -1){
				let newLocation = curLocation.replace(new RegExp('([?&])myLang=(.*?)(?:&|$)'), '$1' + newParam);
				if (newLocation === curLocation){
					newLocation = AspenDiscovery.buildUrl(curLocation, 'myLang', newLanguage);
				}
				window.location.href = newLocation;
			}

			return false;
		},

		showTranslateForm: function(termId) {
			let url = Globals.path + "/AJAX/JSON?method=getTranslationForm&termId=" + termId;
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		saveTranslation: function(){
			let termId = $("#termId").val();
			let translationId = $("#translationId").val();
			let translation = $("#translation").val();
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
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
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
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
		}
	}

}(AspenDiscovery || {}));

jQuery.validator.addMethod("multiemail", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	let emails = value.split(/[,;]/);
	let valid = true;
	for (let i = 0, limit = emails.length; i < limit; i++) {
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
			let check = false;
			let re = /^\d{1,2}(-)\d{1,2}(-)\d{4}$/;
			if( re.test(value)){
				let adata = value.split('-');
				let mm = parseInt(adata[0],10);
				let dd = parseInt(adata[1],10);
				let aaaa = parseInt(adata[2],10);
				let xdata = new Date(aaaa,mm-1,dd);
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
	if(element.id.lastIndexOf('Repeat') == element.id.length - 6) {
		idOriginal = element.id.slice(0,-6);
		valueOriginal = $('#' + idOriginal).val();
		if (value == valueOriginal) {
			return true;
		} else {
			return false;
		}
	}
}, "Repeat fields do not match");