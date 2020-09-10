AspenDiscovery.Admin = (function(){
	return {
		showRecordGroupingNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getRecordGroupingNotes&id=" + id, true);
			return false;
		},
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
			let fontElement = $("#" + fontSelector);
			let fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		},
		checkContrast: function (property1, property2,oneWay=false){
			let color1 = $('#' + property1).val();
			let color2 = $('#' + property2).val();
			if (color1.length === 7 && color2.length === 7){
				let luminance1 = AspenDiscovery.Admin.getLuminanceForColor(color1);
				let luminance2 = AspenDiscovery.Admin.getLuminanceForColor(color2);
				let contrastRatio;
				if (luminance1 > luminance2) {
					contrastRatio = ((luminance1 + 0.05) / (luminance2 + 0.05));
				} else {
					contrastRatio = ((luminance2 + 0.05) / (luminance1 + 0.05));
				}
				let contrastSpan1 = $("#contrast_" + property1);
				let contrastSpan2 = $("#contrast_" + property2);
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
			let r = AspenDiscovery.Admin.getLuminanceComponent(color, 1, 2);
			let g = AspenDiscovery.Admin.getLuminanceComponent(color, 3, 2);
			let b = AspenDiscovery.Admin.getLuminanceComponent(color, 5, 2);
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		},
		getLuminanceComponent: function(color, start, length){
			let component = parseInt(color.substring(start, start + length), 16) / 255;
			if (component <= 0.03928) {
				return component / 12.92;
			} else {
				return Math.pow((component + 0.055) / 1.055, 2.4);
			}
		},

		updateMaterialsRequestFields: function(){
			let materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
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
			let selectedObjects = $('.selectedObject:checked');
			if (selectedObjects.length === 2){
				return true;
			}else{
				AspenDiscovery.showMessage("Error", "Please select only two objects to compare");
				return false;
			}
		},
		displayReleaseNotes: function() {
			let url = Globals.path + "/Admin/AJAX";
			let selectedNotes = $('#releaseSelector').val();
			let params =  {
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
			let selectedSource = $('#sourceSelect').val();
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
			let audienceType = $('#determineAudienceBySelect').val();
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
			let url = Globals.path + '/Admin/AJAX';
			let params = {
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
			let url = Globals.path + '/Admin/AJAX';
			let params = {
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
