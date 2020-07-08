AspenDiscovery.WebBuilder = (function () {
	return {
		editors: [],

		getPortalCellValuesForSource: function () {
			let sourceType = $("#sourceTypeSelect").val();
			if (sourceType === 'markdown') {
				$('#propertyRowmarkdown').show();
				$("#propertyRowsourceId").hide();
			}else{
				$('#propertyRowmarkdown').hide();
				$("#propertyRowsourceId").show();
				let url = Globals.path + '/WebBuilder/AJAX?method=getPortalCellValuesForSource&sourceType=' + sourceType;
				$.getJSON(url, function(data){
					if (data.success === true){
						let sourceIdSelect = $("#sourceIdSelect" );
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

		},

		getUploadImageForm: function(editorName) {
			let url = Globals.path + "/WebBuilder/AJAX" ;
			let params = {
				method: 'getUploadImageForm',
				editorName: editorName
			};
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
		},

		deleteCell: function (id) {
			if (!confirm("Are you sure you want to delete this cell?")){
				return false;
			}
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'deleteCell',
				id: id
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$("#portal-cell-" + id).hide();
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		deleteRow: function (id) {
			if (!confirm("Are you sure you want to delete this row?")){
				return false;
			}
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'deleteRow',
				id: id
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$("#portalRow" + id).hide();
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		addRow: function(pageId) {
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'addRow',
				pageId: pageId
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$('#portal-rows').append(data.newRow);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		addCell: function(rowId) {
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'addCell',
				rowId: rowId
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$('#portal-row-cells-' + rowId).append(data.newCell);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		moveRow: function(rowId, direction) {
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'moveRow',
				rowId: rowId,
				direction: direction
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					let row1 = $("#portalRow" + rowId);
					let row2 = $("#portalRow" + data.swappedWithId);
					if (direction === 'up'){
						row2.before(row1);
					}else{
						row1.before(row2);
					}
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		showEditCellForm: function(cellId) {
			let url = Globals.path + '/WebBuilder/AJAX';
			let params = {
				method: 'getEditCellForm',
				cellId: cellId
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		showImageInPopup: function(title, imageId){
			// buttonsElementId is optional
			let modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				AspenDiscovery.closeLightbox(function(){AspenDiscovery.showElementInPopup(title, elementId)});
			}else{
				if (title === ''){
					title = '&nbsp;';
				}
				$(".modal-title").html(title);
				$(".modal-body").html('<img src="/WebBuilder/ViewImage?id=' + imageId + '" class="img-responsive">');
				$('.modal-buttons').html('');

				modalDialog.addClass('image-popup')
				modalDialog.modal('show');
				return false;
			}
		},
	};
}(AspenDiscovery.WebBuilder || {}));