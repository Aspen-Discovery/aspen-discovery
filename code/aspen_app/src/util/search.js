import React from 'react';
import {create} from 'apisauce';
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';

// custom components and helper files
import {createAuthTokens, ENDPOINT, getHeaders, getResponseCode, postData} from './apiAuth';
import {translate} from '../translations/translations';
import {popToast} from '../components/loadError';
import {GLOBALS} from './globals';
import {PATRON} from './loadPatron';
import {LIBRARY} from './loadLibrary';

export const SEARCH = {
	'term': null,
	'id': null,
	'hasPendingChanges': false,
	'sortMethod': 'relevance',
	'appliedFilters': [],
	'sortList': [],
	'availableFacets': [],
	'defaultFacets': [],
	'pending': [],
};

const endpoint = ENDPOINT.search;

export async function searchResults(
		searchTerm, pageSize = 100, page, libraryUrl, filters = '') {
	let solrScope = '';
	if (GLOBALS.solrScope !== 'unknown') {
		solrScope = GLOBALS.solrScope;
	} else {
		try {
			solrScope = await AsyncStorage.getItem('@solrScope');
		} catch (e) {
			console.log(e);
		}
	}

	const api = create({
		baseURL: libraryUrl + '/API/',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(),
		params: {
			library: solrScope,
			lookfor: searchTerm,
			pageSize: pageSize,
			page: page,
		},
		auth: createAuthTokens(),
	});

	const response = await api.get('/SearchAPI?method=getAppSearchResults' + filters);
	if (response.ok) {
		SEARCH.term = response.data.result.lookfor;
		return response;
	} else {
		popToast(translate('error.no_server_connection'),
				translate('error.no_library_connection'), 'warning');
		console.log(response);
		return response;
	}
}

export async function getDefaultFacets(url, limit = 5) {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		params: {limit: limit},
		auth: createAuthTokens(),
	});
	const data = await discovery.get(endpoint.url + 'getDefaultFacets');
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.defaultFacets = response.data.result.data;
		return response.data.result;
	} else {
		return response;
	}
}

export async function getSearchResults(
		searchTerm, pageSize = 25, page, libraryUrl, filters = '') {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
	});
	const data = await discovery.get(endpoint.url + 'searchLite' + filters, {
		library: PATRON.scope,
		lookfor: searchTerm,
		pageSize: pageSize,
		page: page,
	});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.id = response.data.result.id;
		SEARCH.sortMethod = response.data.result.sort;
		SEARCH.term = response.data.result.lookfor;

		await getSortList();
		await getAvailableFacets();
		await getAppliedFilters();

		return {
			success: response.success,
			data: response.data.result,
		};
	} else {
		return {
			success: response.success,
			data: [],
			error: response.error ?? [],
		};
	}
}

export async function getAppliedFilters() {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
	});
	const data = await discovery.get(
			endpoint.url + 'getAppliedFilters',
			{id: SEARCH.id});
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.appliedFilters = response.data.result.data;
		_.forEach(SEARCH.appliedFilters, function(filter, key) {
			addAppliedFilter(filter.field, filter.value, true);
		});
		return response.data.result.data;
	} else {
		return response;
	}
}

export async function getSortList() {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			id: SEARCH.id,
		},
	});
	const data = await discovery.get(endpoint.url + 'getSortList');
	const response = getResponseCode(data);
	if (response.success) {
		SEARCH.sortList = response.data.result;
		return response.data.result;
	} else {
		return response;
	}
}

export async function getAvailableFacets() {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			includeSortList: true,
			id: SEARCH.id,
		},
	});
	const data = await discovery.get(endpoint.url + 'getAvailableFacets');
	const response = getResponseCode(data);
	if (response.success) {
		await getAvailableFacetsKeys();
		SEARCH.availableFacets = response.data.result;
		return response.data.result;
	} else {
		return response;
	}
}

export async function getAvailableFacetsKeys() {
	const discovery = create({
		baseURL: LIBRARY.url,
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			includeSortList: true,
			id: SEARCH.id,
		},
	});
	const data = await discovery.get(endpoint.url + 'getAvailableFacetsKeys');
	const response = getResponseCode(data);
	if (response.success) {
		const keys = response.data.result.options;
		let map = [];
		let i = 0;
		_.mapKeys(keys, function(value, key) {
			let groupByKey = {
				'field': value,
				'key': i++,
				'facets': [],
			};
			map = _.concat(map, groupByKey);
		});

		SEARCH.pendingFilters = map;
		return map;
	} else {
		return response;
	}
}

export async function getFacetCluster() {
	return false;
}

export async function categorySearchResults(
		category, limit = 25, page, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {
			limit: limit,
			id: category,
			page: page,
		},
		auth: createAuthTokens(),
	});
	const response = await api.post('/SearchAPI?method=getAppBrowseCategoryResults', postBody);
	if (response.ok) {
		return response;
	} else {
		console.log(response);
		return response;
	}
}

export async function filteredSearchResults(
		searchTerm, pageSize = 100, page, libraryUrl, filters) {
	let solrScope = '';
	if (GLOBALS.solrScope !== 'unknown') {
		solrScope = GLOBALS.solrScope;
	} else {
		try {
			solrScope = await AsyncStorage.getItem('@solrScope');
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
		params: {
			library: solrScope,
			lookfor: searchTerm,
			pageSize: pageSize,
			page: page,
		},
		auth: createAuthTokens(),
	});

	const response = await api.get(
			'/SearchAPI?method=getAppSearchResults' + filterParams);
	if (response.ok) {
		return response.data.result;
	} else {
		popToast(translate('error.no_server_connection'),
				translate('error.no_library_connection'), 'warning');
		console.log(response);
		return response;
	}
}

export async function listofListSearchResults(searchId, limit = 25, page, libraryUrl) {
	const myArray = searchId.split('_');
	let id = myArray[myArray.length - 1];

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {
			limit: limit,
			id: id,
			page: page,
		},
		auth: createAuthTokens(),
	});
	const response = await api.post('/SearchAPI?method=getListResults', postBody);
	if (response.ok) {
		return response.data.result;
	} else {
		console.log(response);
		return response;
	}
}

export async function savedSearchResults(
		searchId, limit = 25, page, libraryUrl) {
	const myArray = searchId.split('_');
	let id = myArray[3];

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {
			limit: limit,
			id: id,
			page: page,
		},
		auth: createAuthTokens(),
	});
	const response = await api.post(
			'/SearchAPI?method=getSavedSearchResults',
			postBody);
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
		let thisFormat = item.split('#');
		thisFormat = thisFormat[thisFormat.length - 1];
		formats.push(thisFormat);
	});

	formats = _.uniq(formats);
	return formats;
}

/**
 * Functions for facets and filtering
 * Requires: Aspen Discovery 22.11.00 or greater
 **/

/**
 * Returns a string of encoded values to append to a search URL
 **/
export function buildParamsForUrl() {
	const filters = SEARCH.pendingFilters;
	console.log(filters);
	let params = [];
	_.forEach(filters, function(filter) {
		const field = filter.field;
		const facets = filter.facets;

		if (_.size(facets) > 0) {
			_.forEach(facets, function(facet) {
				if (field === 'sort_by') {
					params = params.concat('&sort=' + encodeURI(facet));
				} else {
					params = params.concat('&filter[]=' + encodeURI(field + ':' + facet));
				}
			});
		}
	});

	params = _.join(params, '');
	console.log(params);
	return params;
}

/**
 * Iterates over objects of the collection of available facet clusters, returning objects of all
 * elements that [field, value] returns truthy for. If no matches are found, returns empty array.
 *
 * **Sample Call:**
 * > `const cluster = getFilterCluster('Available at?', 'field', 'available_at')`
 *
 * **Sample Response:**
 * > `{"count": 116, "display": "Main Library", "field": "available_at", "isApplied": false, "multiSelect": false, "value": "Main Library"}`
 *
 * @param {string} cluster The name of the cluster to search through
 * @param {string} key The key to find a value match to
 * @param {string} value The value to find a key match to
 **/
export function getFilterCluster(cluster, key, value) {
	if (cluster && key && value) {
		return _.filter(SEARCH.availableFacets.data[cluster], [
			key, value,
		]);
	}
	return [];
}

export function formatOptions(cluster) {
	if (cluster) {
		return _.castArray(SEARCH.availableFacets.data[cluster]);
	}
	return [];
}

export function getAppliedFacets(cluster) {
	if (cluster) {
		return _.filter(SEARCH.appliedFilters, 'isApplied');
	}
	return [];
}

export function addAppliedFilter(group, values, multiSelect = false) {
	if (group) {
		if (_.isArray(values) || _.isObject(values)) {
			_.forEach(values, function(value) {
				const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
				if (i !== -1) {
					if (multiSelect) {
						let newValue = _.castArray(value);
						SEARCH.pendingFilters[i]['facets'] = _.concat(SEARCH.pendingFilters[i]['facets'], newValue);
					} else {
						SEARCH.pendingFilters[i]['facets'] = _.castArray(value);
					}
					SEARCH.pendingFilters[i]['facets'] = _.uniqWith(SEARCH.pendingFilters[i]['facets'], _.isEqual);
					return true;
				}
			});
		} else {
			const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
			if (i !== -1) {
				SEARCH.pendingFilters[i]['facets'] = _.castArray(values);
				return true;
			}
		}
	}
	return false;
}

export function removeAppliedFilter(group, values) {
	console.log('group >', group);
	console.log('values >', values);
	if (group) {
		if (_.isArray(values) || _.isObject(values)) {
			_.forEach(values, function(value) {
				const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
				console.log('i >', i);
				if (i !== -1) {
					console.log(SEARCH.pendingFilters[i]['facets']);
					SEARCH.pendingFilters[i]['facets'] = _.pull(SEARCH.pendingFilters[i]['facets'], value);
					return true;
				}
			});
		} else {
			const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
			console.log('i >', i);
			if (i !== -1) {
				console.log(SEARCH.pendingFilters[i]['facets']);
				SEARCH.pendingFilters[i]['facets'] = _.pull(SEARCH.pendingFilters[i]['facets'], values);
				return true;
			}
		}
	}
	return false;
}

export function getPendingFacets(cluster) {
	if (cluster) {
		return _.filter(SEARCH.pendingFilters, ['field', cluster]);
	}
	return [];
}