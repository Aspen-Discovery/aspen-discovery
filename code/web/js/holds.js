function updateSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var newLocation = $('#withSelectedLocation').find(':selected').val();
	var url = path + '/MyResearch/Holds?multiAction=updateSelected&location=' + newLocation + "&" + selectedTitles;
	var queryParams = getQuerystringParameters();
	if ($.inArray('section', queryParams)){
		url += '&section=' + queryParams['section'];
	}
	window.location = url;
	return false;
}
function cancelSelectedHolds(){
	var selectedTitles = getSelectedTitles(false);
	if (selectedTitles.length == 0){
		alert('Please select one or more titles to cancel.');
		return false;
	}
	var url = path + '/MyResearch/Holds?multiAction=cancelSelected&' + selectedTitles;
	var queryParams = getQuerystringParameters();
	if ($.inArray('section', queryParams)){
		url += '&section=' + queryParams['section'];
	}
	window.location = url;
	return false;
}
function freezeSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var suspendDate = '';
	//Check to se whether or not we are using a suspend date.
	if ($('#suspendDateTop').length){
		if ($('#suspendDateTop').val().length > 0){
			var suspendDate = $('#suspendDateTop').val();
		}else{
			var suspendDate = $('#suspendDateBottom').val();
		}

		if (suspendDate.length == 0){
			alert("Please select the date when the hold should be reactivated.");
			return false;
		}
		var url = path + '/MyResearch/Holds?multiAction=freezeSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
		var queryParams = getQuerystringParameters();
		if ($.inArray('section', queryParams)){
			url += '&section=' + queryParams['section'];
		}
		window.location = url;
	}else{
		var url = path + '/MyResearch/Holds?multiAction=freezeSelected&' + selectedTitles + '&suspendDate=' + suspendDate;
		var queryParams = getQuerystringParameters();
		if ($.inArray('section', queryParams)){
			url += '&section=' + queryParams['section'];
		}
		window.location = url;
	}
	return false;
}
function thawSelectedHolds(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	var url = path + '/MyResearch/Holds?multiAction=thawSelected&' + selectedTitles;
	var queryParams = getQuerystringParameters();
	if ($.inArray('section', queryParams)){
		url += '&section=' + queryParams['section'];
	}
	window.location = url;
	return false;
}
function getSelectedTitles(promptForSelectAll){
	if (promptForSelectAll == undefined){
		promptForSelectAll = true;
	}
	var selectedTitles = $("input.titleSelect:checked ").map(function() {
		return $(this).attr('name') + "=" + $(this).val();
	}).get().join("&");
	if (selectedTitles.length == 0 && promptForSelectAll){
		var ret = confirm('You have not selected any items, process all items?');
		if (ret == true){
			$("input.titleSelect").attr('checked', 'checked');
			selectedTitles = $("input.titleSelect").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
		}
	}
	return selectedTitles;
}
function renewSelectedTitles(){
	var selectedTitles = getSelectedTitles();
	if (selectedTitles.length == 0){
		return false;
	}
	$('#renewForm').submit();
	return false;
}