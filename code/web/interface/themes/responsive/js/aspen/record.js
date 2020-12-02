AspenDiscovery.Record = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		showPlaceHold: function(module, source, id, volume){
			if (Globals.loggedIn){
				document.body.style.cursor = "wait";
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source;
				if (volume !== undefined){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					document.body.style.cursor = "default";
					if (data.holdFormBypassed){
						if (data.success){
							AspenDiscovery.showMessage('Hold Placed Successfully', data.message, false, false);
							AspenDiscovery.Account.loadMenuData();
						}else{
							AspenDiscovery.showMessage('Hold Failed', data.message, false, false);
						}
					}
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHold(module, source, id, volume);
				}, false);
			}
			return false;
		},

		showPlaceHoldEditions: function (module, source, id, volume) {
			if (Globals.loggedIn){
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldEditionsForm&recordSource=" + source;
				if (volume !== undefined){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHoldEditions(module, source, id, volume);
				}, false);
			}
			return false;

		},

		showBookMaterial: function(module, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				//var source; // source not used for booking at this time
				if (id.indexOf(":") > 0){
					var idParts = id.split(":", 2);
					//source = idParts[0];
					id = idParts[1];
				//}else{
				//	source = 'ils';
				}
				$.getJSON(Globals.path + "/" + module + "/" + id + "/AJAX?method=getBookMaterialForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail)
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showBookMaterial(id);
				}, false)
			}
			return false;
		},

		submitBookMaterialForm: function(){
			var params = $('#bookMaterialForm').serialize();
			var module = $('#module').val();
			AspenDiscovery.showMessage('Scheduling', 'Processing, please wait.');
			$.getJSON(Globals.path + "/" + module +"/AJAX", params+'&method=bookMaterial', function(data){
				if (data.modalBody) AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					// For errors that can be fixed by the user, the form will be re-displayed
				if (data.success) AspenDiscovery.showMessage('Success', data.message/*, true*/);
				else if (data.message) AspenDiscovery.showMessage('Error', data.message);
			}).fail(AspenDiscovery.ajaxFail);
		},

		submitHoldForm: function(){
			var id = $('#id').val();
			var autoLogOut = $('#autologout').prop('checked');
			var selectedItem = $('#selectedItem');
			var module = $('#module').val();
			var volume = $('#volume');
			var params = {
				'method': 'placeHold',
				pickupBranch: $('#pickupBranch').val(),
				selectedUser: $('#user').val(),
				cancelDate: $('#cancelDate').val(),
				recordSource: $('#recordSource').val(),
				account: $('#account').val(),
				rememberHoldPickupLocation: $('#rememberHoldPickupLocation').prop('checked')
			};
			if (autoLogOut){
				params['autologout'] = true;
			}
			if (selectedItem.length > 0){
				params['selectedItem'] = selectedItem.val();
			}
			if (volume.length > 0){
				params['volume'] = volume.val();
			}
			if (params['pickupBranch'] === 'undefined'){
				alert("Please select a location to pick up your hold when it is ready.");
				return false;
			}
			var holdType = $('#holdType');
			if (holdType.length > 0){
				params['holdType'] = holdType.val();
			}else{
				if ($('#holdTypeBib').is(':checked')){
					params['holdType'] = 'bib';
				}else{
					params['holdType'] = 'item';
				}
			}
			$.getJSON(Globals.path + "/" + module +  "/" + id + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						$('.modal-body').html(data.message);
					}else{
						AspenDiscovery.showMessage('Hold Placed Successfully', data.message, false, autoLogOut);
						AspenDiscovery.Account.loadMenuData();
					}
				}else{
					AspenDiscovery.showMessage('Hold Failed', data.message, false, autoLogOut);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		moreContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="none";
			document.getElementById('additionalContributors').style.display="block";
		},

		lessContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="block";
			document.getElementById('additionalContributors').style.display="none";
		},

		uploadPDF: function (id){
			var url = Globals.path + '/Record/' + id + '/AJAX?method=uploadPDF';
			var uploadPDFData = new FormData($("#uploadPDFForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadPDFData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		uploadSupplementalFile: function (id){
			var url = Globals.path + '/Record/' + id + '/AJAX?method=uploadSupplementalFile';
			var uploadSupplementalFileData = new FormData($("#uploadSupplementalFileForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadSupplementalFileData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		deleteUploadedFile: function(id, fileId) {
			if (confirm("Are you sure you want to delete this file?")){
				var url = Globals.path + '/Record/' + id + '/AJAX?method=deleteUploadedFile&fileId=' +fileId;
				$.getJSON(url, function (data){
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				});
			}
			return false;
		},

		getUploadPDFForm: function (id){
			var url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadPDFForm';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		getUploadSupplementalFileForm: function (id) {
			var url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadSupplementalFileForm';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileDownload: function( recordId, type) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectDownloadForm',
				type: type
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileToView: function( recordId, type) {
			var url = Globals.path + '/Record/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectFileToViewForm',
				type: type
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		downloadSelectedFile: function () {
			var id = $('#id').val();
			var fileType = $('#fileType').val();
			var selectedFile = $('#selectedFile').val();
			if (fileType === 'RecordPDF'){
				window.location = Globals.path + '/Record/' + id + '/DownloadPDF?fileId=' + selectedFile;
			}else{
				window.location = Globals.path + '/Record/' + id + '/DownloadSupplementalFile?fileId=' + selectedFile;
			}
			return false;
		},

		viewSelectedFile: function () {
			var selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Files/' + selectedFile + '/ViewPDF';
			return false;
		},

		getStaffView: function (module, id) {
			var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	};
}(AspenDiscovery.Record || {}));