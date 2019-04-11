VuFind.Hoopla = (function(){
	return {
		checkOutHooplaTitle: function (hooplaId, patronId) {
			if (Globals.loggedIn) {
				if (typeof patronId === 'undefined') {
					patronId = $('#patronId', '#pickupLocationOptions').val(); // Lookup selected user from the options form
				}
				var url = Globals.path + '/Hoopla/'+ hooplaId + '/AJAX',
						params = {
							'method' : 'checkOutHooplaTitle',
							patronId : patronId
						};
				if ($('#stopHooplaConfirmation').prop('checked')){
					params['stopHooplaConfirmation'] = true;
				}
				$.getJSON(url, params, function (data) {
					if (data.success) {
						VuFind.showMessageWithButtons(data.title, data.message, data.buttons);
					} else {
						VuFind.showMessage("Checking Out Title", data.message);
					}
				}).fail(VuFind.ajaxFail)
			}else{
				VuFind.Account.ajaxLogin(null, function(){
					VuFind.Hoopla.checkOutHooplaTitle(hooplaId, patronId);
				}, false);
			}
			return false;
		},

		getCheckOutPrompts: function (hooplaId) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX?method=geCheckOutPrompts";
				$.getJSON(url, function (data) {
					VuFind.showMessageWithButtons(data.title, data.body, data.buttons);
				}).fail(VuFind.ajaxFail);
			} else {
				VuFind.Account.ajaxLogin(null, function () {
					VuFind.Hoopla.getCheckOutPrompts(hooplaId);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, hooplaId) {
			if (Globals.loggedIn) {
				if (confirm('Are you sure you want to return this title?')) {
					VuFind.showMessage("Returning Title", "Returning your title in Hoopla.");
					var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX",
							params = {
								'method': 'returnCheckout'
								,patronId: patronId
							};
					$.getJSON(url, params, function (data) {
						VuFind.showMessage(data.success ? 'Success' : 'Error', data.message, data.success, data.success);
					}).fail(VuFind.ajaxFail);
				}
			} else {
				VuFind.Account.ajaxLogin(null, function () {
					VuFind.Hoopla.returnCheckout(patronId, hooplaId);
				}, false);
			}
			return false;
		}

	}
}(VuFind.Hoopla || {}));