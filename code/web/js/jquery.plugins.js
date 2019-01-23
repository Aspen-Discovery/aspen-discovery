/* Contains all plugins that are in use within VuFind-Plus */

/*!
 * jQuery idleTimer plugin
 * version 0.9.100511
 * by Paul Irish. 
 *   http://github.com/paulirish/yui-misc/tree/
 * MIT license
 
 * adapted from YUI idle timer by nzakas:
 *   http://github.com/nzakas/yui-misc/
*/ 
/*
 * Copyright (c) 2009 Nicholas C. Zakas
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


 // API available in <= v0.8
 /*******************************
 
 // idleTimer() takes an optional argument that defines the idle timeout
 // timeout is in milliseconds; defaults to 30000
 $.idleTimer(10000);


 $(document).bind("idle.idleTimer", function(){
    // function you want to fire when the user goes idle
 });


 $(document).bind("active.idleTimer", function(){
  // function you want to fire when the user becomes active again
 });

 // pass the string 'destroy' to stop the timer
 $.idleTimer('destroy');
 
 // you can query if the user is idle or not with data()
 $.data(document,'idleTimer');  // 'idle'  or 'active'

 // you can get time elapsed since user when idle/active
 $.idleTimer('getElapsedTime'); // time since state change in ms
 
 ********/
 
 
 
 // API available in >= v0.9
 /*************************
 
 // bind to specific elements, allows for multiple timer instances
 $(elem).idleTimer(timeout|'destroy'|'getElapsedTime');
 $.data(elem,'idleTimer');  // 'idle'  or 'active'
 
 // if you're using the old $.idleTimer api, you should not do $(document).idleTimer(...)
 
 // element bound timers will only watch for events inside of them.
 // you may just want page-level activity, in which case you may set up
 //   your timers on document, document.documentElement, and document.body
 
 
 ********/

(function($){

$.idleTimer = function(newTimeout, elem){
  
    // defaults that are to be stored as instance props on the elem
    
    var idle    = false,        //indicates if the user is idle
        enabled = true,        //indicates if the idle timer is enabled
        timeout = 30000,        //the amount of time (ms) before the user is considered idle
        events  = 'mousemove keydown DOMMouseScroll mousewheel mousedown'; // activity is one of these events
        
    
    elem = elem || document;
    
    
        
    /* (intentionally not documented)
     * Toggles the idle state and fires an appropriate event.
     * @return {void}
     */
    var toggleIdleState = function(myelem){
    
        // curse you, mozilla setTimeout lateness bug!
        if (typeof myelem == 'number') myelem = undefined;
    
        var obj = $.data(myelem || elem,'idleTimerObj');
        
        //toggle the state
        obj.idle = !obj.idle;
        
        // reset timeout counter
        obj.olddate = +new Date;
        
        //fire appropriate event
        
        // create a custom event, but first, store the new state on the element
        // and then append that string to a namespace
        var event = jQuery.Event( $.data(elem,'idleTimer', obj.idle ? "idle" : "active" )  + '.idleTimer'   );
        
        // we dont want this to bubble
        //event.stopPropagation();
        $(elem).trigger(event);            
    },

    /**
     * Stops the idle timer. This removes appropriate event handlers
     * and cancels any pending timeouts.
     * @return {void}
     * @method stop
     * @static
     */         
    stop = function(elem){
    
        var obj = $.data(elem,'idleTimerObj');
        
        //set to disabled
        obj.enabled = false;
        
        //clear any pending timeouts
        clearTimeout(obj.tId);
        
        //detach the event handlers
        $(elem).unbind('.idleTimer');
    },
    
    
    /* (intentionally not documented)
     * Handles a user event indicating that the user isn't idle.
     * @param {Event} event A DOM2-normalized event object.
     * @return {void}
     */
    handleUserEvent = function(){
    
        var obj = $.data(this,'idleTimerObj');
        
        //clear any existing timeout
        clearTimeout(obj.tId);
        
        
        
        //if the idle timer is enabled
        if (obj.enabled){
        
          
            //if it's idle, that means the user is no longer idle
            if (obj.idle){
                toggleIdleState(this);           
            } 
        
            //set a new timeout
            obj.tId = setTimeout(toggleIdleState, obj.timeout);
            
        }    
     };
    
      
    /**
     * Starts the idle timer. This adds appropriate event handlers
     * and starts the first timeout.
     * @param {int} newTimeout (Optional) A new value for the timeout period in ms.
     * @return {void}
     * @method $.idleTimer
     * @static
     */ 
    
    
    var obj = $.data(elem,'idleTimerObj') || new function(){};
    
    obj.olddate = obj.olddate || +new Date;
    
    //assign a new timeout if necessary
    if (typeof newTimeout == "number"){
        timeout = newTimeout;
    } else if (newTimeout === 'destroy') {
        stop(elem);
        return this;  
    } else if (newTimeout === 'getElapsedTime'){
        return (+new Date) - obj.olddate;
    }
    
    //assign appropriate event handlers
    $(elem).bind($.trim((events+' ').split(' ').join('.idleTimer ')),handleUserEvent);
    
    
    obj.idle    = idle;
    obj.enabled = enabled;
    obj.timeout = timeout;
    
    
    //set a timeout to toggle state
    obj.tId = setTimeout(toggleIdleState, obj.timeout);
    
    // assume the user is active for the first x seconds.
    $.data(elem,'idleTimer',"active");
    
    // store our instance on the object
    $.data(elem,'idleTimerObj',obj);  
    

    
}; // end of $.idleTimer()


// v0.9 API for defining multiple timers.
$.fn.idleTimer = function(newTimeout){
  
  this[0] && $.idleTimer(newTimeout,this[0]);
  
  return this;
}
    

})(jQuery);


/// <reference path="http://code.jquery.com/jquery-1.4.1-vsdoc.js" />
/*
* Print Element Plugin 1.2
*
* Copyright (c) 2010 Erik Zaadi
*
* Inspired by PrintArea (http://plugins.jquery.com/project/PrintArea) and
* http://stackoverflow.com/questions/472951/how-do-i-print-an-iframe-from-javascript-in-safari-chrome
*
*  Home Page : http://projects.erikzaadi/jQueryPlugins/jQuery.printElement 
*  Issues (bug reporting) : http://github.com/erikzaadi/jQueryPlugins/issues/labels/printElement
*  jQuery plugin page : http://plugins.jquery.com/project/printElement 
*  
*  Thanks to David B (http://github.com/ungenio) and icgJohn (http://www.blogger.com/profile/11881116857076484100)
*  For their great contributions!
* 
* Dual licensed under the MIT and GPL licenses:
*   http://www.opensource.org/licenses/mit-license.php
*   http://www.gnu.org/licenses/gpl.html
*   
*   Note, Iframe Printing is not supported in Opera and Chrome 3.0, a popup window will be shown instead
*/
;(function(g){function k(c){c&&c.printPage?c.printPage():setTimeout(function(){k(c)},50)}function l(c){c=a(c);a(":checked",c).each(function(){this.setAttribute("checked","checked")});a("input[type='text']",c).each(function(){this.setAttribute("value",a(this).val())});a("select",c).each(function(){var b=a(this);a("option",b).each(function(){b.val()==a(this).val()&&this.setAttribute("selected","selected")})});a("textarea",c).each(function(){var b=a(this).attr("value");if(a.browser.b&&this.firstChild)this.firstChild.textContent=
b;else this.innerHTML=b});return a("<div></div>").append(c.clone()).html()}function m(c,b){var i=a(c);c=l(c);var d=[];d.push("<html><head><title>"+b.pageTitle+"</title>");if(b.overrideElementCSS){if(b.overrideElementCSS.length>0)for(var f=0;f<b.overrideElementCSS.length;f++){var e=b.overrideElementCSS[f];typeof e=="string"?d.push('<link type="text/css" rel="stylesheet" href="'+e+'" >'):d.push('<link type="text/css" rel="stylesheet" href="'+e.href+'" media="'+e.media+'" >')}}else a("link",j).filter(function(){return a(this).attr("rel").toLowerCase()==
"stylesheet"}).each(function(){d.push('<link type="text/css" rel="stylesheet" href="'+a(this).attr("href")+'" media="'+a(this).attr("media")+'" >')});d.push('<base href="'+(g.location.protocol+"//"+g.location.hostname+(g.location.port?":"+g.location.port:"")+g.location.pathname)+'" />');d.push('</head><body style="'+b.printBodyOptions.styleToAdd+'" class="'+b.printBodyOptions.classNameToAdd+'">');d.push('<div class="'+i.attr("class")+'">'+c+"</div>");d.push('<script type="text/javascript">function printPage(){focus();print();'+
(!a.browser.opera&&!b.leaveOpen&&b.printMode.toLowerCase()=="popup"?"close();":"")+"}<\/script>");d.push("</body></html>");return d.join("")}var j=g.document,a=g.jQuery;a.fn.printElement=function(c){var b=a.extend({},a.fn.printElement.defaults,c);if(b.printMode=="iframe")if(a.browser.opera||/chrome/.test(navigator.userAgent.toLowerCase()))b.printMode="popup";a("[id^='printElement_']").remove();return this.each(function(){var i=a.a?a.extend({},b,a(this).data()):b,d=a(this);d=m(d,i);var f=null,e=null;
if(i.printMode.toLowerCase()=="popup"){f=g.open("about:blank","printElementWindow","width=650,height=440,scrollbars=yes");e=f.document}else{f="printElement_"+Math.round(Math.random()*99999).toString();var h=j.createElement("IFRAME");a(h).attr({style:i.iframeElementOptions.styleToAdd,id:f,className:i.iframeElementOptions.classNameToAdd,frameBorder:0,scrolling:"no",src:"about:blank"});j.body.appendChild(h);e=h.contentWindow||h.contentDocument;if(e.document)e=e.document;h=j.frames?j.frames[f]:j.getElementById(f);
f=h.contentWindow||h}focus();e.open();e.write(d);e.close();k(f)})};a.fn.printElement.defaults={printMode:"iframe",pageTitle:"",overrideElementCSS:null,printBodyOptions:{styleToAdd:"padding:10px;margin:10px;",classNameToAdd:""},leaveOpen:false,iframeElementOptions:{styleToAdd:"border:none;position:absolute;width:0px;height:0px;bottom:0px;left:0px;",classNameToAdd:""}};a.fn.printElement.cssElement={href:"",media:""}})(window);

/**
 * jQuery Plugin to obtain touch gestures from iPhone, iPod Touch and iPad, should also work with Android mobile phones (not tested yet!)
 * Common usage: wipe images (left and right to show the previous or next image)
 * 
 * @author Andreas Waltl, netCU Internetagentur (http://www.netcu.de)
 * @version 1.1.1 (9th December 2010) - fix bug (older IE's had problems)
 * @version 1.1 (1st September 2010) - support wipe up and wipe down
 * @version 1.0 (15th July 2010)
 */
(function($) { 
   $.fn.touchwipe = function(settings) {
     var config = {
    		min_move_x: 20,
    		min_move_y: 20,
 			wipeLeft: function() { },
 			wipeRight: function() { },
 			wipeUp: function() { },
 			wipeDown: function() { },
			preventDefaultEvents: true
	 };
     
     if (settings) $.extend(config, settings);
 
     this.each(function() {
    	 var startX;
    	 var startY;
		 var isMoving = false;

    	 function cancelTouch() {
    		 this.removeEventListener('touchmove', onTouchMove);
    		 startX = null;
    		 isMoving = false;
    	 }	
    	 
    	 function onTouchMove(e) {
    		 if(config.preventDefaultEvents) {
    			 e.preventDefault();
    		 }
    		 if(isMoving) {
	    		 var x = e.touches[0].pageX;
	    		 var y = e.touches[0].pageY;
	    		 var dx = startX - x;
	    		 var dy = startY - y;
	    		 if(Math.abs(dx) >= config.min_move_x) {
	    			cancelTouch();
	    			if(dx > 0) {
	    				config.wipeLeft();
	    			}
	    			else {
	    				config.wipeRight();
	    			}
	    		 }
	    		 else if(Math.abs(dy) >= config.min_move_y) {
		    			cancelTouch();
		    			if(dy > 0) {
		    				config.wipeDown();
		    			}
		    			else {
		    				config.wipeUp();
		    			}
		    		 }
    		 }
    	 }
    	 
    	 function onTouchStart(e)
    	 {
    		 if (e.touches.length == 1) {
    			 startX = e.touches[0].pageX;
    			 startY = e.touches[0].pageY;
    			 isMoving = true;
    			 this.addEventListener('touchmove', onTouchMove, false);
    		 }
    	 }    	 
    	 if ('ontouchstart' in document.documentElement) {
    		 this.addEventListener('touchstart', onTouchStart, false);
    	 }
     });
 
     return this;
   };
 
 })(jQuery);

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

;(function($) {
    $.fn.waitForImages = function(finishedCallback, eachCallback) {

        eachCallback = eachCallback || function() {};

        if ( ! $.isFunction(finishedCallback) || ! $.isFunction(eachCallback)) {
            throw {
                name: 'invalid_callback',
                message: 'An invalid callback was supplied.'
            };
        };

        var objs = $(this),
            allImgs = objs.find('img'),
            allImgsLength = allImgs.length,
            allImgsLoaded = 0;
        
        if (allImgsLength == 0) {
            finishedCallback.call(this);
        }else{
        	//Don't wait more than 10 seconds for all images to load.
        	setTimeout (function() {finishedCallback.call(this); }, 10000);
        }

        return objs.each(function() {
            var obj = $(this),
                imgs = obj.find('img');

            if (imgs.length == 0) {
                return true;
            };

            imgs.each(function() {
                var image = new Image,
                    imgElement = this;

                image.onload = function() {
                    allImgsLoaded++;
                    eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
                    if (allImgsLoaded == allImgsLength) {
                        finishedCallback.call(obj[0]);
                        return false;
                    };
                };
                
                //Also handle errors and aborts
                image.onabort = function() {
                    allImgsLoaded++;
                    eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
                    if (allImgsLoaded == allImgsLength) {
                        finishedCallback.call(obj[0]);
                        return false;
                    };
                };
                
                image.onerror = function() {
                    allImgsLoaded++;
                    eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
                    if (allImgsLoaded == allImgsLength) {
                        finishedCallback.call(obj[0]);
                        return false;
                    };
                };

                image.src = this.src;
            });
        });
    };
})(jQuery);

/*
 * jQuery blockUI plugin
 * Version 2.23 (21-JUN-2009)
 * @requires jQuery v1.2.3 or later
 *
 * Examples at: http://malsup.com/jquery/block/
 * Copyright (c) 2007-2008 M. Alsup
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Thanks to Amir-Hossein Sobhi for some excellent contributions!
 */

;(function($) {

if (/1\.(0|1|2)\.(0|1|2)/.test($.fn.jquery) || /^1.1/.test($.fn.jquery)) {
    alert('blockUI requires jQuery v1.2.3 or later!  You are using v' + $.fn.jquery);
    return;
}

$.fn._fadeIn = $.fn.fadeIn;

// this bit is to ensure we don't call setExpression when we shouldn't (with extra muscle to handle
// retarded userAgent strings on Vista)
var mode = document.documentMode || 0;
var setExpr = $.browser.msie && (($.browser.version < 8 && !mode) || mode < 8);
var ie6 = $.browser.msie && /MSIE 6.0/.test(navigator.userAgent) && !mode;

// global $ methods for blocking/unblocking the entire page
$.blockUI   = function(opts) { install(window, opts); };
$.unblockUI = function(opts) { remove(window, opts); };

// convenience method for quick growl-like notifications  (http://www.google.com/search?q=growl)
$.growlUI = function(title, message, timeout, onClose) {
	var $m = $('<div class="growlUI"></div>');
	if (title) $m.append('<h1>'+title+'</h1>');
	if (message) $m.append('<h2>'+message+'</h2>');
	if (timeout == undefined) timeout = 3000;
    $.blockUI({
		message: $m, fadeIn: 700, fadeOut: 1000, centerY: false,
		timeout: timeout, showOverlay: false,
		onUnblock: onClose, 
		css: $.blockUI.defaults.growlCSS
    });
};

// plugin method for blocking element content
$.fn.block = function(opts) {
    return this.unblock({ fadeOut: 0 }).each(function() {
        if ($.css(this,'position') == 'static')
            this.style.position = 'relative';
        if ($.browser.msie)
            this.style.zoom = 1; // force 'hasLayout'
        install(this, opts);
    });
};

// plugin method for unblocking element content
$.fn.unblock = function(opts) {
    return this.each(function() {
        remove(this, opts);
    });
};

$.blockUI.version = 2.23; // 2nd generation blocking at no extra cost!

// override these in your code to change the default behavior and style
$.blockUI.defaults = {
    // message displayed when blocking (use null for no message)
    message:  '<h1>Please wait...</h1>',

    // styles for the message when blocking; if you wish to disable
    // these and use an external stylesheet then do this in your code:
    // $.blockUI.defaults.css = {};
    css: {
        padding:        0,
        margin:         0,
        width:          '30%',
        top:            '40%',
        left:           '35%',
        textAlign:      'center',
        color:          '#000',
        border:         '3px solid #aaa',
        backgroundColor:'#fff',
        cursor:         'wait'
    },

    // styles for the overlay
    overlayCSS:  {
        backgroundColor: '#000',
        opacity:          0.6,
        cursor:          'wait'
    },

	// styles applied when using $.growlUI
	growlCSS: {
		width:    '350px',
		top:      '10px',
		left:     '',
		right:    '10px',
	    border:   'none',
	    padding:  '5px',
	    opacity:   0.6,
		cursor:    null,
	    color:    '#fff',
	    backgroundColor: '#000',
	    '-webkit-border-radius': '10px',
	    '-moz-border-radius':    '10px'
	},
	
	// IE issues: 'about:blank' fails on HTTPS and javascript:false is s-l-o-w
	// (hat tip to Jorge H. N. de Vasconcelos)
	iframeSrc: /^https/i.test(window.location.href || '') ? 'javascript:false' : 'about:blank',

	// force usage of iframe in non-IE browsers (handy for blocking applets)
	forceIframe: false,

    // z-index for the blocking overlay
    baseZ: 1000,

    // set these to true to have the message automatically centered
    centerX: true, // <-- only effects element blocking (page block controlled via css above)
    centerY: true,

    // allow body element to be stetched in ie6; this makes blocking look better
    // on "short" pages.  disable if you wish to prevent changes to the body height
    allowBodyStretch: true,

	// enable if you want key and mouse events to be disabled for content that is blocked
	bindEvents: true,

    // be default blockUI will supress tab navigation from leaving blocking content
    // (if bindEvents is true)
    constrainTabKey: true,

    // fadeIn time in millis; set to 0 to disable fadeIn on block
    fadeIn:  200,

    // fadeOut time in millis; set to 0 to disable fadeOut on unblock
    fadeOut:  400,

	// time in millis to wait before auto-unblocking; set to 0 to disable auto-unblock
	timeout: 0,

	// disable if you don't want to show the overlay
	showOverlay: true,

    // if true, focus will be placed in the first available input field when
    // page blocking
    focusInput: true,

    // suppresses the use of overlay styles on FF/Linux (due to performance issues with opacity)
    applyPlatformOpacityRules: true,

    // callback method invoked when unblocking has completed; the callback is
    // passed the element that has been unblocked (which is the window object for page
    // blocks) and the options that were passed to the unblock call:
    //     onUnblock(element, options)
    onUnblock: null,

    // don't ask; if you really must know: http://groups.google.com/group/jquery-en/browse_thread/thread/36640a8730503595/2f6a79a77a78e493#2f6a79a77a78e493
    quirksmodeOffsetHack: 4
};

// private data and functions follow...

var pageBlock = null;
var pageBlockEls = [];

function install(el, opts) {
    var full = (el == window);
    var msg = opts && opts.message !== undefined ? opts.message : undefined;
    opts = $.extend({}, $.blockUI.defaults, opts || {});
    opts.overlayCSS = $.extend({}, $.blockUI.defaults.overlayCSS, opts.overlayCSS || {});
    var css = $.extend({}, $.blockUI.defaults.css, opts.css || {});
    msg = msg === undefined ? opts.message : msg;

    // remove the current block (if there is one)
    if (full && pageBlock)
        remove(window, {fadeOut:0});

    // if an existing element is being used as the blocking content then we capture
    // its current place in the DOM (and current display style) so we can restore
    // it when we unblock
    if (msg && typeof msg != 'string' && (msg.parentNode || msg.jquery)) {
        var node = msg.jquery ? msg[0] : msg;
        var data = {};
        $(el).data('blockUI.history', data);
        data.el = node;
        data.parent = node.parentNode;
        data.display = node.style.display;
        data.position = node.style.position;
		if (data.parent)
			data.parent.removeChild(node);
    }

    var z = opts.baseZ;

    // blockUI uses 3 layers for blocking, for simplicity they are all used on every platform;
    // layer1 is the iframe layer which is used to supress bleed through of underlying content
    // layer2 is the overlay layer which has opacity and a wait cursor (by default)
    // layer3 is the message content that is displayed while blocking

    var lyr1 = ($.browser.msie || opts.forceIframe) 
    	? $('<iframe class="blockUI" style="z-index:'+ (z++) +';display:none;border:none;margin:0;padding:0;position:absolute;width:100%;height:100%;top:0;left:0" src="'+opts.iframeSrc+'"></iframe>')
        : $('<div class="blockUI" style="display:none"></div>');
    var lyr2 = $('<div class="blockUI blockOverlay" style="z-index:'+ (z++) +';display:none;border:none;margin:0;padding:0;width:100%;height:100%;top:0;left:0"></div>');
    var lyr3 = full ? $('<div class="blockUI blockMsg blockPage" style="z-index:'+z+';display:none;position:fixed"></div>')
                    : $('<div class="blockUI blockMsg blockElement" style="z-index:'+z+';display:none;position:absolute"></div>');

    // if we have a message, style it
    if (msg)
        lyr3.css(css);

    // style the overlay
    if (!opts.applyPlatformOpacityRules || !($.browser.mozilla && /Linux/.test(navigator.platform)))
        lyr2.css(opts.overlayCSS);
    lyr2.css('position', full ? 'fixed' : 'absolute');

    // make iframe layer transparent in IE
    if ($.browser.msie || opts.forceIframe)
        lyr1.css('opacity',0.0);

    $([lyr1[0],lyr2[0],lyr3[0]]).appendTo(full ? 'body' : el);

    // ie7 must use absolute positioning in quirks mode and to account for activex issues (when scrolling)
    var expr = setExpr && (!$.boxModel || $('object,embed', full ? null : el).length > 0);
    if (ie6 || expr) {
        // give body 100% height
        if (full && opts.allowBodyStretch && $.boxModel)
            $('html,body').css('height','100%');

        // fix ie6 issue when blocked element has a border width
        if ((ie6 || !$.boxModel) && !full) {
            var t = sz(el,'borderTopWidth'), l = sz(el,'borderLeftWidth');
            var fixT = t ? '(0 - '+t+')' : 0;
            var fixL = l ? '(0 - '+l+')' : 0;
        }

        // simulate fixed position
        $.each([lyr1,lyr2,lyr3], function(i,o) {
            var s = o[0].style;
            s.position = 'absolute';
            if (i < 2) {
                full ? s.setExpression('height','Math.max(document.body.scrollHeight, document.body.offsetHeight) - (jQuery.boxModel?0:'+opts.quirksmodeOffsetHack+') + "px"')
                     : s.setExpression('height','this.parentNode.offsetHeight + "px"');
                full ? s.setExpression('width','jQuery.boxModel && document.documentElement.clientWidth || document.body.clientWidth + "px"')
                     : s.setExpression('width','this.parentNode.offsetWidth + "px"');
                if (fixL) s.setExpression('left', fixL);
                if (fixT) s.setExpression('top', fixT);
            }
            else if (opts.centerY) {
                if (full) s.setExpression('top','(document.documentElement.clientHeight || document.body.clientHeight) / 2 - (this.offsetHeight / 2) + (blah = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"');
                s.marginTop = 0;
            }
			else if (!opts.centerY && full) {
				var top = (opts.css && opts.css.top) ? parseInt(opts.css.top) : 0;
				var expression = '((document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + '+top+') + "px"';
                s.setExpression('top',expression);
			}
        });
    }

    // show the message
	if (msg) {
		lyr3.append(msg);
		if (msg.jquery || msg.nodeType)
			$(msg).show();
	}

	if (($.browser.msie || opts.forceIframe) && opts.showOverlay)
		lyr1.show(); // opacity is zero
	if (opts.fadeIn) {
		if (opts.showOverlay)
			lyr2._fadeIn(opts.fadeIn);
		if (msg)
			lyr3.fadeIn(opts.fadeIn);
	}
	else {
		if (opts.showOverlay)
			lyr2.show();
		if (msg)
			lyr3.show();
	}

    // bind key and mouse events
    bind(1, el, opts);

    if (full) {
        pageBlock = lyr3[0];
        pageBlockEls = $(':input:enabled:visible',pageBlock);
        if (opts.focusInput)
            setTimeout(focus, 20);
    }
    else
        center(lyr3[0], opts.centerX, opts.centerY);

	if (opts.timeout) {
		// auto-unblock
		var to = setTimeout(function() {
			full ? $.unblockUI(opts) : $(el).unblock(opts);
		}, opts.timeout);
		$(el).data('blockUI.timeout', to);
	}
};

// remove the block
function remove(el, opts) {
    var full = el == window;
	var $el = $(el);
    var data = $el.data('blockUI.history');
	var to = $el.data('blockUI.timeout');
	if (to) {
		clearTimeout(to);
		$el.removeData('blockUI.timeout');
	}
    opts = $.extend({}, $.blockUI.defaults, opts || {});
    bind(0, el, opts); // unbind events
    var els = full ? $('body').children().filter('.blockUI') : $('.blockUI', el);

    if (full)
        pageBlock = pageBlockEls = null;

    if (opts.fadeOut) {
        els.fadeOut(opts.fadeOut);
        setTimeout(function() { reset(els,data,opts,el); }, opts.fadeOut);
    }
    else
        reset(els, data, opts, el);
};

// move blocking element back into the DOM where it started
function reset(els,data,opts,el) {
    els.each(function(i,o) {
        // remove via DOM calls so we don't lose event handlers
        if (this.parentNode)
            this.parentNode.removeChild(this);
    });

    if (data && data.el) {
        data.el.style.display = data.display;
        data.el.style.position = data.position;
		if (data.parent)
			data.parent.appendChild(data.el);
        $(data.el).removeData('blockUI.history');
    }

    if (typeof opts.onUnblock == 'function')
        opts.onUnblock(el,opts);
};

// bind/unbind the handler
function bind(b, el, opts) {
    var full = el == window, $el = $(el);

    // don't bother unbinding if there is nothing to unbind
    if (!b && (full && !pageBlock || !full && !$el.data('blockUI.isBlocked')))
        return;
    if (!full)
        $el.data('blockUI.isBlocked', b);

	// don't bind events when overlay is not in use or if bindEvents is false
    if (!opts.bindEvents || (b && !opts.showOverlay)) 
		return;

    // bind anchors and inputs for mouse and key events
    var events = 'mousedown mouseup keydown keypress';
    b ? $(document).bind(events, opts, handler) : $(document).unbind(events, handler);

// former impl...
//    var $e = $('a,:input');
//    b ? $e.bind(events, opts, handler) : $e.unbind(events, handler);
};

// event handler to suppress keyboard/mouse events when blocking
function handler(e) {
    // allow tab navigation (conditionally)
    if (e.keyCode && e.keyCode == 9) {
        if (pageBlock && e.data.constrainTabKey) {
            var els = pageBlockEls;
            var fwd = !e.shiftKey && e.target == els[els.length-1];
            var back = e.shiftKey && e.target == els[0];
            if (fwd || back) {
                setTimeout(function(){focus(back)},10);
                return false;
            }
        }
    }
    // allow events within the message content
    if ($(e.target).parents('div.blockMsg').length > 0)
        return true;

    // allow events for content that is not being blocked
    return $(e.target).parents().children().filter('div.blockUI').length == 0;
};

function focus(back) {
    if (!pageBlockEls)
        return;
    var e = pageBlockEls[back===true ? pageBlockEls.length-1 : 0];
    if (e)
        e.focus();
};

function center(el, x, y) {
    var p = el.parentNode, s = el.style;
    var l = ((p.offsetWidth - el.offsetWidth)/2) - sz(p,'borderLeftWidth');
    var t = ((p.offsetHeight - el.offsetHeight)/2) - sz(p,'borderTopWidth');
    if (x) s.left = l > 0 ? (l+'px') : '0';
    if (y) s.top  = t > 0 ? (t+'px') : '0';
};

function sz(el, p) {
    return parseInt($.css(el,p))||0;
};

})(jQuery);

//JavaScript Document
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

//TODO: Rating JS, move to a VuFind file.
//copyright 2008 Jarrett Vance
//http://jvance.com
$.fn.rater = function(options) {
	var opts = $.extend( {}, $.fn.rater.defaults, options);
	return this.each(function() {
		var $this = $(this);
		var $on = $this.find('.ui-rater-starsOn');
		var $off = $this.find('.ui-rater-starsOff');
		
		if (opts.size == undefined) opts.size = $off.height();
		if (opts.rating == undefined) { 
			opts.rating = $on.width() / $off.width();
		}else{
			$on.width($off.width() * (opts.rating / opts.ratings.length));
		}
		if (opts.id == undefined) opts.id = $this.attr('id');

	
		if (!$this.hasClass('ui-rater-bindings-done')) {
			$this.addClass('ui-rater-bindings-done');
			$off.mousemove(function(e) {
				var left = e.clientX - $off.offset().left;
				var width = $off.width() - ($off.width() - left);
				width = Math.min(Math.ceil(width / (opts.size / opts.step)) * opts.size / opts.step, opts.size * opts.ratings.length)
				$on.width(width);
				var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				$this.attr('title', 'Click to Rate "' + (opts.ratings[r - 1] == undefined ? r : opts.ratings[r - 1]) + '"') ;
			}).hover(function(e) { $on.addClass('ui-rater-starsHover'); }, function(e) {
				$on.removeClass('ui-rater-starsHover'); $on.width(opts.rating * opts.size);
			}).click(function(e) {
				var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				$.fn.rater.rate($this, opts, r);
			}).css('cursor', 'pointer'); $on.css('cursor', 'pointer');
		}
		
	});
};



$.fn.rater.defaults = {
	postHref : location.href,
	ratings: ['Hated It', "Didn't Like It", 'Liked It', 'Really Liked It', 'Loved It'],
	step : 1
};

$.fn.rater.rate = function($this, opts, rating) {
	var $on = $this.find('.ui-rater-starsOn');
	var $off = $this.find('.ui-rater-starsOff');
	if (loggedIn){
		$off.fadeTo(600, 0.4, function() {
			$.ajax( {
				url : opts.postHref,
				type : "POST",
				data : 'id=' + opts.id + '&rating=' + rating,
				complete : function(req) {
					if (req.status == 200) { // success
						opts.rating = parseFloat(req.responseText);
						$off.unbind('click').unbind('mousemove').unbind('mouseenter').unbind('mouseleave');
						$off.css('cursor', 'default'); $on.css('cursor', 'default');
						$off.fadeTo(600, 0.1, function() {
							$on.removeClass('ui-rater-starsHover').width(opts.rating * opts.size);
							$off.fadeTo(500, 1);
							$on.addClass('userRated');
							$this.attr('title', 'Your rating: ' + rating.toFixed(1));
							if ($this.data('show_review') == true){
								doRatingReview(rating, opts.module, opts.recordId);
							}
						});
					} else { // failure
						alert(req.responseText);
						$off.fadeTo(2200, 1);
					}
				}
			});
		});
	}else{
		ajaxLogin(function(){
			$.fn.rater.rate($this, opts, rating);
		});
	}
};

function doRatingReview(rating, module, id){
	if (rating <= 2){
		msg = "We're sorry you didn't like this title.  Would you like to add a review explaining why to help other users?";
	}else{
		msg = "We're glad you liked this title.  Would you like to add a review explaining why to help other users?";
	}
	if (confirm(msg)){
		var reviewForm;
		reviewForm = $("#userreview" + id);
		reviewForm.find(".rateTitle").hide();
		reviewForm.show();
	}
}
/*
 * jQuery Tooltip plugin 1.3
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 * http://docs.jquery.com/Plugins/Tooltip
 *
 * Copyright (c) 2006 - 2008 Jörn Zaefferer
 *
 * $Id: jquery.tooltip.js 5741 2008-06-21 15:22:16Z joern.zaefferer $
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */;(function($){var helper={},current,title,tID,IE=$.browser.msie&&/MSIE\s(5\.5|6\.)/.test(navigator.userAgent),track=false;$.tooltip={blocked:false,defaults:{delay:200,fade:false,showURL:true,extraClass:"",top:15,left:15,id:"tooltip"},block:function(){$.tooltip.blocked=!$.tooltip.blocked;}};$.fn.extend({tooltip:function(settings){settings=$.extend({},$.tooltip.defaults,settings);createHelper(settings);return this.each(function(){$.data(this,"tooltip",settings);this.tOpacity=helper.parent.css("opacity");this.tooltipText=this.title;$(this).removeAttr("title");this.alt="";}).mouseover(save).mouseout(hide).click(hide);},fixPNG:IE?function(){return this.each(function(){var image=$(this).css('backgroundImage');if(image.match(/^url\(["']?(.*\.png)["']?\)$/i)){image=RegExp.$1;$(this).css({'backgroundImage':'none','filter':"progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='"+image+"')"}).each(function(){var position=$(this).css('position');if(position!='absolute'&&position!='relative')$(this).css('position','relative');});}});}:function(){return this;},unfixPNG:IE?function(){return this.each(function(){$(this).css({'filter':'',backgroundImage:''});});}:function(){return this;},hideWhenEmpty:function(){return this.each(function(){$(this)[$(this).html()?"show":"hide"]();});},url:function(){return this.attr('href')||this.attr('src');}});function createHelper(settings){if(helper.parent)return;helper.parent=$('<div id="'+settings.id+'"><h3></h3><div class="body"></div><div class="url"></div></div>').appendTo(document.body).hide();if($.fn.bgiframe)helper.parent.bgiframe();helper.title=$('h3',helper.parent);helper.body=$('div.body',helper.parent);helper.url=$('div.url',helper.parent);}function settings(element){return $.data(element,"tooltip");}function handle(event){if(settings(this).delay)tID=setTimeout(show,settings(this).delay);else
show();track=!!settings(this).track;$(document.body).bind('mousemove',update);update(event);}function save(){if($.tooltip.blocked||this==current||(!this.tooltipText&&!settings(this).bodyHandler))return;current=this;title=this.tooltipText;if(settings(this).bodyHandler){helper.title.hide();var bodyContent=settings(this).bodyHandler.call(this);if(bodyContent.nodeType||bodyContent.jquery){helper.body.empty().append(bodyContent)}else{helper.body.html(bodyContent);}helper.body.show();}else if(settings(this).showBody){var parts=title.split(settings(this).showBody);helper.title.html(parts.shift()).show();helper.body.empty();for(var i=0,part;(part=parts[i]);i++){if(i>0)helper.body.append("<br/>");helper.body.append(part);}helper.body.hideWhenEmpty();}else{helper.title.html(title).show();helper.body.hide();}if(settings(this).showURL&&$(this).url())helper.url.html($(this).url().replace('http://','')).show();else
helper.url.hide();helper.parent.addClass(settings(this).extraClass);if(settings(this).fixPNG)helper.parent.fixPNG();handle.apply(this,arguments);}function show(){tID=null;if((!IE||!$.fn.bgiframe)&&settings(current).fade){if(helper.parent.is(":animated"))helper.parent.stop().show().fadeTo(settings(current).fade,current.tOpacity);else
helper.parent.is(':visible')?helper.parent.fadeTo(settings(current).fade,current.tOpacity):helper.parent.fadeIn(settings(current).fade);}else{helper.parent.show();}update();}function update(event){if($.tooltip.blocked)return;if(event&&event.target.tagName=="OPTION"){return;}if(!track&&helper.parent.is(":visible")){$(document.body).unbind('mousemove',update)}if(current==null){$(document.body).unbind('mousemove',update);return;}helper.parent.removeClass("viewport-right").removeClass("viewport-bottom");var left=helper.parent[0].offsetLeft;var top=helper.parent[0].offsetTop;if(event){left=event.pageX+settings(current).left;top=event.pageY+settings(current).top;var right='auto';if(settings(current).positionLeft){right=$(window).width()-left;left='auto';}helper.parent.css({left:left,right:right,top:top});}var v=viewport(),h=helper.parent[0];if(v.x+v.cx<h.offsetLeft+h.offsetWidth){left-=h.offsetWidth+20+settings(current).left;helper.parent.css({left:left+'px'}).addClass("viewport-right");}if(v.y+v.cy<h.offsetTop+h.offsetHeight){top-=h.offsetHeight+20+settings(current).top;helper.parent.css({top:top+'px'}).addClass("viewport-bottom");}}function viewport(){return{x:$(window).scrollLeft(),y:$(window).scrollTop(),cx:$(window).width(),cy:$(window).height()};}function hide(event){if($.tooltip.blocked)return;if(tID)clearTimeout(tID);current=null;var tsettings=settings(this);function complete(){helper.parent.removeClass(tsettings.extraClass).hide().css("opacity","");}if((!IE||!$.fn.bgiframe)&&tsettings.fade){if(helper.parent.is(':animated'))helper.parent.stop().fadeTo(tsettings.fade,0,complete);else
helper.parent.stop().fadeOut(tsettings.fade,complete);}else
complete();if(settings(this).fixPNG)helper.parent.unfixPNG();}})(jQuery);
 
 /* Copyright (c) 2006 Brandon Aaron (http://brandonaaron.net)
  * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
  * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
  *
  * $LastChangedDate: 2007-06-20 03:23:36 +0200 (Mi, 20 Jun 2007) $
  * $Rev: 2110 $
  *
  * Version 2.1
  */

 (function($){

 /**
  * The bgiframe is chainable and applies the iframe hack to get 
  * around zIndex issues in IE6. It will only apply itself in IE 
  * and adds a class to the iframe called 'bgiframe'. The iframe
  * is appeneded as the first child of the matched element(s) 
  * with a tabIndex and zIndex of -1.
  * 
  * By default the plugin will take borders, sized with pixel units,
  * into account. If a different unit is used for the border's width,
  * then you will need to use the top and left settings as explained below.
  *
  * NOTICE: This plugin has been reported to cause perfromance problems
  * when used on elements that change properties (like width, height and
  * opacity) a lot in IE6. Most of these problems have been caused by 
  * the expressions used to calculate the elements width, height and 
  * borders. Some have reported it is due to the opacity filter. All 
  * these settings can be changed if needed as explained below.
  *
  * @example $('div').bgiframe();
  * @before <div><p>Paragraph</p></div>
  * @result <div><iframe class="bgiframe".../><p>Paragraph</p></div>
  *
  * @param Map settings Optional settings to configure the iframe.
  * @option String|Number top The iframe must be offset to the top
  * 		by the width of the top border. This should be a negative 
  *      number representing the border-top-width. If a number is 
  * 		is used here, pixels will be assumed. Otherwise, be sure
  *		to specify a unit. An expression could also be used. 
  * 		By default the value is "auto" which will use an expression 
  * 		to get the border-top-width if it is in pixels.
  * @option String|Number left The iframe must be offset to the left
  * 		by the width of the left border. This should be a negative 
  *      number representing the border-left-width. If a number is 
  * 		is used here, pixels will be assumed. Otherwise, be sure
  *		to specify a unit. An expression could also be used. 
  * 		By default the value is "auto" which will use an expression 
  * 		to get the border-left-width if it is in pixels.
  * @option String|Number width This is the width of the iframe. If
  *		a number is used here, pixels will be assume. Otherwise, be sure
  * 		to specify a unit. An experssion could also be used.
  *		By default the value is "auto" which will use an experssion
  * 		to get the offsetWidth.
  * @option String|Number height This is the height of the iframe. If
  *		a number is used here, pixels will be assume. Otherwise, be sure
  * 		to specify a unit. An experssion could also be used.
  *		By default the value is "auto" which will use an experssion
  * 		to get the offsetHeight.
  * @option Boolean opacity This is a boolean representing whether or not
  * 		to use opacity. If set to true, the opacity of 0 is applied. If
  *		set to false, the opacity filter is not applied. Default: true.
  * @option String src This setting is provided so that one could change 
  *		the src of the iframe to whatever they need.
  *		Default: "javascript:false;"
  *
  * @name bgiframe
  * @type jQuery
  * @cat Plugins/bgiframe
  * @author Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
  */
 $.fn.bgIframe = $.fn.bgiframe = function(s) {
 	// This is only for IE6
 	if ( $.browser.msie && parseInt($.browser.version) <= 6 ) {
 		s = $.extend({
 			top     : 'auto', // auto == .currentStyle.borderTopWidth
 			left    : 'auto', // auto == .currentStyle.borderLeftWidth
 			width   : 'auto', // auto == offsetWidth
 			height  : 'auto', // auto == offsetHeight
 			opacity : true,
 			src     : 'javascript:false;'
 		}, s || {});
 		var prop = function(n){return n&&n.constructor==Number?n+'px':n;},
 		    html = '<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+
 		               'style="display:block;position:absolute;z-index:-1;'+
 			               (s.opacity !== false?'filter:Alpha(Opacity=\'0\');':'')+
 					       'top:'+(s.top=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+\'px\')':prop(s.top))+';'+
 					       'left:'+(s.left=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+\'px\')':prop(s.left))+';'+
 					       'width:'+(s.width=='auto'?'expression(this.parentNode.offsetWidth+\'px\')':prop(s.width))+';'+
 					       'height:'+(s.height=='auto'?'expression(this.parentNode.offsetHeight+\'px\')':prop(s.height))+';'+
 					'"/>';
 		return this.each(function() {
 			if ( $('> iframe.bgiframe', this).length == 0 )
 				this.insertBefore( document.createElement(html), this.firstChild );
 		});
 	}
 	return this;
 };

 // Add browser.version if it doesn't exist
 if (!$.browser.version)
 	$.browser.version = navigator.userAgent.toLowerCase().match(/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/)[1];

 })(jQuery);
 
 /*!
  * jQuery Form Plugin
  * version: 3.24 (26-DEC-2012)
  * @requires jQuery v1.5 or later
  *
  * Examples and documentation at: http://malsup.com/jquery/form/
  * Project repository: https://github.com/malsup/form
  * Dual licensed under the MIT and GPL licenses:
  *    http://malsup.github.com/mit-license.txt
  *    http://malsup.github.com/gpl-license-v2.txt
  */
 /*global ActiveXObject alert */
 ;(function($) {
 "use strict";

 /*
     Usage Note:
     -----------
     Do not use both ajaxSubmit and ajaxForm on the same form.  These
     functions are mutually exclusive.  Use ajaxSubmit if you want
     to bind your own submit handler to the form.  For example,

     $(document).ready(function() {
         $('#myForm').on('submit', function(e) {
             e.preventDefault(); // <-- important
             $(this).ajaxSubmit({
                 target: '#output'
             });
         });
     });

     Use ajaxForm when you want the plugin to manage all the event binding
     for you.  For example,

     $(document).ready(function() {
         $('#myForm').ajaxForm({
             target: '#output'
         });
     });
     
     You can also use ajaxForm with delegation (requires jQuery v1.7+), so the
     form does not have to exist when you invoke ajaxForm:

     $('#myForm').ajaxForm({
         delegation: true,
         target: '#output'
     });
     
     When using ajaxForm, the ajaxSubmit function will be invoked for you
     at the appropriate time.
 */

 /**
  * Feature detection
  */
 var feature = {};
 feature.fileapi = $("<input type='file'/>").get(0).files !== undefined;
 feature.formdata = window.FormData !== undefined;

 /**
  * ajaxSubmit() provides a mechanism for immediately submitting
  * an HTML form using AJAX.
  */
 $.fn.ajaxSubmit = function(options) {
     /*jshint scripturl:true */

     // fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
     if (!this.length) {
         log('ajaxSubmit: skipping submit process - no element selected');
         return this;
     }
     
     var method, action, url, $form = this;

     if (typeof options == 'function') {
         options = { success: options };
     }

     method = this.attr('method');
     action = this.attr('action');
     url = (typeof action === 'string') ? $.trim(action) : '';
     url = url || window.location.href || '';
     if (url) {
         // clean url (don't include hash vaue)
         url = (url.match(/^([^#]+)/)||[])[1];
     }

     options = $.extend(true, {
         url:  url,
         success: $.ajaxSettings.success,
         type: method || 'GET',
         iframeSrc: /^https/i.test(window.location.href || '') ? 'javascript:false' : 'about:blank'
     }, options);

     // hook for manipulating the form data before it is extracted;
     // convenient for use with rich editors like tinyMCE or FCKEditor
     var veto = {};
     this.trigger('form-pre-serialize', [this, options, veto]);
     if (veto.veto) {
         log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');
         return this;
     }

     // provide opportunity to alter form data before it is serialized
     if (options.beforeSerialize && options.beforeSerialize(this, options) === false) {
         log('ajaxSubmit: submit aborted via beforeSerialize callback');
         return this;
     }

     var traditional = options.traditional;
     if ( traditional === undefined ) {
         traditional = $.ajaxSettings.traditional;
     }
     
     var elements = [];
     var qx, a = this.formToArray(options.semantic, elements);
     if (options.data) {
         options.extraData = options.data;
         qx = $.param(options.data, traditional);
     }

     // give pre-submit callback an opportunity to abort the submit
     if (options.beforeSubmit && options.beforeSubmit(a, this, options) === false) {
         log('ajaxSubmit: submit aborted via beforeSubmit callback');
         return this;
     }

     // fire vetoable 'validate' event
     this.trigger('form-submit-validate', [a, this, options, veto]);
     if (veto.veto) {
         log('ajaxSubmit: submit vetoed via form-submit-validate trigger');
         return this;
     }

     var q = $.param(a, traditional);
     if (qx) {
         q = ( q ? (q + '&' + qx) : qx );
     }    
     if (options.type.toUpperCase() == 'GET') {
         options.url += (options.url.indexOf('?') >= 0 ? '&' : '?') + q;
         options.data = null;  // data is null for 'get'
     }
     else {
         options.data = q; // data is the query string for 'post'
     }

     var callbacks = [];
     if (options.resetForm) {
         callbacks.push(function() { $form.resetForm(); });
     }
     if (options.clearForm) {
         callbacks.push(function() { $form.clearForm(options.includeHidden); });
     }

     // perform a load on the target only if dataType is not provided
     if (!options.dataType && options.target) {
         var oldSuccess = options.success || function(){};
         callbacks.push(function(data) {
             var fn = options.replaceTarget ? 'replaceWith' : 'html';
             $(options.target)[fn](data).each(oldSuccess, arguments);
         });
     }
     else if (options.success) {
         callbacks.push(options.success);
     }

     options.success = function(data, status, xhr) { // jQuery 1.4+ passes xhr as 3rd arg
         var context = options.context || this ;    // jQuery 1.4+ supports scope context 
         for (var i=0, max=callbacks.length; i < max; i++) {
             callbacks[i].apply(context, [data, status, xhr || $form, $form]);
         }
     };

     // are there files to upload?

     // [value] (issue #113), also see comment:
     // https://github.com/malsup/form/commit/588306aedba1de01388032d5f42a60159eea9228#commitcomment-2180219
     var fileInputs = $('input[type=file]:enabled[value!=""]', this); 

     var hasFileInputs = fileInputs.length > 0;
     var mp = 'multipart/form-data';
     var multipart = ($form.attr('enctype') == mp || $form.attr('encoding') == mp);

     var fileAPI = feature.fileapi && feature.formdata;
     log("fileAPI :" + fileAPI);
     var shouldUseFrame = (hasFileInputs || multipart) && !fileAPI;

     var jqxhr;

     // options.iframe allows user to force iframe mode
     // 06-NOV-09: now defaulting to iframe mode if file input is detected
     if (options.iframe !== false && (options.iframe || shouldUseFrame)) {
         // hack to fix Safari hang (thanks to Tim Molendijk for this)
         // see:  http://groups.google.com/group/jquery-dev/browse_thread/thread/36395b7ab510dd5d
         if (options.closeKeepAlive) {
             $.get(options.closeKeepAlive, function() {
                 jqxhr = fileUploadIframe(a);
             });
         }
         else {
             jqxhr = fileUploadIframe(a);
         }
     }
     else if ((hasFileInputs || multipart) && fileAPI) {
         jqxhr = fileUploadXhr(a);
     }
     else {
         jqxhr = $.ajax(options);
     }

     $form.removeData('jqxhr').data('jqxhr', jqxhr);

     // clear element array
     for (var k=0; k < elements.length; k++)
         elements[k] = null;

     // fire 'notify' event
     this.trigger('form-submit-notify', [this, options]);
     return this;

     // utility fn for deep serialization
     function deepSerialize(extraData){
         var serialized = $.param(extraData).split('&');
         var len = serialized.length;
         var result = {};
         var i, part;
         for (i=0; i < len; i++) {
             // #252; undo param space replacement
             serialized[i] = serialized[i].replace(/\+/g,' ');
             part = serialized[i].split('=');
             result[decodeURIComponent(part[0])] = decodeURIComponent(part[1]);
         }
         return result;
     }

      // XMLHttpRequest Level 2 file uploads (big hat tip to francois2metz)
     function fileUploadXhr(a) {
         var formdata = new FormData();

         for (var i=0; i < a.length; i++) {
             formdata.append(a[i].name, a[i].value);
         }

         if (options.extraData) {
             var serializedData = deepSerialize(options.extraData);
             for (var p in serializedData)
                 if (serializedData.hasOwnProperty(p))
                     formdata.append(p, serializedData[p]);
         }

         options.data = null;

         var s = $.extend(true, {}, $.ajaxSettings, options, {
             contentType: false,
             processData: false,
             cache: false,
             type: method || 'POST'
         });
         
         if (options.uploadProgress) {
             // workaround because jqXHR does not expose upload property
             s.xhr = function() {
                 var xhr = jQuery.ajaxSettings.xhr();
                 if (xhr.upload) {
                     xhr.upload.onprogress = function(event) {
                         var percent = 0;
                         var position = event.loaded || event.position; /*event.position is deprecated*/
                         var total = event.total;
                         if (event.lengthComputable) {
                             percent = Math.ceil(position / total * 100);
                         }
                         options.uploadProgress(event, position, total, percent);
                     };
                 }
                 return xhr;
             };
         }

         s.data = null;
             var beforeSend = s.beforeSend;
             s.beforeSend = function(xhr, o) {
                 o.data = formdata;
                 if(beforeSend)
                     beforeSend.call(this, xhr, o);
         };
         return $.ajax(s);
     }

     // private function for handling file uploads (hat tip to YAHOO!)
     function fileUploadIframe(a) {
         var form = $form[0], el, i, s, g, id, $io, io, xhr, sub, n, timedOut, timeoutHandle;
         var useProp = !!$.fn.prop;
         var deferred = $.Deferred();

         if ($('[name=submit],[id=submit]', form).length) {
             // if there is an input with a name or id of 'submit' then we won't be
             // able to invoke the submit fn on the form (at least not x-browser)
             alert('Error: Form elements must not have name or id of "submit".');
             deferred.reject();
             return deferred;
         }
         
         if (a) {
             // ensure that every serialized input is still enabled
             for (i=0; i < elements.length; i++) {
                 el = $(elements[i]);
                 if ( useProp )
                     el.prop('disabled', false);
                 else
                     el.removeAttr('disabled');
             }
         }

         s = $.extend(true, {}, $.ajaxSettings, options);
         s.context = s.context || s;
         id = 'jqFormIO' + (new Date().getTime());
         if (s.iframeTarget) {
             $io = $(s.iframeTarget);
             n = $io.attr('name');
             if (!n)
                  $io.attr('name', id);
             else
                 id = n;
         }
         else {
             $io = $('<iframe name="' + id + '" src="'+ s.iframeSrc +'" />');
             $io.css({ position: 'absolute', top: '-1000px', left: '-1000px' });
         }
         io = $io[0];


         xhr = { // mock object
             aborted: 0,
             responseText: null,
             responseXML: null,
             status: 0,
             statusText: 'n/a',
             getAllResponseHeaders: function() {},
             getResponseHeader: function() {},
             setRequestHeader: function() {},
             abort: function(status) {
                 var e = (status === 'timeout' ? 'timeout' : 'aborted');
                 log('aborting upload... ' + e);
                 this.aborted = 1;

                 try { // #214, #257
                     if (io.contentWindow.document.execCommand) {
                         io.contentWindow.document.execCommand('Stop');
                     }
                 } 
                 catch(ignore) {}

                 $io.attr('src', s.iframeSrc); // abort op in progress
                 xhr.error = e;
                 if (s.error)
                     s.error.call(s.context, xhr, e, status);
                 if (g)
                     $.event.trigger("ajaxError", [xhr, s, e]);
                 if (s.complete)
                     s.complete.call(s.context, xhr, e);
             }
         };

         g = s.global;
         // trigger ajax global events so that activity/block indicators work like normal
         if (g && 0 === $.active++) {
             $.event.trigger("ajaxStart");
         }
         if (g) {
             $.event.trigger("ajaxSend", [xhr, s]);
         }

         if (s.beforeSend && s.beforeSend.call(s.context, xhr, s) === false) {
             if (s.global) {
                 $.active--;
             }
             deferred.reject();
             return deferred;
         }
         if (xhr.aborted) {
             deferred.reject();
             return deferred;
         }

         // add submitting element to data if we know it
         sub = form.clk;
         if (sub) {
             n = sub.name;
             if (n && !sub.disabled) {
                 s.extraData = s.extraData || {};
                 s.extraData[n] = sub.value;
                 if (sub.type == "image") {
                     s.extraData[n+'.x'] = form.clk_x;
                     s.extraData[n+'.y'] = form.clk_y;
                 }
             }
         }
         
         var CLIENT_TIMEOUT_ABORT = 1;
         var SERVER_ABORT = 2;

         function getDoc(frame) {
             var doc = frame.contentWindow ? frame.contentWindow.document : frame.contentDocument ? frame.contentDocument : frame.document;
             return doc;
         }
         
         // Rails CSRF hack (thanks to Yvan Barthelemy)
         var csrf_token = $('meta[name=csrf-token]').attr('content');
         var csrf_param = $('meta[name=csrf-param]').attr('content');
         if (csrf_param && csrf_token) {
             s.extraData = s.extraData || {};
             s.extraData[csrf_param] = csrf_token;
         }

         // take a breath so that pending repaints get some cpu time before the upload starts
         function doSubmit() {
             // make sure form attrs are set
             var t = $form.attr('target'), a = $form.attr('action');

             // update form attrs in IE friendly way
             form.setAttribute('target',id);
             if (!method) {
                 form.setAttribute('method', 'POST');
             }
             if (a != s.url) {
                 form.setAttribute('action', s.url);
             }

             // ie borks in some cases when setting encoding
             if (! s.skipEncodingOverride && (!method || /post/i.test(method))) {
                 $form.attr({
                     encoding: 'multipart/form-data',
                     enctype:  'multipart/form-data'
                 });
             }

             // support timout
             if (s.timeout) {
                 timeoutHandle = setTimeout(function() { timedOut = true; cb(CLIENT_TIMEOUT_ABORT); }, s.timeout);
             }
             
             // look for server aborts
             function checkState() {
                 try {
                     var state = getDoc(io).readyState;
                     log('state = ' + state);
                     if (state && state.toLowerCase() == 'uninitialized')
                         setTimeout(checkState,50);
                 }
                 catch(e) {
                     log('Server abort: ' , e, ' (', e.name, ')');
                     cb(SERVER_ABORT);
                     if (timeoutHandle)
                         clearTimeout(timeoutHandle);
                     timeoutHandle = undefined;
                 }
             }

             // add "extra" data to form if provided in options
             var extraInputs = [];
             try {
                 if (s.extraData) {
                     for (var n in s.extraData) {
                         if (s.extraData.hasOwnProperty(n)) {
                            // if using the $.param format that allows for multiple values with the same name
                            if($.isPlainObject(s.extraData[n]) && s.extraData[n].hasOwnProperty('name') && s.extraData[n].hasOwnProperty('value')) {
                                extraInputs.push(
                                $('<input type="hidden" name="'+s.extraData[n].name+'">').val(s.extraData[n].value)
                                    .appendTo(form)[0]);
                            } else {
                                extraInputs.push(
                                $('<input type="hidden" name="'+n+'">').val(s.extraData[n])
                                    .appendTo(form)[0]);
                            }
                         }
                     }
                 }

                 if (!s.iframeTarget) {
                     // add iframe to doc and submit the form
                     $io.appendTo('body');
                     if (io.attachEvent)
                         io.attachEvent('onload', cb);
                     else
                         io.addEventListener('load', cb, false);
                 }
                 setTimeout(checkState,15);
                 form.submit();
             }
             finally {
                 // reset attrs and remove "extra" input elements
                 form.setAttribute('action',a);
                 if(t) {
                     form.setAttribute('target', t);
                 } else {
                     $form.removeAttr('target');
                 }
                 $(extraInputs).remove();
             }
         }

         if (s.forceSync) {
             doSubmit();
         }
         else {
             setTimeout(doSubmit, 10); // this lets dom updates render
         }

         var data, doc, domCheckCount = 50, callbackProcessed;

         function cb(e) {
             if (xhr.aborted || callbackProcessed) {
                 return;
             }
             try {
                 doc = getDoc(io);
             }
             catch(ex) {
                 log('cannot access response document: ', ex);
                 e = SERVER_ABORT;
             }
             if (e === CLIENT_TIMEOUT_ABORT && xhr) {
                 xhr.abort('timeout');
                 deferred.reject(xhr, 'timeout');
                 return;
             }
             else if (e == SERVER_ABORT && xhr) {
                 xhr.abort('server abort');
                 deferred.reject(xhr, 'error', 'server abort');
                 return;
             }

             if (!doc || doc.location.href == s.iframeSrc) {
                 // response not received yet
                 if (!timedOut)
                     return;
             }
             if (io.detachEvent)
                 io.detachEvent('onload', cb);
             else    
                 io.removeEventListener('load', cb, false);

             var status = 'success', errMsg;
             try {
                 if (timedOut) {
                     throw 'timeout';
                 }

                 var isXml = s.dataType == 'xml' || doc.XMLDocument || $.isXMLDoc(doc);
                 log('isXml='+isXml);
                 if (!isXml && window.opera && (doc.body === null || !doc.body.innerHTML)) {
                     if (--domCheckCount) {
                         // in some browsers (Opera) the iframe DOM is not always traversable when
                         // the onload callback fires, so we loop a bit to accommodate
                         log('requeing onLoad callback, DOM not available');
                         setTimeout(cb, 250);
                         return;
                     }
                     // let this fall through because server response could be an empty document
                     //log('Could not access iframe DOM after mutiple tries.');
                     //throw 'DOMException: not available';
                 }

                 //log('response detected');
                 var docRoot = doc.body ? doc.body : doc.documentElement;
                 xhr.responseText = docRoot ? docRoot.innerHTML : null;
                 xhr.responseXML = doc.XMLDocument ? doc.XMLDocument : doc;
                 if (isXml)
                     s.dataType = 'xml';
                 xhr.getResponseHeader = function(header){
                     var headers = {'content-type': s.dataType};
                     return headers[header];
                 };
                 // support for XHR 'status' & 'statusText' emulation :
                 if (docRoot) {
                     xhr.status = Number( docRoot.getAttribute('status') ) || xhr.status;
                     xhr.statusText = docRoot.getAttribute('statusText') || xhr.statusText;
                 }

                 var dt = (s.dataType || '').toLowerCase();
                 var scr = /(json|script|text)/.test(dt);
                 if (scr || s.textarea) {
                     // see if user embedded response in textarea
                     var ta = doc.getElementsByTagName('textarea')[0];
                     if (ta) {
                         xhr.responseText = ta.value;
                         // support for XHR 'status' & 'statusText' emulation :
                         xhr.status = Number( ta.getAttribute('status') ) || xhr.status;
                         xhr.statusText = ta.getAttribute('statusText') || xhr.statusText;
                     }
                     else if (scr) {
                         // account for browsers injecting pre around json response
                         var pre = doc.getElementsByTagName('pre')[0];
                         var b = doc.getElementsByTagName('body')[0];
                         if (pre) {
                             xhr.responseText = pre.textContent ? pre.textContent : pre.innerText;
                         }
                         else if (b) {
                             xhr.responseText = b.textContent ? b.textContent : b.innerText;
                         }
                     }
                 }
                 else if (dt == 'xml' && !xhr.responseXML && xhr.responseText) {
                     xhr.responseXML = toXml(xhr.responseText);
                 }

                 try {
                     data = httpData(xhr, dt, s);
                 }
                 catch (e) {
                     status = 'parsererror';
                     xhr.error = errMsg = (e || status);
                 }
             }
             catch (e) {
                 log('error caught: ',e);
                 status = 'error';
                 xhr.error = errMsg = (e || status);
             }

             if (xhr.aborted) {
                 log('upload aborted');
                 status = null;
             }

             if (xhr.status) { // we've set xhr.status
                 status = (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304) ? 'success' : 'error';
             }

             // ordering of these callbacks/triggers is odd, but that's how $.ajax does it
             if (status === 'success') {
                 if (s.success)
                     s.success.call(s.context, data, 'success', xhr);
                 deferred.resolve(xhr.responseText, 'success', xhr);
                 if (g)
                     $.event.trigger("ajaxSuccess", [xhr, s]);
             }
             else if (status) {
                 if (errMsg === undefined)
                     errMsg = xhr.statusText;
                 if (s.error)
                     s.error.call(s.context, xhr, status, errMsg);
                 deferred.reject(xhr, 'error', errMsg);
                 if (g)
                     $.event.trigger("ajaxError", [xhr, s, errMsg]);
             }

             if (g)
                 $.event.trigger("ajaxComplete", [xhr, s]);

             if (g && ! --$.active) {
                 $.event.trigger("ajaxStop");
             }

             if (s.complete)
                 s.complete.call(s.context, xhr, status);

             callbackProcessed = true;
             if (s.timeout)
                 clearTimeout(timeoutHandle);

             // clean up
             setTimeout(function() {
                 if (!s.iframeTarget)
                     $io.remove();
                 xhr.responseXML = null;
             }, 100);
         }

         var toXml = $.parseXML || function(s, doc) { // use parseXML if available (jQuery 1.5+)
             if (window.ActiveXObject) {
                 doc = new ActiveXObject('Microsoft.XMLDOM');
                 doc.async = 'false';
                 doc.loadXML(s);
             }
             else {
                 doc = (new DOMParser()).parseFromString(s, 'text/xml');
             }
             return (doc && doc.documentElement && doc.documentElement.nodeName != 'parsererror') ? doc : null;
         };
         var parseJSON = $.parseJSON || function(s) {
             /*jslint evil:true */
             return window['eval']('(' + s + ')');
         };

         var httpData = function( xhr, type, s ) { // mostly lifted from jq1.4.4

             var ct = xhr.getResponseHeader('content-type') || '',
                 xml = type === 'xml' || !type && ct.indexOf('xml') >= 0,
                 data = xml ? xhr.responseXML : xhr.responseText;

             if (xml && data.documentElement.nodeName === 'parsererror') {
                 if ($.error)
                     $.error('parsererror');
             }
             if (s && s.dataFilter) {
                 data = s.dataFilter(data, type);
             }
             if (typeof data === 'string') {
                 if (type === 'json' || !type && ct.indexOf('json') >= 0) {
                     data = parseJSON(data);
                 } else if (type === "script" || !type && ct.indexOf("javascript") >= 0) {
                     $.globalEval(data);
                 }
             }
             return data;
         };

         return deferred;
     }
 };

 /**
  * ajaxForm() provides a mechanism for fully automating form submission.
  *
  * The advantages of using this method instead of ajaxSubmit() are:
  *
  * 1: This method will include coordinates for <input type="image" /> elements (if the element
  *    is used to submit the form).
  * 2. This method will include the submit element's name/value data (for the element that was
  *    used to submit the form).
  * 3. This method binds the submit() method to the form for you.
  *
  * The options argument for ajaxForm works exactly as it does for ajaxSubmit.  ajaxForm merely
  * passes the options argument along after properly binding events for submit elements and
  * the form itself.
  */
 $.fn.ajaxForm = function(options) {
     options = options || {};
     options.delegation = options.delegation && $.isFunction($.fn.on);
     
     // in jQuery 1.3+ we can fix mistakes with the ready state
     if (!options.delegation && this.length === 0) {
         var o = { s: this.selector, c: this.context };
         if (!$.isReady && o.s) {
             log('DOM not ready, queuing ajaxForm');
             $(function() {
                 $(o.s,o.c).ajaxForm(options);
             });
             return this;
         }
         // is your DOM ready?  http://docs.jquery.com/Tutorials:Introducing_$(document).ready()
         log('terminating; zero elements found by selector' + ($.isReady ? '' : ' (DOM not ready)'));
         return this;
     }

     if ( options.delegation ) {
         $(document)
             .off('submit.form-plugin', this.selector, doAjaxSubmit)
             .off('click.form-plugin', this.selector, captureSubmittingElement)
             .on('submit.form-plugin', this.selector, options, doAjaxSubmit)
             .on('click.form-plugin', this.selector, options, captureSubmittingElement);
         return this;
     }

     return this.ajaxFormUnbind()
         .bind('submit.form-plugin', options, doAjaxSubmit)
         .bind('click.form-plugin', options, captureSubmittingElement);
 };

 // private event handlers    
 function doAjaxSubmit(e) {
     /*jshint validthis:true */
     var options = e.data;
     if (!e.isDefaultPrevented()) { // if event has been canceled, don't proceed
         e.preventDefault();
         $(this).ajaxSubmit(options);
     }
 }
     
 function captureSubmittingElement(e) {
     /*jshint validthis:true */
     var target = e.target;
     var $el = $(target);
     if (!($el.is("[type=submit],[type=image]"))) {
         // is this a child element of the submit el?  (ex: a span within a button)
         var t = $el.closest('[type=submit]');
         if (t.length === 0) {
             return;
         }
         target = t[0];
     }
     var form = this;
     form.clk = target;
     if (target.type == 'image') {
         if (e.offsetX !== undefined) {
             form.clk_x = e.offsetX;
             form.clk_y = e.offsetY;
         } else if (typeof $.fn.offset == 'function') {
             var offset = $el.offset();
             form.clk_x = e.pageX - offset.left;
             form.clk_y = e.pageY - offset.top;
         } else {
             form.clk_x = e.pageX - target.offsetLeft;
             form.clk_y = e.pageY - target.offsetTop;
         }
     }
     // clear form vars
     setTimeout(function() { form.clk = form.clk_x = form.clk_y = null; }, 100);
 }


 // ajaxFormUnbind unbinds the event handlers that were bound by ajaxForm
 $.fn.ajaxFormUnbind = function() {
     return this.unbind('submit.form-plugin click.form-plugin');
 };

 /**
  * formToArray() gathers form element data into an array of objects that can
  * be passed to any of the following ajax functions: $.get, $.post, or load.
  * Each object in the array has both a 'name' and 'value' property.  An example of
  * an array for a simple login form might be:
  *
  * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
  *
  * It is this array that is passed to pre-submit callback functions provided to the
  * ajaxSubmit() and ajaxForm() methods.
  */
 $.fn.formToArray = function(semantic, elements) {
     var a = [];
     if (this.length === 0) {
         return a;
     }

     var form = this[0];
     var els = semantic ? form.getElementsByTagName('*') : form.elements;
     if (!els) {
         return a;
     }

     var i,j,n,v,el,max,jmax;
     for(i=0, max=els.length; i < max; i++) {
         el = els[i];
         n = el.name;
         if (!n) {
             continue;
         }

         if (semantic && form.clk && el.type == "image") {
             // handle image inputs on the fly when semantic == true
             if(!el.disabled && form.clk == el) {
                 a.push({name: n, value: $(el).val(), type: el.type });
                 a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
             }
             continue;
         }

         v = $.fieldValue(el, true);
         if (v && v.constructor == Array) {
             if (elements) 
                 elements.push(el);
             for(j=0, jmax=v.length; j < jmax; j++) {
                 a.push({name: n, value: v[j]});
             }
         }
         else if (feature.fileapi && el.type == 'file' && !el.disabled) {
             if (elements) 
                 elements.push(el);
             var files = el.files;
             if (files.length) {
                 for (j=0; j < files.length; j++) {
                     a.push({name: n, value: files[j], type: el.type});
                 }
             }
             else {
                 // #180
                 a.push({ name: n, value: '', type: el.type });
             }
         }
         else if (v !== null && typeof v != 'undefined') {
             if (elements) 
                 elements.push(el);
             a.push({name: n, value: v, type: el.type, required: el.required});
         }
     }

     if (!semantic && form.clk) {
         // input type=='image' are not found in elements array! handle it here
         var $input = $(form.clk), input = $input[0];
         n = input.name;
         if (n && !input.disabled && input.type == 'image') {
             a.push({name: n, value: $input.val()});
             a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
         }
     }
     return a;
 };

 /**
  * Serializes form data into a 'submittable' string. This method will return a string
  * in the format: name1=value1&amp;name2=value2
  */
 $.fn.formSerialize = function(semantic) {
     //hand off to jQuery.param for proper encoding
     return $.param(this.formToArray(semantic));
 };

 /**
  * Serializes all field elements in the jQuery object into a query string.
  * This method will return a string in the format: name1=value1&amp;name2=value2
  */
 $.fn.fieldSerialize = function(successful) {
     var a = [];
     this.each(function() {
         var n = this.name;
         if (!n) {
             return;
         }
         var v = $.fieldValue(this, successful);
         if (v && v.constructor == Array) {
             for (var i=0,max=v.length; i < max; i++) {
                 a.push({name: n, value: v[i]});
             }
         }
         else if (v !== null && typeof v != 'undefined') {
             a.push({name: this.name, value: v});
         }
     });
     //hand off to jQuery.param for proper encoding
     return $.param(a);
 };

 /**
  * Returns the value(s) of the element in the matched set.  For example, consider the following form:
  *
  *  <form><fieldset>
  *      <input name="A" type="text" />
  *      <input name="A" type="text" />
  *      <input name="B" type="checkbox" value="B1" />
  *      <input name="B" type="checkbox" value="B2"/>
  *      <input name="C" type="radio" value="C1" />
  *      <input name="C" type="radio" value="C2" />
  *  </fieldset></form>
  *
  *  var v = $('input[type=text]').fieldValue();
  *  // if no values are entered into the text inputs
  *  v == ['','']
  *  // if values entered into the text inputs are 'foo' and 'bar'
  *  v == ['foo','bar']
  *
  *  var v = $('input[type=checkbox]').fieldValue();
  *  // if neither checkbox is checked
  *  v === undefined
  *  // if both checkboxes are checked
  *  v == ['B1', 'B2']
  *
  *  var v = $('input[type=radio]').fieldValue();
  *  // if neither radio is checked
  *  v === undefined
  *  // if first radio is checked
  *  v == ['C1']
  *
  * The successful argument controls whether or not the field element must be 'successful'
  * (per http://www.w3.org/TR/html4/interact/forms.html#successful-controls).
  * The default value of the successful argument is true.  If this value is false the value(s)
  * for each element is returned.
  *
  * Note: This method *always* returns an array.  If no valid value can be determined the
  *    array will be empty, otherwise it will contain one or more values.
  */
 $.fn.fieldValue = function(successful) {
     for (var val=[], i=0, max=this.length; i < max; i++) {
         var el = this[i];
         var v = $.fieldValue(el, successful);
         if (v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length)) {
             continue;
         }
         if (v.constructor == Array)
             $.merge(val, v);
         else
             val.push(v);
     }
     return val;
 };

 /**
  * Returns the value of the field element.
  */
 $.fieldValue = function(el, successful) {
     var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
     if (successful === undefined) {
         successful = true;
     }

     if (successful && (!n || el.disabled || t == 'reset' || t == 'button' ||
         (t == 'checkbox' || t == 'radio') && !el.checked ||
         (t == 'submit' || t == 'image') && el.form && el.form.clk != el ||
         tag == 'select' && el.selectedIndex == -1)) {
             return null;
     }

     if (tag == 'select') {
         var index = el.selectedIndex;
         if (index < 0) {
             return null;
         }
         var a = [], ops = el.options;
         var one = (t == 'select-one');
         var max = (one ? index+1 : ops.length);
         for(var i=(one ? index : 0); i < max; i++) {
             var op = ops[i];
             if (op.selected) {
                 var v = op.value;
                 if (!v) { // extra pain for IE...
                     v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
                 }
                 if (one) {
                     return v;
                 }
                 a.push(v);
             }
         }
         return a;
     }
     return $(el).val();
 };

/**
 * Clears the form data.  Takes the following actions on the form's input fields:
 *  - input text fields will have their 'value' property set to the empty string
 *  - select elements will have their 'selectedIndex' property set to -1
 *  - checkbox and radio inputs will have their 'checked' property set to false
 *  - inputs of type submit, button, reset, and hidden will *not* be effected
 *  - button elements will *not* be effected
 */
$.fn.clearForm = function(includeHidden) {
	return this.each(function() {
		$('input,select,textarea', this).clearFields(includeHidden);
	});
};

 /**
  * Clears the selected form elements.
  */
 $.fn.clearFields = $.fn.clearInputs = function(includeHidden) {
     var re = /^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i; // 'hidden' is not in this list
     return this.each(function() {
         var t = this.type, tag = this.tagName.toLowerCase();
         if (re.test(t) || tag == 'textarea') {
             this.value = '';
         }
         else if (t == 'checkbox' || t == 'radio') {
             this.checked = false;
         }
         else if (tag == 'select') {
             this.selectedIndex = -1;
         }
		else if (t == "file") {
			if ($.browser.msie) {
				$(this).replaceWith($(this).clone());
			} else {
				$(this).val('');
			}
		}
         else if (includeHidden) {
             // includeHidden can be the value true, or it can be a selector string
             // indicating a special test; for example:
             //  $('#myForm').clearForm('.special:hidden')
             // the above would clean hidden inputs that have the class of 'special'
             if ( (includeHidden === true && /hidden/.test(t)) ||
                  (typeof includeHidden == 'string' && $(this).is(includeHidden)) )
                 this.value = '';
         }
     });
 };

 /**
  * Resets the form data.  Causes all form elements to be reset to their original value.
  */
 $.fn.resetForm = function() {
     return this.each(function() {
         // guard against an input with the name of 'reset'
         // note that IE reports the reset function as an 'object'
         if (typeof this.reset == 'function' || (typeof this.reset == 'object' && !this.reset.nodeType)) {
             this.reset();
         }
     });
 };

 /**
  * Enables or disables any matching elements.
  */
 $.fn.enable = function(b) {
     if (b === undefined) {
         b = true;
     }
     return this.each(function() {
         this.disabled = !b;
     });
 };

 /**
  * Checks/unchecks any matching checkboxes or radio buttons and
  * selects/deselects and matching option elements.
  */
 $.fn.selected = function(select) {
     if (select === undefined) {
         select = true;
     }
     return this.each(function() {
         var t = this.type;
         if (t == 'checkbox' || t == 'radio') {
             this.checked = select;
         }
         else if (this.tagName.toLowerCase() == 'option') {
             var $sel = $(this).parent('select');
             if (select && $sel[0] && $sel[0].type == 'select-one') {
                 // deselect all other options
                 $sel.find('option').selected(false);
             }
             this.selected = select;
         }
     });
 };

 // expose debug var
 $.fn.ajaxSubmit.debug = false;

 // helper fn for console logging
 function log() {
     if (!$.fn.ajaxSubmit.debug) 
         return;
     var msg = '[jquery.form] ' + Array.prototype.join.call(arguments,'');
     if (window.console && window.console.log) {
         window.console.log(msg);
     }
     else if (window.opera && window.opera.postError) {
         window.opera.postError(msg);
     }
 }

 })(jQuery);
