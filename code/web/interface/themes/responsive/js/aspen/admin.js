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
		checkContrast: function (property1, property2,oneWay) {
			if (oneWay === undefined){
				oneWay = false;
			}
			var color1 = $('#' + property1).val();
			var color2 = $('#' + property2).val();
			if (color1.length === 7 && color2.length === 7){
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
				}else if (contrastRatio < 4.5) {
					contrastSpan1.removeClass("alert-danger");
					contrastSpan2.removeClass("alert-danger");
					contrastSpan1.addClass("alert-warning");
					contrastSpan2.addClass("alert-warning");
					contrastSpan1.removeClass("alert-success");
					contrastSpan2.removeClass("alert-success");
				}else{
					contrastSpan1.removeClass("alert-danger");
					contrastSpan2.removeClass("alert-danger");
					contrastSpan1.removeClass("alert-warning");
					contrastSpan2.removeClass("alert-warning");
					contrastSpan1.addClass("alert-success");
					contrastSpan2.addClass("alert-success");
				}
			}else{
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
			if (materialRequestType === "0" || materialRequestType === "2"){
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide()
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
						$("#releaseNotes").html(data.releaseNotes);
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
			}else{
				$("#propertyRowsearchTerm").show();
				$("#propertyRowdefaultFilter").show();
				$("#propertyRowdefaultSort").show();
				$("#propertyRowsourceListId").hide();
			}
		},
		updateIndexingProfileFields: function () {
			var audienceType = $('#determineAudienceBySelect').val();
			if (audienceType === '3') {
				$("#propertyRowaudienceSubfield").show();
			}else{
				$("#propertyRowaudienceSubfield").hide();
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
		}
	};
}(AspenDiscovery.Admin || {}));
