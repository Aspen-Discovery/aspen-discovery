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
	}
}
