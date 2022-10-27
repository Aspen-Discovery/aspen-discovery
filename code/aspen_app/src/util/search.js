import React from "react";
import {create} from 'apisauce';
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import {createAuthTokens, ENDPOINT, getHeaders, getResponseCode, postData} from "./apiAuth";
import {translate} from "../translations/translations";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";
import _ from "lodash";
import {PATRON} from "./loadPatron";
import {LIBRARY} from "./loadLibrary";

export const SEARCH = {
	'term': null,
	'id': null,
	'sortMethod': "relevance",
	'appliedFilters': [],
	'sortList': [],
	'availableFacets': [],
	'defaultFacets': [],
}

const endpoint = ENDPOINT.search;

const discovery = create({
	baseURL: LIBRARY.url,
	timeout: GLOBALS.timeoutAverage,
	auth: createAuthTokens(),
	headers: getHeaders(endpoint.isPost),
});

export async function searchResults(searchTerm, pageSize = 100, page, libraryUrl, filters = "") {
	let solrScope = "";
	if (GLOBALS.solrScope !== "unknown") {
		solrScope = GLOBALS.solrScope;
	} else {
		try {
			solrScope = await AsyncStorage.getItem("@solrScope");
		} catch (e) {
			console.log(e);
		}
	}

	const api = create({
		baseURL: libraryUrl + '/API/',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(),
		params: {library: solrScope, lookfor: searchTerm, pageSize: pageSize, page: page},
		auth: createAuthTokens()
	});

	const response = await api.get('/SearchAPI?method=getAppSearchResults' + filters);
	if (response.ok) {
		SEARCH.term = response.data.result.lookfor;
		let facets = response.config.url;
		facets = facets.split("&").slice(1);
		//formatFacetCluster(facets, true);
		return {
			success: true,
			result: response.data.result,
			filters: facets,
		}
	} else {
		popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
		console.log(response);
		return response
	}
}

export async function getDefaultFacets(limit = 5) {
	const data = await discovery.get(endpoint.url + 'getDefaultFacets', {limit: limit});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.defaultFacets = response.data.result;
		return response.data.result
	} else {
		return response;
	}
}

export async function getSearchResults(searchTerm, pageSize = 25, page, libraryUrl, filters = "") {
	const data = await discovery.get(endpoint.url + 'searchLite' + filters, {
		library: PATRON.scope,
		lookfor: searchTerm,
		pageSize: pageSize,
		page: page
	});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.id = response.data.result.id;
		SEARCH.sortMethod = response.data.result.sort;
		SEARCH.term = response.data.result.lookfor;

		await getAppliedFilters();
		await getSortList();

		return {
			success: response.success,
			data: response.data.result,
		}
	} else {
		return {
			success: response.success,
			data: [],
			error: response.error ?? [],
		}
	}
}

export async function getAppliedFilters() {
	const data = await discovery.get(endpoint.url + 'getAppliedFilters', {id: SEARCH.id});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.appliedFilters = response.data.result;
		return response.data.result
	} else {
		return response;
	}
}

export async function getSortList() {
	const data = await discovery.get(endpoint.url + 'getSortList', {id: SEARCH.id});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.sortList = response.data.result;
		return response.data.result
	} else {
		return response;
	}
}

export async function getAvailableFacets() {
	const data = await discovery.get(endpoint.url + 'getAvailableFacets', {id: SEARCH.id});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.availableFacets = response.data.result;
		return response.data.result
	} else {
		return response;
	}
}

export async function getFacetCluster() {
	return false;
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