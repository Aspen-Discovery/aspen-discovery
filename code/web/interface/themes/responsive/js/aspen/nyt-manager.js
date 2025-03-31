/**
 * Aspen Discovery NYT List Manager JavaScript Module
 */

AspenDiscovery.NYTManager = (function () {
	let nytUpdateStatusInterval = null;
	let currentUpdateLogId = null;
	const MAX_STATUS_CHECK_ATTEMPTS = 10; // Max attempts to find a running update.

	return {
		/**
		 * Run a NYT List update.
		 */
		runNYTUpdate: function () {
			$('#nytUpdateResult').removeClass('hidden alert-danger').addClass('alert-info')
				.html('<i class="fas fa-spinner fa-spin fa-lg"></i> Running the update. Please wait...');

			AspenDiscovery.NYTManager.updateButtonState('running');

			let statusHtml = '<div class="alert alert-warning">';
			statusHtml += '<h4><i id="nytStatusIcon" class="fas fa-sync fa-spin"></i> <span id="nytStatusTitle">NYT Update Starting...</span></h4>';
			statusHtml += '<div id="nytStatusDetails">Please wait while the update initializes...</div>';
			statusHtml += '<div id="nytStatusControls"></div>';
			statusHtml += '</div>';
			$('#nytUpdateStatus').html(statusHtml).show();

			// Track if running update is found.
			let foundRunningUpdate = false;
			let statusCheckAttempts = 0;

			// Initial status check interval.
			const initCheckInterval = setInterval(function () {
				$.ajax({
					url: '/Greenhouse/AJAX',
					method: 'GET',
					dataType: 'json',
					data: {method: 'getNYTUpdateStatus'},
					success: function (response) {
						if (response.success && response.status.isRunning) {
							foundRunningUpdate = true;
							currentUpdateLogId = response.status.logId;
							clearInterval(initCheckInterval);

							AspenDiscovery.NYTManager.startUpdateStatusCheck();
						} else {
							statusCheckAttempts++;
							// Stop checking after MAX_STATUS_CHECK_ATTEMPTS if no running update found.
							if (statusCheckAttempts >= MAX_STATUS_CHECK_ATTEMPTS && !foundRunningUpdate) {
								clearInterval(initCheckInterval);
								if (!foundRunningUpdate) {
									// Update only the parts that need to change
									$('#nytStatusIcon').removeClass('fa-sync fa-spin').addClass('fa-exclamation-circle');
									$('#nytStatusTitle').text('Waiting for Update');
									$('#nytStatusDetails').html('<p>The system is still processing your update request.</p>' + '<p>If the update doesn\'t start within a minute, please check the logs or try again.</p>');
								}
							}
						}
					}
				});
			}, 1000);

			$.ajax({
				url: '/Greenhouse/AJAX', method: 'GET', dataType: 'json', timeout: 30000, // 30 seconds
				data: {method: 'runNYTUpdate'}, success: function (response) {
					if (response.success) {
						$('#nytUpdateResult').removeClass('alert-info');
						$('#forceFullUpdate').prop('checked', false);
						$('#enableExtensiveLogging').prop('checked', false);

						if (response.logId) {
							currentUpdateLogId = response.logId;
						}
					} else {
						$('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
							.html('<i class="fas fa-exclamation-triangle"></i> ' + response.message);

						AspenDiscovery.NYTManager.updateButtonState('normal');
						clearInterval(initCheckInterval);
					}
				}, error: function () {
					$('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
						.html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');

					AspenDiscovery.NYTManager.updateButtonState('normal');
					clearInterval(initCheckInterval);
				}
			});
		},

		/**
		 * Check the status of any running NYT List update.
		 */
		checkNYTUpdateStatus() {
			$.getJSON('/Greenhouse/AJAX', {method: 'getNYTUpdateStatus'}, (response) => {
				if (!response.success) return;

				const status = response.status;
				const $nytUpdateStatus = $('#nytUpdateStatus');

				if (!status.isRunning) {
					$nytUpdateStatus.empty().hide();
					AspenDiscovery.NYTManager.updateButtonState('normal');

					if (nytUpdateStatusInterval !== null) {
						clearInterval(nytUpdateStatusInterval);
						nytUpdateStatusInterval = null;
					}
					return;
				}

				currentUpdateLogId = status.logId;
				const elapsedTime = status.elapsedTime;
				const minutes = Math.floor(elapsedTime / 60);
				const seconds = elapsedTime % 60;
				const timeDisplay = `${minutes}m ${seconds}s`;

				const alertClass = status.haltRequested ? 'alert-warning' : 'alert-info';
				const iconClass = status.haltRequested ? '' : 'fas fa-sync fa-spin';
				const titleText = status.haltRequested ? 'NYT Update Halting' : ' NYT Update Running';
				const haltMessageHtml = '<p class="text-warning">A halt has been requested. The update will stop at the next safe point.</p>';

				const $alertDiv = $nytUpdateStatus.find('div.alert');

				if ($alertDiv.length === 0) {
					const statusHtml = `
                        <div class="alert ${alertClass}">
                            <h4>
                                <i id="nytStatusIcon" class="${iconClass}"></i>
                                <span id="nytStatusTitle">${titleText}</span>
                            </h4>
                            <div id="nytStatusMessage">
                                ${status.haltRequested ? haltMessageHtml : ''}
                            </div>
                            <div id="nytStatusContent" class="row">
                                <div id="nytStatusDetails" class="col-xs-12 col-sm-7">
                                    <div id="nytStatusControls"></div>
                                </div>
                            </div>
                        </div>
                    `;
					$nytUpdateStatus.html(statusHtml).show();
				} else {
					const $statusIcon = $('#nytStatusIcon');
					const $statusTitle = $('#nytStatusTitle');
					const $statusMessage = $('#nytStatusMessage');

					$alertDiv.removeClass('alert-info alert-warning').addClass(alertClass);
					$statusIcon.removeClass('fas fa-sync fa-spin').addClass(iconClass);
					$statusTitle.text(titleText);

					if (status.haltRequested) {
						if ($statusMessage.length === 0) {
							$('<div id="nytStatusMessage"></div>')
								.html(haltMessageHtml)
								.insertAfter($statusTitle.closest('h4'));
						}
					} else {
						$statusMessage.remove();
					}
				}

				const detailsHtml = `
                    <p><strong>Started:</strong> ${new Date(status.startTime * 1000).toLocaleString()}</p>
                    <p><strong>Running for:</strong> ${timeDisplay}</p>
                    ${status.numLists ? `<p><strong>Processing:</strong> ${status.numLists} lists</p>` : ''}
                `;
				$('#nytStatusDetails').html(detailsHtml);

				const controlsHtml = status.haltRequested ? `<button class="btn btn-danger" disabled>
                            <i class="fas fa-stop-circle"></i> Halting...
                       </button>` : `<button class="btn btn-danger" onclick="return AspenDiscovery.NYTManager.haltNYTUpdate(${status.logId});">
                        <i class="fas fa-stop-circle"></i> Halt Update
                       </button>
                    `;
				$('#nytStatusControls').html(controlsHtml);

				AspenDiscovery.NYTManager.updateButtonState('running');
			});
		},

		/**
		 * Start checking for NYT List updates periodically.
		 */
		startUpdateStatusCheck: function () {
			// Check immediately
			AspenDiscovery.NYTManager.checkNYTUpdateStatus();

			if (nytUpdateStatusInterval === null) {
				nytUpdateStatusInterval = setInterval(AspenDiscovery.NYTManager.checkNYTUpdateStatus, 1000);
			}
		},

		/**
		 * Halt a running NYT List update.
		 */
		haltNYTUpdate: function (logId) {
			const modalBody = `
                <p class="lead">Are you sure you want to halt the running update? The update will be stopped at the next safe point.</p>
            `;

			const modalButtons = `
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" 
                        class="btn btn-danger" 
                        onclick="AspenDiscovery.NYTManager.executeHaltNYTUpdate(${logId}); $('#modalDialog').modal('hide');">
                    Halt Update
                </button>
            `;

			AspenDiscovery.showMessageWithButtons('Confirm Halt', modalBody, modalButtons, false, undefined, false, false, true);

			return false;
		},

		/**
		 * Execute the halt request after confirmation.
		 */
		executeHaltNYTUpdate: function (logId) {
			// First check if the update is still running
			$.getJSON('/Greenhouse/AJAX', {
				method: 'getNYTUpdateStatus'
			}, function (statusResponse) {
				if (statusResponse.success && statusResponse.status && statusResponse.status.isRunning) {
					// Update is still running, proceed with halt
					$.getJSON('/Greenhouse/AJAX', {
						method: 'haltNYTUpdate', logId: logId
					}, function (response) {
						if (response.success) {
							let message = response.message;
							if (response.details) {
								message += '<br/><small>' + response.details + '</small>';
							}
							AspenDiscovery.showMessage('Update Halted', message, true);
							// Refresh status
							AspenDiscovery.NYTManager.checkNYTUpdateStatus();
						} else {
							AspenDiscovery.showMessage('Error', response.message, false);
						}
					}).fail(function () {
						AspenDiscovery.showMessage('Error', 'Error communicating with the server. Please try again.', false);
					});
				} else {
					// Update is no longer running
					AspenDiscovery.showMessage('Notice', 'The update is no longer running. It may have completed or been halted already.', true);
					// Refresh status display
					AspenDiscovery.NYTManager.checkNYTUpdateStatus();
				}
			}).fail(function () {
				AspenDiscovery.showMessage('Error', 'Could not verify update status. Please refresh the page and try again.', false);
			});
		},

		/**
		 * Toggle settings for the NYT updater.
		 */
		toggleNYTSetting: function (setting, element) {
			const isChecked = $(element).is(':checked');
			const settingData = {};
			settingData[setting] = isChecked;

			$.getJSON('/Greenhouse/AJAX', {
				method: 'saveNYTSettings', [setting]: isChecked
			}, function (response) {
				if (response.success) {
					// Refresh the toggle state based on actual server value.
					if (response.settings && response.settings[setting] !== undefined) {
						$(element).prop('checked', response.settings[setting]);
					}
				} else {
					AspenDiscovery.showMessage('Error', response.message, false);
					// Reset toggle state.
					$(element).prop('checked', !isChecked);
				}
			}).fail(function () {
				AspenDiscovery.showMessage('Error', 'Error communicating with the server. Please try again.', false);
				// Reset toggle state.
				$(element).prop('checked', !isChecked);
			});
		},

		/**
		 * Update the state and text of the NYT update button.
		 * @param {string} state - Either 'running' or 'normal'.
		 */
		updateButtonState: function (state) {
			const $updateBtn = $('#runNytUpdateBtn');

			if (state === 'running') {
				$updateBtn.prop('disabled', true)
				$updateBtn.contents().filter(function () {
					return this.nodeType === 3; // Text node
				}).replaceWith(' ' + $updateBtn.data('running-text'));
			} else {
				$updateBtn.prop('disabled', false)
				$updateBtn.contents().filter(function () {
					return this.nodeType === 3; // Text node
				}).replaceWith(' ' + $updateBtn.data('original-text').split('</i>')[1]);
			}
		},
	};
}());

// Check for updates when the page loads.
$(function () {
	if ($('#nytUpdateStatus').length) {
		AspenDiscovery.NYTManager.startUpdateStatusCheck();
	}
});