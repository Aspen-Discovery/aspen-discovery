AspenDiscovery.CurbsidePickup = {
	getCurbsidePickupScheduler(locationId) {
		if (Globals.loggedIn) {
			AspenDiscovery.loadingMessage();
			$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=getCurbsidePickupScheduler&pickupLocation=" + locationId, function (data) {
				if (data.success) {
					AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons)
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
		} else {
			this.ajaxLogin(null, this.getCurbsidePickupScheduler, false);
		}
		return false;
	},
	createCurbsidePickup() {
		if (Globals.loggedIn) {

			const patronId = $("#newCurbsidePickupForm input[name=patronId]").val();
			const locationId = $("#newCurbsidePickupForm  input[name=pickupLibrary]").val();
			const date = $("#newCurbsidePickupForm  input[name=pickupDate]").val();
			const time = $("#newCurbsidePickupForm  input[name=pickupTime]:checked").val();
			const note = ($("#newCurbsidePickupForm  textarea[name=pickupNote]").val() || '').substring(0, 255);

			AspenDiscovery.loadingMessage();
			$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=createCurbsidePickup&patronId=" + patronId + "&location=" + locationId + "&date=" + date + "&time=" + time + "&note=" + encodeURIComponent(note), function (data) {
				// Clean up any existing date-picker UI.
				$("div.flatpickr-calendar").remove();
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.body, true, 2000)
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(function () {
				$("div.flatpickr-calendar").remove();
				AspenDiscovery.ajaxFail();
			});
		} else {
			this.ajaxLogin(null, this.createCurbsidePickup, false);
		}
		return false;
	},
	getCancelCurbsidePickup(patronId, pickupId) {
		AspenDiscovery.loadingMessage();
		$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=getCancelCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
			AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
		}).fail(AspenDiscovery.ajaxFail);
		return false;
	},
	cancelCurbsidePickup(patronId, pickupId) {
		AspenDiscovery.loadingMessage();
		$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=cancelCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
			if (data.success) {
				AspenDiscovery.showMessage(data.title, data.body, true, 2000);
			}
			else {
				AspenDiscovery.showMessage(data.title, data.body, false);
			}
		}).fail(AspenDiscovery.ajaxFail);
		return false;
	},
	checkInCurbsidePickup(patronId, pickupId) {
		AspenDiscovery.loadingMessage();
		$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=checkInCurbsidePickup&patronId=" + patronId + "&pickupId=" + pickupId, function (data) {
			if (data.success) {
				AspenDiscovery.showMessage(data.title, data.body, true, 2000);
			}
			else {
				AspenDiscovery.showMessage(data.title, data.body, false);
			}
		}).fail(AspenDiscovery.ajaxFail);
		return false;
	},
	curbsidePickupScheduler(locationCode) {
		$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=getCurbsidePickupUnavailableDays&locationCode=" + locationCode)
			.done(function (unavailableDaysData) {
				if (!unavailableDaysData.success) {
					AspenDiscovery.showMessage(__('Error'), __('Failed to load calendar. Please try again later.'), false);
					return;
				}

				const todayISO = moment().format("YYYY-MM-DD");
				$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=getCurbsidePickupAvailableTimes&date=" + todayISO + "&locationCode=" + locationCode)
					.done(function (availableTimesData) {
						// If today has no time slots because the current time is past them all, then disable today.
						// This will return a false success flag if no time slots have been set in the respective ILS.
						if (!availableTimesData.success || (availableTimesData.times && availableTimesData.times.length === 0)) {
							unavailableDaysData.days = unavailableDaysData.days || [];
							unavailableDaysData.days.push(todayISO);
						}

						$("#pickupDate").flatpickr({
							minDate: "today",
							maxDate: new Date().fp_incr(14),
							altInput: true,
							altFormat: "M j, Y",
							"disable": [
								function(date) {
									const weekday = date.getDay();
									const dateISO = moment(date).format("YYYY-MM-DD");
									return (
										(unavailableDaysData.days && unavailableDaysData.days.includes(weekday)) ||
										(unavailableDaysData.days && unavailableDaysData.days.includes(dateISO))
									);
								}
							],
							"locale": {
								"firstDayOfWeek": 0
							},

							onChange: function (selectedDates, dateStr) {
								// Reset time slot sections before loading new ones.
								$("#morningTimeSlotsAccordion, #afternoonTimeSlotsAccordion, #eveningTimeSlotsAccordion").hide();
								$("#morningTimeSlots, #afternoonTimeSlots, #eveningTimeSlots").empty();
								$("#availableTimeSlots").hide();

								// Get available times for selected date
								$.getJSON(Globals.path + "/CurbsidePickups/AJAX?method=getCurbsidePickupAvailableTimes&date=" + dateStr + "&locationCode=" + locationCode)
									.done(function (data) {
										if (!data.success) {
											AspenDiscovery.showMessage(__('Error'), __('Could not load time slots. Please try again.'), false);
											return;
										}

										if (!data.times || data.times.length === 0) {
											AspenDiscovery.showMessage(__('No Times Available'), __('Sorry, there are no available pickup times for the selected date. Please select a different date.'), false);
											return;
										}

										let morningSlots = 0;
										let afternoonSlots = 0;
										let eveningSlots = 0;
										const morningContainer = document.getElementById("morningTimeSlots");
										const afternoonContainer = document.getElementById("afternoonTimeSlots");
										const eveningContainer = document.getElementById("eveningTimeSlots");

										data.times.forEach(time => {
											const slot = moment(time, "HH:mm").format("h:mm a");
											const slotHTML = `
										<label class='btn btn-primary' style='margin-right: 1em; margin-bottom: 1em'>
											<input type='radio' name='pickupTime' id='slot_${time}' value='${slot}'> ${slot}
										</label>
									`;
											if (time < "12:00") {
												morningSlots++;
												morningContainer.insertAdjacentHTML("beforeend", slotHTML);
											} else if (time < "17:00") {
												afternoonSlots++;
												afternoonContainer.insertAdjacentHTML("beforeend", slotHTML);
											} else {
												eveningSlots++;
												eveningContainer.insertAdjacentHTML("beforeend", slotHTML);
											}
										});

										if (morningSlots > 0) $("#morningTimeSlotsAccordion").show();
										if (afternoonSlots > 0) $("#afternoonTimeSlotsAccordion").show();
										if (eveningSlots > 0) $("#eveningTimeSlotsAccordion").show();
										const panels = $('#availableTimeSlots');

										panels.find('.panel-collapse').removeClass('in');
										panels.find('.panel:visible:first .panel-collapse').addClass('in');
										$("#availableTimeSlots").show();
									})
									.fail(function(jqXHR, textStatus, errorThrown) {
										AspenDiscovery.closeLightbox();
										AspenDiscovery.showMessage(__('Error'), __('Failed to load available times. Please try again later.'), false);
										console.error("Error loading time slots:", textStatus, errorThrown);
									});
							}
						});

						// With the calendar initialized, show the form and button.
						$("#curbsidePickupLoading").hide();
						$("#curbsidePickupContent").show();
						$("#createCurbsidePickupSubmit").show();
					})
					.fail(function(jqXHR, textStatus, errorThrown) {
						AspenDiscovery.showMessage(__('Error'), __('Failed to load available times. Please try again later.'), false);
						console.error("Error loading available times:", textStatus, errorThrown);
					});
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				AspenDiscovery.showMessage(__('Error'), __('Failed to load calendar. Please try again later.'), false);
				console.error("Error loading calendar:", textStatus, errorThrown);
			});
		return false;
	},
	updateCurbsidePickupSettingsFields() {
		const allowCheckIn = $('#allowCheckIn').is(':checked');
		const instructionsField = $('#curbsidePickupInstructions');
		const instructionsHelp = $('#curbsidePickupInstructionsHelpBlock');
		const leadTimeField = $('#timeAllowedBeforeCheckIn');
		const leadTimeHelp = $('#timeAllowedBeforeCheckInHelpBlock');

		if (allowCheckIn) {
			instructionsField.prop('readonly', true);
			instructionsHelp.html('<small><i class="fas fa-info-circle"></i> This field is disabled because "Mark Arrived" is enabled. When patrons can check in, these instructions are not used.</small>');
			leadTimeField.prop('readonly', true);
			leadTimeHelp.html('<small><i class="fas fa-info-circle"></i> This field is disabled because "Mark Arrived" is enabled. It is not used when patrons can mark arrival.</small>');
		} else {
			instructionsField.prop('readonly', false);
			instructionsHelp.html('<small><i class="fas fa-info-circle"></i> For custom instructions per location/branch, edit this mirrored field under the ILS/Account Integration section of the <a href="/Admin/Locations">Location settings</a>.</small>');
			leadTimeField.prop('readonly', false);
			leadTimeHelp.html('<small><i class="fas fa-info-circle"></i> Set to -1 to display at all times. If the pickup is marked as "Staged & Ready,", the instructions will display regardless of this set time.</small>');
		}
		return false;
	},
};

document.addEventListener("DOMContentLoaded", function () {
	const allowCheckIn = document.getElementById('allowCheckIn');
	const curbsidePickupInstructions = document.getElementById('curbsidePickupInstructions');

	if (allowCheckIn && curbsidePickupInstructions) {
		AspenDiscovery.CurbsidePickup.updateCurbsidePickupSettingsFields();
	}
});