import {create} from 'apisauce';
import {GLOBALS} from '../globals';
import {createAuthTokens, ENDPOINT, getHeaders, postData} from '../apiAuth';
import {LIBRARY} from '../loadLibrary';

const endpoint = ENDPOINT.user;
//const endpoint = ENDPOINT.work;

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns grouped work data for a given id
 * @param {string} itemId
 * @param {string} source
 * @param {string} url
 **/
export async function checkoutItem(itemId, source, url) {
	const postBody = await postData();
	const discovery = create({
		baseURL: url,
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			itemId: itemId,
			itemSource: source,
			userId: patronId,
		},
	});
	const response = await discovery.post(`${endpoint.url}checkoutItem`, postBody);
	if (response.ok) {
		return response.data.result;
	} else {
		console.log(response);
		return false;
	}
}

/**
 * Place a standard hold on an item for a given user
 * @param {string} itemId
 * @param {string} source
 * @param {string} pickupBranch
 * @param {string} userId
 * @param {string} url
 **/
export async function placeHold(itemId, source, pickupBranch, userId, url) {

}

/**
 * Place an item-level hold on an item for a given user
 * @param {string} recordId
 * @param {string} pickupBranch
 * @param {string} userId
 * @param {string} url
 **/
export async function placeItemHold(recordId, pickupBranch, userId, url) {
	const postBody = await postData();
	const discovery = create({
		baseURL: url,
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			itemSource: source,
			userId,
			pickupBranch,
			holdType: 'item',
			recordId,
		},
	});
	const response = await discovery.post(`${endpoint.url}placeHold`, postBody);
	if (response.ok) {
		return true;
	} else {
		return false;
	}
}

/**
 * Place a volume-level hold on an item for a given user
 * @param {string} recordId
 * @param {string} volumeId
 * @param {string} pickupBranch
 * @param {string} userId
 * @param {string} url
 **/
export async function placeVolumeHold(recordId, volumeId, pickupBranch, userId, url) {
	const postBody = await postData();
	const discovery = create({
		baseURL: url,
		timeout: GLOBALS.timeoutAverage,
		headers: getHeaders(endpoint.isPost),
		auth: createAuthTokens(),
		params: {
			itemSource: source,
			userId,
			pickupBranch,
			holdType: 'volume',
			volumeId,
		},
	});
	const response = await discovery.post(`${endpoint.url}placeHold`, postBody);
	if (response.ok) {
		return true;
	} else {
		return false;
	}
}