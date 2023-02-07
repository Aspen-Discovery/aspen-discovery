AspenDiscovery.EContent = (function(){
	return {
		submitHelpForm: function(){
			$.post(Globals.path + '/Help/eContentSupport', $("#eContentSupport").serialize(),
					function(data){
						AspenDiscovery.showMessage(data.title, data.message);
					},
					'json').fail(function(){AspenDiscovery.ajaxFail()});
			return false;
		},

		selectItemLink: function( recordId) {
			var url = Globals.path + '/ExternalEContent/' + recordId + '/AJAX';
			var params = {
				method: 'showSelectItemToViewForm'
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		}
	}
}(AspenDiscovery.EContent));
