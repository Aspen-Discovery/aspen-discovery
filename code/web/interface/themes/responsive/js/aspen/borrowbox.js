AspenDiscovery.BorrowBox = (function () {
	return {
		getStaffView: function (id) {
			var url = Globals.path + "/BorrowBox/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getCheckOutPrompts(id, callback) {
			const url = Globals.path + "/BorrowBox/" + id + "/AJAX?method=getCheckOutPrompts";
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.promptNeeded) {
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
					if (callback) callback(data);
				},
				dataType: 'json',
				async: true,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
					if (callback) callback(false);
				}
			});
		},

		checkOutTitle(id, button) {
			if (Globals.loggedIn) {
				AspenDiscovery.toggleButtonSpinner(button, true);

				AspenDiscovery.BorrowBox.getCheckOutPrompts(id, function(promptInfo) {
					AspenDiscovery.toggleButtonSpinner(button, false);

					if (promptInfo && !promptInfo.promptNeeded) {
						AspenDiscovery.BorrowBox.doCheckOut(promptInfo.patronId, id);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.BorrowBox.checkOutTitle(id, button);
				}, false);
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				var ajaxUrl = Globals.path + "/BorrowBox/AJAX?method=checkOutTitle&patronId=" + patronId + "&borrowboxId=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.closeLightbox(function (){
								AspenDiscovery.showMessageWithButtons("Title Checked Out Successfully", data.message, data.buttons);
								AspenDiscovery.Account.loadMenuData();
							});
						} else if (data.noCopies === true) {
							AspenDiscovery.closeLightbox();
							if (confirm(data.message)) {
								AspenDiscovery.BorrowBox.placeHold(id);
							}
						} else {
							AspenDiscovery.showMessage("Error Checking Out Title", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in BorrowBox.  Please try again in a few minutes.");
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.BorrowBox.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, recordId, encodedId) {
			if (!confirm('Are you sure you want to return this title?')) {
				return false;
			}
			var url = Globals.path + "/BorrowBox/AJAX?method=returnCheckout&patronId=" + patronId + "&borrowboxId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					AspenDiscovery.showMessage(data.success ? 'Title Returned' : 'Unable to Return Title', data.message, data.success);
					if (data.success) {
						$(".borrowbox_checkout_" + encodedId + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in BorrowBox.  Please try again in a few minutes.", false);
				}
			});
			return false;
		},

		placeHold(id, button) {
			if (Globals.loggedIn) {
				AspenDiscovery.toggleButtonSpinner(button, true);
				const promptInfo = AspenDiscovery.BorrowBox.getHoldPrompts(id);
				AspenDiscovery.toggleButtonSpinner(button, false);

				if (promptInfo && !promptInfo.promptNeeded) {
					AspenDiscovery.BorrowBox.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.BorrowBox.placeHold(id, button);
				}, false);
			}
			return false;
		},

		getHoldPrompts: function (id) {
			var url = Globals.path + "/BorrowBox/" + id + "/AJAX?method=getHoldPrompts";
			var result = false;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					if (data.promptNeeded) {
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request in BorrowBox.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		doHold: function (patronId, id) {
			var url = Globals.path + "/BorrowBox/AJAX?method=placeHold&patronId=" + patronId + "&borrowboxId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.closeLightbox(function (){
							AspenDiscovery.showMessage("Placed Hold", data.message, true);
							AspenDiscovery.Account.loadMenuData();
						});
					} else {
						AspenDiscovery.showMessage("Error Placing Hold", data.message, false);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in BorrowBox.  Please try again in a few minutes.", false);
				}
			});
			return true;
		},

		cancelHold: function (patronId, id, encodedId) {
			var url = Globals.path + "/BorrowBox/AJAX?method=cancelHold&patronId=" + patronId + "&borrowboxId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$(".borrowboxHold_" + id + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in BorrowBox.  Please try again in a few minutes.", false);
				}
			});
		},

		renewCheckout: function (patronId, id) {
			var url = Globals.path + "/BorrowBox/AJAX?method=renewCheckout&patronId=" + patronId + "&borrowboxId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, false);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in BorrowBox.  Please try again in a few minutes.", false);
				}
			});
			return false;
		},

		processBorrowBoxCheckoutPrompts: function () {
			var id = $("#borrowboxId").val();
			var patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.BorrowBox.doCheckOut(patronId, id);
		},

		processBorrowBoxHoldPrompts: function () {
			var id = $("#borrowboxId").val();
			var patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.BorrowBox.doHold(patronId, id);
		}
	}
}(AspenDiscovery.BorrowBox || {}));
