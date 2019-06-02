AspenDiscovery.EContent = (function(){
	return {
		submitHelpForm: function(){
			$.post(Globals.path + '/Help/eContentSupport', $("#eContentSupport").serialize(),
					function(data){
						AspenDiscovery.showMessage(data.title, data.message);
					},
					'json').fail(function(){AspenDiscovery.ajaxFail()});
			return false;
		}
	}
}(AspenDiscovery.EContent));
