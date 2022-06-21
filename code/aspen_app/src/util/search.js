import React from "react";
import {create} from 'apisauce';
import * as Sentry from 'sentry-expo';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {translate} from "../translations/translations";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";

export async function searchResults(searchTerm, pageSize = 100, page, libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders,
		params: {library: global.solrScope, lookfor: searchTerm, pageSize: pageSize, page: page},
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
		console.log(response.data);
	} else {
		console.log(response);
	}
}