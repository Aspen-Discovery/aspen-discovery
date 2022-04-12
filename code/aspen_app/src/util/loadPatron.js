import React, {useContext} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';
import _ from "lodash";
import {GLOBALS} from "./globals";
import * as Sentry from 'sentry-expo';

// custom components and helper files
import {createAuthTokens, getHeaders, postData} from "./apiAuth";
import {popAlert} from "../components/loadError";
import {userContext} from "../context/user";

export async function getProfile(reload = false, url = "") {
	//const {value} = useContext(userContext);
	//console.log(value);

	let postBody = await postData();

	let libraryUrl;
	try {
		libraryUrl = await AsyncStorage.getItem('@pathUrl');
	} catch (e) {
		console.log(e);
	}

	if(libraryUrl) {
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			auth: createAuthTokens(),
			params: {reload: reload}
		});
		const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
		//console.log(response);
		if(response.ok) {
			if(response.data.result && response.data.result.profile) {
				await getILSMessages(libraryUrl);
				return response.data.result.profile;
			}
		} else {
			//console.log(response);
		}
	}
}

export async function reloadProfile(libraryUrl) {
	const {user, setUser} = React.useContext(userContext);

	let postBody = await postData();

	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getPatronProfile&reload&linkedUsers=true', postBody);
	console.log(response);
	if(response.ok) {
		if(response.data.result && response.data.result.profile) {
			const newUserData = {
				...user,
				user: response.data.result.profile
			}
			setUser(newUserData);
			console.log("User profile forcefully updated");
			await getILSMessages(libraryUrl);
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
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getILSMessages', postBody);
	if (response.ok) {
		let messages = [];

		if(response.data.result.messages) {
			messages = response.data.result.messages;
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
		//console.log("User ILS messages saved");
	} else {
		//console.log(response);
	}
}

export async function getCheckedOutItems(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutSlow,
		headers: getHeaders(true),
		params: {source: 'all', linkedUsers: 'true', refreshCheckouts: 'true'},
		auth: createAuthTokens()
	});

	const response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
	if (response.ok) {
		let items = response.data.result.checkedOutItems;
		items = _.sortBy(items, ['daysUntilDue', 'title'])
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
		params: {source: 'all', linkedUsers: 'true', refreshHolds: 'true'},
		auth: createAuthTokens()
	});
	response = await api.post('/UserAPI?method=getPatronHolds', postBody);
	if (response.ok) {
		const items = response.data.result.holds;
		let holds;
		let holdsReady = [];
		let holdsNotReady = [];

		if(typeof items.unavailable !== 'undefined') {
			holdsNotReady = Object.values(items.unavailable)
		}

		if(typeof items.available !== 'undefined') {
			holdsReady = Object.values(items.available)
		}

		holds = holdsReady.concat(holdsNotReady);
		//console.log(holds);

		await AsyncStorage.setItem('@patronHolds', JSON.stringify(holds));
		await AsyncStorage.setItem('@patronHoldsNotReady', JSON.stringify(holdsNotReady));
		await AsyncStorage.setItem('@patronHoldsReady', JSON.stringify(holdsReady));
		return holds;
	} else {
		console.log(response);
	}
}

export async function getPatronBrowseCategories(libraryUrl, patronId = null) {

	if(!patronId) {
		try {
			patronId = await AsyncStorage.getItem('@patronProfile');
		} catch (e) {
			console.log(e);
		}
	}

	if(patronId) {
		let browseCategories = [];
		const postBody = await postData();
		const api = create({
			baseURL: libraryUrl + '/API',
			timeout: GLOBALS.timeoutAverage,
			headers: getHeaders(true),
			params: {patronId: patronId},
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
			//console.log(hiddenCategories);
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

				if (typeof subCategories !== "undefined" && subCategories.length !== 0) {
					subCategories.forEach(item => activeCategories.push({
						'key': item.key,
						'title': item.title
					}))
				} else {
					activeCategories.push({'key': category.key, 'title': category.title});
				}
			});

			//console.log(activeCategories);
			browseCategories = browseCategories.concat(activeCategories);
		} else {
			console.log(responseActiveCategories);
		}

		browseCategories = _.uniqBy(browseCategories, 'key');
		//browseCategories = _.sortBy(browseCategories, 'title');
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
		params: {patronId: patronId},
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
		console.log(response);
	}

}

export async function getLinkedAccounts(libraryUrl) {
	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(true),
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getLinkedAccounts', postBody);
	if(response.ok) {
		const accounts = response.data.result.linkedAccounts;
		try {
			await AsyncStorage.setItem('@linkedAccounts', JSON.stringify(accounts));
		} catch (e) {
			console.log(e);
		}
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
		auth: createAuthTokens()
	});
	const response = await api.post('/UserAPI?method=getViewers', postBody);
	if(response.ok) {
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
		auth: createAuthTokens()
	});
	const response = await api.post('/ListAPI?method=getUserLists', postBody);
	if(response.ok) {
		let lists = [];
		if(response.data.result.success) {
			lists = response.data.result.lists;
			try {
				await AsyncStorage.setItem('@patronLists', JSON.stringify(lists));
			} catch (e) {
				console.log(e);
			}
			return lists;
		}
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
		params: {title: title, description: description, access: access}
	});
	const response = await api.post('/ListAPI?method=createList', postBody);
	if(response.ok) {
		await getLists(libraryUrl);
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
		params: {title: title, description: description, access: access, recordIds: items}
	});
	const response = await api.post('/ListAPI?method=createList', postBody);
	if(response.ok) {
		await getLists(libraryUrl);
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
		params: {id: listId, title: title, description: description, public: access}
	});
	const response = await api.post('/ListAPI?method=editList', postBody);
	if(response.ok) {
		await getLists(libraryUrl);
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
		params: {listId: listId}
	});
	const response = await api.post('/ListAPI?method=clearListTitles', postBody);
	if(response.ok) {
		await getListTitles(listId, libraryUrl);
		console.log(response.data);
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
		params: {listId: id, recordIds: itemId}
	});
	const response = await api.post('/ListAPI?method=addTitlesToList', postBody);
	if(response.ok) {
		await getLists(libraryUrl);
		if(response.data.result.success) {
			popAlert("Success", response.data.result.numAdded + " added to list", "success");
		} else {
			popAlert("Error", "Unable to add item to list", "error");
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
		params: {id: listId}
	});
	const response = await api.post('/ListAPI?method=getListTitles', postBody);
	if(response.ok) {
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
		params: {listId: listId, recordIds: title}
	});
	const response = await api.post('/ListAPI?method=removeTitlesFromList', postBody);
	if(response.ok) {
		console.log(response.data);
		await getListTitles(listId, libraryUrl);
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
		params: {id: listId}
	});
	const response = await api.post('/ListAPI?method=deleteList', postBody);
	if(response.ok) {
		console.log(response.data);
		await getLists(libraryUrl);
		return response.data.result;
	} else {
		console.log(response);
	}
}