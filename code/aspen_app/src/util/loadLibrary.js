import React from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {create} from 'apisauce';
import _ from "lodash";
import {GLOBALS} from "./globals";

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {removeData} from "./logout";

/**
 * Fetch branch/location information
 **/
export async function getLocationInfo(library, location) {
	const api = create({
		baseURL: library.baseUrl + '/API',
		timeout: 10000,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLocationInfo', {
		id: location.locationId,
		library: global.solrScope, //need to pull this out of global.
		version: GLOBALS.appVersion
	});
	if (response.ok) {
		if(response.data.result.success) {
			let profile = [];
			if(typeof response.data.result.location !== 'undefined') {
				profile = response.data.result.location;
				//console.log("Location profile saved")
			} else {
				console.log(response);
			}
			await AsyncStorage.setItem('@locationInfo', JSON.stringify(profile));
			return profile;
		}
		let profile = [];
		return profile;
	} else {
		//console.log(response);
		let profile = [];
		return profile;
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
		if(response.data.result.success) {
			let profile = [];
			if(typeof response.data.result.library !== 'undefined') {
				profile = response.data.result.library;
				global.barcodeStyle = profile.barcodeStyle;
				global.libraryTheme = profile.themeId;
				global.quickSearches = profile.quickSearches;
				global.allowLinkedAccounts = profile.allowLinkedAccounts;
				//console.log("Library profile saved");
			} else {
				global.barcodeStyle = "CODE128";
				global.libraryTheme = 1;
				global.quickSearches = [];
				global.allowLinkedAccounts = 0;
				console.log(response);
			}
			await AsyncStorage.setItem('@libraryInfo', JSON.stringify(profile));
			return profile;
		}
		let profile = [];
		return profile;
	} else {
		//console.log(response);
		if (_.isUndefined(global.barcodeStyle)) {
			global.barcodeStyle = "CODE128"
		}
		let profile = [];
		return profile;
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
export async function getPickupLocations(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);
	if (response.ok) {
		let locations = [];
		const data = response.data.result.pickupLocations;
		locations = data.map(({displayName, code, locationId}) => ({
			key: locationId,
			locationId: locationId,
			code: code,
			name: displayName,
		}));
		await AsyncStorage.setItem('@pickupLocations', JSON.stringify(locations));
		//console.log("Pickup locations saved")
		return locations;
	} else {
		console.log(response);
	}
}

/**
 * Fetch active browse categories for the branch/location
 **/
export async function getBrowseCategories(libraryUrl) {
	if(libraryUrl) {
		const postBody = await postData();
		const api = create({
			baseURL: libraryUrl + '/API',
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

				if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
					subCategories.forEach(item => allCategories.push({
						'key': item.key,
						'title': item.title,
					}))
				} else {
					allCategories.push({'key': category.key, 'title': category.title});
				}
			});
			//console.log(allCategories);
			return allCategories;
		} else {
			console.log(response);
		}
	} else {
		console.log("getBrowseCategories: " + libraryUrl);

	}
}

export async function getLanguages(libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: 10000,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLanguages');
	if(response.ok) {
		if (typeof response.data.result !== 'undefined') {
			const languages = response.data.result.languages;
			await AsyncStorage.setItem('@libraryLanguages', JSON.stringify(languages));
			console.log("Library languages saved")
		}
	} else {
		console.log(response);
	}
}