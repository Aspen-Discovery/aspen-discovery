AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function() {
            console.log('cookieAgree');
            var aDate = new Date();
            aDate.setMonth(aDate.getMonth() + 3);
            $('.stripPopup').hide();
            $('.modal').modal('hide');
            document.cookie = 'cookieConsent' + '=' + encodeURIComponent(aDate) + '; expires=' + aDate.toUTCString() + '; path=/';
            return;
        },
        cookieDisagree: function() {
            console.log('cookieDisagree');  
            $('.stripPopup').hide();
            AspenDiscovery.showMessageWithButtons("Cookie Policy", Globals.cookiePolicyHTML,'<button onclick=\"AspenDiscovery.CookieConsent.cookieAgree\(\)\;\" class=\'tool btn btn-primary\' id=\'modalConsentAgree\' >Accept essential cookies</button>', true);
            return;
        }
    }
}(AspenDiscovery.CookieConsent));