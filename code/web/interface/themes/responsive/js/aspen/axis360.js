AspenDiscovery.Axis360 = (function () {
	return {
		cancelHold: function (patronId, id) {
			var url = Globals.path + "/Axis360/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$(".axis360Hold_" + id + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				var promptInfo = AspenDiscovery.Axis360.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.Axis360.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.checkOutTitle(id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				var ajaxUrl = Globals.path + "/Axis360/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							if (data.noCopies === true) {
								AspenDiscovery.closeLightbox();
								var ret = confirm(data.message);
								if (ret === true) {
									AspenDiscovery.Axis360.doHold(patronId, id);
								}
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Axis 360.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		doHold: function (patronId, id, axis360Email, promptForAxis360Email) {
			var url = Globals.path + "/Axis360/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id + "&axis360Email=" + axis360Email + "&promptForAxis360Email=" + promptForAxis360Email;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.availableForCheckout) {
						AspenDiscovery.Axis360.doCheckOut(patronId, id);
					} else {
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts: function (id) {
			var url = Globals.path + "/Axis360/" + id + "/AJAX?method=getCheckOutPrompts";
			var result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		getHoldPrompts: function (id) {
			var url = Globals.path + "/Axis360/" + id + "/AJAX?method=getHoldPrompts";
			var result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request in Axis 360.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				var promptInfo = AspenDiscovery.Axis360.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.Axis360.doHold(promptInfo.patronId, id, promptInfo.axis360Email, promptInfo.promptForAxis360Email);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.placeHold(id);
				});
			}
			return false;
		},

		processCheckoutPrompts: function () {
			var id = $("#id").val();
			var checkoutType = $("#checkoutType").val();
			var patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.Axis360.doCheckOut(patronId, id);
		},

		processHoldPrompts: function () {
			var axis360HoldPromptsForm = $("#holdPromptsForm");
			var id = $("#id").val();
			var patronId = $("#patronId option:selected").val();
			var promptForAxis360Email;
			if (axis360HoldPromptsForm.find("input[name=promptForAxis360Email]").is(":checked")){
				promptForAxis360Email = 0;
			}else{
				promptForAxis360Email = 1;
			}
			var axis360Email = axis360HoldPromptsForm.find("input[name=axis360Email]").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.Axis360.doHold(patronId, id, axis360Email, promptForAxis360Email);
		},

		renewCheckout: function (patronId, recordId) {
			var url = Globals.path + "/Axis360/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					} else {
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, recordId, transactionId) {
			var url = Globals.path + "/Axis360/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + transactionId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Returned", data.message, true);
						$(".axis360Checkout_" + recordId + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		getStaffView: function (id) {
			var url = Globals.path + "/Axis360/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		freezeHold: function(patronId, recordId){
			AspenDiscovery.loadingMessage();
			var url = Globals.path + '/Axis360/AJAX';
			var params = {
				'method' : 'freezeHold',
				patronId : patronId,
				recordId : recordId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		thawHold: function(patronId, recordId, caller){
			var popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			var url = Globals.path + '/Axis360/AJAX';
			var params = {
				'method' : 'thawHold',
				patronId : patronId,
				recordId : recordId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		getLargeCover: function (id){
			var url = Globals.path + '/Axis360/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},
	}
}(AspenDiscovery.Axis360 || {}));