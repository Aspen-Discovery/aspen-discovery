AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function() {
            console.log('cookieAgree');
            var aDate = new Date();
            aDate.setMonth(aDate.getMonth() + 3);
            $('.stripPopup').hide();
            $('.modal').modal('hide');
            //set cookie and update db (if logged in) with AJAX
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'saveCookiePreference',
                cookieEssential: cookieString['Essential'],
                cookieAnalytics: cookieString['Analytics'],
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						if (data.message.length > 0){
							//User was logged in, show a message about how to update
							AspenDiscovery.showMessage('Success', data.message, true, true);
						}else{
							//Refresh the page
							// noinspection SillyAssignmentJS
							window.location.href = window.location.href;
						}
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
        },
        cookieDisagree: function() {
            console.log('cookieDisagree');  
            $('.stripPopup').hide();
            AspenDiscovery.showMessageWithButtons("Cookie Policy", Globals.cookiePolicyHTML,'<button onclick=\"AspenDiscovery.CookieConsent.cookieAgree\(\)\;\" class=\'tool btn btn-primary\' id=\'modalConsentAgree\' >Accept essential cookies</button>', true);
            return;
        }
    }
}(AspenDiscovery.CookieConsent));