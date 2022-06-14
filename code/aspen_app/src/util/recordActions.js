import React from "react";
import {create} from 'apisauce';
import * as WebBrowser from 'expo-web-browser';
import _ from "lodash";
import * as Sentry from 'sentry-expo';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {translate} from "../translations/translations";
import {getCheckedOutItems, getHolds, getProfile} from "./loadPatron";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";
import {userContext} from "../context/user";

/**
 * Fetch information for GroupedWork
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the GroupedWork id for the record</li>
 * </ul>
 **/
export async function getGroupedWork(libraryUrl, itemId) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/ItemAPI?method=getAppGroupedWork', {id: itemId});
	if (response.ok) {
		return response.data;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

/**
 * Checkout item to patron
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the id for the record</li>
 *     <li>source - the source of the item, i.e. ils, hoopla, overdrive. If left empty, Aspen assumes ils.</li>
 *     <li>patronId - the id for the patron</li>
 * </ul>
 * @param {string} libraryUrl
 * @param {number} itemId
 * @param {string} source
 * @param {number} patronId
 **/
export async function checkoutItem(libraryUrl, itemId, source, patronId) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {itemId: itemId, itemSource: source, userId: patronId}
	});
	const response = await api.post('/UserAPI?method=checkoutItem', postBody);
	console.log(response);
	if (response.ok) {
		const responseData = response.data;
		const results = responseData.result;

		// reload patron data in the background
		await getCheckedOutItems(libraryUrl);

		return results;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

/**
 * Place hold on item for patron
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the id for the record</li>
 *     <li>source - the source of the item, i.e. ils, hoopla, overdrive. If left empty, Aspen assumes ils.</li>
 *     <li>patronId - the id for the patron</li>
 *     <li>pickupBranch - the location id for where the hold will be picked up at</li>
 * </ul>
 **/
export async function placeHold(libraryUrl, itemId, source, patronId, pickupBranch, volumeId = null) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {itemId: itemId, itemSource: source, userId: patronId, pickupBranch: pickupBranch, volumeId: volumeId}
	});
	const response = await api.post('/UserAPI?method=placeHold', postBody);
	console.log(response);
	if (response.ok) {
		const responseData = response.data;
		const results = responseData.result;

		// reload patron data in the background
		await getHolds(libraryUrl);

		return results;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function overDriveSample(libraryUrl, formatId, itemId, sampleNumber) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {
			overDriveId: itemId,
			formatId: formatId,
			sampleNumber: sampleNumber,
			itemSource: "overdrive",
			isPreview: "true"
		}
	});
	const response = await api.post('/UserAPI?method=viewOnlineItem', postBody);

	if (response.ok) {
		const result = response.data;
		const accessUrl = result.result.url;

		await WebBrowser.openBrowserAsync(accessUrl)
			.then(res => {
				console.log(res);
			})
			.catch(async err => {
				if (err.message === "Another WebBrowser is already being presented.") {

					try {
						WebBrowser.dismissBrowser();
						await WebBrowser.openBrowserAsync(accessUrl)
							.then(response => {
								console.log(response);
							})
							.catch(async error => {
								console.log("Unable to close previous browser session.");
							});
					} catch (error) {
						console.log("Really borked.");
					}
				} else {
					popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
				}
			});


	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function openSideLoad(redirectUrl) {
	if (redirectUrl) {
		await WebBrowser.openBrowserAsync(redirectUrl)
			.then(res => {
				console.log(res);
			})
			.catch(async err => {
				if (err.message === "Another WebBrowser is already being presented.") {

					try {
						WebBrowser.dismissBrowser();
						await WebBrowser.openBrowserAsync(redirectUrl)
							.then(response => {
								console.log(response);
							})
							.catch(async error => {
								console.log("Unable to close previous browser session.");
								popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
							});
					} catch (error) {
						console.log("Tried to open again but still unable");
						popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
					}
				} else {
					console.log("Unable to open browser window.");
					popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
				}
			});
	} else {
		popToast(translate('error.no_open_resource'), translate('error.no_valid_url'), "warning");
		console.log(response);
	}
}

export function openCheckouts() {
	navigation.navigate("CheckedOut");
}

export async function getItemDetails(libraryUrl, id, format) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {recordId: id, format: format}
	});
	const response = await api.post('/ItemAPI?method=getItemDetails', postBody);
	if (response.ok) {
		//console.log(response);
		return _.values(response.data);
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}