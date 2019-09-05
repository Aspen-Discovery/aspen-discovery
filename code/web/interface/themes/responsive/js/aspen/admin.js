AspenDiscovery.Admin = (function(){
	return {
		showSierraExportNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getSierraExportNotes&id=" + id, true);
			return false;
		},
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
		}
	};
}(AspenDiscovery.Admin || {}));
