AspenDiscovery.Account = (function(){

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
		 * Called from list-form.tpl
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
			$.getJSON(url, params,function (data) {
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
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					ajaxCallback();
				} else if (AspenDiscovery.Account.ajaxCallback != null && typeof(AspenDiscovery.Account.ajaxCallback) === "function") {
					AspenDiscovery.Account.ajaxCallback();
					AspenDiscovery.Account.ajaxCallback = null;
				}
			} else {
				var multiStep = false,
						loginLink = false;
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					multiStep = true;
				}
				AspenDiscovery.Account.ajaxCallback = ajaxCallback;
				AspenDiscovery.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
				if (trigger !== undefined && trigger !== null) {
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					loginLink = trigger.data('login');
					/*
					  Set the trigger html element attribute data-login="true" to cause the pop-up login dialog
					  to act as if the only action is login, ie not a multi-step process.

					 */
				}
				var dialogDestination = Globals.path + '/MyAccount/AJAX?method=LoginForm';
				if (multiStep && !loginLink){
					dialogDestination += "&multiStep=true";
				}
				var modalDialog = $("#modalDialog");
				$('.modal-body').html("Loading...");
				$(".modal-content").load(dialogDestination);
				$(".modal-title").text(dialogTitle);
				modalDialog.modal("show");
			}
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

		loadListData: function (){
			var url = Globals.path + "/MyAccount/AJAX?method=getListData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
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
			let url = Globals.path + "/MyAccount/AJAX?method=getMenuData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function(data){
				$("#lists-placeholder").html(data.lists);
				$(".checkouts-placeholder").html(data.checkouts);
				$(".holds-placeholder").html(data.holds);
				$(".readingHistory-placeholder").html(data.readingHistory);
				$(".materialsRequests-placeholder").html(data.materialsRequests);
				$(".ratings-placeholder").html(data.ratings);
				$(".recommendations-placeholder").html(data.recommendations);
				$(".bookings-placeholder").html(data.bookings);
				$("#availableHoldsNotice-placeHolder").html(data.availableHoldsNotice);
				$(".expirationFinesNotice-placeholder").html(data.expirationFinesNotice);
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
				var rememberMe = $("#rememberMe").prop('checked'),
						showPwd = $('#showPwd').prop('checked');
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
				var username = $("#username").val(),
						password = $("#password").val(),
						rememberMe = $("#rememberMe").prop('checked'),
						loginErrorElem = $('#loginError'),
						loadingElem = $('#loading'),
						url = Globals.path + "/AJAX/JSON?method=loginUser",
						params = {username: username, password: password, rememberMe: rememberMe};
				if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
					var showCovers = window.localStorage.getItem('showCovers') || false;
					if (showCovers && showCovers.length > 0) { // if there is a set value, pass it back with the login info
						params.showCovers = showCovers
					}
				}
				loginErrorElem.hide();
				loadingElem.show();
				$.post(url, params, function(response){
							loadingElem.hide();
							if (response.result.success === true) {
								// Hide "log in" options and show "log out" options:
								$('.loginOptions, #loginOptions').hide();
								$('.logoutOptions, #logoutOptions').show();

								// Show user name on page in case page doesn't reload
								var name = $.trim(response.result.name);
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
						}, 'json'
				).fail(function(){
					loginErrorElem.text("There was an error processing your login, please try again.").show();
				})
			}
			return false;
		},

		processAddLinkedUser: function (){
			if(this.preProcessLogin()) {
				var username = $("#username").val(),
						password = $("#password").val(),
						loginErrorElem = $('#loginError'),
						url = Globals.path + "/MyAccount/AJAX?method=addAccountLink";
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
				var url = Globals.path + "/MyAccount/AJAX?method=removeAccountLink&idToRemove=" + idToRemove;
				$.getJSON(url, function(data){
					if (data.result === true){
						AspenDiscovery.showMessage('Linked Account Removed', data.message, true, true);
						//setTimeout(function(){window.location.reload()}, 3000);
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
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId + "&renewIndicator="+renewIndicator, function(data){
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success); // autoclose when successful
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
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewAll", function (data) {
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success);
						// autoclose when all successful
						if (data.success || data.renewed > 0) {
							// Refresh page on close when a item has been successfully renewed, otherwise stay
							$("#modalDialog").on('hidden.bs.modal', function (e) {
								location.reload(true);
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
				var selectedTitles = AspenDiscovery.getSelectedTitles();
				if (selectedTitles) {
					if (confirm('Renew selected Items?')) {
						AspenDiscovery.loadingMessage();
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewSelectedItems&" + selectedTitles, function (data) {
							var reload = data.success || data.renewed > 0;
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
			var barcode = $('#card_number').val();
			if (barcode.length == 0){
				alert("Please enter your library card number");
			}else{
				var url = path + '/MyAccount/AJAX?method=requestPinReset&barcode=' + barcode;
				$.getJSON(url, function(data){
					if (data.error == false){
						alert(data.message);
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
			if (requireLogin == undefined) {
				requireLogin = false;
			}
			if (requireLogin && !Globals.loggedIn) {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Account.ajaxLightbox(urlToDisplay, requireLogin);
				}, false);
			} else {
				AspenDiscovery.loadingMessage();
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
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=confirmCancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons); // autoclose when successful
			}).fail(AspenDiscovery.ajaxFail);

			return false
		},

		cancelHold: function(patronId, recordId, holdIdToCancel){
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
					AspenDiscovery.showMessage(data.title, data.body, data.success, data.success); // autoclose when successful
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
					var c = {};
					c[patronId] = cancelId;
					//console.log(c);
					//$.getJSON(Globals.path + "/MyAccount/AJAX", {method:"cancelBooking", patronId:patronId, cancelId:cancelId}, function(data){
					$.getJSON(Globals.path + "/MyAccount/AJAX", {method:"cancelBooking", cancelId:c}, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // autoclose when successful
						if (data.success) {
							// remove canceled item from page
							var escapedId = cancelId.replace(/:/g, "\\:"); // needed for jquery selector to work correctly
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
				var selectedTitles = this.getSelectedTitles(),
						numBookings = $("input.titleSelect:checked").length;
				// if numBookings equals 0, quit because user has canceled in getSelectedTitles()
				if (numBookings > 0 && confirm('Cancel ' + numBookings + ' selected scheduled item' + (numBookings > 1 ? 's' : '') + '?')) {
					AspenDiscovery.loadingMessage();
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&"+selectedTitles, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // autoclose when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect:checked").closest('div.result').remove();
						} else if (data.failed) { // remove items that didn't fail
							var searchArray = data.failed.map(function(ele){return ele.toString()});
							// convert any number values to string, this is needed bcs inArray() below does strict comparisons
							// & id will be a string. (sometimes the id values are of type number )
							$("input.titleSelect:checked").each(function(){
								var id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
								if ($.inArray(id, searchArray) == -1) // if the item isn't one of the failed cancels, get rid of its containing div.
									$(this).closest('div.result').remove();
							});
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
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&cancelAll=1", function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // autoclose when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect").closest('div.result').remove();
						} else if (data.failed) { // remove items that didn't fail
							var searchArray = data.failed.map(function(ele){return ele.toString()});
							// convert any number values to string, this is needed bcs inArray() below does strict comparisons
							// & id will be a string. (sometimes the id values are of type number )
							$("input.titleSelect").each(function(){
								var id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
								if ($.inArray(id, searchArray) == -1) // if the item isn't one of the failed cancels, get rid of its containing div.
									$(this).closest('div.result').remove();
							});
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
			var paramString = AspenDiscovery.replaceQueryParam(sortParameterName, newSort);
			location.replace(location.pathname + paramString)
		},

		changeHoldPickupLocation: function (patronId, recordId, holdId){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getChangeHoldLocationForm&patronId=" + patronId + "&recordId=" + recordId + "&holdId=" + holdId, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					return AspenDiscovery.Account.changeHoldPickupLocation(patronId, recordId, holdId);
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
				var url = Globals.path + "/MyAccount/AJAX";
				var params = "method=deleteSearch&searchId=" + encodeURIComponent(searchId);
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
			var url = Globals.path + "/MyAccount/AJAX"
					,params = {
						'method': 'changeHoldLocation'
						,patronId : $('#patronId').val()
						,recordId : $('#recordId').val()
						,holdId : $('#holdId').val()
						,newLocation : $('#newPickupLocation').val()
					};

			$.getJSON(url, params,
					function(data) {
						if (data.success) {
							AspenDiscovery.showMessage("Success", data.message, true, true);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
			).fail(AspenDiscovery.ajaxFail);
		},

		freezeHold: function(patronId, recordId, holdId, promptForReactivationDate, caller){
			AspenDiscovery.loadingMessage();
			var url = Globals.path + '/MyAccount/AJAX',
					params = {
						patronId : patronId
						,recordId : recordId
						,holdId : holdId
					};
			if (promptForReactivationDate){
				//Prompt the user for the date they want to reactivate the hold
				params['method'] = 'getReactivationDateForm'; // set method for this form
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				}).fail(AspenDiscovery.ajaxFail);

			}else{
				var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
				params['method'] = 'freezeHold'; //set method for this ajax call
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
			var popUpBoxTitle = $(caller).text() || "Freezing Hold"  // freezing terminology can be customized, so grab text from click button: caller
					,params = {
						'method' : 'freezeHold'
						,patronId : $('#patronId').val()
						,recordId : $('#recordId').val()
						,holdId : $("#holdId").val()
						,reactivationDate : $("#reactivationDate").val()
					}
					,url = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		/* Hide this code for now. I should be to re-enable when re-enable selections for Holds
		plb 9-14-2015

		freezeSelectedHolds: function (){
			var selectedTitles = this.getSelectedTitles();
			if (selectedTitles.length == 0){
				return false;
			}
			var suspendDate = '',
					suspendDateTop = $('#suspendDateTop'),
					url = '',
					queryParams = '';
			if (suspendDateTop.length) { //Check to see whether or not we are using a suspend date.
				if (suspendDateTop.val().length > 0) {
					suspendDate = suspendDateTop.val();
				} else {
					suspendDate = $('#suspendDateBottom').val();
				}
				if (suspendDate.length == 0) {
					alert("Please select the date when the hold should be reactivated.");
					return false;
				}
			}
			url = Globals.path + '/MyAccount/Holds?multiAction=freezeSelected&patronId=' + patronId + '&recordId=' + recordId + '&' + selectedTitles + '&suspendDate=' + suspendDate;
			queryParams = AspenDiscovery.getQuerystringParameters();
			if ($.inArray('section', queryParams)){
				url += '&section=' + queryParams['section'];
			}
			window.location = url;
			return false;
		},
		*/


		getSelectedTitles: function(promptForSelectAll){
			if (promptForSelectAll == undefined){
				promptForSelectAll = true;
			}
			var selectedTitles = $("input.titleSelect:checked ");
			if (selectedTitles.length == 0 && promptForSelectAll && confirm('You have not selected any items, process all items?')) {
				selectedTitles = $("input.titleSelect")
					.attr('checked', 'checked');
			}
			var queryString = selectedTitles.map(function() {
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
				var url = Globals.path + "/MyAccount/AJAX",
						params = {method :'saveSearch', searchId :searchId};
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

		showCreateListForm: function(id = null){
			if (Globals.loggedIn){
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {method:"getCreateListForm"};
				if (id != null){
					params.recordId= id;
				}
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
			var popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			var url = Globals.path + '/MyAccount/AJAX',
					params = {
						'method' : 'thawHold'
						,patronId : patronId
						,recordId : recordId
						,holdId : holdId
					};
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
			var paramString = AspenDiscovery.replaceQueryParam('showCovers', this.showCovers ? 'on': 'off'); // set variable
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
				window.localStorage.setItem('showCovers', this.showCovers ? 'on' : 'off');
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		validateCookies: function(){
			if (navigator.cookieEnabled == false){
				$("#cookiesError").show();
			}
		},

		getMasqueradeForm: function () {
			AspenDiscovery.loadingMessage();
			var url = Globals.path + "/MyAccount/AJAX",
					params = {method:"getMasqueradeAsForm"};
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initiateMasquerade: function() {
			var url = Globals.path + "/MyAccount/AJAX",
					params = {
						method:"initiateMasquerade"
						,cardNumber:$('#cardNumber').val()
					};
			$('#masqueradeAsError').hide();
			$('#masqueradeLoading').show();
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
			var url = Globals.path + "/MyAccount/AJAX",
					params = {method:"endMasquerade"};
			$.getJSON(url, params).done(function(){
					location.href = Globals.path + '/MyAccount/Home';
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}

	};
}(AspenDiscovery.Account || {}));