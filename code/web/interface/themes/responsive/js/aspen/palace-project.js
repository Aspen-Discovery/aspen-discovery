AspenDiscovery.PalaceProject = (function () {
	return {

		getStaffView: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		showPreview: function (palaceProjectId) {
			var url = Globals.path + "/PalaceProject/" + palaceProjectId + "/AJAX?method=getPreview";
			$.getJSON(url, function (data){
				if (data.success){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}else{
					AspenDiscovery.showMessage('Error', data.message);
				}
			});
		},

		getLargeCover: function (id){
			var url = Globals.path + '/PalaceProject/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		getCheckOutPrompts: function (id) {
			var url = Globals.path + "/PalaceProject/" + id + "/AJAX?method=getCheckOutPrompts";
			var result = false;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				var promptInfo = AspenDiscovery.PalaceProject.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.PalaceProject.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.PalaceProject.checkOutTitle(id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				var ajaxUrl = Globals.path + "/PalaceProject/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							if (data.noCopies === true) {
								AspenDiscovery.closeLightbox(function (){
									var ret = confirm(data.message);
									if (ret === true) {
										AspenDiscovery.PalaceProject.doHold(patronId, id);
									}
								});
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Palace Project.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.PalaceProject.checkOutTitle(id);
				}, false);
			}
			return false;
		},

	}
}(AspenDiscovery.PalaceProject || {}));