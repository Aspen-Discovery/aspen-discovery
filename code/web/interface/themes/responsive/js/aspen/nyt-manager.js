AspenDiscovery.NYTManager = (function () {
    let nytUpdateStatusInterval = null;
    let currentUpdateLogId = null;
    const MAX_STATUS_CHECK_ATTEMPTS = 10; // Max attempts to find a running update.

    return {
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
                    data: { method: 'getNYTUpdateStatus' },
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
                                    $('#nytStatusDetails').html('<p>The system is still processing your update request.</p>' +
                                        '<p>If the update doesn\'t start within a minute, please check the logs or try again.</p>');
                                }
                            }
                        }
                    }
                });
            }, 1000);

            $.ajax({
                url: '/Greenhouse/AJAX',
                method: 'GET',
                dataType: 'json',
                timeout: 30000, // 30 seconds
                data: { method: 'runNYTUpdate' },
                success: function (response) {
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
                },
                error: function () {
                    $('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');

                    AspenDiscovery.NYTManager.updateButtonState('normal');
                    clearInterval(initCheckInterval);
                }
            });
        },

        /**
         * Check the status of any running NYT update
         */
        checkNYTUpdateStatus: function() {
            $.getJSON('/Greenhouse/AJAX', {
                method: 'getNYTUpdateStatus'
            }, function(response) {
                if (response.success) {
                    const status = response.status;

                    if (status.isRunning) {
                        currentUpdateLogId = status.logId;

                        const elapsedTime = status.elapsedTime;
                        const minutes = Math.floor(elapsedTime / 60);
                        const seconds = elapsedTime % 60;
                        const timeDisplay = minutes + 'm ' + seconds + 's';

                        // Check if status div exists, create if not.
                        if ($('#nytUpdateStatus div.alert').length === 0) {
                            // Create the initial structure
                            let statusHtml = '<div class="alert ' + (status.haltRequested ? 'alert-warning' : 'alert-info') + '">';
                            statusHtml += '<h4><i id="nytStatusIcon" class="' + (status.haltRequested ? '' : 'fas fa-sync fa-spin') + '"></i>';
                            statusHtml += '<span id="nytStatusTitle">' + (status.haltRequested ? 'NYT Update Halting' : ' NYT Update Running') + '</span></h4>';
                            statusHtml += '<div id="nytStatusMessage">' + (status.haltRequested ? '<p class="text-warning">A halt has been requested. The update will stop at the next safe point.</p>' : '') + '</div>';
                            statusHtml += '<div id="nytStatusContent" class="row">';
                            statusHtml += '<div id="nytStatusDetails" class="col-xs-12 col-sm-7">';
                            statusHtml += '<div id="nytStatusControls"></div>';
                            statusHtml += '</div>';
                            $('#nytUpdateStatus').html(statusHtml).show();
                        } else {
                            $('#nytUpdateStatus div.alert').removeClass('alert-info alert-warning')
                                .addClass(status.haltRequested ? 'alert-warning' : 'alert-info');

                            $('#nytStatusIcon').removeClass('fas fa-sync fa-spin')
                                .addClass(status.haltRequested ? '' : 'fas fa-sync fa-spin');
                            $('#nytStatusTitle').text(status.haltRequested ? 'NYT Update Halting' : ' NYT Update Running');

                            if (status.haltRequested) {
                                if ($('#nytStatusMessage').length === 0) {
                                    $('<div id="nytStatusMessage"><p class="text-warning">A halt has been requested. The update will stop at the next safe point.</p></div>')
                                        .insertAfter($('#nytStatusTitle').closest('h4'));
                                }
                            } else {
                                $('#nytStatusMessage').remove();
                            }
                        }

                        let detailsHtml = '<p><strong>Started:</strong> ' + new Date(status.startTime * 1000).toLocaleString() + '</p>';
                        detailsHtml += '<p><strong>Running for:</strong> ' + timeDisplay + '</p>';
                        if (status.numLists) {
                            detailsHtml += '<p><strong>Processing:</strong> ' + status.numLists + ' lists</p>';
                        }
                        $('#nytStatusDetails').html(detailsHtml);

                        let controlsHtml = '';
                        if (!status.haltRequested) {
                            controlsHtml += '<button class="btn btn-danger" onclick="return AspenDiscovery.NYTManager.haltNYTUpdate(' + status.logId + ');">';
                            controlsHtml += '<i class="fas fa-stop-circle"></i> Halt Update</button> ';
                        } else {
                            controlsHtml += '<button class="btn btn-danger" disabled>';
                            controlsHtml += '<i class="fas fa-stop-circle"></i> Halting...</button> ';
                        }
                        $('#nytStatusControls').html(controlsHtml);

                        AspenDiscovery.NYTManager.updateButtonState('running');
                    } else {
                        $('#nytUpdateStatus').html('').hide();

                        AspenDiscovery.NYTManager.updateButtonState('normal');

                        if (nytUpdateStatusInterval !== null) {
                            clearInterval(nytUpdateStatusInterval);
                            nytUpdateStatusInterval = null;
                        }
                    }
                }
            });
        },

        /**
         * Start checking for updates periodically
         */
        startUpdateStatusCheck: function() {
            // Check immediately
            AspenDiscovery.NYTManager.checkNYTUpdateStatus();

            if (nytUpdateStatusInterval === null) {
                nytUpdateStatusInterval = setInterval(AspenDiscovery.NYTManager.checkNYTUpdateStatus, 1000);
            }
        },

        /**
         * Halt a running NYT update
         */
        haltNYTUpdate: function(logId) {
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

            AspenDiscovery.showMessageWithButtons(
                'Confirm Halt',
                modalBody,
                modalButtons,
                false,
                undefined,
                false,
                false,
                true
            );

            return false;
        },

        /**
         * Execute the halt request after confirmation
         */
        executeHaltNYTUpdate: function(logId) {
            // First check if the update is still running
            $.getJSON('/Greenhouse/AJAX', {
                method: 'getNYTUpdateStatus'
            }, function(statusResponse) {
                if (statusResponse.success && statusResponse.status && statusResponse.status.isRunning) {
                    // Update is still running, proceed with halt
                    $.getJSON('/Greenhouse/AJAX', {
                        method: 'haltNYTUpdate',
                        logId: logId
                    }, function(response) {
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
                    }).fail(function() {
                        AspenDiscovery.showMessage('Error', 'Error communicating with the server. Please try again.', false);
                    });
                } else {
                    // Update is no longer running
                    AspenDiscovery.showMessage('Notice', 'The update is no longer running. It may have completed or been halted already.', true);
                    // Refresh status display
                    AspenDiscovery.NYTManager.checkNYTUpdateStatus();
                }
            }).fail(function() {
                AspenDiscovery.showMessage('Error', 'Could not verify update status. Please refresh the page and try again.', false);
            });
        },

        /**
         * Toggle settings for the NYT updater
         */
        toggleNYTSetting: function(setting, element) {
            const isChecked = $(element).is(':checked');
            const settingData = {};
            settingData[setting] = isChecked;

            $.getJSON('/Greenhouse/AJAX', {
                method: 'saveNYTSettings',
                [setting]: isChecked
            }, function(response) {
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
            }).fail(function() {
                AspenDiscovery.showMessage('Error', 'Error communicating with the server. Please try again.', false);
                // Reset toggle state.
                $(element).prop('checked', !isChecked);
            });
        },

        /**
         * Update the state and text of the NYT update button.
         * @param {string} state - Either 'running' or 'normal'.
         */
        updateButtonState: function(state) {
            const $updateBtn = $('#runNytUpdateBtn');

            if (state === 'running') {
                $updateBtn.prop('disabled', true)
                $updateBtn.contents().filter(function() {
                    return this.nodeType === 3; // Text node
                }).replaceWith(' ' + $updateBtn.data('running-text'));
            } else {
                $updateBtn.prop('disabled', false)
                $updateBtn.contents().filter(function() {
                    return this.nodeType === 3; // Text node
                }).replaceWith(' ' + $updateBtn.data('original-text').split('</i>')[1]);
            }
        },
    };
}());

// Check for updates when the page loads.
$(function() {
    if ($('#nytUpdateStatus').length) {
        AspenDiscovery.NYTManager.startUpdateStatusCheck();
    }
});