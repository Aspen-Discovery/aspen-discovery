import Constants from "expo-constants";
import * as Updates from 'expo-updates';
import {Platform} from "react-native";

export const GLOBALS = {
	'timeoutAverage': 60000,
	'timeoutSlow': 100000,
	'timeoutFast': 30000,
	'appVersion': Constants.manifest.version,
	'appBuild': Platform.OS === 'android' ? Constants.manifest.android.versionCode : Constants.manifest.ios.buildNumber,
	'appSessionId': Constants.sessionId,
	'appPatch': 0,
	'slug': Constants.manifest.slug,
	'apiUrl': Constants.manifest.extra.apiUrl,
	'releaseChannel': Updates.releaseChannel,
	'language': "en",
	'prevLaunched': false,
	'pendingSearchFilters': [],
	'solrScope': "unknown",
}