/*
  This file is deprecated. Any calls to functions in this script should be replaced.
  The file is retained for development reference only
 */

$(document).ready(function(){
	if($("#searchForm") != null && $("#lookfor") != null){
		$("#lookfor").focus();
	}
	if($("#loginForm") != null){
		$("#username").focus();
	}
	
	var url = location.hash;
	if (url.length > 0) {
		url = url.substr(1);
	} else {
		url = location.href;
	}
	var match = url.match(/([&?]?ui=[^&]+)/);
	if (match) {
		var replace = ((match[1].indexOf('?') != -1) ? '?' : '&') + 'ui=mobile';
		url = url.replace(match[1], replace);
	} else {
		url += ((url.indexOf('?') == -1) ? '?' : '&') + 'ui=mobile';
	}
	url = url.replace('&ui-state=dialog', '');
	$('a.mobile-view').each(function() {
		$(this).attr('href', url);
	});
	collapseFieldsets();
	
});

function collapseFieldsets(){
	//Implement collapsible fieldsets.
	var collapsibles = $('fieldset.fieldset-collapsible');
	if (collapsibles.length > 0) {
		collapsibles.each(function() {
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
	}
}

function lightbox(left, width, top, height){
	
	if (!width) width = 'auto';
	if (!height) height = 'auto';
	
	var loadMsg = $('#lightboxLoading').html();

	$('#popupbox').html('<div class="lightboxLoadingContents"><div class="lightboxLoadingMessage">' + loadMsg + '</div><img src="' + url + '/images/loading_bar.gif" class="lightboxLoadingImage"/></div>');
   
	hideSelects('hidden');

	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('width', width);
	$('#popupbox').css('height', height);
	if (left != undefined && top != undefined){
		$('#popupbox').position({
			'my': 'top left',
			'at': top + " " + left,
			'collision': 'fit'
		});
	}else{
		$('#popupbox').position({
			my: 'center center',
			at: 'center center',
			collision: 'fit',
			of: window
		});
	}
	//$('#popupbox').css('top', top);
	//$('#popupbox').css('left', left);
}

function ajaxLightbox(urlToLoad, parentId, left, width, top, height){
	
	var loadMsg = $('#lightboxLoading').html();

	hideSelects('hidden');

	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');
	
	$('#popupbox').html('<img src="' + path + '/images/loading.gif" /><br />' + loadMsg);
	
	$('#popupbox').show();
	$('#popupbox').width('auto');
	$('#popupbox').height('auto');
	$('#popupbox').position({
		my: 'center center',
		at: 'center center',
		of: window,
		collision: 'fit'
	});
	
	$.get(urlToLoad, function(data) {
		$('#popupbox').html(data);
		
		$('#popupbox').show();
		if (parentId){
			//Automatically position the lightbox over the cursor
			$("#popupbox").position({
				my: 'top right',
				at: 'top right',
				of: parentId,
				collision: "fit"
			});
		}else{
			if (!left) left = '100px';
			if (!top) top = '100px';
			if (!width) width = 'auto';
			if (!height) height = 'auto';
			
			$('#popupbox').css('top', top);
			$('#popupbox').css('left', left);
			$('#popupbox').width(width);
			$('#popupbox').height(height);
			
			$(document).scrollTop(0);
		}
		if ($("#popupboxHeader").length > 0){
			$("#popupbox").draggable({ handle: "#popupboxHeader" });
		}
	}).error(function(){ 
		$('#popupbox').html("There was an error loading this information, please try again later.")
		$('#popupbox').show();
	});
	return false;
}

function showElementInLightbox(title, elementSelector){
	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', '100px');
	$('#popupbox').css('left', '100px');
	$('#popupbox').width('auto');
	$('#popupbox').height('auto');
	
	var lightboxContents = "<div class='header'>" + title + "<a href='#' onclick='hideLightbox();return false;' class='closeIcon'>Close <img src='" + path + "/images/silk/cancel.png' alt='close' /></a></div>";
	lightboxContents += "<div id='popupboxContent' class='content'>" + $(elementSelector).html() + "</div>";
	
	$('#popupbox').html(lightboxContents);
	$('#popupbox').position({
		my: 'center center',
		at: 'center center',
		of: window,
		collision: 'fit'
	});
}

function showHtmlInLightbox(title, htmlSnippet){
	// Find out how far down the screen the user has scrolled.
	var new_top =  document.body.scrollTop;

	// Get the height of the document
	var documentHeight = $(document).height();

	$('#lightbox').show();
	$('#lightbox').css('height', documentHeight + 'px');

	$('#popupbox').show();
	$('#popupbox').css('top', '100px');
	$('#popupbox').css('left', '100px');
	$('#popupbox').width('auto');
	$('#popupbox').height('auto');
	
	var lightboxContents = "<div class='header'>" + title + "<a href='#' onclick='hideLightbox();return false;' class='closeIcon'>Close <img src='" + path + "/images/silk/cancel.png' alt='close' /></a></div>";
	lightboxContents += "<div id='popupboxContent' class='content'>" + htmlSnippet + "</div>";
	
	$('#popupbox').html(lightboxContents);
	$('#popupbox').position({
		my: 'center center',
		at: 'center center',
		of: window,
		collision: 'fit'
	});
}

function hideLightbox(){
	var lightbox = $('#lightbox');
	var popupbox = $('#popupbox');

	hideSelects('visible');
	lightbox.hide();
	popupbox.hide();
}

function hideSelects(visibility)
{
	selects = document.getElementsByTagName('select');
	for(i = 0; i < selects.length; i++) {
		selects[i].style.visibility = visibility;
	}
}

function toggleMenu(elemId){
	var o = document.getElementById(elemId);
	o.style.display = o.style.display == 'block' ? 'none' : 'block';
}

function getElem(id)
{
    if (document.getElementById) {
        return document.getElementById(id);
    } else if (document.all) {
        return document.all[id];
    }
}

function filterAll(element)
{
    // Go through all elements
    var e = getElem('searchForm').elements;
    var len = e.length;
    for (var i = 0; i < len; i++) {
        // Look for filters (specifically checkbox filters)
        if (e[i].name == 'filter[]' && e[i].checked != undefined) {
            e[i].checked = element.checked;
        }
    }
}

function jsEntityEncode(str)
{
    var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    return new_str;
}

/*
 * Function to check if user is logged in. It expects a function as an argument,
 * to which a value of TRUE or FALSE will be supplied, once it comes back from
 * the server
 */
function isLoggedIn(logged_in_function) {
	var url = path + '/AJAX/Home?method=isLoggedIn';
	
	$.get(url, function(response) {
		var logged_in = $(response).find('result').text();
		if (logged_in == "1") {
			logged_in = true;
		}else{
			logged_in = false;
		}
		logged_in_function(logged_in);
	});
	return false; 
}

/*
 * Function to check if user is logged in. Runs Synchronously and returns the
 * value of whether it is logged in or not.
 */
function isLoggedInSync() {
	var url = path + '/AJAX/Home?method=isLoggedIn';
	
	var response = $.ajax({
		url: url,
		async: false,
		cache: false
	}).responseText;
	
	var logged_in = $(response).find('result').text();
	if (logged_in == "1") {
		logged_in = true;
	}else{
		logged_in = false;
	}

	return logged_in; 
}

/* update the sort parameter and redirect the user back to the same page */
function changeSort(newSort){
	// Get the current url
	var currentLocation = window.location.href;
	// Check to see if we already have a sort parameter. .
	if (currentLocation.match(/(sort=[^&]*)/)) {
		// Replace the existing sort with the new sort parameter
		currentLocation = currentLocation.replace(/sort=[^&]*/, 'sort=' + newSort);
	} else {
		// Add the new sort parameter
		if (currentLocation.match(/\?/)) {
			currentLocation += "&sort=" + newSort;
		}else{
			currentLocation += "?sort=" + newSort;
		}
	}
	// Redirect back to this page.
	window.location.href = currentLocation;
}


/* update the sort parameter and redirect the user back to the same page */
function changeAccountSort(newSort){
	// Get the current url
	var currentLocation = window.location.href;
	// Check to see if we already have a sort parameter. .
	if (currentLocation.match(/(accountSort=[^&]*)/)) {
		// Replace the existing sort with the new sort parameter
		currentLocation = currentLocation.replace(/accountSort=[^&]*/, 'accountSort=' + newSort);
	} else {
		// Add the new sort parameter
		if (currentLocation.match(/\?/)) {
			currentLocation += "&accountSort=" + newSort;
		}else{
			currentLocation += "?accountSort=" + newSort;
		}
	}
	// Redirect back to this page.
	window.location.href = currentLocation;
}

function checkAll(){
	
	for (var i=0;i<document.forms[1].elements.length;i++)
	{
		var e=document.forms[1].elements[i];
		var cbName = e.name;
		
		if (document.forms[1].elements[i].type == 'checkbox')
		{
			if (cbName.substring(0,8)== 'selected')
			{
				e.checked = document.forms[1].selectAll.checked;
			}
		}
	}
}
function enableSearchTypes(){
	var searchSource = $("#searchSource");
	if (searchSource.val() != 'genealogy'){
		$("#basicSearchTypes").show();
		$("#genealogySearchTypes").hide();
	}else{
		$("#genealogySearchTypes").show();
		$("#basicSearchTypes").hide();
	}
}

function startSearch(){
	// Stop auto complete since there is a search running already
	$('#lookfor').autocomplete( "disable" );
}


function returnEpub(returnUrl){
  $.getJSON(returnUrl, function (data){
    if (data.success == false){
      alert("Error returning EPUB file\r\n" + data.message);
    }else{
      alert("The file was returned successfully.");
      window.location.reload();
    }
    
  });
}

function cancelEContentHold(cancelUrl){
	$.getJSON(cancelUrl, function (data){
    if (data.result == false){
      alert("Error cancelling hold.\r\n" + data.message);
    }else{
      alert(data.message);
      window.location.reload();
    }
    
  });
}

function reactivateEContentHold(reactivateUrl){
	$.getJSON(reactivateUrl, function (data){
    if (data.error){
      alert("Error reactivating hold.\r\n" + data.error);
    }else{
      alert("The hold was activated successfully.");
      window.location.reload();
    }
    
  });
}

function getOverDriveSummary(){
	$.getJSON(path + '/MyAccount/AJAX?method=getOverDriveSummary', function (data){
		if (data.error){
			// Unable to load overdrive summary
		}else{
			// Load checked out items
			$("#checkedOutItemsOverDrivePlaceholder").html(data.numCheckedOut);
			// Load available holds
			$("#availableHoldsOverDrivePlaceholder").html(data.numAvailableHolds);
			// Load unavailable holds
			$("#unavailableHoldsOverDrivePlaceholder").html(data.numUnavailableHolds);
			// Load wishlist
			$("#wishlistOverDrivePlaceholder").html(data.numWishlistItems);
		}
	});
}

var ajaxCallback = null;
function ajaxLogin(callback){
	ajaxCallback = callback;
	return ajaxLightbox(path + '/MyAccount/AJAX?method=LoginForm');
}

function processAjaxLogin(){
	var username = $("#username").val();
	var password = $("#password").val();
	var rememberMe = $("#rememberMe").val();
	if (!username || !password){
		alert("Please enter both the username and password");
		return false;
	}
	var url = path + "/AJAX/JSON?method=loginUser";
	$.ajax({url: url,
			data: {username: username, password: password, rememberMe: rememberMe},
			success: function(response){
				if (response.result.success == true){
					loggedIn = true;
					// Hide "log in" options and show "log out" options:
					$('.loginOptions').hide();
					$('.logoutOptions').show();
					$('#loginOptions').hide();
					$('#logoutOptions').show();
					$('#myAccountNameLink').html(response.result.name);
					hideLightbox();
					if (ajaxCallback  && typeof(ajaxCallback) === "function"){
						ajaxCallback();
					}
				}else{
					alert("That login information was not recognized.  Please try again.");
				}
			},
			error: function(){
				alert("There was an error processing your login, please try again.");
			},
			dataType: 'json',
			type: 'post' 
	});
	
	return false;
}

function showProcessingIndicator(message){
	if (message != undefined){
		$('#lightboxLoading').html(message);
	}
	lightbox();
}

function searchSubmit(){
	// Stop auto complete since there is a search running already
	$('#lookfor').autocomplete( "disable" );
	
	document.forms.searchForm.action='/Union/Search'
	document.forms.searchForm.submit();
}



function pwdToText(fieldId){
	var elem = document.getElementById(fieldId);
	var input = document.createElement('input');
	input.id = elem.id;
	input.name = elem.name;
	input.value = elem.value;
	input.size = elem.size;
	input.onfocus = elem.onfocus;
	input.onblur = elem.onblur;
	input.className = elem.className;
	if (elem.type == 'text' ){
		input.type = 'password';
	} else {
		input.type = 'text'; 
	}

	elem.parentNode.replaceChild(input, elem);
	return input;
}

function toggleCheckboxes(checkboxSelector, value){
	if (value == undefined){
		$(checkboxSelector).removeAttr('checked');
	}else{
		$(checkboxSelector).attr('checked', value);
	}
}

/**
 * Login function for logging in while the user is adding a rating.
 * @param id
 * @param rating
 * @param module
 */
function ratingLogin(id, rating, module) {
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax( {
		url : url,
		data : {
			username : $('#username').val(),
			password : $('#password').val()
		},
		success : function(response) {
			if (response.result.success == true) {
				// Update the main display to show the user is logged in
				// Hide "log in" options and show "log out" options:
				$('.loginOptions').hide();
				$('.logoutOptions').show();
				$('#myAccountNameLink').html(response.result.name);
				
				// update the rating in the database
				$.get(path + "/" + module + "/" + id + "/Rate?rating=" + rating + "&submit=true", function() {
					window.location.reload(true);
				});
			} else {
				alert("That login was not recognized.  Please try again.");
			}
		},
		dataType : 'json',
		type : 'post'
	});
}

/* Setup autocomplete for search box */
try{
	$(document).ready(
	function() {
		try{
			if ($("#lookfor").length==1){
				$("#lookfor").autocomplete({
					source: function(request, response){
						var url = path + "/Search/AJAX?method=GetAutoSuggestList&type=" + $("#type").val() + "&searchTerm=" +  $("#lookfor").val();
						$.ajax({
							url: url,
							dataType: "json",
							success: function(data){
								response(data);
							}
						});
					},
					position: {
						my: "left top",
						at: "left bottom",
						of: "#lookfor",
						collision: "none"
					},
					minLength: 4,
					delay: 600
				});
			}
		} catch (e) {
			alert("error during autocomplete setup" + e);
		}
	});
} catch (e) {
	alert("error during autocomplete setup" + e);
}

/* This file contains AJAX routines that are shared by multiple VuFind modules.
 */

/*
 * Given a base URL and a set of parameters, use AJAX to send an email; this
 * assumes that a lightbox is already open.
 */
function sendAJAXEmail(url, params, strings){
	$('#popupbox').html('<h3>' + strings.sending + '</h3>');

	$.ajax({
		url: url+'?'+params,
		success: function(data) {
			var value = $(data).find('result');
			if (value) {
					if (value.text() == "Done") {
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
							setTimeout("hideLightbox();", 3000);
					} else {
							var errorDetails = data.details;
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>' +
									(errorDetails ? '<h3>' + errorDetails + '</h3>' : '');
					}
			} else {
					document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>';
			}
		},
		error: function(transaction) {
				document.getElementById('popupbox').innerHTML = strings.failure;
		}
	});
}

/*
 * Send the current URL in an email to a specific address, from a specific
 * address, and including some message text.
 */
function SendURLEmail(to, from, message, strings){
	var url = path + "/Search/AJAX";
	var params = "method=SendEmail&" +
							 "url=" + URLEncode(window.location.href) + "&" +
							 "from=" + encodeURIComponent(from) + "&" +
							 "to=" + encodeURIComponent(to) + "&" +
							 "message=" + encodeURIComponent(message);
	sendAJAXEmail(url, params, strings);
}

function URLEncode(clearString) {
	var output = '';
	var x = 0;
	clearString = clearString.toString();
	var regex = /(^[a-zA-Z0-9_.]*)/;
	while (x < clearString.length) {
			var match = regex.exec(clearString.substr(x));
			if (match != null && match.length > 1 && match[1] != '') {
					output += match[1];
					x += match[1].length;
			} else {
					if (clearString[x] == ' ')
							output += '+';
					else {
							var charCode = clearString.charCodeAt(x);
							var hexVal = charCode.toString(16);
							output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
					}
					x++;
			}
	}
	return output;
}

function sendAJAXSMS(url, params, strings) {
	document.getElementById('popupbox').innerHTML = '<h3>' + strings.sending + '</h3>';

	$.ajax({
		url: url+'?'+params,
		
		success: function(data) {
			var value = $(data).find('result');
			if (value) {
					if (value.text() == "Done") {
							document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
							setTimeout("hideLightbox();", 3000);
					} else {
							document.getElementById('popupbox').innerHTML = strings.failure;
					}
			} else {
					document.getElementById('popupbox').innerHTML = strings.failure;
			}
		},
		error: function() {
				document.getElementById('popupbox').innerHTML = strings.failure;
		}
	});
}

function moreFacets(name)
{
	document.getElementById("more" + name).style.display="none";
	document.getElementById("narrowGroupHidden_" + name).style.display="block";
}

function moreFacetPopup(title, name)
{
	showElementInLightbox(title, "#moreFacetPopup_" + name);
}

function lessFacets(name)
{
	document.getElementById("more" + name).style.display="block";
	document.getElementById("narrowGroupHidden_" + name).style.display="none";
}

function showReviewForm(id, source){
	if (loggedIn){
		if (source == 'VuFind'){
			$('.userreview').slideUp();$('#userreview' + id).slideDown();
		}else{
			$('.userecontentreview').slideUp();$('#userecontentreview' + id).slideDown();
		}
	}else{
		ajaxLogin(function (){
			showReviewForm(id, source);
		});
	}
	return false;
}

function getQuerystringParameters(){
	var vars = [];
	var q = document.URL.split('?')[1];
	if(q != undefined){
		q = q.split('&');
		for(var i = 0; i < q.length; i++){
			var hash = q[i].split('=');
			vars[hash[0]] = hash[1];
		}
	}
	return vars;
}

function createWidgetFromList(listId){
	//prompt for the widget to add to 
	ajaxLightbox(path + '/Admin/AJAX?method=getAddToWidgetForm&source=list&id=' + listId);
	return false;
}
function createWidgetFromSearch(searchId){
	//prompt for the widget to add to
	ajaxLightbox(path + '/Admin/AJAX?method=getAddToWidgetForm&source=search&id=' + searchId);
	return false;
}

function changeDropDownFacet(dropDownId, facetLabel){
	var selectedOption = $("#" + dropDownId + " :selected");
	var destination = selectedOption.data("destination");
	var value = selectedOption.data("label");
	window.location.href = destination;
}

function toggleSection(sectionName){
	$("." + sectionName).toggle();
	$("#holdings-section-" + sectionName).toggleClass('collapsed expanded');
}

function getWikipediaArticle(articleName){
	var url = path + "/Author/AJAX?method=getWikipediaData&articleName=" + articleName;
	$.getJSON(url, function(data){
		if (data.success)
			$("#wikipedia_placeholder").html(data.formatted_article);
	});
}
