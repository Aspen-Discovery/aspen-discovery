import React from "react";
import {create} from 'apisauce';
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {translate} from "../translations/translations";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";
import _ from "lodash";

export async function searchResults(searchTerm, pageSize = 100, page, libraryUrl) {
	let solrScope;
	try {
		solrScope = await AsyncStorage.getItem("@solrScope");
	} catch (e) {
		console.log(e);
	}

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders,
		params: {library: solrScope, lookfor: searchTerm, pageSize: pageSize, page: page},
		auth: createAuthTokens()
	});
	const response = await api.get('/SearchAPI?method=getAppSearchResults');

	if (response.ok) {
		return response;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
		return response;
	}
}

export async function categorySearchResults(category, limit = 25, page, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {limit: limit, id: category, page: page},
		auth: createAuthTokens()
	});
	const response = await api.post('/SearchAPI?method=getAppBrowseCategoryResults', postBody);
	if (response.ok) {
		return response;
	} else {
		console.log(response);
		return response;
	}
}

export async function listofListSearchResults(searchId, limit = 25, page, libraryUrl) {
	console.log(searchId);
	const myArray = searchId.split("_");
	let id = myArray[myArray.length - 1];

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {limit: limit, id: id, page: page},
		auth: createAuthTokens()
	});
	const response = await api.post('/SearchAPI?method=getListResults', postBody);
	if (response.ok) {
		//console.log(response);
		return response.data.result;
	} else {
		console.log(response);
		return response;
	}
}

export async function savedSearchResults(searchId, limit = 25, page, libraryUrl) {
	const myArray = searchId.split("_");
	let id = myArray[3];

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {limit: limit, id: id, page: page},
		auth: createAuthTokens()
	});
	const response = await api.post('/SearchAPI?method=getSavedSearchResults', postBody);
	if (response.ok) {
		console.log(response);
		return response;
	} else {
		console.log(response);
		return response;
	}
}

export function getFormats(data) {
	let formats = [];

	data.map((item) => {
		let thisFormat = item.split("#");
		thisFormat = thisFormat[thisFormat.length - 1];
		formats.push(thisFormat);
	});

	formats = _.uniq(formats);
	return formats;
}