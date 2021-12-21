AspenDiscovery.Admin = (function(){
	return {
		showReindexNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
			return false;
		},
		showCronNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
			return false;
		},
		showCronProcessNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
			return false;
		},
		toggleCronProcessInfo: function (id){
			$("#cronEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},

		showExtractNotes: function (id, source){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getExtractNotes&id=" + id + "&source=" + source, true);
			return false;
		},
		loadGoogleFontPreview: function (fontSelector) {
			var fontElement = $("#" + fontSelector);
			var fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		},
		getDefaultColor:function(property1,extendedTheme) {
			if(property1 == 'pageBackgroundColor'){
				// check if active theme has a value for "extendsTheme"
				if(extendedTheme != null) {
					// if a value is present, grab the color from that theme instead of Aspen default
				} else {
					document.getElementById(property1 + 'Hex').value = "#ffffff";
					document.getElementById(property1).value = "#ffffff";
				}
			} else if(property1 == 'bodyBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'bodyTextColor') {
				document.getElementById(property1 + 'Hex').value = "#6B6B6B";
				document.getElementById(property1).value = "#6B6B6B";
			} else if(property1 == 'linkColor') {
				document.getElementById(property1 + 'Hex').value = "#3174AF";
				document.getElementById(property1).value = "#3174AF";
			} else if(property1 == 'linkHoverColor') {
				document.getElementById(property1 + 'Hex').value = "#265a87";
				document.getElementById(property1).value = "#265a87";
			} else if(property1 == 'resultLabelColor') {
				document.getElementById(property1 + 'Hex').value = "#44484a";
				document.getElementById(property1).value = "#44484a";
			} else if(property1 == 'resultValueColor') {
				document.getElementById(property1 + 'Hex').value = "#6B6B6B";
				document.getElementById(property1).value = "#6B6B6B";
			} else if(property1 == 'headerBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f1f1f1";
				document.getElementById(property1).value = "#f1f1f1";
			} else if(property1 == 'headerForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#303030";
				document.getElementById(property1).value = "#303030";
			} else if(property1 == 'breadcrumbsBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f5f5f5";
				document.getElementById(property1).value = "#f5f5f5";
			} else if(property1 == 'breadcrumbsForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#6B6B6B";
				document.getElementById(property1).value = "#6B6B6B";
			} else if(property1 == 'searchToolsBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f5f5f5";
				document.getElementById(property1).value = "#f5f5f5";
			} else if(property1 == 'searchToolsBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#e3e3e3";
				document.getElementById(property1).value = "#e3e3e3";
			} else if(property1 == 'searchToolsForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#6B6B6B";
				document.getElementById(property1).value = "#6B6B6B";
			} else if(property1 == 'footerBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f1f1f1";
				document.getElementById(property1).value = "#f1f1f1";
			} else if(property1 == 'footerForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#303030";
				document.getElementById(property1).value = "#303030";
			} else if(property1 == 'primaryBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#0a7589";
				document.getElementById(property1).value = "#0a7589";
			} else if(property1 == 'primaryForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'secondaryBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#de9d03";
				document.getElementById(property1).value = "#de9d03";
			} else if(property1 == 'secondaryForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#303030";
				document.getElementById(property1).value = "#303030";
			} else if(property1 == 'tertiaryBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#de1f0b";
				document.getElementById(property1).value = "#de1f0b";
			} else if(property1 == 'tertiaryForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#000000";
				document.getElementById(property1).value = "#000000";
			} else if(property1 == 'menubarBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f1f1f1";
				document.getElementById(property1).value = "#f1f1f1";
			} else if(property1 == 'menubarForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#303030";
				document.getElementById(property1).value = "#303030";
			} else if(property1 == 'menubarHighlightBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f1f1f1";
				document.getElementById(property1).value = "#f1f1f1";
			} else if(property1 == 'menubarHighlightForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#265a87";
				document.getElementById(property1).value = "#265a87";
			} else if(property1 == 'menuDropdownBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ededed";
				document.getElementById(property1).value = "#ededed";
			} else if(property1 == 'menuDropdownForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#404040";
				document.getElementById(property1).value = "#404040";
			} else if(property1 == 'modalDialogBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'modalDialogForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333";
			} else if(property1 == 'modalDialogHeaderFooterBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'modalDialogHeaderFooterForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333";
			} else if(property1 == 'modalDialogHeaderFooterBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#e5e5e5";
				document.getElementById(property1).value = "#e5e5e5";
			} else if(property1 == 'browseCategoryPanelColor') {
				document.getElementById(property1 + 'Hex').value = "#d7dce3";
				document.getElementById(property1).value = "#d7dce3";
			} else if(property1 == 'selectedBrowseCategoryBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#0087AB";
				document.getElementById(property1).value = "#0087AB";
			} else if(property1 == 'selectedBrowseCategoryForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'selectedBrowseCategoryBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#0087AB";
				document.getElementById(property1).value = "#0087AB";
			} else if(property1 == 'deselectedBrowseCategoryBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#0087AB";
				document.getElementById(property1).value = "#0087AB";
			} else if(property1 == 'deselectedBrowseCategoryForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'deselectedBrowseCategoryBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#0087AB";
				document.getElementById(property1).value = "#0087AB";
			} else if(property1 == 'badgeBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#666666";
				document.getElementById(property1).value = "#666666";
			} else if(property1 == 'badgeForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff";
			} else if(property1 == 'closedPanelBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#e7e7e7";
				document.getElementById(property1).value = "#e7e7e7";
			} else if(property1 == 'closedPanelForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333"
			} else if(property1 == 'openPanelBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333"
			} else if(property1 == 'openPanelForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'panelBodyBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'panelBodyForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#404040";
				document.getElementById(property1).value = "#404040"
			} else if(property1 == 'defaultButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'defaultButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333"
			} else if(property1 == 'defaultButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#cccccc";
				document.getElementById(property1).value = "#cccccc"
			} else if(property1 == 'defaultButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#eeeeee";
				document.getElementById(property1).value = "#eeeeee"
			} else if(property1 == 'defaultButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#333333";
				document.getElementById(property1).value = "#333333"
			} else if(property1 == 'defaultButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#cccccc";
				document.getElementById(property1).value = "#cccccc"
			} else if(property1 == 'primaryButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'primaryButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'primaryButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'primaryButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'primaryButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'primaryButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'actionButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'actionButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'actionButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'actionButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'actionButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'actionButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'editionsButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f8f9fa";
				document.getElementById(property1).value = "#f8f9fa"
			} else if(property1 == 'editionsButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#212529";
				document.getElementById(property1).value = "#212529"
			} else if(property1 == 'editionsButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#999999";
				document.getElementById(property1).value = "#999999"
			} else if(property1 == 'editionsButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'editionsButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'editionsButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#1b6ec2";
				document.getElementById(property1).value = "#1b6ec2"
			} else if(property1 == 'toolsButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#747474";
				document.getElementById(property1).value = "#747474"
			} else if(property1 == 'toolsButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'toolsButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#636363";
				document.getElementById(property1).value = "#636363"
			} else if(property1 == 'toolsButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#636363";
				document.getElementById(property1).value = "#636363"
			} else if(property1 == 'toolsButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'toolsButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#636363";
				document.getElementById(property1).value = "#636363"
			} else if(property1 == 'infoButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#8cd2e7";
				document.getElementById(property1).value = "#8cd2e7"
			} else if(property1 == 'infoButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#000000";
				document.getElementById(property1).value = "#000000"
			} else if(property1 == 'infoButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#999999";
				document.getElementById(property1).value = "#999999"
			} else if(property1 == 'infoButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'infoButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#217e9b";
				document.getElementById(property1).value = "#217e9b"
			} else if(property1 == 'infoButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#217e9b";
				document.getElementById(property1).value = "#217e9b"
			} else if(property1 == 'warningButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#f4d03f";
				document.getElementById(property1).value = "#f4d03f"
			} else if(property1 == 'warningButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#000000";
				document.getElementById(property1).value = "#000000"
			} else if(property1 == 'warningButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#999999";
				document.getElementById(property1).value = "#999999"
			} else if(property1 == 'warningButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'warningButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#8d6708";
				document.getElementById(property1).value = "#8d6708"
			} else if(property1 == 'warningButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#8d6708";
				document.getElementById(property1).value = "#8d6708"
			} else if(property1 == 'dangerButtonBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#D50000";
				document.getElementById(property1).value = "#D50000"
			} else if(property1 == 'dangerButtonForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'dangerButtonBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#999999";
				document.getElementById(property1).value = "#999999"
			} else if(property1 == 'dangerButtonHoverBackgroundColor') {
				document.getElementById(property1 + 'Hex').value = "#ffffff";
				document.getElementById(property1).value = "#ffffff"
			} else if(property1 == 'dangerButtonHoverForegroundColor') {
				document.getElementById(property1 + 'Hex').value = "#D50000";
				document.getElementById(property1).value = "#D50000"
			} else if(property1 == 'dangerButtonHoverBorderColor') {
				document.getElementById(property1 + 'Hex').value = "#D50000";
				document.getElementById(property1).value = "#D50000"
			};
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
		getLuminanceForColor: function(color){
			var r = AspenDiscovery.Admin.getLuminanceComponent(color, 1, 2);
			var g = AspenDiscovery.Admin.getLuminanceComponent(color, 3, 2);
			var b = AspenDiscovery.Admin.getLuminanceComponent(color, 5, 2);
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		},
		getLuminanceComponent: function(color, start, length){
			var component = parseInt(color.substring(start, start + length), 16) / 255;
			if (component <= 0.03928) {
				return component / 12.92;
			} else {
				return Math.pow((component + 0.055) / 1.055, 2.4);
			}
		},

		updateMaterialsRequestFields: function(){
			var materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
			$("#propertyRowallowDeletingILSRequests").hide();
			if (materialRequestType === "0" || materialRequestType === "2"){
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide();
				if (materialRequestType === "2"){
					$("#propertyRowallowDeletingILSRequests").show();
				}
			}else if (materialRequestType === "1"){
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").show();
				$("#propertyRowmaxOpenRequests").show();
				$("#propertyRowmaterialsRequestDaysToPreserve").show();
				$("#propertyRowmaterialsRequestFieldsToDisplay").show();
				$("#propertyRowmaterialsRequestFormats").show();
				$("#propertyRowmaterialsRequestFormFields").show()
			}else if (materialRequestType === "3"){
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
		updateDonationFields: function(){
			var donationsEnabled = $("#enableDonations");
			var donationsEnabledValue = $("#enableDonations:checked").val()
			if(donationsEnabledValue == 1) {
				$("#propertyRowallowDonationsToBranch").show();
				$("#propertyRowallowDonationEarmark").show();
				$("#propertyRowallowDonationDedication").show();
				$("#propertyRowdonationValues").show();
				$("#propertyRowdonationContent").show();
			}else{
				$("#propertyRowallowDonationsToBranch").hide();
				$("#propertyRowallowDonationEarmark").hide();
				$("#propertyRowallowDonationDedication").hide();
				$("#propertyRowdonationValues").hide();
				$("#propertyRowdonationContent").hide();
			}

			return false;
		},
		validateCompare: function() {
			var selectedObjects = $('.selectedObject:checked');
			if (selectedObjects.length === 2){
				return true;
			}else{
				AspenDiscovery.showMessage("Error", "Please select only two objects to compare");
				return false;
			}
		},
		showBatchUpdateFieldForm: function(module, toolName, batchUpdateScope) {
			var selectedObjects = $('.selectedObject:checked');
			if (batchUpdateScope === 'all' || selectedObjects.length >= 1){
				var url = Globals.path + "/Admin/AJAX";
				var params =  {
					method : 'getBatchUpdateFieldForm',
					moduleName : module,
					toolName: toolName,
					batchUpdateScope: batchUpdateScope
				};
				$.getJSON(url, params,
					function(data) {
						if (data.success) {
							AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
						} else {
							$("#releaseNotes").html("Error + " + data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
				return false;
			}else{
				AspenDiscovery.showMessage("Error", "Please select at least one object to update");
				return false;
			}
		},
		processBatchUpdateFieldForm: function(module, toolName, batchUpdateScope){
			var selectedObjects = $('.selectedObject:checked');
			if (batchUpdateScope === 'all' || selectedObjects.length >= 1){
				var url = Globals.path + "/Admin/AJAX";
				var selectedField = $('#fieldSelector').val();
				var selectedFieldControl = $('#' + selectedField);
				var newValue;
				if (selectedFieldControl.prop("type") === undefined){
					selectedFieldControl = $('#' + selectedField + "Select");
				}
				if (selectedFieldControl.prop("type") === 'checkbox'){
					newValue = selectedFieldControl.prop("checked") ? 1 : 0;
				}else {
					newValue = selectedFieldControl.val();
				}
				var params =  {
					method : 'doBatchUpdateField',
					moduleName : module,
					toolName: toolName,
					batchUpdateScope: batchUpdateScope,
					selectedField: selectedField,
					newValue: newValue
				};
				selectedObjects.each(function(){
					params[$(this).prop('name')] = 'on';
				});
				$.getJSON(url, params,
					function(data) {
						if (data.success) {
							AspenDiscovery.showMessage(data.title, data.message, true, true);
						} else {
							AspenDiscovery.showMessage(data.title, data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
				return false;
			}else{
				AspenDiscovery.showMessage("Error", "Please select at least one object to update");
				return false;
			}
		},
		addFilterRow: function(module, toolName) {
			var url = Globals.path + "/Admin/AJAX";
			var params =  {
				method : 'getFilterOptions',
				moduleName : module,
				toolName: toolName
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		getNewFilterRow: function(module, toolName) {
			var url = Globals.path + "/Admin/AJAX";
			var selectedFilter = $("#fieldSelector").val();
			var params =  {
				method : 'getNewFilterRow',
				moduleName : module,
				toolName: toolName,
				selectedFilter: selectedFilter
			};
			$.getJSON(url, params,
				function(data) {
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
		displayReleaseNotes: function() {
			var url = Globals.path + "/Admin/AJAX";
			var selectedNotes = $('#releaseSelector').val();
			var params =  {
				method : 'getReleaseNotes',
				release : selectedNotes
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						$("#releaseVersion").html(data.release);
						$("#releaseNotes").html(data.releaseNotes);
						if (data.actionItems === ''){
							$("#actionItemsSection").hide();
						}else{
							$("#actionItemsSection").show();
							$("#actionItems").html(data.actionItems);
						}
						if (data.testingSuggestions === ''){
							$("#testingSection").hide();
						}else{
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
			}else if (selectedSource === 'CourseReserve') {
				$("#propertyRowsearchTerm").hide();
				$("#propertyRowdefaultFilter").hide();
				$("#propertyRowdefaultSort").hide();
				$("#propertyRowsourceListId").hide();
				$("#propertyRowsourceCourseReserveId").show();
			}else{
				$("#propertyRowsearchTerm").show();
				$("#propertyRowdefaultFilter").show();
				$("#propertyRowdefaultSort").show();
				$("#propertyRowsourceListId").hide();
				$("#propertyRowsourceCourseReserveId").hide();
			}
		},
		updateIndexingProfileFields: function () {
			var audienceType = $('#determineAudienceBySelect').val();
			if (audienceType === '3') {
				$("#propertyRowaudienceSubfield").show();
			}else{
				$("#propertyRowaudienceSubfield").hide();
			}
			var formatSource = $('#formatSourceSelect').val();
			if (formatSource === 'specified'){
				$("#propertyRowspecifiedFormat").show();
				$("#propertyRowspecifiedFormatCategory").show();
				$("#propertyRowspecifiedFormatBoost").show();
				$("#propertyRowcheckRecordForLargePrint").hide();
				$("#propertyRowformatMap").hide();
			}else{
				$("#propertyRowspecifiedFormat").hide();
				$("#propertyRowspecifiedFormatCategory").hide();
				$("#propertyRowspecifiedFormatBoost").hide();
				$("#propertyRowformatMap").show();
				$("#propertyRowcheckRecordForLargePrint").show();
			}
		},
		updateLayoutSettingsFields: function () {
			var useHomeLink = $('#useHomeLinkSelect').val();
			if ((useHomeLink === '0') || useHomeLink === '2') {
				$("#propertyRowshowBookIcon").show();
				$("#propertyRowhomeLinkText").hide();
			}else{
				$("#propertyRowshowBookIcon").hide();
				$("#propertyRowhomeLinkText").show();
			}
		},
		updateBrowseCategoryFields: function () {
			var sharingType = $('#sharingSelect').val();
			if (sharingType === 'library') {
				$("#propertyRowlibraryId").show();
			}else{
				$("#propertyRowlibraryId").hide();
			}
		},
		showCreateRoleForm: function(){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getCreateRoleForm', true);
			return false;
		},
		createRole: function () {
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'createRole',
				roleName: $('#roleName').val(),
				description: $('#description').val()
			}
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						window.location.href = Globals.path + '/Admin/Permissions?roleId=' + data.roleId;
					} else {
						AspenDiscovery.showMessage('Error', data.message, false);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},

		deleteRole: function(roleId){
			var url = Globals.path + '/Admin/AJAX';
			var params = {
				method: 'deleteRole',
				roleId: $("#roleId").find("option:selected").val()
			}
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						window.location.href = Globals.path + '/Admin/Permissions';
					} else {
						AspenDiscovery.showMessage('Error', data.message, false);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},

		updateMakeRowAccordion: function() {
			var makeRowAccordion = $('#makeAccordion');
			$(makeRowAccordion).click(function() {
				if(makeRowAccordion.is(":checked")){
					$("#rowTitle").attr('required',"true");
				}else{
					$("#rowTitle").removeAttr('required');
				}
			});
		},

		updateMakeCellAccordion: function() {
			var makeCellAccordion = $('#makeCellAccordion');
			$(makeCellAccordion).click(function() {
				if(makeCellAccordion.is(":checked")){
					$("#title").attr('required',"true");
				}else{
					$("#title").removeAttr('required');
				}
			});
		},

		deleteNYTList: function(id){
			var listId = id;
			if (confirm("Are you sure you want to delete this list?")){
				$.getJSON(Globals.path + '/Admin/AJAX?method=deleteNYTList&id=' + listId, function (data) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				})
			}
			return false;
		},

		updateLibraryLinksFields: function () {
			var requireLogin = $('#showToLoggedInUsersOnly');
			if(requireLogin.is(":checked")) {
				$("#propertyRowallowAccess").show();
			} else {
				$("#propertyRowallowAccess").hide();
			}

			$(requireLogin).click(function() {
				if(requireLogin.is(":checked")){
					$("#propertyRowallowAccess").show();
				}else{
					$("#propertyRowallowAccess").hide();
				}
			});
		},

		updateDonationsSettingFields: function () {
			var allowEarmarks = $('#allowDonationEarmark');
			if(allowEarmarks.is(":checked")) {
				$("#propertyRowdonationEarmarks").show();
			} else {
				$("#propertyRowdonationEarmarks").hide();
			}
			$(allowEarmarks).click(function() {
				if(allowEarmarks.is(":checked")){
					$("#propertyRowdonationEarmarks").show();
				}else{
					$("#propertyRowdonationEarmarks").hide();
				}
			});

			var allowDedications = $('#allowDonationDedication');
			if(allowDedications.is(":checked")) {
				$("#propertyRowdonationDedicationTypes").show();
			} else {
				$("#propertyRowdonationDedicationTypes").hide();
			}

			$(allowDedications).click(function() {
				if(allowDedications.is(":checked")){
					$("#propertyRowdonationDedicationTypes").show();
				}else{
					$("#propertyRowdonationDedicationTypes").hide();
				}
			});
		},
	};
}(AspenDiscovery.Admin || {}));