import React from 'react';
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';
import _ from 'lodash';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';

// custom components and helper files
import {GLOBALS, LOGIN_DATA} from './globals';
import {PATRON} from './loadPatron';
import {createAuthTokens, getHeaders, postData, problemCodeMap} from './apiAuth';
import {popToast} from '../components/loadError';

export async function makeGreenhouseRequestNearby() {
	let method = 'getLibraries';
	let url = Constants.manifest.extra.greenhouse;
	let latitude, longitude = 0;
	if (GLOBALS.slug !== 'aspen-lida') {
		method = 'getLibrary';
		url = Constants.manifest.extra.apiUrl;
		LOGIN_DATA.runGreenhouse = false;
	}
	if (_.isNull(PATRON.coords.lat) && _.isNull(PATRON.coords.long)) {
		try {
			latitude = await SecureStore.getItemAsync('latitude');
			longitude = await SecureStore.getItemAsync('longitude');
			PATRON.coords.lat = latitude;
			PATRON.coords.long = longitude;
		} catch (e) {
			console.log(e);
		}
	}
	const api = create({
		baseURL: url + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(),
	});
	const response = await api.get('/GreenhouseAPI?method=' + method, {
		latitude: PATRON.coords.lat,
		longitude: PATRON.coords.long,
		release_channel: Updates.releaseChannel,
	});
	if (response.ok) {
		const data = response.data;
		let libraries;
		if (Constants.manifest.slug === 'aspen-lida') {
			libraries = _.uniqBy(data.libraries, v => [v.locationId, v.libraryId].join());
			libraries = _.uniqBy(libraries, v => [v.librarySystem, v.name].join());
		} else {
			libraries = _.uniqBy(data.library, v => [v.locationId, v.name].join());
			libraries = _.values(libraries);
			libraries = _.uniqBy(libraries, v => [v.libraryId, v.name].join());
		}

		console.log(libraries);

		if (data.count <= 1) {
			LOGIN_DATA.showSelectLibrary = false;
		}
		LOGIN_DATA.nearbyLocations = libraries;
		LOGIN_DATA.hasPendingChanges = true;
		console.log('Greenhouse request completed.');
		return true;
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, 'warning');
	}
	return false;
}

export async function makeGreenhouseRequestAll() {
	const api = create({
		baseURL: Constants.manifest.extra.greenhouse + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(),
	});
	const response = await api.get('/GreenhouseAPI?method=getLibraries', {
		release_channel: Updates.releaseChannel,
	});
	if (response.ok) {
		const data = response.data;
		LOGIN_DATA.allLocations = _.uniqBy(data.libraries, v => [v.librarySystem, v.name].join());
		LOGIN_DATA.hasPendingChanges = true;
		console.log('Full greenhouse request completed.');
		return true;
	} else {
		console.log(response);
	}
	return false;
}

export async function checkCachedUrl(url) {
	const postBody = await postData();
	const api = create({
		baseURL: url + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);
	if (response.ok) {
		return true;
	} else {
		return false;
	}
}