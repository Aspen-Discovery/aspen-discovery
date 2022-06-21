import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';
import * as Sentry from 'sentry-expo';

// custom components and helper files
import {createAuthTokens, getHeaders} from "./apiAuth";
import {popAlert} from "../components/loadError";
import {GLOBALS} from "./globals";

/**
 * Logout the user from Aspen LiDA and remove all saved data
 **/
export async function removeData() {
	const keys = ['@libraryHomeLink', '@libraryAddress', '@libraryPhone',
		'@libraryEmail', '@libraryShowHours', '@libraryHoursMessage',
		'@libraryHours', '@libraryLatitude', '@libraryLongitude',
		'@patronProfile', '@patronLibrary', '@libraryInfo', '@locationInfo',
		'@appSettings', '@pickupLocations', '@browseCategories', '@ILSMessages',
		'@patronCheckouts', '@patronHolds', '@patronHoldsNotReady', '@patronHoldsReady',
		'@linkedAccounts', '@viewerAccounts', '@pathUrl', '@userToken'];

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
		await AsyncStorage.removeItem('@userToken');
		await AsyncStorage.removeItem('@patronProfile');
		await AsyncStorage.removeItem('@libraryInfo');
		await AsyncStorage.removeItem('@locationInfo');
		await AsyncStorage.removeItem('@pathUrl');
		//await AsyncStorage.clear();
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
		console.log("Storage data cleansed.")
}

/**
 * Logout the user and end the Aspen Discovery session
 **/
export async function logoutUser(libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
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

export async function removeDataOnly() {
	const keys = ['@libraryHomeLink', '@libraryAddress', '@libraryPhone',
		'@libraryEmail', '@libraryShowHours', '@libraryHoursMessage',
		'@libraryHours', '@libraryLatitude', '@libraryLongitude',
		'@patronProfile', '@patronLibrary', '@libraryInfo', '@locationInfo',
		'@appSettings', '@pickupLocations', '@browseCategories', '@ILSMessages',
		'@patronCheckouts', '@patronHolds', '@patronHoldsNotReady', '@patronHoldsReady',
		'@linkedAccounts', '@viewerAccounts'];

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
		await AsyncStorage.removeItem('@userToken');
		await AsyncStorage.removeItem('@patronProfile');
		await AsyncStorage.removeItem('@libraryInfo');
		await AsyncStorage.removeItem('@locationInfo');
		await AsyncStorage.removeItem('@pathUrl');
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
		global.libraryUrl = ""

}