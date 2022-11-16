import React, {Component} from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import {Center, HStack, NativeBaseProvider, Spinner, StatusBar} from 'native-base';
import {SSRProvider} from '@react-aria/ssr';
import App from './src/components/navigation';
import {createTheme, saveTheme} from './src/themes/theme';
import {userContext} from './src/context/user';
import {create} from 'apisauce';
import _ from 'lodash';
import * as Sentry from 'sentry-expo';
// Access any @sentry/react-native exports via:
// Sentry.Native.*
import {LogBox} from 'react-native';
import {createAuthTokens, getHeaders, postData} from './src/util/apiAuth';
import {GLOBALS} from './src/util/globals';

import {enableScreens} from 'react-native-screens';
import {getPatronBrowseCategories} from './src/util/loadPatron';
import {getBrowseCategories} from './src/util/loadLibrary';

enableScreens();

// Hide log error/warning popups in simulator (useful for demoing)
LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
LogBox.ignoreAllLogs();//Ignore all log notifications

export default class AppContainer extends Component {
	constructor(props) {
		super(props);
		this.state = {
			themeSet: false,
			themeSetSession: 0,
			user: [],
			library: [],
			location: [],
			browseCategories: [],
			hasLoaded: false,
		};
		this.aspenTheme = null;
		//this.login();
	}

	componentDidMount = async () => {
		this.setState({appReady: false});
		await createTheme().then(async response => {
			if (this.state.themeSetSession !== Constants.sessionId) {
				this.aspenTheme = response;
				this.setState({themeSet: true, themeSetSession: Constants.sessionId})
				this.aspenTheme.colors.primary['baseContrast'] === "#000000" ? this.setState({statusBar: "dark-content"}) : this.setState({statusBar: "light-content"})
				console.log("Theme set from createTheme in App.js");
				await saveTheme();
			} else {
				console.log("Theme previously saved.")
			}
		});

		this.interval = setInterval(async () => {
			let count = 0;
			let userToken;

			try {
				userToken = await AsyncStorage.getItem('@userToken');
				//userToken = await SecureStore.getItemAsync("userToken");
			} catch (e) {
				console.log(e);
			}

			//console.log(userToken);

			if (userToken) {
				//console.log("USER TOKEN FOUND");
				if (_.isEmpty(this.state.user) || !_.isEmpty(this.state.library) || !_.isEmpty(this.state.location) || !_.isEmpty(this.state.browseCategories)) {
					//console.log("Trying to run async login...");
					//await this.login(userToken);
				}
			}
		}, 5000);

		return () => clearInterval(this.interval);
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	async login(userToken) {
		//console.log("Running login function with user token: " + userToken);
		if (userToken) {
			let libraryUrl;
			let libraryId;
			let librarySolrScope;
			let locationId;
			let libName;
			try {
				libraryUrl = await AsyncStorage.getItem('@pathUrl');
				libName = await AsyncStorage.getItem('@libName');
				libraryId = await AsyncStorage.getItem('@libraryId');
				librarySolrScope = await AsyncStorage.getItem('@solrScope');
				locationId = await AsyncStorage.getItem('@locationId');
			} catch (e) {
				console.log(e);
			}

			if (libraryUrl) {
				//console.log("Connecting to " + libName + " using " + libraryUrl);
				let postBody = await postData();
				const api = create({
					baseURL: libraryUrl + '/API',
					timeout: GLOBALS.timeoutAverage,
					headers: getHeaders(true),
					auth: createAuthTokens()
				});

				//const patronProfile = await AsyncStorage.getItem('@patronProfile');
				if (_.isEmpty(this.state.user)) {
					//console.log("fetching getPatronProfile...");
					const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
					if (response.ok) {
						let data = [];
						if (response.data.result.profile) {
							data = response.data.result.profile;
							this.setState({user: data});
							await AsyncStorage.setItem('@patronProfile', JSON.stringify(this.state.user));
							//console.log("patron loaded into context");
						}
					}
				}

				if (libraryId) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					//const libraryProfile = await AsyncStorage.getItem('@libraryInfo');
					if (_.isEmpty(this.state.library)) {
						//console.log("fetching getLibraryInfo...");
						const response = await api.get('/SystemAPI?method=getLibraryInfo', {id: libraryId});
						if (response.ok) {
							let data = [];
							if (response.data.result.library) {
								data = response.data.result.library;
								this.setState({library: data});
								//await AsyncStorage.setItem('@libraryInfo', JSON.stringify(this.state.library));
								//console.log("library loaded into context");
							}
						}
					}

				}

				if (locationId && librarySolrScope) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					//const locationProfile = await AsyncStorage.getItem('@locationInfo');
					if (_.isEmpty(this.state.location)) {
						const response = await api.get('/SystemAPI?method=getLocationInfo', {id: locationId, library: librarySolrScope, version: Constants.manifest.version});
						if (response.ok) {
							let data = [];
							if (response.data.result.location) {
								data = response.data.result.location;
								this.setState({location: data});
							}
						}
					}
				}

				let discoveryVersion;
				if (this.state.library.discoveryVersion) {
					let version = this.state.library.discoveryVersion;
					version = version.split(" ");
					discoveryVersion = version[0];
				} else {
					discoveryVersion = "22.06.00";
				}

				if (_.isEmpty(this.state.browseCategories)) {

					if (discoveryVersion >= "22.07.00") {
						await getBrowseCategories(libraryUrl, discoveryVersion, 5).then(response => {
							this.setState({
								browseCategories: response,
							})
						})
					} else if (discoveryVersion >= "22.05.00") {
						await getBrowseCategories(libraryUrl, discoveryVersion).then(response => {
							this.setState({
								browseCategories: response,
							})
						})
					} else {
						const user = this.state;
						await getPatronBrowseCategories(libraryUrl, user.id).then(response => {
							this.setState({
								browseCategories: response,
							})
						})
					}
				}

				//await AsyncStorage.setItem('@patronProfile', JSON.stringify(this.state.user));
				await AsyncStorage.setItem('@libraryInfo', JSON.stringify(this.state.library));
				await AsyncStorage.setItem('@locationInfo', JSON.stringify(this.state.location));

			}
		}
	}

	render() {
		const user = this.state.user;
		const library = this.state.library;
		const location = this.state.location;
		const browseCategories = this.state.browseCategories;

		if (this.state.themeSet) {
			return (
					<userContext.Provider value={{user, library, location, browseCategories}}>
						<SSRProvider>
							<Sentry.Native.TouchEventBoundary>
								<NativeBaseProvider theme={this.aspenTheme}>
									<StatusBar barStyle={this.state.statusBar}/>
									<App/>
								</NativeBaseProvider>
							</Sentry.Native.TouchEventBoundary>
						</SSRProvider>
					</userContext.Provider>
			);
		} else {
			return (
					<userContext.Provider value={{user, library, location, browseCategories}}>
						<SSRProvider>
							<Sentry.Native.TouchEventBoundary>
								<NativeBaseProvider>
									<StatusBar barStyle="dark-content"/>
									<Center flex={1}>
										<HStack>
											<Spinner size="lg" accessibilityLabel="Loading..."/>
										</HStack>
									</Center>
								</NativeBaseProvider>
							</Sentry.Native.TouchEventBoundary>
						</SSRProvider>
					</userContext.Provider>
			);
		}
	}
}