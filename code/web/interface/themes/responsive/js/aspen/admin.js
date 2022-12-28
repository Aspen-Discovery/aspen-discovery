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
			;
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

		updateMaterialsRequestFields: function () {
			var materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
			$("#propertyRowallowDeletingILSRequests").hide();
			if (materialRequestType === "0" || materialRequestType === "2") {
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
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
				$("#propertyRowmaxOpenRequests").show();
				$("#propertyRowmaterialsRequestDaysToPreserve").show();
				$("#propertyRowmaterialsRequestFieldsToDisplay").show();
				$("#propertyRowmaterialsRequestFormats").show();
				$("#propertyRowmaterialsRequestFormFields").show()
			} else if (materialRequestType === "3") {
				$("#propertyRowexternalMaterialsRequestUrl").show();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
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
			var url = Globals.path + "/Admin/AJAX";
			var selectedNotes = $('#releaseSelector').val();
			var params = {
				method: 'getReleaseNotes',
				release: selectedNotes
			};
			$.getJSON(url, params,
				function (data) {
					if (data.success) {
						$("#releaseVersion").html(data.release);
						$("#releaseNotes").html(data.releaseNotes);
						if (data.actionItems === '') {
							$("#actionItemsSection").hide();
						} else {
							$("#actionItemsSection").show();
							$("#actionItems").html(data.actionItems);
						}
						if (data.testingSuggestions === '') {
							$("#testingSection").hide();
						} else {
							$("#testingSection").show();
							$("#testingSuggestions").html(data.testingSuggestions);
						}
					} else {
						$("#releaseNotes").html("Error + " + data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
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
		updateGroupedWorkDisplayFields: function() {
			var showSearchTools = $('#showSearchTools');
			if(showSearchTools.is(":checked")) {
				$("#propertyRowshowSearchToolsAtTop").show();
			}else {
				$("#propertyRowshowSearchToolsAtTop").hide();
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
			AspenDiscovery.Admin.toggleOAuthGatewayFields();
			AspenDiscovery.Admin.toggleOAuthPrivateKeysField();

			var ssoService = $("#serviceSelect").val();
			if (ssoService === "oauth") {
				AspenDiscovery.Admin.toggleoAuthFields('show');
				AspenDiscovery.Admin.toggleSamlFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
			} else {
				AspenDiscovery.Admin.toggleSamlFields('show');
				AspenDiscovery.Admin.toggleoAuthFields('hide');
				AspenDiscovery.Admin.toggleOAuthGatewayFields();
				AspenDiscovery.Admin.toggleOAuthPrivateKeysField();
			}
		},
		toggleoAuthFields: function (displayMode) {
			if (displayMode === "show") {
				$('#propertyRowclientId').show();
				$('#propertyRowclientSecret').show();
				$('#propertyRowgateway').show();
				$('#propertyRowoAuthGatewayLabel').hide();
				$('#propertyRowoAuthAccessTokenUrl').hide();
				$('#propertyRowoAuthAuthorizeUrl').hide();
				$('#propertyRowoAuthResourceOwnerUrl').hide();
				$('#propertyRowoAuthLogoutUrl').hide();
				$('#propertyRowoAuthScope').hide();
				$('#propertyRowoAuthGrantType').hide();
				$('#propertyRowoAuthPrivateKeys').hide();
				$('#propertyRowoAuthGatewayIcon').hide();
				$('#propertyRowoAuthButtonBackgroundColor').hide();
				$('#propertyRowoAuthButtonTextColor').hide();
			} else {
				$('#propertyRowclientId').hide();
				$('#propertyRowclientSecret').hide();
				$('#propertyRowgateway').hide();
				$('#propertyRowoAuthGatewayLabel').hide();
				$('#propertyRowoAuthAccessTokenUrl').hide();
				$('#propertyRowoAuthAuthorizeUrl').hide();
				$('#propertyRowoAuthResourceOwnerUrl').hide();
				$('#propertyRowoAuthLogoutUrl').hide();
				$('#propertyRowoAuthScope').hide();
				$('#propertyRowoAuthGrantType').hide();
				$('#propertyRowoAuthPrivateKeys').hide();
				$('#propertyRowoAuthGatewayIcon').hide();
				$('#propertyRowoAuthButtonBackgroundColor').hide();
				$('#propertyRowoAuthButtonTextColor').hide();
			}
		},
		toggleSamlFields: function (displayMode) {
			if (displayMode === "show") {
				$('#propertyRowssoName').show();
				$('#propertyRowssoXmlUrl').show();
				$('#propertyRowssoUniqueAttribute').show();
				$('#propertyRowssoIdAttr').show();
				$('#propertyRowssoUsernameAttr').show();
				$('#propertyRowssoFirstnameAttr').show();
				$('#propertyRowssoLastnameAttr').show();
				$('#propertyRowssoEmailAttr').show();
				$('#propertyRowssoDisplayNameAttr').show();
				$('#propertyRowssoPhoneAttr').show();
				$('#propertyRowssoAddressAttr').show();
				$('#propertyRowssoCityAttr').show();
				$('#propertyRowssoPatronTypeSection').show();
				$('#propertyRowssoLibraryIdSection').show();
				$('#propertyRowssoCategoryIdSection').show();
				$('#propertyRowssoMetadataFilename').show();
				$('#propertyRowssoEntityId').show();
			} else {
				$('#propertyRowssoName').hide();
				$('#propertyRowssoXmlUrl').hide();
				$('#propertyRowssoUniqueAttribute').hide();
				$('#propertyRowssoIdAttr').hide();
				$('#propertyRowssoUsernameAttr').hide();
				$('#propertyRowssoFirstnameAttr').hide();
				$('#propertyRowssoLastnameAttr').hide();
				$('#propertyRowssoEmailAttr').hide();
				$('#propertyRowssoDisplayNameAttr').hide();
				$('#propertyRowssoPhoneAttr').hide();
				$('#propertyRowssoAddressAttr').hide();
				$('#propertyRowssoCityAttr').hide();
				$('#propertyRowssoPatronTypeSection').hide();
				$('#propertyRowssoLibraryIdSection').hide();
				$('#propertyRowssoCategoryIdSection').hide();
				$('#propertyRowssoMetadataFilename').hide();
				$('#propertyRowssoEntityId').hide();
			}
		},
		toggleOAuthGatewayFields: function () {
			var oAuthGateway = $("#oAuthGatewaySelect").val();
			if (oAuthGateway === "custom") {
				$('#propertyRowoAuthGatewayLabel').show();
				$('#propertyRowoAuthAccessTokenUrl').show();
				$('#propertyRowoAuthAuthorizeUrl').show();
				$('#propertyRowoAuthResourceOwnerUrl').show();
				$('#propertyRowoAuthLogoutUrl').show();
				$('#propertyRowoAuthScope').show();
				$('#propertyRowoAuthGrantType').show();
				$('#propertyRowoAuthGatewayIcon').show();
				$('#propertyRowoAuthButtonBackgroundColor').show();
				$('#propertyRowoAuthButtonTextColor').show();
			} else {
				$('#propertyRowoAuthGatewayLabel').hide();
				$('#propertyRowoAuthAccessTokenUrl').hide();
				$('#propertyRowoAuthAuthorizeUrl').hide();
				$('#propertyRowoAuthResourceOwnerUrl').hide();
				$('#propertyRowoAuthLogoutUrl').hide();
				$('#propertyRowoAuthScope').hide();
				$('#propertyRowoAuthGrantType').hide();
				$('#propertyRowoAuthPrivateKeys').hide();
				$('#propertyRowoAuthGatewayIcon').hide();
				$('#propertyRowoAuthButtonBackgroundColor').hide();
				$('#propertyRowoAuthButtonTextColor').hide();
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
		}
	};
}(AspenDiscovery.Admin || {}));