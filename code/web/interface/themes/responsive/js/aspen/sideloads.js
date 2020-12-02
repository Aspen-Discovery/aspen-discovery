AspenDiscovery.SideLoads = (function(){
	return {
		deleteMarc: function (sideLoadId, fileName, fileIndex) {
			if (!confirm('Are you sure you want to delete this ' + fileName + '?')){
				return false;
			}
			var params = {
				method : 'deleteMarc',
				id: sideLoadId,
				file: fileName
			};

			$.getJSON(Globals.path + "/SideLoads/AJAX",params, function(data){
				if (data.success){
					$("#file" + fileIndex).hide();
				}else{
					AspenDiscovery.showMessage('Delete Failed', data.message, false, autoLogOut);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}
	}
}(AspenDiscovery.SideLoads || {}));