AspenDiscovery.Record = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		showPlaceHold: function(module, source, id, volume){
			if (Globals.loggedIn){
				document.body.style.cursor = "wait";
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source;
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
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldEditionsForm&recordSource=" + source;
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
					let idParts = id.split(":", 2);
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
			let params = $('#bookMaterialForm').serialize();
			let module = $('#module').val();
			AspenDiscovery.showMessage('Scheduling', 'Processing, please wait.');
			$.getJSON(Globals.path + "/" + module +"/AJAX", params+'&method=bookMaterial', function(data){
				if (data.modalBody) AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					// For errors that can be fixed by the user, the form will be re-displayed
				if (data.success) AspenDiscovery.showMessage('Success', data.message/*, true*/);
				else if (data.message) AspenDiscovery.showMessage('Error', data.message);
			}).fail(AspenDiscovery.ajaxFail);
		},

		submitHoldForm: function(){
			let id = $('#id').val();
			let autoLogOut = $('#autologout').prop('checked');
			let selectedItem = $('#selectedItem');
			let module = $('#module').val();
			let volume = $('#volume');
			let params = {
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
			let holdType = $('#holdType');
			if (holdType.length > 0){
				params['holdType'] = holdType.val();
			}else{
				if ($('#holdTypeBib').attr('checked')){
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
			let url = Globals.path + '/Record/' + id + '/AJAX?method=uploadPDF';
			let uploadPDFData = new FormData($("#uploadPDFForm")[0]);
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

		deletePDF: function(id, fileId) {
			if (confirm("Are you sure you want to delete this file?")){
				let url = Globals.path + '/Record/' + id + '/AJAX?method=deletePDF&fileId=' +fileId;
				$.getJSON(url, function (data){
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				});
			}
			return false;
		},

		getUploadPDFForm: function (id){
			let url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadPDFForm';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileDownload: function( recordId) {
			let url = Globals.path + '/Record/' + recordId + '/AJAX?method=showSelectDownloadForm';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		downloadSelectedFile: function () {
			let id = $('#id').val();
			let selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Record/' + id + '/DownloadPDF?fileId=' + selectedFile;
			return false;
		},

		getStaffView: function (module, id) {
			let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getStaffView";
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