AspenDiscovery.Greenhouse = (function () {
    return {
        /**
         * Submit the cover reload form via AJAX
         */
        reloadCoverSources: function () {
            // Get selected sources
            const selectedSources = [];
            $('input[name="sources[]"]:checked').each(function () {
                selectedSources.push($(this).val());
            });

            if (selectedSources.length === 0) {
                // Show error message if no sources selected
                $('#coverReloadResult').removeClass('hidden').removeClass('alert-success').addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> Please select at least one cover source to process.');
                return false;
            }

            // Show loading message
            $('#coverReloadResult').removeClass('hidden').removeClass('alert-danger').addClass('alert-info')
                .html('<i class="fas fa-spinner fa-spin fa-lg"></i> Processing selected sources. Please wait...');

            // Disable the process button
            $('#processCoversBtn').prop('disabled', true);

            // Send AJAX request
            $.getJSON('/Greenhouse/AJAX', {
                method: 'reloadCoverSources',
                sources: selectedSources
            }, function (response) {
                if (response.success) {
                    $('#coverReloadResult').removeClass('alert-info').addClass('alert-success')
                        .html(response.message);
                } else {
                    $('#coverReloadResult').removeClass('alert-info').addClass('alert-danger')
                        .html(response.message);
                }

                // Re-enable the process button
                $('#processCoversBtn').prop('disabled', false);
            }).fail(function () {
                $('#coverReloadResult').removeClass('alert-info').addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');

                // Re-enable the process button
                $('#processCoversBtn').prop('disabled', false);
            });

            return false;
        },
    };
}());
