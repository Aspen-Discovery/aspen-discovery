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
			$("accessOnline_" + recordId).enabled = false;
			$.getJSON(url, params, function (data){
				$("accessOnline_" + recordId).enabled = true;
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		viewItemLink: function () {
			var selectedItem = $('#selectedItem').val();
			var id = $('#id').val();
			var url = Globals.path + '/ExternalEContent/' + id + '/AJAX';
			var params = {
				method: 'viewItem',
				selectedItem: selectedItem
			};
			$.getJSON(url, params, function (data){
				if (data.success) {
					AspenDiscovery.closeLightbox();
					window.open(data.url, '_blank');
				}else {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			});
			return false;
		}
	}
}(AspenDiscovery.EContent));
