AspenDiscovery.Admin = (function () {
	const DEFAULT_COLORS = {
        pageBackgroundColor: "#ffffff",
        bodyBackgroundColor: "#ffffff",
        bodyTextColor: "#6B6B6B",
        linkColor: "#3174AF",
        linkHoverColor: "#265a87",
        resultLabelColor: "#44484a",
        resultValueColor: "#6B6B6B",
        headerBackgroundColor: "#f1f1f1",
        headerForegroundColor: "#303030",
        breadcrumbsBackgroundColor: "#f5f5f5",
        breadcrumbsForegroundColor: "#6B6B6B",
        searchToolsBackgroundColor: "#f5f5f5",
        searchToolsBorderColor: "#e3e3e3",
        searchToolsForegroundColor: "#6B6B6B",
        footerBackgroundColor: "#f1f1f1",
        footerForegroundColor: "#303030",

        primaryBackgroundColor: "#0a7589",
        primaryForegroundColor: "#ffffff",
        secondaryBackgroundColor: "#de9d03",
        secondaryForegroundColor: "#303030",
        tertiaryBackgroundColor: "#de1f0b",
        tertiaryForegroundColor: "#000000",

        menubarBackgroundColor: "#f1f1f1",
        menubarForegroundColor: "#303030",
        menubarHighlightBackgroundColor: "#f1f1f1",
        menubarHighlightForegroundColor: "#265a87",
        menuDropdownBackgroundColor: "#ededed",
        menuDropdownForegroundColor: "#404040",

        modalDialogBackgroundColor: "#ffffff",
        modalDialogForegroundColor: "#333333",
        modalDialogHeaderFooterBackgroundColor: "#ffffff",
        modalDialogHeaderFooterForegroundColor: "#333333",
        modalDialogHeaderFooterBorderColor: "#e5e5e5",

        browseCategoryPanelColor: "#d7dce3",
        selectedBrowseCategoryBackgroundColor: "#0087AB",
        selectedBrowseCategoryForegroundColor: "#ffffff",
        selectedBrowseCategoryBorderColor: "#0087AB",
        deselectedBrowseCategoryBackgroundColor: "#0087AB",
        deselectedBrowseCategoryForegroundColor: "#ffffff",
        deselectedBrowseCategoryBorderColor: "#0087AB",

        badgeBackgroundColor: "#666666",
        badgeForegroundColor: "#ffffff",

        closedPanelBackgroundColor: "#e7e7e7",
        closedPanelForegroundColor: "#333333",
        openPanelBackgroundColor: "#333333",
        openPanelForegroundColor: "#ffffff",
        panelBodyBackgroundColor: "#ffffff",
        panelBodyForegroundColor: "#404040",

        defaultButtonBackgroundColor: "#ffffff",
        defaultButtonForegroundColor: "#333333",
        defaultButtonBorderColor: "#cccccc",
        defaultButtonHoverBackgroundColor: "#eeeeee",
        defaultButtonHoverForegroundColor: "#333333",
        defaultButtonHoverBorderColor: "#cccccc",

        primaryButtonBackgroundColor: "#1b6ec2",
        primaryButtonForegroundColor: "#ffffff",
        primaryButtonBorderColor: "#1b6ec2",
        primaryButtonHoverBackgroundColor: "#ffffff",
        primaryButtonHoverForegroundColor: "#1b6ec2",
        primaryButtonHoverBorderColor: "#1b6ec2",

        actionButtonBackgroundColor: "#1b6ec2",
        actionButtonForegroundColor: "#ffffff",
        actionButtonBorderColor: "#1b6ec2",
        actionButtonHoverBackgroundColor: "#ffffff",
        actionButtonHoverForegroundColor: "#1b6ec2",
        actionButtonHoverBorderColor: "#1b6ec2",

        editionsButtonBackgroundColor: "#f8f9fa",
        editionsButtonForegroundColor: "#212529",
        editionsButtonBorderColor: "#999999",
        editionsButtonHoverBackgroundColor: "#ffffff",
        editionsButtonHoverForegroundColor: "#1b6ec2",
        editionsButtonHoverBorderColor: "#1b6ec2",

        toolsButtonBackgroundColor: "#747474",
        toolsButtonForegroundColor: "#ffffff",
        toolsButtonBorderColor: "#636363",
        toolsButtonHoverBackgroundColor: "#636363",
        toolsButtonHoverForegroundColor: "#ffffff",
        toolsButtonHoverBorderColor: "#636363",

        infoButtonBackgroundColor: "#8cd2e7",
        infoButtonForegroundColor: "#000000",
        infoButtonBorderColor: "#999999",
        infoButtonHoverBackgroundColor: "#ffffff",
        infoButtonHoverForegroundColor: "#217e9b",
        infoButtonHoverBorderColor: "#217e9b",

        warningButtonBackgroundColor: "#f4d03f",
        warningButtonForegroundColor: "#000000",
        warningButtonBorderColor: "#999999",
        warningButtonHoverBackgroundColor: "#ffffff",
        warningButtonHoverForegroundColor: "#8d6708",
        warningButtonHoverBorderColor: "#8d6708",

        dangerButtonBackgroundColor: "#D50000",
        dangerButtonForegroundColor: "#ffffff",
        dangerButtonBorderColor: "#999999",
        dangerButtonHoverBackgroundColor: "#ffffff",
        dangerButtonHoverForegroundColor: "#D50000",
        dangerButtonHoverBorderColor: "#D50000"
	};

	function applyColor(property, color) {
        document.getElementById(property + "Hex").value = color;
        document.getElementById(property).value = color;
    }

	return {
		showReindexNotes: function (id) {
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
			return false;
		},
		showCronNotes: function (id) {
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
			return false;
		},
		showCronProcessNotes: function (id) {
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
			return false;
		},
		toggleCronProcessInfo: function (id) {
			$("#cronEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},

		showExtractNotes: function (id, source) {
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getExtractNotes&id=" + id + "&source=" + source, true);
			return false;
		},
		loadGoogleFontPreview: function (fontSelector) {
			var fontElement = $("#" + fontSelector);
			var fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		},
		getDefaultColor: function (property, extendedThemeDefault) {
			applyColor(property, extendedThemeDefault ?? DEFAULT_COLORS[property]);
		},
		checkContrast: function (property1, property2, oneWay, minRatio) {
			if (oneWay === undefined) {
				oneWay = false;
			}
			var color1 = $('#' + property1).val();
			var color2 = $('#' + property2).val();
			if (color1.length === 7 && color2.length === 7) {
				var luminance1 = AspenDiscovery.Admin.getLuminanceForColor(color1);
				var luminance2 = AspenDiscovery.Admin.getLuminanceForColor(color2);
				const contrastCalc = (a, b) => ((a + .05) / (b + .05))
				var contrastRatio = Math.max(contrastCalc(luminance1, luminance2), contrastCalc(luminance2, luminance1));

				var contrastSpan1 = $("#contrast_" + property1);
				var contrastSpan2 = $("#contrast_" + property2);
				const displayContrast = (ratio) => {
					contrastSpan1.text(ratio.toFixed(2));
					contrastSpan2.text(ratio.toFixed(2));
				};

				const updateClasses = (ratio) => {
					const isDanger = (minRatio === 7.0 && ratio < 4.5) || (minRatio !== 7.0 && ratio < 3.5);
					const isWarning = (minRatio === 7.0 && ratio < minRatio) || (minRatio !== 7.0 && ratio < minRatio);

					contrastSpan1.toggleClass("alert-danger", isDanger);
					contrastSpan2.toggleClass("alert-danger", isDanger);
					contrastSpan1.toggleClass("alert-warning", isWarning);
					contrastSpan2.toggleClass("alert-warning", isWarning);
					contrastSpan1.toggleClass("alert-success", !isDanger && !isWarning);
					contrastSpan2.toggleClass("alert-success", !isDanger && !isWarning);
				};

				displayContrast(contrastRatio);
				updateClasses(contrastRatio);
			} else {
				$("#contrastCheck_" + property1).hide();
				if (!oneWay) {
					$("#contrastCheck_" + property2).hide();
				}
				$("#contrast_" + property1).innerHTML = 'Unknown';
				if (!oneWay) {
					$("#contrast_" + property2).innerHTML = 'Unknown';
				}
			}
		},
		getLuminanceForColor: function (color) {
			var r = AspenDiscovery.Admin.getLuminanceComponent(color, 1, 2);
			var g = AspenDiscovery.Admin.getLuminanceComponent(color, 3, 2);
			var b = AspenDiscovery.Admin.getLuminanceComponent(color, 5, 2);
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		},
		getLuminanceComponent: function (color, start, length) {
			var component = parseInt(color.substring(start, start + length), 16) / 255;
			if (component <= 0.03928) {
				return component / 12.92;
			} else {
				return Math.pow((component + 0.055) / 1.055, 2.4);
			}
		},
		updateLocationFields: function () {
			var useLibraryThemes = $("#useLibraryThemes").prop("checked");
			if (useLibraryThemes) {
				$("#propertyRowthemes").hide();
			} else {
				$("#propertyRowthemes").show();
			}
		},
		updateMaterialsRequestFields() {
			const materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
			const rowsToHide = [
				"#propertyRowdisplayMaterialsRequestToPublic",
				"#propertyRowallowDeletingILSRequests",
				"#propertyRowallowMaterialRequestsBranchChoice",
				"#propertyRowexternalMaterialsRequestUrl",
				"#propertyRowmaxRequestsPerYear",
				"#propertyRowmaxActiveRequests",
				"#propertyRowrequestCalendarStartDate",
				"#propertyRowmaterialsRequestDaysToPreserve",
				"#propertyRowmaterialsRequestFieldsToDisplay",
				"#propertyRowmaterialsRequestFormats",
				"#propertyRowmaterialsRequestFormFields",
				"#propertyRowmaterialsRequestSendStaffEmailOnNew",
				"#propertyRowmaterialsRequestNewEmail",
				"#propertyRowmaterialsRequestSendStaffEmailOnAssign",
				"#propertyRownewMaterialsRequestSummary",
				"#propertyRowyearlyRequestLimitType",
				"#propertyRowcheckRequestsForExistingTitles"
			];
			rowsToHide.forEach(selector => $(selector).hide());

			let rowsToShow = [];
			switch (materialRequestType) {
				case "1": // Aspen Request System
					rowsToShow = [
						"#propertyRowdisplayMaterialsRequestToPublic",
						"#propertyRowmaxRequestsPerYear",
						"#propertyRowyearlyRequestLimitType",
						"#propertyRowmaxActiveRequests",
						"#propertyRowrequestCalendarStartDate",
						"#propertyRownewMaterialsRequestSummary",
						"#propertyRowmaterialsRequestDaysToPreserve",
						"#propertyRowmaterialsRequestFieldsToDisplay",
						"#propertyRowmaterialsRequestFormats",
						"#propertyRowmaterialsRequestFormFields",
						"#propertyRowmaterialsRequestSendStaffEmailOnNew",
						"#propertyRowmaterialsRequestNewEmail",
						"#propertyRowmaterialsRequestSendStaffEmailOnAssign",
						"#propertyRowcheckRequestsForExistingTitles"
					];
					break;
				case "2": // ILS Request System
					rowsToShow = [
						"#propertyRowallowDeletingILSRequests", 
						"#propertyRowallowMaterialRequestsBranchChoice", 
						"#propertyRowdisplayMaterialsRequestToPublic", 
						"#propertyRownewMaterialsRequestSummary"
					];
					break;
				case "3": // External Request Link
					rowsToShow = [
						"#propertyRowexternalMaterialsRequestUrl", 
						"#propertyRowdisplayMaterialsRequestToPublic"
					];
					break;
				default: // None (0)
					break;
			}
			rowsToShow.forEach(selector => $(selector).show());
			return false;
		},

		updateHoldCancellationDateFields() {
			const showCancelDateEnabled = $("#showHoldCancelDate:checked").val();
			const fieldsToToggle = [
				"#propertyRowdefaultNotNeededAfterDays",
				"#propertyRowmaxHoldCancellationDate"
			];

			if (showCancelDateEnabled) {
				fieldsToToggle.forEach(selector => $(selector).show());
			} else {
				fieldsToToggle.forEach(selector => $(selector).hide());
			}

			return false;
		},

		updateDonationFields: function () {
			var donationsEnabledValue = $("#enableDonations:checked").val()
			const propList = [
				"#propertyRowallowDonationsToBranch",
				"#propertyRowallowDonationEarmark",
				"#propertyRowallowDonationDedication",
				"#propertyRowdonationValues",
				"#propertyRowdonationContent"
			];

			if(donationsEnabledValue == 1){
				propList.forEach(prop => prop.show());
			} else {
				propList.forEach(prop => prop.hide());
			}

			return false;
		},
		validateCompare: function () {
			const selectedObjects = $('.selectedObject:checked');
			if (selectedObjects.length === 2) {
				return true;
			}
			AspenDiscovery.showMessage("Failed to Compare Objects", "Please select only two objects to compare.");
			return false;
		},
		showBatchUpdateFieldForm: function (module, toolName, batchUpdateScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchUpdateScope === 'all' || selectedObjects.length >= 1) {
				var url = Globals.path + "/Admin/AJAX";
				var params = {
					method: 'getBatchUpdateFieldForm',
					moduleName: module,
					toolName: toolName,
					batchUpdateScope: batchUpdateScope
				};
				$.getJSON(url, params,
					function (data) {
						if (data.success) {
							AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
						} else {
							$("#releaseNotes").html("Error + " + data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
				return false;
			}
			AspenDiscovery.showMessage("Error", "Please select at least one object to update");
			return false;
		},
		processBatchUpdateFieldForm: function (module, toolName, batchUpdateScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchUpdateScope === 'all' || selectedObjects.length >= 1) {
				var url = Globals.path + "/Admin/AJAX";
				var selectedField = $('#fieldSelector').val();
				var selectedFieldControl = $(`#${selectedField}`)
				var newValue;
				if (selectedFieldControl.prop("type") === undefined) {
					selectedFieldControl = $('#' + selectedField + "Select");
				}
				if (selectedFieldControl.prop("type") === 'checkbox') {
					newValue = selectedFieldControl.prop("checked") ? 1 : 0;
				} else {
					newValue = selectedFieldControl.val();
				}
				var params = {
					method: 'doBatchUpdateField',
					moduleName: module,
					toolName: toolName,
					batchUpdateScope: batchUpdateScope,
					selectedField: selectedField,
					newValue: newValue
				};
				selectedObjects.each(function () {
					params[$(this).prop('name')] = 'on';
				});
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
			} else {
				AspenDiscovery.showMessage("Error", "Please select at least one object to update");
				return false;
			}
		},
		showCopyFacetGroupForm: function (id) {
			var url = Globals.path + "/Admin/AJAX";
			var params = {
				method: 'getCopyFacetGroupForm',
				facetGroupId: id
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
		processCopyFacetGroupForm: function () {
			var url = Globals.path + "/Admin/AJAX";
			var applyToSettings = $('#displaySettingsSelector').val();
			var newGroupName = $('#groupName').val();
			var facetGroupId = $('#facetGroupId').val();
			var params = {
				method: 'doCopyFacetGroup',
				id: facetGroupId,
				name: newGroupName,
				displaySettings: applyToSettings
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
		showCopyEventsFacetGroupForm: function (id) {
			var url = Globals.path + "/Admin/AJAX";
			var params = {
				method: 'getCopyEventsFacetGroupForm',
				facetGroupId: id
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
		processCopyEventsFacetGroupForm: function () {
			var url = Globals.path + "/Admin/AJAX";
			var newGroupName = $('#groupName').val();
			var facetGroupId = $('#facetGroupId').val();
			var params = {
				method: 'doCopyEventsFacetGroup',
				id: facetGroupId,
				name: newGroupName
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
		showBatchDeleteForm: function (module, toolName, batchDeleteScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchDeleteScope === 'all' || selectedObjects.length >= 1) {
				var url = Globals.path + "/Admin/AJAX";
				var params = {
					method: 'getBatchDeleteForm',
					moduleName: module,
					toolName: toolName,
					batchDeleteScope: batchDeleteScope
				};
				$.getJSON(url, params,
					function (data) {
						if (data.success) {
							AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
						} else {
							$("#releaseNotes").html("Error + " + data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
				return false;
			} else {
				AspenDiscovery.showMessage("Error", "Please select at least one object to delete");
				return false;
			}
		},
		processBatchDeleteForm: function (module, toolName, batchDeleteScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchDeleteScope === 'all' || selectedObjects.length >= 1) {
				var url = Globals.path + "/Admin/AJAX";
				var params = {
					method: 'doBatchDelete',
					moduleName: module,
					toolName: toolName,
					batchDeleteScope: batchDeleteScope
				};
				selectedObjects.each(function () {
					params[$(this).prop('name')] = 'on';
				});
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
			} else {
				AspenDiscovery.showMessage("Error", "Please select at least one object to delete");
				return false;
			}
		},
		showCopyDisplaySettingsForm: function (id) {
			var url = Globals.path + "/Admin/AJAX";
			var params = {
				method: 'getCopyDisplaySettingsForm',
				settingsId: id
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
		processCopyDisplaySettingsForm: function () {
			var url = Globals.path + "/Admin/AJAX";
			var newName = $('#settingsName').val();
			var settingsId = $('#settingsId').val();
			var params = {
				method: 'doCopyDisplaySettings',
				id: settingsId,
				name: newName
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
		addFilterRow: function (module, toolName) {
			var url = Globals.path + "/Admin/AJAX";
			var params = {
				method: 'getFilterOptions',
				moduleName: module,
				toolName: toolName
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
		getNewFilterRow: function (module, toolName) {
			var url = Globals.path + "/Admin/AJAX";
			var selectedFilter = $("#fieldSelector").val();
			var params = {
				method: 'getNewFilterRow',
				moduleName: module,
				toolName: toolName,
				selectedFilter: selectedFilter
			};
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						$('#activeFilters').append(data.filterRow);
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		displayReleaseNotes: function () {
			var url = Globals.path + "/Admin/ReleaseNotes";
			var selectedRelease = $('#releaseSelector').val();
			window.location.href = url + "?release=" + selectedRelease;
			return false;
		},
		
		updateBrowseSearchForSource() {
			const selectedSource = $('#sourceSelect').val();

			const rowsToHide = [
				"#propertyRowsearchTerm",
				"#propertyRowdefaultFilter",
				"#propertyRowdefaultSort",
				"#propertyRowsourceListId",
				"#propertyRowsourceCourseReserveId"
			];
			rowsToHide.forEach(selector => $(selector).hide());

			switch (selectedSource) {
				case 'List':
					$("#propertyRowsourceListId").show();
					break;
				case 'CourseReserve':
					$("#propertyRowsourceCourseReserveId").show();
					break;
				default:
					[
						"#propertyRowsearchTerm",
						"#propertyRowdefaultFilter",
						"#propertyRowdefaultSort"
					].forEach(selector => $(selector).show());
					break;
			}

			this.updateSortOptionsForSource(selectedSource);
			return false;
		},

		updateSortOptionsForSource(selectedSource) {
			if (selectedSource === 'List' || selectedSource === 'CourseReserve') return;

			const sortOptionsBySource = {
				GroupedWork: {
					relevance: 'Best Match',
					popularity: 'Popularity',
					newest_to_oldest: 'Date Added',
					author: 'Author',
					title: 'Title',
					user_rating: 'Rating',
					holds: 'Number of Holds',
					publication_year_desc: 'Publication Year Desc',
					publication_year_asc: 'Publication Year Asc'
				},
				Events: {
					relevance: 'Best Match',
					event_date: 'Event Date',
					title: 'Title'
				},
				OpenArchives: {
					relevance: 'Best Match',
					title: 'Title'
				},
				Genealogy: {
					relevance: 'Best Match',
					title: 'Title'
				},
				EbscoEds: {
					relevance: 'Best Match'
				},
				Websites: {
					relevance: 'Best Match',
					title: 'Title'
				},
				CourseReserves: {
					relevance: 'Best Match',
					title: 'Title'
				},
				Lists: {
					relevance: 'Best Match',
					title: 'Title',
					newest_to_oldest: 'Date Added',
					oldest_to_newest: 'Date Added (Oldest First)',
					newest_updated_to_oldest: 'Date Updated',
					oldest_updated_to_newest: 'Date Updated (Oldest First)'
				}
			};

			const $sortSelect = $('#defaultSortSelect');
			const currentValue = $sortSelect.val();
			$sortSelect.empty();

			const sortOptions = sortOptionsBySource[selectedSource] || sortOptionsBySource.GroupedWork;
			Object.entries(/** @type {{ [key: string]: string }} */ sortOptions).forEach(([value, label]) => {
				$sortSelect.append(new Option(label, value));
			});


			if (sortOptions.hasOwnProperty(currentValue)) {
				$sortSelect.val(currentValue);
			} else {
				$sortSelect.prop('selectedIndex', 0);
			}
		},

		updateCollectionSpotlightFields() {
			const collSpotStyle = $("#styleSelect option:selected").val();
			const rowsToHide = [
				"#propertyRowshowTitle",
				"#propertyRowshowAuthor",
				"#propertyRowshowRatings",
				"#propertyRowautoRotate"
			];
			rowsToHide.forEach(selector => $(selector).hide());

			switch (collSpotStyle) {
				case "text-list":
					// All rows already hidden.
					break;
				case "horizontal-carousel":
					[
						"#propertyRowshowTitle",
						"#propertyRowshowAuthor",
						"#propertyRowshowRatings"
					].forEach(selector => $(selector).show());
					break;
				default:
					// Show all for other styles.
					rowsToHide.forEach(selector => $(selector).show());
					break;
			}

			if ($("#showViewMoreLink").is(":checked")) {
				$("#propertyRowviewMoreLinkMode").show();
			} else {
				$("#propertyRowviewMoreLinkMode").hide();
			}

			return false;
		},
		updateGroupedWorkDisplayFields() {
			const showSearchTools = $('#showSearchTools');
			if (showSearchTools.is(":checked")) {
				$("#propertyRowshowSearchToolsAtTop").show();
			} else {
				$("#propertyRowshowSearchToolsAtTop").hide();
			}

			const showSeriesSelection = $('#showInSearchResultsMainDetails_showSeries');
			const showSeriesSelected = showSeriesSelection.is(":checked");
			if (showSeriesSelected) {
				$("#propertyRowshowIndexedSeriesWithNoveList").show();
				$("#propertyRownumSeriesToShowBeforeMore").show();
				$("#propertyRowhideIndexedEContentSeries").show();
			} else {
				$("#propertyRowshowIndexedSeriesWithNoveList").hide();
				$("#propertyRownumSeriesToShowBeforeMore").hide();
				$("#propertyRowhideIndexedEContentSeries").hide();
			}

			if (!showSeriesSelection.data('listener-attached')) {
				showSeriesSelection.on('change', function() {
					AspenDiscovery.Admin.updateGroupedWorkDisplayFields();
				});
				showSeriesSelection.data('listener-attached', true);
			}
		},
		initializeFormatSort: function () {
			this.updateGroupedWorkSortFields('book');
			this.updateGroupedWorkSortFields('comic');
			this.updateGroupedWorkSortFields('movie');
			this.updateGroupedWorkSortFields('music');
			this.updateGroupedWorkSortFields('other');
		},
		updateGroupedWorkSortFields: function (groupingCategory) {
			const formatSelectors = {
				book: {
					select: "#bookSortMethodSelect",
					propertyRow: "#propertyRowsortedBookFormats"
				},
				comic: {
					select: "#comicSortMethodSelect",
					propertyRow: "#propertyRowsortedComicFormats"
				},
				movie: {
					select: "#movieSortMethodSelect",
					propertyRow: "#propertyRowsortedMovieFormats"
				},
				music: {
					select: "#musicSortMethodSelect",
					propertyRow: "#propertyRowsortedMusicFormats"
				},
				other: {
					select: "#otherSortMethodSelect",
					propertyRow: "#propertyRowsortedOtherFormats"
				}
			};

			const selectedOption = formatSelectors[groupingCategory];
			if (selectedOption) {
				const selectedValue = $(selectedOption.select).find(":selected").val();
				const isHidden = selectedValue == 1;

				$(selectedOption.propertyRow).toggle(!isHidden);
			}
		},
		updateGroupedWorkEContentSortFields: function () {
			var selectedOption = $("#sortMethodSelect").find(":selected").val();
			if (selectedOption === "1") {
				$("#propertyRowsortedEContentSources").hide();
			} else {
				$("#propertyRowsortedEContentSources").show();
			}
		},
		updateIndexingProfileFields: function () {
			var audienceType = $('#determineAudienceBySelect').val();
			if (audienceType === '3') {
				$("#propertyRowaudienceSubfield").show();
			} else {
				$("#propertyRowaudienceSubfield").hide();
			}

			let propsToHide = [
				"#propertyRowspecifiedFormat",
				"#propertyRowspecifiedFormatCategory",
				"#propertyRowspecifiedFormatBoost",
				"#propertyRowcheckRecordForLargePrint",
				"#propertyRowformatMap"
			]
			
			propsToHide.forEach(prop => prop.hide());

			var formatSource = $('#formatSourceSelect').val();
			switch(formatSource) {
				case "specified":
					$("#propertyRowspecifiedFormat").show();
					$("#propertyRowspecifiedFormatCategory").show();
					$("#propertyRowspecifiedFormatBoost").show();
					break;
				case "item":
					$("#propertyRowcheckRecordForLargePrint").show();
					$("#propertyRowformatMap").show();
					break;
				default:
					$("#propertyRowformatMap").show();
					break;
			}
		},
		setIndexingProfileDefaultsByIndexingClass: function () {
			var selectedIndexingClass = $("#indexingClassSelect").val();
			var catalogDriverMap = {
				'': 'AbstractIlsDriver',
				'ArlingtonKoha': 'Koha',
				'CarlX': 'CarlX',
				'Evergreen': 'Evergreen',
				'Evolve': 'Evolve',
				'III': 'Sierra',
				'Koha': 'Koha',
				'NashvilleCarlX': 'Nashville',
				'Polaris': 'Polaris',
				'Symphony': 'SirsiDynixROA'
			};

			$("#catalogDriver").val(catalogDriverMap[selectedIndexingClass] || catalogDriverMap['']);
		},
		updateLayoutSettingsFields: function () {
			var useHomeLink = $('#useHomeLinkSelect').val();
			if ((useHomeLink === '0') || useHomeLink === '2') {
				$("#propertyRowshowBookIcon").show();
				$("#propertyRowhomeLinkText").hide();
			} else {
				$("#propertyRowshowBookIcon").hide();
				$("#propertyRowhomeLinkText").show();
			}
		},
		updateBrowseCategoryFields: function () {
			var sharingType = $('#sharingSelect').val();
			if (sharingType === 'library') {
				$("#propertyRowlibraryId").show();
			} else {
				$("#propertyRowlibraryId").hide();
			}
		},
		showCreateRoleForm: function () {
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getCreateRoleForm', true);
			return false;
		},
		createRole: function () {
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'createRole',
				roleName: $('#roleName').val(),
				description: $('#description').val(),
				copyFrom: $('#roleCopySelector').val()
			}
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						window.location.href = Globals.path + '/Admin/Permissions?roleId=' + data.roleId;
					} else {
						AspenDiscovery.showMessage('Error', data.message, false);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},

		deleteRole: function (roleId) {
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'deleteRole',
				roleId: $("#roleId").find("option:selected").val()
			}
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						window.location.href = Globals.path + '/Admin/Permissions';
					} else {
						AspenDiscovery.showMessage('Error', data.message, false);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},

		updateMakeRowAccordion: function () {
			var makeRowAccordion = $('#makeAccordion');
			$(makeRowAccordion).on('click', function () {
				if (makeRowAccordion.is(":checked")) {
					$("#rowTitle").attr('required', "true");
				} else {
					$("#rowTitle").removeAttr('required');
				}
			});
		},

		updateMakeCellAccordion: function () {
			var makeCellAccordion = $('#makeCellAccordion');
			$(makeCellAccordion).on('click', function () {
				if (makeCellAccordion.is(":checked")) {
					$("#title").attr('required', "true");
				} else {
					$("#title").removeAttr('required');
				}
			});
		},

		deleteNYTList: function (id) {
			var listId = id;
			if (confirm("Are you sure you want to delete this list?")) {
				$.getJSON(Globals.path + '/Admin/AJAX?method=deleteNYTList&id=' + listId, function (data) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				})
			}
			return false;
		},

		updateLibraryLinksFields: function () {
			var requireLogin = $('#showToLoggedInUsersOnly');
			if (requireLogin.is(":checked")) {
				$("#propertyRowallowAccess").show();
			} else {
				$("#propertyRowallowAccess").hide();
			}

			$(requireLogin).on('click', function () {
				if (requireLogin.is(":checked")) {
					$("#propertyRowallowAccess").show();
				} else {
					$("#propertyRowallowAccess").hide();
				}
			});
		},

		updateDonationsSettingFields: function () {
			var allowEarmarks = $('#allowDonationEarmark');
			if (allowEarmarks.is(":checked")) {
				$("#propertyRowdonationEarmarks").show();
			} else {
				$("#propertyRowdonationEarmarks").hide();
			}
			$(allowEarmarks).on('click', function () {
				if (allowEarmarks.is(":checked")) {
					$("#propertyRowdonationEarmarks").show();
				} else {
					$("#propertyRowdonationEarmarks").hide();
				}
			});

			var allowDedications = $('#allowDonationDedication');
			if (allowDedications.is(":checked")) {
				$("#propertyRowdonationDedicationTypes").show();
			} else {
				$("#propertyRowdonationDedicationTypes").hide();
			}

			$(allowDedications).on('click', function () {
				if (allowDedications.is(":checked")) {
					$("#propertyRowdonationDedicationTypes").show();
				} else {
					$("#propertyRowdonationDedicationTypes").hide();
				}
			});
		},
		createRecovery2FACode: function () {
			var username = $("#username").val();
			if (Globals.loggedIn) {
				$.getJSON(Globals.path + "/Admin/AJAX?method=createRecoveryCode&user=" + username, function (data) {
					// update #codeVerificationFailedPlaceholder with failed verification status, otherwise move onto next step
					if (data.success) {
						$("#error").html(data.message).hide();
						$("#generatedCode").html(data.message).show();
					} else {
						$("#generatedCode").html(data.message).hide();
						$("#error").html(data.message).show();
					}
					return data;
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					return AspenDiscovery.Account.verify2FA();
				}, false);
			}
			return false;
		},
		setDateFilterFieldVisibility: function (propertyName) {
			var selectedValue = $('#filterType_' + propertyName).val();
			if (selectedValue === 'afterTime') {
				$('#filterValue_' + propertyName).show();
				$('#filterValue2_' + propertyName).val('').hide();
			} else if (selectedValue === 'beforeTime') {
				$('#filterValue_' + propertyName).val('').hide();
				$('#filterValue2_' + propertyName).show();
			} else {
				$('#filterValue_' + propertyName).show();
				$('#filterValue2_' + propertyName).show();
			}
		},
		getUrlOptions: function () {
			function adjustProperties(type){
				$('#propertyRowctaUrl').hide();
				$('#propertyRowdeepLinkId').hide();
				$('#propertyRowdeepLinkPath').hide();
				$('#propertyRowdeepLinkFullPath').hide();

				switch(type){
					case 0:
					case "0":
						$('#propertyRowdeepLinkPath').show();
						break;
					default:
						$('#propertyRowctaUrl').show();
						break;
				}
			}

			var linkType = $("#linkTypeSelect").val();
			adjustProperties(linkType);
		},
		getDeepLinkFullPath: function () {
			var selectedPath = $("#deepLinkPathSelect").val();
			stringMap = {
				"search": "Search Term",
				"search/grouped_work": "Grouped Work Id",
				"search/browse_category": "Browse Category Text Id",
				"search/author": "Author",
				"search/list": "List Id" 
			}	
			
			if (!selectedPath){
				$('#propertyRowdeepLinkId').hide();
				return;
			}

			$('#propertyRowdeepLinkId').show();
			$('label[for="deepLinkId"]').text(stringMap[selectedPath]);
		},
		getSSOFields: function () {

			function setFieldVisibility(type) {
				AspenDiscovery.Admin.toggleoAuthFields('hide');
				AspenDiscovery.Admin.toggleSamlFields('hide');
				AspenDiscovery.Admin.toggleLDAPFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
				AspenDiscovery.Admin.toggleSamlMetadataFields();

				switch(type) {
					case "oauth":
						AspenDiscovery.Admin.toggleoAuthFields('show');
						break;
					case "saml":
						AspenDiscovery.Admin.toggleSamlFields('show');
						AspenDiscovery.Admin.toggleSamlUserIdFields();
						AspenDiscovery.Admin.toggleSamlUsernameFormatFields();
						break;
					case "ldap":
						AspenDiscovery.Admin.toggleLDAPFields('show');
						AspenDiscovery.Admin.toggleSamlUserIdFields();
						AspenDiscovery.Admin.toggleSamlUsernameFormatFields();
						break;
					default:
						break;
				}
			}
			
			$("#clientSecret").attr('autocomplete', "off");
			$("#ldapPassword").attr('autocomplete', "off");
			var ssoService = $("#serviceSelect").val();
			setFieldVisibility(ssoService);
		},
		toggleoAuthFields: function (displayMode) {
			if (displayMode === "show") {
				$('#propertyRowoAuthConfigSection').show();
				$('#propertyRowdataMappingSection').show();
			} else {
				$('#propertyRowoAuthConfigSection').hide();
				document.getElementById("clientSecret").value = "";
			}
		},
		toggleSamlFields: function (displayMode) {
			if (displayMode === "show") {
				$('#propertyRowsamlConfigSection').show();
				$('#propertyRowdataMappingSection').hide();
			} else {
				$('#propertyRowsamlConfigSection').hide();
			}
		},
		toggleLDAPFields: function (displayMode) {
			if (displayMode === "show") {
				$('#propertyRowldapConfigSection').show();
				$('#propertyRowdataMappingSection').show();
			} else {
				$('#propertyRowldapConfigSection').hide();
				document.getElementById("ldapPassword").value = "";
			}
		},
		toggleOAuthGatewayFields: function () {
			var oAuthGateway = $("#oAuthGatewaySelect").val();
			if (oAuthGateway === "custom") {
				$('#propertyRowoAuthCustomGatewayOptionsSection').show();
			} else {
				$('#propertyRowoAuthCustomGatewayOptionsSection').hide();
			}
		},
		toggleOAuthPrivateKeysField: function () {
			var oAuthGrantType = $("#oAuthGrantTypeSelect").val();
			if (oAuthGrantType === 2 || oAuthGrantType === '2') {
				$('#propertyRowoAuthPrivateKeys').show();
			} else {
				$('#propertyRowoAuthPrivateKeys').hide();
			}
		},
		toggleSamlMetadataFields: function () {
			var metadataType = $("#samlMetadataOptionSelect").val();
			if (metadataType === 'url') {
				$('#propertyRowssoXmlUrl').show();
				$('#propertyRowssoMetadataFilename').hide();
			} else {
				$('#propertyRowssoXmlUrl').hide();
				$('#propertyRowssoMetadataFilename').show();
			}
		},
		toggleSamlUserIdFields: function () {
			var userIdOption = $('#ssoUseGivenUserId');
			if (userIdOption.is(":checked")) {
				$('#propertyRowssoIdAttr').show();
			} else {
				$('#propertyRowssoIdAttr').hide();
			}
			$(userIdOption).on('click', function () {
				if (userIdOption.is(":checked")) {
					$('#propertyRowssoIdAttr').show();
				} else {
					$('#propertyRowssoIdAttr').hide();
				}
			});
		},
		toggleSamlUsernameFormatFields: function () {
			var usernameFormat = $('#ssoUseGivenUsername');
			if (usernameFormat.is(":checked")) {
				$('#propertyRowssoUsernameAttr').show();
				$('#propertyRowssoUsernameFormat').hide();
			} else {
				$('#propertyRowssoUsernameFormat').show();
				$('#propertyRowssoUsernameAttr').hide();
			}
			$(usernameFormat).on('click', function () {
				if (usernameFormat.is(":checked")) {
					$('#propertyRowssoUsernameAttr').show();
					$('#propertyRowssoUsernameFormat').hide();
				} else {
					$('#propertyRowssoUsernameFormat').show();
					$('#propertyRowssoUsernameAttr').hide();
				}
			});
		},
		toggleLibrarySharingOptions: function () {
			if ($('#owningLibrarySelect').val() !== '-1'){
				$('#propertyRowsharing').show();
				if ($('#sharingSelect').val() === '1'){
					$('#propertyRowsharedWithLibrary').show();
				} else {
					$('#propertyRowsharedWithLibrary').hide();
				}
			} else {
				$('#propertyRowsharing').hide();
				$('#propertyRowsharedWithLibrary').hide();
			}
		},
		toggleHeroSliderFields() {
			const imageType = $('#typeSelect').val();
			if (imageType === 'hero_slider') {
				$('#propertyRowaltText').show();
				$('#propertyRowpageLink').show();
				$('#propertyRowstartDate').show();
				$('#propertyRowendDate').show();
				$('#propertyRowaspectRatioWidth').show();
				$('#propertyRowaspectRatioHeight').show();
				$('#propertyRowcalculatedAspectRatio').show();
			} else {
				$('#propertyRowaltText').hide();
				$('#propertyRowpageLink').hide();
				$('#propertyRowstartDate').hide();
				$('#propertyRowendDate').hide();
				$('#propertyRowaspectRatioWidth').hide();
				$('#propertyRowaspectRatioHeight').hide();
				$('#propertyRowcalculatedAspectRatio').hide();
			}
		},
		updateHeroSliderFields() {
			const displayStyle = $('#displayStyleSelect').val();
			const $autoRotate = $('#autoRotate');

			if (displayStyle === 'digital_signage') {
				// noinspection JSUnresolvedReference
				$autoRotate.prop('checked', true).prop('disabled', true);
			} else {
				// noinspection JSUnresolvedReference
				$autoRotate.prop('disabled', false);
			}

			const preset = $('#aspectRatioPresetSelect').val();
			if (preset === 'custom') {
				$('#propertyRowaspectRatioWidth').show();
				$('#propertyRowaspectRatioHeight').show();
			} else {
				$('#propertyRowaspectRatioWidth').hide();
				$('#propertyRowaspectRatioHeight').hide();
				if (preset && preset.includes(':')) {
					const parts = preset.split(':');
					$('#aspectRatioWidth').val(parts[0]);
					$('#aspectRatioHeight').val(parts[1]);
				}
			}
		},
		linkingSettingOptionChange: function () {
			var url = Globals.path + "/Admin/AJAX";
			var pType = $("#pType").val();
			var selected = $('#accountLinkingSettingSelect option:selected').val();
			var params = {
				method: "getFormPTypeSetting",
				data: {pType: pType, selected: selected}
			};
			$.getJSON(url, params, function (data) {
				if (data.success === true) {
					AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
				} else {
					return false;
				}
			});
		},

		toggleSSOSettingsInAccountProfile: function () {
			var authMethod = $("#authenticationMethodSelect").val();
			if (authMethod === "sso") {
				$('#propertyRowssoSettingId').show();
			} else {
				$('#propertyRowssoSettingId').hide();
			}
		},

		toggleAccountProfileIlsFields: function () {
			var selectedIls = $("#ilsSelect").val();
			var propertyRows = $(".propertyRow");
			propertyRows.each(function () {
				if ($(this).attr("data-related-ils") !== undefined){
					var relatedIls = $(this).data("related-ils");
					if (relatedIls.includes("~" + selectedIls + "~")) {
						$(this).show();
					}else{
						$(this).hide();
					}
				}
			});
		},

		toggleIlsSpecificFields: function () {
			var activeIls = $("#activeIls").val();
			var propertyRows = $(".propertyRow");
			propertyRows.each(function () {
				if ($(this).attr("data-related-ils") !== undefined){
					var relatedIls = $(this).data("related-ils");
					if (relatedIls.includes("~" + activeIls + "~")) {
						$(this).show();
					}else{
						$(this).hide();
					}
				}
			});
			var oneToManyCells = $(".oneToManyCell");
			oneToManyCells.each(function () {
				if ($(this).attr("data-related-ils") !== undefined){
					var relatedIls = $(this).data("related-ils");
					if (relatedIls.includes("~" + activeIls + "~")) {
						$(this).show();
					}else{
						$(this).hide();
					}
				}
			});
		},

		setAccountProfileDefaultsByIls: function () {
			var selectedIls = $("#ilsSelect").val();
			var driverMap = {
				'na': { driver: '', authMethod: 'db' },
				'carlx': { driver: 'CarlX', authMethod: 'ils' },
				'evergreen': { driver: 'Evergreen', authMethod: 'ils' },
				'evolve': { driver: 'Evolve', authMethod: 'ils' },
				'koha': { driver: 'Koha', authMethod: 'ils' },
				'polaris': { driver: 'Polaris', authMethod: 'ils' },
				'sierra': { driver: 'Sierra', authMethod: 'ils' },
				'symphony': { driver: 'SirsiDynixROA', authMethod: 'ils' }
			};

			var driverInfo = driverMap[selectedIls] || driverMap['na'];
			$("#driver").val(driverInfo.driver);
			$("#authenticationMethodSelect").val(driverInfo.authMethod);
		},

		searchSettings: function () {
			var searchValue = $("#settingsSearch").val();
			var searchRegex = new RegExp(searchValue, 'i');
			if (searchValue.length === 0) {
				$(".adminAction").show();
				$(".adminSection").show();
			} else {
				var allAdminSections = $(".adminSection");
				allAdminSections.each(function () {
					var curSection = $(this);
					var sectionLabel = curSection.find(".adminSectionLabel");
					var adminSectionLabel = sectionLabel.text();
					var actionsInSection = curSection.find(".adminAction");
					if (searchRegex.test(adminSectionLabel)) {
						curSection.show();
						actionsInSection.show();
					} else {
						var numVisibleActions = 0;
						actionsInSection.each(function () {
							var curAction = $(this);
							var title = curAction.find(".adminActionLabel").text();
							var description = curAction.find(".adminActionDescription").text();
							var titleMatches = searchRegex.test(title);
							var descriptionMatches = searchRegex.test(description);
							if (!titleMatches && !descriptionMatches) {
								curAction.hide();
							} else {
								curAction.show();
								numVisibleActions++;
							}
						});
						if (numVisibleActions > 0) {
							curSection.show();
						} else {
							curSection.hide();
						}
					}
				});
			}
		},
		searchPermissions: function () {
			var searchValue = $("#searchPermissions").val();
			var searchRegex = new RegExp(searchValue, 'i');
			if (searchValue.length === 0) {
				$(".permissionRow").show();
				$(".permissionSection").show().removeClass('active');
				$('.searchCollapse').addClass('collapse').css('height', '0px');
			} else {
				$('.searchCollapse').removeClass('collapse').css('height', 'auto');
				$('.permissionSection').addClass('active');
				var allPermissionSections = $(".permissionSection");
				allPermissionSections.each(function () {
					var curSection = $(this);
					var sectionLabel = curSection.find(".permissionHeading");
					var permissionSectionLabel = sectionLabel.text();
					var permissionsInSection = curSection.find(".permissionRow");
					if (searchRegex.test(permissionSectionLabel)) {
						curSection.show();
						permissionsInSection.show();
						console.log(permissionsInSection)
					} else {
						var numVisibleActions = 0;
						permissionsInSection.each(function () {
							var curPermission = $(this);
							var title = curPermission.find("#permissionLabel").text();
							var description = curPermission.find("#permissionDescription").text();
							var titleMatches = searchRegex.test(title);
							var descriptionMatches = searchRegex.test(description);
							if (!titleMatches && !descriptionMatches) {
								curPermission.hide();
							} else {
								curPermission.show();
								numVisibleActions++;
							}
						});
						if (numVisibleActions > 0) {
							curSection.show();
						} else {
							curSection.hide();
						}
					}
				});
			}
		},

		searchProperties: function () {
			var searchValue = $("#propertySearch").val();
			var searchRegex = new RegExp(searchValue, 'i');
			if (searchValue.length === 0) {
				$(".propertyRow").show();
				$(".propertySectionHeading").show();
				$(".propertySection").show();
				//Collapse all panels
				$(".editor .panel-title a").removeClass('expanded').addClass('collapsed').attr("aria-expanded", "false");
				$(".editor .panel").removeClass('active').attr("aria-expanded", "false");
				$(".editor .accordion_body").removeClass('in').hide();
			} else {
				var allAPropertyRows = $(".propertyRow");
				allAPropertyRows.each(function () {
					var curRow = $(this);
					var rowText = curRow.text();
					if (searchRegex.test(rowText)) {
						curRow.show();
					} else {
						curRow.hide();
					}
				});
				//Expand all panels
				$(".editor .panel-title a").removeClass('collapsed').addClass('expanded').attr("aria-expanded", "true");
				$(".editor .panel").addClass('active').attr("aria-expanded", "true");
				$(".editor .accordion_body").addClass('in').show();
			}
		},

		searchAdminBar: function () {
			var searchValue = $("#searchAdminBar").val();
			var searchRegex = new RegExp(searchValue, 'i');
			if (searchValue.length === 0) {
				$(".adminMenuLink").show();
				$('.admin-search-collapse').addClass('collapse').css('height', '0px');
				$('.admin-menu-section').show().removeClass('active')
			} else {
				$('.admin-search-collapse').removeClass('collapse').css('height', 'auto');
				$('.admin-menu-section').addClass('active')
				var allMenuSections = $(".admin-menu-section");
				allMenuSections.each(function () {
					var curSection = $(this);
					var sectionLabel = curSection.find(".adminTitleItem");
					var menuSectionLabel = sectionLabel.text();
					var adminLinksInSection = curSection.find(".adminMenuLink");
					if (searchRegex.test(menuSectionLabel)) {
						curSection.show();
						adminLinksInSection.show();
					} else {
						var numVisibleActions = 0;
						adminLinksInSection.each(function () {
							var curMenuLink = $(this);
							var title = curMenuLink.find("a").text();
							var titleMatches = searchRegex.test(title);
							if (!titleMatches) {
								curMenuLink.hide();
							} else {
								curMenuLink.show();
								numVisibleActions++;
							}
						});
						if (numVisibleActions > 0) {
							curSection.show();
						} else {
							curSection.hide();
						}
					}
				});

			}
		},


		showSearch: function () {
			$('#adminSearchBox').css('display', 'block');
			$('#showSearchButton').css('display', 'none');
			document.getElementById('searchAdminBar').focus();
		},

		showFindCommunityContentForm: function (toolModule, toolName, objectType) {
			var params = {
				method: 'getSearchCommunityContentForm',
				toolModule: toolModule,
				toolName: toolName,
				objectType: objectType
			}
			var url = Globals.path + "/Admin/AJAX";
			$.getJSON(url, params, function (data) {
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		searchCommunityContentKeyDown: function (e, toolModule, toolName) {
			if (e.keyCode === 9) {
				AspenDiscovery.Admin.searchCommunityContent(toolModule, toolName);
			} else if (e.keyCode === 10 || e.keyCode === 13) {
				e.preventDefault();
				AspenDiscovery.Admin.searchCommunityContent(toolModule, toolName);
			}
			return false;
		},

		searchCommunityContent: function (toolModule, toolName) {
			$("#communitySearchResultsLoading").show();
			$("#communitySearchResults").html("");
			var searchForm = $("#searchCommunityContentForm");
			var objectType = searchForm.find("#objectType").val();
			var communitySearchTerm = searchForm.find("#communitySearchTerm").val();
			var url = Globals.path + '/API/CommunityAPI';
			var params = {
				'method': 'searchSharedContent',
				'objectType': objectType,
				'toolModule': toolModule,
				'toolName': toolName,
				'communitySearchTerm': communitySearchTerm,
				'includeHtml': true
			}
			$.getJSON(url, params, function (data) {
				$("#communitySearchResultsLoading").hide();
				if (data.success === true) {
					$("#communitySearchResults").html(data.communityResults);
				} else {
					$("#communitySearchResults").html(data.message);
				}
			});
		},

		searchReleaseNotes: function () {
			var url = Globals.path + '/Admin/AJAX';
			var searchTermString = $("#releaseSearchTerm").val();
			var params = {
				'method': 'searchReleaseNotes',
				'searchTerm': searchTermString,
				'includeHtml': true
			}

			if (searchTermString !== '') {
				$.getJSON(url, params, function (data) {
					$("#noSearchTerm").hide();
					$("#releaseNotesSearchResults").html(data.html);
				});
			} else {
				$("#noSearchTerm").show();
				$("#releaseNotesSearchResults").html('');
			}
		},

		showBatchScheduleUpdateForm: function (implementationStatus, siteType, version, timezone) {
			var url = Globals.path + '/Greenhouse/AJAX';
			var params = {
				'method': 'getBatchScheduleUpdateForm',
				'implementationStatus': implementationStatus,
				'siteType': siteType,
				'currentVersion': version,
				'timezone': timezone
			}
			AspenDiscovery.loadingMessage();
			$.getJSON(url, params,
				function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		showSelectedScheduleUpdateForm: function () {
			var selectedSites = AspenDiscovery.getSelectedAspenSites();
			if (selectedSites) {
				var url = Globals.path + '/Greenhouse/AJAX';
				var params = {
					'method': 'getSelectedScheduleUpdateForm',
					'sitesToUpdate': selectedSites
				}
				AspenDiscovery.loadingMessage();
				$.getJSON(url, params,
					function (data) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					}
				);
				return false;
			}
		},

		showScheduleUpdateForm: function (siteId) {
			var url = Globals.path + '/Greenhouse/AJAX';
			var params = {
				'method': 'getScheduleUpdateForm',
				'siteId': siteId
			}
			AspenDiscovery.loadingMessage();
			$.getJSON(url, params,
				function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		scheduleUpdate: function () {
			var url = Globals.path + '/Greenhouse/AJAX?method=scheduleUpdate';
			var newData = new FormData($("#scheduleUpdateForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: newData,
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

		showScheduledUpdateDetails: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + "/Greenhouse/AJAX?method=showScheduledUpdateDetails&id=" + id, true);
		},

		toggleFieldLock: function (module, tool, field) {
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'toggleFieldLock',
				moduleName: module,
				toolName: tool,
				fieldName: field
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					$('#fieldLock' + field).replaceWith(data.lockToggle);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		showCopyOptions: function (module, toolname, id) {
			var url = Globals.path + '/' + module + '/' + toolname;
			var params = {
				id: id,
				objectAction: 'getCopyOptions'
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		showCopyMenuLinksForm: function (libraryId) {
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'getCopyMenuLinksForm',
				libraryId: libraryId
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		processCopyMenuLinksForm: function () {
			var selectedMenuLinks = $('.menuLink:checked');
			var selectedLibraries = $('.library:checked');
			if (selectedMenuLinks.length >= 1) {
				if (selectedLibraries.length >= 1) {
					var url = Globals.path + "/Admin/AJAX";
					var params = {
						method: 'copyMenuLinks',
						sourceLibraryId: $('#sourceLibraryId').val()
					};
					selectedMenuLinks.each(function () {
						params[$(this).prop('name')] = 'on';
					});
					selectedLibraries.each(function () {
						params[$(this).prop('name')] = 'on';
					});
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
				} else {
					alert("Select at least one library to copy to");
				}
			} else {
				alert("Select at least one menu link to copy");
			}
			return false;
		},

		calculateGroupingCategories: function (sourceControl) {
			var sourceControlObj = $(sourceControl);
			var index = sourceControlObj.data("id");
			if (index !== undefined) {
				var format = $(`input[name="formatMap_format[${index}]"]`).val();
				var formatCategory = $(`select[name="formatMap_formatCategory[${index}]"] option:selected`).val();
				var groupingCategory = format.match(/graphicnovel|graphic novel|comic|ecomic|manga/gi) ? 'comic' : 'book';
				switch(formatCategory) {
					case "Movies":
						groupingCategory = 'movie';
						break;
					case "Music":
						groupingCategory = 'music';
						break;
					case "Other":
						groupingCategory = 'other';
						break;
					default:
						break;
				}
				$(`#formatMap_groupingCategory_${index}`).text(groupingCategory);
			}
			return true;
		},
		updateSyndeticsFields: function () {
			var isUnbound = $("#syndeticsUnbound").prop("checked");
			if (isUnbound) {
				$("#propertyRowunboundAccountNumber").show();
				$("#propertyRowunboundInstanceNumber").show();
				$("#propertyRowhasSummary").hide();
				$("#propertyRowhasAvSummary").hide();
				$("#propertyRowhasAvProfile").hide();
				$("#propertyRowhasToc").hide();
				$("#propertyRowhasExcerpt").hide();
				$("#propertyRowhasFictionProfile").hide();
				$("#propertyRowhasAuthorNotes").hide();
				$("#propertyRowhasVideoClip").hide();
			} else {
				$("#propertyRowunboundAccountNumber").hide();
				$("#propertyRowunboundInstanceNumber").hide();
				$("#propertyRowhasSummary").show();
				$("#propertyRowhasAvSummary").show();
				$("#propertyRowhasAvProfile").show();
				$("#propertyRowhasToc").show();
				$("#propertyRowhasExcerpt").show();
				$("#propertyRowhasFictionProfile").show();
				$("#propertyRowhasAuthorNotes").show();
				$("#propertyRowhasVideoClip").show();
			}
		},
		validateSublocationHoldPickupAreaAspen: function (sourceControl) {
			var sourceControlObj = $(sourceControl);
			var index = sourceControlObj.data("id");
			if (index !== undefined) {
				var ilsId = $('input[name="sublocations_ilsId[' + index + ']"]').val();
				var isValidHoldPickupAreaILSValue = $('input[name="sublocations_isValidHoldPickupAreaILS[' + index + ']"]').is(":checked");
				var isValidHoldPickupAreaAspen = $('input[name="sublocations_isValidHoldPickupAreaAspen[' + index + ']"]');
				if (ilsId === '' || !isValidHoldPickupAreaILSValue) {
					isValidHoldPickupAreaAspen.removeAttr('checked');
					$(isValidHoldPickupAreaAspen).attr('disabled', true);
				} else {
					$(isValidHoldPickupAreaAspen).attr('disabled', false);
				}
			}
			return true;
		},

		checkSSOAuthOnlyPatronTypes: function (changedTarget) {
			const $ssoCheckbox = $("#ssoAuthOnly");
			const isSSOEnabled = $ssoCheckbox.is(':checked');
			const ssoSettingId = $("input[name='id']").val();
			const $formGroup = $ssoCheckbox.closest(".form-group");
			const $fallbackSelect = $("select[name='ssoCategoryIdFallback']");
			const $serviceSelect = $("#serviceSelect");
			const samlStaffPTypeSelect = $("select[name='samlStaffPType']");
			const oAuthStaffPTypeSelect = $("select[name='oAuthStaffPType']")
			const ssoService = $serviceSelect.val();
			const warningId = "#ssoAuthOnly_warning";
			const fallbackWarningId = "#fallback_warning";
			const staffWarningId = "#staff_warning";

			// Get the appropriate staff type selector based on the current service.
			let $staffTypeSelect;
			if (ssoService === 'saml') {
				$staffTypeSelect = samlStaffPTypeSelect
			} else if (ssoService === 'oauth') {
				$staffTypeSelect = oAuthStaffPTypeSelect
			} else {
				$staffTypeSelect = $();
			}

			// Check if LDAP is selected as the service or no service selected, and if so, exit early.
			if (ssoService === 'ldap' || ssoService === '0') {
				$(warningId).remove();
				$(fallbackWarningId).remove();
				$(staffWarningId).remove();
				return true;
			}

			// Remove previous warnings and listeners.
			if (changedTarget === 'fallback') {
				$(fallbackWarningId).remove();
			}
			else if (changedTarget === 'staff') {
				$(staffWarningId).remove();
			}
			else {
				$(fallbackWarningId).remove();
				$(staffWarningId).remove();
				$(warningId).remove();
			}
			$fallbackSelect.off('change.ssoWarning');
			samlStaffPTypeSelect.off('change.ssoWarning');
			oAuthStaffPTypeSelect.off('change.ssoWarning');
			$serviceSelect.off('change.ssoService');

			// Change listener to service selector to rerun validation.
			$serviceSelect.on('change.ssoService', function() {
				setTimeout(() => AspenDiscovery.Admin.checkSSOAuthOnlyPatronTypes(), 100);
			});

			if (!isSSOEnabled) return true;

			// Show loading message.
			if (!changedTarget) {
				$formGroup.after(`
					<div id="ssoAuthOnly_warning" class="alert alert-warning mt-2">
						<strong>Checking patron type configuration...</strong>
					</div>
				`);
			}

			$fallbackSelect.on('change.ssoWarning', function () {
				setTimeout(() => AspenDiscovery.Admin.checkSSOAuthOnlyPatronTypes('fallback'), 100);
			});

			$staffTypeSelect.on('change.ssoWarning', function () {
				setTimeout(() => AspenDiscovery.Admin.checkSSOAuthOnlyPatronTypes('staff'), 100);
			});

			$.getJSON(`${Globals.path}/Admin/AJAX`, {
				method: 'getSSOPatronTypesForProfiles',
				ssoSettingId: ssoSettingId
			}).done(function (response) {
				if (changedTarget === 'fallback') {
					$(fallbackWarningId).remove();
				}
				else if (changedTarget === 'staff') {
					$(staffWarningId).remove();
				}
				else {
					$(fallbackWarningId).remove();
					$(staffWarningId).remove();
					$(warningId).remove();
				}

				if (!response.success) {
					showError(response.message || 'Could not retrieve patron type information.');
					return;
				}

				const {
					adminSSOExists,
					primaryProfilesData,
					adminSSOPatronTypes
				} = response;

				if (!adminSSOExists) {
					showMissingProfileError();
					return;
				}

				const adminPatronTypeKeys = Object.keys(adminSSOPatronTypes || {});
				const missingLabels = new Set();

				const selectedStaffType = $staffTypeSelect.val();
				const selectedFallbackType = $fallbackSelect.val();

				for (const [profileId, patronTypes] of Object.entries(primaryProfilesData)) {
					for (const [ptypeValue, ptypeLabel] of Object.entries(/** @type {Record<string, string>} */ (patronTypes))) {
						if (!adminPatronTypeKeys.includes(ptypeValue)) {
							missingLabels.add(ptypeLabel);

							if ((!changedTarget || changedTarget === 'staff') && ptypeValue === selectedStaffType && $staffTypeSelect.length > 0) {
								$(staffWarningId).remove();
								$staffTypeSelect.closest(".form-group").after(`
									<div id="staff_warning" class="alert alert-danger mt-2">
										<strong>Warning:</strong> The selected staff patron type "${ptypeLabel}" does not exist in the admin_sso profile.
										This patron type must be assigned to the admin_sso profile with the "Treat as Staff" option enabled and its respective staff role assigned for proper staff permissions.
										${ssoService === 'saml'
									? `<div class="mt-2">You may leave this blank if you selected a "A fallback value for category ID" assigned to the admin_sso account profile.</div>`
									: ''
								}
									</div>
								`);
							}

							if ((!changedTarget || changedTarget === 'fallback') && ptypeValue === selectedFallbackType) {
								$(fallbackWarningId).remove();
								$fallbackSelect.closest(".form-group").after(`
									<div id="fallback_warning" class="alert alert-danger mt-2">
										<strong>Warning:</strong> The selected fallback patron type "${ptypeLabel}" is not assigned to the admin_sso profile.
										This patron type must be assigned to the admin_sso profile for SSO users to receive the correct permissions.
									</div>
								`);
							}
						}
					}
				}

				if (!changedTarget) {
					$(warningId).remove();
					const availablePatronTypes = Object.values(adminSSOPatronTypes || {});
					let warningHtml = `
						<div id="ssoAuthOnly_warning" class="alert alert-warning mt-2">
							<strong>Important:</strong> When "Only authenticate users with single sign-on" is enabled, 
							you must ensure that patron types used by SSO users are assigned to the admin_sso account profile.`;

					if (missingLabels.size > 0) {
						warningHtml += `
							<div class="mt-2">
								The following patron types from other primary account profile(s) are not assigned to the admin_sso profile: 
								${[...missingLabels].join(", ")}.
							</div>`;
					}

					if (availablePatronTypes.length > 0) {
						warningHtml += `
							<div class="mt-2">
								<strong>Available patron type(s) assigned to the admin_sso profile:</strong> ${availablePatronTypes.join(", ")}.
							</div>`;
					} else {
						warningHtml += `
							<div class="mt-2">
								<strong>No patron types are assigned to the admin_sso profile.</strong>
							</div>`;
					}

					warningHtml += `
						<div class="mt-2">
							<strong>Solution:</strong> Create at least one matching patron type assigned to the admin_sso profile that should have the same permissions 
							as its counterpart in other account profile(s).
						</div>
					</div>`;

					$formGroup.after(warningHtml);
				}
			}).fail(function () {
				showError('Could not connect to the server to verify patron types.');
			});

			return true;

			function showError(message) {
				$(warningId).remove();
				$formGroup.after(`
					<div id="ssoAuthOnly_warning" class="alert alert-warning mt-2">
						<p><strong>${message}</strong></p>
						<strong>Important:</strong> When "Only authenticate users with single sign-on" is enabled, 
						you must ensure that patron types used by SSO users are assigned to the admin_sso account profile. 
						If using a fallback patron type, it must also exist in the admin_sso profile.
						<div class="mt-2">Failure to configure these properly will result in users not receiving the correct permissions and staff features may not work.</div>
					</div>
				`);
			}

			function showMissingProfileError() {
				$(warningId).remove();
				$formGroup.after(`
					<div id="ssoAuthOnly_warning" class="alert alert-danger alert-outline mt-2">
						<strong>Error:</strong> The "admin_sso" account profile has not been created yet. 
						This profile is required when "Only authenticate users with single sign-on" is enabled.
						Please complete initial SSO setup first and ensure it is spelled and formatted correctly.
					</div>
				`);
			}
		},

		recycleBinDelete(scope) {
			const selected = $('.selectedObject:checked');
			const count = selected.length;
			let title, body, okLabel;

			if (scope === 'selected') {
				if (!selected.length) {
					AspenDiscovery.showMessage('Failed to Delete Selected Objects', 'Please select at least one object to delete.');
					return false;
				}
			}
			if (scope === 'all') {
				title = 'Permanently Delete All';
				body = 'Are you sure you want to permanently delete ALL objects? This action cannot be undone.';
				okLabel = 'Delete All';
			} else {
				title = 'Permanently Delete Selected';
				body = 'Are you sure you want to permanently delete ' + count + ' object(s)? This action cannot be undone.';
				okLabel = 'Delete';
			}

			const confirmJs = "$(\"#objectAction\").val(\"batchHardDelete\"); $(\"#propertiesListForm\").trigger('submit');";

			AspenDiscovery.confirm(title, body, okLabel, 'Cancel', true, confirmJs, 'btn-danger');
			return false;
		},

		getNotificationDevicesForUser: function () {
			const barcode = $("#testPatronBarcode").val();
			if (barcode) {
				$.getJSON(Globals.path + "/Admin/AJAX?method=getNotificationDevicesForUser&user=" + barcode, function (data) {
					if (data.success) {
						$("#error").html(data.message).hide();
						$("#patronDevices").html(data.message).show();
						$("#notificationSetup").show();
					} else {
						$("#patronDevices").html(data.message).hide();
						$("#error").html(data.message).show();
					}
					return data;
				});
			}
			return false;
		},
		displayDigitalRewardPlaceholderUpload: function () {
			const placeholderImageUpload = $('#propertyRowdigitalRewardPlaceholderImage');
			const digitalRewardControl = $('#displayDigitalRewardOnlyWhenAwarded');
			const displayOnRewardOnlyIsChecked = digitalRewardControl.is(':checked');

			if (displayOnRewardOnlyIsChecked) {
				placeholderImageUpload.show();
			} else {
				placeholderImageUpload.hide();
			}
		},
		highlightCampaignsOpenToEnroll: function() {
			const highlightOpenToEnrollCampaigns = $('#propertyRowhighlightCommunityEngagementOpenToEnroll');
			const highlightCampaigns = $('#highlightCommunityEngagement');
			const isChecked = highlightCampaigns.is(':checked');

				if (isChecked) {
					highlightOpenToEnrollCampaigns.show();
				} else {
					highlightOpenToEnrollCampaigns.hide();
				}
		},

		initializeScrollPositioning() {
			// Check if the scrollToId parameter is in the URL.
			const urlParams = new URLSearchParams(window.location.search);
			const scrollToId = urlParams.get('scrollToId');
			if (!scrollToId) return;

			const findTargetRow = () => {
				return $('#adminTable tbody tr').filter((index, row) => {
					const $row = $(row);
					const $editLink = $row.find(`a[href*="objectAction=edit"][href*="id=${scrollToId}"]`);
					return $editLink.length > 0;
				}).first();
			};

			const scrollPageToTable = () => {
				const $tableContainer = $('.adminTableRegion');
				if ($tableContainer.length === 0) return;

				const containerOffset = $tableContainer.offset();
				const windowHeight = $(window).height();
				const windowScrollTop = $(window).scrollTop();
				const containerTop = containerOffset.top;
				const containerBottom = containerTop + $tableContainer.outerHeight();

				// Check if the container needs to be scrolled into view.
				const containerNotVisible = containerTop < windowScrollTop || containerBottom > (windowScrollTop + windowHeight);
				if (containerNotVisible) {
					window.scrollTo(0, containerTop - 100);
				}
			};

			const scrollTableToRow = ($targetRow) => {
				const $tableContainer = $('.adminTableRegion');
				const $table = $('#adminTable');

				if ($tableContainer.length === 0 || $table.length === 0 || $targetRow.length === 0) {
					return false;
				}

				// Calculate position relative to the table container only.
				const containerOffset = $tableContainer.offset();
				const rowOffset = $targetRow.offset();
				const relativeRowTop = rowOffset.top - containerOffset.top;
				const containerHeight = $tableContainer.height();
				const targetScroll = relativeRowTop - (containerHeight / 2);
				$tableContainer.scrollTop(Math.max(0, targetScroll));

				return true;
			};

			const $targetRow = findTargetRow();
			if ($targetRow.length === 0) {
				return;
			}

			// Always scroll page immediately.
			scrollPageToTable();

			// Check if there are images in the table that might still be loading.
			const $images = $('#adminTable img');
			if ($images.length > 0) {
				const $indicator = $('<div class="scroll-loading-indicator" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; font-size: 14px;"><i class="fas fa-spinner fa-spin"></i> Positioning content...</div>');
				$('body').append($indicator);

				let loadedCount = 0;
				const totalImages = $images.length;
				let isCompleted = false;

				const finalizeScrolling = () => {
					if (isCompleted) return;
					isCompleted = true;

					// Calculate position relative to container only.
					const $tableContainer = $('.adminTableRegion');
					const containerOffset = $tableContainer.offset();
					const rowOffset = $targetRow.offset();
					const relativeRowTop = rowOffset.top - containerOffset.top;
					const containerHeight = $tableContainer.height();
					const targetScroll = relativeRowTop - (containerHeight / 2);
					$tableContainer.scrollTop(Math.max(0, targetScroll));

					$indicator.remove();
				};

				const checkAllImagesLoaded = () => {
					loadedCount++;
					if (loadedCount >= totalImages) {
						setTimeout(finalizeScrolling, 100);
					}
				};

				// Set a timeout fallback in case images don't load properly.
				const timeoutId = setTimeout(() => {
					console.warn('Image loading timeout - proceeding with scroll positioning.');
					finalizeScrolling();
				}, 5000);

				$images.each(function() {
					const img = this;
					if (img.complete && img.naturalWidth > 0) {
						checkAllImagesLoaded();
					} else {
						$(img).on('load error', () => {
							checkAllImagesLoaded();
							if (loadedCount >= totalImages) {
								clearTimeout(timeoutId);
							}
						});
					}
				});

				if (loadedCount >= totalImages) {
					clearTimeout(timeoutId);
				}
			} else {
				// No images, scroll to row immediately.
				scrollTableToRow($targetRow);
			}
		},

		toggleCheckboxOptions(checkboxId) {
			const $optionsDiv = $('#' + checkboxId + '_options');
			if ($('#' + checkboxId).prop('checked')) {
				$optionsDiv.stop(true, true).slideDown(200);
			} else {
				$optionsDiv.stop(true, true).slideUp(200);
			}
		},

		toggleAllCheckboxOptions(propName, selectAllId){
			const $selectAll = $(selectAllId);
			const isChecked = $selectAll.prop('checked');
			const $checkboxes = $(`.${propName}Checkbox`);

			$checkboxes.each((_, el) => {
				const $checkbox = $(el);
				const id = $checkbox.attr('id');
				const $options = $(`#${id}_options`);

				$checkbox.prop('checked', isChecked);
				if (isChecked) {
					$options.stop(true, true).slideDown(200);
				} else {
					$options.stop(true, true).slideUp(200);
				}
			});
		}
	};
}(AspenDiscovery.Admin || {}));