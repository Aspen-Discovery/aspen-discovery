import Constants from "expo-constants";
import * as Updates from 'expo-updates';

export const GLOBALS = {
	'timeoutAverage': 10000,
	'timeoutSlow': 60000,
	'timeoutFast': 5000,
	'appVersion': Constants.manifest.version,
	'appBuild': Constants.nativeAppVersion,
	'appSessionId': Constants.sessionId,
	'appPatch': 1,
	'slug': Constants.manifest.slug,
	'apiUrl': Constants.manifest.extra.apiUrl,
	'releaseChannel': Updates.releaseChannel,
	'language': "en"
}