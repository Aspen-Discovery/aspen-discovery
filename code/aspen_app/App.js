import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import {NativeBaseProvider, StatusBar} from "native-base";
import {SSRProvider} from "@react-aria/ssr";
import App from "./src/components/navigation";
import {createTheme, saveTheme} from "./src/themes/theme";
import {userContext} from "./src/context/user";
import {create} from 'apisauce';

import { LogBox } from 'react-native';
import {createAuthTokens, getHeaders, postData} from "./src/util/apiAuth";
import {GLOBALS} from "./src/util/globals";

// Hide log error/warning popups in simulator (useful for demoing)
//LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
LogBox.ignoreAllLogs();//Ignore all log notifications

export default class AppContainer extends Component {
	constructor(props) {
		super(props);
		this.state = {
			themeSet: false,
			themeSetSession: 0,
			user: {},
			library: {},
			location: {},
			hasLoaded: false,
		};
		this.aspenTheme = null;
		//this.login();
	}

	componentDidMount = async () => {
		await createTheme().then(async response => {
			if(this.state.themeSetSession !== Constants.sessionId) {
				this.aspenTheme = response;
				this.setState({ themeSet: true, themeSetSession: Constants.sessionId })
				this.aspenTheme.colors.primary['baseContrast'] === "#000000" ? this.setState({ statusBar: "dark-content" }) : this.setState({ statusBar: "light-content" })
				console.log("Theme set from createTheme in App.js");
				await saveTheme();
			} else {
				console.log("Theme previously saved.")
			}
		});

		this.interval = setInterval(async () => {
			console.log("Looking for a user token...");
			let userToken;
			try {
				//userToken = await AsyncStorage.getItem('@userToken');
				userToken = await SecureStore.getItemAsync("userToken");
			} catch (e) {
				console.log(e);
			}

			console.log(userToken);

			if(userToken) {
				console.log("USER TOKEN FOUND");
				console.log("Trying to run async login...");
				this.login(userToken);
			}
		}, 1000);

		return () => clearInterval(this.interval);
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	async login(userToken) {
		console.log("Running login function with user token: " + userToken);
		if (userToken) {
			let libraryUrl;
			let libName;
			try {
				libraryUrl = await AsyncStorage.getItem('@pathUrl');
				libName = await AsyncStorage.getItem('@libName');
			} catch (e) {
				console.log(e);
			}

			if (libraryUrl) {
				console.log("Connecting to " + libName + " using " + libraryUrl);
				let postBody = await postData();
				const api = create({
					baseURL: libraryUrl + '/API',
					timeout: GLOBALS.timeoutAverage,
					headers: getHeaders(true),
					auth: createAuthTokens()
				});

				const patronProfile = await AsyncStorage.getItem('@patronProfile');
				if(patronProfile === null) {
					console.log("fetching getPatronProfile...");
					const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
					if (response.ok) {
						let data = [];
						if (response.data.result.profile) {
							data = response.data.result.profile;
							this.setState({user: data});
						}
						await AsyncStorage.setItem('@patronProfile', JSON.stringify(data));
					}
				}

				let libraryId;
				let librarySolrScope;
				let locationId;
				try {
					libraryId = await SecureStore.getItemAsync('library');
					librarySolrScope = await SecureStore.getItemAsync('solrScope');
					locationId = await SecureStore.getItemAsync('locationId');
				} catch (e) {
					console.log(e);
				}

				if(libraryId) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					const libraryProfile = await AsyncStorage.getItem('@libraryInfo');
					if(libraryProfile === null) {
						console.log("fetching getLibraryInfo...");
						const response = await api.get('/SystemAPI?method=getLibraryInfo', {id: libraryId});
						if(response.ok) {
							let data = [];
							if(response.data.result.library) {
								data = response.data.result.library;
								this.setState({library: data});
							}
							await AsyncStorage.setItem('@libraryInfo', JSON.stringify(data));
						}
					}
				}

				if(locationId) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					const locationProfile = await AsyncStorage.getItem('@locationInfo');
					if(locationProfile === null) {
						console.log("fetching getLocationInfo...");
						const response = await api.get('/SystemAPI?method=getLocationInfo', {id: locationId, library: librarySolrScope, version: Constants.manifest.version});
						if(response.ok) {
							let data = [];
							if(response.data.result.location) {
								data = response.data.result.location;
								this.setState({location: data});
							}
							await AsyncStorage.setItem('@locationInfo', JSON.stringify(data));
						}
					}
				}
			}
		}
	}

	render() {
		const value = {
			user: this.state.user,
			library: this.state.library,
			location: this.state.location,
		}

		const user = this.state.user;
		const library = this.state.library;
		const location = this.state.location;

		if(this.state.themeSet) {
			return (
				<userContext.Provider value={{ user, library, location }}>
				<SSRProvider>
					<NativeBaseProvider theme={this.aspenTheme}>
						<StatusBar barStyle={this.state.statusBar} />
						<App/>
					</NativeBaseProvider>
				</SSRProvider>
				</userContext.Provider>
			);
		} else {
			return (
				<userContext.Provider value={value}>
				<SSRProvider>
					<NativeBaseProvider>
						<StatusBar barStyle="dark-content"/>
						<App/>
					</NativeBaseProvider>
				</SSRProvider>
				</userContext.Provider>
			);
		}
	}
}
