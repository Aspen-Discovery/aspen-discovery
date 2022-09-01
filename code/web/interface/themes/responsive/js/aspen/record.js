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
					if (data.holdFormBypassed) {
						if (data.success) {
							if (data.needsItemLevelHold){
								AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
							}else {
								AspenDiscovery.showMessage(data.title, data.message, false, false);
								AspenDiscovery.Account.loadMenuData();
							}
						}else if (data.confirmationNeeded){
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
						} else {
							AspenDiscovery.showMessage(data.title, data.message, false, false);
						}
					}else {
						if (data.success) {
							AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
						} else {
							AspenDiscovery.showMessage(data.title, data.message);
						}
					}
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHold(module, source, id, volume);
				}, false);
			}
			return false;
		},

		showVdxRequest: function(module, source, id) {
			if (Globals.loggedIn){
				document.body.style.cursor = "wait";
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getVdxRequestForm&recordSource=" + source;
				$.getJSON(url, function(data){
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showVdxRequest(module, source, id, volume);
				}, false);
			}
			return false;
		},

		submitVdxRequest: function(module, id) {
			if (Globals.loggedIn){
				document.body.style.cursor = "wait";
				var module = module;
				var params = {
					'method': 'submitVdxRequest',
					title: $('#title').val(),
					author: $('#author').val(),
					publisher: $('#publisher').val(),
					isbn: $('#isbn').val(),
					maximumFeeAmount: $('#maximumFeeAmount').val(),
					acceptFee: $('#acceptFee').prop('checked'),
					pickupLocation: $('#pickupLocationSelect').val(),
					catalogKey: $('#catalogKey').val(),
					note: $('#note').val()
				};
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=submitVdxRequest";
				$.getJSON(url, params, function(data){
					document.body.style.cursor = "default";
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
					} else {
						AspenDiscovery.showMessage(data.title, data.message, false, false);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showVdxRequest(module, source, id, volume);
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

		showPlaceHoldVolumes: function (module, source, id) {
			if (Globals.loggedIn){
				var url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldVolumesForm&recordSource=" + source;
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHoldVolumes(module, source, id);
				}, false);
			}
			return false;
		},

		submitHoldForm: function(){
			var requestTitleButton = $('#requestTitleButton');
			requestTitleButton.prop('disabled', true);
			requestTitleButton.addClass('disabled');
			document.querySelector('.fa-spinner').classList.remove('hidden');
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
			params = this.loadHoldNotificationOptions(params);

			$.getJSON(Globals.path + "/" + module +  "/" + id + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						var requestTitleButton = $('#requestTitleButton');
						requestTitleButton.prop('disabled', false);
						requestTitleButton.removeClass('disabled');
						document.querySelector('.fa-spinner').classList.add('hidden');
						$('.modal-body').html(data.message);
					}else{
						AspenDiscovery.showMessage(data.title, data.message, false, data.autologout);
						if (!data.autologout){
							AspenDiscovery.Account.loadMenuData();
						}
					}
				}else if (data.confirmationNeeded){
					AspenDiscovery.showMessageWithButtons(data.title, data.message, data.modalButtons);
				}else{
					AspenDiscovery.showMessage(data.title, data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		loadHoldNotificationOptions: function (params){
			var emailNotification = $('#emailNotification');
			if (emailNotification.length > 0){
				if (emailNotification.is(':checked')){
					params['emailNotification'] = 'on';
				}else{
					params['emailNotification'] = 'off';
				}
			}
			var phoneNotification = $('#phoneNotification');
			if (phoneNotification.length > 0){
				if (phoneNotification.is(':checked')){
					params['phoneNotification'] = 'on';
				}else{
					params['phoneNotification'] = 'off';
				}
			}
			var phoneNumber = $('#phoneNumber');
			if (phoneNumber.length > 0){
				params['phoneNumber'] = phoneNumber.val();
			}
			var smsNotification = $('#smsNotification');
			if (smsNotification.length > 0){
				if (smsNotification.is(':checked')){
					params['smsNotification'] = 'on';
				}else{
					params['smsNotification'] = 'off';
				}
			}
			var smsNumber = $('#smsNumber');
			if (smsNumber.length > 0){
				params['smsNumber'] = smsNumber.val();
			}
			var smsCarrier = $('#smsCarrier');
			if (smsCarrier.length > 0){
				params['smsCarrier'] =$("#smsCarrier option:selected").val();
			}
			return params;
		},

		placeVolumeHold: function(){
			var id = $('#id').val();
			var autoLogOut = $('#autologout').prop('checked');
			var module = $('#module').val();
			var volume = $('#selectedVolume');
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
					params['holdType'] = 'volume';
				}
			}
			params = this.loadHoldNotificationOptions(params);
			$.getJSON(Globals.path + "/" + module +  "/" + id + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						var requestTitleButton = $('#requestTitleButton');
						requestTitleButton.prop('disabled', false);
						requestTitleButton.removeClass('disabled');
						document.querySelector('.fa-spinner').classList.add('hidden');
						$('.modal-body').html(data.message);
					}else{
						AspenDiscovery.showMessage(data.title, data.message, false, autoLogOut);
						AspenDiscovery.Account.loadMenuData();
					}
				}else{
					AspenDiscovery.showMessage(data.title, data.message, false, autoLogOut);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		confirmHold: function (module, bibId, confirmationId) {
			var params = {
				'method': 'confirmHold',
				confirmationId: confirmationId
			};
			$.getJSON(Globals.path + "/" + module +  "/" + bibId + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						var requestTitleButton = $('#requestTitleButton');
						requestTitleButton.prop('disabled', false);
						requestTitleButton.removeClass('disabled');
						document.querySelector('.fa-spinner').classList.add('hidden');
						$('.modal-body').html(data.message);
					}else{
						AspenDiscovery.showMessage(data.title, data.message, false);
						AspenDiscovery.Account.loadMenuData();
					}
				}else{
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
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