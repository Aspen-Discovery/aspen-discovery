import React, {Component} from "react";
import * as SplashScreen from "expo-splash-screen";
import * as SecureStore from 'expo-secure-store';
import * as Location from "expo-location";
import {AuthContext} from "./navigation";

// TODO: Compare installed version to latest version and prompt user to update
export default class Permissions extends Component {
	constructor(props) {
		super(props);
		this.state = {
			appIsReady: false,
		};
	}

	async componentDidMount() {
		// Prevent native splash screen from autohiding
		try { await SplashScreen.preventAutoHideAsync(); } catch (e) { }

		await this.prepareResources();
	}

	prepareResources = async () => {
		await getPermissions();
		await setAppDetails();

		this.setState({
			appIsReady: true
		}, async () => {
			await SplashScreen.hideAsync();
		});

	};

	render() {
		if (!this.state.appIsReady) {
			return null;
		}

		return this.props.navigation.navigate(
			this.state.loginToken ? 'Loading' : 'Auth', {
				theme: this.state.theme
			})
	}
}

async function getPermissions() {
	let {status} = await Location.requestForegroundPermissionsAsync();

	if (status !== "granted") {
		await SecureStore.setItemAsync("latitude", "0");
		await SecureStore.setItemAsync("longitude", "0");
		return;
	}

	let location = await Location.getLastKnownPositionAsync({});

	if (location != null) {
		let latitude = JSON.stringify(location.coords.latitude);
		let longitude = JSON.stringify(location.coords.longitude);
		await SecureStore.setItemAsync("latitude", latitude);
		await SecureStore.setItemAsync("longitude", longitude);
	} else {
		await SecureStore.setItemAsync("latitude", "0");
		await SecureStore.setItemAsync("longitude", "0");
	}

	let text = "Checking things...";
	if (location) {
		text = JSON.stringify(location);
	}

	return location;
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