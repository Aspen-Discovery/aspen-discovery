import React, {Component} from "react";
import * as SecureStore from 'expo-secure-store';
import * as Location from "expo-location";

export default class Splash extends Component {
	constructor() {
		super();
		this.state = {
			sessionReady: true,
		};
	}

	componentDidMount = async () => {
		await setAppDetails();
	}

	render() {
		if (!this.state.sessionReady) {
			return null;
		}
		return null;
	}
}

async function setAppDetails() {
	try {
		global.releaseChannel = Updates.releaseChannel;
		global.version = Constants.manifest.version;
		global.build = Constants.nativeBuildVersion;

		try {
			await SecureStore.setItemAsync("slug", Constants.manifest.slug);
			await SecureStore.setItemAsync("apiUrl", Constants.manifest.extra.apiUrl);
		} catch (e) {
			console.log(e);
		}

		if (global.releaseChannel === "production" || global.releaseChannel === "beta") {
			await SecureStore.setItemAsync("releaseChannel", global.releaseChannel);
		} else {
			await SecureStore.setItemAsync("releaseChannel", "any");
		}

		console.log("Release channel variable set.")
	} catch (e) {
		console.log("Error setting release channel variable.")
	}
}