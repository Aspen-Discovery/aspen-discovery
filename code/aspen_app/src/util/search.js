import React from "react";
import {create} from 'apisauce';
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {translate} from "../translations/translations";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";
import _ from "lodash";

export async function searchResults(searchTerm, pageSize = 100, page, libraryUrl, filters = "") {
	let solrScope = "";
	if(GLOBALS.solrScope !== "unknown") {
		solrScope = GLOBALS.solrScope;
	} else {
		try {
			solrScope = await AsyncStorage.getItem("@solrScope");
		} catch (e) {
			console.log(e);
		}
	}

	const api = create({
		baseURL: libraryUrl,
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders,
		params: {library: solrScope, lookfor: searchTerm, pageSize: pageSize, page: page},
		auth: createAuthTokens()
	});

	const response = await api.get('/API/SearchAPI?method=getAppSearchResults' + filters);
	if (response.ok) {
		let str = response.config.url;
		str = str.split("&").slice(1);
		return {
			result: response.data.result,
			filters: str,
		}
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

export async function filteredSearchResults(searchTerm, pageSize = 100, page, libraryUrl, filters) {
	let solrScope = "";
	if(GLOBALS.solrScope !== "unknown") {
		solrScope = GLOBALS.solrScope;
	} else {
		try {
			solrScope = await AsyncStorage.getItem("@solrScope");
		} catch (e) {
			console.log(e);
		}
	}

	console.log('solrScope: ', solrScope);

	const filterParams = buildUrl(filters);

	const api = create({
		baseURL: 'http://aspen.local:8888/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders,
		params: {library: solrScope, lookfor: searchTerm, pageSize: pageSize, page: page},
		auth: createAuthTokens()
	});

	const response = await api.get('/SearchAPI?method=getAppSearchResults' + filterParams);
	if (response.ok) {
		return response.data.result;
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
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

export async function getCurrentParams() {
	try {
		const searchParams = await AsyncStorage.getItem('@searchParams');
		return searchParams != null ? JSON.parse(searchParams) : null;
	} catch(e) {
		console.log(e);
	}
	return false;
}

export async function createParams(group, value) {
	try {
		let params = [];
		let param = {
			[group]: value,
		}
		params.push(param);
		await AsyncStorage.setItem('@searchParams', JSON.stringify(params));
		return true;
	} catch (e) {
		console.log(e);
	}
	return false;
}

export async function updateParams(group, value) {
	let storage = await getCurrentParams().then(async response => {
		if(response) {
			//other params are set, so we add or remove from it
			let param = {
				[group]: value,
			}
			response.push(param);
			try {
				await AsyncStorage.setItem('@searchParams', JSON.stringify(response))
				return true;
			} catch (e) {
				//for some reason we weren't able to update storage
				console.log(e);
			}
		} else {
			await createParams(group, value);
		}
	})
	return false;
}

function buildUrl(filters) {
	if(_.isArray(filters) && filters.length > 0) {
		let params = [];
		_.map(filters, function(item, index, array) {
			console.log("index: ", index);
			console.log(item);

			//params.push({
			// '&filter[]=' + encodeURI(item.category + ':' + item.value);
			// })
		})
		//console.log(params);
	}
	return false;
}