AspenDiscovery.Greenhouse = (function () {
    return {
        runNYTUpdate: function (siteUrl) {
            $('#nytUpdateResult').removeClass('hidden').removeClass('alert-danger').addClass('alert-info')
                .html('<i class="fas fa-spinner fa-spin fa-lg"></i> Running the update. Please wait...');

            $.getJSON('/Greenhouse/AJAX', {
                method: 'runNYTUpdate',
                siteUrl: siteUrl
            }, function (response) {
                if (response.success) {
                    $('#nytUpdateResult').removeClass('alert-info').addClass('alert-success')
                        .html('<i class="fas fa-check"></i> ' + response.message);

                    // Redirect to the log page if we have a log ID
                    if (response.logId) {
                        setTimeout(function() {
                            window.location.href = '/UserLists/NYTUpdatesLog?id=' + response.logId;
                        }, 2000);
                    } else {
                        setTimeout(function() {
                            window.location.href = '/UserLists/NYTUpdatesLog';
                        }, 2000);
                    }
                } else {
                    $('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> ' + response.message);

                    // If there's output, show it
                    if (response.output) {
                        $('#nytUpdateResult').append('<pre>' + response.output + '</pre>');
                    }
                }
            }).fail(function () {
                $('#nytUpdateResult').removeClass('alert-info').addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> Error communicating with the server. Please try again.');
            });

            return false;
        }
    };
}());