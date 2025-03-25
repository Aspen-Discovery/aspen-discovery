AspenDiscovery.Greenhouse = (function () {
    let nytUpdateStatusInterval = null;
    let currentUpdateLogId = null;

    return {
        runNYTUpdate: function () {
            $('#nytUpdateResult').removeClass('hidden alert-danger').addClass('alert-info')
                .html('<i class="fas fa-spinner fa-spin fa-lg"></i> Running the update. Please wait...');

            // Disable the update button
            $('#runNytUpdateBtn').prop('disabled', true);

            // Show temporary status
            let statusHtml = '<div class="alert alert-warning">';
            statusHtml += '<h4><i class="fas fa-sync fa-spin"></i> NYT Update Starting...</h4>';
            statusHtml += '<p>' + new Date().toLocaleString() + '</p>';
            statusHtml += '<p>Please wait while the update initializes...</p>';
            statusHtml += '</div>';
            $('#nytUpdateStatus').html(statusHtml).show();

            // Track if running update is found
            let foundRunningUpdate = false;
            let statusCheckAttempts = 0;

            // Initial status check interval
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

                            AspenDiscovery.Greenhouse.startUpdateStatusCheck();
                        } else {
                            statusCheckAttempts++;
                        }
                    }
                });
            }, 1000);

            console.log("Calling JS to run NYT update.");
            $.ajax({
                url: '/Greenhouse/AJAX',
                method: 'GET',
                dataType: 'json',
                data: { method: 'runNYTUpdate' },
                success: function (response) {
                    console.log("Responded!");
                    if (response.success) {
                        $('#nytUpdateResult').removeClass('alert-info').addClass('alert-success')
                            .html('<i class="fas fa-check"></i> ' + response.message);

                        if (response.logId) {
                            currentUpdateLogId = response.logId;
                        }
                    } else {
                        $('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
                            .html('<i class="fas fa-exclamation-triangle"></i> ' + response.message);

                        $('#runNytUpdateBtn').prop('disabled', false);
                        clearInterval(initCheckInterval);
                    }
                },
                error: function () {
                    $('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');

                    $('#runNytUpdateBtn').prop('disabled', false);
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
                        // Update is running
                        currentUpdateLogId = status.logId;

                        // Update the UI to show running status
                        const elapsedTime = status.elapsedTime;
                        const minutes = Math.floor(elapsedTime / 60);
                        const seconds = elapsedTime % 60;
                        const timeDisplay = minutes + 'm ' + seconds + 's';

                        let statusHtml = '<div class="alert ' + (status.haltRequested ? 'alert-warning' : 'alert-info') + '">';

                        if (status.haltRequested) {
                            statusHtml += '<h4><i class="fas fa-hand-stop-o"></i> NYT Update Halting</h4>';
                            statusHtml += '<p class="text-warning">A halt has been requested. The update will stop at the next safe point.</p>';
                        } else {
                            statusHtml += '<h4><i class="fas fa-sync fa-spin"></i> NYT Update Running</h4>';
                        }

                        statusHtml += '<div class="row">';

                        // Left column - Status info
                        statusHtml += '<div class="col-xs-12 col-sm-7">';
                        statusHtml += '<p><strong>Started:</strong> ' + new Date(status.startTime * 1000).toLocaleString() + '</p>';
                        statusHtml += '<p><strong>Running for:</strong> ' + timeDisplay + '</p>';

                        if (status.numLists) {
                            statusHtml += '<p><strong>Processing:</strong> ' + status.numLists + ' lists</p>';
                        }

                        // Add halt button only if not already halting
                        if (!status.haltRequested) {
                            statusHtml += '<button class="btn btn-danger" onclick="return AspenDiscovery.Greenhouse.haltNYTUpdate(' + status.logId + ');">';
                            statusHtml += '<i class="fas fa-stop-circle"></i> Halt Update</button> ';
                        } else {
                            statusHtml += '<button class="btn btn-danger" disabled>';
                            statusHtml += '<i class="fas fa-stop-circle"></i> Halting...</button> ';
                        }

                        statusHtml += '</div>';

                        $('#nytUpdateStatus').html(statusHtml).show();

                        // Disable the update button
                        $('#runNytUpdateBtn').prop('disabled', true).attr('title', 'An update is already running');
                    } else {
                        // No update running
                        $('#nytUpdateStatus').html('').hide();

                        // Enable the update button
                        $('#runNytUpdateBtn').prop('disabled', false).attr('title', 'Run NYT Lists Update');

                        // Stop checking for updates
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
            AspenDiscovery.Greenhouse.checkNYTUpdateStatus();

            if (nytUpdateStatusInterval === null) {
                nytUpdateStatusInterval = setInterval(AspenDiscovery.Greenhouse.checkNYTUpdateStatus, 1000);
            }
        },

        /**
         * Halt a running NYT update
         */
        haltNYTUpdate: function(logId) {
            AspenDiscovery.Modals.showConfirmActionModal({
                title: 'Confirm Halt',
                prompt: 'Are you sure you want to halt the running update?',
                description: 'The update will be stopped at the next safe point.',
                confirmLabel: 'Halt Update',
                confirmButtonClass: 'btn-danger',
                onConfirm: function() {
                    // Execute the halt request after confirmation
                    AspenDiscovery.Greenhouse.executeHaltNYTUpdate(logId);
                }
            });

            return false;
        },

        /**
         * Execute the halt request after confirmation
         */
        executeHaltNYTUpdate: function(logId) {
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
                    AspenDiscovery.Greenhouse.checkNYTUpdateStatus();
                } else {
                    AspenDiscovery.showMessage('Error', response.message, false);
                }
            }).fail(function() {
                AspenDiscovery.showMessage('Error', 'Error communicating with the server. Please try again.', false);
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
        }
    };
}());

// Check for updates when the page loads
$(document).ready(function() {
    if ($('#nytUpdateStatus').length) {
        AspenDiscovery.Greenhouse.startUpdateStatusCheck();
    }
});