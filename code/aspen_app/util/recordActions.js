import React from "react";
import {create} from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {translate} from "./translations";
import {getCheckedOutItems, getHolds, getProfile} from "./loadPatron";
import {popToast} from "../components/loadError";

/**
 * Fetch information for GroupedWork
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the GroupedWork id for the record</li>
 * </ul>
 **/
export async function getGroupedWork(itemId) {
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutSlow,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/ItemAPI?method=getAppGroupedWork', {id: itemId});
	//console.log(response);
	if (response.ok) {
		return response.data;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
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
 * @param {number} itemId
 * @param {string} source
 * @param {number} patronId
 **/
export async function checkoutItem(itemId, source, patronId) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {itemId: itemId, itemSource: source, patronId: patronId}
	});
	const response = await api.post('/UserAPI?method=checkoutItem', postBody);

	if (response.ok) {
		const responseData = response.data;
		const results = responseData.result;

		// reload patron data in the background
		await getProfile(true);
		await getCheckedOutItems(true);

		return results;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
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
export async function placeHold(itemId, source, patronId, pickupBranch) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {itemId: itemId, itemSource: source, patronId: patronId, pickupBranch: pickupBranch}
	});
	const response = await api.post('/UserAPI?method=placeHold', postBody);
	if (response.ok) {
		//console.log(response);
		const responseData = response.data;
		const results = responseData.result;

		// reload patron data in the background
		await getProfile(true);
		await getHolds(true);

		return results;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function overDriveSample(formatId, itemId, sampleNumber) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(),
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
	}
}

export function openCheckouts() {
	navigation.navigate("CheckedOut");
}