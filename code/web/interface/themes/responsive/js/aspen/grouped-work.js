AspenDiscovery.GroupedWork = (function(){
	return {
		hasTableOfContentsInRecord: false,

		clearUserRating: function (groupedWorkId){
			let url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUserRating';
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
			let url = Globals.path + '/GroupedWork/' + notInterestedId + '/AJAX?method=clearNotInterested';
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

		forceReindex: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=forceReindex';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessage("Success", data.message, true, false);
					setTimeout("AspenDiscovery.closeLightbox();", 3000);
				}
			);
			return false;
		},

		getGoDeeperData: function (id, dataType){
			let placeholder;
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
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
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

		loadDescription: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=getDescription';
			$.getJSON(url, function (data){
					if (data.success){
						$("#descriptionPlaceholder").html(data.description);
					}
				}
			);
			return false;
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
					let seriesSummary = data.seriesSummary;
					if (seriesSummary){
						$('#seriesPlaceholder' + id).html(seriesSummary);
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

		loadMoreLikeThis: function (id, forceReload) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
				params = {'method':'getMoreLikeThis'};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					let similarTitleData = data.similarTitles;
					if (similarTitleData && similarTitleData.titles.length > 0) {
						morelikethisScroller = new TitleScroller('titleScrollerMoreLikeThis', 'MoreLikeThis', 'morelikethisList');
						$('#moreLikeThisInfo').show();
						morelikethisScroller.loadTitlesFromJsonData(similarTitleData);
					}else{
						$('#moreLikeThisPanel').hide();
					}

				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},

		loadReviewInfo: function (id) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewInfo";
			$.getJSON(url, function(data) {
				if (data.numSyndicatedReviews == 0){
					$("#syndicatedReviewsPanel").hide();
				}else{
					let syndicatedReviewsData = data.syndicatedReviewsHtml;
					if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
						$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
					}
				}

				if (data.numCustomerReviews == 0){
					$("#borrowerReviewsPanel").hide();
				}else{
					let customerReviewsData = data.customerReviewsHtml;
					if (customerReviewsData && customerReviewsData.length > 0) {
						$("#customerReviewPlaceholder").html(customerReviewsData);
					}
				}
			});
		},

		loadSeriesSummary: function (recordId){
			let url = Globals.path + '/GroupedWork/' + recordId + '/AJAX?method=getSeriesSummary';
			$.getJSON(
				url, function(data){
					if (data.result == true){
						$("#seriesPlaceholder" + recordId).html(data.seriesSummary);
					}
				}
			);
			return false;
		},

		markNotInterested: function (recordId){
			if (Globals.loggedIn){
				let url = Globals.path + '/GroupedWork/' + recordId + '/AJAX?method=markNotInterested';
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
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadCover';
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
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadIslandora';
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

		saveToList: function(id){
			if (Globals.loggedIn){
				let listId = $('#addToList-list').val();
				let notes  = $('#addToList-notes').val();
				let url    = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				let params = {
					'method':'saveToList'
					,notes:notes
					,listId:listId
				};
				$.getJSON(url, params,
						function(data) {
							if (data.success) {
								AspenDiscovery.showMessage("Added Successfully", data.message, 2000); // auto-close after 2 seconds.
								AspenDiscovery.Account.loadListData();
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
				let from = $('#from').val();
				let to = $('#to').val();
				let message = $('#message').val();
				let related_record = $('#related_record').val();
				let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				let params = {
					'method' : 'sendEmail',
					from : from,
					to : to,
					message : message,
					related_record : related_record
				};
				$.getJSON(url, params, function(data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		showCopyDetails: function(id, format, recordId){
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			let params = {
				'method' : 'getCopyDetails',
				format : format,
				recordId : recordId,
			};
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
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
				let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getSaveToListForm";
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

		getUploadCoverForm: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=getUploadCoverForm';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadCover: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=uploadCover';
			let uploadCoverData = new FormData($("#uploadCoverForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},
		getGroupWithForm: function(trigger, id) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithForm";
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.getGroupWithForm(id);
				});
			}
			return false;
		},
		getGroupWithSearchForm: function (trigger, id, searchId, page) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithSearchForm&searchId=" + searchId + "&page=" + page;
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.getGroupWithForm(id);
				});
			}
			return false;
		},
		getGroupWithInfo: function(id) {
			let groupWithId = $('#workToGroupWithId').val().trim();
			if (groupWithId.length === 36){
				let url = Globals.path + "/GroupedWork/" + groupWithId + "/AJAX?method=getGroupWithInfo";
				$.getJSON(url, function(data){
					$("#groupWithInfo").html(data.message);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				$("groupWithInfo").html("");
			}
		},
		processGroupWithForm: function() {
			let id = $('#id').val();
			let groupWithId = $('#workToGroupWithId').val().trim();
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=processGroupWithForm&groupWithId=" + groupWithId;
			//AspenDiscovery.closeLightbox();
			$.getJSON(url, function(data){
				if (data.success){
					AspenDiscovery.showMessage("Success", data.message, true, false);
				}else{
					AspenDiscovery.showMessage("An error occurred", data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		ungroupRecord: function(trigger, recordId) {
			if (Globals.loggedIn){
				let url = Globals.path + "/Admin/AJAX?method=ungroupRecord&recordId=" + recordId;
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessage("Success", data.message);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.ungroupRecord(id);
				});
			}
			return false;
		},

		getStaffView: function (id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getWhileYouWait: function (id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getWhileYouWait";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		getYouMightAlsoLike: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getYouMightAlsoLike";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		}
	};
}(AspenDiscovery.GroupedWork || {}));