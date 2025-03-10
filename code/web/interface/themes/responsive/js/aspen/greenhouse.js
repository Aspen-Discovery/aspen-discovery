/**
 * Aspen Discovery Greenhouse JavaScript Module
 */

AspenDiscovery.Greenhouse = (function () {
    return {
        /**
         * Submit the cover reload form via AJAX.
         */
        reloadCoverSources: function () {
            const selectedSources = $('input[name="sources[]"]:checked').map(function () {
                return $(this).val();
            }).get();

            const $coverReloadResult = $('#coverReloadResult');
            const $processCoversBtn = $('#processCoversBtn');

            if (selectedSources.length === 0) {
                $coverReloadResult
                    .removeClass('hidden alert-success alert-info')
                    .addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> Please select at least one cover source to process.');
                return false;
            }

            $coverReloadResult
                .removeClass('hidden alert-danger alert-success')
                .addClass('alert-info')
                .html('<i class="fas fa-spinner fa-spin fa-lg"></i> Processing selected sources. Please wait...');

            $processCoversBtn.prop('disabled', true);

            $.ajax({
                url: '/Greenhouse/AJAX',
                dataType: 'json',
                method: 'POST',
                data: {
                    method: 'reloadCoverSources',
                    sources: selectedSources
                }
            })
                .done(function (response) {
                    if (response.success) {
                        $coverReloadResult
                            .removeClass('alert-info alert-danger')
                            .addClass('alert-success')
                            .html(response.message);
                    } else {
                        $coverReloadResult
                            .removeClass('alert-info alert-success')
                            .addClass('alert-danger')
                            .html(response.message);
                    }
                })
                .fail(function () {
                    $coverReloadResult
                        .removeClass('alert-info alert-success')
                        .addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');
                })
                .always(function () {
                    $processCoversBtn.prop('disabled', false);
                });

            return false;
        },
    };
}());