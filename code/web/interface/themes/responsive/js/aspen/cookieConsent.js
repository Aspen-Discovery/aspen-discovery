AspenDiscovery.CookieConsent = (function() {
	return {
		cookieAgree: function(props) {
			if (props == 'all') {
				var cookieString = {
					Essential:1,
					Analytics:1,
					UserLocalAnalytics:1,
				};
			} else if (props == 'essential') {
				var cookieString = {
					Essential:1,
					Analytics:0,
					UserLocalAnalytics:0,
				};
			}
			$('.stripPopup').hide();
			$('.modal').modal('hide');
			//set cookie and update db (if logged in) with AJAX
			var url = Globals.path + "/AJAX/JSON";
			var params = {
				method : 'saveCookiePreference',
				cookieEssential: cookieString['Essential'],
				cookieAnalytics: cookieString['Analytics'],
				cookieUserLocalAnalytics: cookieString['UserLocalAnalytics'],
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
				UserLocalAnalytics:0,
			};
			var params = {
				cookieEssential: cookieString['Essential'],
				cookieAnalytics: cookieString['Analytics'],
				cookieUserLocalAnalytics: cookieString['UserLocalAnalytics'],
			};
			$.getJSON(url, params,
				function(data) {
					if(data.success){
						AspenDiscovery.showMessageWithButtons("Manage Your Privacy Settings", data.modalBody, data.modalButtons);
						$('.stripPopup').hide();
					} else {
						AspenDiscovery.showMessage("There was an error retreiving your privacy settings");
					}
				}
			 ).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		cookieManagementPreferences: function() {
			var formData = $('#cookieManagementPreferencesForm').serializeArray();
			var cookieEssential = $('#cookieEssential').is(':checked') ? 1 : 0;
			var cookieAnalytics = $('#cookieAnalytics').is(':checked') ? 1 : 0;
			var cookieUserLocalAnalytics = $('#cookieUserLocalAnalytics').is(':checked') ? 1 : 0;

			formData.push({name: 'cookieEssential', value: cookieEssential});
			formData.push({name: 'cookieAnalytics', value: cookieAnalytics});
			formData.push({name: 'cookieUserLocalAnalytics', value: cookieUserLocalAnalytics});
			 var url = Globals.path + "/AJAX/JSON?method=saveCookieManagementPreferences";

			$.getJSON(url, formData,
			function(data) {
				if(data.success) {
					AspenDiscovery.showMessage(data.message);
				} else {
					AspenDiscovery.showMessage("There was an error updating your privacy settings");
				}
			}
		).fail(AspenDiscovery.ajaxFail);
		return false;
		},
		viewCookieConsentPolicy: function() {
		 	console.log("Print Cookie Policy");
		},
		fetchUserCookie: function(Values) {
			document.cookie = 'cookieConsent' + '=' + encodeURIComponent(Values) + '; path=/';
			return;
		}
	}
}(AspenDiscovery.CookieConsent));

