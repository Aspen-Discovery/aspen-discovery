AspenDiscovery.WebBuilder = (function () {
	return {
		getPortalCellValuesForSource: function (cellId) {
			let sourceType = $("#cellssourceType_" + cellId).val();
			let url = Globals.path + '/WebBuilder/AJAX?method=getPortalCellValuesForSource&sourceType=' + sourceType;
			$.getJSON(url, function(data){
				if (data.success === true){
					let sourceIdSelect = $("#cellssourceId_" + cellId);
					sourceIdSelect.find('option').remove();
					let optionValues = data.values;
					for (let key in optionValues) {
						sourceIdSelect.append('<option value="' + key + '">' + optionValues[key] + '</option>')
					}
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			});
		}
	};
}(AspenDiscovery.WebBuilder || {}));