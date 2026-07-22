AspenDiscovery.Events = (function(){
	return {
		saveEventsObjCallback: function() {},

		trackUsage: function (id) {
			var ajaxUrl = Globals.path + "/Events/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		},

		toggleEventFieldAllowableValues: function() {
			var toggleAllowableValues = function() {
				var typeVal = $('#typeSelect').val();
				$('#propertyRowallowableValues').toggle(typeVal === '3');
			};
			toggleAllowableValues();
			$('#typeSelect').on('change', toggleAllowableValues);
		},

		//For Aspen Events
		displayFieldOptionsForSelectedUse: function(selectedFieldUse) {
			const fieldUse = selectedFieldUse ?? document.getElementById('fieldSetUseSelect')?.value;
			if (!fieldUse || fieldUse === "0") {
				return;
			}
	
			const fieldSetId = document.getElementById('id').value;

			// handle new fieldset creation
			if (!fieldSetId) {
				AspenDiscovery.Events.getFieldOptionsByUse(fieldUse)
				return;
			}

			// loads existing fieldset
			const url = Globals.path + '/Events/AJAX';
			const params = {
				method: 'getFieldSetFields',
				fieldSetId
			};

			$.getJSON(url, params, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('An error occurred ', data.message);
					return;
				}
				AspenDiscovery.Events.getFieldOptionsByUse(fieldUse, data.selectedFields)
				return;
			});	
		},

		getFieldOptionsByUse: function(fieldUse, selectedFields = null) {
			const url = Globals.path + '/Events/AJAX';
			const params = {
				method: 'getFieldsByUse',
				fieldUse
			};

			$.getJSON(url, params, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('An error occurred ', data.message);
					return;
				}

				const propertyRoweventFields = $("#propertyRoweventFields");
				const checkboxWrapper = $("#propertyRoweventFields > .controls > .checkbox:not(.form-group)");
				checkboxWrapper.addClass('eventFieldWrapper');
				checkboxWrapper.empty();

				if (data.useFields && Object.keys(data.useFields).length > 0) {
					propertyRoweventFields.show();
				} else {
					propertyRoweventFields.hide();
				}
				
				$.each(data.useFields, (index, fieldName) => {
						const checkboxLabel = $('<label />', {
							for: 'eventFields_' + index,
						}).appendTo(checkboxWrapper);
						$('<input />', {
							type: 'checkbox',
							id: 'eventFields_' + index, 
							value: index,
							class: 'eventFields',
							name: 'eventFields[]',
							checked: !!selectedFields?.includes(parseInt(index))
						}).appendTo(checkboxLabel);
						$('<strong></strong>', {
							text: ' ' + fieldName 
						}).appendTo(checkboxLabel);
						$('<br>').appendTo(checkboxLabel);
					}
				)
			})
		},

		getEventTypesForLocation: function(locationId) {
			var url = Globals.path + '/Events/AJAX';
			var params = {
				method: 'getEventTypesAndSublocationsForLocation',
				locationId: locationId
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					if(data.sublocations && data.sublocations.length > 0) {
						var sublocations = JSON.parse(data.sublocations);
						$("#sublocationIdSelect").html("");
						Object.keys(sublocations).forEach(function(key) {
							$("<option/>", {
								value: key,
								text: sublocations[key]
							}).appendTo("#sublocationIdSelect");
						});
						if (sublocations.length === 0) {
							$("#propertyRowsublocationId").hide();
						} else {
							$("#propertyRowsublocationId").show();
						}

					} else {
						$("#sublocationIdSelect").html("");
						$("#propertyRowsublocationId").hide();
					}
					if (data.eventTypes && data.eventTypes.length > 0) {
						var eventTypes = JSON.parse(data.eventTypes);
						$("#eventTypeIdSelect").html("");
						$("<option/>", {
							value: '',
							text: "Choose an event type"
						}).appendTo("#eventTypeIdSelect");
						Object.keys(eventTypes).forEach(function (key) {
							$("<option/>", {
								value: key,
								text: eventTypes[key]
							}).appendTo("#eventTypeIdSelect");
						});
						$("#propertyRoweventTypeId").show();
						$("#propertyRowtitle").hide();
						$("#propertyRowinfoSection").hide();
						$("#propertyRowscheduleSection").hide();
					} else {
						$("#eventTypeIdSelect").html("");
						$("<option/>", {
							value: '',
							text: "No event types available at this location"
						}).appendTo("#eventTypeIdSelect");
						$("#propertyRowtitle").hide();
						$("#propertyRowinfoSection").hide();
						$("#propertyRowscheduleSection").hide();
					}
				} else {
					AspenDiscovery.showMessage('An error occurred ', data.message);
				}
			});
		},

		getEventTypeFields: function (eventTypeId) {
			var url = Globals.path + '/Events/AJAX';
			var params = {
				method: 'getEventTypeFields',
				eventTypeId: eventTypeId
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					if (data.status == "resetForm") {
						$("#eventTypeIdSelect").val("");
						$("#propertyRowtitle").hide();
						$("#propertyRowinfoSection").hide();
						$("#propertyRowscheduleSection").hide();
						$("#editFormInstructions").html("");
						$("#description").text("");
						return false;
					} else {
						if (data.editFormInstructions.length > 0) {
							$("#editFormInstructions").html(data.editFormInstructions).show();
						}else{
							$("#editFormInstructions").hide();
						}
						eventType = data.eventType;
						$("#title").val(eventType.title);
						if (!eventType.titleCustomizable) {
							$("#title").attr('readonly', 'readonly');
						} else {
							$("#title").prop('readonly', false);
						}
						var descriptionEditor = tinymce.get("description");
						$("#description").text(eventType.description);
						descriptionEditor.setContent(eventType.description);
						if (!eventType.descriptionCustomizable) {
							$("#description").attr('readonly', 'readonly');
							descriptionEditor.setMode("readonly");
						} else {
							descriptionEditor.setMode("design");
							$("#description").prop('readonly', false);
						}
						$("#importFile-label-cover").val(eventType.cover);
						if (!eventType.coverCustomizable) {
							$("#importFile-label-cover").attr('readonly', 'readonly');
						} else {
							$("#importFile-label-cover").prop('readonly', false);
						}
						if (eventType.eventLength != null) {
							var minutes = eventType.eventLength % 60;
							var hours = Math.floor(eventType.eventLength / 60);
							$("#eventLength_hours").val(hours);
							$("#eventLength_minutes").val(minutes);
						}
						$("#eventLength").val(eventType.eventLength);
						if (!eventType.lengthCustomizable) {
							$("#eventLength_minutes").attr('readonly', 'readonly');
							$("#eventLength_hours").attr('readonly', 'readonly');
							$("#eventLength").attr('readonly', 'readonly');
						} else {
							$("#eventLength").prop('readonly', false);
							$("#eventLength_minutes").prop('readonly', false);
							$("#eventLength_hours").prop('readonly', false);
						}
						$("#accordion_body_Fields_for_this_Event_Type .panel-body").html(data.typeFields);
						$('#accordion_body_Fields_for_this_Event_Type [data-toggle="tooltip"]').tooltip();
				
						// Wait a tick for the DOM to render before running the display logic
						setTimeout(function() {
							AspenDiscovery.Events.displayRegistrationNumberOfSeats();
							AspenDiscovery.Events.displayWaitingListEnable();
							AspenDiscovery.Events.displayWaitingListNumberOfSeats();

							$('#registrationRequired').off('change').on('change', function() {
								AspenDiscovery.Events.displayRegistrationNumberOfSeats();
								AspenDiscovery.Events.displayWaitingListEnable();
								AspenDiscovery.Events.displayWaitingListNumberOfSeats();
							});
							$('#waitingList').off('change').on('change', function() {
								AspenDiscovery.Events.displayWaitingListNumberOfSeats();
							});
						}, 0);

						$("#propertyRowtitle").show();
						$("#propertyRowinfoSection").show();
						$("#propertyRowscheduleSection").show();
						$("#propertyRowinfoSection .propertyRow").show();
						descriptionEditor.hide();
						descriptionEditor.show(); // Prevents editor from being collapsed if it's been hidden
					}
				} else {
					AspenDiscovery.showMessage('An error occurred ', data.message);
				}
			});
			return false;
		},

		updateRecurrenceOptions: function (startDate) {
			startDate = moment(startDate);
			if (startDate.isValid()) {
				startDay = startDate.format("dddd");
				var date = startDate.format("MMMM D");
				var weekOfMonth = AspenDiscovery.Events.getWeekofMonth(startDate);
				weekOfMonth = moment.localeData().ordinal(weekOfMonth); // Format as ordinal
				$("#recurrenceOptionSelect option[value=3]").text("Weekly on " + startDay + "s");
				$("#recurrenceOptionSelect option[value=4]").text("Monthly on the " + weekOfMonth + " " + startDay);
				$("#recurrenceOptionSelect option[value=5]").text("Annually on " + date);
				AspenDiscovery.Events.calculateEndTime();
				AspenDiscovery.Events.calculateRecurrenceDates();
			}
			return false;
		},

		toggleStartEndTimestamp: function () {
			const hideTimestamps = $('#hideTimestamps');
			if (hideTimestamps.is(":checked")) {
				$("#propertyRowstartTime").hide();
				$("#propertyRowendTime").hide();
			} else {
				$("#propertyRowstartTime").show();
				$("#propertyRowendTime").hide();
			}
		},

		getWeekofMonth: function (date) {
			return date.week() - date.startOf('month').week() + 1;
		},

		calculateEndTime: function () {
			var startDate = moment($("#startDate").val());
			var startTime = $("#startTime").val();
			var length = $("#eventLength").val();
			if (startDate && startDate.isValid() && startTime && startTime.length && length && length.length) {
				var timeParts = startTime.split(":");
				startDate.hour(timeParts[0]).minute(timeParts[1]);
				startDate.add(length, 'm');
				$("#endDate").val(startDate.format("YYYY-MM-DD"));
				$("#endTime").val(startDate.format("HH:mm"));
			}
			AspenDiscovery.Events.calculateRecurrenceDates();
			return false;
		},

		calculateRecurrenceDates: function () {

			var endDate;
			var recurrenceTotal;
			var count = 0;
			var useEndDate = false;
			if ($("#endOptionSelect").val() == "1" && $("#recurrenceEnd").val()) {
				endDate = moment($("#recurrenceEnd").val());
				if (!endDate.isValid()) {
					return false;
				}
				useEndDate = true;
			} else if ($("#endOptionSelect").val() == "2" && $("#recurrenceCount").val() > 0) {
				recurrenceTotal = $("#recurrenceCount").val();
			} else {
				return false; // We need either the end date or the number of recurrences to be set or else we can't calculate dates yet
			}

			var date = moment($("#startDate").val());
			if (!date.isValid()) {
				date = moment(); // Use today's date if there's no start date
			}
			var originalStart = date.format();

			var datesPreview = [];
			var dates = [];
			var frequency = $("#recurrenceFrequencySelect").val();
			var interval = $("#recurrenceInterval").val() || 1; // Assume interval is 1 if not set

			function processMonthlyRepeat() {
				tempDate = date.format(); // Keep original date
				if (repeatBasedOnDate) {
					if (dayNumber <= date.daysInMonth()) {
						date.date(dayNumber);
					} else {
						date.add(1, 'M');
						return false; // Don't generate if the day doesn't exist in the month
					}
				} else {
					endOfMonth = date.endOf("month").format();
					startOfMonth = date.startOf("Month").format();
					if (date.day(weekDay).isBefore(startOfMonth)) {
						date.add(1, 'w');
					}
					if (weekNumber > 0) {
						date.add(weekNumber - 1, 'w');
					} else { // Handle last week of the month
						date.add(4, 'w');
						if (date.isAfter(endOfMonth)) {
							date.subtract(7, 'd');
						}
					}
					if (date.isBefore(originalStart)) {
						date.add(1, 'M'); // If it's before the start date, add a month and try again
						return false;
					}
					if (date.isAfter(endOfMonth)) {
						date = moment(tempDate).add(interval, 'M');
						return false; // Don't generate if the day doesn't exist in the month
					}
				}
				if (!repeatBasedOnDate && offset != 0) {
					date.add(offset, 'd');
					if (useEndDate && date.isAfter(endDate)) {
						date = moment(tempDate).add(interval, 'M');
						return false;
					}
					datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
					dates.push(date.format('YYYY-MM-DD'));
					date = moment(tempDate);
				} else {
					datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
					dates.push(date.format('YYYY-MM-DD'));
				}
				date.add(interval, 'M');
				return true;
			}

			switch (frequency) {
				// daily
				case '1':
					if (useEndDate) {
						while (date.isSameOrBefore(endDate)) {
							datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
							dates.push(date.format('YYYY-MM-DD'));
							date.add(interval, 'd');
						}
					} else {
						while (count < recurrenceTotal) {
							datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
							dates.push(date.format('YYYY-MM-DD'));
							date.add(interval, 'd');
							count++;
						}
					}
					break;
				case '2':
				// weekly
					var days = [];
					$("#propertyRowweekDays input:checked").each(function () {
						days.push($(this).val());
					});
					if (days.length) {
						if (useEndDate) {
							while (date.isSameOrBefore(endDate)) {
								for (i = 0; i < days.length; i++) {
									date.day(days[i]); // Set the date to the matching day in the same week
									if (date.isBefore(originalStart)) {
										date.add(1, 'w'); // If it's before the start date, add a week
									}
									datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
									dates.push(date.format('YYYY-MM-DD'));
								}
								date.add(interval, 'w');
							}
						} else {
							while (count < recurrenceTotal) {
								for (i = 0; i < days.length && count < recurrenceTotal; i++) {
									date.day(days[i]); // Set the date to the matching day in the same week
									if (date.isBefore(originalStart)) {
										date.add(1, 'w'); // If it's before the start date, add a week
									}
									datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
									dates.push(date.format('YYYY-MM-DD'));
									count++;
								}
								date.add(interval, 'w');
							}
						}
					} else {
						return false; //No days selected
					}
					break;
				case '3':
				// monthly
					var repeatBasedOnDate = $("#monthlyOptionSelect").val() == "2";
					var dayNumber = $("#monthDate").val() || date.format('D'); // If not set, use startDate
					var weekNumber = $("#weekNumberSelect").val() || AspenDiscovery.Events.getWeekofMonth(date);
					var weekDay = $("#monthDaySelect").val() || date.format('d');
					var endOfMonth;
					var startOfMonth;
					var tempDate;
					var offset = $("#monthOffset").val();
					if (useEndDate) {
						while (date.isSameOrBefore(endDate)) {
							processMonthlyRepeat();
						}
					} else {
						while (count < recurrenceTotal) {
							if (processMonthlyRepeat()) {
								count++; // Only count if the date wasn't skipped
							}
						}
					}
					break;

				case '4':
				// yearly
					if (useEndDate) {
						while (date.isSameOrBefore(endDate)) {
							datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
							dates.push(date.format('YYYY-MM-DD'));
							date.add(interval, 'y');
						}
					} else {
						while (count < recurrenceTotal) {
							datesPreview.push(date.format('dddd, MMMM Do, YYYY'));
							dates.push(date.format('YYYY-MM-DD'));
							date.add(interval, 'y');
							count++;
						}
					}
			}
			$("#datesPreview").html(datesPreview.join("<br/>"));
			$("#dates").val(dates);
			return false;
		},

		collapsePanel: function (panelSelector) {
			$(panelSelector + " .panel-title a").removeClass('expanded').addClass('collapsed').attr("aria-expanded", "false");
			$(panelSelector + " .panel").removeClass('active').attr("aria-expanded", "false");
			$(panelSelector + " .accordion_body").removeClass('in').hide();
			$(panelSelector + " .accordion_body").removeClass('in').hide();
		},

		expandPanel: function (panelSelector) {
			$(panelSelector + " .panel-title a").removeClass('collapsed').addClass('expanded').attr("aria-expanded", "true");
			$(panelSelector + " .panel").addClass('active').attr("aria-expanded", "true");
			$(panelSelector + " .accordion_body").addClass('in').show();
		},

		toggleRecurrenceSections: function (recurrence) {

			function resetRecurrenceSections() {
				$("#propertyRowfrequencySection").hide();
				$("#propertyRowweeklySection").hide();
				$("#propertyRowmonthlySection").hide();
				$("#propertyRowrepeatEndsSection").hide();
				$("#propertyRowdatesPreview").hide();
				$("#propertyRowweekDays input").prop("checked", false);
				$("#propertyRowweekNumber option").prop("selected", false);
				$("#propertyRowmonthDay option").prop("selected", false);
				AspenDiscovery.Events.collapsePanel("#accordion_Repeat_Frequency");
			}
			var startDate = moment($("#startDate").val());  // Check what happens if invalid date
			var dayNumber = startDate.format('d');
			var dayOfWeek = startDate.day();
			var weekOfMonth = AspenDiscovery.Events.getWeekofMonth(startDate);
			switch (recurrence) {
				case '1':
					// Does not repeat
					resetRecurrenceSections();
					break;
				case '2':
					// Daily
					resetRecurrenceSections();
					$("#recurrenceFrequencySelect option[value=1]").prop("selected","true");
					$("#recurrenceInterval").val("1");
					$("#propertyRowfrequencySection").show();
					$("#propertyRowdatesPreview").show();
					$("#propertyRowrepeatEndsSection").show();
					break;
				case '3':
					// Weekly on same day of week
					resetRecurrenceSections();
					$("#recurrenceFrequencySelect option[value=2]").prop("selected","true");
					$("#recurrenceInterval").val("1");
					$("#propertyRowfrequencySection").show();
					// Show weekly with specific day selected based on startDate
					$("#propertyRowweekDays input[value=" + dayOfWeek + "]").prop("checked", true);
					$("#propertyRowweeklySection").show();
					$("#propertyRowrepeatEndsSection").show();
					$("#propertyRowdatesPreview").show();
					break;
				case '4':
					// Monthly on same day of week
					resetRecurrenceSections();
					$("#recurrenceFrequencySelect option[value=3]").prop("selected","true");
					$("#recurrenceInterval").val("1");
					$("#propertyRowfrequencySection").show();
					// Show monthly with specific day based on startdate
					$("#propertyRowweekNumber option[value=" + weekOfMonth + "]").prop("selected", true);
					$("#propertyRowmonthDay option[value=" + dayOfWeek + "]").prop("selected", true);
					$("#propertyRowweekNumber").show();
					$("#propertyRowmonthDay").show();
					$("#propertyRowmonthDate").hide();
					$("#propertyRowmonthlySection").show();
					$("#propertyRowrepeatEndsSection").show();
					$("#propertyRowdatesPreview").show();
					break;
				case '5':
					// Annually
					resetRecurrenceSections();
					$("#recurrenceFrequencySelect option[value=4]").prop("selected","true");
					$("#recurrenceInterval").val("1");
					$("#propertyRowfrequencySection").show();
					$("#propertyRowrepeatEndsSection").show();
					$("#propertyRowdatesPreview").show();
					break;
				case '6':
					// Every week day
					resetRecurrenceSections();
					$("#recurrenceFrequencySelect option[value=2]").prop("selected","true");
					$("#recurrenceInterval").val("1");
					$("#propertyRowfrequencySection").show();
					$("#propertyRowweekDays input[value!=6][value!=0]").prop("checked", true);
					$("#propertyRowweeklySection").show();
					$("#propertyRowrepeatEndsSection").show();
					$("#propertyRowdatesPreview").show();
					break;
				case '7':
					// Custom - nothing preset
					resetRecurrenceSections();
					AspenDiscovery.Events.expandPanel("#accordion_Repeat_Frequency");
					$("#propertyRowfrequencySection").show();
					// Get current value of repeat frequency and open appropriate panels
					$repeatFrequency = $("#recurrenceFrequencySelect").val();
					if ($repeatFrequency == "2") { // weekly
						$("#propertyRowweeklySection").show();
						AspenDiscovery.Events.expandPanel("#propertyRowweeklySection");
					} else if ($repeatFrequency == "3") { //monthly
						$("#propertyRowmonthlySection").show();
						AspenDiscovery.Events.expandPanel("#propertyRowmonthlySection");
					}
					$("#propertyRowrepeatEndsSection").show();
					$("#propertyRowdatesPreview").show();
					break;
			}
			AspenDiscovery.Events.calculateRecurrenceDates();
			return false;
		},

		toggleMonthlyOptions: function (option) {
			switch (option) {
				case '1':
					// By day of week
					$("#propertyRowweekNumber").show();
					$("#propertyRowmonthDay").show();
					$("#propertyRowmonthDate").hide();
					$("#propertyRowmonthOffset").show();
					break;
				case '2':
					// By date
					$("#propertyRowweekNumber").hide();
					$("#propertyRowmonthDay").hide();
					$("#propertyRowmonthDate").show();
					$("#propertyRowmonthOffset").hide();
					break;
			}
			AspenDiscovery.Events.calculateRecurrenceDates();
			return false;
		},

		toggleEndOptions: function (option) {
			switch (option) {
				case '1':
					// By date
					$("#propertyRowrecurrenceEnd").show();
					$("#propertyRowrecurrenceCount").hide();
					break;
				case '2':
					// By count
					$("#propertyRowrecurrenceEnd").hide();
					$("#propertyRowrecurrenceCount").show();
					break;
			}
			AspenDiscovery.Events.calculateRecurrenceDates();
			return false;
		},

		toggleSectionsByFrequency: function (option) {

			function resetSections() {
				$("#propertyRowweeklySection").hide();
				$("#propertyRowmonthlySection").hide();
				AspenDiscovery.Events.collapsePanel("#propertyRowmonthlySection");
				AspenDiscovery.Events.collapsePanel("#propertyRowweeklySection");
			}

			switch (option) {
				case '1':
					// Daily
					// No extra options
					resetSections();
					break;
				case '2':
					// Weekly
					resetSections();
					$("#propertyRowweeklySection").show();
					AspenDiscovery.Events.expandPanel("#propertyRowweeklySection");
					break;
				case '3':
					// Monthly
					resetSections();
					$("#propertyRowmonthlySection").show();
					AspenDiscovery.Events.expandPanel("#propertyRowmonthlySection");
					break;
				case '4':
					// Annually
					// No extra options
					resetSections();
					break;
			}
			AspenDiscovery.Events.calculateRecurrenceDates();
			return false;
		},
		iCalendarExport: function (eventId, source, wholeSeries) {
			var url = Globals.path + '/Events/AJAX';
			var params = {
				method: 'iCalendarExport',
				source: source,
				eventId: eventId,
				wholeSeries : wholeSeries
			};

			$.getJSON(url, params, function (data) {
				if (data.success && data.icsFile.length > 0) {
					var filename = eventId + ".ics";
					var element = document.createElement('a');
					element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data.icsFile));
					element.setAttribute('download', filename);
					element.style.display = 'none';
					document.body.appendChild(element);
					element.click();
					document.body.removeChild(element);
				}
			});
		},
		showCopyEventsForm: function (eventId) {
			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'getCopyEventsForm',
				eventId: eventId
			};
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		processCopyEventsForm: function () {
			var url = Globals.path + "/Events/AJAX";
			var eventName = $('#eventName').val();
			var eventId = $('#eventId').val();
			var eventLocation = $('#eventLocation').val();
			var sublocationId = $('#sublocationIdSelect').val();
			var eventDate = $('#eventDate').val();
			var params = {
				method: 'doCopyEvent',
				id: eventId,
				name: eventName,
				locationId: eventLocation,
				sublocationId: sublocationId,
				date: eventDate
			};
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, true, true);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		getPrintListOptions: function (week, month, year) {
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Events/AJAX?method=getListPrintOptions&week=' + week + '&month=' + month + "&year=" + year);
			return false;
		},
		buildAndOpenPrintUrl: function () {
			const print = document.getElementById('print').value;
			const week = document.getElementById('week').value;
			const month = document.getElementById('month').value;
			const year = document.getElementById('year').value;

			const baseUrl = Globals.path + '/Events/Calendar';


			// Checkbox names (in order as in the form)
			const checkboxIds = [
				'endTime'
			];

			// Build URL params object
			const params = {
				print,
				week,
				month,
				year
			};

			checkboxIds.forEach(id => {
				const el = document.getElementById(id);
				if (el) {
					// Only include if checked, send value "true" (or customize as needed)
					params[id] = el.checked ? 'true' : 'false';
				}
			});

			const checkboxes = document.querySelectorAll('input[type="checkbox"].agenda-print-option, input[type="checkbox"].calendar-print-option');
			checkboxes.forEach(el => {
				if (el.id) {
					params[el.id] = el.checked ? 'true' : 'false';
				}
			});

			// Build search string
			const urlSearchParams = new URLSearchParams(params).toString();

			// Final URL
			const printUrl = `${baseUrl}?${urlSearchParams}`;

			// Open print window and prompt print dialog once loaded
			const win = window.open(printUrl, '_blank', 'width=900,height=900');
			if (win) {
				// Wait for the new window to load content, then trigger print
				win.onload = function () {
					win.print();
				};
			}
		},
		checkEventsForType: function (submitForm) {
			var titleCustomizable = $("#titleCustomizable").is(':checked');
			var descriptionCustomizable = $("#descriptionCustomizable").is(':checked');
			var coverCustomizable = $("#coverCustomizable").is(':checked');
			var eventLengthCustomizable = $("#lengthCustomizable").is(':checked');
			var displayBranchOnThumbnailCustomizable = $("#displayEventBranchOnThumbnailCustomizable").is(':checked');

			var url = Globals.path + "/Events/AJAX";
			var params = {
				'method': 'checkEventsForType',
				titleCustomizable: titleCustomizable,
				descriptionCustomizable: descriptionCustomizable,
				coverCustomizable: coverCustomizable,
				eventLengthCustomizable: eventLengthCustomizable,
				displayBranchOnThumbnailCustomizable: displayBranchOnThumbnailCustomizable,
				objectId: $("#id").val()
			};

			$.getJSON(url, params,function(data){
				if (data.success){
					if (data.noEventsOfType === true){
						submitForm();
					} else{
						AspenDiscovery.Events.saveEventsObjCallback = submitForm;
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons, '', '', false, '', true);
					}
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			})
		},
		handleRegistrationEnabledToggle: function () {
			AspenDiscovery.Events.displayRegistrationNumberOfSeats();
			AspenDiscovery.Events.displayWaitingListEnable();
			AspenDiscovery.Events.displayWaitingListNumberOfSeats();
		},
		displayRegistrationNumberOfSeats: function () {
			const requireEventRegistration = document.getElementById('registrationRequired');
			if (!requireEventRegistration) {
				return;
			}

			let registrationNumberOfSeats = document.getElementById('propertyRownumberOfSeats');
			if (!registrationNumberOfSeats) {
				return;
			}
			
			if (requireEventRegistration.checked) {
				registrationNumberOfSeats.style.display = '';
				return;
			}
			AspenDiscovery.Events.unsetNumberOfSeats();
			registrationNumberOfSeats.style.display = 'none';
		},
		displayWaitingListEnable: function () {
			let requireEventRegistration = document.getElementById('registrationRequired');
			let waitingListEnabled = document.getElementById('propertyRowwaitingList');

			if (requireEventRegistration && requireEventRegistration.checked) {
				waitingListEnabled.style.display = '';
			} else {
				waitingListEnabled.style.display = 'none';
			}
		},
		displayWaitingListNumberOfSeats: function () {
			let waitingList = document.getElementById('waitingList');
			let waitingListNumberOfSeats = document.getElementById('propertyRowwaitingListNumberOfSeats');

			if (waitingList && waitingList.checked) {
				waitingListNumberOfSeats.style.display = '';
			} else {
				waitingListNumberOfSeats.style.display = 'none';
			}
		},
		unsetNumberOfSeats: function () {
			let numberofSeats = document.getElementById('numberOfSeats');
			if (!numberofSeats) {
				return;
			}
			numberofSeats.value = null;
		},
		saveEventsForType: function(doFullSave){
			if (doFullSave) {
				var titleCustomizable = $("#titleCustomizable").is(':checked');
				var descriptionCustomizable = $("#descriptionCustomizable").is(':checked');
				var coverCustomizable = $("#coverCustomizable").is(':checked');
				var eventLengthCustomizable = $("#lengthCustomizable").is(':checked');
				var displayBranchOnThumbnailCustomizable = $("#displayEventBranchOnThumbnailCustomizable").is(':checked');

				var eventLengthHoursToMinutes = $("#eventLength_hours").val() * 60;
				var eventLengthMinutes = $("#eventLength_minutes").val();
				var eventLength = parseInt(eventLengthHoursToMinutes) + parseInt(eventLengthMinutes);

				var params = {
					objectId: $("#id").val(),
					title: $("#title").val(),
					description: $("#description").val(),
					cover: $("#importFile-label-cover").val(),
					eventLength: eventLength,
					titleCustomizable: titleCustomizable,
					descriptionCustomizable: descriptionCustomizable,
					coverCustomizable: coverCustomizable,
					eventLengthCustomizable: eventLengthCustomizable,
					displayBranchOnThumbnailCustomizable: displayBranchOnThumbnailCustomizable,
					doFullSave: doFullSave
				};
				var url = Globals.path + '/Events/AJAX?method=saveEventsForType';
				$.getJSON(url, params, function (data) {
					if (data.success === true) {
						AspenDiscovery.Events.saveEventsObjCallback();
					} else {
						AspenDiscovery.showMessage('Sorry', data.message);
					}
				});
			}else{
				AspenDiscovery.Events.saveEventsObjCallback();
			}
		},

		showStaffRegistrationModal: function(eventInstanceId) {
			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'getStaffRegistrationModal',
				eventInstanceId: eventInstanceId
			};
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		lookupPatronForRegistration: function() {
			var barcode = $("#patronBarcodeInput").val().trim();
			if (!barcode) {
				$("#patronLookupError").text("Please enter a barcode.").show();
				$("#patronLookupResult").hide();
				return;
			}

			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'lookupPatronByBarcode',
				barcode: barcode
			};

			$.getJSON(url, params, function(data) {
				if (data.success) {
					$("#foundPatronId").val(data.user.id);
					$("#foundPatronName").text(data.user.displayName);
					$("#foundPatronBarcode").text(data.user.barcode);
					$("#foundPatronEmail").text(data.user.email || '-');
					$("#foundPatronLocation").text(data.user.homeLocation || '-');
					$("#patronLookupResult").show();
					$("#patronLookupError").hide();
				} else {
					$("#patronLookupError").text(data.message).show();
					$("#patronLookupResult").hide();
				}
			}).fail(function() {
				$("#patronLookupError").text("Error looking up patron.").show();
				$("#patronLookupResult").hide();
			});
		},

		confirmStaffRegistration: function() {
			const eventInstanceId = $("#staffRegEventInstanceId").val();
			const userId = $("#foundPatronId").val();

			if (!eventInstanceId || !userId) {
				AspenDiscovery.showMessage("Error", "Missing event or patron information.");
				return;
			}

			const url = Globals.path + "/Events/AJAX";
			const params = {
				method: 'staffRegisterUserForEvent',
				eventInstanceId: eventInstanceId,
				userId: userId
			};

			const attendeeCategoryInputs = document.querySelectorAll('#staffRegistrationModal input[name^="attendeeCategory["]');
			for (const input of attendeeCategoryInputs) {
				const count = parseInt(input.value, 10) || 0;
				if (count > 0) {
					params[input.name] = count;
				}
			}

			$.getJSON(url, params, function(data) {
				AspenDiscovery.showMessage(data.title, data.message, !data.success);
				if (data.success) {
					setTimeout(function() {
						location.reload();
					}, 1500);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		staffUnregisterUser: function(eventInstanceId, userId) {
			if (!confirm("Are you sure you want to cancel this registration?")) {
				return;
			}

			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'staffUnregisterUserFromEvent',
				eventInstanceId: eventInstanceId,
				userId: userId
			};

			$.getJSON(url, params, function(data) {
				AspenDiscovery.showMessage(data.title, data.message, !data.success);
				if (data.success) {
					setTimeout(function() {
						location.reload();
					}, 1500);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		staffReregisterUser: function(eventInstanceId, userId) {
			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'staffRegisterUserForEvent',
				eventInstanceId: eventInstanceId,
				userId: userId
			};

			$.getJSON(url, params, function(data) {
				AspenDiscovery.showMessage(data.title, data.message, !data.success);
				if (data.success) {
					setTimeout(function() {
						location.reload();
					}, 1500);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		toggleAttendance: function(registrationId, attended) {
			var url = Globals.path + "/Events/AJAX";
			var params = {
				method: 'markAttendance',
				registrationId: registrationId,
				attended: attended ? 1 : 0
			};

			var checkbox = $("#attended-" + registrationId);
			var cell = checkbox.closest('td');
			checkbox.prop('disabled', true);

			$.getJSON(url, params, function(data) {
				checkbox.prop('disabled', false);
				if (data.success) {
					cell.css('background-color', '#dff0d8');
					setTimeout(function() { cell.css('background-color', ''); }, 800);
				} else {
					cell.css('background-color', '#f2dede');
					setTimeout(function() { cell.css('background-color', ''); }, 1200);
					checkbox.prop('checked', !attended);
				}
			}).fail(function() {
				checkbox.prop('disabled', false);
				cell.css('background-color', '#f2dede');
				setTimeout(function() { cell.css('background-color', ''); }, 1200);
				checkbox.prop('checked', !attended);
			});
		},

		lessEventDates: function() {
			document.getElementById("moreEventDates").style.display = "block";
			document.getElementById("narrowGroupHidden_eventDates").style.display = "none";
		},

		moreEventDates: function() {
			document.getElementById("moreEventDates").style.display = "none";
			document.getElementById("narrowGroupHidden_eventDates").style.display = "block";
		}
	};
}(AspenDiscovery.Events || {}));
