import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import {Platform} from 'react-native';

export const GLOBALS = {
	'timeoutAverage': 60000,
	'timeoutSlow': 100000,
	'timeoutFast': 30000,
	'appVersion': Constants.manifest.version,
	'appBuild': Platform.OS === 'android' ? Constants.manifest.android.versionCode : Constants.manifest.ios.buildNumber,
	'appSessionId': Constants.sessionId,
	'appPatch': 0,
	'showSelectLibrary': true,
	'runGreenhouse': true,
	'slug': Constants.manifest.slug,
	'apiUrl': Constants.manifest.extra.apiUrl,
	'releaseChannel': Updates.releaseChannel,
	'language': 'en',
	'lastSeen': null,
	'prevLaunched': false,
	'pendingSearchFilters': [],
	'availableFacetClusters': [],
	'hasPendingChanges': false,
	'solrScope': 'unknown',
};

export let LOGIN_DATA = {
	'showSelectLibrary': true,
	'runGreenhouse': true,
	'num': 0,
	'nearbyLocations': [],
	'allLocations': [],
	'extra': [],
	'hasPendingChanges': false,
};

export let GLOBAL_ALL_LOCATIONS = {
	'branches': [],
};

/**
 * Store an empty/default version of each global variable to easily reset them when logging out
 **/

export const GLOBALS_LIBRARY = {
	'url': null,
	'name': null,
	'favicon': null,
	'version': '22.10.00',
	'languages': [],
	'vdx': [],
};

export const GLOBALS_PATRON = {
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
};