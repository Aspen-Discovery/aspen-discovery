AspenDiscovery.Account = (function(){

	// noinspection JSUnusedGlobalSymbols
	return {
		ajaxCallback: null,
		closeModalOnAjaxSuccess: false,
		showCovers: null,

		addAccountLink: function(){
			const url = Globals.path + "/MyAccount/AJAX?method=getAddAccountLinkForm";
			AspenDiscovery.Account.ajaxLightbox(url, true);
		},

		/**
		 * Creates a new list in the system for the active user.
		 *
		 * Called from createListForm.tpl
		 * @returns {boolean}
		 */
		addList: function(recordId){
			let form = $("#addListForm");
			recordId = recordId === undefined ? form.find("input[name=recordId]").val() : recordId;
			let		isPublic = form.find("#public").prop("checked"),
					title = form.find("input[name=title]").val(),
					desc = $("#listDesc").val(),
					url = Globals.path + "/MyAccount/AJAX";
			let params = {
				'method':'addList',
				title: title,
				public: isPublic,
				desc: desc,
				recordId: recordId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params,function (data) {
				if (data.success) {
					AspenDiscovery.showMessage("Added Successfully", data.message, true, false);
					AspenDiscovery.Account.loadListData();
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
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					ajaxCallback();
				} else if (AspenDiscovery.Account.ajaxCallback != null && typeof(AspenDiscovery.Account.ajaxCallback) === "function") {
					AspenDiscovery.Account.ajaxCallback();
					AspenDiscovery.Account.ajaxCallback = null;
				}
			} else {
				let multiStep = false;
				let loginLink = false;
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					multiStep = true;
				}
				AspenDiscovery.Account.ajaxCallback = ajaxCallback;
				AspenDiscovery.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
				let dialogTitle = "Login";
				if (trigger !== undefined && trigger !== null) {
					dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					loginLink = trigger.data('login');
				}
				let dialogDestination = Globals.path + '/MyAccount/AJAX?method=getLoginForm';
				if (multiStep && !loginLink){
					dialogDestination += "&multiStep=true";
				}
				let modalDialog = $("#modalDialog");
				$('.modal-body').html("Loading...");
				$(".modal-content").load(dialogDestination);
				$(".modal-title").text(dialogTitle);
				modalDialog.modal("show");
			}
			return false;
		},

		changeLinkedAccount: function(){
			let patronId = $("#patronId option:selected").val();
			document.location.href = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'patronId', patronId);
		},

		exportCheckouts: function(source, sort){
			let url = Globals.path + "/MyAccount/AJAX?method=exportCheckouts&source=" + source;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			document.location.href = url;
			return false;
		},

		exportHolds: function(source, availableHoldsSort, unavailableHoldsSort){
			let url = Globals.path + "/MyAccount/AJAX?method=exportHolds&source=" + source;
			if (availableHoldsSort !== undefined){
				url += "&availableHoldsSort=" + availableHoldsSort;
			}
			if (unavailableHoldsSort !== undefined){
				url += "&unavailableHoldsSort=" + unavailableHoldsSort;
			}
			document.location.href = url;
			return false;
		},

		followLinkIfLoggedIn: function (trigger, linkDestination) {
			if (trigger === undefined) {
				alert("You must provide the trigger to follow a link after logging in.");
			}
			let jqTrigger = $(trigger);
			if (linkDestination === undefined) {
				linkDestination = jqTrigger.attr("href");
			}
			this.ajaxLogin(jqTrigger, function () {
				document.location = linkDestination;
			}, true);
			return false;
		},

		loadCheckouts: function(source, sort, showCovers){
			let url = Globals.path + "/MyAccount/AJAX?method=getCheckouts&source=" + source;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			let stateObj = {
				page: 'Checkouts',
				source: source,
				sort: sort,
				showCovers: showCovers
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href ){
				let label = 'Checkouts';
				if (source === 'ils'){
					label = 'Physical Checkouts';
				}else if (source === 'overdrive'){
					label = 'OverDrive Checkouts';
				}else if (source === 'hoopla'){
					label = 'Hoopla Checkouts';
				}else if (source === 'rbdigital'){
					label = 'RBdigital Checkouts';
				}else if (source === 'cloud_library'){
					label = 'Cloud Library Checkouts';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#" + source + "CheckoutsPlaceholder").html(data.checkouts);
				}else{
					$("#" + source + "CheckoutsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadHolds: function(source, availableHoldSort, unavailableHoldSort, showCovers){
			let url = Globals.path + "/MyAccount/AJAX?method=getHolds&source=" + source;
			if (availableHoldSort !== undefined){
				url += "&availableHoldSort=" + availableHoldSort;
			}
			if (unavailableHoldSort !== undefined){
				url += "&unavailableHoldSort=" + unavailableHoldSort;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			let stateObj = {
				page: 'Holds',
				source: source,
				availableHoldSort: availableHoldSort,
				unavailableHoldSort: unavailableHoldSort,
				showCovers: showCovers
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href ){
				let label = 'Holds';
				if (source === 'ils'){
					label = 'Physical Holds';
				}else if (source === 'overdrive'){
					label = 'OverDrive Holds';
				}else if (source === 'rbdigital'){
					label = 'RBdigital Holds';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#" + source + "HoldsPlaceholder").html(data.holds);
				}else{
					$("#" + source + "HoldsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadReadingHistory: function(selectedUser, sort, page, showCovers, filter){
			let url = Globals.path + "/MyAccount/AJAX?method=getReadingHistory&patronId=" + selectedUser;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			if (page !== undefined){
				url += "&page=" + page;
			}else{
				page = 1;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			if (filter !== undefined){
				url += "&readingHistoryFilter=" + filter;
			}
			let stateObj = {
				page: 'ReadingHistory',
				pageNumber: page,
				selectedUser: selectedUser,
				sort: sort,
				showCovers: showCovers,
				readingHistoryFilter: filter,
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'selectedUser', selectedUser);
			newUrl = AspenDiscovery.buildUrl(newUrl, 'page', page);
			if (filter !== undefined){
				newUrl = AspenDiscovery.buildUrl(newUrl, 'readingHistoryFilter', filter);
			}
			if (document.location.href ){
				let label = 'Reading History page ' . page;
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#readingHistoryListPlaceholder").html(data.readingHistory);
				}else{
					$("#readingHistoryListPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadListData: function (){
			let url = Globals.path + "/MyAccount/AJAX?method=getListData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function(data){
				$("#lists-placeholder").html(data.lists);
			});
			return false;
		},

		loadRatingsData: function (){
			let url = Globals.path + "/MyAccount/AJAX?method=getRatingsData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function(data){
				$(".ratings-placeholder").html(data.ratings);
				$(".recommendations-placeholder").html(data.recommendations);
			});
			return false;
		},

		loadMenuData: function (){
			let ilsUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataIls&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			let totalCheckouts = 0;
			let totalHolds = 0;
			$.getJSON(ilsUrl, function(data){
				if (data.success) {
					$(".ils-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					if (data.summary.numOverdue > 0) {
						$(".ils-overdue-placeholder").html(data.summary.numOverdue);
						$(".ils-overdue").show();
					}
					$(".ils-holds-placeholder").html(data.summary.numHolds);
					totalHolds += parseInt(data.summary.numHolds);
					$(".holds-placeholder").html(totalHolds);
					if (data.summary.numAvailableHolds > 0) {
						$(".ils-available-holds-placeholder").html(data.summary.numAvailableHolds);
						$(".ils-available-holds").show();
					}
					$(".readingHistory-placeholder").html(data.summary.readingHistory);
					$(".materialsRequests-placeholder").html(data.summary.materialsRequests);
					$(".bookings-placeholder").html(data.summary.bookings);
					$(".expirationFinesNotice-placeholder").html(data.summary.expirationFinesNotice);
				}
			});
			let rbdigitalUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataRBdigital&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(rbdigitalUrl, function(data){
				if (data.success) {
					$(".rbdigital-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					$(".rbdigital-holds-placeholder").html(data.summary.numUnavailableHolds);
					totalHolds += parseInt(data.summary.numUnavailableHolds);
					$(".holds-placeholder").html(totalHolds);
				}
			});
			let cloudLibraryUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataCloudLibrary&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(cloudLibraryUrl, function(data){
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
			let hooplaUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataHoopla&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(hooplaUrl, function(data){
				if (data.success) {
					$(".hoopla-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
				}
			});
			let overdriveUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataOverDrive&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(overdriveUrl, function(data){
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

			return false;
		},

		preProcessLogin: function (){
			let username = $("#username").val(),
				password = $("#password").val(),
				loginErrorElem = $('#loginError');
			if (!username || !password) {
				loginErrorElem
						.text($("#missingLoginPrompt").text())
						.show();
				return false;
			}
			if (AspenDiscovery.hasLocalStorage()){
				let rememberMe = $("#rememberMe").prop('checked');
				let showPwd = $('#showPwd').prop('checked');
				if (rememberMe){
					window.localStorage.setItem('lastUserName', username);
					window.localStorage.setItem('lastPwd', password);
					window.localStorage.setItem('showPwd', showPwd);
					window.localStorage.setItem('rememberMe', rememberMe);
				}else{
					window.localStorage.removeItem('lastUserName');
					window.localStorage.removeItem('lastPwd');
					window.localStorage.removeItem('showPwd');
					window.localStorage.removeItem('rememberMe');
				}
			}
			return true;
		},

		processAjaxLogin: function (ajaxCallback) {
			if(this.preProcessLogin()) {
				let username = $("#username").val();
				let password = $("#password").val();
				let rememberMe = $("#rememberMe").prop('checked');
				let loginErrorElem = $('#loginError');
				let loadingElem = $('#loading');
				let url = Globals.path + "/AJAX/JSON?method=loginUser";
				let params = {username: username, password: password, rememberMe: rememberMe};
				if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
					let showCovers = window.localStorage.getItem('showCovers') || false;
					if (showCovers && showCovers.length > 0) { // if there is a set value, pass it back with the login info
						params.showCovers = showCovers
					}
				}
				loginErrorElem.hide();
				loadingElem.show();
				// noinspection JSUnresolvedFunction
				$.post(url, params, function(response){
					loadingElem.hide();
					if (response.result.success === true) {
						// Hide "log in" options and show "log out" options:
						$('.loginOptions, #loginOptions').hide();
						$('.logoutOptions, #logoutOptions').show();

						// Show user name on page in case page doesn't reload
						let name = $.trim(response.result.name);
						//name = 'Logged In As ' + name.slice(0, name.lastIndexOf(' ') + 2) + '.';
						name = 'Logged In As ' + name.slice(0, 1) + '. ' + name.slice(name.lastIndexOf(' ') + 1, name.length) + '.';
						$('#side-bar #myAccountNameLink').html(name);

						if (AspenDiscovery.Account.closeModalOnAjaxSuccess) {
							AspenDiscovery.closeLightbox();
						}

						Globals.loggedIn = true;
						if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
							ajaxCallback();
						} else if (AspenDiscovery.Account.ajaxCallback !== undefined && typeof(AspenDiscovery.Account.ajaxCallback) === "function") {
							AspenDiscovery.Account.ajaxCallback();
							AspenDiscovery.Account.ajaxCallback = null;
						}
					} else {
						loginErrorElem.text(response.result.message).show();
					}
				}, 'json').fail(function(){
					loginErrorElem.text("There was an error processing your login, please try again.").show();
				})
			}
			return false;
		},

		processAddLinkedUser: function (){
			if(this.preProcessLogin()) {
				let username = $("#username").val();
				let password = $("#password").val();
				let loginErrorElem = $('#loginError');
				let url = Globals.path + "/MyAccount/AJAX?method=addAccountLink";
				loginErrorElem.hide();
				$.ajax({
					url: url,
					data: {username: username, password: password},
					success: function (response) {
						if (response.result === true) {
							AspenDiscovery.showMessage("Account to Manage", response.message ? response.message : "Successfully linked the account.", true, true);
						} else {
							loginErrorElem.text(response.message);
							loginErrorElem.show();
						}
					},
					error: function () {
						loginErrorElem.text("There was an error processing the account, please try again.")
								.show();
					},
					dataType: 'json',
					type: 'post'
				});
			}
			return false;
		},


		removeLinkedUser: function(idToRemove){
			if (confirm("Are you sure you want to stop managing this account?")){
				let url = Globals.path + "/MyAccount/AJAX?method=removeAccountLink&idToRemove=" + idToRemove;
				$.getJSON(url, function(data){
					if (data.result === true){
						AspenDiscovery.showMessage('Linked Account Removed', data.message, true, true);
					}else{
						AspenDiscovery.showMessage('Unable to Remove Account Link', data.message);
					}
				});
			}
			return false;
		},

		renewTitle: function(patronId, recordId, renewIndicator) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId + "&renewIndicator="+renewIndicator, function(data){
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success); // automatically close when successful
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					this.renewTitle(renewIndicator);
				}, false)
			}
			return false;
		},

		renewAll: function() {
			if (Globals.loggedIn) {
				if (confirm('Renew All Items?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewAll", function (data) {
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success);
						// automatically close when all successful
						if (data.success || data.renewed > 0) {
							// Refresh page on close when a item has been successfully renewed, otherwise stay
							// noinspection JSUnusedLocalSymbols
							$("#modalDialog").on('hidden.bs.modal', function (e) {
								location.reload();
							});
						}
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
				let selectedTitles = AspenDiscovery.getSelectedTitles();
				if (selectedTitles) {
					if (confirm('Renew selected Items?')) {
						AspenDiscovery.loadingMessage();
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewSelectedItems&" + selectedTitles, function (data) {
							let reload = data.success || data.renewed > 0;
							AspenDiscovery.showMessage(data.title, data.modalBody, data.success, reload);
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.renewSelectedTitles, true);
				 //auto close so that if user opts out of renew, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false
		},

		resetPin: function(){
			let barcode = $('#card_number').val();
			if (barcode.length === 0){
				alert("Please enter your library card number");
			}else{
				let url = path + '/MyAccount/AJAX?method=requestPinReset&barcode=' + barcode;
				$.getJSON(url, function(data){
					// noinspection EqualityComparisonWithCoercionJS
					if (data.error == false){
						alert(data.message);
						// noinspection EqualityComparisonWithCoercionJS
						if (data.result == true){
							AspenDiscovery.closeLightbox();
						}
					}else{
						alert("There was an error requesting your pin reset information.  Please contact the library for additional information.");
					}
				});
			}
			return false;
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
				$.getJSON(urlToDisplay, function(data){
					if (data.success){
						data = data.result;
					}
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		confirmCancelHold: function(patronId, recordId, holdIdToCancel) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=confirmCancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons); // automatically close when successful
			}).fail(AspenDiscovery.ajaxFail);

			return false
		},

		cancelHold: function(patronId, recordId, holdIdToCancel){
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
					AspenDiscovery.showMessage(data.title, data.body, data.success);
					if (data.success){
						let tmpRecordId = recordId.replace('.', '_').replace('~', '_');
						let tmpHoldIdToCancel = holdIdToCancel.replace('.', '_').replace('~', '_');
						let holdClass = '.ilsHold_' + tmpRecordId + '_' + tmpHoldIdToCancel;
						$(holdClass).hide();
						AspenDiscovery.Account.loadMenuData();
					}
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					AspenDiscovery.Account.cancelHold(patronId, recordId, holdIdToCancel)
				}, false);
			}

			return false
		},

		cancelBooking: function(patronId, cancelId){
			if (confirm("Are you sure you want to cancel this scheduled item?")){
				if (Globals.loggedIn) {
					AspenDiscovery.loadingMessage();
					let c = {};
					c[patronId] = cancelId;
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX", {method:"cancelBooking", cancelId:c}, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled item from page
							let escapedId = cancelId.replace(/:/g, "\\:"); // needed for jquery selector to work correctly
							// first backslash for javascript escaping, second for css escaping (within jquery)
							$('div.result').has('#selected'+escapedId).remove();
						}
					}).fail(AspenDiscovery.ajaxFail)
				} else {
					this.ajaxLogin(null, function () {
						AspenDiscovery.Account.cancelBooking(cancelId)
					}, false);
				}
			}

			return false
		},

		cancelSelectedBookings: function(){
			if (Globals.loggedIn) {
				let selectedTitles = this.getSelectedTitles();
				let numBookings = $("input.titleSelect:checked").length;
				// if numBookings equals 0, quit because user has canceled in getSelectedTitles()
				if (numBookings > 0 && confirm('Cancel ' + numBookings + ' selected scheduled item' + (numBookings > 1 ? 's' : '') + '?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&"+selectedTitles, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect:checked").closest('div.result').remove();
						} else {
							if (data.failed) { // remove items that didn't fail
								let searchArray = data.failed.map(function(ele){return ele.toString()});
								// convert any number values to string, this is needed bcs inArray() below does strict comparisons
								// & id will be a string. (sometimes the id values are of type number )
								$("input.titleSelect:checked").each(function(){
									let id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
									if ($.inArray(id, searchArray) === -1) // if the item isn't one of the failed cancels, get rid of its containing div.
										$(this).closest('div.result').remove();
								});
							}
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, AspenDiscovery.Account.cancelSelectedBookings, false);
			}
			return false;

		},

		cancelAllBookings: function(){
			if (Globals.loggedIn) {
				if (confirm('Cancel all of your scheduled items?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&cancelAll=1", function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect").closest('div.result').remove();
						} else {
							if (data.failed) { // remove items that didn't fail
								let searchArray = data.failed.map(function (ele) {
									return ele.toString()
								});
								// convert any number values to string, this is needed bcs inArray() below does strict comparisons
								// & id will be a string. (sometimes the id values are of type number )
								$("input.titleSelect").each(function () {
									let id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
									if ($.inArray(id, searchArray) === -1) // if the item isn't one of the failed cancels, get rid of its containing div.
										$(this).closest('div.result').remove();
								});
							}
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, AspenDiscovery.Account.cancelAllBookings, false);
			}
			return false;
		},

		changeAccountSort: function (newSort, sortParameterName){
			if (typeof sortParameterName === 'undefined') {
				sortParameterName = 'accountSort'
			}
			let paramString = AspenDiscovery.replaceQueryParam(sortParameterName, newSort);
			location.replace(location.pathname + paramString)
		},

		changeHoldPickupLocation: function (patronId, recordId, holdId, currentLocation){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getChangeHoldLocationForm&patronId=" + patronId + "&recordId=" + recordId + "&holdId=" + holdId + "&currentLocation=" + currentLocation, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					return AspenDiscovery.Account.changeHoldPickupLocation(patronId, recordId, holdId, currentLocation);
				}, false);
			}
			return false;
		},

		deleteSearch: function(searchId){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Searches.saveSearch(searchId);
				}, false);
			}else{
				let url = Globals.path + "/MyAccount/AJAX";
				let params = "method=deleteSearch&searchId=" + encodeURIComponent(searchId);
				$.getJSON(url + '?' + params,
					function(data) {
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

		doChangeHoldLocation: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				'method': 'changeHoldLocation'
				,patronId : $('#patronId').val()
				,recordId : $('#recordId').val()
				,holdId : $('#holdId').val()
				,newLocation : $('#newPickupLocation').val()
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		freezeHold: function(patronId, recordId, holdId, promptForReactivationDate, caller){
			AspenDiscovery.loadingMessage();
			let url = Globals.path + '/MyAccount/AJAX';
			let params = {
				patronId : patronId
				,recordId : recordId
				,holdId : holdId
			};
			if (promptForReactivationDate){
				//Prompt the user for the date they want to reactivate the hold
				params['method'] = 'getReactivationDateForm'; // set method for this form
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				}).fail(AspenDiscovery.ajaxFail);

			}else{
				let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
				params['method'] = 'freezeHold'; //set method for this ajax call
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					if (data.success) {
						AspenDiscovery.showMessage("Success", data.message, true, true);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			let params = {
				'method' : 'freezeHold'
				,patronId : $('#patronId').val()
				,recordId : $('#recordId').val()
				,holdId : $("#holdId").val()
				,reactivationDate : $("#reactivationDate").val()
			};
			let url = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		getSelectedTitles: function(promptForSelectAll){
			if (promptForSelectAll === undefined){
				promptForSelectAll = true;
			}
			let selectedTitles = $("input.titleSelect:checked ");
			if (selectedTitles.length === 0 && promptForSelectAll && confirm('You have not selected any items, process all items?')) {
				selectedTitles = $("input.titleSelect")
					.attr('checked', 'checked');
			}
			// noinspection UnnecessaryLocalVariableJS
			let queryString = selectedTitles.map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");

			return queryString;
		},

		saveSearch: function(searchId){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Account.saveSearch(searchId);
				}, false);
			}else{
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {method :'saveSearch', searchId :searchId};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params,
						function(data){
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

		showCreateListForm: function(id){
			if (Globals.loggedIn){
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {method:"getCreateListForm"};
				if (id !== undefined){
					params.recordId= id;
				}
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($trigger, function(){
					return AspenDiscovery.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},

		thawHold: function(patronId, recordId, holdId, caller){
			let popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			let url = Globals.path + '/MyAccount/AJAX';
			let params = {
				'method' : 'thawHold'
				,patronId : patronId
				,recordId : recordId
				,holdId : holdId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		toggleShowCovers: function(showCovers){
			this.showCovers = showCovers;
			let paramString = AspenDiscovery.replaceQueryParam('showCovers', this.showCovers ? 'on': 'off'); // set variable
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
				window.localStorage.setItem('showCovers', this.showCovers ? 'on' : 'off');
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		validateCookies: function(){
			if (navigator.cookieEnabled === false){
				$("#cookiesError").show();
			}
		},

		getMasqueradeForm: function () {
			AspenDiscovery.loadingMessage();
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {method:"getMasqueradeAsForm"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initiateMasquerade: function() {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method:"initiateMasquerade",
				cardNumber:$('#cardNumber').val()
			};
			$('#masqueradeAsError').hide();
			$('#masqueradeLoading').show();
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
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
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {method:"endMasquerade"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).done(function(){
				location.href = Globals.path + '/MyAccount/Home';
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		dismissMessage: function(messageId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "dismissMessage",
				messageId: messageId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		enableAccountLinking: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "enableAccountLinking",
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		stopAccountLinking: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "stopAccountLinking",
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		createPayPalOrder: function(finesFormId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "createPayPalOrder",
				patronId: $(finesFormId + " input[name=patronId]").val(),
				fineTotal: $(finesFormId + " input[name=totalToPay]").val(),
			};
			$(finesFormId + " .selectedFine:checked").each(
				function() {
					let name = $(this).attr('name');
					params[name] = $(this).val();

					let fineAmount = $(finesFormId + " #amountToPay" + $(this).data("fine_id"));
					if (fineAmount){
						params[fineAmount.attr('name')] = fineAmount.val();
					}
				}
			);
			let orderInfo = false;
			// noinspection JSUnresolvedFunction
			$.ajax({
				url: url,
				data: params,
				dataType: 'json',
				async: false,
				method: 'GET'
			}).success(
				function (response){
					if (response.success === false){
						AspenDiscovery.showMessage("Error", response.message);
						return false;
					}else{
						orderInfo = response.orderID;
					}
				}
			).fail(AspenDiscovery.ajaxFail);

			return orderInfo;
		},

		completePayPalOrder: function(orderId, patronId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "completePayPalOrder",
				patronId: patronId,
				orderId: orderId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage('Thank you', 'Your payment was processed successfully, thank you', false, true);
				} else {
					AspenDiscovery.showMessage('Error', 'Unable to process your payment, please visit the library with your receipt', false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},
		updateFineTotal: function(finesFormId, userId, paymentType) {
			let totalFineAmt = 0;
			let totalOutstandingAmt = 0;
			$(finesFormId + " .selectedFine:checked").each(
				function() {
					if (paymentType === "1"){
						totalFineAmt += $(this).data('fine_amt') * 1;
						totalOutstandingAmt += $(this).data('outstanding_amt') * 1;
					}else{
						let fineId = $(this).data('fine_id');
						let fineAmountInput = $("#amountToPay" + fineId);
						totalFineAmt += fineAmountInput.val() * 1;
						totalOutstandingAmt += fineAmountInput.val() * 1;
					}
				}
			);
			$('#formattedTotal' + userId).text("$" + totalFineAmt.toFixed(2));
			$('#formattedOutstandingTotal' + userId).text("$" + totalOutstandingAmt.toFixed(2));
		},
		dismissPlacard:function(patronId, placardId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "dismissPlacard",
				placardId: placardId,
				patronId: patronId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					$("#placard" + placardId).hide();
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		updateAutoRenewal:function(patronId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "updateAutoRenewal",
				allowAutoRenewal: $('#allowAutoRenewal').prop("checked"),
				patronId: patronId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage('Success', data.message, true);
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showSaveToListForm:function (trigger, source, id) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {
					method: "getSaveToListForm",
					sourceId: id,
					source: source
				}
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.Account.showSaveToListForm(trigger, source, id);
				});
			}
			return false;
		},

		saveToList: function(){
			if (Globals.loggedIn){
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {
					'method':'saveToList',
					'notes':$('#addToList-notes').val(),
					'listId':$('#addToList-list').val(),
					'source':$('#source').val(),
					'sourceId':$('#sourceId').val()
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params,function(data) {
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
	};
}(AspenDiscovery.Account || {}));