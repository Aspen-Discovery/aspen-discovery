VuFind.Admin = (function(){
	return {
		showSierraExportNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getSierraExportNotes&id=" + id, true);
			return false;
		},
		showRecordGroupingNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getRecordGroupingNotes&id=" + id, true);
			return false;
		},
		showReindexNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
			return false;
		},
		toggleReindexProcessInfo: function (id){
			$("#reindexEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},
		showReindexProcessNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id, true);
			return false;
		},

		showCronNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
			return false;
		},
		showCronProcessNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
			return false;
		},
		toggleCronProcessInfo: function (id){
			$("#cronEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},

		showExtractNotes: function (id, source){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getExtractNotes&source=overdrive&id=" + id + "&source=" + source, true);
			return false;
		},
		loadGoogleFontPreview: function (fontSelector) {
			let fontElement = $("#" + fontSelector);
			let fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		}
	};
}(VuFind.Admin || {}));
