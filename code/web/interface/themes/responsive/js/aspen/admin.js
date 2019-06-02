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
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getExtractNotes&source=overdrive&id=" + id + "&source=" + source, true);
			return false;
		},
		loadGoogleFontPreview: function (fontSelector) {
			let fontElement = $("#" + fontSelector);
			let fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		}
	};
}(AspenDiscovery.Admin || {}));
