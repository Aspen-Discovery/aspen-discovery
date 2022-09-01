import React from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {create} from 'apisauce';
import _ from "lodash";
import {GLOBALS} from "./globals";

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {removeData} from "./logout";

/**
 * Fetch branch/location information
 **/
export async function getLocationInfo(library, location) {
	let solrScope;
	try {
		solrScope = await AsyncStorage.getItem("@solrScope");
	} catch (e) {
		console.log(e);
	}

	let profile = [];

	const api = create({
		baseURL: library.baseUrl + '/API',
		timeout: 10000,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLocationInfo', {
		id: location.locationId,
		library: solrScope,
		version: GLOBALS.appVersion
	});
	if (response.ok) {
		if(response.data.result.success) {
			if(typeof response.data.result.location !== 'undefined') {
				profile = response.data.result.location;
				//console.log("Location profile saved")
			} else {
				console.log("Location undefined.")
				console.log(response);
			}
			await AsyncStorage.setItem('@locationInfo', JSON.stringify(profile));
			return profile;
		}
		return profile;
	} else {
		console.log("Unable to fetch location.");
		console.log(response);
		return profile;
	}
}

/**
 * Fetch library information
 **/
export async function getLibraryInfo(libraryId, libraryUrl, timeout) {
	let profile = [];
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: timeout,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLibraryInfo', {id: libraryId});
	if (response.ok) {
		if(response.data.result.success) {
			if(typeof response.data.result.library !== 'undefined') {
				profile = response.data.result.library;
				//console.log("Library profile saved");
			} else {
				console.log(response);
			}
			await AsyncStorage.setItem('@libraryInfo', JSON.stringify(profile));
			return profile;
		}
		return profile;
	} else {
		console.log("Unable to fetch library.");
		console.log(response);
		return profile;
	}
}

/**
 * Fetch settings for app that are maintained by the library
 **/
export async function getAppSettings(url, timeout, slug) {
	const api = create({
		baseURL: url + '/API',
		timeout: timeout,
		headers: getHeaders(),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getAppSettings', {slug: slug});
	if (response.ok) {
		const appSettings = response.data.result.settings;
		await AsyncStorage.setItem('@appSettings', JSON.stringify(appSettings));
		console.log("App settings saved")
		return response.data.result;
	} else {
		console.log(response);
	}
}

/**
 * Fetch valid pickup locations for the patron
 **/
export async function getPickupLocations(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);

	if (response.ok) {
		let locations = [];
		const data = response.data.result.pickupLocations;
		if(_.isObject(data) || _.isArray(data)) {
			locations = data.map(({displayName, code, locationId}) => ({
				key: locationId,
				locationId: locationId,
				code: code,
				name: displayName,
			}));
		}
		await AsyncStorage.setItem('@pickupLocations', JSON.stringify(locations));
		//console.log("Pickup locations saved")
		//console.log(locations);
		return locations;
	} else {
		console.log(response);
	}
}

/**
 * Fetch active browse categories for the branch/location
 **/
export async function getBrowseCategories(libraryUrl, discoveryVersion, limit = null) {
	if(libraryUrl) {
		const postBody = await postData();
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {maxCategories: limit, LiDARequest: true}
		});
		const hiddenCategories = [];
		if(discoveryVersion < "22.07.00") {
			const responseHiddenCategories = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
			console.log(responseHiddenCategories.data);
			if(responseHiddenCategories.ok) {
				if(typeof responseHiddenCategories.data.result !== "undefined") {
					const categories = responseHiddenCategories.data.result.categories;
					if (_.isArray(categories) === true) {
						if (categories.length > 0) {
							categories.map(function (category, index, array) {
								hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
							});
						}
					}
				}
			}
		}
		let response = '';
		response = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
		//console.log(response);
		if (response.status === 403) {
			await removeData().then(res => {
				console.log("Session ended.")
			});
		}
		if (response.ok) {
			//console.log(response.data);
			const items = response.data.result;
			let allCategories = [];
			if(typeof items !== "undefined") {
				items.map(function (category, index, array) {
					const subCategories = category['subCategories'];
					const listOfLists = category['lists'];
					const items = category['records'];
					const lists = [];

					//console.log(category);

					if(discoveryVersion >= "22.07.00") {
						if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
							subCategories.forEach(item => allCategories.push({
								'key': item.key,
								'title': item.title,
								'source': item.source,
								'records': item.records,
							}))
						} else {
							if (typeof subCategories !== "undefined" || typeof listOfLists !== "undefined" || typeof items !== "undefined") {
								if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
									subCategories.forEach(item => allCategories.push({
										'key': item.key,
										'title': item.title,
										'source': item.source,
										'records': item.records,
									}))
								} else {
									if(typeof listOfLists !== "undefined" && listOfLists.length !== 0) {
										let array = _.values(listOfLists);
										//console.log(array);
										listOfLists.forEach(item => lists.push({
											'id': item.sourceId,
											'categoryId': item.id,
											'source': 'List',
											'title_display': item.title,
										}))
									}

									let id = category.key;
									let categoryId = category.key;
									if(lists.length !== 0) {
										if(typeof category.listId !== "undefined") {
											id = category.listId;
										}

										let numNewTitles = 0;
										if(typeof category.numNewTitles !== "undefined") {
											numNewTitles = category.numNewTitles;
										}
										allCategories.push({'key': id, 'title': category.title, 'source': category.source, 'numNewTitles': numNewTitles, 'records': lists, 'id': categoryId});
									} else {
										if(typeof category.listId !== "undefined") {
											id = category.listId;
										}

										let numNewTitles = 0;
										if(typeof category.numNewTitles !== "undefined") {
											numNewTitles = category.numNewTitles;
										}
										allCategories.push({'key': id, 'title': category.title, 'source': category.source, 'numNewTitles': numNewTitles, 'records': category.records, 'id': categoryId});
									}
								}

							}
						}


					} else if(discoveryVersion >= "22.05.00" || discoveryVersion <= "22.06.10") {
						if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
							subCategories.forEach(item => allCategories.push({
								'key': item.key,
								'title': item.title,
								'records': item.records,
							}))
						} else {
							//allCategories.push({'key': category.key, 'title': category.title});

							if (typeof subCategories != "undefined") {
								if (subCategories.length !== 0) {
									subCategories.forEach(item => allCategories.push({
										'key': item.key,
										'title': item.title,
										'records': item.records,
									}))
								} else {
									allCategories.push({'key': category.key, 'title': category.title, 'records': category.records});
								}

							}
						}
					} else {
						if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
							subCategories.forEach(item => allCategories.push({
								'key': item.key,
								'title': item.title,
							}))
						} else {
							allCategories.push({'key': category.key, 'title': category.title});

							if (typeof subCategories != "undefined") {
								if (subCategories.length !== 0) {
									subCategories.forEach(item => allCategories.push({
										'key': item.key,
										'title': item.title,
									}))
								} else {
									allCategories.push({'key': category.key, 'title': category.title});
								}

							}
						}
					}
				});
			}


			allCategories = _.pullAllBy(allCategories, hiddenCategories, 'key');
			//console.log(allCategories);
			return allCategories;
		} else {
			console.log(response);
		}
	} else {
		console.log("No library URL to fetch browse categories.");
	}
}

export async function getLanguages(libraryUrl) {
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: 10000,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.get('/SystemAPI?method=getLanguages');
	if(response.ok) {
		if (typeof response.data.result !== 'undefined') {
			const languages = response.data.result.languages;
			await AsyncStorage.setItem('@libraryLanguages', JSON.stringify(languages));
			console.log("Library languages saved")
		}
	} else {
		console.log(response);
	}
}