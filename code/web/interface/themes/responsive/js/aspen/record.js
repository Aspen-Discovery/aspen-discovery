AspenDiscovery.Record = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		showPlaceHold: function(module, id){
			if (Globals.loggedIn){
				let source;
				let volume = null;
				if (id.indexOf(":") > 0){
					let idParts = id.split(":");
					source = idParts[0];
					id = idParts[1];
					if (idParts.length > 2){
						volume = idParts[2];
					}
				}else{
					source = 'ils';
				}
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source;
				if (volume != null){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHold(module, id);
				}, false);
			}
			return false;
		},

		showPlaceHoldEditions: function (module, id) {
			if (Globals.loggedIn){
				let source;
				let volume = null;
				if (id.indexOf(":") > 0){
					let idParts = id.split(":");
					source = idParts[0];
					id = idParts[1];
					if (idParts.length > 2){
						volume = idParts[2];
					}
				}else{
					source = 'ils';
				}

				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldEditionsForm&recordSource=" + source;
				if (volume != null){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHoldEditions(module, id);
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
				'method': 'placeHold'
				,campus: $('#campus').val()
				,selectedUser: $('#user').val()
				,cancelDate: $('#cancelDate').val()
				,recordSource: $('#recordSource').val()
				,account: $('#account').val()
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
			if (params['campus'] === 'undefined'){
				alert("Please select a location to pick up your hold when it is ready.");
				return false;
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

		reloadCover: function(module, id){
			let url = Globals.path + '/' +module + '/' + id + '/AJAX?method=reloadCover';
			$.getJSON(url, function (data){
						AspenDiscovery.showMessage("Success", data.message, true, true);
						setTimeout("AspenDiscovery.closeLightbox();", 3000);
					}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		moreContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="none";
			document.getElementById('additionalContributors').style.display="block";
		},

		lessContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="block";
			document.getElementById('additionalContributors').style.display="none";
		}

	};
}(AspenDiscovery.Record || {}));