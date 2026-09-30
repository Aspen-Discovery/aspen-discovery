AspenDiscovery.Ratings = (function(){
	$(function(){
		AspenDiscovery.Ratings.initializeRaters();
	});
	return{
		initializeRaters: function(){
			$(".rater").each(function(){
				var ratingElement = $(this),
						userRating = ratingElement.data("user_rating"),
						id = ratingElement.data("id"),
						options = {
							id: id,
							rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")),
							//url: Globals.path +"AJAX" // only works for grouped works
							//url: location.protocol+'\\'+location.host+ "/GroupedWork/AJAX" // full path
							//url: Globals.path + "/GroupedWork/AJAX" // full path // works on our servers but not locally. plb 12-29-2015
							url: Globals.path + "/GroupedWork/"+ encodeURIComponent( id ) + "/AJAX" // full path
						};
				ratingElement.rater(options);
			});
		},

		doRatingReview: function (id){
			$.getJSON(Globals.path + "/GroupedWork/"+id+"/AJAX?method=getPromptForReviewForm", function(data){
				if (data.prompt) AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons); // only ask if user hasn't set the setting already
				if (data.error)  AspenDiscovery.showMessage(__('Error'), data.message);
			}).fail(AspenDiscovery.ajaxFail)
		},

		doNoRatingReviews : function (){
			$.getJSON(Globals.path + "/GroupedWork/AJAX?method=setNoMoreReviews", function(data){
				if (data.success) AspenDiscovery.showMessage(__('Success'), __('You will no longer be asked to give a review.'), true)
				else AspenDiscovery.showMessage(__('Error'), __('Failed to save your setting.'))
			}).fail(AspenDiscovery.ajaxFail);
		}
	};
}(AspenDiscovery.Ratings));

/*
*  Jquery Ratings Plugin, Adapted for Aspen Discovery
 *
* */
//copyright 2008 Jarrett Vance
//http://jvance.com
$.fn.rater = function(options) {
	var opts = $.extend( {}, $.fn.rater.defaults, options);
	return this.each(function() {
		var $this = $(this),
				$on = $this.find('.ui-rater-starsOn'),
				$off = $this.find('.ui-rater-starsOff');

		if (opts.size == undefined) opts.size = $off.height();
		if (opts.rating == undefined) {
			opts.rating = $on.width() / $off.width();
		}else{
			$on.width($off.width() * (opts.rating / opts.ratings.length));
		}
		if (opts.id == undefined) opts.id = $this.attr('id');
		var initialRating = opts.rating;
		if ($this.data('currentRating') === undefined) {
			$this.data('currentRating', initialRating);
		}

		if (!$this.hasClass('ui-rater-bindings-done')) {
			$this.addClass('ui-rater-bindings-done');
			$off.on('mousemove', function(e) {
				var left = e.clientX - $off.offset().left,
						width = $off.width() - ($off.width() - left);
				width = Math.min(Math.ceil(width / (opts.size / opts.step)) * opts.size / opts.step, opts.size * opts.ratings.length);
				$on.width(width);
				var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				//$this.attr('title', 'Click to Rate "' + (opts.ratings[r - 1] == undefined ? r : opts.ratings[r - 1]) + '"');
				// TODO ratings label's are customized now.
				$this.attr('title', 'Click to Rate "' +  r  + ' stars"');
			}).on('mouseenter', function(e) { // Hover In
					$on.addClass('ui-rater-starsHover');
			}).on('mouseleave', function(e) { // Hover out
				$on.removeClass('ui-rater-starsHover');
				let currentRating = $this.data('currentRating');
				if (currentRating === undefined) {
					currentRating = initialRating;
				}
				$on.width(currentRating * opts.size);
			}).on('click', function(e) {
				const r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				$.fn.rater.rate($this, opts, r);
			}).css('cursor', 'pointer'); $on.css('cursor', 'pointer');
		}
	});
};


$.fn.rater.defaults = {
	url : location.href,
	ratings: [__('Hated It'), __("Didn't Like It"), __('Liked It'), __('Really Liked It'), __('Loved It')],
	step : 1
};

$.fn.rater.rate = function($this, opts, rating) {
	if (Globals.loggedIn){
		var $on = $this.find('.ui-rater-starsOn'),
				$off = $this.find('.ui-rater-starsOff');
		$off.fadeTo(600, 0.4, function() {
			$.getJSON(opts.url, {method: 'rateTitle', id: opts.id, rating: rating}, function(data) {
				if (data.error) {
					AspenDiscovery.showMessage(__('Error'), data.error);
					$off.fadeTo(500, 1).mouseleave(); // Reset rater in light of failure
				}
				if (data.rating) { // success
					opts.rating = parseFloat(data.rating);
					//$on.css('cursor', 'default');
					$off
						// detach rater.
						//	.unbind('click').unbind('mousemove').unbind('mouseenter').unbind('mouseleave')
							//.css('cursor', 'default')

						// wrap-up
							.fadeTo(600, 0.1, function() {
								$on.removeClass('ui-rater-starsHover').width(opts.rating * opts.size).addClass('userRated');
								$this.data('currentRating', opts.rating);
								$off.fadeTo(500, 1);
								$this.attr('title', 'Your rating: ' + rating.toFixed(1));
								if ($this.data('show_review') == true){
									AspenDiscovery.Ratings.doRatingReview(opts.id);
								}
							});
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$off.fadeTo(500, 1).mouseleave(); // Reset rater in light of failure
			});

		});
	}else{
		AspenDiscovery.Account.ajaxLogin(null, function(){
			$.fn.rater.rate($this, opts, rating);
		}, true);
	}
};