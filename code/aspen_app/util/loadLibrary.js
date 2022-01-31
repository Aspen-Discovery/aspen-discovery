import React from "react";
import {create} from 'apisauce';
import _ from "lodash";

// custom components and helper files
import {translate} from "./translations";
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {popToast} from "../components/loadError";

/**
 * Fetch branch/location information
 **/
export async function getLocationInfo() {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLocationInfo', {
		id: global.locationId,
		library: global.solrScope,
		version: global.version
	});
	if (response.ok) {
		const result = response.data.result;
		const profile = result.location;

		global.location_homeLink = profile.homeLink;
		global.location_address = profile.address;
		global.location_phone = profile.phone;
		global.location_description = profile.description;
		global.location_email = profile.email;
		global.location_showInLocationsAndHoursList = profile.showInLocationsAndHoursList;
		if (profile.showInLocationsAndHoursList === 1) {
			global.location_hoursMessage = profile.hoursMessage;
			global.location_hours = JSON.stringify(profile.hours);
		} else {
			global.location_hoursMessage = null;
			global.location_hours = null;
		}
		global.location_latitude = profile.latitude;
		global.location_longitude = profile.longitude;
		global.locationTheme = profile.theme;

		console.log("Location profile set")
		return profile;
	} else {
		// no data yet
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
		const result = response.data.result;
		const profile = result.library;

		global.barcodeStyle = profile.barcodeStyle;
		global.libraryTheme = profile.themeId;
		global.quickSearches = profile.quickSearches;

		console.log("Library profile set")
		return profile;
	} else {
		// no data yet
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
		console.log("App settings set.")
		return response.data.result;
	} else {
		console.log(response.problem);
	}
}

/**
 * Fetch valid pickup locations for the patron
 **/
export async function getPickupLocations() {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/UserAPI?method=getValidPickupLocations', {
		username: global.userKey,
		password: global.secretKey
	});

	if (response.ok) {
		const result = response.data;
		const fetchedData = result.result;
		var locations = fetchedData.pickupLocations.map(({displayName, code, locationId}) => ({
			key: locationId,
			locationId: locationId,
			code: code,
			name: displayName,
		}));
		return locations;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

/**
 * Fetch active browse categories for the branch/location
 **/
export async function getActiveBrowseCategories() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
	if (response.ok) {
		//console.log(response);
		const items = response.data;
		const results = items.result;
		var allCategories = [];
		const categoriesArray = results.map(function (category, index, array) {
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
		return allCategories;
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
	}
}