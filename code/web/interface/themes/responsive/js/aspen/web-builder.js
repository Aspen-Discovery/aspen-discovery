AspenDiscovery.WebBuilder = function () {
	// noinspection JSUnusedGlobalSymbols
	return {
		editors: [],
		// Track placard views (whenever shown to a user, regardless of whether they interact with it)
		trackPlacardView: function(placardId) {
			const url = Globals.path + '/WebBuilder/AJAX';
			const params = {
				method: 'trackPlacardUsage',
				id: placardId,
				operation: 'view'
			};
			$.getJSON(url, params);
		},

		// Track placard clicks
		trackPlacardClick: function(placardId) {
			const url = Globals.path + '/WebBuilder/AJAX';
			const params = {
				method: 'trackPlacardUsage',
				id: placardId,
				operation: 'click'
			};
			$.getJSON(url, params);
		},
		saveLinkedObjCallback: function() {},

		getPortalCellValuesForSource: function () {
			var portalCellId = $("#id").val();
			var sourceType = $("#sourceTypeSelect").val();
			const $staticLocationSelector = $('#propertyRowstaticLocationId');
			$($staticLocationSelector).hide();
			if (sourceType === 'markdown') {
				$('#propertyRowmarkdown').show();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowimgAction').hide();
				$('#propertyRowimgAlt').hide();
				$('#propertyRowpdfView').hide();
				$('#propertyRowcustomImage').hide();
				$('#propertyRowhideDescription').hide();
			}else if (sourceType === 'youtube_video' || sourceType === 'vimeo_video') {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').show();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowimgAction').hide();
				$('#propertyRowimgAlt').hide();
				$('#propertyRowpdfView').hide();
				$('#propertyRowcustomImage').hide();
				$('#propertyRowhideDescription').hide();
			}else if (sourceType === 'iframe') {
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').show();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').show();
				$('#propertyRowimageURL').hide();
				$('#propertyRowimgAction').hide();
				$('#propertyRowimgAlt').hide();
				$('#propertyRowpdfView').hide();
				$('#propertyRowcustomImage').hide();
				$('#propertyRowhideDescription').hide();
			}else if (sourceType === 'hours_locations') {
				$($staticLocationSelector).show();
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").hide();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowimgAction').hide();
				$('#propertyRowimgAlt').hide();
				$('#propertyRowpdfView').hide();
				$('#propertyRowcustomImage').hide();
				$('#propertyRowhideDescription').hide();
			}else {
				$($staticLocationSelector).hide();
				$('#propertyRowmarkdown').hide();
				$('#propertyRowsourceInfo').hide();
				$("#propertyRowsourceId").show();
				$('#propertyRowframeHeight').hide();
				$('#propertyRowimageURL').hide();
				$('#propertyRowimgAction').hide();
				$('#propertyRowimgAlt').hide();
				$('#propertyRowpdfView').hide();
				$('#propertyRowcustomImage').hide();
				$('#propertyRowhideDescription').hide();
				if (sourceType === 'custom_web_resource_page') {
					$('#propertyRowcustomImage').show();
					$('#propertyRowhideDescription').show();
				}
				if (sourceType === 'image') {
					$('#propertyRowimgAction').show();
					$('#propertyRowimgAlt').show();
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

		getSourceValuesForPlacard: function () {
			var placardId = $("#id").val();
			var sourceType = $("#sourceTypeSelect").val();

			if (sourceType === 'none') {
				$("#propertyRowsourceId").hide();
			} else {
				$("#propertyRowsourceId").show();
			}

			var url = Globals.path + '/WebBuilder/AJAX?method=getSourceValuesForPlacard&placardId=' + placardId + '&sourceType=' + sourceType;
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
		},

		// Call this whenever placards are rendered/shown.
		renderPlacards: function(placardIds) {
			if (Array.isArray(placardIds)) {
				placardIds.forEach(function(id) {
					AspenDiscovery.WebBuilder.trackPlacardView(id);
				});
			}
		},

		checkLinkedObject: function (submitForm) {
			var url = Globals.path + "/WebBuilder/AJAX";
			var params = {
				'method': 'checkLinkedObject',
				objectId: $("#id").val()
			};

			$.getJSON(url, params,function(data){
				if (data.success){
					if (data.noLinkedObject === true){
						submitForm();
					} else{
						AspenDiscovery.WebBuilder.saveLinkedObjCallback = submitForm;
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons, '', '', false, '', true);
					}
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			})
		},

		setPlacardToCustomized: function() {

		},

		saveLinkedObject: function(doFullSave){
			var body = $("#teaser").val();
			if (body === '') {
				body = $("#description").val();
			}
			var params = {
				objectId: $("#id").val(),
				objectName: $("#name").val(),
				url: $("#url").val(),
				body: body,
				image: $("#importFile-label-logo").val(),
				doFullSave: doFullSave
			};
			var url = Globals.path + '/WebBuilder/AJAX?method=saveLinkedObject';
			$.getJSON(url, params,function(data){
				if (data.success === true){
					AspenDiscovery.WebBuilder.saveLinkedObjCallback();
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			});
		},

		getImageActionFields: function() {
			var imgAction = $("#imgActionSelect").val();
			if (imgAction === '1' || imgAction === '2') {
				$('#propertyRowimageURL').show();
			} else {
				$('#propertyRowimageURL').hide();
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

		deleteCell(id) {
			AspenDiscovery.confirm('Delete Cell', `Are you sure you want to delete this cell (ID: ${id})?`, 'Delete', 'Cancel', true, `AspenDiscovery.WebBuilder.deleteCellConfirmed("${id}")`, 'btn-danger');
			return false;
		},

		deleteCellConfirmed(id) {
			const url = `${Globals.path}/WebBuilder/AJAX`;
			const params = {
				method: 'deleteCell',
				id
			};

			$.getJSON(url, params, (data) => {
				const { success, rowId, newRow,  message} = data || [];
				if (success) {
					const $targetRow = $(`#portalRow${rowId}`);
					if ($targetRow.length) {
						$targetRow.replaceWith(newRow);
					}
					AspenDiscovery.closeLightbox();
				} else {
					AspenDiscovery.showMessage('Error Deleting Cell', data.message);
				}
			});
			return false;
		},

		deleteRow(id) {
			AspenDiscovery.confirm('Delete Row', `Are you sure you want to delete this row (ID: ${id})?`, 'Delete', 'Cancel', true, `AspenDiscovery.WebBuilder.deleteRowConfirmed("${id}")`, 'btn-danger');
			return false;
		},

		deleteRowConfirmed(id) {
			const url = `${Globals.path}/WebBuilder/AJAX`;
			const params = {
				method: 'deleteRow',
				id
			};

			$.getJSON(url, params, (data) => {
				const { success, message } = data;
				if (success){
					$("#portalRow" + id).hide();
					AspenDiscovery.closeLightbox();
				} else {
					AspenDiscovery.showMessage('Error Deleting Row', data.message);
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
				$("#propertyRowallowableHomeLocations").show();
			} else {
				$("#propertyRowallowAccess").hide();
				$("#propertyRowrequireLoginUnlessInLibrary").hide();
				$("#propertyRowallowableHomeLocations").hide();
			}

			$(requireLogin).on('click', function() {
				if(requireLogin.is(":checked")){
					$("#propertyRowallowAccess").show();
					$("#propertyRowrequireLoginUnlessInLibrary").show();
					$("#propertyRowallowableHomeLocations").show();
				}else{
					$("#propertyRowallowAccess").hide();
					$("#propertyRowrequireLoginUnlessInLibrary").hide();
					$("#propertyRowallowableHomeLocations").hide();
				}
			});
		},

		updateWebResourcesFields: function () {
			var requireLogin = $('#requireLoginUnlessInLibrary');
			if(requireLogin.is(":checked")) {
				$("#propertyRowallowAccessByLibrary").show();
			} else {
				$("#propertyRowallowAccessByLibrary").hide();
			}

			$(requireLogin).on('click', function() {
				if(requireLogin.is(":checked")){
					$("#propertyRowallowAccessByLibrary").show();
				}else{
					$("#propertyRowallowAccessByLibrary").hide();
				}
			});
		},

		getWebResource(id, fromPlacard = false) {
			const url = `${Globals.path}/WebBuilder/AJAX`;
			const params = {
				method: "getWebResource",
				resourceId: id
			};

			$.getJSON(url, params, (data) => {
				const { requireLogin, canView, openInNewTab, url: resourceUrl, userNoAccessTitle, userNoAccessMessage } = data;

				const openResource = () => {
					if (openInNewTab) {
						const newTab = window.open("", '_blank');
						if (newTab == null) {
							location.assign(resourceUrl);
						} else {
							newTab.location.href = resourceUrl;
						}
					} else {
						location.assign(resourceUrl);
					}
				};

				const trackUsage = (authType) => {
					const trackParams = {
						method: "trackWebResourceUsage",
						id,
						authType
					};
					if (fromPlacard) {
						trackParams.fromPlacard = 1;
					}
					$.getJSON(url, trackParams, () => openResource());
				};

				if (requireLogin) {
					if (Globals.loggedIn && canView) {
						trackUsage(Globals.loggedIn ? "user" : "library");
					} else if (Globals.loggedIn && !canView) {
						AspenDiscovery.showMessage(userNoAccessTitle, userNoAccessMessage);
					} else {
						AspenDiscovery.Account.ajaxLogin(null, () => AspenDiscovery.WebBuilder.getWebResource(id, fromPlacard), true);
					}
				} else {
					trackUsage("none");
				}
			}).fail(AspenDiscovery.ajaxFail);

			return false;
		},

		placardClickHandler: function(placardId) {
			AspenDiscovery.WebBuilder.trackPlacardClick(placardId);
		},

		getAddQuickPollOptionForm: function (pollId) {
			var url = Globals.path + '/WebBuilder/AJAX';
			var params = {
				method: 'getAddQuickPollOptionForm',
				pollId: pollId
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

		addQuickPollOption: function () {
			var url = Globals.path + '/WebBuilder/AJAX';
			var newOption = $("#newOption").val();
			var pollId = $("#pollId").val();
			var params = {
				method: 'addQuickPollOption',
				pollId: pollId,
				newOption: newOption
			};

			$.getJSON(url, params, function (data) {
				if (data.success){
					$('#newOptionPlaceholder').before(data.formattedOption);
					AspenDiscovery.closeLightbox();
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		}
	};
}(AspenDiscovery.WebBuilder || {});