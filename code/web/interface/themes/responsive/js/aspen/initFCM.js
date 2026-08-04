import { initializeApp } from 'https://www.gstatic.com/firebasejs/12.1.0/firebase-app.js';
import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging.js";
import * as UAP from "../lib/ua-parser-min.js";

var appToken = "default";
function getModelName() {
	let parser = new UAParser(window.navigator.userAgent);
	let result = parser.getResult();
	let modelName = result.device.vendor ? result.device.vendor + " " : "";
	modelName += result.device.model ? result.device.model + " " : "";
	modelName += result.os.name ? result.os.name + " " : "";
	modelName += result.os.version ? result.os.version + " " : "";
	modelName += result.cpu.architecture ? result.cpu.architecture + " " : "";
	modelName += result.browser.name ? result.browser.name + " " : "";
	modelName ||= "Unknown ";
	modelName += "PWA";
	return modelName;
}
function initialize() {
	fetch("/API/SystemAPI?method=getFirebaseMessagingConfig").then(function (response) {
		return response.json();
	}).then(function (data) {
		if(!Globals.loggedIn || !data.result?.success)
		{
			console.log("We ran into a snag getting settings");
			console.log(data.result.error);
			return;
		}
		//do things for getting settings here. 
		const firebaseConfig = data.result.settings;
		// Initialize Firebase
		const app = initializeApp(firebaseConfig);

		// Initialize Firebase Cloud Messaging and get a reference to the service
		const messaging = getMessaging(app);
		getToken(messaging, {vapidKey: firebaseConfig.vapidKey}).then((currentToken) => {
			if(!currentToken)
			{
				console.log('no registration token available. Something went wrong with getToken.');
				return;
			}
			if (Notification.permission !== 'granted')
			{
				//permission should already be granted before
				//initialize is called
				return;
			}
			appToken = currentToken;
			let parser = new UAParser(window.navigator.userAgent);
			let result = parser.getResult();
			let modelName = getModelName();
			const postData = {
				"pushToken": currentToken,
				"deviceModel": modelName,
				"tokenType": "firebase"
			}

			fetch("/AspenPWA/AJAX?method=saveNotificationPushToken", {
				method: "POST",
				headers: {
					'Cache-Control': 'no-cache'
				},
				body: new URLSearchParams(postData)
			}).then(() =>{
				//we only need to send a request if we were on
				if($("#notifySavedSearch").is(":checked"))
				{
					handleNotificationControls("notifySavedSearch");
				}
				if($("#notifyCustom").is(":checked"))
				{
					handleNotificationControls("notifyCustom");
				}
				if($("#notifyAccount").is(":checked"))
				{
					handleNotificationControls("notifyAccount");
				}
			});
			
		}).catch((err) => {
			console.log('an error occured while retrieving token. ', err);
		});
	});
}

function handleAllowNotifications() {
	var allow = $("#allowNotifications").is(":checked");
	if(allow)
	{
		Notification.requestPermission().then((permission) => {
			if(permission !== 'granted')
			{
				return;
			}
			initialize();
			$(".notification-permission-controls").show();
		});
	} else {
		$(".notification-permission-controls").hide();
		const postData = {
			"pushToken": appToken
		}

		fetch("/AspenPWA/AJAX?method=deleteNotificationPushToken", {
			method: "POST",
			headers: {
				'Cache-Control': 'no-cache'
			},
			body: new URLSearchParams(postData)
		});
	}
}
function handleNotificationControls(type) {
	let value = $("#"+type).is(":checked");
	let postData = {
		"pushToken": appToken,
		"type": type,
		"value": value
	};
	fetch("/AspenPWA/AJAX?method=setNotificationPreference", {
		method: "POST",
		headers: {
			'Cache-Control': 'no-cache'
		},
		body: new URLSearchParams(postData)
	});
}

$(document).ready(function(){
	$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
	$("#notifySavedSearch").on('switchChange.bootstrapSwitch', function(){handleNotificationControls('notifySavedSearch')});
	$("#notifyAccount").on('switchChange.bootstrapSwitch', function(){handleNotificationControls('notifyAccount')});
	$("#notifyCustom").on('switchChange.bootstrapSwitch', function(){handleNotificationControls('notifyCustom')});
	$("#allowNotifications").on('switchChange.bootstrapSwitch',handleAllowNotifications);

	let modelName = getModelName();
	let token = $('[data-device="'+modelName+'"]');
	let notifySavedSearch = token.data("notifySavedSearch");
	let notifyCustom = token.data("notifyCustom");
	let notifyAccount = token.data("notifyAccount");
	appToken = token.data("token");

	//if we dont have a token or dont have permissions we need
	//to request
	if(token.length && Notification.permission === "granted")
	{
		$("#allowNotifications").prop("checked", true).trigger("change");
	} else {
		$(".notification-permission-controls").hide();
	}

	$("#notifySavedSearch").prop("checked", notifySavedSearch).trigger("change");
	$("#notifyCustom").prop("checked", notifyCustom).trigger("change");
	$("#notifyAccount").prop("checked", notifyAccount).trigger("change");

	
});