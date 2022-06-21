import React from "react";
import {create} from "apisauce";
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from "lodash";


import {createAuthTokens, getHeaders, postData, problemCodeMap} from "../util/apiAuth";
import {GLOBALS} from "../util/globals";
import {showILSMessage} from "../components/Notifications";

export async function getTranslation(term, language, libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getTranslation', {
		term: term,
		language: language
	});

	if(response.ok) {
		if(response.data.result.success) {
			let translation = _.values(response.data.result.translations);
			return translation;
		} else {
			return term;
		}
	} else {
		console.log(response);
		// no data yet
	}
}

export async function getTranslations(terms, language, libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getTranslation', {
		terms: terms,
		language: language
	});

	if(response.ok) {
		let translations = response.data.result.translations;
		return translations;
	} else {
		console.log(response);
		// no data yet
	}
}

export async function getDefaultTranslations(libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const terms =
		[
			"Version",
			"Build",
			"Patch",
			"Beta",
			"Login",
			"Logout",
			"OK",
			"Save",
			"Privacy Policy",
			"Contact",
			"Close",
			"Hide",
			"Updating",
			"Loading",
			"Library Barcode",
			"Password/PIN",
			"Discover",
			"Search",
			"Search Results",
			"Card",
			"Library Card",
			"Account",
			"More",
			"Note",
			"Select Your Library",
			"Find Your Library",
			"Reset Geolocation",
			"By",
			"Item Details",
			"Language",
			"No matches found",
			"View Item Details",
			"Where is it?",
			"Holds",
			"Place Hold",
			"Titles on Hold",
			"Ready for Pickup",
			"Author",
			"Format",
			"On Hold For",
			"Pickup Location",
			"Pickup By",
			"Position",
			"View Item Details",
			"Cancelling",
			"Cancel Hold",
			"Cancel All",
			"Freezing",
			"Freeze Hold",
			"Freeze All",
			"Thawing",
			"Thaw Hold",
			"Thaw All",
			"You have no items on hold",
			"Change Pickup Location",
			"Renew",
			"Renew All",
			"Due",
			"Overdue",
			"Return Now",
			"Access Online",
			"Read Online",
			"Listen Online",
			"Watch Online",
			"Settings",
			"Account Summary",
		]
	const tmp = await AsyncStorage.getItem('@libraryLanguages');
	let languages = JSON.parse(tmp);
	languages = _.values(languages);
	languages.map(async (language) => {
		//language.code
		//map through the languages with term list, and save to their unique array named by language code?

		const response = await api.get('/SystemAPI?method=getTranslation', {
			terms: terms,
			language: language.code
		});

		if (response.ok) {
			if(response.data.result.success) {
				const translations = response.data.result.translations;
				await AsyncStorage.setItem(language.code, JSON.stringify(translations));
				console.log(language.displayNameEnglish + " translations saved")
			} else {
				// error
				console.log(response)
			}
		} else {
			console.log(response);
			// no data yet
		}
	})
}

export async function getDefaultTranslation(term, language, libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getDefaultTranslation', {
		term: term,
		languageCode: language
	});

	if(response.ok) {

	} else {
		console.log(response);
		// no data yet
	}
}

export async function getAvailableTranslations() {

}

