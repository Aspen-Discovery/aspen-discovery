AspenDiscovery.CookieConsent = (function() {
    return {
        cookieAgree: function(props) {
            if (props == 'all') {
                var cookieString = {
                    Essential:1,
                    Analytics:1,
                    UserAxis360:1,
                    UserEbscoEds:1,
                    UserEbscoHost:1,
                    UserSummon:1,
                    UserEvents:1,
                    UserHoopla:1,
                    UserOpenArchives:1,
                    UserOverdrive:1,
                    UserPalaceProject:1,
                    UserSideLoad:1,
                    UserCloudLibrary:1,
                    UserWebsite:1,
                };
            } else if (props == 'essential') {
                var cookieString = {
                    Essential:1,
                    Analytics:0,
                    UserAxis360:0,
                    UserEbscoEds:0,
                    UserEbscoHost:0,
                    UserSummon:0,
                    UserEvents:0,
                    UserHoopla:0,
                    UserOpenArchives:0,
                    UserOverdrive:0,
                    UserPalaceProject:0,
                    UserSideLoad:0,
                    UserCloudLibrary:0,
                    UserWebsite:0,
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
                UserEbscoEds:0,
                UserEbscoHost:0,
                UserSummon:0,
                UserEvents:0,
                UserHoopla:0,
                UserOpenArchives:0,
                UserOverdrive:0,
                UserPalaceProject:0,
                UserSideLoad:0,
                UserCloudLibrary:0,
                UserWebsite:0,
            };
            var params =  {
                cookieEssential: cookieString['Essential'],
                cookieAnalytics: cookieString['Analytics'],
                cookieUserAxis360: cookieString['UserAxis360'],
                cookieUserEbscoEds: cookieString['UserEbscoEds'],
                cookieUserEbscoHost: cookieString['UserEbscoHost'],
                cookieUserSummon: cookieString['UserSummon'],
                cookieUserEvents: cookieString['UserEvents'],
                cookieUserHoopla: cookieString['UserHoopla'],
                cookieUserOpenArchives: cookieString['UserOpenArchives'],
                cookieUserOverdrive: cookieString['UserOverdrive'],
                cookieUserPalaceProject: cookieString['UserPalaceProject'],
                cookieUserSideLoad: cookieString['UserSideLoad'],
                cookieUserCloudLibrary: cookieString['UserCloudLibrary'],
                cookieUserWebsite: cookieString['UserWebsite'],
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
            var cookieEssential = $('#cookieEssential').is(':checked') ? 1 : 0;
            var cookieAnalytics = $('#cookieAnalytics').is(':checked') ? 1 : 0;
            var cookieUserAxis360 = $('#cookieUserAxis360').is(':checked') ? 1 : 0;
            var cookieUserEbscoEds = $('#cookieUserEbscoEds').is(':checked') ? 1 : 0;
            var cookieUserEbscoHost = $('#cookieUserEbscoHost').is(':checked') ? 1 : 0;
            var cookieUserSummon = $('#cookieUserSummon').is(':checked') ? 1 : 0;
            var cookieUserEvents = $('#cookieUserEvents').is(':checked') ? 1 : 0;
            var cookieUserHoopla = $('#cookieUserHoopla').is(':checked') ? 1 : 0;
            var cookieUserOpenArchives = $('#cookieUserOpenArchives').is(':checked') ? 1 : 0;
            var cookieUserOverdrive = $('#cookieUserOverdrive').is(':checked') ? 1 : 0;
            var cookieUserPalaceProject = $('#cookieUserPalaceProject').is(':checked') ? 1 : 0;
            var cookieUserSideLoad = $('#cookieUserSideLoad').is(':checked') ? 1 : 0;
            var cookieUserCloudLibrary = $('#cookieUserCloudLibrary').is(':checked') ? 1 : 0;
            var cookieUserWebsite = $('#cookieUserWebsite').is(':checked') ? 1 : 0;

            formData.push({name: 'cookieEssential', value: cookieEssential});
            formData.push({name: 'cookieAnalytics', value: cookieAnalytics});
            formData.push({name: 'cookieUserAxis360', value: cookieUserAxis360});
            formData.push({name: 'cookieUserEbscoEds', value: cookieUserEbscoEds });
            formData.push({name: 'cookieUserEbscoHost', value: cookieUserEbscoHost});
            formData.push({name: 'cookieUserSummon', value: cookieUserSummon});
            formData.push({name: 'cookieUserEvents', value: cookieUserEvents});
            formData.push({name: 'cookieUserHoopla', value: cookieUserHoopla});
            formData.push({name: 'cookieUserOpenArchives', value: cookieUserOpenArchives});
            formData.push({name: 'cookieUserOverdrive', value: cookieUserOverdrive});
            formData.push({name: 'cookieUserPalaceProject', value: cookieUserPalaceProject});
            formData.push({name: 'cookieUserSideLoad', value: cookieUserSideLoad});
            formData.push({name: 'cookieUserCloudLibrary', value: cookieUserCloudLibrary});
            formData.push({name: 'cookieUserWebsite', value: cookieUserWebsite});
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

