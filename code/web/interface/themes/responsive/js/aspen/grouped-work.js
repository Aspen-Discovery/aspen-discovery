AspenDiscovery.GroupedWork = (function(){
	return {
		hasTableOfContentsInRecord: false,

		clearUserRating: function (groupedWorkId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUserRating';
			$.getJSON(url, function(data){
				if (data.result == true){
					$('.rate' + groupedWorkId).find('.ui-rater-starsOn').width(0);
					$('#myRating' + groupedWorkId).hide();
					AspenDiscovery.showMessage('Success', data.message, true);
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			});
			return false;
		},

		clearNotInterested: function (notInterestedId){
			var url = Globals.path + '/GroupedWork/' + notInterestedId + '/AJAX?method=clearNotInterested';
			$.getJSON(
					url, function(data){
						if (data.result == false){
							AspenDiscovery.showMessage('Sorry', "There was an error updating the title.");
						}else{
							$("#notInterested" + notInterestedId).hide();
						}
					}
			);
		},

		deleteReview: function(id, reviewId){
			if (confirm("Are you sure you want to delete this review?")){
				var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=deleteUserReview';
				$.getJSON(url, function(data){
					if (data.result == true){
						$('#review_' + reviewId).hide();
						AspenDiscovery.showMessage('Success', data.message, true);
					}else{
						AspenDiscovery.showMessage('Sorry', data.message);
					}
				});
			}
			return false;
		},

		forceRegrouping: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=forceRegrouping';
			$.getJSON(url, function (data){
						AspenDiscovery.showMessage("Success", data.message, true, true);
						setTimeout("AspenDiscovery.closeLightbox();", 3000);
					}
			);
			return false;
		},

		forceReindex: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=forceReindex';
			$.getJSON(url, function (data){
						AspenDiscovery.showMessage("Success", data.message, true, true);
						setTimeout("AspenDiscovery.closeLightbox();", 3000);
					}
			);
			return false;
		},

		getGoDeeperData: function (id, dataType){
			var placeholder;
			if (dataType == 'excerpt') {
				placeholder = $("#excerptPlaceholder");
			} else if (dataType == 'avSummary') {
				placeholder = $("#avSummaryPlaceholder");
			} else if (dataType == 'tableOfContents') {
				placeholder = $("#tableOfContentsPlaceholder");
			} else if (dataType == 'authornotes') {
				placeholder = $("#authornotesPlaceholder");
			}
			if (placeholder.hasClass("loaded")) return;
			placeholder.show();
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
					params = {'method': 'GetGoDeeperData', dataType:dataType};
			$.getJSON(url, params, function(data) {
				placeholder.html(data.formattedData).addClass('loaded');
			});
		},

		getGoodReadsComments: function (isbn){
			$("#goodReadsPlaceHolder").replaceWith(
				"<iframe id='goodreads_iframe' class='goodReadsIFrame' src='https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn=" + isbn + "&links=660&review_back=fff&stars=000&text=000' width='100%' height='400px' frameborder='0'></iframe>"
			);
		},

		loadEnrichmentInfo: function (id, forceReload) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
					params = {'method':'getEnrichmentInfo'};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					let seriesData = data.seriesInfo;
					if (seriesData && seriesData.titles.length > 0) {
						seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');
						$('#seriesInfo').show();
						seriesScroller.loadTitlesFromJsonData(seriesData);
						$('#seriesPanel').show();
					}else{
						$('#seriesPanel').hide();
					}
					let similarTitleData = data.similarTitles;
					if (similarTitleData && similarTitleData.titles.length > 0) {
						morelikethisScroller = new TitleScroller('titleScrollerMoreLikeThis', 'MoreLikeThis', 'morelikethisList');
						$('#moreLikeThisInfo').show();
						morelikethisScroller.loadTitlesFromJsonData(similarTitleData);
					}else{
						$('#moreLikeThisPanel').hide();
					}
					let showGoDeeperData = data.showGoDeeper;
					if (showGoDeeperData) {
						//$('#goDeeperLink').show();
						let goDeeperOptions = data.goDeeperOptions;
						//add a tab before citation for each item
						for (let option in goDeeperOptions){
							if (option == 'excerpt') {
								$("#excerptPanel").show();
							} else if (option == 'avSummary') {
								$("#avSummaryPlaceholder,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option == 'tableOfContents') {
								$("#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option == 'authorNotes') {
								$('#authornotesPlaceholder,#authornotesPanel').show();
							}
						}
					}
					if (AspenDiscovery.GroupedWork.hasTableOfContentsInRecord){
						$("#tableofcontentstab_label,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
					}
					let similarTitlesNovelist = data.similarTitlesNovelist;
					if (similarTitlesNovelist && similarTitlesNovelist.length > 0){
						$("#novelistTitlesPlaceholder").html(similarTitlesNovelist);
						$("#novelistTab_label,#similarTitlesPanel").show()
						;
					}

					let similarAuthorsNovelist = data.similarAuthorsNovelist;
					if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
						$("#novelistAuthorsPlaceholder").html(similarAuthorsNovelist);
						$("#novelistTab_label,#similarAuthorsPanel").show();
					}

					let similarSeriesNovelist = data.similarSeriesNovelist;
					if (similarSeriesNovelist && similarSeriesNovelist.length > 0){
						$("#novelistSeriesPlaceholder").html(similarSeriesNovelist);
						$("#novelistTab_label,#similarSeriesPanel").show();
					}

					// Show Explore More Sidebar Section loaded above
					$('.ajax-carousel', '#explore-more-body')
						.parents('.jcarousel-wrapper').show()
						.prev('.sectionHeader').show();
					// Initiate Any Explore More JCarousels
					AspenDiscovery.initCarousels('.ajax-carousel');

				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},

		loadReviewInfo: function (id) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewInfo";
			$.getJSON(url, function(data) {
				if (data.numSyndicatedReviews == 0){
					$("#syndicatedReviewsPanel").hide();
				}else{
					var syndicatedReviewsData = data.syndicatedReviewsHtml;
					if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
						$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
					}
				}

				if (data.numCustomerReviews == 0){
					$("#borrowerReviewsPanel").hide();
				}else{
					var customerReviewsData = data.customerReviewsHtml;
					if (customerReviewsData && customerReviewsData.length > 0) {
						$("#customerReviewPlaceholder").html(customerReviewsData);
					}
				}
			});
		},

		markNotInterested: function (recordId){
			if (Globals.loggedIn){
				var url = Globals.path + '/GroupedWork/' + recordId + '/AJAX?method=markNotInterested';
				$.getJSON(
						url, function(data){
							if (data.result == true){
								$("#notInterested" + recordId).css('background-color', '#f73d3d').css('color', 'white').prop("disabled", true);
							}else{
								AspenDiscovery.showMessage('Sorry', data.message);
							}
						}
				);
				return false;
			}else{
				return AspenDiscovery.Account.ajaxLogin(null, function(){markNotInterested(source, recordId)}, false);
			}
		},

		reloadCover: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadCover';
			$.getJSON(url, function (data){
						AspenDiscovery.showMessage("Success", data.message, true, true);
					}
			);
			return false;
		},

		reloadEnrichment: function (id){
			AspenDiscovery.GroupedWork.loadEnrichmentInfo(id, true);
		},

		reloadIslandora: function(id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadIslandora';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessage("Success", data.message, true, true);
				}
			);
			return false;
		},

		saveReview: function(id){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function(){
					this.saveReview(id)
				})
			} else {
				var comment = $('#comment' + id).val(),
						rating = $('#rating' + id).val(),
						url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
						params =  {
							method : 'saveReview'
							,comment : comment
							,rating : rating
						};
				$.getJSON(url, params,
					function(data) {
						if (data.success) {
							if (data.newReview){
								$("#customerReviewPlaceholder").append(data.reviewHtml);
							}else{
								$("#review_" + data.reviewId).replaceWith(data.reviewHtml);
							}
							AspenDiscovery.closeLightbox();
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		saveTag: function(id){
			var tag = $("#tags_to_apply").val();
			$("#saveToList-button").prop('disabled', true);

			var url = Globals.path + "/GroupedWork/" + id + "/AJAX";
			var params = "method=SaveTag&" +
					"tag=" + encodeURIComponent(tag);
			$.ajax({
				url: url+'?'+params,
				dataType: "json",
				success: function(data) {
					if (data.success) {
						AspenDiscovery.showMessage("Success", data.message);
						setTimeout("AspenDiscovery.closeLightbox();", 3000);
					} else {
						AspenDiscovery.showMessage("Error adding tags", "There was an unexpected error adding tags to this title.<br/>" + data.message);
					}

				},
				error: function(jqXHR, textStatus) {
					AspenDiscovery.showMessage("Error adding tags", "There was an unexpected error adding tags to this title.<br/>" + textStatus);
				}
			});
		},

		saveToList: function(id){
			if (Globals.loggedIn){
				var listId = $('#addToList-list').val(),
						notes  = $('#addToList-notes').val(),
						url    = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
						params = {
							'method':'saveToList'
							,notes:notes
							,listId:listId
						};
				$.getJSON(url, params,
						function(data) {
							if (data.success) {
								AspenDiscovery.showMessage("Added Successfully", data.message, 2000); // auto-close after 2 seconds.
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		sendEmail: function(id){
			if (Globals.loggedIn){
				var from = $('#from').val(),
						to = $('#to').val(),
						message = $('#message').val(),
						related_record = $('#related_record').val(),
						url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
						params = {
							'method' : 'sendEmail',
							from : from,
							to : to,
							message : message,
							related_record : related_record
						};
				$.getJSON(url, params,
						function(data) {
							if (data.result) {
								AspenDiscovery.showMessage("Success", data.message);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		sendSMS: function(id){
			if (Globals.loggedIn){
				var phoneNumber = $('#sms_phone_number').val(),
						provider = $('#provider').val(),
						related_record = $('#related_record').val(),
						url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
						params = {
							'method' : 'sendSMS',
							provider : provider,
							sms_phone_number : phoneNumber,
							related_record : related_record
						};
				$.getJSON(url, params,
						function(data) {
							if (data.result) {
								AspenDiscovery.showMessage("Success", data.message);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		showEmailForm: function(trigger, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getEmailForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					return AspenDiscovery.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},


		showGroupedWorkInfo:function(id, browseCategoryId){
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getWorkInfo";
			if (browseCategoryId != undefined){
				url += "&browseCategoryId=" + browseCategoryId;
			}
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showReviewForm: function(trigger, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					return AspenDiscovery.GroupedWork.showReviewForm(trigger, id);
				}, false);
			}
			return false;
		},

		showSaveToListForm: function (trigger, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getSaveToListForm";
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.showSaveToListForm(trigger, id);
				});
			}
			return false;
		},

	};
}(AspenDiscovery.GroupedWork || {}));