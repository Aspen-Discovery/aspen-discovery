import React from "react";
import * as SecureStore from 'expo-secure-store';
import moment from "moment";
import {create} from 'apisauce';
import * as WebBrowser from 'expo-web-browser';
import i18n from 'i18n-js';
import * as Sentry from 'sentry-expo';

// custom components and helper files
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {translate} from "../translations/translations";
import {popAlert, popToast} from "../components/loadError";
import {
	getCheckedOutItems,
	getHolds,
	getLinkedAccounts,
	getPatronBrowseCategories, getProfile,
	reloadCheckedOutItems,
	reloadHolds
} from './loadPatron';
import {getActiveBrowseCategories, getBrowseCategories} from "./loadLibrary";
import {GLOBALS} from "./globals";
import {userContext} from "../context/user";


export async function isLoggedIn(pathUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: pathUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=isLoggedIn', postBody);

	if (response.ok) {
		console.log(response.data);
		return response.data.result;
	} else {
		console.log(response);
		return response.problem;
	}
}

/* ACTIONS ON CHECKOUTS */
export async function renewCheckout(barcode, recordId, source, itemId, libraryUrl, userId) {

	let validId;
	if (itemId == null) {
		validId = barcode;
	} else {
		validId = itemId;
	}

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		params: {itemBarcode: validId, recordId: recordId, itemSource: source, userId: userId},
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=renewItem', postBody);

	console.log(response);
	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (source === "ils") {
			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				await reloadCheckedOutItems(libraryUrl);
			} else {
				popAlert(result.title, result.message, "error");
			}
		} else {
			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				await reloadCheckedOutItems(libraryUrl);
			} else {
				popAlert(result.title, result.message, "error");
			}
		}

	} else {
		console.log(response);
	}

}

export async function renewAllCheckouts(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=renewAll', postBody);
	//console.log(response);
	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.renewalMessage[0], "success");
			await reloadCheckedOutItems(libraryUrl);
		} else {
			popAlert(result.title, result.renewalMessage[0], "error");
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function returnCheckout(userId, id, source, overDriveId, libraryUrl, discoveryVersion) {
	const postBody = await postData();

	let itemId = id;
	if (overDriveId != null) {
		itemId = overDriveId;
	}
	if(discoveryVersion >= "22.05.00") {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {itemId: itemId, userId: userId, itemSource: source}
		});
		const response = await api.post('/UserAPI?method=returnCheckout', postBody);
		console.log(response);

		if (response.ok) {
			const fetchedData = response.data;
			const result = fetchedData.result;

			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				await reloadCheckedOutItems(libraryUrl);
			} else {
				popAlert(result.title, result.message, "error");
			}
		} else {
			popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			console.log(response);
		}
	} else {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {id: itemId, userId: userId, itemSource: source}
		});
		const response = await api.post('/UserAPI?method=returnCheckout', postBody);
		console.log(response);

		if (response.ok) {
			const fetchedData = response.data;
			const result = fetchedData.result;

			if (result.success === true) {
				popAlert(result.title, result.message, "success");
				await reloadCheckedOutItems(libraryUrl);
			} else {
				popAlert(result.title, result.message, "error");
			}
		} else {
			popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			console.log(response);
		}
	}

}

export async function viewOnlineItem(userId, id, source, accessOnlineUrl, libraryUrl) {
	const postBody = await postData();

	if (source === "hoopla") {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(),
			auth: createAuthTokens(),
			params: {userId: userId, itemId: id, itemSource: source}
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

export async function viewOverDriveItem(userId, formatId, overDriveId, libraryUrl) {
	const postBody = await postData();

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {userId: userId, overDriveId: overDriveId, formatId: formatId, itemSource: "overdrive"}
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

/* ACTIONS ON HOLDS */
export async function freezeHold(cancelId, recordId, source, libraryUrl, patronId, selectedReactivationDate = null) {
	const postBody = await postData();

	const today = moment().format('YYYY-MM-DD');
	let reactivationDate = null;
	if(selectedReactivationDate) {
		reactivationDate = moment(selectedReactivationDate).format('YYYY-MM-DD');
		if(reactivationDate === "Invalid date") {
			reactivationDate = null;
		} else if (reactivationDate === today) {
			reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
		}
	} else {
		reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
	}

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			holdId: cancelId,
			recordId: recordId,
			itemSource: source,
			reactivationDate: reactivationDate,
			userId: patronId
		}
	});
	const response = await api.post('/UserAPI?method=freezeHold', postBody);

	if (response.ok) {
		//console.log(response);
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert("Hold frozen", result.message, "success");
			// reload patron data in the background
			await reloadHolds(libraryUrl);
		} else {
			popAlert("Unable to freeze hold", result.message, "error");
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function freezeHolds(data, libraryUrl, selectedReactivationDate = null) {
	const postBody = await postData();

	const today = moment().format('YYYY-MM-DD');
	let reactivationDate = null;
	if(selectedReactivationDate) {
		reactivationDate = moment(selectedReactivationDate).format('YYYY-MM-DD');
		if(reactivationDate === "Invalid date") {
			reactivationDate = null;
		} else if (reactivationDate === today) {
			reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
		}
	} else {
		reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
	}

	let numSuccess = 0;
	let numFailed = 0;

	const holdsToFreeze = data.map(async (hold, index) => {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {
				sessionId: GLOBALS.appSessionId,
				holdId: hold.cancelId,
				recordId: hold.recordId,
				itemSource: hold.source,
				reactivationDate: reactivationDate,
				userId: hold.patronId
			}
		});
		const response = await api.post('/UserAPI?method=freezeHold', postBody);
		if (response.ok) {
			const fetchedData = response.data;
			const result = fetchedData.result;

			if (result.success === true) {
				numSuccess = numSuccess + 1;
			} else {
				numFailed = numFailed + 1;
			}
		} else {
			popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			console.log(response);
		}
	})

	await reloadHolds(libraryUrl)

	let message = "";
	let status = "success";
	if(numSuccess > 0) {
		message = message.concat(numSuccess + " holds frozen successfully.");
	}

	if(numFailed > 0) {
		status = "warning";
		message = message.concat(" Unable to freeze " + numFailed + " holds.")
	}
	popAlert("Holds frozen", message, status)
}

export async function thawHold(cancelId, recordId, source, libraryUrl, patronId) {
	const postBody = await postData();

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			holdId: cancelId,
			recordId: recordId,
			itemSource: source,
			userId: patronId
		}
	});
	const response = await api.post('/UserAPI?method=activateHold', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert("Hold thawed", result.message, "success");
			// reload patron data in the background
			await reloadHolds(libraryUrl);
		} else {
			popAlert("Unable to thaw hold", result.message, "error");
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function thawHolds(data, libraryUrl) {
	const postBody = await postData();

	let numSuccess = 0;
	let numFailed = 0;

	const holdsToThaw = data.map(async (hold, index) => {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {
				sessionId: GLOBALS.appSessionId,
				holdId: hold.cancelId,
				recordId: hold.recordId,
				itemSource: hold.source,
				userId: hold.patronId,
			}
		});
		const response = await api.post('/UserAPI?method=activateHold', postBody);
		if (response.ok) {
			const fetchedData = response.data;
			const result = fetchedData.result;

			if (result.success === true) {
				numSuccess = numSuccess + 1;
			} else {
				numFailed = numFailed + 1;
			}
		} else {
			popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			console.log(response);
		}
	})

	await reloadHolds(libraryUrl)

	let message = "";
	let status = "success";
	if(numSuccess > 0) {
		message = message.concat(numSuccess + " holds thawed successfully.");
	}

	if(numFailed > 0) {
		status = "warning";
		message = message.concat(" Unable to thaw " + numFailed + " holds.")
	}
	popAlert("Holds thawed", message, status)
}

export async function cancelHold(cancelId, recordId, source, libraryUrl, patronId) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {
			sessionId: GLOBALS.appSessionId,
			cancelId: cancelId,
			recordId: recordId,
			itemSource: source,
			userId: patronId
		}
	});
	const response = await api.post('/UserAPI?method=cancelHold', postBody);

	console.log(response);
	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			popAlert(result.title, result.message, "success");
			// reload patron data in the background
			await reloadHolds(libraryUrl);
		} else {
			popAlert(result.title, result.message, "error");
		}

		await getProfile();

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function cancelHolds(data, libraryUrl) {
	const postBody = await postData();

	let numSuccess = 0;
	let numFailed = 0;

	const holdsToCancel = data.map(async (hold, index) => {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutFast,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {
				sessionId: GLOBALS.appSessionId,
				cancelId: hold.cancelId,
				recordId: hold.recordId,
				itemSource: hold.source,
				userId: hold.patronId,
			}
		});
		const response = await api.post('/UserAPI?method=cancelHold', postBody);
		if (response.ok) {
			const fetchedData = response.data;
			const result = fetchedData.result;

			if (result.success === true) {
				numSuccess = numSuccess + 1;
			} else {
				console.log(response);
				numFailed = numFailed + 1;
			}
		} else {
			popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			console.log(response);
		}
	})

	await reloadHolds(libraryUrl)

	let message = "";
	let status = "success";
	if(numSuccess > 0) {
		message = message.concat(numSuccess + " holds cancelled successfully.");
	}

	if(numFailed > 0) {
		status = "warning";
		message = message.concat(" Unable to cancel " + numFailed + " holds.")
	}
	popAlert("Holds cancelled", message, status)
}

export async function changeHoldPickUpLocation(holdId, newLocation, libraryUrl, userId) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {sessionId: GLOBALS.appSessionId, holdId: holdId, newLocation: newLocation, userId: userId}
	});
	const response = await api.post('/UserAPI?method=changeHoldPickUpLocation', postBody);

	if (response.ok) {
		const fetchedData = response.data;
		const result = fetchedData.result;

		if (result.success === true) {
			console.log(result);
			popAlert(result.title, result.message, "success");
			// reload patron data in the background
			await reloadHolds(libraryUrl);
		} else {
			popAlert(result.title, result.message, "error");
		}

	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

export async function updateOverDriveEmail(itemId, source, patronId, overdriveEmail, promptForOverdriveEmail, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {
			itemId: itemId,
			itemSource: source,
			userId: patronId,
			overdriveEmail: overdriveEmail,
			promptForOverdriveEmail: promptForOverdriveEmail
		}
	});
	const response = await api.post('/UserAPI?method=updateOverDriveEmail', postBody);

	if (response.ok) {
		const responseData = response.data;
		const result = responseData.result;
		// reload patron data in the background
		return result;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
	}
}

/* ACTIONS ON BROWSE CATEGORIES */
export async function dismissBrowseCategory(libraryUrl, browseCategoryId, patronId, discoveryVersion) {
	const postBody = await postData();
	if(discoveryVersion >= "22.05.00") {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {browseCategoryId: browseCategoryId}
		});
		const response = await api.post('/UserAPI?method=dismissBrowseCategory', postBody);
		console.log(response);
		if (response.ok) {
			return response.data;
		} else {
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
			console.log(response);
		}
	} else {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {browseCategoryId: browseCategoryId, patronId: patronId}
		});
		const response = await api.post('/UserAPI?method=dismissBrowseCategory', postBody);
		console.log(response);
		if (response.ok) {
			return response.data;
		} else {
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
			console.log(response);
		}
	}

}

export async function showBrowseCategory(libraryUrl, browseCategoryId, patronId, discoveryVersion) {
	const postBody = await postData();

	if(discoveryVersion >= "22.05.00") {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {browseCategoryId: browseCategoryId}
		});
		const response = await api.post('/UserAPI?method=showBrowseCategory', postBody);

		if (response.ok) {
			await getPatronBrowseCategories(libraryUrl, patronId);
			await getBrowseCategories(libraryUrl, discoveryVersion);
			return response.data;
		} else {
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
			console.log(response);
		}
	} else {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {browseCategoryId: browseCategoryId, patronId: patronId}
		});
		const response = await api.post('/UserAPI?method=showBrowseCategory', postBody);

		if (response.ok) {
			await getPatronBrowseCategories(libraryUrl, patronId);
			await getBrowseCategories(libraryUrl, discoveryVersion);
			return response.data;
		} else {
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
			console.log(response);
		}
	}
}

export async function addLinkedAccount(username, password, libraryUrl) {
	let postBody = await postData();
	postBody.append('accountToLinkUsername', username);
	postBody.append('accountToLinkPassword', password);
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=addAccountLink', postBody);
	if(response.ok) {
		await getLinkedAccounts(libraryUrl);
		if(response.data.result.success) {
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

export async function removeLinkedAccount(id, libraryUrl) {
	let postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=removeAccountLink&idToRemove=' + id, postBody);
	if(response.ok) {
		await getLinkedAccounts(libraryUrl);
		if(response.data.result.success) {
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

export async function saveLanguage(code, libraryUrl) {
	let postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
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

export async function cancelVdxRequest(libraryUrl, sourceId, cancelId) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=cancelVdxRequest', postBody);
	if (response.ok) {
		if(response.data.result.success === "true") {
			popAlert(response.data.result.title, response.data.result.message, "success");
		} else {
			console.log(response);
			popAlert("Error", response.data.result.message, "error");
		}
	} else {
		const problem = problemCodeMap(response.problem);
		popAlert(problem.title, problem.message, "warning");
		console.log(response);
	}
}