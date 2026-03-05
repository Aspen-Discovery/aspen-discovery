AspenDiscovery.GroupedWork = (function(){
	return {
		hasTableOfContentsInRecord: false,
		groupedWorks: {},
		manifestationSwipers: {},
		variationSwipers: {},

		clearUserRating: function (groupedWorkId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUserRating';
			$.getJSON(url, function(data){
				if (data.result === true){
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
					if (data.result === false){
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
					if (data.result === true){
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
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=forceReindex';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage(data.title, data.message, true, false);
				setTimeout("AspenDiscovery.closeLightbox();", 3000);
			});
			return false;
		},

		viewDebugging: function (id) {
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=viewDebugging';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons, false, false);
			});
			return false;
		},

		resetDebugging: function (id) {
			AspenDiscovery.closeLightbox();
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=resetDebugging';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage(data.title, data.message, true, false);
			});
			return false;
		},

		getGoDeeperData: function (id, dataType){
			var placeholder;
			if (dataType === 'excerpt') {
				placeholder = $("#excerptPlaceholder");
			} else if (dataType === 'avSummary') {
				placeholder = $("#avSummaryPlaceholder");
			} else if (dataType === 'tableOfContents') {
				placeholder = $("#tableOfContentsPlaceholder");
			} else if (dataType === 'authornotes') {
				placeholder = $("#authornotesPlaceholder");
			} else if (dataType === 'loralAllInOne') {
				placeholder = $("#loralAllInOnePlaceholder");
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
			// noinspection HtmlDeprecatedAttribute
			$("#goodReadsPlaceHolder").replaceWith(
				"<iframe id='goodreads_iframe' class='goodReadsIFrame' src='https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn=" + isbn + "&links=660&review_back=fff&stars=000&text=000' width='100%' height='400px' frameborder='0'></iframe>"
			);
		},

		loadDescription: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=getDescription';
			$.getJSON(url, function (data){
					if (data.success){
						$("#descriptionPlaceholder").html(data.description);
					}
				}
			);
			return false;
		},

		loadEnrichmentInfo: function (id, forceReload) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
					params = {'method':'getEnrichmentInfo'};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					var seriesData = data.seriesInfo;
					if (seriesData && seriesData.titles.length > 0) {
						//Create an unordered list for display
						var html = '<ul>';

						$.each(seriesData.titles, function() {
							html += '<li class="carouselTitleWrapper">' + this.formattedTitle + '</li>';
						});

						html += '</ul>';

						var carouselElement = $('#seriesCarousel');
						carouselElement.html(html);
						var jCarousel = carouselElement.jcarousel({wrap:null});

						// Reload carousel
						jCarousel.jcarousel('reload');
						jCarousel.jcarousel('scroll', seriesData.currentIndex)
						$('.seriesLoadingNote').hide();
						$('#seriesInfo').show();
					}else{
						$('#seriesPanel').hide();
					}
					var seriesSummary = data.seriesSummary;
					if (seriesSummary){
						$('#seriesPlaceholder' + id).html(seriesSummary);
					}
					var showGoDeeperData = data.showGoDeeper;
					if (showGoDeeperData) {
						//$('#goDeeperLink').show();
						var goDeeperOptions = data.goDeeperOptions;
						//add a tab before citation for each item
						for (var option in goDeeperOptions){
							if (option === 'excerpt') {
								$("#excerptPanel").show();
							} else if (option === 'avSummary') {
								$("#avSummaryPlaceholder,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option === 'tableOfContents') {
								$("#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option === 'authorNotes') {
								$('#authornotesPlaceholder,#authornotesPanel').show();
							}
						}
					}
					if (AspenDiscovery.GroupedWork.hasTableOfContentsInRecord){
						$("#tableofcontentstab_label,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
					}
					var similarTitlesNovelist = data.similarTitlesNovelist;
					if (similarTitlesNovelist && similarTitlesNovelist.length > 0){
						$("#novelistTitlesPlaceholder").html(similarTitlesNovelist);
						$("#novelistTab_label,#similarTitlesPanel").show()
						;
					}

					var similarAuthorsNovelist = data.similarAuthorsNovelist;
					if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
						$("#novelistAuthorsPlaceholder").html(similarAuthorsNovelist);
						$("#novelistTab_label,#similarAuthorsPanel").show();
					}

					var similarSeriesNovelist = data.similarSeriesNovelist;
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
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = {
				'method':'getMoreLikeThis'
			};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					var similarTitleData = data.similarTitles;
					if (similarTitleData && similarTitleData.titles.length > 0) {
						//Create an unordered list for display
						var html = '<ul>';

						$.each(similarTitleData.titles, function() {
							html += '<li class="carouselTitleWrapper">' + this.formattedTitle + '</li>';
						});

						html += '</ul>';

						var carouselElement = $('#moreLikeThisCarousel');
						carouselElement.html(html);
						var jCarousel = carouselElement.jcarousel();

						// Reload carousel
						jCarousel.jcarousel('reload');
					}else{
						$('#moreLikeThisPanel').hide();
					}

				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},

		loadReviewInfo: function (id) {
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewInfo";
			$.getJSON(url, function(data) {
				if (data.numSyndicatedReviews === 0){
					$("#syndicatedReviewsPanel").hide();
				}else{
					var syndicatedReviewsData = data.syndicatedReviewsHtml;
					if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
						$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
					}
				}

				if (data.numCustomerReviews === 0){
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
							if (data.result === true){
								$("#groupedRecord" + recordId).parent('.result').hide();
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

		sendEmail: function(id){
			if (Globals.loggedIn){
				var from = $('#from').val();
				var to = $('#to').val();
				var message = $('#message').val();
				var related_record = $('#related_record').val();
				var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				var params = {
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
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = {
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
			var url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getWorkInfo";
			if (browseCategoryId !== undefined){
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

		thirdPartyCoverToggle: function (groupedWorkId, recordType, recordId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=thirdPartyCoverToggle&recordType=' + recordType + '&recordId=' + recordId;
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage("Success", data.message, true, true);
			});
			return false;
		},

		clearUploadedCover: function (groupedWorkId, recordType, recordId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUploadedCover&recordType=' + recordType + '&recordId=' + recordId;
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage("Success", data.message, true, true);
			});
			return false;
		},

		getUploadCoverForm: function (groupedWorkId, recordType, recordId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=getUploadCoverForm&recordType=' + recordType + '&recordId=' + recordId;
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadCover: function (groupedWorkId, recordType, recordId){
			var uploadOption = $('#uploadOption').val();
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=uploadCover&recordType=' + recordType + '&recordId=' + recordId + '&uploadOption=' + uploadOption;
			var uploadCoverData = new FormData($("#uploadCoverForm")[0]);
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

		getUploadCoverFormByURL: function (groupedWorkId, recordType, recordId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=getUploadCoverFormByURL&recordType=' + recordType + '&recordId=' + recordId;
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadCoverByURL: function (groupedWorkId, recordType, recordId){
			var uploadOption = $('#uploadOption').val();
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=uploadCoverByURL&recordType=' + recordType + '&recordId=' + recordId + '&uploadOption=' + uploadOption;
			var uploadCoverData = new FormData($("#uploadCoverFormByURL")[0]);
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

		getPreviewRelatedCover: function (recordId,groupedWorkId,recordType){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=getPreviewRelatedCover&recordId=' + recordId + '&recordType=' + recordType;
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		setRelatedCover: function (recordId,groupedWorkId,recordType){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=setRelatedCover&recordId=' + recordId + '&recordType=' + recordType;
			AspenDiscovery.closeLightbox();
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage(data.title, data.message, true, true);
			});
			return false;
		},

		clearRelatedCover: function (id){
			var url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=clearRelatedCover';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessage("Success", data.message, true, true);
			});
			return false;
		},

		getGroupWithForm: function(trigger, id) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithForm";
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
				var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithSearchForm&searchId=" + searchId + "&page=" + page;
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

		getGroupWithInfo: function() {
			var groupWithId = $('#workToGroupWithId').val().trim();
			if (groupWithId.length === 36){
				var url = Globals.path + "/GroupedWork/" + groupWithId + "/AJAX?method=getGroupWithInfo";
				$.getJSON(url, function(data){
					$("#groupWithInfo").html(data.message);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				$("groupWithInfo").html("");
			}
		},
		processGroupWithForm: function() {
			var id = $('#id').val();
			var groupWithId = $('#workToGroupWithId').val().trim();
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=processGroupWithForm&groupWithId=" + groupWithId;
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
				var url = Globals.path + "/Admin/AJAX?method=ungroupRecord&recordId=" + recordId;
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

		getMoveRecordForm(trigger, recordId, groupedWorkId) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				const url = `${Globals.path}/GroupedWork/${groupedWorkId}/AJAX?method=getMoveRecordForm&recordId=${encodeURIComponent(recordId)}`;
				$.getJSON(url, /** @param {{success: boolean, title: string, modalBody: string, modalButtons: string, message: string}} data */ (data) => {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin($(trigger), () => {
					AspenDiscovery.GroupedWork.getMoveRecordForm(trigger, recordId, groupedWorkId);
				}, false);
			}
			return false;
		},

		getMoveRecordInfo() {
			const targetWorkId = $('#targetWorkId').val().trim();
			if (targetWorkId.length === 36) {
				const url = `${Globals.path}/GroupedWork/AJAX?method=getMoveRecordInfo&targetWorkId=${encodeURIComponent(targetWorkId)}`;
				$.getJSON(url, /** @param {{message: string}} data */ (data) => {
					$("#moveRecordInfo").html(data.message);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				$("#moveRecordInfo").html("");
			}
		},

		processMoveRecordForm() {
			const recordId = $('#recordId').val();
			const targetWorkId = $('#targetWorkId').val().trim();
			const url = `${Globals.path}/GroupedWork/AJAX?method=processMoveRecordForm&recordId=${encodeURIComponent(recordId)}&targetWorkId=${encodeURIComponent(targetWorkId)}`;
			$.getJSON(url, /** @param {{success: boolean, title: string, message: string}} data */ (data) => {
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.message, true, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		getStaffView: function (id) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getWhileYouWait: function (id, format) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getWhileYouWait&activeFormat=" + format;
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		getYouMightAlsoLike: function(id, format) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getYouMightAlsoLike&activeFormat=" + format;
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		deleteAlternateTitle: function(id) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=deleteAlternateTitle";
			$.getJSON(url, function (data){
				if (data.success){
					$("#alternateTitle" + id).hide();
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		deleteRecordGroupingOverride(id) {
			const url = `${Globals.path}/GroupedWork/AJAX?method=deleteRecordGroupingOverride&id=${encodeURIComponent(id)}`;
			$.getJSON(url, /** @param {{success: boolean, title: string, message: string}} data */ (data) => {
				if (data.success) {
					$("#recordGroupingOverride" + id).hide();
					AspenDiscovery.showMessage(data.title, data.message, true, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		deleteUngrouping: function(groupedWorkId, ungroupingId) {
			var url = Globals.path + "/GroupedWork/" + groupedWorkId + "/AJAX?method=deleteUngrouping&ungroupingId=" + ungroupingId;
			$.getJSON(url, function (data){
				if (data.success){
					$("#ungrouping").hide();
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		getDisplayInfoForm: function(id) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getDisplayInfoForm";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			});
			return false;
		},

		processGroupedWorkDisplayInfoForm: function(id) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX";
			var params = {
				"method": "processDisplayInfoForm",
				"title" : $("#title").val(),
				"author" : $("#author").val(),
				"seriesName" : $("#seriesName").val(),
				"seriesDisplayOrder" : $("#seriesDisplayOrder").val(),
				"description" : $("#description").val()
			}
			$.getJSON(url, params, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		deleteDisplayInfo: function(id) {
			var url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=deleteDisplayInfo";
			$.getJSON(url, function (data){
				if (data.success){
					$("#groupedWorkDisplayInfo").hide();
					AspenDiscovery.showMessage(data.title, data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		selectFileDownload: function( groupedWorkId, type) {
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX';
			var params = {
				method: 'showSelectDownloadForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		downloadSelectedFile: function () {
			var id = $('#id').val();
			var fileType = $('#fileType').val();
			var selectedFile = $('#selectedFile').val();
			if (fileType === 'RecordPDF'){
				window.location = Globals.path + '/GroupedWork/' + id + '/DownloadPDF?fileId=' + selectedFile;
			}else{
				window.location = Globals.path + '/GroupedWork/' + id + '/DownloadSupplementalFile?fileId=' + selectedFile;
			}
			return false;
		},

		selectFileToView: function( recordId, type) {
			var url = Globals.path + '/GroupedWork/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectFileToViewForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		viewSelectedFile: function () {
			var id = $('#id').val();
			var selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Files/' + selectedFile + '/ViewPDF';
			return false;
		},

		getLargeCover: function (groupedWorkId){
			var url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		initializeHorizontalFormatSwipers: function (workId) {
			var container = document.getElementById('slider-' + workId);
			AspenDiscovery.initializeHorizontalSwiper(container, function (slide) {
				var workId = slide.getAttribute('data-workId');
				var format = slide.getAttribute('data-format');
				var cleanedWorkId = slide.getAttribute('data-cleanedWorkId');
				AspenDiscovery.GroupedWork.showManifestation(workId, format, cleanedWorkId);
			});
		},

		initializeHorizontalSourceSwipers: function (workId) {
			var container = document.getElementById('variationsInfo_' + workId);
			AspenDiscovery.initializeHorizontalSwiper(container, function (slide) {
				var workId = slide.getAttribute('data-workId');
				var format = slide.getAttribute('data-format');
				var cleanedWorkId = slide.getAttribute('data-cleanedWorkId');
				var variationId = slide.getAttribute('data-variationId');
				AspenDiscovery.GroupedWork.showVariation(workId, format, variationId, cleanedWorkId);
			});
		},

		showManifestation: function(workId, format, cleanedWorkId) {
			let variationsInfoElement = $('#variationsInfo_' + workId);
			variationsInfoElement.hide();
			let activeManifestationInfoJSON = this.groupedWorks[cleanedWorkId][format];
			let activeManifestationInfo = JSON.parse(activeManifestationInfoJSON);
			// noinspection JSUnresolvedReference
			if (activeManifestationInfo.numVariations === 1) {
				const activeVariationInfo = Object.values(activeManifestationInfo.variations)[0];
				this.showVariation(workId, format, activeVariationInfo.databaseId, cleanedWorkId);
			}else{
				//Show variations swiper
				let variationSlider = $('#slider-variations-' + workId);
				variationsInfoElement.show();
				variationSlider.html('');
				var i = 0;
				$.each(activeManifestationInfo.variations, function () {
					var activeClass = (i === 0) ? ' active' : '';
					var variationButton = '<div role="option" tabindex="0" class="slider-slide horizontal-format-button slider-sm' + activeClass + '" data-workId="' + workId + '" data-variationid="' + this.databaseId + '" data-format="' + format + '" data-cleanedWorkId="' + cleanedWorkId + '">\n' +
						'<div>' +
						this.label + '<br/>' + this.groupedStatus +
						'</div>' +
						'</div>';
					variationSlider.append(variationButton);
					i++;
				});
				variationsInfoElement.show();
				//Show the first variation
				const activeVariationInfo = Object.values(activeManifestationInfo.variations)[0];
				this.showVariation(workId, format, activeVariationInfo.databaseId, cleanedWorkId);
				this.initializeHorizontalSourceSwipers(workId);
			}

			return false;
		},


		showVariation: function(workId, format, variationId, cleanedWorkId){
			let activeManifestationInfoJSON = this.groupedWorks[cleanedWorkId][format];
			let activeManifestationInfo = JSON.parse(activeManifestationInfoJSON);
			//let activeVariationInfo = activeManifestationInfo.variations[variationId];

			this.showEdition(workId, format, variationId, cleanedWorkId);

			//$("#variationInfo_" + workId).html(editionInfo);

			return false;
		},

		showEdition: function(workId, format, variationId) {
			$("#variationInfo_" + workId).html("Loading");
			var url = Globals.path + '/GroupedWork/' + workId + '/AJAX';
			let params = {
				'method': 'getHorizDisplayFormatEdition',
				'format':format,
				'variationId':variationId
			}
			$.getJSON(url, params, function (data){
				if (data.success) {
					$("#variationInfo_" + workId).html(data.message);
					AspenDiscovery.LazyCirculation.scanAndRefresh("#variationInfo_" + workId);
				} else {
					$("#variationInfo_" + workId).html("");
				}
			});

			return false;
		},

		showAllEditionsForVariation: function(workId, format, variationId) {
			$("#horizDisplayShowEditionsRow_" + workId + ' .horizDisplayShowEditionsBtn').hide();
			$("#horizDisplayShowEditionsRow_" + workId + ' .horizDisplayHideEditionsBtn').show();
			var url = Globals.path + '/GroupedWork/' + workId + '/AJAX';
			let params = {
				'method': 'getAllEditionsForVariation',
				'format':format,
				'variationId':variationId
			}
			$.getJSON(url, params, function (data){
				if (data.success) {
					$("#horizDisplayAllEditions_" + workId).html(data.message);
					AspenDiscovery.LazyCirculation.scanAndRefresh("#horizDisplayAllEditions_" + workId);
				} else {
					$("#horizDisplayAllEditions_" + workId).html("");
				}
			});

			return false;
		},
		hideAllEditionsForVariation: function (workId, format, variationId) {
			$("#horizDisplayShowEditionsRow_" + workId + ' .horizDisplayHideEditionsBtn').hide();
			$("#horizDisplayShowEditionsRow_" + workId + ' .horizDisplayShowEditionsBtn').show();
			$("#horizDisplayAllEditions_" + workId).html("");
			return false;
		},
		initializeHorizontalEditionSelectionSwipers: function () {
			var container = document.getElementById('slider-edition');
			AspenDiscovery.initializeHorizontalSwiper(container, function (slide) {
			});
		},
		checkEditions: function (volumeId, defaultRememberChoice) {
			var option = $('#selectedVolume option[value="' + volumeId + '"]');
			if (option.data('has-editions') === true) {
				var editionsJson = option.attr('data-editions');
				var editionOptions = editionsJson ? JSON.parse(editionsJson) : [];
				if (editionOptions && !Array.isArray(editionOptions)) {
					editionOptions = Object.values(editionOptions);
				}
				var html = '';
				var count = editionOptions.length;
				editionOptions.forEach(function (edition, idx) {
					var current = idx + 1;
					html += '<div role="option" tabindex="0" class="slider-slide horizontal-edition-option' + (idx === 0 ? ' active' : '') + '">';
					html += '<label for="editionOption' + idx + '">';
					html += '<div class="edition-radio"><input type="radio" name="selectedEdition" id="editionOption' + idx + '" value="' + edition.id + '" ' + (idx === 0 ? 'checked' : '') + '> ' + edition.label + '</div>';
					html += '<div class="edition-cover"><img src="' + edition.coverUrl + '" class="img-thumbnail" alt="Book Cover" role="presentation"></div>';
					html += '<div class="edition-data">' + edition.publicationDate + '. ' + edition.publisher + '. ' + edition.physical + '.<br/>' + edition.statusIndicator + '<br/><span>' + current + ' of ' + count + ' editions</span></div>';
					html += '</label></div>';
				});
				$('#slider-edition .slider-wrapper').html(html);
				AspenDiscovery.GroupedWork.initializeHorizontalEditionSelectionSwipers();
				if (defaultRememberChoice == 2) {
					$('#editionSelectionOptions').show();
					$('#editionSelectionSlider').show();
					$('#editionSelectionOptionRemember').show();
				} else {
					$('#editionSelectionOptions').hide();
					$('#editionSelectionSlider').hide();
					$('#editionSelectionOptionRemember').hide();
				}
			}
		},
		showEditionSwiper: function () {
			var option = $('#selectedEditionOption').val();
			if (option === '2') {
				$('#editionSelectionSlider').show();
				$('#editionSelectionOptionRemember').hide();
			} else {
				$('#editionSelectionSlider').hide();
				$('#editionSelectionOptionRemember').show();
			}
		}
	};
}(AspenDiscovery.GroupedWork || {}));