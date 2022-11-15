import React from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';

// custom components and helper files
import {createAuthTokens, getHeaders} from './apiAuth';
import {GLOBALS, LOGIN_DATA} from './globals';
import {LIBRARY} from './loadLibrary';
import {PATRON} from './loadPatron';

/**
 * Logout the user from Aspen LiDA and remove all saved data
 **/
export async function removeData() {
	try {
		SecureStore.deleteItemAsync('patronName');
		SecureStore.deleteItemAsync('library');
		SecureStore.deleteItemAsync('libraryName');
		SecureStore.deleteItemAsync('locationId');
		SecureStore.deleteItemAsync('solrScope');
		SecureStore.deleteItemAsync('pathUrl');
		SecureStore.deleteItemAsync('version');
		SecureStore.deleteItemAsync('userKey');
		SecureStore.deleteItemAsync('secretKey');
		SecureStore.deleteItemAsync('userToken');
		SecureStore.deleteItemAsync('logo');
		SecureStore.deleteItemAsync('favicon');
		await AsyncStorage.removeItem('@userToken');
		await AsyncStorage.removeItem('@patronProfile');
		await AsyncStorage.removeItem('@libraryInfo');
		await AsyncStorage.removeItem('@locationInfo');
		await AsyncStorage.removeItem('@pathUrl');
	} catch (e) {
		console.log(e);
	} finally {
		LIBRARY.url = null;
		LIBRARY.name = null;
		LIBRARY.favicon = null;
		LIBRARY.version = '22.10.00';
		LIBRARY.languages = [];
		LIBRARY.vdx = [];
		PATRON.userToken = null;
		PATRON.scope = null;
		PATRON.library = null;
		PATRON.location = null;
		PATRON.listLastUsed = null;
		PATRON.fines = 0;
		PATRON.messages = [];
		PATRON.num.checkedOut = 0;
		PATRON.num.holds = 0;
		PATRON.num.lists = 0;
		PATRON.num.overdue = 0;
		PATRON.num.ready = 0;
		PATRON.num.savedSearches = 0;
		PATRON.num.updatedSearches = 0;
		PATRON.promptForOverdriveEmail = 1;
		PATRON.rememberHoldPickupLocation = 0;
		PATRON.pickupLocations = [];
		PATRON.language = 'en';
		PATRON.coords.lat = 0;
		PATRON.coords.long = 0;
		LOGIN_DATA.showSelectLibrary = true;
		LOGIN_DATA.runGreenhouse = true;
		LOGIN_DATA.num = 0;
		LOGIN_DATA.nearbyLocations = [];
		LOGIN_DATA.allLocations = [];
		LOGIN_DATA.hasPendingChanges = false;
		
		console.log('Storage data cleansed.');
	}
}

/**
 * Logout the user and end the Aspen Discovery session
 **/
export async function logoutUser(libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
	});
	const response = await api.get('/UserAPI?method=logout');

	if (response.ok) {
		return response.data;
	} else {
		return response.problem;
	}
}

export async function removeDataOnly() {
	const keys = [
		'@libraryHomeLink', '@libraryAddress', '@libraryPhone',
		'@libraryEmail', '@libraryShowHours', '@libraryHoursMessage',
		'@libraryHours', '@libraryLatitude', '@libraryLongitude',
		'@patronProfile', '@patronLibrary', '@libraryInfo', '@locationInfo',
		'@appSettings', '@pickupLocations', '@browseCategories', '@ILSMessages',
		'@patronCheckouts', '@patronHolds', '@patronHoldsNotReady', '@patronHoldsReady',
		'@linkedAccounts', '@viewerAccounts',
	];

	SecureStore.deleteItemAsync('patronName');
	SecureStore.deleteItemAsync('library');
	SecureStore.deleteItemAsync('libraryName');
	SecureStore.deleteItemAsync('locationId');
	SecureStore.deleteItemAsync('solrScope');
	SecureStore.deleteItemAsync('pathUrl');
	SecureStore.deleteItemAsync('version');
	SecureStore.deleteItemAsync('userKey');
	SecureStore.deleteItemAsync('secretKey');
	SecureStore.deleteItemAsync('userToken');
	SecureStore.deleteItemAsync('logo');
	SecureStore.deleteItemAsync('favicon');
	await AsyncStorage.removeItem('@userToken');
	await AsyncStorage.removeItem('@patronProfile');
	await AsyncStorage.removeItem('@libraryInfo');
	await AsyncStorage.removeItem('@locationInfo');
	await AsyncStorage.removeItem('@pathUrl');
	global.promptForOverdriveEmail = '';
	global.overdriveEmail = '';
	global.patronId = '';
	global.barcode = '';
	global.rememberHoldPickupLocation = '';
	global.pickupLocationId = '';
	global.homeLocationId = '';
	global.interfaceLanguage = '';
	global.numCheckedOut = '';
	global.numOverdue = '';
	global.numHolds = '';
	global.numHoldsAvailable = '';
	global.userKey = '';
	global.secretKey = '';
	global.allHolds = '';
	global.unavailableHolds = '';
	global.availableHolds = '';
	global.allUserHolds = '';
	global.checkedOutItems = '';
	global.libraryUrl = '';

}