AspenDiscovery.Modals = (function () {
	return {
		/**
		 * Configure and show the confirm action modal
		 *
		 * @param {Object} options Configuration options
		 * @param {string} options.title Modal title
		 * @param {string} options.prompt Main prompt text
		 * @param {string} options.description Additional description (optional)
		 * @param {string} options.confirmLabel Text for confirmation button (defaults to "Confirm")
		 * @param {string} options.confirmButtonClass CSS class for confirmation button (defaults to "btn-danger")
		 * @param {Function} options.onConfirm Callback function to execute when confirmed
		 */
		showConfirmActionModal: function (options) {
			$('#confirmActionModalTitle').text(options.title || 'Confirm Action');
			$('#confirmActionModalPrompt').text(options.prompt || 'Are you sure you want to perform this action?');

			if (options.description) {
				$('#confirmActionModalDescription small').text(options.description);
				$('#confirmActionModalDescription').show();
			} else {
				$('#confirmActionModalDescription').hide();
			}

			$('#confirmActionBtn')
				.text(options.confirmLabel || 'Confirm')
				.removeClass()
				.addClass('btn ' + (options.confirmButtonClass || 'btn-danger'))
				.off('click')
				.on('click', function () {
					if (typeof options.onConfirm === 'function') {
						options.onConfirm();
					}
					$('#confirmActionModal').modal('hide');
				});

			$('#confirmActionModal').modal('show');
		},
	};

}());