AspenDiscovery.ListWidgets = (function(){
	return {
		createWidgetFromList: function (listId){
			//prompt for the widget to add to
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToWidgetForm&source=list&id=' + listId, true);
			return false;
		},
		createWidgetFromSearch: function (searchId){
			//prompt for the widget to add to
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToWidgetForm&source=search&id=' + searchId, true);
			return false;
		}
	};
}(AspenDiscovery.ListWidgets || {}));