import React from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {create} from 'apisauce';
import _ from "lodash";
import {GLOBALS} from "./globals";

// custom components and helper files
import {translate} from "../translations/translations";
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {popToast} from "../components/loadError";
import {getILSMessages} from "./loadPatron";
import {removeData} from "./logout";

/**
 * Fetch branch/location information
 **/
export async function getLocationInfo() {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: 10000,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLocationInfo', {
		id: global.locationId,
		library: global.solrScope,
		version: global.version
	});
	if (response.ok) {
		const profile = response.data.result.location;
		await AsyncStorage.setItem('@locationInfo', JSON.stringify(profile));
		console.log("Location profile saved")
	} else {
		console.log(response);
	}
}

/**
 * Fetch library information
 **/
export async function getLibraryInfo(libraryId, libraryUrl, timeout) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: timeout,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLibraryInfo', {id: libraryId});
	if (response.ok) {
		const profile = response.data.result.library;

		global.barcodeStyle = profile.barcodeStyle;
		global.libraryTheme = profile.themeId;
		global.quickSearches = profile.quickSearches;
		global.allowLinkedAccounts = profile.allowLinkedAccounts;

		await AsyncStorage.setItem('@libraryInfo', JSON.stringify(profile));
		console.log("Library profile saved")

		return profile;
	} else {
		// no data yet
		console.log(response);
		if (_.isUndefined(global.barcodeStyle)) {
			global.barcodeStyle = 0
		}
	}
}

/**
 * Fetch settings for app that are maintained by the library
 **/
export async function getAppSettings(url, timeout, slug) {
	const api = create({
		baseURL: url + '/API',
		timeout: timeout,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getAppSettings', {slug: slug});
	if (response.ok) {
		global.privacyPolicy = response.data.result.settings.privacyPolicy;
		const appSettings = response.data.result.settings;
		await AsyncStorage.setItem('@appSettings', JSON.stringify(appSettings));
		console.log("App settings saved")
		return response.data.result;
	} else {
		console.log(response);
	}
}

/**
 * Fetch valid pickup locations for the patron
 **/
export async function getPickupLocations() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);
	if (response.ok) {
		const data = response.data.result.pickupLocations;
		const locations = data.map(({displayName, code, locationId}) => ({
			key: locationId,
			locationId: locationId,
			code: code,
			name: displayName,
		}));
		await AsyncStorage.setItem('@pickupLocations', JSON.stringify(locations));
		console.log("Pickup locations saved")
		return locations;
	} else {
		console.log(response);
	}
}

/**
 * Fetch active browse categories for the branch/location
 **/
export async function getBrowseCategories() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
	if (response.status === 403) {
		await removeData().then(res => {
			console.log("Session ended.")
		});
	}
	if (response.ok) {
		const items = response.data.result;
		let allCategories = [];
		items.map(function (category, index, array) {
			const subCategories = category['subCategories'];

			if (subCategories.length !== 0) {
				subCategories.forEach(item => allCategories.push({
					'key': item.key,
					'title': item.title,
					'isHidden': false
				}))
			} else {
				allCategories.push({'key': category.key, 'title': category.title, 'isHidden': false});
			}
		});
		await AsyncStorage.setItem('@browseCategories', JSON.stringify(allCategories));
	} else {
		console.log(response);
	}
}

export async function getLanguages() {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: 10000,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLanguages');
	if(response.ok) {
		if (typeof response.data.result !== "undefined") {
			const languages = response.data.result.languages;
			await AsyncStorage.setItem('@libraryLanguages', JSON.stringify(languages));
			console.log("Library languages saved")
		}
	} else {
		console.log(response);
	}
}