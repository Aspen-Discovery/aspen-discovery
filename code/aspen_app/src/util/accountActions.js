import React from "react";
import moment from "moment";
import {create} from 'apisauce';
import * as WebBrowser from 'expo-web-browser';
import i18n from 'i18n-js';


// custom components and helper files
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {translate} from "../translations/translations";
import {popAlert, popToast} from "../components/loadError";
import {getCheckedOutItems, getHolds, getLinkedAccounts, getPatronBrowseCategories, getProfile} from './loadPatron';
import {getActiveBrowseCategories, getBrowseCategories} from "./loadLibrary";
import {GLOBALS} from "./globals";


export async function isLoggedIn() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=isLoggedIn', postBody);

	if (response.ok) {
		return response.data;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		return response.problem;
	}
}

/* ACTIONS ON CHECKOUTS */
export async function renewCheckout(barcode, recordId, source, itemId) {

	let validId;
	if (itemId == null) {
		validId = barcode;
	} else {
		validId = itemId;
	}

	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		params: {itemBarcode: validId, recordId: recordId, itemSource: source},
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=renewItem', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (source === "ils") {
			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				const forceReload = true;
				await getCheckedOutItems(forceReload);
				await getProfile(true);
			} else {
				popAlert(result.title, result.message, "error");
			}
		} else {
			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				await getCheckedOutItems(true);
				await getProfile(true);
			} else {
				popAlert(result.title, result.message, "error");
			}
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}

}

export async function renewAllCheckouts() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=renewAll', postBody);
	//console.log(response);
	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.renewalMessage[0], "success");
		} else {
			popAlert(result.title, result.renewalMessage[0], "error");
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function returnCheckout(userId, id, source, overDriveId) {
	const postBody = await postData();

	let itemId = id;
	if (overDriveId != null) {
		itemId = overDriveId;
	}

	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {id: itemId, patronId: userId, itemSource: source}
	});
	const response = await api.post('/UserAPI?method=returnCheckout', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.message, "success");
			await getCheckedOutItems(true);
			await getProfile(true);
		} else {
			popAlert(result.title, result.message, "error");
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}

}

export async function viewOnlineItem(userId, id, source, accessOnlineUrl) {
	const postBody = await postData();

	if (source === "hoopla") {
		const api = create({
			baseURL: global.libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(),
			auth: createAuthTokens(),
			params: {patronId: userId, itemId: id, itemSource: source}
		});
		const response = await api.post('/UserAPI?method=viewOnlineItem', postBody);

		if (response.ok) {
			const results = response.data;
			const result = results.result.url;

			await WebBrowser.openBrowserAsync(result)
				.then(res => {
					console.log(res);
				})
				.catch(async err => {
					if (err.message === "Another WebBrowser is already being presented.") {

						try {
							WebBrowser.dismissBrowser();
							await WebBrowser.openBrowserAsync(result)
								.then(response => {
									console.log(response);
								})
								.catch(async error => {
									popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
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
	} else {
		await WebBrowser.openBrowserAsync(accessOnlineUrl)
			.then(res => {
				console.log(res);
			})
			.catch(async err => {
				if (err.message === "Another WebBrowser is already being presented.") {

					try {
						WebBrowser.dismissBrowser();
						await WebBrowser.openBrowserAsync(accessOnlineUrl)
							.then(response => {
								console.log(response);
							})
							.catch(async error => {
								popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
							});
					} catch (error) {
						console.log("Unable to open.")
					}

				} else {
					popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
				}
			});
	}

}

export async function viewOverDriveItem(userId, formatId, overDriveId) {
	const postBody = await postData();

	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {patronId: userId, overDriveId: overDriveId, formatId: formatId, itemSource: "overdrive"}
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

/* ACTIONS ON HOLDS */
export async function freezeHold(cancelId, recordId, source) {
	const postBody = await postData();

	const today = moment();
	const reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			holdId: cancelId,
			recordId: recordId,
			itemSource: source,
			reactivationDate: reactivationDate,
			patronId: global.patronId
		}
	});
	const response = await api.post('/UserAPI?method=freezeHold', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert("Hold frozen", result.message, "success");
			// reload patron data in the background
			await getProfile(true);
			await getHolds(true);
		} else {
			popAlert("Unable to freeze hold", result.message, "error");
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function thawHold(cancelId, recordId, source) {
	const postBody = await postData();

	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			holdId: cancelId,
			recordId: recordId,
			itemSource: source,
			patronId: global.patronId
		}
	});
	const response = await api.post('/UserAPI?method=activateHold', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert("Hold thawed", result.message, "success");
			// reload patron data in the background
			await getProfile(true);
			await getHolds(true);
		} else {
			popAlert("Unable to thaw hold", result.message, "error");
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function cancelHold(cancelId, recordId, source) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			cancelId: cancelId,
			recordId: recordId,
			itemSource: source,
			patronId: global.patronId
		}
	});
	const response = await api.post('/UserAPI?method=cancelHold', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.message, "success");
			// reload patron data in the background
			await getProfile(true);
			await getHolds(true);
		} else {
			popAlert(result.title, result.message, "error");
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function changeHoldPickUpLocation(holdId, newLocation) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {sessionId: GLOBALS.appSessionId, holdId: holdId, newLocation: newLocation}
	});
	const response = await api.post('/UserAPI?method=changeHoldPickUpLocation', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.message, "success");
			// reload patron data in the background
			await getProfile(true);
			await getHolds(true);
		} else {
			popAlert(result.title, result.message, "error");
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

export async function updateOverDriveEmail(itemId, source, patronId, overdriveEmail, promptForOverdriveEmail) {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {
			itemId: itemId,
			itemSource: source,
			patronId: patronId,
			overdriveEmail: overdriveEmail,
			promptForOverdriveEmail: promptForOverdriveEmail
		}
	});
	const response = await api.post('/UserAPI?method=updateOverDriveEmail', postBody);

	if (response.ok) {
		const responseData = response.data;
		const result = responseData.result;
		// reload patron data in the background
		await getProfile(true);
		return result;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
	}
}

/* ACTIONS ON BROWSE CATEGORIES */
export async function dismissBrowseCategory(browseCategoryId, patronId) {
	const postBody = await postData();

	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {patronId: patronId, browseCategoryId: browseCategoryId}
	});
	const response = await api.post('/UserAPI?method=dismissBrowseCategory', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;
		await getPatronBrowseCategories();
		await getBrowseCategories();

		if (result.success === false) {
			popAlert(result.title, result.message, "error");
		}
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
		console.log(response);
	}
}

export async function showBrowseCategory(browseCategoryId) {
	const postBody = await postData();

	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {patronId: global.patronId, browseCategoryId: browseCategoryId}
	});
	const response = await api.post('/UserAPI?method=showBrowseCategory', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		await getPatronBrowseCategories();
		await getBrowseCategories();
		if (result.success === false) {
			popAlert(result.title, result.message, "error");
		}
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
		console.log(response);
	}
}

export async function addLinkedAccount(username, password) {
	let postBody = await postData();
	postBody.append('accountToLinkUsername', username);
	postBody.append('accountToLinkPassword', password);
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=addAccountLink', postBody);
	if(response.ok) {
		if(response.data.result.success) {
			await getLinkedAccounts();
			popAlert(response.data.result.title, response.data.result.message, "success");
		} else {
			popAlert(response.data.result.title, response.data.result.message, "error");
		}
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
		console.log(response);
	}
}

export async function removeLinkedAccount(id) {
	let postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=removeAccountLink&idToRemove=' + id, postBody);
	if(response.ok) {
		if(response.data.result.success) {
			await getLinkedAccounts();
			popAlert(response.data.result.title, response.data.result.message, "success");
		} else {
			popAlert(response.data.result.title, response.data.result.message, "error");
		}
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
		console.log(response);
	}
}

export async function saveLanguage(code) {
	let postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: 10000,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=saveLanguage&languageCode=' + code, postBody);
	if(response.ok) {
		console.log(response.data);
		i18n.locale = code;
	} else {
		console.log(response);
	}
}