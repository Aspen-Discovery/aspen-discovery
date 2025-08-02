AspenDiscovery.SideLoads = (() => {
	return {
		deleteMarc(sideLoadId, fileName, fileIndex) {
			/*
				Because the modal button’s onclick attribute is enclosed in single quotes,
				removing literal apostrophes prevents the attribute from being prematurely closed,
				and the entity is decoded back to an apostrophe when the JavaScript runs.
			 */
			const fileNameHtmlSafe = fileName.replace(/'/g, "&#39;");
			AspenDiscovery.confirm(
				'Confirm Delete',
				`Are you sure you want to delete this <strong>${fileName}</strong>?`,
				'Delete',
				'Cancel',
				true,
				`AspenDiscovery.closeLightbox();AspenDiscovery.SideLoads.doDeleteMarc(${sideLoadId}, \"${fileNameHtmlSafe}\", ${fileIndex});`,
				'btn-danger'
			);

			return false;
		},

		doDeleteMarc(sideLoadId, fileName, fileIndex) {
			const params = {
				method : 'deleteMarc',
				id: sideLoadId,
				file: fileName
			};
			$.getJSON(Globals.path + "/SideLoads/AJAX", params, function(data) {
				const { success, message } = data;
				if (success){
					$("#file" + fileIndex).hide();
				}else{
					AspenDiscovery.showMessage('Delete Failed', message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}
	}
})();