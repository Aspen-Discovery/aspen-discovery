import React, {useEffect, useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {create} from 'apisauce';
import _ from "lodash";
import {GLOBALS} from "./globals";

// custom components and helper files
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "./apiAuth";
import {popAlert, popToast} from "../components/loadError";

export async function getProfile() {
	let postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getPatronProfile&reload&linkedUsers=true', postBody);
	if(response.ok) {
		const profile = response.data.result.profile;
		await AsyncStorage.setItem('@patronProfile', JSON.stringify(profile));
		await getILSMessages();
		console.log("User profile saved")
	} else {
		console.log(response);
	}
}

export async function getILSMessages() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getILSMessages', postBody);
	if (response.ok) {
		const messages = response.data.result.messages;
		await AsyncStorage.setItem('@ILSMessages', JSON.stringify(messages));
		console.log("User ILS messages saved")
	} else {
		console.log(response);
	}
}

export async function getCheckedOutItems() {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true', refreshCheckouts: 'true'},
		auth: createAuthTokens()
	});

	response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
	if (response.ok) {
		let items = response.data.result.checkedOutItems;
		items = _.sortBy(items, ['daysUntilDue', 'title'])
		await AsyncStorage.setItem('@patronCheckouts', JSON.stringify(items));
		console.log("User checkouts saved");
	} else {
		console.log(response);
	}

}

export async function getHolds() {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true', refreshHolds: 'true'},
		auth: createAuthTokens()
	});
	response = await api.post('/UserAPI?method=getPatronHolds', postBody);
	if (response.ok) {
		const items = response.data.result.holds;
		let holds;
		let holdsReady = [];
		let holdsNotReady = [];

		if(typeof items.unavailable !== "undefined") {
			holdsNotReady = Object.values(items.unavailable)
		}

		if(typeof items.available !== "undefined") {
			holdsReady = Object.values(items.available)
		}

		holds = holdsReady.concat(holdsNotReady);

		await AsyncStorage.setItem('@patronHolds', JSON.stringify(holds));
		await AsyncStorage.setItem('@patronHoldsNotReady', JSON.stringify(holdsNotReady));
		await AsyncStorage.setItem('@patronHoldsReady', JSON.stringify(holdsReady));
		return holds;
	} else {
		console.log(response);
	}
}

export async function getPatronBrowseCategories() {
	let browseCategories = [];
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		params: {patronId: global.patronId},
		auth: createAuthTokens()
	});
	const responseHiddenCategories = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
	if(responseHiddenCategories.ok) {
		const categories = responseHiddenCategories.data.result.categories;
		const hiddenCategories = [];
		if (_.isArray(categories) === true) {
			if (categories.length > 0) {
				categories.map(function (category, index, array) {
					hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
				});
			}
		}

		browseCategories = browseCategories.concat(hiddenCategories);
	} else {
		console.log(responseHiddenCategories);
	}

	const responseActiveCategories = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
	if(responseActiveCategories.ok) {
		const categories = responseActiveCategories.data.result;
		const activeCategories = [];
		categories.map(function (category, index, array) {
			const subCategories = category['subCategories'];

			if (subCategories.length !== 0) {
				subCategories.forEach(item => activeCategories.push({
					'key': item.key,
					'title': item.title,
					'isHidden': false
				}))
			} else {
				activeCategories.push({'key': category.key, 'title': category.title, 'isHidden': false});
			}
		});

		browseCategories = browseCategories.concat(activeCategories);
	} else {
		console.log(responseActiveCategories);
	}

	browseCategories = _.uniqBy(browseCategories, 'key');
	browseCategories = _.sortBy(browseCategories, 'title');
	await AsyncStorage.setItem('@patronBrowseCategories', JSON.stringify(browseCategories));
}

export async function getHiddenBrowseCategories() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		params: {patronId: global.patronId},
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
	if (response.ok) {
		const categories = response.data.result.categories;
		let hiddenCategories = [];
		if (_.isArray(categories) === true) {
			if (categories.length > 0) {
				categories.map(function (category, index, array) {
					hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
				});
			}
		}

		await AsyncStorage.setItem('@hiddenBrowseCategories', JSON.stringify(hiddenCategories));
		return hiddenCategories;
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
		console.log(response);
	}

}

export async function getLinkedAccounts() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getLinkedAccounts', postBody);
	if(response.ok) {
		const accounts = response.data.result.linkedAccounts;
		await AsyncStorage.setItem('@linkedAccounts', JSON.stringify(accounts));
		await getProfile();;
		console.log("Linked accounts saved")
	} else {
		console.log(response);
	}
}

export async function getViewers() {
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getViewers', postBody);
	if(response.ok) {
		const viewers = response.data.result.viewers;
		await AsyncStorage.setItem('@viewerAccounts', JSON.stringify(viewers));
		await getProfile();
		console.log("Viewer accounts saved")
	} else {
		console.log(response);
	}
}