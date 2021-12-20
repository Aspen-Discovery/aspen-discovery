import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';

// custom components and helper files
import {createAuthTokens, getHeaders} from "./apiAuth";
import {popAlert} from "../components/loadError";

/**
 * Logout the user from Aspen LiDA and remove all saved data
 **/
export async function removeData() {
	const keys = ['@libraryHomeLink', '@libraryAddress', '@libraryPhone',
		'@libraryEmail', '@libraryShowHours', '@libraryHoursMessage',
		'@libraryHours', '@libraryLatitude', '@libraryLongitude'];

	await logoutUser().then(response => {
		try {
			SecureStore.deleteItemAsync("patronName");
			SecureStore.deleteItemAsync("library");
			SecureStore.deleteItemAsync("libraryName");
			SecureStore.deleteItemAsync("locationId");
			SecureStore.deleteItemAsync("solrScope");
			SecureStore.deleteItemAsync("pathUrl");
			SecureStore.deleteItemAsync("version");
			SecureStore.deleteItemAsync("userKey");
			SecureStore.deleteItemAsync("secretKey");
			SecureStore.deleteItemAsync("userToken");
			SecureStore.deleteItemAsync("logo");
			SecureStore.deleteItemAsync("favicon");
			AsyncStorage.multiRemove(keys);
			global.promptForOverdriveEmail = "";
			global.overdriveEmail = "";
			global.patronId = "";
			global.barcode = "";
			global.rememberHoldPickupLocation = "";
			global.pickupLocationId = "";
			global.homeLocationId = "";
			global.interfaceLanguage = "";
			global.numCheckedOut = "";
			global.numOverdue = "";
			global.numHolds = "";
			global.numHoldsAvailable = "";
			global.userKey = "";
			global.secretKey = "";
			global.allHolds = "";
			global.unavailableHolds = "";
			global.availableHolds = "";
			global.allUserHolds = "";
			global.checkedOutItems = "";
		} catch (error) {
			popAlert("Unable to logout", "Something went wrong when clearing user data. Try again.", "error");
			console.log("Unable to remove user data.");
			console.log(error);
		}
	})
}

/**
 * Logout the user and end the Aspen Discovery session
 **/
export async function logoutUser() {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/UserAPI?method=logout');

	if (response.ok) {
		return response.data;
	} else {
		return response.problem;
	}
}