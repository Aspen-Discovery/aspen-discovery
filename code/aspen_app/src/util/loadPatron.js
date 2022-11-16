import React from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {create} from 'apisauce';
import _ from 'lodash';
import {GLOBALS} from './globals';

// custom components and helper files
import {createAuthTokens, ENDPOINT, getHeaders, postData} from './apiAuth';
import {popAlert} from '../components/loadError';

export let PATRON = {
	'userToken': null,
	'scope': null,
	'library': null,
	'location': null,
	'listLastUsed': null,
	'fines': 0,
	'messages': [],
	'num': {
		'checkedOut': 0,
		'holds': 0,
		'lists': 0,
		'overdue': 0,
		'ready': 0,
		'savedSearches': 0,
		'updatedSearches': 0,
	},
	'promptForOverdriveEmail': 1,
	'rememberHoldPickupLocation': 0,
	'pickupLocations': [],
	'language': 'en',
	'coords': {
		'lat': null,
		'long': null,
	},
	'linkedAccounts': [],
	'holds': [],
};

const endpoint = ENDPOINT.user;

export async function getProfile(reload = false) {
	let postBody = await postData();
	let libraryUrl;
	try {
		libraryUrl = await AsyncStorage.getItem('@pathUrl');
	} catch (e) {
		console.log(e);
	}

	if (libraryUrl) {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {reload: reload},
		});
		const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
		await getILSMessages(libraryUrl);
		if (response.ok) {
			if (!_.isUndefined(response.data.result)) {
				if (!_.isUndefined(response.data.result.profile)) {
					return response.data.result.profile;
				}
			}
		}
	}
}

export async function reloadProfile(libraryUrl) {
	let postBody = await postData();

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getPatronProfile&reload&linkedUsers=true', postBody);
	//console.log(response);
	if (response.ok) {
		if (!_.isUndefined(response.data.result)) {
			if (!_.isUndefined(response.data.result.profile)) {
				const profile = response.data.result.profile;
				console.log('User profile forcefully updated');
				await reloadCheckedOutItems(libraryUrl);
				await reloadHolds(libraryUrl);
				return profile;
			}
		}
	} else {
		//console.log(response);
	}

}

export async function getILSMessages(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getILSMessages', postBody);
	if (response.ok) {
		let messages = [];

		if (response.data.result.messages) {
			messages = response.data.result.messages;
			PATRON.messages = messages;
			try {
				await AsyncStorage.setItem('@ILSMessages', JSON.stringify(messages));
			} catch (e) {
				console.log(e);
			}
		} else {
			try {
				await AsyncStorage.setItem('@ILSMessages', JSON.stringify(messages));
			} catch (e) {
				console.log(e);
			}
		}
		return messages;
	}
}

export async function getCheckedOutItems(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true'},
		auth: createAuthTokens(),
	});

	const response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
	if (response.ok) {
		let items = response.data.result.checkedOutItems;
		items = _.sortBy(items, ['daysUntilDue', 'title']);
		await AsyncStorage.setItem('@patronCheckouts', JSON.stringify(items));
		return items;
		//console.log("User checkouts saved");
	} else {
		console.log(response);
	}

}

export async function reloadCheckedOutItems(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true', refreshCheckouts: 'true'},
		auth: createAuthTokens(),
	});

	const response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
	if (response.ok) {
		let items = response.data.result.checkedOutItems;
		items = _.sortBy(items, ['daysUntilDue', 'title']);
		await AsyncStorage.setItem('@patronCheckouts', JSON.stringify(items));
		return items;
		//console.log("User checkouts saved");
	} else {
		console.log(response);
	}
}

export async function getHolds(libraryUrl) {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true'},
		auth: createAuthTokens(),
	});
	response = await api.post('/UserAPI?method=getPatronHolds', postBody);
	if (response.ok) {
		//console.log(response.data);
		let holds;
		let holdsReady = [];
		let holdsNotReady = [];
		if (!_.isUndefined(response.data.result)) {
			if (!_.isUndefined(response.data.result.holds)) {
				const items = response.data.result.holds;

				if (typeof items !== 'undefined') {
					if (typeof items.unavailable !== 'undefined') {
						holdsNotReady = Object.values(items.unavailable);
					}

					if (typeof items.available !== 'undefined') {
						holdsReady = Object.values(items.available);
					}
				}

				holds = holdsReady.concat(holdsNotReady);
				//console.log(holds);

				await AsyncStorage.setItem('@patronHolds', JSON.stringify(holds));
				await AsyncStorage.setItem('@patronHoldsNotReady', JSON.stringify(holdsNotReady));
				await AsyncStorage.setItem('@patronHoldsReady', JSON.stringify(holdsReady));
			}
		}

		PATRON.holds = holds;
		return {
			'holds': holds,
			'holdsReady': holdsReady,
			'holdsNotReady': holdsNotReady,
		};
	} else {
		console.log(response);
	}
}

export async function reloadHolds(libraryUrl) {
	let response;
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true', refreshHolds: 'true'},
		auth: createAuthTokens(),
	});
	response = await api.post('/UserAPI?method=getPatronHolds', postBody);
	if (response.ok) {
		//console.log(response.data);
		const items = response.data.result.holds;
		let holds;
		let holdsReady = [];
		let holdsNotReady = [];

		if (typeof items !== 'undefined') {
			if (typeof items.unavailable !== 'undefined') {
				holdsNotReady = Object.values(items.unavailable);
			}

			if (typeof items.available !== 'undefined') {
				holdsReady = Object.values(items.available);
			}
		}

		holds = holdsReady.concat(holdsNotReady);
		//console.log(holds);

		await AsyncStorage.setItem('@patronHolds', JSON.stringify(holds));
		await AsyncStorage.setItem('@patronHoldsNotReady', JSON.stringify(holdsNotReady));
		await AsyncStorage.setItem('@patronHoldsReady', JSON.stringify(holdsReady));
		return {
			'holds': holds,
			'holdsReady': holdsReady,
			'holdsNotReady': holdsNotReady,
		};
	} else {
		console.log(response);
		return {
			'holds': [],
			'holdsReady': [],
			'holdsNotReady': [],
		};
	}
}

export async function getPatronBrowseCategories(libraryUrl, patronId = null) {

	if (!patronId) {
		try {
			patronId = await AsyncStorage.getItem('@patronProfile');
		} catch (e) {
			console.log(e);
			patronId = null;
		}
	}

	if (patronId) {
		let browseCategories = [];
		const postBody = await postData();
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
		});
		const responseHiddenCategories = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
		if (responseHiddenCategories.ok) {
			const hiddenCategories = [];

			if (typeof responseHiddenCategories.data.result !== 'undefined') {
				const categories = responseHiddenCategories.data.result.categories;
				if (_.isArray(categories) === true) {
					if (categories.length > 0) {
						categories.map(function(category, index, array) {
							hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
						});
					}
				}
				//console.log(hiddenCategories);
			}
			browseCategories = browseCategories.concat(hiddenCategories);

		} else {
			console.log(responseHiddenCategories);
		}

		const responseActiveCategories = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
		if (responseActiveCategories.ok) {
			let numNewTitles = 0;
			let isNew = false;
			const categories = responseActiveCategories.data.result;
			const activeCategories = [];
			categories.map(function(category, index, array) {
				const subCategories = category['subCategories'];

				//console.log(categories);
				if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
					subCategories.forEach(item => activeCategories.push({
						'key': item.key,
						'title': item.title,
					}));
				} else {
					activeCategories.push({'key': category.key, 'title': category.title});
					if (typeof subCategories != 'undefined') {
						if (subCategories.length !== 0) {
							subCategories.forEach(item => activeCategories.push({
								'key': item.key,
								'title': item.title,
							}));
						} else {
							activeCategories.push({'key': category.key, 'title': category.title});
						}

					}
				}
			});

			//console.log(activeCategories);
			browseCategories = browseCategories.concat(activeCategories);
		} else {
			console.log(responseActiveCategories);
		}

		browseCategories = _.uniqBy(browseCategories, 'key');
		browseCategories = _.sortBy(browseCategories, 'title');
		await AsyncStorage.setItem('@patronBrowseCategories', JSON.stringify(browseCategories));
		return browseCategories;
	}
}

export async function getHiddenBrowseCategories(libraryUrl, patronId) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
	if (response.ok) {
		const categories = response.data.result.categories;
		let hiddenCategories = [];
		if (_.isArray(categories) === true) {
			if (categories.length > 0) {
				categories.map(function(category, index, array) {
					hiddenCategories.push({'key': category.id, 'title': category.name, 'isHidden': true});
				});
			}
		}

		await AsyncStorage.setItem('@hiddenBrowseCategories', JSON.stringify(hiddenCategories));
		return hiddenCategories;
	} else {
		console.log(response);
	}

}

export async function getLinkedAccounts(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getLinkedAccounts', postBody);
	if (response.ok) {
		let accounts = [];
		if (!_.isUndefined(response.data.result.linkedAccounts)) {
			accounts = response.data.result.linkedAccounts;
			try {
				await AsyncStorage.setItem('@linkedAccounts', JSON.stringify(accounts));
			} catch (e) {
				console.log(e);
			}
			PATRON.linkedAccounts = accounts;
		}

		return accounts;
		//console.log("Linked accounts saved")
	} else {
		console.log(response);
	}
}

export async function getViewers(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/UserAPI?method=getViewers', postBody);
	if (response.ok) {
		const viewers = response.data.result.viewers;
		try {
			await AsyncStorage.setItem('@viewerAccounts', JSON.stringify(viewers));
		} catch (e) {
			console.log(e);
		}
		//console.log("Viewer accounts saved")
	} else {
		console.log(response);
	}
}

export async function getLists(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/ListAPI?method=getUserLists&checkIfValid=false', postBody);
	if (response.ok) {
		//console.log(response);
		let lists = [];
		if (response.data.result.success) {
			if (!_.isUndefined(response.data.result.lists)) {
				lists = response.data.result.lists;
			}
		}
		return lists;
	} else {
		console.log(response);
	}
}

export async function createList(title, description, access, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {title: title, description: description, access: access},
	});
	const response = await api.post('/ListAPI?method=createList', postBody);
	if (response.ok) {
		await getLists(libraryUrl);
		//await reloadProfile(libraryUrl);
		if (response.data.result.listId) {
			PATRON.listLastUsed = response.data.result.listId;
			await AsyncStorage.setItem('@lastListUsed', response.data.result.listId);
		}
		return response.data.result;
	} else {
		console.log(response);
	}
}

export async function createListFromTitle(title, description, access, items, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {title: title, description: description, access: access, recordIds: items},
	});
	const response = await api.post('/ListAPI?method=createList', postBody);
	if (response.ok) {
		await getLists(libraryUrl);
		//await reloadProfile(libraryUrl);
		if (response.data.result.listId) {
			await AsyncStorage.setItem('@lastListUsed', response.data.result.listId);
			PATRON.listLastUsed = response.data.result.listId;
		}

		let status = 'success';
		let alertTitle = 'Success';
		if (!response.data.result.success) {
			status = 'danger';
			alertTitle = 'Error';
		}

		if (response.data.result.numAdded) {
			popAlert(alertTitle, response.data.result.numAdded + ' added to ' + title, status);
		} else {
			popAlert(alertTitle, 'Title added to ' + title, status);
		}
		return response.data.result;
	} else {
		console.log(response);
	}
}

export async function editList(listId, title, description, access, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {id: listId, title: title, description: description, public: access},
	});
	const response = await api.post('/ListAPI?method=editList', postBody);
	if (response.ok) {
		PATRON.listLastUsed = listId;
		await getLists(libraryUrl);
		//await reloadProfile(libraryUrl);
		await AsyncStorage.setItem('@lastListUsed', listId);
		return response.data;
	} else {
		console.log(response);
	}
}

export async function clearListTitles(listId, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {listId: listId},
	});
	const response = await api.post('/ListAPI?method=clearListTitles', postBody);
	if (response.ok) {
		PATRON.listLastUsed = listId;
		//await getListTitles(listId, libraryUrl);
		return response.data;
	} else {
		console.log(response);
	}
}

export async function addTitlesToList(id, itemId, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {listId: id, recordIds: itemId},
	});
	const response = await api.post('/ListAPI?method=addTitlesToList', postBody);
	if (response.ok) {
		PATRON.listLastUsed = id;
		if (response.data.result.success) {
			popAlert('Success', response.data.result.numAdded + ' added to list', 'success');
		} else {
			popAlert('Error', 'Unable to add item to list', 'error');
		}
		return response.data.result;
	} else {
		console.log(response);
	}
}

export async function getListTitles(listId, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {id: listId},
	});
	const response = await api.post('/ListAPI?method=getListTitles', postBody);
	if (response.ok) {
		//console.log(response);
		return response.data.result.titles;
	} else {
		console.log(response);
	}
}

export async function removeTitlesFromList(listId, title, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {listId: listId, recordIds: title},
	});
	const response = await api.post('/ListAPI?method=removeTitlesFromList', postBody);
	if (response.ok) {
		//console.log(response.data);
		PATRON.listLastUsed = listId;
		await getListTitles(listId, libraryUrl);
		//await AsyncStorage.setItem('@lastListUsed', listId);
		return response.data.result;
	} else {
		console.log(response);
	}
}

export async function deleteList(listId, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {id: listId},
	});
	const response = await api.post('/ListAPI?method=deleteList', postBody);
	//console.log(response);
	if (response.ok) {
		return response.data.result;
	} else {
		console.log(response);
	}
}

export async function getSavedSearches(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const response = await api.post('/ListAPI?method=getSavedSearchesForLiDA&checkIfValid=false', postBody);
	if (response.ok) {
		//console.log(response);
		let savedSearches = [];
		if (response.data.result.success) {
			savedSearches = response.data.result.searches;
		}
		return savedSearches;
	} else {
		console.log(response);
	}
}

export async function getSavedSearchTitles(searchId, libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {searchId: searchId, numTitles: 30},
	});
	const response = await api.post('/ListAPI?method=getSavedSearchTitles', postBody);
	if (response.ok) {
		let savedSearches = [];
		//console.log(response);
		if (response.data.result) {
			savedSearches = response.data.result;
		}
		return savedSearches;
	} else {
		console.log(response);
	}
}