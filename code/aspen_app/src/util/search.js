import React from "react";
import {create} from 'apisauce';

// custom components and helper files
import {createAuthTokens, getHeaders} from "./apiAuth";
import {translate} from "../translations/translations";
import {popToast} from "../components/loadError";
import {GLOBALS} from "./globals";

export async function searchResults(searchTerm, pageSize = 100, page) {
	const api = create({
		baseURL: global.libraryUrl + '/API',
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