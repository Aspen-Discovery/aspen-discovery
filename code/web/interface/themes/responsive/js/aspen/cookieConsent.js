AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function(props) {
            if (props == 'all') {
                var cookieString = {
                    Essential:1,
                    Analytics:1,
                };
            } else if (props == 'essential') {
                var cookieString = {
                    Essential:1,
                    Analytics:0,
                };
            }
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
            AspenDiscovery.showMessage("Cookie Policy", Globals.cookiePolicyHTML);
            return;
        },
        fetchUserCookie: function(Values) {
            document.cookie = 'cookieConsent' + '=' + encodeURIComponent(Values) + ';  path=/';
            return;
        },
    }
}(AspenDiscovery.CookieConsent));

