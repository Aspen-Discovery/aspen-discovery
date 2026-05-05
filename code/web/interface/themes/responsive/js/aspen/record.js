AspenDiscovery.Record = (function () {
	// noinspection JSUnusedGlobalSymbols
	return {
		volumeHoldInProgress: false,
		showPlaceHold: function (module, source, id, volume, variationId, button) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var buttonClicked = $(button);
				var promptEditionData = buttonClicked.attr('data-prompt-edition');
				var promptForEdition = false;
				if (promptEditionData === "true") {
					promptForEdition = true;
				}

				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source + "&promptForEdition=" + promptForEdition;
				if (volume !== undefined) {
					url += "&volume=" + volume;
				}
				if (variationId !== undefined) {
					url += "&variationId=" + variationId;
				}

				AspenDiscovery.toggleButtonSpinner(button, true);

				$.getJSON(url, function (data) {
					let existingButton;
					AspenDiscovery.toggleButtonSpinner(button, false);
					if (data.holdFormBypassed) {
						if (data.success) {
							if (data.needsItemLevelHold) {
								AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
							} else {
								existingButton = $("#onHoldAction" + id);
								if (existingButton.length === 0) {
									$(data.viewHoldsAction).insertBefore('#actionButton' + id);
									$(data.viewHoldsAction).insertBefore('#relatedRecordactionButton' + id);
									$(data.viewHoldsAction).insertBefore('#firstRecordactionButton' + id);
								}
								AspenDiscovery.showMessage(data.title, data.message, false, false);
								AspenDiscovery.Account.loadMenuData();
							}
						} else if (data.confirmationNeeded) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
						} else {
							existingButton = $("#onHoldAction" + id);
							if (existingButton.length === 0) {
								$(data.viewHoldsAction).insertBefore('#actionButton' + id);
								$(data.viewHoldsAction).insertBefore('#relatedRecordactionButton' + id);
								$(data.viewHoldsAction).insertBefore('#firstRecordactionButton' + id);
							}
							AspenDiscovery.showMessage(data.title, data.message, false, false);
						}
					} else {
						if (data.success) {
							AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons, false);
						} else {
							AspenDiscovery.showMessage(data.title, data.message);
						}
					}
					AspenDiscovery.Account.reloadHolds();
				}).fail(function() {
					AspenDiscovery.toggleButtonSpinner(button, false);
					AspenDiscovery.ajaxFail.apply(this, arguments);
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showPlaceHold(module, source, id, volume, variationId, button);
				}, false);
			}
			return false;
		},

		showVdxRequest: function (module, source, id) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getVdxRequestForm&recordSource=" + source;
				$.getJSON(url, function (data) {
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showVdxRequest(module, source, id);
				}, false);
			}
			return false;
		},

		submitVdxRequest: function (module, id) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var params = {
					'method': 'submitVdxRequest',
					title: $('#title').val(),
					author: $('#author').val(),
					publisher: $('#publisher').val(),
					isbn: $('#isbn').val(),
					oclcNumber: $('#oclcNumber').val(),
					maximumFeeAmount: $('#maximumFeeAmount').val(),
					acceptFee: $('#acceptFee').prop('checked'),
					pickupLocation: $('#pickupLocationSelect').val(),
					catalogKey: $('#catalogKey').val(),
					note: $('#note').val()
				};
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=submitVdxRequest";
				$.getJSON(url, params, function (data) {
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
					} else {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showVdxRequest(module, source, id, volume);
				}, false);
			}
			return false;
		},

		showLocalIllRequest: function (module, source, id, volume) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getLocalIllRequestForm&recordSource=" + source;
				if (volume !== undefined) {
					url += "&volume=" + volume;
				}
				$.getJSON(url, function (data) {
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showLocalIllRequest(module, source, id, volume);
				}, false);
			}
			return false;
		},

		submitLocalIllRequest: function (module, id) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var acceptFeeField = $('#acceptFee');
				if (acceptFeeField !== undefined && acceptFeeField.prop("required")) {
					if (!acceptFeeField.prop('checked')) {
						alert("You must agree to pay any fees associated with this requests before continuing.");
						return false;
					}
				}
				var volumeId;
				var volumeIdField = $('#volumeId');
				var volumeIdSelectField = $('#volumeIdSelect option:selected');
				var volumeSelected = false;
				if (volumeIdSelectField !== undefined) {
					volumeId = volumeIdSelectField.val()
					volumeSelected = true;
				} else if (volumeIdField !== undefined) {
					volumeId = volumeIdField.val();
					volumeSelected = true;
				}
				var params = {
					'method': 'submitLocalIllRequest',
					title: $('#title').val(),
					author: $('#author').val(),
					publisher: $('#publisher').val(),
					isbn: $('#isbn').val(),
					oclcNumber: $('#oclcNumber').val(),
					maximumFeeAmount: $('#maximumFeeAmount').val(),
					acceptFee: acceptFeeField.prop('checked'),
					pickupLocation: $('#pickupLocationSelect').val(),
					catalogKey: $('#catalogKey').val(),
					note: $('#note').val(),
					volumeId: volumeId,
					volumeSelected: volumeSelected
				};
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=submitLocalIllRequest";
				$.getJSON(url, params, function (data) {
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
						var existingButton = $("#onHoldAction" + id);
						if (existingButton.length === 0) {
							$(data.viewHoldsAction).insertBefore('#actionButton' + id);
							$(data.viewHoldsAction).insertBefore('#relatedRecordactionButton' + id);
							$(data.viewHoldsAction).insertBefore('#firstRecordactionButton' + id);
						}
						if (!data.autologout) {
							AspenDiscovery.Account.loadMenuData();
						}
					} else {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showLocalIllRequest(module, source, id, volume);
				}, false);
			}
			return false;
		},

		showPlaceHoldEditions: function (module, source, id, volume, variationId, button) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldEditionsForm&recordSource=" + source;
				if (volume !== undefined) {
					url += "&volume=" + volume;
				}
				if (variationId !== undefined) {
					url += "&variationId=" + variationId;
				}

				AspenDiscovery.toggleButtonSpinner(button, true);

				$.getJSON(url, function (data) {
					document.body.style.cursor = "default";
					AspenDiscovery.toggleButtonSpinner(button, false);
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(function() {
					document.body.style.cursor = "default";
					AspenDiscovery.toggleButtonSpinner(button, false);
					AspenDiscovery.ajaxFail.apply(this, arguments);
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showPlaceHoldEditions(module, source, id, volume, variationId, button);
				}, false);
			}
			return false;
		},

		showPlaceHoldVolumes: function (module, source, id, button) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				var buttonClicked = $(button);
				var promptEditionData = buttonClicked.attr('data-promptEdition');
				if (typeof promptEditionData === 'undefined') {
					promptEditionData = buttonClicked.attr('data-promptedition');
				}
				var promptForEdition = false;
				if (promptEditionData === "true") {
					promptForEdition = true;
				}

				const url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldVolumesForm&recordSource=" + source + "&promptForEdition=" + promptForEdition;

				AspenDiscovery.toggleButtonSpinner(button, true);

				$.getJSON(url, function (data) {
					document.body.style.cursor = "default";
					AspenDiscovery.toggleButtonSpinner(button, false);
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(function() {
					document.body.style.cursor = "default";
					AspenDiscovery.toggleButtonSpinner(button, false);
					AspenDiscovery.ajaxFail.apply(this, arguments);
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showPlaceHoldVolumes(module, source, id, button);
				}, false);
			}
			return false;
		},

		submitHoldForm: function () {
			const requestTitleButton = $('#requestTitleButton');
			AspenDiscovery.toggleButtonSpinner(requestTitleButton, true);

			document.body.style.cursor = "wait";
			const id = $('#id').val();

			const targetButton = $('#actionButton' + id);
			AspenDiscovery.toggleButtonSpinner(targetButton, true);

			const autoLogOut = $('#autologout').prop('checked');
			const selectedItem = $('#selectedItem');
			const module = $('#module').val();
			const volume = $('#volume');
			const variationId = $('#variationId');
			const pickupSublocation = $('#pickupSublocation');
			const cancelDateInput = $('#cancelDate');
			let params = {
				'method': 'placeHold',
				pickupBranch: $('#pickupBranch').val(),
				pickupSublocation: pickupSublocation === undefined ? '' : pickupSublocation.val(),
				selectedUser: $('#user').val(),
				cancelDate: cancelDateInput.val(),
				recordSource: $('#recordSource').val(),
				account: $('#account').val(),
				rememberHoldPickupLocation: $('#rememberHoldPickupLocation').prop('checked'),
				promptForEdition: $('#holdPromptForEditions').val(),
				freezeHoldImmediately: $('#freezeHoldImmediately').prop('checked'),
				reactivationDate: $('#reactivationDate').val()
			};
			if (autoLogOut) {
				params['autologout'] = true;
			}
			if (selectedItem.length > 0) {
				params['selectedItem'] = selectedItem.val();
			}
			if (volume.length > 0) {
				params['volume'] = volume.val();
			}
			if (variationId.length > 0) {
				params['variationId'] = variationId.val();
			}
			if (params['pickupBranch'] === 'undefined') {
				alert("Please select a location to pick up your hold when it is ready.");
				return false;
			}
			const holdType = $('#holdType');
			if (holdType.length > 0) {
				params['holdType'] = holdType.val();
				if (holdType.val() === 'item' && selectedItem.val().length === 0) {
					alert("Please select an item to place your hold on");
					AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);
					document.body.style.cursor = "pointer";
					return false;
				} else if (holdType.val() === 'volume' && volume.val().length === 0) {
					alert("Please select a volume to place your hold on");
					AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);
					document.body.style.cursor = "pointer";
					return false;
				}
			} else {
				if ($('#holdTypeBib').is(':checked')) {
					params['holdType'] = 'bib';
				} else {
					params['holdType'] = 'item';
					if (selectedItem.val().length === 0) {
						alert("Please select an item to place your hold on");
						AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);
						document.body.style.cursor = "pointer";
						return false;
					}
				}
			}

			if (params['promptForEdition'] == '1' || params['promptForEdition'] == '2') {
				params['placeHoldOnEdition'] = $('#selectedEditionOption').val();
				params['selectedEdition'] = $('input[name="editionOption"]:checked').val();
				params['rememberUserEditionPreference'] = $('#rememberEditionSelection').prop('checked');
			}

			params = this.loadHoldNotificationOptions(params);

			const cancelDate = cancelDateInput.val();
			if (cancelDate) {
				const today = new Date().getTime();
				const cancelOn = new Date(cancelDate).getTime();

				if (today > cancelOn) {
					$("#cancelHoldDateHelpBlock").show();
					AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);
					document.body.style.cursor = "pointer";
					return false;
				} else {
					$("#cancelHoldDateHelpBlock").hide();
				}
			}

			$("#placeHoldForm").hide();
			$("#placingHoldMessage").show();
			$.getJSON(Globals.path + "/" + module + "/" + id + "/AJAX", params, function (data) {
				document.body.style.cursor = "default";
				AspenDiscovery.toggleButtonSpinner(targetButton, false);
				if (data.success) {
					if (data.needsItemLevelHold) {
						const requestTitleButton = $('#requestTitleButton');
						AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);

						$("#placeHoldForm").show();
						$("#placingHoldMessage").hide();
						$('.modal-body').html(data.message);
					} else if (data.needsIllRequest) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons, data.autologout);
						var existingButton = $("#onHoldAction" + id);
						if (existingButton.length === 0) {
							$(data.viewHoldsAction).insertBefore('#actionButton' + id);
							$(data.viewHoldsAction).insertBefore('#relatedRecordactionButton' + id);
							$(data.viewHoldsAction).insertBefore('#firstRecordactionButton' + id);
						}
						if (!data.autologout) {
							AspenDiscovery.Account.loadMenuData();
						}
					}
				} else if (data.confirmationNeeded) {
					AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false, false);
				}
				AspenDiscovery.Account.reloadHolds()
			}).fail(AspenDiscovery.ajaxFail);
		},

		loadHoldNotificationOptions: function (params) {
			var emailNotification = $('#emailNotification');
			if (emailNotification.length > 0) {
				if (emailNotification.is(':checked')) {
					params['emailNotification'] = 'on';
				} else {
					params['emailNotification'] = 'off';
				}
			}
			var phoneNotification = $('#phoneNotification');
			if (phoneNotification.length > 0) {
				if (phoneNotification.is(':checked')) {
					params['phoneNotification'] = 'on';
				} else {
					params['phoneNotification'] = 'off';
				}
			}
			var phoneNumber = $('#phoneNumber');
			if (phoneNumber.length > 0) {
				params['phoneNumber'] = phoneNumber.val();
			}
			var smsNotification = $('#smsNotification');
			if (smsNotification.length > 0) {
				if (smsNotification.is(':checked')) {
					params['smsNotification'] = 'on';
				} else {
					params['smsNotification'] = 'off';
				}
			}
			var smsNumber = $('#smsNumber');
			if (smsNumber.length > 0) {
				params['smsNumber'] = smsNumber.val();
			}
			var smsCarrier = $('#smsCarrier');
			if (smsCarrier.length > 0) {
				params['smsCarrier'] = $("#smsCarrier option:selected").val();
			}
			return params;
		},

		placeVolumeHold: function (button) {
			// Prevent multiple volume hold submissions; button state alone is insufficient.
			if (this.volumeHoldInProgress) {
				return false;
			}
			this.volumeHoldInProgress = true;

			AspenDiscovery.toggleButtonSpinner(button, true);

			const $volumeSelect = $("#selectedVolume");
			const selectedVolume = $volumeSelect.find("option:selected").val();
			const $holdTypeBib = $("#holdTypeBib");

			$('#holdTypeBib').off('change.volumeValidation');
			$('#selectedVolume').off('change.volumeValidation');
			$("#volumeSelectionError").remove();

			$volumeSelect.on("change.volumeValidation", (e) => {
				if (e.target.value !== "unselected" || $holdTypeBib.is(':checked')) {
					$("#volumeSelectionError").remove();
				}
			});

			// Only validate volume selection when "Specific Volume" radio is checked.
			if (selectedVolume === 'unselected' && (!$holdTypeBib.length || !$holdTypeBib.is(':checked'))){
				const errorHtml = `
					<div id="volumeSelectionError" class="alert alert-danger mt-3" role="alert">
						Please select a volume before attempting to place a hold.
					</div>
				`;
				$('#volumeSelection').prepend(errorHtml);

				this.volumeHoldInProgress = false;
				AspenDiscovery.toggleButtonSpinner(button, false);
				return false;
			}

			const requestTitleButton = $('#requestTitleButton');
			AspenDiscovery.toggleButtonSpinner(requestTitleButton, true);

			const id = $('#id').val();
			const autoLogOut = $('#autologout').prop('checked');
			const module = $('#module').val();

			let params = {
				'method': 'placeHold',
				pickupBranch: $('#pickupBranch').val(),
				selectedUser: $('#user').val(),
				cancelDate: $('#cancelDate').val(),
				recordSource: $('#recordSource').val(),
				account: $('#account').val(),
				rememberHoldPickupLocation: $('#rememberHoldPickupLocation').prop('checked'),
				promptForEdition: $('#holdPromptForEditions').val(),
				freezeHoldImmediately: $('#freezeHoldImmediately').prop('checked'),
				reactivationDate: $('#reactivationDate').val()
			};
			if (autoLogOut) {
				params['autologout'] = true;
			}
			if (selectedVolume.length > 0) {
				params['volume'] = selectedVolume;
			}
			if (params['pickupBranch'] === 'undefined') {
				alert("Please select a location to pick up your hold when it is ready.");
				this.volumeHoldInProgress = false;
				AspenDiscovery.toggleButtonSpinner(button, false);
				return false;
			}
			const holdType = $('#holdType');
			if (holdType.length > 0) {
				params['holdType'] = holdType.val();
			} else {
				if ($holdTypeBib.is(':checked')) {
					params['holdType'] = 'bib';
				} else {
					params['holdType'] = 'volume';
				}
			}

			if (params['promptForEdition'] == '1' || params['promptForEdition'] == '2') {
				params['placeHoldOnEdition'] = $('#selectedEditionOption').val();
				params['selectedEdition'] = $('input[name="editionOption"]:checked').val();
				params['rememberUserEditionPreference'] = $('#rememberEditionSelection').prop('checked');
			}

			params = this.loadHoldNotificationOptions(params);

			$("#placeHoldForm").hide();
			$("#placingHoldMessage").show();
			$.getJSON(Globals.path + "/" + module + "/" + id + "/AJAX", params, function (data) {
				if (data.success) {
					if (data.needsItemLevelHold) {
						AspenDiscovery.Record.volumeHoldInProgress = false;
						const requestTitleButton = $('#requestTitleButton');
						AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);

						$("#placeHoldForm").show();
						$("#placingHoldMessage").hide();
						$('.modal-body').html(data.message);
					} else if (data.needsIllRequest) {
						AspenDiscovery.Record.volumeHoldInProgress = false;
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.Record.volumeHoldInProgress = false;
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons, autoLogOut);
						AspenDiscovery.Account.loadMenuData();
					}
				} else {
					AspenDiscovery.Record.volumeHoldInProgress = false;
					AspenDiscovery.showMessage(data.title, data.message, false, autoLogOut);
				}
			}).fail(function() {
				AspenDiscovery.Record.volumeHoldInProgress = false;
				AspenDiscovery.ajaxFail.apply(this, arguments);
			});
		},

		confirmHold: function (module, bibId, confirmationId) {
			const params = {
				'method': 'confirmHold',
				confirmationId: confirmationId
			};
			$.getJSON(Globals.path + "/" + module + "/" + bibId + "/AJAX", params, function (data) {
				if (data.success) {
					if (data.needsItemLevelHold) {
						const requestTitleButton = $('#requestTitleButton');
						AspenDiscovery.toggleButtonSpinner(requestTitleButton, false);
						$('.modal-body').html(data.message);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
						AspenDiscovery.Account.loadMenuData();
					}
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		moreContributors: function () {
			document.getElementById('showAdditionalContributorsLink').style.display = "none";
			document.getElementById('additionalContributors').style.display = "block";
		},

		lessContributors: function () {
			document.getElementById('showAdditionalContributorsLink').style.display = "block";
			document.getElementById('additionalContributors').style.display = "none";
		},

		uploadPDF: function (id) {
			var url = Globals.path + '/Record/' + id + '/AJAX?method=uploadPDF';
			var uploadPDFData = new FormData($("#uploadPDFForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadPDFData,
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

		uploadSupplementalFile: function (id) {
			var url = Globals.path + '/Record/' + id + '/AJAX?method=uploadSupplementalFile';
			var uploadSupplementalFileData = new FormData($("#uploadSupplementalFileForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadSupplementalFileData,
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

		deleteUploadedFile: function (id, fileId) {
			if (confirm("Are you sure you want to delete this file?")) {
				var url = Globals.path + '/Record/' + id + '/AJAX?method=deleteUploadedFile&fileId=' + fileId;
				$.getJSON(url, function (data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				});
			}
			return false;
		},

		getUploadPDFForm: function (id) {
			var url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadPDFForm';
			$.getJSON(url, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		getUploadSupplementalFileForm: function (id) {
			var url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadSupplementalFileForm';
			$.getJSON(url, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileDownload: function (recordId, type) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectDownloadForm',
				type: type
			};
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileToView: function (recordId, type) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectFileToViewForm',
				type: type
			};
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		downloadSelectedFile: function () {
			var id = $('#id').val();
			var fileType = $('#fileType').val();
			var selectedFile = $('#selectedFile').val();
			if (fileType === 'RecordPDF') {
				window.location = Globals.path + '/Record/' + id + '/DownloadPDF?fileId=' + selectedFile;
			} else {
				window.location = Globals.path + '/Record/' + id + '/DownloadSupplementalFile?fileId=' + selectedFile;
			}
			return false;
		},

		viewSelectedFile: function () {
			var selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Files/' + selectedFile + '/ViewPDF';
			return false;
		},

		select856Link: function (recordId) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelect856ToViewForm'
			};
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		view856Link: function () {
			var selected856LinkId = $('#selected856Link').val();
			var id = $('#id').val();
			window.location = Globals.path + '/Record/' + id + '/AJAX?method=View856&linkId=' + selected856LinkId;
			return false;
		},

		getStaffView: function (module, id) {
			var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		forceReindex: function (recordSource, id) {
			var url = Globals.path + '/Record/' + id + '/AJAX';
			var params = {
				method: 'forceReindex',
				recordSource: recordSource
			};
			$.getJSON(url, params, function (data) {
					AspenDiscovery.showMessage("Success", data.message, true, false);
					setTimeout("AspenDiscovery.closeLightbox();", 3000);
				}
			);
			return false;
		},

		selectItemLink: function (recordId, variationId) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectItemToViewForm',
				variationId: variationId
			};
			$("accessOnline_" + recordId).enabled = false;
			$.getJSON(url, params, function (data) {
				$("accessOnline_" + recordId).enabled = true;
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		viewItemLink: function (variationId) {
			var selectedItem = $('#selectedItem').val();
			var id = $('#id').val();
			var url = Globals.path + '/Record/' + id + '/AJAX';
			var params = {
				method: 'viewItem',
				selectedItem: selectedItem,
				variationId: variationId
			};
			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.closeLightbox();
					window.open(data.url, '_blank');
				} else {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			});
			return false;
		},

		generateSublocationSelect: function () {
			var locationCode = $('#pickupBranch').val();
			var selectPlaceholder = document.getElementById("sublocationSelectPlaceHolder");
			var url = Globals.path + '/MyAccount/AJAX';
			var params = {
				method: 'getSublocationsSelect',
				locationCode: locationCode,
				context: 'placeHold'
			};
			$.getJSON(url, params, function (data) {
				if (data.success) {
					selectPlaceholder.innerHTML = data.selectHtml;
				} else {
					selectPlaceholder.innerHTML = '';
				}
			});
			return false;
		},

		showLocalIllEmailForm: function (module, source, id, volume) {
			if (Globals.loggedIn) {
				document.body.style.cursor = "wait";
				if (volume === undefined) {
					volume = '';
				}
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getLocalIllEmailForm&recordSource=" + source + "&volume=" + volume;
				$.getJSON(url, function (data) {
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Record.showLocalIllEmailForm(module, source, id, volume);
				}, false);
			}
			return false;
		},

		submitLocalIllEmailForm: function () {
			document.body.style.cursor = "wait";
			var id = $('#recordId').val();
			var url = Globals.path + "/Record/" + id + "/AJAX";
			var params = {
				method: 'submitLocalIllEmailForm',
				title: $("#title").val(),
				author: $("#author").val(),
				volume: $("#volume").val(),
				recordId: $("#recordId").val(),
				note: $("#note").val(),
				context: 'placeHold'
			};
			$.getJSON(url, params, function (data) {
				document.body.style.cursor = "default";
				AspenDiscovery.showMessage(data.title, data.message);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		getLargeCover: function (module, recordId){
			var url = Globals.path + '/' + module + '/' + recordId + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		}
	};
}(AspenDiscovery.Record || {}));