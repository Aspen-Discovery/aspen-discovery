import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';
import React from 'react';
import {createAuthTokens, getHeaders} from '../apiAuth';
import {GLOBALS} from '../globals';

/**
 * Fetch library login labels
 **/
export async function getLibraryLoginLabels(id, url) {
	let usernameLabel = "Your Name";
	let passwordLabel = "Library Card Number"

	const api = create({
		baseURL: url + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(),
		auth: createAuthTokens(),
	});
	const response = await api.get('/SystemAPI?method=getLibraryInfo', {
		id,
	});
	if (response.ok) {
		if (response.data.result.success) {
			if (typeof response.data.result.library !== 'undefined') {
				const profile = response.data.result.library;
				usernameLabel = profile.usernameLabel;
				passwordLabel = profile.passwordLabel;
			}
		}
	}

	return {
		username: usernameLabel,
		password: passwordLabel,
	}
}