AspenDiscovery.WebBuilder = function () {
	// noinspection JSUnusedGlobalSymbols
	return {
		editors: [],

		getPortalCellValuesForSource: function () {
			var portalCellId = $("#id").val();
			var sourceType = $("#sourceTypeSelect").val();
			if (sourceType === 'markdown') {
				$('#propertyRowmarkdown').show();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowpdfView').hide();
			}else if (sourceType === 'youtube_video' || sourceType === 'vimeo_video') {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').show();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowpdfView').hide();
			}else if (sourceType === 'iframe') {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').show();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').show();
				$('#propertyRowimageURL').hide();
				$('#propertyRowpdfView').hide();
			}else if (sourceType === 'hours_locations') {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowpdfView').hide();
			}else {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").show();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowpdfView').hide();
				if (sourceType === 'image') {
					$('#propertyRowimageURL').show();
				} else if (sourceType === 'pdf') {
					$('#propertyRowpdfView').show();
				}

				var url = Globals.path + '/WebBuilder/AJAX?method=getPortalCellValuesForSource&portalCellId=' + portalCellId + '&sourceType=' + sourceType;
				$.getJSON(url, function(data){
					if (data.success === true){
						var sourceIdSelect = $("#sourceIdSelect" );
						sourceIdSelect.find('option').remove();
						var optionValues = data.values;
						for (var key in optionValues) {
							if (data.selected === key){
								sourceIdSelect.append('<option value="' + key + '" selected>' + optionValues[key] + '</option>')
							}else{
								sourceIdSelect.append('<option value="' + key + '">' + optionValues[key] + '</option>')
							}
						}
					}else{
						AspenDiscovery.showMessage('Sorry', data.message);
					}
				});
			}

		},

		getUploadImageForm: function(editorName) {
			var url = Globals.path + "/WebBuilder/AJAX" ;
			var params = {
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
			var url = Globals.path + '/WebBuilder/AJAX?method=uploadImage';
			var uploadCoverData = new FormData($("#uploadImageForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					var editorName = $('#editorName').val();
					var cm = AspenDiscovery.WebBuilder.editors[editorName].codemirror;
					var output = '';
					var selectedText = cm.getSelection();
					var text = selectedText || 'placeholder';

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
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
				method: 'deleteCell',
				id: id
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$('#portalRow' + data.rowId).replaceWith(data.newRow);
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
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
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
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
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
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
				method: 'addCell',
				rowId: rowId
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					$('#portalRow' + rowId).replaceWith(data.newRow);
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		moveRow: function(rowId, direction) {
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
				method: 'moveRow',
				rowId: rowId,
				direction: direction
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					var row1 = $("#portalRow" + rowId);
					var row2 = $("#portalRow" + data.swappedWithId);
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

		moveCell: function(cellId, direction) {
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
				method: 'moveCell',
				cellId: cellId,
				direction: direction
			};
			$.getJSON(url, params, function (data) {
				if (data.success){
					var cell1 = $("#portal-cell-" + cellId);
					var cell2 = $("#portal-cell-" + data.swappedWithId);
					if (direction === 'left'){
						cell2.before(cell1);
					}else{
						cell1.before(cell2);
					}
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		showEditCellForm: function(cellId) {
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
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
			var modalDialog = $("#modalDialog");
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

		updateWebBuilderFields: function () {
			var requireLogin = $('#requireLogin');
			if(requireLogin.is(":checked")) {
				$("#propertyRowallowAccess").show();
				$("#propertyRowrequireLoginUnlessInLibrary").show();
			} else {
				$("#propertyRowallowAccess").hide();
				$("#propertyRowrequireLoginUnlessInLibrary").hide();
			}

			$(requireLogin).click(function() {
				if(requireLogin.is(":checked")){
					$("#propertyRowallowAccess").show();
					$("#propertyRowrequireLoginUnlessInLibrary").show();
				}else{
					$("#propertyRowallowAccess").hide();
					$("#propertyRowrequireLoginUnlessInLibrary").hide();
				}
			});
		},

		getWebResource:function (id) {
			var url = Globals.path + "/WebBuilder/AJAX";
			var params = {
				method: "getWebResource",
				resourceId: id
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if(data.requireLogin) {
					if(Globals.loggedIn || data.inLibrary) {
						var params = {
							method: "trackWebResourceUsage",
							id: id,
							authType: Globals.loggedIn ? "user" : "library"
						};
						$.getJSON(url, params, function(usage){
							if(data.openInNewTab) {
								window.open(data.url, '_blank');
							} else {
								location.assign(data.url);
							}
						})
					} else {
						AspenDiscovery.Account.ajaxLogin(null, function(){
							return AspenDiscovery.Account.getWebResource(id);
						}, false);
					}
				} else {
					var params = {
						method: "trackWebResourceUsage",
						id: id,
						authType: "none"
					};
					$.getJSON(url, params, function(usage){
						if(data.openInNewTab) {
							window.open(data.url, '_blank');
						} else {
							location.assign(data.url);
						}
					})
				}
			}).fail(AspenDiscovery.ajaxFail);

			return false;
		}
	};
}(AspenDiscovery.WebBuilder || {});