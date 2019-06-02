AspenDiscovery.Lists = (function(){
	return {
		addToHomePage: function(listId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getAddBrowseCategoryFromListForm&listId=' + listId, true);
			return false;
		},

		editListAction: function (){
			$('#listDescription,#listTitle,#FavEdit').hide();
			$('#listEditControls,#FavSave').show();
			return false;
		},
		//editListAction: function (){
		//	$('#listDescription').hide();
		//	$('#listTitle').hide();
		//	$('#listEditControls').show();
		//	$('#FavEdit').hide();
		//	$('#FavSave').show();
		//	return false;
		//},

		submitListForm: function(action){
			$('#myListActionHead').val(action);
			$('#myListFormHead').submit();
			return false;
		},

		makeListPublicAction: function (){
			return this.submitListForm('makePublic');
		},

		makeListPrivateAction: function (){
			return this.submitListForm('makePrivate');
		},

		deleteListAction: function (){
			if (confirm("Are you sure you want to delete this list?")){
				this.submitListForm('deleteList');
			}
			return false;
		},

		updateListAction: function (){
			return this.submitListForm('saveList');
		},

		deleteAllListItemsAction: function (){
			if (confirm("Are you sure you want to delete all titles from this list?  This cannot be undone.")){
				this.submitListForm('deleteAll');
			}
			return false;
		},

		emailListAction: function (listId) {
			var urlToDisplay = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.loadingMessage();
			$.getJSON(urlToDisplay, {
					method  : 'getEmailMyListForm'
					,listId : listId
				},
					function(data){
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		SendMyListEmail: function () {
			var url = Globals.path + "/MyAccount/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					listId   : $('#emailListForm input[name="listId"]').val()
					,to      : $('#emailListForm input[name="to"]').val()
					,from    : $('#emailListForm input[name="from"]').val()
					,message : $('#emailListForm textarea[name="message"]').val()
					,method  : 'sendMyListEmail' // serverside method
				},
				function(data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			);
		},

		citeListAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getCitationFormatsForm&listId=' + id, false);
			//return false;
			//TODO: ajax call not working
		},

		processCiteListForm: function(){
			$("#citeListForm").submit();
		},

		batchAddToListAction: function (id){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getBulkAddToListForm&listId=' + id);
			//return false;
		},

		processBulkAddForm: function(){
			$("#bulkAddToList").submit();
		},

		changeList: function (){
			var availableLists = $("#availableLists");
			window.location = Globals.path + "/MyAccount/MyList/" + availableLists.val();
		},

		printListAction: function (){
			window.print();
			return false;
		},

		importListsFromClassic: function (){
			if (confirm("This will import any lists you had defined in the old catalog.  This may take several minutes depending on the size of your lists. Are you sure you want to continue?")){
				window.location = Globals.path + "/MyAccount/ImportListsFromClassic";
			}
			return false;
		}//,

		//setDefaultSort: function(selectedElement, selectedValue) {
		//	$('#default-sort').val(selectedValue);
		//	$('#default-sort + div>ul li').css('background-color', 'inherit');
		//	$(selectedElement).css('background-color', 'gray');
		//}
	};
}(AspenDiscovery.Lists || {}));