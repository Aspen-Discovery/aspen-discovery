AspenDiscovery.Account = (function () {

	// noinspection JSUnusedGlobalSymbols
	return {
		ajaxCallback: null,
		closeModalOnAjaxSuccess: false,
		showCovers: null,
		currentHoldSource: null,
		currentCheckoutsSource: null,

		addAccountLink: function () {
			var url = Globals.path + "/MyAccount/AJAX?method=getAddAccountLinkForm";
			AspenDiscovery.Account.ajaxLightbox(url, true);
		},

		/**
		 * Creates a new list in the system for the active user.
		 *
		 * Called from createListForm.tpl
		 * @returns {boolean}
		 */
		addList: function () {
			var form = $("#addListForm");
			var source = form.find("input[name=source]").val();
			var sourceId = form.find("input[name=sourceId]").val();
			var isPublic = form.find("#public").prop("checked");
			var isSearchable = false;
			var searchableControl = $("#searchable");
			if (searchableControl) {
				isSearchable = searchableControl.prop("checked");
			}
			var isDisplayListAuthor = false;
			var displayListAuthorControl = $("#displayListAuthor");
			if (displayListAuthorControl) {
				isDisplayListAuthor = displayListAuthorControl.prop("checked");
			}
			var titleInput = form.find("input[name=title]");
			var title;
			if (titleInput.length > 0){
				title = titleInput.val();
			}else{
				title = $('#listTitle option:selected').text();
			}

			var desc = $("#listDesc").val();
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				'method': 'addList',
				title: title,
				public: isPublic,
				searchable: isSearchable,
				displayListAuthor: isDisplayListAuthor,
				desc: desc,
				source: source,
				sourceId: sourceId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessage("Added Successfully", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		/**
		 * Do an ajax process, but only if the user is logged in.
		 * If the user is not logged in, force them to login and then do the process.
		 * Can also be called without the ajax callback to just login and not go anywhere
		 *
		 * @param trigger
		 * @param ajaxCallback
		 * @param closeModalOnAjaxSuccess
		 * @returns {boolean}
		 */
		ajaxLogin: function (trigger, ajaxCallback, closeModalOnAjaxSuccess) {
			if (Globals.loggedIn) {
				if (ajaxCallback !== undefined && typeof (ajaxCallback) === "function") {
					ajaxCallback();
				} else if (AspenDiscovery.Account.ajaxCallback != null && typeof (AspenDiscovery.Account.ajaxCallback) === "function") {
					AspenDiscovery.Account.ajaxCallback();
					AspenDiscovery.Account.ajaxCallback = null;
				}
			} else {
				if(Globals.bypassAspenLoginForSSO && Globals.ssoLoginUrl !== '') {
					window.location = Globals.ssoLoginUrl + '&followupModule=' + Globals.activeModule + '&followupAction=' + Globals.activeAction;
				} else {
					var multiStep = false;
					var loginLink = false;
					if (ajaxCallback !== undefined && typeof (ajaxCallback) === "function") {
						multiStep = true;
					}
					AspenDiscovery.Account.ajaxCallback = ajaxCallback;
					AspenDiscovery.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
					var dialogTitle = "Sign In";
					if (trigger !== undefined && trigger !== null) {
						dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
						loginLink = trigger.data('login');
					}
					var dialogDestination = Globals.path + '/MyAccount/AJAX?method=getLoginForm';
					if (multiStep && !loginLink) {
						dialogDestination += "&multiStep=true";
					}
					var modalDialog = $("#modalDialog");
					$('.modal-body').html("Loading...");
					$(".modal-content").load(dialogDestination);
					$(".modal-title").text(dialogTitle);
					modalDialog.removeClass('image-popup');
					modalDialog.modal("show");
				}
			}
			return false;
		},

		changeLinkedAccount: function () {
			var patronId = $("#patronId option:selected").val();
			document.location.href = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'patronId', patronId);
		},

		exportCheckouts: function (source, sort) {
			var url = Globals.path + "/MyAccount/AJAX?method=exportCheckouts&source=" + source;
			if (sort !== undefined) {
				url += "&sort=" + sort;
			}
			document.location.href = url;
			return false;
		},

		exportHolds: function (source, availableHoldsSort, unavailableHoldsSort) {
			var url = Globals.path + "/MyAccount/AJAX?method=exportHolds&source=" + source;
			if (availableHoldsSort !== undefined) {
				url += "&availableHoldsSort=" + availableHoldsSort;
			}
			if (unavailableHoldsSort !== undefined) {
				url += "&unavailableHoldsSort=" + unavailableHoldsSort;
			}
			document.location.href = url;
			return false;
		},

		followLinkIfLoggedIn: function (trigger, linkDestination) {
			if (trigger === undefined) {
				alert("You must provide the trigger to follow a link after logging in.");
			}
			var jqTrigger = $(trigger);
			if (linkDestination === undefined) {
				linkDestination = jqTrigger.attr("href");
			}
			this.ajaxLogin(jqTrigger, function () {
				document.location = linkDestination;
			}, true);
			return false;
		},

		//Force the current page to be reloaded from the source
		reloadCheckouts: function () {
			var source = 'all';
			if (AspenDiscovery.Account.currentCheckoutsSource != null) {
				source = AspenDiscovery.Account.currentCheckoutsSource;
			}
			document.body.style.cursor = "wait";
			var url = Globals.path + "/MyAccount/AJAX?method=getCheckouts&source=" + source + "&refreshCheckouts=true";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$('#accountLoadTime').html(data.checkoutInfoLastLoaded);
					$("#" + source + "CheckoutsPlaceholder").html(data.checkouts);
				} else {
					$("#" + source + "CheckoutsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadCheckouts: function (source, sort, showCovers) {
			AspenDiscovery.Account.currentCheckoutsSource = source;
			var url = Globals.path + "/MyAccount/AJAX?method=getCheckouts&source=" + source;
			if (sort !== undefined) {
				url += "&sort=" + sort;
			}
			if (showCovers !== undefined) {
				url += "&showCovers=" + showCovers;
			}
			var stateObj = {
				page: 'Checkouts',
				source: source,
				sort: sort,
				showCovers: showCovers
			};
			var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href) {
				var label = 'Checkouts';
				if (source === 'ils') {
					label = 'Physical Checkouts';
				} else if (source === 'overdrive') {
					label = 'OverDrive Checkouts';
				} else if (source === 'hoopla') {
					label = 'Hoopla Checkouts';
				} else if (source === 'cloud_library') {
					label = 'Cloud Library Checkouts';
				} else if (source === 'axis360') {
					label = 'Axis 360 Checkouts';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$('#accountLoadTime').html(data.checkoutInfoLastLoaded);
					$("#" + source + "CheckoutsPlaceholder").html(data.checkouts);
				} else {
					$("#" + source + "CheckoutsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		//Force the current page to be reloaded from the source
		reloadHolds: function () {
			var source = 'all';
			if (AspenDiscovery.Account.currentHoldSource != null) {
				source = AspenDiscovery.Account.currentHoldSource;
			}
			document.body.style.cursor = "wait";
			var url = Globals.path + "/MyAccount/AJAX?method=getHolds&source=" + source + "&refreshHolds=true";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$('#accountLoadTime').html(data.holdInfoLastLoaded);
					$("#" + source + "HoldsPlaceholder").html(data.holds);
				} else {
					$("#" + source + "HoldsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadHolds: function (source, availableHoldSort, unavailableHoldSort, showCovers) {
			AspenDiscovery.Account.currentHoldSource = source;
			var url = Globals.path + "/MyAccount/AJAX?method=getHolds&source=" + source;
			if (availableHoldSort !== undefined) {
				url += "&availableHoldSort=" + availableHoldSort;
			}
			if (unavailableHoldSort !== undefined) {
				url += "&unavailableHoldSort=" + unavailableHoldSort;
			}
			if (showCovers !== undefined) {
				url += "&showCovers=" + showCovers;
			}
			var stateObj = {
				page: 'Holds',
				source: source,
				availableHoldSort: availableHoldSort,
				unavailableHoldSort: unavailableHoldSort,
				showCovers: showCovers
			};
			var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href) {
				var label = 'Holds';
				if (source === 'ils') {
					label = 'Physical Holds';
				} else if (source === 'interlibrary_loan') {
					label = 'Interlibrary Loan Requests';
				} else if (source === 'overdrive') {
					label = 'OverDrive Holds';
				} else if (source === 'cloud_library') {
					label = 'Cloud Library Holds';
				} else if (source === 'axis360') {
					label = 'Axis 360 Holds';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$('#accountLoadTime').html(data.holdInfoLastLoaded);
					$("#" + source + "HoldsPlaceholder").html(data.holds);
				} else {
					$("#" + source + "HoldsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadReadingHistory: function (selectedUser, sort, page, showCovers, filter) {
			var url = Globals.path + "/MyAccount/AJAX?method=getReadingHistory&patronId=" + selectedUser;
			if (sort !== undefined) {
				url += "&sort=" + sort;
			}
			if (page !== undefined) {
				url += "&page=" + page;
			} else {
				page = 1;
			}
			if (showCovers !== undefined) {
				url += "&showCovers=" + showCovers;
			}
			if (filter !== undefined) {
				url += "&readingHistoryFilter=" + filter;
			}
			var stateObj = {
				page: 'ReadingHistory',
				pageNumber: page,
				selectedUser: selectedUser,
				sort: sort,
				showCovers: showCovers,
				readingHistoryFilter: filter
			};
			var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'selectedUser', selectedUser);
			newUrl = AspenDiscovery.buildUrl(newUrl, 'page', page);
			if (filter !== undefined) {
				newUrl = AspenDiscovery.buildUrl(newUrl, 'readingHistoryFilter', filter);
			}
			if (document.location.href) {
				var label = 'Reading History page '.page;
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$("#readingHistoryListPlaceholder").html(data.readingHistory);
				} else {
					$("#readingHistoryListPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadListData: function () {
			var url = Globals.path + "/MyAccount/AJAX?method=getListData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function (data) {
				$("#lists-placeholder").html(data.lists);
			});
			return false;
		},

		loadRatingsData: function () {
			var url = Globals.path + "/MyAccount/AJAX?method=getRatingsData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function (data) {
				$(".ratings-placeholder").html(data.ratings);
				$(".notInterested-placeholder").html(data.notInterested);
			});
			return false;
		},

		loadMenuData: function () {
			var totalCheckouts = 0;
			var totalHolds = 0;
			var totalFines = 0;
			if (Globals.hasILSConnection) {
				var ilsUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataIls&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(ilsUrl, function (data) {
					if (data.success) {
						// noinspection JSDeprecatedSymbols
						var summary = data.summary;
						$(".ils-checkouts-placeholder").html(summary.numCheckedOut);
						totalCheckouts += parseInt(summary.numCheckedOut);
						$(".checkouts-placeholder").html(totalCheckouts);
						if (summary.numOverdue > 0) {
							$(".ils-overdue-placeholder").html(summary.numOverdue);
							$(".ils-overdue").show();
						} else {
							$(".ils-overdue-placeholder").html("0");
						}
						$(".ils-holds-placeholder").html(summary.numHolds);
						totalHolds += parseInt(summary.numHolds);
						$(".holds-placeholder").html(totalHolds);
						if (summary.numAvailableHolds > 0) {
							$(".ils-available-holds-placeholder").html(summary.numAvailableHolds);
							$(".ils-available-holds").show();
						} else {
							$(".ils-available-holds-placeholder").html("0");
						}
						$(".readingHistory-placeholder").html(summary.readingHistory);
						if (summary.hasUpdatedSavedSearches) {
							$(".saved-searches-placeholder").html(summary.savedSearches);
							$(".newSavedSearchBadge").show();
						} else {
							$(".newSavedSearchBadge").hide();
						}

						$(".materialsRequests-placeholder").html(summary.materialsRequests);
						$(".expirationNotice-placeholder").html(summary.expirationNotice);
						$(".finesBadge-placeholder").html(summary.finesBadge);
					}
				});
			}
			if (Globals.hasCloudLibraryConnection) {
				var cloudLibraryUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataCloudLibrary&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(cloudLibraryUrl, function (data) {
					if (data.success) {
						$(".cloud_library-checkouts-placeholder").html(data.summary.numCheckedOut);
						totalCheckouts += parseInt(data.summary.numCheckedOut);
						$(".checkouts-placeholder").html(totalCheckouts);
						$(".cloud_library-holds-placeholder").html(data.summary.numHolds);
						totalHolds += parseInt(data.summary.numHolds);
						$(".holds-placeholder").html(totalHolds);
						if (data.summary.numAvailableHolds > 0) {
							$(".cloud_library-available-holds-placeholder").html(data.summary.numAvailableHolds);
							$(".cloud_library-available-holds").show();
						}
					}
				});
			}
			if (Globals.hasAxis360Connection) {
				var axis360Url = Globals.path + "/MyAccount/AJAX?method=getMenuDataAxis360&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(axis360Url, function (data) {
					if (data.success) {
						$(".axis360-checkouts-placeholder").html(data.summary.numCheckedOut);
						totalCheckouts += parseInt(data.summary.numCheckedOut);
						$(".checkouts-placeholder").html(totalCheckouts);
						$(".axis360-holds-placeholder").html(data.summary.numHolds);
						totalHolds += parseInt(data.summary.numHolds);
						$(".holds-placeholder").html(totalHolds);
						if (data.summary.numAvailableHolds > 0) {
							$(".axis360-available-holds-placeholder").html(data.summary.numAvailableHolds);
							$(".axis360-available-holds").show();
						}
					}
				});
			}
			if (Globals.hasHooplaConnection) {
				var hooplaUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataHoopla&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(hooplaUrl, function (data) {
					if (data.success) {
						$(".hoopla-checkouts-placeholder").html(data.summary.numCheckedOut);
						totalCheckouts += parseInt(data.summary.numCheckedOut);
						$(".checkouts-placeholder").html(totalCheckouts);
					}
				});
			}
			if (Globals.hasOverDriveConnection) {
				var overdriveUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataOverDrive&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(overdriveUrl, function (data) {
					if (data.success) {
						$(".overdrive-checkouts-placeholder").html(data.summary.numCheckedOut);
						totalCheckouts += parseInt(data.summary.numCheckedOut);
						$(".checkouts-placeholder").html(totalCheckouts);
						$(".overdrive-holds-placeholder").html(data.summary.numHolds);
						totalHolds += parseInt(data.summary.numHolds);
						$(".holds-placeholder").html(totalHolds);
						if (data.summary.numAvailableHolds > 0) {
							$(".overdrive-available-holds-placeholder").html(data.summary.numAvailableHolds);
							$(".overdrive-available-holds").show();
						}
					}
				});
			}
			if (Globals.hasInterlibraryLoanConnection) {
				var interlibraryLoanUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataInterlibraryLoan&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
				$.getJSON(interlibraryLoanUrl, function (data) {
					if (data.success) {
						$(".interlibrary-loan-requests-placeholder").html(data.summary.numHolds);
						totalHolds += parseInt(data.summary.numHolds);
						$(".holds-placeholder").html(totalHolds);
					}
				});
			}

			return false;
		},

		preProcessLogin: function () {
			var username = $("#username").val(),
				password = $("#password").val(),
				loginErrorElem = $('#loginError');
			if (!username || !password) {
				loginErrorElem
					.text($("#missingLoginPrompt").text())
					.show();
				return false;
			}
			if (AspenDiscovery.hasLocalStorage()) {
				var rememberMe = $("#rememberMe").prop('checked');
				var showPwd = $('#showPwd').prop('checked');
				if (rememberMe) {
					window.localStorage.setItem('lastUserName', username);
					window.localStorage.setItem('lastPwd', password);
					window.localStorage.setItem('showPwd', showPwd);
					window.localStorage.setItem('rememberMe', rememberMe);
				} else {
					window.localStorage.removeItem('lastUserName');
					window.localStorage.removeItem('lastPwd');
					window.localStorage.removeItem('showPwd');
					window.localStorage.removeItem('rememberMe');
				}
			}
			return true;
		},

		processAjaxLogin: function (ajaxCallback) {
			if (this.preProcessLogin()) {
				var username = $("#username").val();
				var password = $("#password").val();
				var rememberMe = $("#rememberMe").prop('checked');
				var loginErrorElem = $('#loginError');
				var loadingElem = $('#loading');
				var multiStep = $('#multiStep').val();
				var ldapLoginObj = $('#ldapLogin');
				if (ldapLoginObj !== undefined) {
					ldapLogin = ldapLoginObj.val()
				} else {
					ldapLogin = "";
				}
				var url = Globals.path + "/AJAX/JSON?method=loginUser";
				var params = {username: username, password: password, rememberMe: rememberMe, ldapLogin: ldapLogin};
				if (!Globals.opac && AspenDiscovery.hasLocalStorage()) {
					var showCovers = window.localStorage.getItem('showCovers') || false;
					if (showCovers && showCovers.length > 0) { // if there is a set value, pass it back with the login info
						params.showCovers = showCovers
					}
				}
				var module = Globals.activeModule;
				var action = Globals.activeAction;

				var referer;
				if ((module === "WebBuilder") && ((action === "BasicPage") || (action === "PortalPage"))) {
					referer = "/MyAccount/Home";
				} else if ((module === "Search") && (action === "Home")) {
					referer = "/MyAccount/Home";
				} else if ((module === "MyAccount") && (action === "InitiateResetPin" || action === 'CompletePinReset' || action === 'EmailResetPin') || (action === "SelfReg")) {
					referer = "/MyAccount/Home";
				} else {
					referer = window.location;
				}

				loginErrorElem.hide();
				loadingElem.show();
				// noinspection JSUnresolvedFunction
				$.post(url, params, function (response) {
					if (response.result.success === true) {
						loadingElem.hide();
						$('#loginLinkIcon').removeClass('fa-sign-in-alt').addClass('fa-user');
						$('#login-button-label').html(response.result.name);
						$('#logoutLink').show();

						if (AspenDiscovery.Account.closeModalOnAjaxSuccess) {
							AspenDiscovery.closeLightbox();
						}

						Globals.loggedIn = true;
						if (ajaxCallback !== undefined && typeof (ajaxCallback) === "function") {
							ajaxCallback();
						} else if (AspenDiscovery.Account.ajaxCallback !== undefined && typeof (AspenDiscovery.Account.ajaxCallback) === "function") {
							AspenDiscovery.Account.ajaxCallback();
							AspenDiscovery.Account.ajaxCallback = null;
						}
						if (multiStep !== 'true') {
							window.location.replace(referer);
						} else {
							$('.modal-body').html("Loading...");
							$(".modal-title").text("Loading...");
						}
					} else if (response.result.success === false && response.result.passwordExpired === true) {
						AspenDiscovery.showMessageWithButtons(response.result.title, response.result.body, response.result.buttons);
						$('#resetPin').validate();
					} else if (response.result.success === false && response.result.enroll2FA === true) {
						AspenDiscovery.showMessageWithButtons('Error', 'Your patron type requires that you enroll into two-factor authentication before logging in.', '<button class=\'tool btn btn-primary\' onclick=\'AspenDiscovery.Account.show2FAEnrollment(true); return false;\'>Continue</button>');
					} else if (response.result.success === false && response.result.has2FA === true) {
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=auth2FALogin&referer=" + referer + "&name=" + response.result.name, function (data) {
							if (data.success) {
								AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
							}
						});
					} else {
						loginErrorElem.html(response.result.message).show();
					}
				}, 'json').fail(function () {
					loginErrorElem.text("There was an error processing your login, please try again.").show();
				})
			}
			return false;
		},

		processAddLinkedUser: function () {
			if (this.preProcessLogin()) {
				var username = $("#username").val();
				var password = $("#password").val();
				var loginErrorElem = $('#loginError');
				var url = Globals.path + "/MyAccount/AJAX?method=addAccountLink";
				loginErrorElem.hide();
				$.ajax({
					url: url,
					data: {username: username, password: password},
					success: function (response) {
						if (response.success === true) {
							AspenDiscovery.showMessage(response.title, response.message, true, response.success);
						} else {
							loginErrorElem.html(response.message);
							loginErrorElem.show();
						}
					},
					error: function () {
						loginErrorElem.text("There was an error processing the account, please try again.").show();
					},
					dataType: 'json',
					type: 'post'
				});
			}
			return false;
		},


		removeLinkedUser: function (idToRemove) {
			if (confirm("Are you sure you want to stop managing this account?")) {
				var url = Globals.path + "/MyAccount/AJAX?method=removeAccountLink&idToRemove=" + idToRemove;
				$.getJSON(url, function (data) {
					if (data.success === true) {
						AspenDiscovery.showMessage(data.title, data.message, true, true);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			}
			return false;
		},

		removeManagingAccount: function (idToRemove) {
			if (confirm("Are you sure you want to break the link with this account?")) {
				var url = Globals.path + "/MyAccount/AJAX?method=removeManagingAccount&idToRemove=" + idToRemove;
				$.getJSON(url, function (data) {
					if (data.success === true) {
						AspenDiscovery.showMessageWithButtons('Linked Account Removed', data.message, data.modalButtons, true);
					} else {
						AspenDiscovery.showMessage('Unable to Remove Account Link', data.message);
					}
				});
			}
			return false;
		},

		//CALL FOR ON CLICK, INITIAL MODAL POPUP
		disableAccountLinkingPopup: function () {
			var url = Globals.path + "/MyAccount/AJAX?method=disableAccountLinkingInfo";
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		//CALL FOR HITTING ACCEPT ON POPUP - GOES TO TOGGLEACCOUNTLINKING AJAX
		toggleAccountLinkingAccept: function() {
			var url = Globals.path + "/MyAccount/AJAX?method=toggleAccountLinking";
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons, data.success);
			});
			return false;
		},

		allowAccountLink: function() {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "allowAccountLink"
			};
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessage(data.title, data.message, data.success, data.success);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		redirectLinkedAccounts: function() {
			window.location.href = Globals.path + "/MyAccount/LinkedAccounts";
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "allowAccountLink" //dismisses "confirm_linked_accts" message but we won't display "link accepted" message
			};
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		redirectPinReset: function() {
			window.location.href = Globals.path + "/MyAccount/ResetPinPage";
		},

		renewTitle: function (patronId, recordId, renewIndicator) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId + "&renewIndicator=" + renewIndicator, function (data) {
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success); // automatically close when successful
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					this.renewTitle(renewIndicator);
				}, false)
			}
			return false;
		},

		renewAll: function () {
			if (Globals.loggedIn) {
				if (confirm('Renew All Items?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewAll", function (data) {
						var reload = data.success || (data.renewed > 0);
						AspenDiscovery.showMessage(data.title, data.modalBody, reload, reload);
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, this.renewAll, true);
				//auto close so that if user opts out of renew, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false;
		},

		renewSelectedTitles: function () {
			if (Globals.loggedIn) {
				var selectedTitles = AspenDiscovery.getSelectedTitles();
				if (selectedTitles) {
					if (confirm('Renew selected Items?')) {
						AspenDiscovery.loadingMessage();
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewSelectedItems&" + selectedTitles, function (data) {
							var reload = data.success || (data.renewed > 0);
							AspenDiscovery.showMessage(data.title, data.modalBody, reload, reload);
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.renewSelectedTitles, true);
				//auto close so that if user opts out of renew, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false
		},

		ajaxLightbox: function (urlToDisplay, requireLogin) {
			if (requireLogin === undefined) {
				requireLogin = false;
			}
			if (requireLogin && !Globals.loggedIn) {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Account.ajaxLightbox(urlToDisplay, requireLogin);
				}, false);
			} else {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(urlToDisplay, function (data) {
					if (data.success) {
						data = data.result;
					}
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		confirmCancelHold: function (patronId, recordId, holdIdToCancel, isIll) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=confirmCancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId=" + holdIdToCancel + "&isIll=" + isIll, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons); // automatically close when successful
			}).fail(AspenDiscovery.ajaxFail);

			return false
		},

		cancelHold: function (patronId, recordId, holdIdToCancel, isIll) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId=" + holdIdToCancel + "&isIll=" + isIll, function (data) {
					AspenDiscovery.showMessage(data.title, data.body, data.success);
					if (data.success) {
						var tmpRecordId = recordId.replace('.', '_').replace('~', '_');
						var tmpHoldIdToCancel = holdIdToCancel.replace('.', '_').replace('~', '_').replace(' ', '_').replace(':', '_');
						var holdClass = '.ilsHold_' + tmpRecordId + '_' + tmpHoldIdToCancel;
						$(holdClass).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Cancelling hold failed", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					AspenDiscovery.Account.cancelHold(patronId, recordId, holdIdToCancel, isIll)
				}, false);
			}
			//AspenDiscovery.Account.reloadHolds();

			return false
		},

		cancelHoldSelectedTitles: function (patronId, recordId, holdIdToCancel, caller) {
			if (Globals.loggedIn) {
				var selectedTitles = AspenDiscovery.getSelectedTitles();
				var popUpBoxTitle = $(caller).text() || "Canceling Holds";
				if (selectedTitles) {
					if (confirm('Cancel selected holds?')) {
						AspenDiscovery.loadingMessage();
						AspenDiscovery.showMessage(popUpBoxTitle, "Updating your holds.  This may take a minute.");
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHoldSelectedItems&" + selectedTitles, function (data) {
							if (data.success) {
								AspenDiscovery.Account.reloadHolds();
								AspenDiscovery.showMessage("Success", data.message, true, false);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.cancelHoldSelectedTitles, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			AspenDiscovery.Account.reloadHolds();
			return false
		},

		cancelHoldAll: function (caller) {
			if (Globals.loggedIn) {
				var popUpBoxTitle = $(caller).text() || "Canceling Holds";
				if (confirm('Cancel all holds?')) {
					AspenDiscovery.loadingMessage();
					AspenDiscovery.showMessage(popUpBoxTitle, "Updating your holds.  This may take a minute.");
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelAllHolds", function (data) {
						if (data.success) {
							AspenDiscovery.Account.reloadHolds();
							AspenDiscovery.showMessage("Success", data.message, true, false);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, this.cancelHoldAll, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			AspenDiscovery.Account.reloadHolds();
			return false;
		},

		cancelVdxRequest: function (patronId, requestId, cancelId) {
			if (confirm("Are you sure you want to cancel this request?")) {
				var ajaxUrl = Globals.path + "/MyAccount/AJAX?method=cancelVdxRequest&patronId=" + patronId + "&requestId=" + requestId + "&cancelId=" + cancelId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success) {
							AspenDiscovery.showMessage("Request Cancelled", data.message, true);
							//remove the row from the holds list
							$("#vdxHold_" + requestId + "_" + cancelId).hide();
							AspenDiscovery.Account.loadMenuData();
						} else {
							AspenDiscovery.showMessage("Error Cancelling Request", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						AspenDiscovery.showMessage("Error Cancelling Request", "An error occurred processing your request.  Please try again in a few minutes.", false);
					}
				});
			}
			return false;
		},

		changeAccountSort: function (newSort, sortParameterName) {
			if (typeof sortParameterName === 'undefined') {
				sortParameterName = 'accountSort'
			}
			var paramString = AspenDiscovery.replaceQueryParam(sortParameterName, newSort);
			location.replace(location.pathname + paramString)
		},

		changeHoldPickupLocation: function (patronId, recordId, holdId, currentLocation, source) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getChangeHoldLocationForm&patronId=" + patronId + "&recordId=" + recordId + "&holdId=" + holdId + "&currentLocation=" + currentLocation + "&source=" + source, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.changeHoldPickupLocation(patronId, recordId, holdId, currentLocation);
				}, false);
			}
			return false;
		},

		deleteSearch: function (searchId) {
			if (!Globals.loggedIn) {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Searches.saveSearch(searchId);
				}, false);
			} else {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = "method=deleteSearch&searchId=" + encodeURIComponent(searchId);
				$.getJSON(url + '?' + params,
					function (data) {
						if (data.result) {
							AspenDiscovery.showMessage("Success", data.message);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
				);
			}
			return false;
		},

		doChangeHoldLocation: function () {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				'method': 'changeHoldLocation'
				, patronId: $('#patronId').val()
				, recordId: $('#recordId').val()
				, holdId: $('#holdId').val()
				, newLocation: $('#newPickupLocation').val()
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		freezeHold: function (patronId, recordId, holdId, promptForReactivationDate, caller) {
			AspenDiscovery.loadingMessage();
			var url = Globals.path + '/MyAccount/AJAX';
			var params = {
				patronId: patronId
				, recordId: recordId
				, holdId: holdId
			};
			if (promptForReactivationDate) {
				//Prompt the user for the date they want to reactivate the hold
				params['method'] = 'getReactivationDateForm'; // set method for this form
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				}).fail(AspenDiscovery.ajaxFail);

			} else {
				var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
				params['method'] = 'freezeHold'; //set method for this ajax call
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.Account.reloadHolds();
						AspenDiscovery.showMessage("Success", data.message, true, true);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function (caller) {
			var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			var params = {
				'method': 'freezeHold'
				, patronId: $('#patronId').val()
				, recordId: $('#recordId').val()
				, holdId: $("#holdId").val()
				, reactivationDate: $("#reactivationDate").val()
			};
			var url = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		freezeHoldSelected: function (patronId, recordId, holdId, caller) {
			if (Globals.loggedIn) {
				var selectedTitles = AspenDiscovery.getSelectedTitles();
				var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				if (selectedTitles) {
					if (confirm('Freeze selected holds?')) {
						AspenDiscovery.loadingMessage();
						AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=freezeHoldSelectedItems&" + selectedTitles, function (data) {
							if (data.success) {
								AspenDiscovery.Account.reloadHolds();
								AspenDiscovery.showMessage("Success", data.message, true, false);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.freezeHoldSelected, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false
		},

		freezeHoldAll: function (userId, caller) {
			if (Globals.loggedIn) {
				var popUpBoxTitle = $(caller).text() || "Freezing Holds";
				if (confirm('Freeze all holds?')) {
					AspenDiscovery.loadingMessage();
					AspenDiscovery.showMessage(popUpBoxTitle, "Freezing your holds.  This may take a minute.");
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=freezeHoldAll&patronId=" + userId, function (data) {
						if (data.success) {
							AspenDiscovery.Account.reloadHolds();
							AspenDiscovery.showMessage("Success", data.message, true, false);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, this.freezeHoldAll, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false;
		},

		thawHoldSelected: function (patronId, recordId, holdId, caller) {
			if (Globals.loggedIn) {
				var selectedTitles = AspenDiscovery.getSelectedTitles();
				var popUpBoxTitle = $(caller).text() || "Thawing Hold";
				if (selectedTitles) {
					if (confirm('Thaw selected holds?')) {
						AspenDiscovery.loadingMessage();
						AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=thawHoldSelectedItems&" + selectedTitles, function (data) {
							if (data.success) {
								AspenDiscovery.Account.reloadHolds();
								AspenDiscovery.showMessage("Success", data.message, true, false);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.thawHoldSelected, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false
		},

		thawHoldAll: function (userId, caller) {
			if (Globals.loggedIn) {
				var popUpBoxTitle = $(caller).text() || "Thawing Holds";
				if (confirm('Thaw all holds?')) {
					AspenDiscovery.loadingMessage();
					AspenDiscovery.showMessage(popUpBoxTitle, "Thawing your holds.  This may take a minute.");
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=thawHoldAll&patronId=" + userId, function (data) {
						if (data.success) {
							AspenDiscovery.Account.reloadHolds();
							AspenDiscovery.showMessage("Success", data.message, true, false);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, this.thawHoldAll, true);
				//auto close so that if user opts out of canceling, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false;
		},

		getSelectedTitles: function (promptForSelectAll) {
			if (promptForSelectAll === undefined) {
				promptForSelectAll = true;
			}
			var selectedTitles = $("input.titleSelect:checked ");
			if (selectedTitles.length === 0 && promptForSelectAll && confirm('You have not selected any items, process all items?')) {
				selectedTitles = $("input.titleSelect")
					.attr('checked', 'checked');
			}
			// noinspection UnnecessaryLocalVariableJS
			var queryString = selectedTitles.map(function () {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");

			return queryString;
		},
		getSelectedLists: function (promptForSelectAll) {
			var selectedLists = $("input.listSelect:checked ");
			// noinspection UnnecessaryLocalVariableJS
			var queryString = selectedLists.map(function () {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");

			return queryString;
		},

		saveSearch: function (searchId) {
			if (!Globals.loggedIn) {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Account.saveSearch(searchId);
				}, false);
			} else {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					'method': 'saveSearch',
					'searchId': $('#searchId').val(),
					'title': $('#searchName').val()
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params,
					function (data) {
						if (data.success) {
							AspenDiscovery.showMessage("Saved Successfully", data.message, data.modalButtons);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		showSearchToolbar: function (displayMode, showCovers, rssLink, excelLink, searchId, sortList) {
			var url = Globals.path + "/Search/AJAX";
			var params = {
				method: 'showSearchToolbar',
				displayMode: AspenDiscovery.Searches.displayMode,
				showCovers: showCovers,
				rssLink: rssLink,
				excelLink: excelLink,
				searchId: searchId,
				sortList: sortList
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessage(data.title, data.modalBody, false);
			}).fail(AspenDiscovery.ajaxFail);
		},

		showEmailSearchForm: function () {
			if (Globals.loggedIn) {
				var url = Globals.path + "/Search/AJAX";
				var params = {
					method: 'getEmailForm'
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.showEmailSearchForm();
				}, false);
			}

			return false;
		},

		showSaveSearchForm: function (searchId) {
			if (Globals.loggedIn) {
				$('#searchToolsModal').modal('hide');
				AspenDiscovery.loadingMessage();
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					method: 'getSaveSearchForm',
					searchId: searchId
				};

				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.showSaveSearchForm(searchId);
				}, false);
			}
			return false;
		},

		showCreateListForm: function (source, sourceId) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {method: "getCreateListForm"};
				if (source !== undefined) {
					params.source = source;
				}
				if (sourceId !== undefined) {
					params.sourceId = sourceId;
				}
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin($trigger, function () {
					return AspenDiscovery.Account.showCreateListForm(source, sourceId);
				}, false);
			}
			return false;
		},

		thawHold: function (patronId, recordId, holdId, caller) {
			var popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			var url = Globals.path + '/MyAccount/AJAX';
			var params = {
				'method': 'thawHold'
				, patronId: patronId
				, recordId: recordId
				, holdId: holdId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.Account.reloadHolds();
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		toggleShowCovers: function (showCovers) {
			this.showCovers = showCovers;
			var paramString = AspenDiscovery.replaceQueryParam('showCovers', this.showCovers ? 'on' : 'off'); // set variable
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
				window.localStorage.setItem('showCovers', this.showCovers ? 'on' : 'off');
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		validateCookies: function () {
			if (navigator.cookieEnabled === false) {
				$("#cookiesError").show();
			}
		},

		getMasqueradeForm: function () {
			AspenDiscovery.loadingMessage();
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {method: "getMasqueradeAsForm"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initiateMasquerade: function () {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "initiateMasquerade",
				cardNumber: $('#cardNumber').val()
			};
			$('#masqueradeAsError').hide();
			$('#masqueradeLoading').show();
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					location.href = Globals.path + '/MyAccount/Home';
				} else {
					$('#masqueradeLoading').hide();
					$('#masqueradeAsError').html(data.error).show();
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		endMasquerade: function () {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {method: "endMasquerade"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).done(function () {
				location.href = Globals.path + '/MyAccount/Home';
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		dismissMessage: function (messageId) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "dismissMessage",
				messageId: messageId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		dismissSystemMessage: function (messageId) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "dismissSystemMessage",
				messageId: messageId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		createGenericOrder: function (finesFormId, paymentType, transactionType, token) {
			var url = Globals.path + "/MyAccount/AJAX";

			var params = {
				method: "create" + paymentType + "Order",
				patronId: $(finesFormId + " input[name=patronId]").val(),
				type: transactionType
			};
			if (token) {
				params.token = token;
			}
			if (transactionType === 'donation') {

				if ($(finesFormId + " input[name=customAmount]").val()) {
					params.amount = $(finesFormId + " input[name=customAmount]").val();
				} else {
					params.amount = $(finesFormId + " input[name=predefinedAmount]:checked").val();
				}
				params.earmark = $(finesFormId + " select[name=earmark]").val();
				params.toLocation = $(finesFormId + " select[name=toLocation]").val();
				params.isDedicated = $(finesFormId + " input[name=shouldBeDedicated]:checked").val();
				params.dedicationType = $(finesFormId + " input[name=dedicationType]:checked").val();
				params.honoreeFirstName = $(finesFormId + " input[name=honoreeFirstName]").val();
				params.honoreeLastName = $(finesFormId + " input[name=honoreeLastName]").val();
				params.shouldBeNotified = $(finesFormId + " input[name=shouldBeNotified]:checked").val();
				params.notificationFirstName = $(finesFormId + " input[name=notificationFirstName]").val();
				params.notificationLastName = $(finesFormId + " input[name=notificationLastName]").val();
				params.notificationAddress = $(finesFormId + " input[name=notificationAddress]").val();
				params.notificationCity = $(finesFormId + " input[name=notificationCity]").val();
				params.notificationState = $(finesFormId + " input[name=notificationState]").val();
				params.notificationZip = $(finesFormId + " input[name=notificationZip]").val();
				params.firstName = $(finesFormId + " input[name=firstName]").val();
				params.lastName = $(finesFormId + " input[name=lastName]").val();
				params.isAnonymous = $(finesFormId + " input[name=makeAnonymous]:checked").val();
				params.emailAddress = $(finesFormId + " input[name=emailAddress]").val();
				params.settingId = $(finesFormId + " input[name=settingId]").val();
			}

			if(paymentType === 'PayPalPayflow') {
				params.billingFirstName = $(finesFormId + " input[name=billingFirstName]").val();
				params.billingLastName = $(finesFormId + " input[name=billingLastName]").val();
				params.billingAddress = $(finesFormId + " input[name=billingStreet]").val();
				params.billingCity = $(finesFormId + " input[name=billingCity]").val();
				params.billingState = $(finesFormId + " input[name=billingState]").val();
				params.billingZip = $(finesFormId + " input[name=billingZip]").val();
			}

			$(finesFormId + " .selectedFine:checked").each(
				function () {
					var name = $(this).attr('name');
					params[name] = $(this).val();

					var fineAmount = $(finesFormId + " #amountToPay" + $(this).data("fine_id"));
					if (fineAmount) {
						params[fineAmount.attr('name')] = fineAmount.val();
					}
				}
			);
			var orderInfo = false;
			// noinspection JSUnresolvedFunction
			$.ajax({
				url: url,
				data: params,
				dataType: 'json',
				async: false,
				method: 'GET'
			}).success(
				function (response) {
					if (response.success === false) {
						AspenDiscovery.showMessage("Error", response.message);
						return false;
					} else {
						if (paymentType === 'PayPal') {
							orderInfo = response.orderID;
						} else if (paymentType === 'MSB') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'Comprise') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'ProPay') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'XpressPay') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'WorldPay') {
							orderInfo = response.paymentId;
						} else if (paymentType === 'ACI') {
							orderInfo = response.paymentId;
						} else if (paymentType === 'InvoiceCloud') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'CertifiedPaymentsByDeluxe') {
							orderInfo = response.paymentRequestUrl;
						} else if (paymentType === 'PayPalPayflow') {
							orderInfo = response.paymentIframe;
						} else if (paymentType === 'Square') {
							orderInfo = response.paymentId;
						}
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return orderInfo;
		},

		createMSBOrder: function (finesFormId, transactionType) {
			var url = this.createGenericOrder(finesFormId, 'MSB', transactionType, null);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
			}
		},

		createPayPalOrder: function (finesFormId, transactionType) {
			return this.createGenericOrder(finesFormId, 'PayPal', transactionType, null);
		},

		createWorldPayOrder: function (finesFormId, transactionType) {
			return this.createGenericOrder(finesFormId, 'WorldPay', transactionType, null);
		},

		createCompriseOrder: function (finesFormId, transactionType) {
			var url = this.createGenericOrder(finesFormId, 'Comprise', transactionType, null);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
			}
		},

		createProPayOrder: function (finesFormId, transactionType) {
			var url = this.createGenericOrder(finesFormId, 'ProPay', transactionType, null);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
			}
		},

		createXpressPayOrder: function (finesFormId, transactionType) {
			var url = this.createGenericOrder(finesFormId, 'XpressPay', transactionType, null);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
				$(".ils-available-holds-placeholder").html(summary.numAvailableHolds);
				$(".ils-available-holds").show();
			}
		},

		createACIOrder: function (finesFormId, transactionType, token) {
			return this.createGenericOrder(finesFormId, 'ACI', transactionType, token)
		},

		completeACIOrder: function (fundingToken, patronId, transactionType, paymentId, accessToken) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "completeACIOrder",
				patronId: patronId,
				paymentId: paymentId,
				accessToken: accessToken,
				fundingToken: fundingToken,
				type: transactionType
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCompleted?type=aciSpeedpay&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						AspenDiscovery.showMessage('Thank you', 'Your payment was processed successfully, thank you', false, true);
					}
				} else {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCancelled?type=aciSpeedpay&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						var message;
						if (data.message) {
							message = data.message;
						} else {
							message = 'Unable to process your payment, please visit the library with your receipt';
						}
						AspenDiscovery.showMessage('Error', message, false);
					}
				}
			}).fail(AspenDiscovery.ajaxFail);
		},
		handleACIError: function (error) {
			AspenDiscovery.showMessage('Error', 'There was an error completing your payment. ' + error, false);
		},
		createInvoiceCloudOrder: function (finesFormId, transactionType) {
			var url = this.createGenericOrder(finesFormId, 'InvoiceCloud', transactionType, null);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
			}
		},

		createCertifiedPaymentsByDeluxeOrder: function (finesFormId, transactionType, remittanceId) {
			var url = this.createGenericOrder(finesFormId, 'CertifiedPaymentsByDeluxe', transactionType, remittanceId);
			if (url === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				window.location.href = url;
			}
		},

		createSquareOrder: function (finesFormId, transactionType, token) {
			this.createGenericOrder(finesFormId, 'Square', transactionType, token);
		},

		createPayPalPayflowOrder: function (userId, transactionType) {
			var result = this.createGenericOrder('#fines' + userId, 'PayPalPayflow', transactionType);
			if (result === false) {
				// Do nothing; there was an error that should be displayed
			} else {
				$("#myModalLabel").html('Pay with PayPal');
				$(".modal-body").html(result);
				$('.modal-buttons').html('');
				$('.modal-dialog').addClass('paymentModal');
				$("#modalDialog").modal('show');
				$("#modalDialog").on('hide.bs.modal', function(){
					location.reload();
				})
			}
		},

		completePayPalOrder: function (orderId, patronId, transactionType) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "completePayPalOrder",
				patronId: patronId,
				orderId: orderId,
				type: transactionType
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCompleted?type=paypal&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						AspenDiscovery.showMessage('Thank you', data.message, false, true);
					}
				} else {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCancelled?type=paypal&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						var message;
						if (data.message) {
							message = data.message;
						} else {
							message = 'Unable to process your payment, please visit the library with your receipt';
						}
						AspenDiscovery.showMessage('Error', message, false);
					}
				}
			}).fail(AspenDiscovery.ajaxFail);
		},
		handlePayPalError: function (error) {
			AspenDiscovery.showMessage('Error', 'There was an error completing your payment. ' + error, true);
		},
		cancelPayPalError: function () {
			AspenDiscovery.showMessage('Payment cancelled', 'Your payment has successfully been cancelled.', true);
		},

		completeSquareOrder: function (patronId, transactionType, token) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "completeSquareOrder",
				patronId: patronId,
				type: transactionType,
				token: token,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCompleted?type=square&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						AspenDiscovery.showMessage('Thank you', data.message, false, true);
					}
				} else {
					if (data.isDonation) {
						window.location.href = Globals.path + '/Donations/DonationCancelled?type=square&payment=' + data.paymentId + '&donation=' + data.donationId;
					} else {
						var message;
						if (data.message) {
							message = data.message;
						} else {
							message = 'Unable to process your payment, please visit the library with your receipt';
						}
						AspenDiscovery.showMessage('Error', message, false);
					}
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		updateFineTotal: function (finesFormId, userId, paymentType) {
			var totalFineAmt = 0;
			var totalOutstandingAmt = 0;
			var outstandingGrandTotalAmt = 0;
			$(finesFormId + " .selectedFine:checked").each(
				function () {
					if (paymentType === "1") {
						totalFineAmt += $(this).data('fine_amt') * 1;
						totalOutstandingAmt += $(this).data('outstanding_amt') * 1;
						outstandingGrandTotalAmt += $(this).data('outstanding_amt') * 1;
					} else {
						var fineId = $(this).data('fine_id');
						var fineAmountInput = $("#amountToPay" + fineId);
						totalFineAmt += fineAmountInput.val() * 1;
						totalOutstandingAmt += fineAmountInput.val() * 1;
						outstandingGrandTotalAmt += fineAmountInput.val() * 1;
					}
				}
			);

			var feeAmt = document.getElementById('convenienceFee').getAttribute('data-fee_amt');
			outstandingGrandTotalAmt += feeAmt * 1;

			AspenDiscovery.formatCurrency(totalFineAmt, $('#formattedTotal' + userId));
			AspenDiscovery.formatCurrency(totalOutstandingAmt, $('#formattedOutstandingTotal' + userId));
			AspenDiscovery.formatCurrency(outstandingGrandTotalAmt, $('#outstandingGrandTotal' + userId));
		},
		dismissPlacard: function (patronId, placardId) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "dismissPlacard",
				placardId: placardId,
				patronId: patronId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					$("#placard" + placardId).hide();
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		dismissBrowseCategory: function (patronId, browseCategoryId) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "dismissBrowseCategory",
				browseCategoryId: browseCategoryId || AspenDiscovery.Browse.curCategory,
				patronId: patronId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					window.location = window.location.href.split("?")[0];
				} else {
					AspenDiscovery.showMessage('Error', data.message, false, true);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		showHiddenBrowseCategories: function (patronId) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					method: "getHiddenBrowseCategories",
					patronId: patronId
				};

				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},
		showBrowseCategory: function () {
			if (Globals.loggedIn) {
				var selectedCategories = AspenDiscovery.getSelectedBrowseCategories();
				var patronId = $("#updateBrowseCategories input[name=patronId]").val();
				if (selectedCategories) {
					$.getJSON(Globals.path + '/MyAccount/AJAX?method=showBrowseCategory&patronId=' + patronId + '&' + selectedCategories, function () {
						location.reload();
					});
				}
			} else {
				this.ajaxLogin(null, this.showBrowseCategory, true);
			}
			return false
		},
		updateAutoRenewal: function (patronId) {
			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: "updateAutoRenewal",
				allowAutoRenewal: $('#allowAutoRenewal').prop("checked"),
				patronId: patronId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessage('Success', data.message, true);
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		updateSelfRegistrationFields: function () {
			var cardTypeSelect = $('#cardTypeSelect');
			if (cardTypeSelect) {
				var selectedValue = $('#cardTypeSelect option:selected').val();
				if (selectedValue === 'adult') {
					$('#propertyRowparentName').hide();
				} else {
					$('#propertyRowparentName').show();
				}
			}
			var smsNotices = $("#smsNotices");
			if (smsNotices) {
				if (smsNotices.prop("checked")) {
					$('#propertyRowcellPhone').show();
				} else {
					$('#propertyRowcellPhone').hide();
				}
			}
		},

		saveEvent: function (trigger, source, id) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					'method': 'saveEvent',
					sourceId: id,
					source: source
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, 2000, true); // auto-close after 2 seconds.
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.saveEvent(trigger, source, id);
				}, false);
			}
			return false;
		},

		regInfoModal: function (trigger, source, id, body, regLink) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					'method': 'eventRegistrationModal',
					sourceId: id,
					source: source,
					body: body,
					regLink: regLink,
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, body, data.buttons, false);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.regInfoModal(trigger, source, id);
				}, false);
			}
			return false;
		},

		deleteSavedEvent: function(id, page, filter){
			if (confirm("Are you sure you want to remove this event?")){
				var url = Globals.path + '/MyAccount/AJAX?method=deleteSavedEvent&id=' + id ;

				$.getJSON(url, function(data){
					if (data.result === true){
						AspenDiscovery.showMessage('Success', data.message, false);
						AspenDiscovery.Account.loadEvents(page, filter);
					}else{
						AspenDiscovery.showMessage('Sorry', data.message);
					}
				});
			}
			return false;
		},

		loadEvents: function (page, filter) {
			var url = Globals.path + "/MyAccount/AJAX?method=getSavedEvents";
			if (page !== undefined) {
				url += "&page=" + page;
			} else {
				page = 1;
			}
			if (filter !== undefined) {
				url += "&eventsFilter=" + filter;
			}
			var stateObj = {
				page: 'MyEvents',
				pageNumber: page,
				readingHistoryFilter: filter
			};
			var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'page', page);
			if (filter !== undefined) {
				newUrl = AspenDiscovery.buildUrl(newUrl, 'eventsFilter', filter);
			}
			if (document.location.href) {
				var label = 'My Events page '.page;
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function (data) {
				document.body.style.cursor = "default";
				if (data.success) {
					$("#myEventsPlaceholder").html(data.myEvents);
				} else {
					$("#myEventsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showSaveToListForm: function (trigger, source, id) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					method: "getSaveToListForm",
					sourceId: id,
					source: source
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.showSaveToListForm(trigger, source, id);
				}, false);
			}
			return false;
		},

		saveToList: function () {
			if (Globals.loggedIn) {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					'method': 'saveToList',
					'notes': $('#addToList-notes').val(),
					'listId': $('#addToList-list').val(),
					'source': $('#source').val(),
					'sourceId': $('#sourceId').val()
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Added Successfully", data.message, 2000); // auto-close after 2 seconds.
						AspenDiscovery.Account.loadListData();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},
		deleteAll: function (id) {
			if (confirm("Are you sure you want to delete all items in this list?")) {
				var url = Globals.path + '/MyAccount/AJAX?method=deleteListItems&id=' + id;
				$.getJSON(url, function () {
					location.reload();
				});
			}
			return false;
		},
		deleteSelected: function (id) {
			var selectedTitles = AspenDiscovery.getSelectedTitles();
			if (selectedTitles) {
				if (confirm("Are you sure you want to delete the selected items from this list?")) {
					$.getJSON(Globals.path + '/MyAccount/AJAX?method=deleteListItems&id=' + id + '&' + selectedTitles, function () {
						location.reload();
					});
				}
			}
			return false;
		},
		deleteSelectedLists: function () {
			var selectedLists = AspenDiscovery.getSelectedLists();
			if (selectedLists) {
				if (confirm("Are you sure you want to delete the selected lists?")) {
					$.getJSON(Globals.path + '/MyAccount/AJAX?method=deleteList&' + selectedLists, function () {
						location.reload();
					});
				}
			}
			return false;
		},
		getEditListForm: function (listEntryId, listId) {
			var url = Globals.path + "/MyAccount/AJAX?method=getEditListForm&listEntryId=" + listEntryId + "&listId=" + listId;
			$.getJSON(url, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},
		editListItem: function () {
			var url = Globals.path + '/MyAccount/AJAX?method=editListItem';
			var newData = new FormData($("#listEntryEditForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: newData,
				dataType: 'json',
				success: function (data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},
		loadRecommendations: function () {
			var url = Globals.path + "/MyAccount/AJAX",
				params = {'method': 'getSuggestionsSpotlight'};
			$.getJSON(url, params, function (data) {
				try {
					var suggestionsData = data.suggestions;
					if (suggestionsData && suggestionsData.length > 0) {
						//Create an unordered list for display
						var html = '<ul>';

						$.each(suggestionsData, function () {
							html += '<li class="carouselTitleWrapper">' + this.formattedTitle + '</li>';
						});

						html += '</ul>';

						var carouselElement = $('#recommendationsCarousel');
						carouselElement.html(html);
						var jCarousel = carouselElement.jcarousel({wrap: null});

						// Reload carousel
						jCarousel.jcarousel('reload');
					} else {
						$('#recommendedForYouInfo').hide();
					}
				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},
		getDonationValuesForDisplay: function () {
			if ($("input[name='customAmount']").val()) {
				var amount = $("input[name='customAmount']").val();
				$('#thisDonation').show();
				document.getElementById("thisDonationValue").innerHTML = amount
			} else if ($("input[name='predefinedAmount']:checked").val()) {
				var amount = $("input[name='predefinedAmount']:checked").val();
				$('#thisDonation').show();
				document.getElementById("thisDonationValue").innerHTML = amount
			} else {
				$('#thisDonation').hide();
			}
		},
		getCurbsidePickupScheduler: function (locationId) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getCurbsidePickupScheduler&pickupLocation=" + locationId, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.getCurbsidePickupScheduler(locationId);
				}, false);
			}
			return false;
		},
		createCurbsidePickup: function () {
			if (Globals.loggedIn) {

				var patronId = $("#newCurbsidePickupForm input[name=patronId]").val();
				var locationId = $("#newCurbsidePickupForm  input[name=pickupLibrary]").val();
				var date = $("#newCurbsidePickupForm  input[name=pickupDate]").val();
				var time = $("#newCurbsidePickupForm  input[name=pickupTime]:checked").val();
				var note = $("#newCurbsidePickupForm  input[name=pickupNote]").text();

				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=createCurbsidePickup&patronId=" + patronId + "&location=" + locationId + "&date=" + date + "&time=" + time + "&note=" + note, function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.body, true, 2000)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.createCurbsidePickup(patronId, locationId, dateTime, note);
				}, false);
			}
			return false;
		},
		getCancelCurbsidePickup: function (patronId, pickupId) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=getCancelCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons); // automatically close when successful
			}).fail(AspenDiscovery.ajaxFail);
			return false
		},
		cancelCurbsidePickup: function (patronId, pickupId) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
				AspenDiscovery.showMessage(data.title, data.body, true, 2000); // automatically close when successful
			}).fail(AspenDiscovery.ajaxFail);
			return false
		},
		checkInCurbsidePickup: function (patronId, pickupId) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=checkInCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
				AspenDiscovery.showMessage(data.title, data.body, false);
			}).fail(AspenDiscovery.ajaxFail);
			return false
		},
		curbsidePickupScheduler: function (locationCode) {
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=getCurbsidePickupUnavailableDays&locationCode=" + locationCode, function (data) {
				$("#pickupDate").flatpickr(
					{
						minDate: "today",
						maxDate: new Date().fp_incr(14),
						altInput: true,
						altFormat: "M j, Y",
						"disable": [
							function (date) {
								return data.includes(date.getDay());
							}
						],
						"locale": {
							"firstDayOfWeek": 0
						},
						onChange: function (selectedDates, dateStr, instance) {
							//... send dateStr to check what times are available
							$.getJSON(Globals.path + "/MyAccount/AJAX?method=getCurbsidePickupAvailableTimes&date=" + dateStr + "&locationCode=" + locationCode, function (data) {
								// return available timeslots to dom
								var numOfSlots = data.length;
								var morningSlots = 0;
								var afternoonSlots = 0;
								var eveningSlots = 0;
								for (var i = 0; i < numOfSlots; i++) {
									if (data[i] < "12:00") {
										morningSlots++;
										var timeSlotContainer = document.getElementById("morningTimeSlots");
										var slot = moment(data[i], "HH:mm").format("h:mm a");
										timeSlotContainer.innerHTML += "<label class='btn btn-primary' style='margin-right: 1em; margin-bottom: 1em'><input type='radio' name='pickupTime' id='slot_" + data[i] + "' value='" + slot + "'> " + slot + "</label>";
									} else if (data[i] < "17:00") {
										afternoonSlots++;
										var timeSlotContainer = document.getElementById("afternoonTimeSlots");
										var slot = moment(data[i], "HH:mm").format("h:mm a");
										timeSlotContainer.innerHTML += "<label class='btn btn-primary' style='margin-right: 1em; margin-bottom: 1em'><input type='radio' name='pickupTime' id='slot_" + data[i] + "' value='" + slot + "'> " + slot + "</label>";
									} else {
										eveningSlots++;
										var timeSlotContainer = document.getElementById("eveningTimeSlots");
										var slot = moment(data[i], "HH:mm").format("h:mm a");
										timeSlotContainer.innerHTML += "<label class='btn btn-primary' style='margin-right: 1em; margin-bottom: 1em'><input type='radio' name='pickupTime' id='slot_" + data[i] + "' value='" + slot + "'> " + slot + "</label>";
									}
								}

								if (morningSlots === 0) {
									$("#morningTimeSlotsAccordion").hide();
								}
								if (afternoonSlots === 0) {
									$("#afternoonTimeSlotsAccordion").hide();
								}
								if (eveningSlots === 0) {
									$("#eveningTimeSlotsAccordion").hide();
								}
								$('#availableTimeSlots').find('div.panel:visible:first').addClass('active');
								$("#availableTimeSlots").show();
							});
						}
					}
				);
			}).fail(AspenDiscovery.ajaxFail);
			return false
		},
		show2FAEnrollment: function (mandatoryEnroll) {
			if (Globals.loggedIn || mandatoryEnroll) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=get2FAEnrollment&step=register&mandatoryEnrollment=" + mandatoryEnroll, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.show2FAEnrollmentVerify(mandatoryEnroll);
				}, false);
			}
			return false;
		},
		show2FAEnrollmentVerify: function (mandatoryEnroll) {
			if (Globals.loggedIn || mandatoryEnroll) {
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=get2FAEnrollment&step=verify&mandatoryEnrollment=" + mandatoryEnroll, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.show2FAEnrollmentVerify(mandatoryEnroll);
				}, false);
			}
			return false;
		},
		show2FAEnrollmentBackupCodes: function (mandatoryEnroll) {
			if (Globals.loggedIn || mandatoryEnroll) {
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=get2FAEnrollment&step=backup&mandatoryEnrollment=" + mandatoryEnroll, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.show2FAEnrollmentBackupCodes(mandatoryEnroll);
				}, false);
			}
			return false;
		},
		show2FAEnrollmentSuccess: function (mandatoryEnroll) {
			if (Globals.loggedIn || mandatoryEnroll) {
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=get2FAEnrollment&step=complete&mandatoryEnrollment=" + mandatoryEnroll, function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.body, false, 2000)
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.show2FAEnrollmentSuccess(mandatoryEnroll);
				}, false);
			}
			return false;
		},
		showCancel2FA: function () {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=confirmCancel2FA", function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.cancel2FA();
				}, false);
			}
			return false;
		},
		cancel2FA: function () {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancel2FA", function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.body, true, 2000)
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.cancel2FA();
				}, false);
			}
			return false;
		},
		verify2FA: function (mandatoryEnrollment) {
			var code = $("#code").val();
			var loggingIn = mandatoryEnrollment ? true : false;
			if (Globals.loggedIn || mandatoryEnrollment) {
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=verify2FA&loggingIn=" + loggingIn + "&code=" + code + "&mandatoryEnrollment=" + mandatoryEnrollment, function (data) {
					// update #codeVerificationFailedPlaceholder with failed verification status, otherwise move onto next step
					if (data.success === "true") {
						return AspenDiscovery.Account.show2FAEnrollmentBackupCodes(mandatoryEnrollment);
					}
					$("#codeVerificationFailedPlaceholder").html(data.message).show();
					return data;
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.verify2FA();
				}, false);
			}
			return false;
		},
		new2FACode: function () {
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=new2FACode", function (data) {
				// update #newCodeSentPlaceholder with sent status
				$("#newCodeSentPlaceholder").html(data.body).show();
				return data;
			});
			return false;
		},
		showNewBackupCodes: function () {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=newBackupCodes", function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.body, false, 2000)
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.showNewBackupCodes();
				}, false);
			}
			return false;
		},
		show2FALogin: function () {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=newBackupCodes", function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.body, false, 2000)
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.show2FALogin();
				}, false);
			}
			return false;
		},
		verify2FALogin: function (ajaxCallback) {
			var code = $("#code").val();
			var referer = $("#referer").val();
			var name = $("#name").val();
			var myAccountAuth = $("#myAccountAuth").val();
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=verify2FA&loggingIn=true&code=" + code, function (data) {
				// update #codeVerificationFailedPlaceholder with failed verification status, otherwise move onto next step
				if (data.success === "true") {
					Globals.loggedIn = true;

					if (myAccountAuth === 'true') {
						window.location.reload();
					}
					$('#loginLinkIcon').removeClass('fa-sign-in-alt').addClass('fa-user');
					$('#login-button-label').html(name);
					$('#logoutLink').show();

					if (AspenDiscovery.Account.closeModalOnAjaxSuccess) {
						AspenDiscovery.closeLightbox();
					}

					if (ajaxCallback !== undefined && typeof (ajaxCallback) === "function") {
						ajaxCallback();
					} else if (AspenDiscovery.Account.ajaxCallback !== undefined && typeof (AspenDiscovery.Account.ajaxCallback) === "function") {
						AspenDiscovery.Account.ajaxCallback();
						AspenDiscovery.Account.ajaxCallback = null;
					}
					if (multiStep !== 'true') {
						window.location.replace(referer);
					}
				}
				$("#codeVerificationFailedPlaceholder").html(data.message).show();
				return data;
			});
			return false;
		},
		checkWorldPayStatus: function (paymentId, status) {
			if (Globals.activeAction === "WorldPayCompleted" && Globals.loggedIn) {
				var params = {
					paymentId: paymentId,
					currentStatus: status
				};
				$.getJSON(Globals.path + '/MyAccount/AJAX?method=checkWorldPayOrderStatus', params, function (data) {
					if (data.success) {
						$('#successMessage').html(data.message);
						clearInterval(pollStatus);
						return true;
					}
				});
			}
			return false;
		}
	};
}(AspenDiscovery.Account || {}));