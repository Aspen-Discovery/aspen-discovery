AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function(props) {
            if (props == 'all') {
                var cookieString = {
                    Essential:1,
                    Analytics:1,
                    UserAxis360:1,
                };
            } else if (props == 'essential') {
                var cookieString = {
                    Essential:1,
                    Analytics:0,
                    UserAxis360:0,
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
                UserAxis360:0,
            };
            var params =  {
                cookieEssential: cookieString['Essential'],
                cookieAnalytics: cookieString['Analytics'],
                cookieUserAxis360: cookieString['UserAxis360'],

			};
            $.getJSON(url, params,
                function(data) {
                    console.log('data success:', data.success);
                    console.log('DATA:', data);
                    if(data.success){
                        AspenDiscovery.showMessage("Manage Your Cookie Preferences", data.modalBody);
                    } else {
                        AspenDiscovery.showMessage("There was an error retreiving your cookie preference options");
                    }
                }
             ).fail(AspenDiscovery.ajaxFail);
            return false;
        },
        cookieManagementPreferences: function() {
            console.log('LAST FUNCTION');
            var formData = $('#cookieManagementPreferencesForm').serializeArray();
            var cookieUserAxis360 = $('#cookieUserAxis360').is(':checked') ? 1 : 0;
            formData.push({name: 'cookieUserAxis360', value: cookieUserAxis360});
       var url = Globals.path + "/AJAX/JSON?method=saveCookieManagementPreferences";

       $.getJSON(url, formData,
         function(data) {
            console.log('LAST DATA:', data);
            if(data.success) {
                AspenDiscovery.showMessage(data.message);
            } else {
                AspenDiscovery.showMessage("There was an error updating your cookie management preferences");
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

