import React from "react";
import {create} from 'apisauce';
import _ from "lodash";

// custom components and helper files
import {translate} from "./translations";
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {popAlert, popToast} from "../components/loadError";

export async function getProfile(forceReload = false) {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});

	if (forceReload === false) {
		response = await api.post('/UserAPI?method=getPatronProfile', postBody);
	} else {
		response = await api.post('/UserAPI?method=getPatronProfile&reload', postBody);
	}
	if (response.ok) {
		const results = response.data;
		const result = results.result;
		const profile = result.profile;

		global.homeLocationId = profile.homeLocationId;
		global.barcode = profile.cat_username;
		global.interfaceLanguage = profile.interfaceLanguage;
		global.patronId = profile.id;
		global.rememberHoldPickupLocation = profile.rememberHoldPickupLocation;
		global.pickupLocationId = profile.pickupLocationId;
		global.promptForOverdriveEmail = profile.promptForOverdriveEmail;
		global.overdriveEmail = profile.overdriveEmail;
		global.numCheckedOut = profile.numCheckedOut;
		global.numOverdue = profile.numOverdue;
		global.numHolds = profile.numHolds;
		global.numHoldsAvailable = profile.numHoldsAvailable;

	} else {
		return response.problem;
	}
}

export async function getILSMessages() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutSlow,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getILSMessages', postBody);
	if (response.ok) {
		const data = response.data;
		const result = data.result;
		return result.messages;
	} else {
		return response.problem;
	}
}

export async function getCheckedOutItems(forceReload = false, silentReload = true) {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all'},
		auth: createAuthTokens()
	});
	if (forceReload === false) {
		response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
		if (response.ok) {
			const result = response.data;
			const fetchedData = result.result;
			global.checkedOutItems = fetchedData.checkedOutItems;
		} else {
			return response.problem;
		}
	} else {
		response = await api.post('/UserAPI?method=getPatronCheckedOutItems&refreshCheckouts=' + forceReload, postBody);
		if (response.ok) {
			const result = response.data;
			const fetchedData = result.result;
			global.checkedOutItems = fetchedData.checkedOutItems;
			if (silentReload === false) {
				popAlert("Reload complete", "Checked out items have been refreshed", "success");
			}
		} else {
			const fetchedData = response.problem;
			if (silentReload === false) {
				popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
			}
			return fetchedData;
		}
	}
}

export async function getHolds(forceReload = false, silentReload = true) {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all'},
		auth: createAuthTokens()
	});
	if (forceReload === false) {
		response = await api.post('/UserAPI?method=getPatronHolds', postBody);
		if (response.ok) {
			const result = response.data;
			const fetchedData = result.result;
			const allHolds = fetchedData.holds;
			global.allHolds = allHolds;
			if (global.allHolds) {
				try {
					global.unavailableHolds = Object.values(allHolds.unavailable);
				} catch (error) {
					global.unavailableHolds = [];
				}
				try {
					global.availableHolds = Object.values(allHolds.available);
				} catch (error) {
					global.availableHolds = [];
				}
				try {
					global.allUserHolds = global.availableHolds.concat(global.unavailableHolds);
				} catch (error) {
					global.allUserHolds = [];
				}
			} else {
				global.allHolds = [];
			}

			return allHolds;
		} else {
			return response.problem;
		}
	} else {
		response = await api.post('/UserAPI?method=getPatronHolds&refreshHolds=' + forceReload, postBody);
		if (response.ok) {
			const result = response.data;
			const fetchedData = result.result;
			const allHolds = fetchedData.holds;
			global.allHolds = allHolds;
			global.unavailableHolds = Object.values(allHolds.unavailable);
			global.availableHolds = Object.values(allHolds.available);
			global.allUserHolds = global.availableHolds.concat(global.unavailableHolds);
			if (silentReload === false) {
				popAlert("Reload complete", "Holds have been refreshed", "success");
			}
			return allHolds;
		} else {
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
		}
	}
}

export async function getHiddenBrowseCategories() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: global.timeoutAverage,
		headers: getHeaders(true),
		params: {patronId: global.patronId},
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
	if (response.ok) {
		const result = response.data.result;
		const categories = result.categories;

		const hiddenCategories = [];
		if (_.isArray(categories) === true) {
			if (categories.length > 0) {
				categories.map(function (category, index, array) {
					hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
				});
			}
		}


		return hiddenCategories;
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
	}

}