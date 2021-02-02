AspenDiscovery.EBSCO = (function () {
	return {
		dismissResearchStarter: function(id){
			if (Globals.loggedIn){
				var ajaxUrl = Globals.path + "/EBSCO/JSON";
				var params = {
					'method':'dismissResearchStarter',
					id: id
				};
				$.getJSON(ajaxUrl, params,function (data) {
					$('#researchStarter-' + id).hide();
					AspenDiscovery.showMessage(data.title, data.message, true, false);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.EBSCO.dismissResearchStarter(id);
				}, true);
			}
			return false;
		},

		getResearchStarters: function(searchTerm){
			var ajaxUrl = Globals.path + "/EBSCO/JSON";
			var params = {
				'method':'getResearchStarters',
				lookfor: searchTerm
			};
			$.getJSON(ajaxUrl, params,function (data) {
				$('#research-starter-placeholder').html(data.researchStarters);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		trackEdsUsage: function (id) {
			var ajaxUrl = Globals.path + "/EBSCO/JSON?method=trackEdsUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.EBSCO || {}));