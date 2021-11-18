/**
 * Create a title scroller object for display
 * 
 * @param scrollerId - the id of the scroller which will hold the titles
 * @param scrollerShortName
 * @param container - a container to display if any titles are found
 * @param autoScroll - whether or not the selected title should change automatically
 * @param style - The style of the scroller:  vertical, horizontal, single or text-list
 * @return
 */
function TitleScroller(scrollerId, scrollerShortName, container,
		autoScroll, style) {
	this.scrollerTitles = [];
	this.currentScrollerIndex = 0;
	this.numScrollerTitles = 0;
	this.scrollerId = scrollerId;
	this.scrollerShortName = scrollerShortName;
	this.container = container;
	this.scrollInterval = 0;
	this.swipeInterval = 5;
	this.autoScroll = (typeof autoScroll == "undefined") ? false : autoScroll;
	this.style = (typeof style == "undefined") ? 'horizontal' : style;
}

TitleScroller.prototype.loadTitlesFrom = function(jsonUrl) {
	jsonUrl = decodeURIComponent(jsonUrl);
	var scroller = this;
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	scrollerBody.hide();
	$("#titleScrollerSelectedTitle" + this.scrollerShortName+",#titleScrollerSelectedAuthor" + this.scrollerShortName).html("");
	$(".scrollerLoadingContainer").show();
	$.getJSON(jsonUrl, function(data) {
		scroller.loadTitlesFromJsonData(data);
	}).error(function(){
		scrollerBody.html("Unable to load titles. Please try again later.").show();
		$(".scrollerLoadingContainer").hide();
	});
};

TitleScroller.prototype.loadTitlesFromJsonData = function(data) {
	var scroller = this;
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		if (data.error) throw {description:data.error}; // throw exceptions for server error messages.
		if (data.titles.length === 0){
			scrollerBody.html("No titles were found for this list. Please try again later.");
			$('#' + this.scrollerId + " .scrollerBodyContainer .scrollerLoadingContainer").hide();
			scrollerBody.show();
		}else{
			scroller.scrollerTitles = [];
			var i = 0;
			// TODO: try direct assignment instead of loop. don't see the need to loop, other than resetting key. plb
			$.each(data.titles, function(key, val) {
				scroller.scrollerTitles[i++] = val;
			});
			if (scroller.container && data.titles.length > 0) {
				$("#" + scroller.container).fadeIn();
			}
			scroller.numScrollerTitles = data.titles.length;
			if (this.style === 'horizontal' || this.style === 'vertical'){
				// vertical or horizontal scrollers should start in the middle of the data. plb 11-24-2014
				scroller.currentScrollerIndex = data.currentIndex;
			}else{
				scroller.currentScrollerIndex = 0;
			}
			//console.log('current index is : '+scroller.currentScrollerIndex);
			TitleScroller.prototype.updateScroller.call(scroller);
		}
	} catch (err) {
		//alert("error loading titles from data " + err.description);
		if (scrollerBody != null){
			scrollerBody.html("Error loading titles from data : '" + err.description + "' Please try again later.").show();
			$(".scrollerLoadingContainer").hide();
		}
		//else{
		//	//alert("Could not find scroller body for " + this.scrollerId);
		//}
	}
};

TitleScroller.prototype.updateScroller = function() {
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		var scrollerBodyContents = "";
		var curScroller = this;
		if (this.style === 'horizontal'){
			for ( var i in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[i]['formattedTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.width(this.scrollerTitles.length * 300) // use a large enough interval to accomodate medium covers sizes
					.waitForImages(function() {
						TitleScroller.prototype.finishLoadingScroller.call(curScroller);
					});
		}else if (this.style === 'vertical'){
			for ( var j in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[j]['formattedTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.height(this.scrollerTitles.length * 131)
					.waitForImages(function() {
						//console.log(scrollerBody);
						TitleScroller.prototype.finishLoadingScroller.call(curScroller);
					});
		}else if (this.style === 'text-list'){
			for ( var k in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[k]['formattedTextOnlyTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.height(this.scrollerTitles.length * 40); //TODO re-calibrate

			TitleScroller.prototype.finishLoadingScroller.call(curScroller);
		}else{
			this.currentScrollerIndex = 0;
			scrollerBody.html(this.scrollerTitles[this.currentScrollerIndex]['formattedTitle']);
			TitleScroller.prototype.finishLoadingScroller.call(this);
		}
		
	} catch (err) {
		alert("error in updateScroller for scroller " + this.scrollerId + " " + err.description);
		scrollerBody.html("Error loading titles from data: '" + err + "' Please try again later.").show();
		$(".scrollerLoadingContainer").hide();
	}

};

TitleScroller.prototype.finishLoadingScroller = function() {
	$(".scrollerLoadingContainer").hide();
	//var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	//scrollerBody.show();
	$('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody").show();
	TitleScroller.prototype.activateCurrentTitle.call(this);
	var curScroller = this;

	// Whether we are hovering over an individual title or not.
	$('.scrollerTitle').bind('mouseover', {scroller: curScroller}, function() {
		curScroller.hovered = true;
		//console.log('over');
	}).bind('mouseout', {scroller: curScroller}, function() {
		curScroller.hovered = false;
		//console.log('out');
	});

	// Set initial state.
	curScroller.hovered = false;

	if (this.autoScroll && this.scrollInterval === 0){
		this.scrollInterval = setInterval(function() {
			// Only proceed if not hovering.
			if (!curScroller.hovered) {
				curScroller.scrollToRight();
			}
		}, 5000);
	}
};

TitleScroller.prototype.scrollToRight = function() {
	this.currentScrollerIndex++;
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.scrollToLeft = function() {
	this.currentScrollerIndex--;
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

// noinspection JSUnusedGlobalSymbols
TitleScroller.prototype.swipeToRight = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex -= customSwipeInterval; // swipes progress the opposite of scroll buttons
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

// noinspection JSUnusedGlobalSymbols
TitleScroller.prototype.swipeToLeft = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex += customSwipeInterval; // swipes progress the opposite of scroll buttons
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

// noinspection JSUnusedGlobalSymbols
TitleScroller.prototype.swipeUp = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex -= customSwipeInterval;
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

// noinspection JSUnusedGlobalSymbols
TitleScroller.prototype.swipeDown = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex += customSwipeInterval;
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.activateCurrentTitle = function() {
	if (this.numScrollerTitles === 0) {
		return;
	}
	var scrollerTitles = this.scrollerTitles,
			scrollerShortName = this.scrollerShortName,
			currentScrollerIndex = this.currentScrollerIndex,
			scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody"),
			scrollerTitleId = "#scrollerTitle" + this.scrollerShortName + currentScrollerIndex;

	$("#tooltip").hide();  //Make sure to clear the current tooltip if any

	// Update the actual display
	if (this.style === 'horizontal'){
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		if ($(scrollerTitleId).length !== 0) {
				var widthItemsLeft = $(scrollerTitleId).position().left,
						widthCurrent = $(scrollerTitleId).width(),
						containerWidth = $('#' + this.scrollerId + " .scrollerBodyContainer").width(),
						// center the book in the container
						leftPosition = -((widthItemsLeft + widthCurrent / 2) - (containerWidth / 2));
				scrollerBody.animate({
					left : leftPosition + "px"
				}, 400, function() {
					for ( var i in scrollerTitles) {
						var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
						$(scrollerTitleId2).removeClass('selected');
					}
					$(scrollerTitleId).addClass('selected');
				});
		}
	}else if (this.style === 'vertical'){
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		// Scroll Upwards/Downwards
		if ($(scrollerTitleId).length !== 0) {
			//Move top of the current title to the top of the scroller.
			var relativeTopOfElement = $(scrollerTitleId).position().top,
					// center the book in the container
					topPosition = 25 - relativeTopOfElement;
			scrollerBody.animate( {
				top : topPosition + "px"
			}, 400, function() {
				for ( var i in scrollerTitles) {
					var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
					$(scrollerTitleId2).removeClass('selected');
				}
				$(scrollerTitleId).addClass('selected');
			});
		}
	}else if (this.style === 'text-list'){
		// No Action Needed
	}else{
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		scrollerBody.left = "0px";
		scrollerBody.html(this.scrollerTitles[currentScrollerIndex]['formattedTitle']);
	}
};

/*
 * waitForImages 1.1.2
 * -----------------
 * Provides a callback when all images have loaded in your given selector.
 * http://www.alexanderdickson.com/
 *
 *
 * Copyright (c) 2011 Alex Dickson
 * Licensed under the MIT licenses.
 * See website for more info.
 *
 */

(function($) {
	$.fn.waitForImages = function(finishedCallback, eachCallback) {

		eachCallback = eachCallback || function() {};

		if ( ! $.isFunction(finishedCallback) || ! $.isFunction(eachCallback)) {
			throw {
				name: 'invalid_callback',
				message: 'An invalid callback was supplied.'
			};
		}

		var objs = $(this),
				allImgs = objs.find('img'),
				allImgsLength = allImgs.length,
				allImgsLoaded = 0;

		if (allImgsLength === 0) {
			finishedCallback.call(this);
		}else{
			//Don't wait more than 10 seconds for all images to load.
			setTimeout (function() {finishedCallback.call(this); }, 10000);
		}

		return objs.each(function() {
			var obj = $(this),
					imgs = obj.find('img');

			if (imgs.length === 0) {
				return;
			}

			imgs.each(function() {
				var image = new Image,
						imgElement = this;

				image.onload = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded === allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				//Also handle errors and aborts
				image.onabort = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded === allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				image.onerror = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded === allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				image.src = this.src;
			});
		});
	};
})(jQuery);
