AspenDiscovery.Admin = (function () {
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
			if (property === 'pageBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'bodyBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'bodyTextColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#6B6B6B";
					document.getElementById(property).value = "#6B6B6B";
				}
			} else if (property === 'linkColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#3174AF";
					document.getElementById(property).value = "#3174AF";
				}
			} else if (property === 'linkHoverColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#265a87";
					document.getElementById(property).value = "#265a87";
				}
			} else if (property === 'resultLabelColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#44484a";
					document.getElementById(property).value = "#44484a";
				}
			} else if (property === 'resultValueColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#6B6B6B";
					document.getElementById(property).value = "#6B6B6B";
				}
			} else if (property === 'headerBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f1f1f1";
					document.getElementById(property).value = "#f1f1f1";
				}
			} else if (property === 'headerForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#303030";
					document.getElementById(property).value = "#303030";
				}
			} else if (property === 'breadcrumbsBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f5f5f5";
					document.getElementById(property).value = "#f5f5f5";
				}
			} else if (property === 'breadcrumbsForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#6B6B6B";
					document.getElementById(property).value = "#6B6B6B";
				}
			} else if (property === 'searchToolsBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f5f5f5";
					document.getElementById(property).value = "#f5f5f5";
				}
			} else if (property === 'searchToolsBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#e3e3e3";
					document.getElementById(property).value = "#e3e3e3";
				}
			} else if (property === 'searchToolsForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#6B6B6B";
					document.getElementById(property).value = "#6B6B6B";
				}
			} else if (property === 'footerBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f1f1f1";
					document.getElementById(property).value = "#f1f1f1";
				}
			} else if (property === 'footerForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#303030";
					document.getElementById(property).value = "#303030";
				}
			} else if (property === 'primaryBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#0a7589";
					document.getElementById(property).value = "#0a7589";
				}
			} else if (property === 'primaryForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'secondaryBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#de9d03";
					document.getElementById(property).value = "#de9d03";
				}
			} else if (property === 'secondaryForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#303030";
					document.getElementById(property).value = "#303030";
				}
			} else if (property === 'tertiaryBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#de1f0b";
					document.getElementById(property).value = "#de1f0b";
				}
			} else if (property === 'tertiaryForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#000000";
					document.getElementById(property).value = "#000000";
				}
			} else if (property === 'menubarBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f1f1f1";
					document.getElementById(property).value = "#f1f1f1";
				}
			} else if (property === 'menubarForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#303030";
					document.getElementById(property).value = "#303030";
				}
			} else if (property === 'menubarHighlightBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f1f1f1";
					document.getElementById(property).value = "#f1f1f1";
				}
			} else if (property === 'menubarHighlightForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#265a87";
					document.getElementById(property).value = "#265a87";
				}
			} else if (property === 'menuDropdownBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ededed";
					document.getElementById(property).value = "#ededed";
				}
			} else if (property === 'menuDropdownForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#404040";
					document.getElementById(property).value = "#404040";
				}
			} else if (property === 'modalDialogBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'modalDialogForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'modalDialogHeaderFooterBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'modalDialogHeaderFooterForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'modalDialogHeaderFooterBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#e5e5e5";
					document.getElementById(property).value = "#e5e5e5";
				}
			} else if (property === 'browseCategoryPanelColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#d7dce3";
					document.getElementById(property).value = "#d7dce3";
				}
			} else if (property === 'selectedBrowseCategoryBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#0087AB";
					document.getElementById(property).value = "#0087AB";
				}
			} else if (property === 'selectedBrowseCategoryForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'selectedBrowseCategoryBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#0087AB";
					document.getElementById(property).value = "#0087AB";
				}
			} else if (property === 'deselectedBrowseCategoryBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#0087AB";
					document.getElementById(property).value = "#0087AB";
				}
			} else if (property === 'deselectedBrowseCategoryForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'deselectedBrowseCategoryBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#0087AB";
					document.getElementById(property).value = "#0087AB";
				}
			} else if (property === 'badgeBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#666666";
					document.getElementById(property).value = "#666666";
				}
			} else if (property === 'badgeForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'closedPanelBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#e7e7e7";
					document.getElementById(property).value = "#e7e7e7";
				}
			} else if (property === 'closedPanelForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'openPanelBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'openPanelForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'panelBodyBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'panelBodyForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#404040";
					document.getElementById(property).value = "#404040";
				}
			} else if (property === 'defaultButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'defaultButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'defaultButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#cccccc";
					document.getElementById(property).value = "#cccccc";
				}
			} else if (property === 'defaultButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#eeeeee";
					document.getElementById(property).value = "#eeeeee";
				}
			} else if (property === 'defaultButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#333333";
					document.getElementById(property).value = "#333333";
				}
			} else if (property === 'defaultButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#cccccc";
					document.getElementById(property).value = "#cccccc";
				}
			} else if (property === 'primaryButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'primaryButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'primaryButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'primaryButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'primaryButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'primaryButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'actionButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'actionButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'actionButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'actionButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'actionButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'actionButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'editionsButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f8f9fa";
					document.getElementById(property).value = "#f8f9fa";
				}
			} else if (property === 'editionsButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#212529";
					document.getElementById(property).value = "#212529";
				}
			} else if (property === 'editionsButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#999999";
					document.getElementById(property).value = "#999999";
				}
			} else if (property === 'editionsButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'editionsButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'editionsButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#1b6ec2";
					document.getElementById(property).value = "#1b6ec2";
				}
			} else if (property === 'toolsButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#747474";
					document.getElementById(property).value = "#747474";
				}
			} else if (property === 'toolsButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'toolsButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#636363";
					document.getElementById(property).value = "#636363";
				}
			} else if (property === 'toolsButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#636363";
					document.getElementById(property).value = "#636363";
				}
			} else if (property === 'toolsButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'toolsButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#636363";
					document.getElementById(property).value = "#636363";
				}
			} else if (property === 'infoButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#8cd2e7";
					document.getElementById(property).value = "#8cd2e7";
				}
			} else if (property === 'infoButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#000000";
					document.getElementById(property).value = "#000000";
				}
			} else if (property === 'infoButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#999999";
					document.getElementById(property).value = "#999999";
				}
			} else if (property === 'infoButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'infoButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#217e9b";
					document.getElementById(property).value = "#217e9b";
				}
			} else if (property === 'infoButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#217e9b";
					document.getElementById(property).value = "#217e9b";
				}
			} else if (property === 'warningButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#f4d03f";
					document.getElementById(property).value = "#f4d03f";
				}
			} else if (property === 'warningButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#000000";
					document.getElementById(property).value = "#000000";
				}
			} else if (property === 'warningButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#999999";
					document.getElementById(property).value = "#999999";
				}
			} else if (property === 'warningButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'warningButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#8d6708";
					document.getElementById(property).value = "#8d6708";
				}
			} else if (property === 'warningButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#8d6708";
					document.getElementById(property).value = "#8d6708";
				}
			} else if (property === 'dangerButtonBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#D50000";
					document.getElementById(property).value = "#D50000";
				}
			} else if (property === 'dangerButtonForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'dangerButtonBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#999999";
					document.getElementById(property).value = "#999999";
				}
			} else if (property === 'dangerButtonHoverBackgroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#ffffff";
					document.getElementById(property).value = "#ffffff";
				}
			} else if (property === 'dangerButtonHoverForegroundColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#D50000";
					document.getElementById(property).value = "#D50000";
				}
			} else if (property === 'dangerButtonHoverBorderColor') {
				if (extendedThemeDefault != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
					document.getElementById(property + 'Hex').value = extendedThemeDefault;
					document.getElementById(property).value = extendedThemeDefault;
				} else {
					document.getElementById(property + 'Hex').value = "#D50000";
					document.getElementById(property).value = "#D50000";
				}
			}
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
				var contrastRatio;
				if (luminance1 > luminance2) {
					contrastRatio = ((luminance1 + 0.05) / (luminance2 + 0.05));
				} else {
					contrastRatio = ((luminance2 + 0.05) / (luminance1 + 0.05));
				}
				var contrastSpan1 = $("#contrast_" + property1);
				var contrastSpan2 = $("#contrast_" + property2);
				contrastSpan1.text(contrastRatio.toFixed(2));
				contrastSpan2.text(contrastRatio.toFixed(2));
				if (minRatio == 7.0) {
					if (contrastRatio < 4.5) {
						contrastSpan1.addClass("alert-danger");
						contrastSpan2.addClass("alert-danger");
						contrastSpan1.removeClass("alert-warning");
						contrastSpan2.removeClass("alert-warning");
						contrastSpan1.removeClass("alert-success");
						contrastSpan2.removeClass("alert-success");
					} else if (contrastRatio < minRatio) {
						contrastSpan1.removeClass("alert-danger");
						contrastSpan2.removeClass("alert-danger");
						contrastSpan1.addClass("alert-warning");
						contrastSpan2.addClass("alert-warning");
						contrastSpan1.removeClass("alert-success");
						contrastSpan2.removeClass("alert-success");
					} else {
						contrastSpan1.removeClass("alert-danger");
						contrastSpan2.removeClass("alert-danger");
						contrastSpan1.removeClass("alert-warning");
						contrastSpan2.removeClass("alert-warning");
						contrastSpan1.addClass("alert-success");
						contrastSpan2.addClass("alert-success");
					}
				} else {
					if (contrastRatio < 3.5) {
						contrastSpan1.addClass("alert-danger");
						contrastSpan2.addClass("alert-danger");
						contrastSpan1.removeClass("alert-warning");
						contrastSpan2.removeClass("alert-warning");
						contrastSpan1.removeClass("alert-success");
						contrastSpan2.removeClass("alert-success");
					} else if (contrastRatio < minRatio) {
						contrastSpan1.removeClass("alert-danger");
						contrastSpan2.removeClass("alert-danger");
						contrastSpan1.addClass("alert-warning");
						contrastSpan2.addClass("alert-warning");
						contrastSpan1.removeClass("alert-success");
						contrastSpan2.removeClass("alert-success");
					} else {
						contrastSpan1.removeClass("alert-danger");
						contrastSpan2.removeClass("alert-danger");
						contrastSpan1.removeClass("alert-warning");
						contrastSpan2.removeClass("alert-warning");
						contrastSpan1.addClass("alert-success");
						contrastSpan2.addClass("alert-success");
					}
				}
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
		updateMaterialsRequestFields: function () {
			var materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
			$("#propertyRowallowDeletingILSRequests").hide();
			if (materialRequestType === "0" || materialRequestType === "2") {
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxActiveRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide();
				if (materialRequestType === "2") {
					$("#propertyRowallowDeletingILSRequests").show();
				}
			} else if (materialRequestType === "1") {
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").show();
				$("#propertyRowmaxActiveRequests").show();
				$("#propertyRowmaterialsRequestDaysToPreserve").show();
				$("#propertyRowmaterialsRequestFieldsToDisplay").show();
				$("#propertyRowmaterialsRequestFormats").show();
				$("#propertyRowmaterialsRequestFormFields").show()
			} else if (materialRequestType === "3") {
				$("#propertyRowexternalMaterialsRequestUrl").show();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxActiveRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide()
			}
			return false;
		},
		updateDonationFields: function () {
			var donationsEnabled = $("#enableDonations");
			var donationsEnabledValue = $("#enableDonations:checked").val()
			if (donationsEnabledValue == 1) {
				$("#propertyRowallowDonationsToBranch").show();
				$("#propertyRowallowDonationEarmark").show();
				$("#propertyRowallowDonationDedication").show();
				$("#propertyRowdonationValues").show();
				$("#propertyRowdonationContent").show();
			} else {
				$("#propertyRowallowDonationsToBranch").hide();
				$("#propertyRowallowDonationEarmark").hide();
				$("#propertyRowallowDonationDedication").hide();
				$("#propertyRowdonationValues").hide();
				$("#propertyRowdonationContent").hide();
			}

			return false;
		},
		validateCompare: function () {
			var selectedObjects = $('.selectedObject:checked');
			if (selectedObjects.length === 2) {
				return true;
			} else {
				AspenDiscovery.showMessage("Error", "Please select only two objects to compare");
				return false;
			}
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
			} else {
				AspenDiscovery.showMessage("Error", "Please select at least one object to update");
				return false;
			}
		},
		processBatchUpdateFieldForm: function (module, toolName, batchUpdateScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchUpdateScope === 'all' || selectedObjects.length >= 1) {
				var url = Globals.path + "/Admin/AJAX";
				var selectedField = $('#fieldSelector').val();
				var selectedFieldControl = $('#' + selectedField);
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
		updateBrowseSearchForSource: function () {
			var selectedSource = $('#sourceSelect').val();
			if (selectedSource === 'List') {
				$("#propertyRowsearchTerm").hide();
				$("#propertyRowdefaultFilter").hide();
				$("#propertyRowdefaultSort").hide();
				$("#propertyRowsourceListId").show();
				$("#propertyRowsourceCourseReserveId").hide();
			} else if (selectedSource === 'CourseReserve') {
				$("#propertyRowsearchTerm").hide();
				$("#propertyRowdefaultFilter").hide();
				$("#propertyRowdefaultSort").hide();
				$("#propertyRowsourceListId").hide();
				$("#propertyRowsourceCourseReserveId").show();
			} else {
				$("#propertyRowsearchTerm").show();
				$("#propertyRowdefaultFilter").show();
				$("#propertyRowdefaultSort").show();
				$("#propertyRowsourceListId").hide();
				$("#propertyRowsourceCourseReserveId").hide();
			}
		},
		updateGroupedWorkDisplayFields: function () {
			var showSearchTools = $('#showSearchTools');
			if (showSearchTools.is(":checked")) {
				$("#propertyRowshowSearchToolsAtTop").show();
			} else {
				$("#propertyRowshowSearchToolsAtTop").hide();
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
			if (groupingCategory == 'book') {
				var selectedOption = $("#bookSortMethodSelect").find(":selected").val();
				if (selectedOption == 1) {
					$("#propertyRowsortedBookFormats").hide();
				} else {
					$("#propertyRowsortedBookFormats").show();
				}
			} else if (groupingCategory == 'comic') {
				var selectedOption = $("#comicSortMethodSelect").find(":selected").val();
				if (selectedOption == 1) {
					$("#propertyRowsortedComicFormats").hide();
				} else {
					$("#propertyRowsortedComicFormats").show();
				}
			} else if (groupingCategory == 'movie') {
				var selectedOption = $("#movieSortMethodSelect").find(":selected").val();
				if (selectedOption == 1) {
					$("#propertyRowsortedMovieFormats").hide();
				} else {
					$("#propertyRowsortedMovieFormats").show();
				}
			} else if (groupingCategory == 'music') {
				var selectedOption = $("#musicSortMethodSelect").find(":selected").val();
				if (selectedOption == 1) {
					$("#propertyRowsortedMusicFormats").hide();
				} else {
					$("#propertyRowsortedMusicFormats").show();
				}
			} else if (groupingCategory == 'other') {
				var selectedOption = $("#otherSortMethodSelect").find(":selected").val();
				if (selectedOption == 1) {
					$("#propertyRowsortedOtherFormats").hide();
				} else {
					$("#propertyRowsortedOtherFormats").show();
				}
			}
		},
		updateIndexingProfileFields: function () {
			var audienceType = $('#determineAudienceBySelect').val();
			if (audienceType === '3') {
				$("#propertyRowaudienceSubfield").show();
			} else {
				$("#propertyRowaudienceSubfield").hide();
			}
			var formatSource = $('#formatSourceSelect').val();
			if (formatSource === 'specified') {
				$("#propertyRowspecifiedFormat").show();
				$("#propertyRowspecifiedFormatCategory").show();
				$("#propertyRowspecifiedFormatBoost").show();
				$("#propertyRowcheckRecordForLargePrint").hide();
				$("#propertyRowformatMap").hide();
			} else if (formatSource === 'item') {
				$("#propertyRowspecifiedFormat").hide();
				$("#propertyRowspecifiedFormatCategory").hide();
				$("#propertyRowspecifiedFormatBoost").hide();
				$("#propertyRowformatMap").show();
				$("#propertyRowcheckRecordForLargePrint").show();
			} else {
				$("#propertyRowspecifiedFormat").hide();
				$("#propertyRowspecifiedFormatCategory").hide();
				$("#propertyRowspecifiedFormatBoost").hide();
				$("#propertyRowformatMap").show();
				$("#propertyRowcheckRecordForLargePrint").hide();
			}
		},
		setIndexingProfileDefaultsByIndexingClass: function () {
			var selectedIndexingClass = $("#indexingClassSelect").val();
			if (selectedIndexingClass === '') {
				$("#catalogDriver").val('AbstractIlsDriver');
			}else {
				if (selectedIndexingClass === 'ArlingtonKoha') {
					$("#catalogDriver").val('Koha');
				}else if (selectedIndexingClass === 'CarlX') {
					$("#catalogDriver").val('CarlX');
				}else if (selectedIndexingClass === 'Evergreen') {
					$("#catalogDriver").val('Evergreen');
				}else if (selectedIndexingClass === 'Evolve') {
					$("#catalogDriver").val('Evolve');
				}else if (selectedIndexingClass === 'III') {
					$("#catalogDriver").val('Sierra');
				}else if (selectedIndexingClass === 'Koha') {
					$("#catalogDriver").val('Koha');
				}else if (selectedIndexingClass === 'NashvilleCarlX') {
					$("#catalogDriver").val('Nashville');
				}else if (selectedIndexingClass === 'Polaris') {
					$("#catalogDriver").val('Polaris');
				}else if (selectedIndexingClass === 'Symphony') {
					$("#catalogDriver").val('SirsiDynixROA');
				}
			}
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
			$(makeRowAccordion).click(function () {
				if (makeRowAccordion.is(":checked")) {
					$("#rowTitle").attr('required', "true");
				} else {
					$("#rowTitle").removeAttr('required');
				}
			});
		},

		updateMakeCellAccordion: function () {
			var makeCellAccordion = $('#makeCellAccordion');
			$(makeCellAccordion).click(function () {
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

			$(requireLogin).click(function () {
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
			$(allowEarmarks).click(function () {
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

			$(allowDedications).click(function () {
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
			$('#propertyRowctaUrl').hide();
			$('#propertyRowdeepLinkId').hide();
			$('#propertyRowdeepLinkPath').hide();
			$('#propertyRowdeepLinkFullPath').hide();

			var linkType = $("#linkTypeSelect").val();
			if (linkType === "0" || linkType === 0) {
				$('#propertyRowctaUrl').hide();
				$('#propertyRowdeepLinkId').hide();
				$('#propertyRowdeepLinkPath').show();
			} else {
				$('#propertyRowctaUrl').show();
				$('#propertyRowdeepLinkId').hide();
				$('#propertyRowdeepLinkPath').hide();
				$('#propertyRowdeepLinkFullPath').hide();
			}
		},
		getDeepLinkFullPath: function () {
			var selectedPath = $("#deepLinkPathSelect").val();
			if (selectedPath === "search") {
				$('#propertyRowdeepLinkId').show();
				$('label[for="deepLinkId"]').text("Search Term");
			} else if (selectedPath === "search/grouped_work") {
				$('#propertyRowdeepLinkId').show();
				$('label[for="deepLinkId"]').text("Grouped Work Id");
			} else if (selectedPath === "search/browse_category") {
				$('#propertyRowdeepLinkId').show();
				$('label[for="deepLinkId"]').text("Browse Category Text Id");
			} else if (selectedPath === "search/author") {
				$('#propertyRowdeepLinkId').show();
				$('label[for="deepLinkId"]').text("Author");
			} else if (selectedPath === "search/list") {
				$('#propertyRowdeepLinkId').show();
				$('label[for="deepLinkId"]').text("List Id");
			} else {
				$('#propertyRowdeepLinkId').hide();
			}
		},
		getSSOFields: function () {
			AspenDiscovery.Admin.toggleoAuthFields('hide');
			AspenDiscovery.Admin.toggleSamlFields('hide');
			AspenDiscovery.Admin.toggleLDAPFields('hide');
			AspenDiscovery.Admin.toggleOAuthGatewayFields();
			AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
			AspenDiscovery.Admin.toggleSamlMetadataFields();
			$("#clientSecret").attr('autocomplete', "off");
			$("#ldapPassword").attr('autocomplete', "off");
			var ssoService = $("#serviceSelect").val();
			if (ssoService === "oauth") {
				AspenDiscovery.Admin.toggleoAuthFields('show');
				AspenDiscovery.Admin.toggleSamlFields('hide');
				AspenDiscovery.Admin.toggleLDAPFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
				AspenDiscovery.Admin.toggleSamlMetadataFields();
			} else if (ssoService === "saml") {
				AspenDiscovery.Admin.toggleSamlFields('show');
				AspenDiscovery.Admin.toggleoAuthFields('hide');
				AspenDiscovery.Admin.toggleLDAPFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
				AspenDiscovery.Admin.toggleSamlMetadataFields();
				AspenDiscovery.Admin.toggleSamlUserIdFields();
				AspenDiscovery.Admin.toggleSamlUsernameFormatFields();
			} else if (ssoService === 'ldap') {
				AspenDiscovery.Admin.toggleSamlFields('hide');
				AspenDiscovery.Admin.toggleoAuthFields('hide');
				AspenDiscovery.Admin.toggleLDAPFields('show');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
				AspenDiscovery.Admin.toggleSamlMetadataFields();
				AspenDiscovery.Admin.toggleSamlUserIdFields();
				AspenDiscovery.Admin.toggleSamlUsernameFormatFields();
			} else {
				AspenDiscovery.Admin.toggleSamlFields('hide');
				AspenDiscovery.Admin.toggleoAuthFields('hide');
				AspenDiscovery.Admin.toggleLDAPFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
				AspenDiscovery.Admin.toggleSamlMetadataFields();
			}
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
			$(userIdOption).click(function () {
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
			$(usernameFormat).click(function () {
				if (usernameFormat.is(":checked")) {
					$('#propertyRowssoUsernameAttr').show();
					$('#propertyRowssoUsernameFormat').hide();
				} else {
					$('#propertyRowssoUsernameFormat').show();
					$('#propertyRowssoUsernameAttr').hide();
				}
			});
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
			if (selectedIls === 'na') {
				$("#driver").val('');
				$("#authenticationMethodSelect").val('db') ;
			}else {
				$("#authenticationMethodSelect").val('ils') ;
				if (selectedIls === 'carlx') {
					$("#driver").val('CarlX');
				}else if (selectedIls === 'evergreen') {
					$("#driver").val('Evergreen');
				}else if (selectedIls === 'evolve') {
					$("#driver").val('Evolve');
				}else if (selectedIls === 'koha') {
					$("#driver").val('Koha');
				}else if (selectedIls === 'polaris') {
					$("#driver").val('Polaris');
				}else if (selectedIls === 'sierra') {
					$("#driver").val('Sierra');
				}else if (selectedIls === 'symphony') {
					$("#driver").val('SirsiDynixROA');
				}
			}
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
				var format = $('input[name="formatMap_format[' + index + ']"]').val();
				var formatCategory = $('select[name="formatMap_formatCategory[' + index + ']"] option:selected').val();
				var groupingCategory = 'book';
				if (format.match(/graphicnovel|graphic novel|comic|ecomic|manga/gi)) {
					groupingCategory = 'comic';
				} else {
					if (formatCategory === "Movies") {
						groupingCategory = 'movie';
					} else if (formatCategory === "Music") {
						groupingCategory = 'music';
					} else if (formatCategory === "Other") {
						groupingCategory = 'other';
					}
				}
				$("#formatMap_groupingCategory_" + index).text(groupingCategory);
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
		}
	};
}(AspenDiscovery.Admin || {}));