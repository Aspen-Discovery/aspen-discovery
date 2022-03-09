AspenDiscovery.Hoopla = (function(){
	return {
		checkOutHooplaTitle: function (hooplaId, patronId) {
			if (Globals.loggedIn) {
				if (typeof patronId === 'undefined') {
					patronId = $('#patronId', '#pickupLocationOptions').val(); // Lookup selected user from the options form
				}
				var url = Globals.path + '/Hoopla/'+ hooplaId + '/AJAX';
				var params = {
					'method' : 'checkOutHooplaTitle',
					patronId : patronId
				};
				if ($('#stopHooplaConfirmation').prop('checked')){
					params['stopHooplaConfirmation'] = true;
				}
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail)
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Hoopla.checkOutHooplaTitle(hooplaId, patronId);
				}, false);
			}
			return false;
		},

		getCheckOutPrompts: function (hooplaId) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX?method=getCheckOutPrompts";
				$.getJSON(url, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.getCheckOutPrompts(hooplaId);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, hooplaId) {
			if (Globals.loggedIn) {
				if (confirm('Are you sure you want to return this title?')) {
					AspenDiscovery.showMessage("Returning Title", "Returning your title in Hoopla.");
					var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX",
							params = {
								'method': 'returnCheckout'
								,patronId: patronId
							};
					$.getJSON(url, params, function (data) {
						AspenDiscovery.showMessage(data.success ? 'Success' : 'Error', data.message, data.success, data.success);
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.returnCheckout(patronId, hooplaId);
					AspenDiscovery.Account.loadMenuData();
				}, false);
			}
			return false;
		},

		getLargeCover: function (id){
			var url = Globals.path + '/Hoopla/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

	}
}(AspenDiscovery.Hoopla || {}));