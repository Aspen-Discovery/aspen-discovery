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
		updateSpotlightSearchForSource: function () {
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
		}
	};
}(AspenDiscovery.Admin || {}));
