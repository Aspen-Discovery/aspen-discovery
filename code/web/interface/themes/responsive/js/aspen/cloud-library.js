AspenDiscovery.CloudLibrary = (function () {
	return {
		cancelHold: function (patronId, id) {
			var url = Globals.path + "/CloudLibrary/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$("#cloudLibraryHold_" + id).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle: function (patronId, id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				var promptInfo = AspenDiscovery.CloudLibrary.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.CloudLibrary.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.checkOutTitle(patronId, id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				var ajaxUrl = Globals.path + "/CloudLibrary/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
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
									AspenDiscovery.CloudLibrary.doHold(patronId, id);
								}
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Cloud Library.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		doHold: function (patronId, id) {
			var url = Globals.path + "/CloudLibrary/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.availableForCheckout) {
						AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
					} else {
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts: function (id) {
			var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getCheckOutPrompts";
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
			var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getHoldPrompts";
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
					alert("An error occurred processing your request in Cloud Library.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				var promptInfo = AspenDiscovery.CloudLibrary.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.CloudLibrary.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.placeHold(id);
				});
			}
			return false;
		},

		processCheckoutPrompts: function () {
			var id = $("#id").val();
			var patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
		},

		processHoldPrompts: function () {
			var id = $("#id").val();
			var patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.CloudLibrary.doHold(patronId, id);
		},

		renewCheckout: function (patronId, recordId) {
			var url = Globals.path + "/CloudLibrary/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
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
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, recordId) {
			var url = Globals.path + "/CloudLibrary/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Returned", data.message, true);
						$(".cloudLibraryCheckout_" + recordId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Title", data.message, true);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		getStaffView: function (id) {
			var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getLargeCover: function (id){
			var url = Globals.path + '/CloudLibrary/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},
	}
}(AspenDiscovery.CloudLibrary || {}));