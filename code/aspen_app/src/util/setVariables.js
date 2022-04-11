import React from "react";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import moment from "moment";
import * as Sentry from 'sentry-expo';

// custom components and helper files

export async function setInitialVariables() {
	try {
		global.releaseChannel = await SecureStore.getItemAsync("releaseChannel");
		global.latitude = await SecureStore.getItemAsync("latitude");
		global.longitude = await SecureStore.getItemAsync("longitude");
	} catch (e) {
		console.log("Error setting fetching data from SecureStore.");
		console.log(e);
		Sentry.Native.captureException(e);
	}
}

export async function setGlobalVariables() {

	// prepare app data
	global.version = Constants.manifest.version;
	global.build = Constants.nativeAppVersion;

	// set timeout options
	global.timeoutFast = 3000;
	global.timeoutAverage = 5000;
	global.timeoutSlow = 10000;

	try {
		// prepare app data
		global.slug = await SecureStore.getItemAsync("slug");
		global.apiUrl = await SecureStore.getItemAsync("apiUrl");

		// prepare user data
		global.userKey = await SecureStore.getItemAsync("userKey");
		global.secretKey = await SecureStore.getItemAsync("secretKey");
		global.patron = await SecureStore.getItemAsync("patronName");

		// prepare library data
		global.libraryId = await SecureStore.getItemAsync("library");
		global.libraryName = await SecureStore.getItemAsync("libraryName");
		global.locationId = await SecureStore.getItemAsync("locationId");
		global.solrScope = await SecureStore.getItemAsync("solrScope");
		global.libraryUrl = await SecureStore.getItemAsync("pathUrl");
		global.logo = await SecureStore.getItemAsync("logo");
		global.favicon = await SecureStore.getItemAsync("favicon");
		console.log("Global variables set.");
	} catch (e) {
		console.log("Error setting fetching data from SecureStore.");
		console.log(e);
	}
}