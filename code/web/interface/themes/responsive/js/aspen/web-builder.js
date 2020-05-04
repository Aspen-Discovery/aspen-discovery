AspenDiscovery.WebBuilder = (function () {
	return {
		editors: [],

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
		},

		getUploadImageForm: function(editorName) {
			let url = Globals.path + "/WebBuilder/AJAX" ;
			let params = {
				method: 'getUploadImageForm',
				editorName: editorName
			}
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage('An error occurred', data.message)
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		doImageUpload: function(){
			let url = Globals.path + '/WebBuilder/AJAX?method=uploadImage';
			let uploadCoverData = new FormData($("#uploadImageForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					let editorName = $('#editorName').val();
					let cm = AspenDiscovery.WebBuilder.editors[editorName].codemirror;
					let output = '';
					let selectedText = cm.getSelection();
					let text = selectedText || 'placeholder';

					output = '![' + data.title + '](' + data.imageUrl + ')';
					cm.replaceSelection(output);

					AspenDiscovery.closeLightbox();
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		}
	};
}(AspenDiscovery.WebBuilder || {}));