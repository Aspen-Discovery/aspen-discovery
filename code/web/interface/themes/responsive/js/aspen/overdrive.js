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
				patronId : patronId
				,overDriveId : overDriveId
			};
			//Prompt the user for the date they want to reactivate the hold
			params['method'] = 'getReactivationDateForm'; // set method for this form
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).error(AspenDiscovery.ajaxFail);
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			var popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			var params = {
				'method' : 'freezeHold'
				,patronId : $('#patronId').val()
				,overDriveId : $('#overDriveId').val()
				,reactivationDate : $("#reactivationDate").val()
			};
			var url = Globals.path + '/OverDrive/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
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

		getCheckOutPrompts: function(overDriveId){
			var url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getCheckOutPrompts";
			var result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					result = data;
					if (data.promptNeeded){
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
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

		checkOutTitle: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				var promptInfo = AspenDiscovery.OverDrive.getCheckOutPrompts(overDriveId, 'hold');
				if (!promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveCheckout(promptInfo.patronId, overDriveId);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overDriveId);
				});
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
					if (data.availableForCheckout){
						AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
					}else{
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

		followOverDriveDownloadLink: function(patronId, overDriveId, formatId){
			var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=getDownloadLink&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + formatId;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.success){
						//Reload the page
						var win = window.open(data.downloadUrl, '_blank');
						win.focus();
						//window.location.href = data.downloadUrl ;
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
						if (data.promptNeeded){
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

		placeHold: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				var promptInfo = AspenDiscovery.OverDrive.getOverDriveHoldPrompts(overDriveId, 'hold');
				if (promptInfo !== false && !promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveHold(promptInfo.patronId, overDriveId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.placeHold(overDriveId);
				});
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
						AspenDiscovery.showMessage("Title Returned", data.message, data.success);
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

		selectOverDriveDownloadFormat: function(patronId, overDriveId, time){
			var selectedOption = $("#downloadFormat_" + overDriveId + "_" + time + " option:selected");
			var selectedFormatId = selectedOption.val();
			var selectedFormatText = selectedOption.text();
			// noinspection EqualityComparisonWithCoercionJS
			if (selectedFormatId == -1){
				alert("Please select a format to download.");
			}else{
				if (confirm("Are you sure you want to download the " + selectedFormatText + " format? You cannot change format after downloading.")){
					var ajaxUrl = Globals.path + "/OverDrive/AJAX?method=selectOverDriveDownloadFormat&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + selectedFormatId;
					$.ajax({
						url: ajaxUrl,
						cache: false,
						success: function(data){
							if (data.success){
								//Reload the page
								window.location.href = data.downloadUrl;
							}else{
								AspenDiscovery.showMessage("Error Selecting Format", data.message);
							}
						},
						dataType: 'json',
						async: false,
						error: function(){
							AspenDiscovery.showMessage("Error Selecting Format", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
						}
					});
				}
			}
			return false;
		},

		getStaffView: function (id) {
			var url = Globals.path + "/OverDrive/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		showPreview: function (overdriveId, formatId, sampleNumber) {
			var url = Globals.path + "/OverDrive/" + overdriveId + "/AJAX?method=getPreview&formatId=" + formatId + "&sampleNumber=" + sampleNumber;
			$.getJSON(url, function (data){
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage('Error', data.message);
				}
			});
		}
	}
}(AspenDiscovery.OverDrive || {}));