AspenDiscovery.Account.ReadingHistory = (function(){
	return {
		initAccordions() {
			const updateToggleLabel = ($link, isExpanded) => {
				$link.attr('aria-expanded', isExpanded);
				// noinspection JSUnresolvedFunction
				const textNode = $link.contents().filter(function() {
					return this.nodeType === 3;
				}).first();

				const newText = isExpanded ? 'Hide Details' : 'Show Details';
				if (textNode.length) {
					textNode[0].nodeValue = `${newText} `;
				} else {
					$link.prepend(`${newText} `);
				}
			};

			$(document)
				.off('click.readingHistory', '.reading-history-toggle-details')
				.on('click.readingHistory', '.reading-history-toggle-details', function(e) {
					e.preventDefault();
					const $link = $(this);
					const targetId = $link.data('target');
					const $target = $('#' + targetId);

					if ($target.length) {
						// noinspection JSUnresolvedFunction
						const shouldExpand = !$target.hasClass('in');
						$target.collapse(shouldExpand ? 'show' : 'hide');
						updateToggleLabel($link, shouldExpand);
					}
				});

			$(document)
				.off('shown.bs.collapse.readingHistory hidden.bs.collapse.readingHistory', '.reading-history-details')
				.on('shown.bs.collapse.readingHistory hidden.bs.collapse.readingHistory', '.reading-history-details', function(e) {
					const targetId = $(this).attr('id');
					if (!targetId) {
						return;
					}
					const isExpanded = e.type === 'shown';
					const $link = $(`.reading-history-toggle-details[data-target="${targetId}"]`);
					if ($link.length) {
						updateToggleLabel($link, isExpanded);
					}
				});
		},

		toggleSelectionMode() {
			const $selectTitle = $('.selectTitle');
			const isSelectionMode = $selectTitle.is(':visible');

			if (isSelectionMode) {
				$selectTitle.hide().removeClass('col-xs-1');
				// noinspection JSUnresolvedFunction
				$selectTitle.prop('checked', false);

				$('.coverColumn').removeClass('col-xs-2 col-sm-3').addClass('col-xs-3 col-sm-4');
				$('.titleColumn').removeClass('col-xs-9 col-sm-8 col-md-9').addClass('col-xs-9 col-sm-8 col-md-10');
				$('.titleColumn.col-xs-11').removeClass('col-xs-11').addClass('col-xs-12');

				$('#selectItemsBtn').show();
				$('#deleteAllBtn').show();
				$('#cancelSelectionBtn').hide();
				$('#deleteDropdown').hide();
			} else {
				$selectTitle.show().addClass('col-xs-1');
				$('.coverColumn').removeClass('col-xs-3 col-sm-4').addClass('col-xs-2 col-sm-3');
				$('.titleColumn').removeClass('col-xs-9 col-sm-8 col-md-10').addClass('col-xs-9 col-sm-8 col-md-9');
				$('.titleColumn.col-xs-12').removeClass('col-xs-12').addClass('col-xs-11');

				$('#selectItemsBtn').hide();
				$('#deleteAllBtn').hide();
				$('#cancelSelectionBtn').show();
				$('#deleteDropdown').show();
			}

			return false;
		},

		deleteEntry(patronId, id) {
			AspenDiscovery.confirm(
				__('Delete Reading History Entry'),
				__('The item will be irreversibly deleted from your reading history. Proceed?'),
				__('Delete'),
				__('Cancel'),
				false,
				`AspenDiscovery.Account.ReadingHistory.doDeleteEntry(${patronId}, ${id})`,
				'btn-danger'
			);

			return false;
		},

		doDeleteEntry(patronId, id) {
			const url = `${Globals.path}/MyAccount/AJAX`;
			const params = {
				method: 'deleteReadingHistoryEntry',
				patronId,
				entryId: id
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params)
				.done((data) => {
					if (data.success) {
						$(`#readingHistoryEntry${id}`).hide();
						AspenDiscovery.showMessage(data.title, data.message, true);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, '', false, '', false, false, false);
					}
				})
			.fail(AspenDiscovery.ajaxFail);

			return false;
		},

		deleteGroupedEntry(patronId, groupedWorkPermanentId, title, author, displayId) {
			AspenDiscovery.confirm(
				__('Delete Reading History Entry'),
				__('All checkout records for this title will be irreversibly deleted from your reading history. Proceed?'),
				__('Delete'),
				__('Cancel'),
				false,
				`AspenDiscovery.Account.ReadingHistory.doDeleteGroupedEntry(&quot;${patronId}&quot;, &quot;${groupedWorkPermanentId}&quot;, &quot;${title}&quot;, &quot;${author}&quot;, &quot;${displayId}&quot;)`,
				'btn-danger'
			);

			return false;
		},

		doDeleteGroupedEntry(patronId, groupedWorkPermanentId, title, author, displayId) {
			const url = `${Globals.path}/MyAccount/AJAX`;
			const params = {
				method: 'deleteGroupedReadingHistoryEntry',
				patronId,
				groupedWorkPermanentId,
				title,
				author
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params)
				.done((data) => {
					if (data.success) {
						// noinspection JSUnresolvedFunction
						$(`#readingHistoryEntry${displayId}`).fadeOut();
						AspenDiscovery.showMessage(data.title, data.message, true);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, '', false, '', false, false, false);
					}
				})
			.fail(AspenDiscovery.ajaxFail);

			return false;
		},

		deleteIndividualEntry(patronId, entryId, groupId) {
			AspenDiscovery.confirm(
				__('Delete Checkout Record'),
				__('This checkout record will be irreversibly deleted from your reading history. Proceed?'),
				__('Delete'),
				__('Cancel'),
				false,
				`AspenDiscovery.Account.ReadingHistory.doDeleteIndividualEntry(&quot;${patronId}&quot;, &quot;${entryId}&quot;, &quot;${groupId}&quot;)`,
				'btn-danger'
			);

			return false;
		},

		doDeleteIndividualEntry(patronId, entryId, groupId) {
			const url = `${Globals.path}/MyAccount/AJAX`;
			const params = {
				method: 'deleteReadingHistoryEntry',
				patronId,
				entryId
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params)
				.done((data) => {
					if (data.success) {
						// noinspection JSUnresolvedFunction
						$(`#readingHistoryDetailEntry${entryId}`).fadeOut(function() {
							$(this).remove();
							const remainingRows = $(`#readingHistoryDetails${groupId} tbody tr`).length;
							if (remainingRows === 0) {
								// If no rows left, hide the entire grouped entry.
								// noinspection JSUnresolvedFunction
								$(`#readingHistoryEntry${groupId}`).fadeOut();
							} else {
								const countText = remainingRows === 1 ? 'Checked out 1 time' : `Checked out ${remainingRows} times`;
								const $readingHistoryCount = $(`#readingHistoryEntry${groupId} .reading-history-count-text`);
								$readingHistoryCount.text(countText);

								// If only one remains, hide the count badge.
								if (remainingRows === 1) {
									$readingHistoryCount.hide();
								}
							}
						});
						AspenDiscovery.showMessage(data.title, data.message, true);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, '', false, '', false, false, false);
					}
				})
			.fail(AspenDiscovery.ajaxFail);

			return false;
		},

		deleteSelectedAction() {
			const selectedItems = $('.titleSelect:checked');
			if (selectedItems.length === 0) {
				AspenDiscovery.showMessageWithButtons(__('Failed to Delete Reading History Entries'), __('Please select one or more items to delete.'), '', false, '', false, false, false);
				return false;
			}

			AspenDiscovery.confirm(
				__('Delete Selected Items'),
				__('You have selected {count} item(s) to delete from your reading history. This action is irreversible. Proceed?', { count: selectedItems.length }),
				__('Delete'),
				__('Cancel'),
				false,
				'AspenDiscovery.Account.ReadingHistory.doDeleteSelected()',
				'btn-danger'
			);
			return false;
		},

		doDeleteSelected() {
			const selectedIds = [];
			// noinspection JSCheckFunctionSignatures
			$('.titleSelect:checked').each(function() {
				const name = $(this).attr('name');
				const match = name.match(/selected\[(\d+)]/);
				if (match && match[1]) {
					selectedIds.push(match[1]);
				}
			});

			const url = `${Globals.path}/MyAccount/AJAX`;
			const params = {
				method: 'deleteSelectedReadingHistoryEntries',
				patronId: $('#patronId').val(),
				ids: selectedIds
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params)
				.done((data) => {
					if (data.success) {
						selectedIds.forEach((id) => {
							// noinspection JSUnresolvedFunction
							$(`#readingHistoryEntry${id}`).fadeOut();
						});
						AspenDiscovery.showMessageWithButtons(data.title, data.message, '', false, '', false, false, false);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title || 'Error', data.message || 'Failed to delete selected items.', '', false, '', false, false, false);
					}
				})
				.fail(AspenDiscovery.ajaxFail);
		},

		deleteAllAction() {
			AspenDiscovery.confirm(
				__('Delete All Reading History'),
				__('Your entire reading history will be irreversibly deleted. Proceed?'),
				__('Delete All'),
				__('Cancel'),
				false,
				'AspenDiscovery.Account.ReadingHistory.doDeleteAllAction()',
				'btn-danger'
			);
			return false;
		},

		doDeleteAllAction() {
			$('#readingHistoryAction').val('deleteAll');
			$('#readingListForm').trigger('submit');
			return false;
		},

		optOutAction() {
			AspenDiscovery.confirm(
				__('Opt Out of Reading History'),
				__('Opting out of Reading History will also delete your entire reading history irreversibly. Proceed?'),
				__('Opt Out'),
				__('Cancel'),
				false,
				'AspenDiscovery.Account.ReadingHistory.doOptOutAction()',
				'btn-danger'
			);
			return false;
		},

		doOptOutAction() {
			$('#readingHistoryAction').val('optOut');
			$('#readingListForm').trigger('submit');
			return false;
		},

		optInAction(){
			$('#readingHistoryAction').val('optIn');
			$('#readingListForm').trigger('submit');
			return false;
		},

		exportListAction(){
			document.location.href = Globals.path + "/MyAccount/AJAX?method=exportReadingHistory";
			return false;
		},

		initEditableReturnDates() {
			$(document)
				.off('dblclick.returnDate', 'td[data-entry-id]')
				.on('dblclick.returnDate', 'td[data-entry-id]', function(e) {
					e.preventDefault();
					const $td = $(this);
					const $display = $td.find('.date-display');
					const $input = $td.find('.date-edit');

					$td.data('value-on-edit-start', $input.val());

					$display.hide();
					$input.show().focus().select();
				});

			// Format date input as user types.
			$(document)
				.off('input.returnDate', 'td[data-entry-id] .date-edit')
				.on('input.returnDate', 'td[data-entry-id] .date-edit', function() {
					let value = $(this).val();
					const digitsOnly = value.replace(/\D/g, '');
					let formatted = digitsOnly;

					if (digitsOnly.length > 4) {
						formatted = digitsOnly.substring(0, 4) + '-' + digitsOnly.substring(4);
					}
					if (digitsOnly.length > 6) {
						formatted = formatted.substring(0, 7) + '-' + formatted.substring(7);
					}
					formatted = formatted.substring(0, 10);

					$(this).val(formatted);
				});

			$(document)
				.off('blur.returnDate', 'td[data-entry-id] .date-edit')
				.on('blur.returnDate', 'td[data-entry-id] .date-edit', function() {
					AspenDiscovery.Account.ReadingHistory.saveReturnDate($(this));
				});

			$(document)
				.off('keydown.returnDate', 'td[data-entry-id] .date-edit')
				.on('keydown.returnDate', 'td[data-entry-id] .date-edit', function(e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						AspenDiscovery.Account.ReadingHistory.saveReturnDate($(this));
					} else if (e.key === 'Escape') {
						e.preventDefault();
						const $td = $(this).closest('td[data-entry-id]');
						const $display = $td.find('.date-display');
						const $input = $td.find('.date-edit');

						// Restore original value and hide input.
						const originalDate = $td.data('edited-date') || $td.data('original-date');
						if (originalDate) {
							const dateObj = new Date(originalDate * 1000);
							$input.val(dateObj.toISOString().split('T')[0]);
						}
						$input.hide();
						$display.show();
					}
				});
		},

		saveReturnDate($input) {
			const $td = $input.closest('td[data-entry-id]');
			const $display = $td.find('.date-display');
			const entryId = $td.data('entry-id');
			const newDateStr = $input.val();
			const editedTimestamp = $td.data('edited-date');
			const originalTimestamp = $td.data('original-date');
			const valueOnEditStart = $td.data('value-on-edit-start');

			// If the value hasn't changed from when editing started, don't save.
			if (newDateStr === valueOnEditStart) {
				$input.hide();
				$display.show();
				return;
			}

			// If empty and no original/edited date exists, just cancel (no change for "Currently Checked Out").
			if (!newDateStr && !originalTimestamp && !editedTimestamp) {
				$input.hide();
				$display.show();
				return;
			}

			if (!newDateStr || !/^\d{4}-\d{2}-\d{2}$/.test(newDateStr)) {
				$input.hide();
				$display.show();
				AspenDiscovery.showMessageWithButtons('Invalid Date', 'Please enter a valid date in YYYY-MM-DD format.');
				return;
			}

			const newDate = new Date(newDateStr + 'T00:00:00');
			const newTimestamp = Math.floor(newDate.getTime() / 1000);

			// Validate that the date is not in the future.
			const now = new Date();
			now.setHours(0, 0, 0, 0);
			const todayTimestamp = Math.floor(now.getTime() / 1000);
			if (newTimestamp > todayTimestamp) {
				$input.hide();
				$display.show();
				AspenDiscovery.showMessageWithButtons('Invalid Date', 'Return date cannot be in the future.');
				return;
			}

			const currentTimestamp = (editedTimestamp && editedTimestamp !== '') ? editedTimestamp : originalTimestamp;
			if (newTimestamp === parseInt(currentTimestamp)) {
				// No change, just hide input and show display.
				$input.hide();
				$display.show();
				return;
			}

			const url = `${Globals.path}/MyAccount/AJAX`;
			const params = {
				method: 'updateReadingHistoryReturnDate',
				entryId: entryId,
				newReturnDate: newTimestamp
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params)
				.done((data) => {
					if (data.success) {
						const { formattedDate }  = data;
						$display.removeClass('label label-success');
						$display.text(formattedDate);
						$td.data('edited-date', newTimestamp);
						$input.hide();
						$display.show();

						AspenDiscovery.showMessageWithButtons(data.title, data.message);
					} else {
						$input.hide();
						$display.show();
						AspenDiscovery.showMessageWithButtons(data.title, data.message);
					}
				})
				.fail(() => {
					$input.hide();
					$display.show();
					AspenDiscovery.showMessageWithButtons('Error', 'Failed to update return date. Please try again.');
				});
		}
	};
}(AspenDiscovery.Account.ReadingHistory || {}));
