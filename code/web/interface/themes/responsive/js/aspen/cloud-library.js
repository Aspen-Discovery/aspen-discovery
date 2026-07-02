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
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle(patronId, id, button) {
			if (Globals.loggedIn) {
				AspenDiscovery.toggleButtonSpinner(button, true);

				AspenDiscovery.CloudLibrary.getCheckOutPrompts(id, function(promptInfo) {
					AspenDiscovery.toggleButtonSpinner(button, false);

					// noinspection JSUnresolvedVariable
					if (promptInfo && !promptInfo.promptNeeded) {
						AspenDiscovery.CloudLibrary.doCheckOut(promptInfo.patronId, id);
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.checkOutTitle(patronId, id, button);
				}, false);
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
						alert(__('An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.'));
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
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts(id, callback) {
			const url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getCheckOutPrompts";
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
					if (callback) callback(data);
				},
				dataType: 'json',
				async: true,
				error: function () {
					alert(__('An error occurred processing your request.  Please try again in a few minutes.'));
					AspenDiscovery.closeLightbox();
					if (callback) callback(false);
				}
			});
		},

		getHoldPrompts: function (id) {
			var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getHoldPrompts";
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
					alert(__('An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.'));
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold(id, button) {
			if (Globals.loggedIn) {
				AspenDiscovery.toggleButtonSpinner(button, true);
				const promptInfo = AspenDiscovery.CloudLibrary.getHoldPrompts(id, 'hold');
				AspenDiscovery.toggleButtonSpinner(button, false);

				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.CloudLibrary.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.placeHold(id, button);
				}, false);
			}
			return false;
		},

		processCheckoutPrompts: function () {
			var id = $("#id").val();
			var patronId = $("#patronId option:selected").val();
			var useAlternateCard = $("#useAlternateLibraryCard").val();
			var validCard = $("#patronId option:selected").attr("data-valid-card");
			if (useAlternateCard == 0 || validCard === "1") {
				return AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
			} else {
				var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=prepareAlternateLibraryCardPrompts&type=checkOutTitle&patronId=" + patronId;
				var result = true;
				$.ajax({
					url: url,
					cache: false,
					success: function (data) {
						result = data;
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert(__('An error occurred processing your request.  Please try again in a few minutes.'));
						AspenDiscovery.closeLightbox();
					}
				});
				return result;
			}
		},

		processHoldPrompts: function () {
			var id = $("#id").val();
			var patronId = $("#patronId option:selected").val();
			var useAlternateCard = $("#useAlternateLibraryCard").val();
			var validCard = $("#patronId option:selected").attr("data-valid-card");
			if (useAlternateCard == 0 || validCard === "1") {
				return AspenDiscovery.CloudLibrary.doHold(patronId, id);
			} else {
				var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=prepareAlternateLibraryCardPrompts&type=placeHold&patronId=" + patronId;
				var result = true;
				$.ajax({
					url: url,
					cache: false,
					success: function (data) {
						result = data;
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert(__('An error occurred processing your request.  Please try again in a few minutes.'));
						AspenDiscovery.closeLightbox();
					}
				});
				return result;
			}
		},

		renewCheckout: function (patronId, recordId) {
			var $checkoutRow = $('.cloudLibraryCheckout_' + recordId);
			var $renewButton = $checkoutRow.find('a').filter(function() {
				return $(this).text().indexOf('Renew Checkout') >= 0;
			});

			var originalButtonText = $renewButton.html();
			$renewButton.html('<i class="fas fa-spinner fa-spin"></i> Renewing...');
			$renewButton.addClass('disabled').attr('disabled', 'disabled');

			var $expiresRow = $checkoutRow.find('.row').filter(function() {
				var label = $(this).find('.result-label').text().trim();
				return label === 'Expires' || label.indexOf('Expires') === 0;
			});

			$expiresRow.css('background-color', '#f0f0f0');
			var pulseEffect = setInterval(function() {
				$expiresRow.fadeTo(700, 0.7).fadeTo(700, 1);
			}, 1400);

			var url = Globals.path + "/CloudLibrary/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					clearInterval(pulseEffect);
					$expiresRow.stop(true, true).css('opacity', '1');

					$renewButton.html(originalButtonText);
					$renewButton.removeClass('disabled').removeAttr('disabled');

					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);

						AspenDiscovery.Account.loadMenuData();

						if (data.dueDate) {
							var $dueDateElement = $expiresRow.find('.result-value');

							if ($dueDateElement.length) {
								$dueDateElement.text(data.dueDate);

								$expiresRow.css('background-color', '#dff0d8');
								setTimeout(function() {
									$expiresRow.css('background-color', '');
								}, 5000);

								$renewButton.closest('.btn-group').find('a').filter(function() {
									return $(this).text().indexOf('Renew Checkout') >= 0;
								}).hide();

								setTimeout(function() {
									var currentSource = 'cloud_library';
									if (AspenDiscovery.Account.currentCheckoutsSource) {
										currentSource = AspenDiscovery.Account.currentCheckoutsSource;
									}

									var sort = $('#accountSort_' + currentSource).length ?
										$('#accountSort_' + currentSource + ' option:selected').val() : 'title';
									var $coversEl = $('#hideCovers_' + currentSource);
									var showCovers = $coversEl.length ? !$coversEl.is(':checked') : true;

									AspenDiscovery.Account.loadCheckouts(currentSource, sort, showCovers);
								}, 2000);
							}
						}
					} else {
						var errorTitle = data.api && data.api.title ? data.api.title : "Unable to Renew Title";
						AspenDiscovery.showMessage(errorTitle, data.message, true);

						$expiresRow.css('background-color', '#f2dede');
						setTimeout(function() {
							$expiresRow.css('background-color', '');
						}, 3000);
					}
				},
				dataType: 'json',
				async: true,
				error: function () {
					clearInterval(pulseEffect);
					$expiresRow.stop(true, true).css('opacity', '1');

					$renewButton.html(originalButtonText);
					$renewButton.removeClass('disabled').removeAttr('disabled');

					$expiresRow.css('background-color', '#f2dede');
					setTimeout(function() {
						$expiresRow.css('background-color', '');
					}, 3000);

					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in cloudLibrary. Please try again in a few minutes.", false);
				}
			});

			// Prevent default anchor behavior
			return false;
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
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.", false);
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

		addAlternateLibraryCard: function () {
			var id = $("#id").val();
			var patronId = $("#patronId").val();
			var type = $("#type").val();
			var url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=addAlternateLibraryCard&type=" + type;
			var alternateLibraryCard = $("#alternateLibraryCard").val();
			var alternateLibraryCardPassword = $("#alternateLibraryCardPassword").val();
			$.ajax({
				url: url,
				cache: false,
				type: "POST",
				data:  JSON.stringify({
					alternateLibraryCard: alternateLibraryCard,
					alternateLibraryCardPassword: alternateLibraryCardPassword,
					patronId: patronId,
				}),
				success: function (data) {
					if (data.success) {
						if (type === "checkOutTitle") {
							AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
						} else if (type === "placeHold") {
							AspenDiscovery.CloudLibrary.doHold(patronId, id);
						} else {
							AspenDiscovery.showMessage("Card Added", data.message, false);
						}
					} else {
						AspenDiscovery.showMessage("Error Adding Card", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Adding Card", "An error occurred processing your request in cloudLibrary.  Please try again in a few minutes.", false);
				}
			});
		},
	}
}(AspenDiscovery.CloudLibrary || {}));