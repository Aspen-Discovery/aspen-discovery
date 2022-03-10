import Constants from "expo-constants";
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';
import {createAuthTokens, getHeaders, problemCodeMap} from "./apiAuth";
import {popToast} from "../components/loadError";

/**
 * Fetch libraries to log into
 **/
export async function makeGreenhouseRequest(method, fetchAll = false) {
	let slug = Constants.manifest.slug;
	let greenhouseUrl;
	if(slug === "aspen-lida") {
		greenhouseUrl = Constants.manifest.extra.greenhouse;
	} else {
		greenhouseUrl = Constants.manifest.extra.apiUrl;
	}
	let latitude = await SecureStore.getItemAsync("latitude");
	let longitude = await SecureStore.getItemAsync("longitude");

	if(fetchAll) {
		latitude = 0;
		longitude = 0;
	}

	const api = create({
		baseURL: greenhouseUrl + '/API',
		timeout: 10000,
		headers: getHeaders(),
		auth: createAuthTokens(),
		params: {
			latitude: latitude,
			longitude: longitude,
			release_channel: await SecureStore.getItemAsync("releaseChannel")
		}
	});
	const response = await api.post('/GreenhouseAPI?method=' + method);
	if (response.ok) {
		return response.data;
	} else {
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
	}
}