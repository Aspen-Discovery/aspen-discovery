var suggestionScroller;
function getSuggestions(){
	suggestionScroller = new TitleScroller('titleScrollerSuggestion', 'Suggestion', 'suggestionList');
	
	var url = path + "/MyAccount/AJAX";
	var params = "method=GetSuggestions";
	var fullUrl = url + "?" + params;
	suggestionScroller.loadTitlesFrom(fullUrl);
}


function resetPinReset(){
	var barcode = $('#card_number').val();
	if (barcode.length == 0){
		alert("Please enter your library card number");
	}else{
		var url = path + '/MyAccount/AJAX?method=requestPinReset&barcode=' + barcode;
		$.getJSON(url, function(data){
			if (data.error == false){
				alert(data.message);
				if (data.result == true){
					VuFind.closeLightbox();
				}
			}else{
				alert("There was an error requesting your pin reset information.  Please contact the library for additional information.");
			}
		});
	}
	return false;
}
