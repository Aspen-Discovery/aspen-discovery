AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function(props) {
            if (props == 'all') {
                var cookieString = {
                    Essential:1,
                    Analytics:1,
                    UserEvents:1,
                    UserOpenArchives:1,
                    UserWebsite:1,
                    UserExternalSearchServices:1,
                };
            } else if (props == 'essential') {
                var cookieString = {
                    Essential:1,
                    Analytics:0,
                    UserEvents:0,
                    UserOpenArchives:0,
                    UserWebsite:0,
                    UserExternalSearchServices:0,
                };
            }
            $('.stripPopup').hide();
            $('.modal').modal('hide');
            //set cookie and update db (if logged in) with AJAX
			var url = Globals.path + "/AJAX/JSON";
			var params =  {
				method : 'saveCookiePreference',
                cookieEssential: cookieString['Essential'],
                cookieAnalytics: cookieString['Analytics']
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
        cookieManage: function() {
            var url = Globals.path + "/AJAX/JSON?method=manageCookiePreferences";
            var cookieString = {
                Essential:1,
                Analytics:0,
                UserEvents:0,
                UserOpenArchives:0,
                UserWebsite:0,
                UserExternalSearchServices:0,
            };
            var params =  {
                cookieEssential: cookieString['Essential'],
                cookieAnalytics: cookieString['Analytics'],
                cookieUserEvents: cookieString['UserEvents'],
                cookieUserOpenArchives: cookieString['UserOpenArchives'],
                cookieUserWebsite: cookieString['UserWebsite'],
                cookieUserExternalSearchServices: cookieString['UserExternalSearchServices'],
			};
            $.getJSON(url, params,
                function(data) {
                    if(data.success){
                        AspenDiscovery.showMessageWithButtons("Manage Your Cookie & Analytics Preferences", data.modalBody, data.modalButtons);
                        $('.stripPopup').hide();
                    } else {
                        AspenDiscovery.showMessage("There was an error retreiving your preferences");
                    }
                }
             ).fail(AspenDiscovery.ajaxFail);
            return false;
        },
        cookieManagementPreferences: function() {
            var formData = $('#cookieManagementPreferencesForm').serializeArray();
            var cookieEssential = $('#cookieEssential').is(':checked') ? 1 : 0;
            var cookieAnalytics = $('#cookieAnalytics').is(':checked') ? 1 : 0;
            var cookieUserEvents = $('#cookieUserEvents').is(':checked') ? 1 : 0;
            var cookieUserOpenArchives = $('#cookieUserOpenArchives').is(':checked') ? 1 : 0;
            var cookieUserWebsite = $('#cookieUserWebsite').is(':checked') ? 1 : 0;
            var cookieUserExternalSearchServices = $('#cookieUserExternalSearchServices').is(':checked') ? 1 : 0;

            formData.push({name: 'cookieEssential', value: cookieEssential});
            formData.push({name: 'cookieAnalytics', value: cookieAnalytics});
            formData.push({name: 'cookieUserEvents', value: cookieUserEvents});
            formData.push({name: 'cookieUserOpenArchives', value: cookieUserOpenArchives});
            formData.push({name: 'cookieUserWebsite', value: cookieUserWebsite});
            formData.push({name: 'cookieUserExternalSearchServices', value: cookieUserExternalSearchServices});
             var url = Globals.path + "/AJAX/JSON?method=saveCookieManagementPreferences";

       $.getJSON(url, formData,
         function(data) {
            if(data.success) {
                AspenDiscovery.showMessage(data.message);
            } else {
                AspenDiscovery.showMessage("There was an error updating your preferences");
            }
         }
        ).fail(AspenDiscovery.ajaxFail);
        return false;
       },
        fetchUserCookie: function(Values) {
            document.cookie = 'cookieConsent' + '=' + encodeURIComponent(Values) + ';  path=/';
            return;
        }
    }
}(AspenDiscovery.CookieConsent));

