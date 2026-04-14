AspenDiscovery.Lists = (function () {
	// noinspection JSUnusedGlobalSymbols
	return {
		addToHomePage: function (listId, selectedResourceTypes, activeFilters) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getAddBrowseCategoryFromListForm&listId=' + listId, true);
		},

		editListAction: function () {
			$('#listDescription,#listTitle,#FavEdit,.listViewButton').hide();
			$('#listEditControls,#FavSave,.listEditButton').show();
			AspenDiscovery.Lists.updateListEditFields();
			const element = document.getElementById('listEditControls');
			element.scrollIntoView();
			return false;
		},

		cancelEditListAction: function () {
			$('#listDescription,#listTitle,#FavEdit,.listViewButton').show();
			$('#listEditControls,#FavSave,.listEditButton').hide();
			return false;
		},

		submitListForm: function (action) {
			$('#myListActionHead').val(action);
			$('#myListFormHead').trigger('submit');
			AspenDiscovery.Account.loadListData();
			return false;
		},

		updateListEditFields: function () {
			let publicSwitch = $("#public");
			let searchableSwitch = $("#searchable");
			let displayListAuthorSwitch = $("#displayListAuthor");
			if (publicSwitch.prop('checked')) {
				if (searchableSwitch !== undefined) {
					$('#searchableRow').show();
					if (searchableSwitch.prop('checked')) {
						$('#displayListAuthorRow').show();
						if (displayListAuthorSwitch.prop('checked')) {
							$('#customAuthorNameRow').show();
						}else{
							$('#customAuthorNameRow').hide();
						}
					}else{
						$('#displayListAuthorRow').hide();
						$('#customAuthorNameRow').hide();
					}
				}
			}else{
				$('#searchableRow').hide();
				$('#displayListAuthorRow').hide();
				$('#customAuthorNameRow').hide();
			}
		},

		makeListPublicAction: function () {
			return this.submitListForm('makePublic');
		},

		makeListPrivateAction: function () {
			return this.submitListForm('makePrivate');
		},

		deleteListAction() {
			const url = Globals.path + '/MyAccount/AJAX?method=getDeleteListForm';
			$.getJSON(url, function (data) {
				const {title, modalBody, modalButtons} = data;
				AspenDiscovery.showMessageWithButtons(title, modalBody, modalButtons, false, '', false, false, true);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		deleteEntryFromList: function (listId, listEntryId) {
			window.location.href = Globals.path + '/MyAccount/MyList/' + listId + '?delete=' + listEntryId;
		},

		doDeleteList() {
			$('#confirmDeleteList .fa-spinner').show();
			$('#confirmDeleteList').prop('disabled', true);
			const hardDelete = $('#optOutSoftDeletion').is(':checked');

			if (hardDelete) {
				this.submitListForm('deleteListHard');
			} else {
				this.submitListForm('deleteList');
			}
		},

		updateListAction: function () {
			return this.submitListForm('saveList');
		},

		emailListAction: function (listId, selectedResourceTypes, activeFilters) {
			var urlToDisplay = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.loadingMessage();
			$.getJSON(urlToDisplay, {
					method: 'getEmailMyListForm',
					listId: listId,
					selectedResourceTypes: selectedResourceTypes,
					activeFilters: activeFilters
				},
				function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		sendMyListEmail: function () {
			var url = Globals.path + "/MyAccount/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					listId: $('#emailListForm input[name="listId"]').val(),
					selectedResourceTypes: $('#emailListForm input[name="selectedResourceTypes"]').val(),
					activeFilters: $('#emailListForm input[name="activeFilters"]').val(),
					to: $('#emailListForm input[name="to"]').val(),
					from: $('#emailListForm input[name="from"]').val(),
					message: $('#emailListForm textarea[name="message"]').val(),
					method: 'sendMyListEmail'
				},
				function (data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			);
		},

		citeListAction: function (id, selectedResourceTypes, activeFilters) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getCitationFormatsForm&listId=' + id + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters, false);
		},

		processCiteListForm: function () {
			$("#citeListForm").trigger('submit');
		},

		batchAddToListAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getBulkAddToListForm&listId=' + id);
		},

		processBulkAddForm: function () {
			$("#bulkAddToList").trigger('submit');
		},

		changeList: function () {
			var availableLists = $("#availableLists");
			window.location = Globals.path + "/MyAccount/MyList/" + availableLists.val();
		},

		printListAction: function () {
			window.print();
			return false;
		},

		printListWithDescriptions: function () {
			// Ensure descriptions are shown.
			$('body').removeClass('no-print-descriptions');
			window.print();
			return false;
		},

		printListWithoutDescriptions: function () {
			// Hide descriptions during print.
			$('body').addClass('no-print-descriptions');
			window.print();
			return false;
		},

		importListsFromClassic: function () {
			AspenDiscovery.confirm("Import Lists?", "This will import any lists you had defined in the old catalog.  This may take several minutes depending on the size of your lists. Are you sure you want to continue?", "Yes", "No", true, "AspenDiscovery.Lists.doImportListsFromClassic()");
			return false;
		},
		doImportListsFromClassic: function () {
			window.location = Globals.path + "/MyAccount/ImportListsFromClassic";
			return false;
		},
		getUploadListCoverForm: function (id) {
			var url = Globals.path + '/MyAccount/AJAX?id=' + id + '&method=getUploadListCoverForm';
			$.getJSON(url, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadListCover: function (id) {
			var url = Globals.path + '/MyAccount/AJAX?id=' + id + '&method=uploadListCover';
			var uploadCoverData = new FormData($("#uploadListCoverForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function (data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		getUploadListCoverFormByURL: function (id) {
			var url = Globals.path + '/MyAccount/AJAX?id=' + id + '&method=getUploadListCoverFormByURL';
			$.getJSON(url, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadListCoverByURL: function (id) {
			var url = Globals.path + '/MyAccount/AJAX?id=' + id + '&method=uploadListCoverByURL';
			var uploadCoverData = new FormData($("#uploadListCoverFormByURL")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function (data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		removeUploadedListCover: function (id) {
			var url = Globals.path + '/MyAccount/AJAX?listId=' + id + '&method=removeUploadedListCover';
			$.getJSON(url, function (data) {
				if (data.success) {
					$("#removeUploadedListCover").hide();
				}
				AspenDiscovery.showMessage(data.title, data.message);
			});
			return false;
		},

		changeWeight: function (listEntryId, direction) {
			var url = Globals.path + '/MyAccount/AJAX';
			var params = {
				method: 'updateWeight',
				listEntryId: listEntryId,
				direction: direction
			};
			$.getJSON(url, params, function (data) {
				if (data.success) {
					var entry1 = $(listEntryId);
					var entry2 = $(data.swappedWithId);
					if (direction === 'up') {
						entry2.before(entry1);
					} else {
						entry1.before(entry2);
					}
					location.reload();
				} else {
					AspenDiscovery.showMessage('An error occurred', data.message);
				}
			});
			return false;
		},

		getPrintListOptions: function (listId, selectedResourceTypes, activeFilters) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getListPrintOptions&listId=' + listId + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters);
		},

		buildAndOpenPrintUrl: function () {
			const print = document.getElementById('print').value;
			const listId = document.getElementById('listId').value;
			const selectedResourceTypes = document.getElementById('selectedResourceTypes').value;
			const activeFilters = document.getElementById('activeFilters').value;

			const baseUrl = Globals.path + '/MyAccount/MyList/' + listId;


			// Checkbox names (in order as in the form)
			const checkboxIds = [
				'printLibraryName',
				'printLibraryLogo',
				'listAuthor',
				'listDescription',
				'covers',
				'series',
				'formats',
				'description',
				'notes',
				'rating',
				'holdings'
			];

			// Build URL params object
			const params = {
				print,
				selectedResourceTypes,
				activeFilters
			};

			checkboxIds.forEach(id => {
				const el = document.getElementById(id);
				if (el) {
					// Only include if checked, send value "true" (or customize as needed)
					params[id] = el.checked ? 'true' : 'false';
				}
			});

			// Build search string
			const urlSearchParams = new URLSearchParams(params).toString();

			// Final URL
			const printUrl = `${baseUrl}?${urlSearchParams}`;

			// Open print window and prompt print dialog once loaded
			const win = window.open(printUrl, '_blank', 'width=900,height=900');
			if (win) {
				// Wait for the new window to load content, then trigger print
				win.onload = function () {
					win.print();
				};
			}
		},

		exportToCSV(listId, selectedResourceTypes, activeFilters) {
			const url = `${Globals.path}/MyAccount/AJAX`;
			$.getJSON(url, {
				method: 'exportUserListCSV',
				listId: listId,
				selectedResourceTypes: selectedResourceTypes,
				activeFilters: activeFilters
			}).done((data) => {
				if (data.success === false) {
					AspenDiscovery.showMessage(data.title, data.message);
				} else {
					window.location.href = `${Globals.path}/MyAccount/AJAX?method=exportUserListCSV&listId=${listId}` + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters;
				}
			}).fail(() => {
				// If the AJAX call itself failed, still try the download.
				window.location.href = `${Globals.path}/MyAccount/AJAX?method=exportUserListCSV&listId=${listId}` + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters;
			});
			return false;
		},

		exportToRIS(listId, selectedResourceTypes, activeFilters) {
			const url = `${Globals.path}/MyAccount/AJAX`;
			$.getJSON(url, {
				method: 'exportUserListRIS',
				listId: listId,
				selectedResourceTypes: selectedResourceTypes,
				activeFilters: activeFilters
			}).done((data) => {
				if (data.success === false) {
					AspenDiscovery.showMessage(data.title, data.message);
				} else {
					window.location.href = `${Globals.path}/MyAccount/AJAX?method=exportUserListRIS&listId=${listId}` + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters;
				}
			}).fail(() => {
				// If the AJAX call itself failed, still try the download.
				window.location.href = `${Globals.path}/MyAccount/AJAX?method=exportUserListRIS&listId=${listId}` + '&selectedResourceTypes=' + selectedResourceTypes + '&activeFilters=' + activeFilters;
			});
			return false;
		},

		listTransferAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListTransferForm&listId=' + id);
		},

		listTransferValidation: function () {
			const url = Globals.path + "/MyAccount/AJAX";
			const params = {
				method: 'listTransferValidation',
				newListOwner: $('#newListOwner').val(),
				listId: $('#listId').val()
			};

			$.getJSON(url, params, function (data) {
				if (data.success === false) {
					const listId = $('#listId').val();
					return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListTransferForm&listId=' + listId + '&validationFailed=true');
				} else {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		listTransferProcess: function (listId, userId) {
			$('#listTransferProcessBtn .fa-spinner').show();
			$('#listTransferProcessBtn').prop('disabled', true);
			var url = Globals.path + "/MyAccount/AJAX?method=listTransferProcess&listId=" + listId + "&userId=" + userId;
			$.getJSON(url, function (data) {
				if (data.success) {
					window.location.href = Globals.path + "/MyAccount/Lists";
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		listGroupTransferAction: function (listGroupId) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListGroupTransferForm&listGroupId=' + listGroupId);
		},

		listGroupTransferValidation: function () {
			const url = Globals.path + "/MyAccount/AJAX";
			const params = {
				method: 'listGroupTransferValidation',
				newListGroupOwner: $('#newListGroupOwner').val(),
				listGroupId: $('#listGroupId').val()
			};

			$.getJSON(url, params, function (data) {
				if (data.success === false) {
					const listGroupId = $('#listGroupId').val();
					return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListGroupTransferForm&listGroupId=' + listGroupId + '&validationFailed=true');
				} else {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		listGroupTransferProcess: function (listGroupId, userId) {
			$('#listTransferProcessBtn .fa-spinner').show();
			$('#listTransferProcessBtn').prop('disabled', true);
			var url = Globals.path + "/MyAccount/AJAX?method=listGroupTransferProcess&listGroupId=" + listGroupId + "&userId=" + userId;
			$.getJSON(url, function (data) {
				if (data.success) {
					window.location.href = Globals.path + "/MyAccount/Lists";
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		listsTransferAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListsTransferForm&prevListOwner=' + id);
		},

		listsTransferValidation: function () {
			const url = Globals.path + "/MyAccount/AJAX";
			const params = {
				method: 'listsTransferValidation',
				newListOwner: $('#newListOwner').val(),
				prevListOwner: $('#prevListOwner').val(),
			};

			$.getJSON(url, params, function (data) {
				if (data.success === false) {
					const prevListOwner = $('#prevListOwner').val();
					return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getListsTransferForm&prevListOwner=' + prevListOwner + '&validationFailed=true');
				} else {
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		listsTransferProcess: function (userId) {
			$('#listTransferProcessBtn .fa-spinner').show();
			$('#listTransferProcessBtn').prop('disabled', true);
			var url = Globals.path + "/MyAccount/AJAX?method=listsTransferProcess&userId=" + userId;
			$.getJSON(url, function (data) {
				if (data.success) {
					window.location.href = Globals.path + "/MyAccount/Lists";
				} else {
					AspenDiscovery.showMessage(data.title, data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}
	};
}(AspenDiscovery.Lists || {}));