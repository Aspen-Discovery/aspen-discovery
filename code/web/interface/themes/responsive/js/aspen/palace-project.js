AspenDiscovery.PalaceProject = (function () {
	return {

		getStaffView: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		showPreview: function (palaceProjectId) {
			var url = Globals.path + "/PalaceProject/" + palaceProjectId + "/AJAX?method=getPreview";
			$.getJSON(url, function (data){
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage('Error', data.message);
				}
			});
		},

		getLargeCover: function (id){
			var url = Globals.path + '/PalaceProject/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		getCheckOutPrompts: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getCheckOutPrompts";
			var result = false;
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

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				var promptInfo = AspenDiscovery.PalaceProject.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.PalaceProject.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.PalaceProject.checkOutTitle(id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				var ajaxUrl = Globals.path + "/PalaceProject/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
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
								AspenDiscovery.closeLightbox(function (){
									var ret = confirm(data.message);
									if (ret === true) {
										AspenDiscovery.PalaceProject.doHold(patronId, id);
									}
								});
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Palace Project.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.PalaceProject.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, recordId, encodedId) {
			var url = Globals.path + "/PalaceProject/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, true);
						$(".palace_project_checkout_" + encodedId + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage(data.title, data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in Palace Project.  Please try again in a few minutes.", false);
				}
			});
			return false;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				var promptInfo = AspenDiscovery.PalaceProject.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.PalaceProject.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.PalaceProject.placeHold(id);
				});
			}
			return false;
		},

		getHoldPrompts: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getHoldPrompts";
			var result = false;
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
					alert("An error occurred processing your request in Palace Project.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		doHold: function (patronId, id) {
			var url = Globals.path + "/PalaceProject/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					AspenDiscovery.closeLightbox(function (){
						// noinspection JSUnresolvedVariable
						if (data.availableForCheckout) {
							AspenDiscovery.PalaceProject.doCheckOut(patronId, id);
						} else {
							AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
							AspenDiscovery.Account.loadMenuData();
						}
					});
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in Palace Project.  Please try again in a few minutes.", false);
				}
			});
			return true;
		},

		cancelHold: function (patronId, id, encodedId) {
			var url = Globals.path + "/PalaceProject/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$(".palace_projectHold_" + id + "_" + patronId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Palace Project.  Please try again in a few minutes.", false);
				}
			});
		},

		showUsageInstructions: function () {
			var url = Globals.path + "/PalaceProject/AJAX?method=getUsageInstructions";
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message);
					} else {
						AspenDiscovery.showMessage("Error Loading Instructions", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Loading Instructions", "An error occurred loading instructions.  Please try again in a few minutes.", false);
				}
			});
		}
	}
}(AspenDiscovery.PalaceProject || {}));