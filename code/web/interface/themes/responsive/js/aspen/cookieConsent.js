$(document).ready(function () {
    var aDate = new Date();
    aDate.setMonth(aDate.getMonth() + 3);
    $(document).on('click', '#consentAgree, #modalConsentAgree', function (event) {
        event.preventDefault();
        $('.stripPopup').hide();
        $('.modal').modal('hide');
        document.cookie = 'cookieConsent' + '=' +encodeURIComponent(aDate)+'; expires=' + aDate.toUTCString() + '; path=/';
    });
    $('#consentDisagree').click(function() {
        $('.stripPopup').hide();
        AspenDiscovery.showMessageWithButtons("Cookie Policy",cookiePolicyHTML,'<button class=\'tool btn btn-primary\' id=\'modalConsentAgree\' >Accept essential cookies</button>', true);
    });
});
