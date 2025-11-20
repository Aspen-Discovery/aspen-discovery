AspenDiscovery.OverDrive = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		cancelOverDriveHold: function(patronId, overdriveId){
			if (confirm("Are you sure you want to cancel this hold?")){
				var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=cancelHold&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success){
							AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
							//remove the row from the holds list
							$("#overDriveHold_" + overdriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}else{
							AspenDiscovery.showMessage("Error Cancelling Hold", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
					}
				});
			}
			return false;
		},

		freezeHold: function(patronId, overDriveId){
			AspenDiscovery.loadingMessage();
			var url = Globals.path + '/OverDrive/AJAX';
			var params = {
				'method' : 'freezeHold',
				patronId : patronId,
				overDriveId : overDriveId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Successfully Froze Hold", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Failed to Freeze Hold", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		thawHold: function(patronId, overDriveId, caller){
			var popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			var url = Globals.path + '/OverDrive/AJAX';
			var params = {
				'method' : 'thawHold'
				,patronId : patronId
				,overDriveId : overDriveId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		getCheckOutPrompts(overDriveId, callback) {
			const url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getCheckOutPrompts";
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					// noinspection JSUnresolvedReference
					if (data.promptNeeded){
						// noinspection JSUnresolvedReference
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
					if (callback) callback(data);
				},
				dataType: 'json',
				async: true,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
					if (callback) callback(false);
				}
			});
		},

		checkOutTitle(overDriveId, button){
			if (Globals.loggedIn) {
				AspenDiscovery.toggleButtonSpinner(button, true);

				AspenDiscovery.OverDrive.getCheckOutPrompts(overDriveId, function(promptInfo) {
					AspenDiscovery.toggleButtonSpinner(button, false);

					// noinspection JSUnresolvedReference
					if (promptInfo && !promptInfo.promptNeeded){
						AspenDiscovery.OverDrive.doOverDriveCheckout(promptInfo.patronId, overDriveId);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overDriveId, button);
				}, false);
			}
			return false;
		},

		processOverDriveCheckoutPrompts: function(){
			var overdriveCheckoutPromptsForm = $("#overdriveCheckoutPromptsForm");
			var patronId = $("#patronId").val();
			var overdriveId = overdriveCheckoutPromptsForm.find("input[name=overdriveId]").val();
			AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
		},

		doOverDriveCheckout: function(patronId, overdriveId){
			if (Globals.loggedIn){
				var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=checkOutTitle&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success === true){
							AspenDiscovery.showMessageWithButtons("Title Checked Out Successfully", data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						}else{
							// noinspection JSUnresolvedReference
							if (data.noCopies === true){
								AspenDiscovery.closeLightbox();
								var ret = confirm(data.message);
								if (ret === true){
									AspenDiscovery.OverDrive.placeHold(overdriveId);
								}
							}else{
								AspenDiscovery.showMessage("Error Checking Out Title", data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					}
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overdriveId);
				}, false);
			}
			return false;
		},

		doOverDriveHold: function(patronId, overDriveId, overdriveEmail, promptForOverdriveEmail){
			var url = Globals.path + "/OverDrive/AJAX?method=placeHold&patronId=" + patronId + "&overDriveId=" + overDriveId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					// noinspection JSUnresolvedReference
					if (data.availableForCheckout){
						AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overDriveId);
					}else{
						// noinspection JSUnresolvedReference
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		followOverDriveDownloadLink: function(patronId, overDriveId, formatId, isSupplement){
			var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=getDownloadLink&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + formatId + "&isSupplement=" + isSupplement;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.success){
						// noinspection JSUnresolvedReference
						AspenDiscovery.showMessageWithButtons(data.message, data.modalBody, data.modalButtons);
					}else{
						AspenDiscovery.showMessage('Error', data.message);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				}
			});
		},

		getOverDriveHoldPrompts: function(overDriveId){
			var url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getHoldPrompts";
			var result = false;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.success){
						result = data;
						// noinspection JSUnresolvedReference
						if (data.promptNeeded){
							// noinspection JSUnresolvedReference
							AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
						}
					}else{
						AspenDiscovery.showMessage('An Error occurred', data.message);
					}

				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				}
			});
			return result;
		},

		placeHold: function(overDriveId, button){
			if (Globals.loggedIn){
				AspenDiscovery.toggleButtonSpinner(button, true);

				//Get any prompts needed for placing holds (email and format) depending on the interface.
				var promptInfo = AspenDiscovery.OverDrive.getOverDriveHoldPrompts(overDriveId);

				AspenDiscovery.toggleButtonSpinner(button, false);

				// noinspection JSUnresolvedReference
				if (promptInfo !== false && !promptInfo.promptNeeded){
					// noinspection JSUnresolvedReference
					AspenDiscovery.OverDrive.doOverDriveHold(promptInfo.patronId, overDriveId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.placeHold(overDriveId, button);
				}, true);
			}
			return false;
		},

		processOverDriveHoldPrompts: function(){
			var overdriveHoldPromptsForm = $("#overdriveHoldPromptsForm");
			var patronId = $("#patronId").val();
			var overdriveId = overdriveHoldPromptsForm.find("input[name=overdriveId]").val();
			var promptForOverdriveEmail;
			if (overdriveHoldPromptsForm.find("input[name=promptForOverdriveEmail]").is(":checked")){
				promptForOverdriveEmail = 0;
			}else{
				promptForOverdriveEmail = 1;
			}
			var overdriveEmail = overdriveHoldPromptsForm.find("input[name=overdriveEmail]").val();
			AspenDiscovery.OverDrive.doOverDriveHold(patronId, overdriveId, overdriveEmail, promptForOverdriveEmail);
		},

		renewCheckout: function(patronId, recordId){
			var url = Globals.path + "/OverDrive/AJAX?method=renewCheckout&patronId=" + patronId + "&overDriveId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					}else{
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, overDriveId){
			if (confirm('Are you sure you want to return this title?')){
				AspenDiscovery.showMessage("Returning Title", "Returning your title in OverDrive.  This may take a minute.");
				var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=returnCheckout&patronId=" + patronId + "&overDriveId=" + overDriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						AspenDiscovery.showMessage(
							data.success ? 'Title Returned' : 'Unable to Return Title', data.message, data.success
						);
						if (data.success){
							$(".overdrive_checkout_" + overDriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Returning Title", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					}
				});
			}
			return false;
		},

		getStaffView: function (id) {
			var url = Globals.path + "/OverDrive/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					// noinspection JSUnresolvedReference
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		showPreview: function (overdriveId, formatId, sampleNumber) {
			var url = Globals.path + "/OverDrive/" + overdriveId + "/AJAX?method=getPreview&formatId=" + formatId + "&sampleNumber=" + sampleNumber;
			$.getJSON(url, function (data){
				if (data.success){
					// noinspection JSUnresolvedReference
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage('Error', data.message);
				}
			});
			return false;
		},

		getLargeCover: function (id){
			var url = Globals.path + '/OverDrive/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					// noinspection JSUnresolvedReference
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		}
	}
}(AspenDiscovery.OverDrive || {}));