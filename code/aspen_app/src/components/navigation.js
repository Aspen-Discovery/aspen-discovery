import React from 'react';
import * as SecureStore from 'expo-secure-store';
import {DefaultTheme, NavigationContainer} from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {Spinner, useColorModeValue, useContrastText, useToken} from 'native-base';
import * as Location from 'expo-location';
import * as Updates from 'expo-updates';
import Constants from 'expo-constants';
import {create} from 'apisauce';
import * as Sentry from 'sentry-expo';
// Access any @sentry/react-native exports via:
// Sentry.Native.*
import * as Notifications from 'expo-notifications';
import * as Linking from 'expo-linking';

import Login from '../screens/Auth/Login';
import AccountDrawer from '../navigations/drawer/DrawerNavigator';
import {translate} from '../translations/translations';
import {createAuthTokens, getHeaders} from '../util/apiAuth';
import {popAlert, popToast} from './loadError';
import {removeData} from '../util/logout';
import {GLOBALS} from '../util/globals';

// Before rendering any navigation stack
import {enableScreens} from 'react-native-screens';
import {formatDiscoveryVersion, LIBRARY} from '../util/loadLibrary';
import {PATRON} from '../util/loadPatron';
import {navigationRef} from "../helpers/RootNavigator";
import {checkCachedUrl} from "../util/login";

enableScreens();

const Stack = createNativeStackNavigator();
const routingInstrumentation = new Sentry.Native.ReactNavigationInstrumentation();

export const AuthContext = React.createContext();

Sentry.init({
	dsn: Constants.manifest.extra.sentryDSN,
	enableInExpoDevelopment: false,
	enableAutoSessionTracking: true,
	sessionTrackingIntervalMillis: 10000,
	debug: true,
	tracesSampleRate: .25,
	environment: Updates.releaseChannel,
	release: Constants.manifest.version,
	dist: GLOBALS.appPatch,
	integrations: [
		new Sentry.Native.ReactNativeTracing({
			routingInstrumentation,
		}),
	],
});

const prefix = Linking.createURL('/');

const PERSISTENCE_KEY = 'NAVIGATION_STATE_V1';

export function App() {

	const [isReady, setIsReady] = React.useState(!__DEV__);
	const [initialState, setInitialState] = React.useState();

	const primaryColor = useToken('colors', 'primary.base');
	const primaryColorContrast = useToken('colors', useContrastText(primaryColor));
	const screenBackgroundColor = useToken('colors', useColorModeValue('warmGray.50', 'coolGray.800'));
	const navigationTheme = {
		...DefaultTheme,
		colors: {
			...DefaultTheme.colors,
			primary: primaryColorContrast,
			card: primaryColor,
			text: primaryColorContrast,
			background: screenBackgroundColor,
		},
	};

	const [state, dispatch] = React.useReducer(
			(prevState, action) => {
				switch (action.type) {
					case 'RESTORE_TOKEN':
						return {
							...prevState,
							userToken: action.token,
							isLoading: false,
						};
					case 'SIGN_IN':
						return {
							...prevState,
							isSignout: false,
							userToken: action.token,
						};
					case 'SIGN_OUT':
						return {
							...prevState,
							isSignout: true,
							userToken: null,
						};
				}
			},
			{
				isLoading: true,
				isSignout: false,
				userToken: null,
			},
	);

	React.useEffect(() => {

		const timer = setInterval(async () => {
			if (!__DEV__) {
				const update = await Updates.checkForUpdateAsync();
				if (update.isAvailable) {
					try {
						await Updates.fetchUpdateAsync().then(async r => {
							await Updates.reloadAsync();
						});
					} catch (e) {
						console.log(e);
					}
				}
			}
		}, 15000);
		return () => {
			clearInterval(timer);
		};
	}, []);

	React.useEffect(() => {
		const bootstrapAsync = async () => {

			await getPermissions();
			await setAppDetails();

			console.log('Checking existing session...');
			let userToken
			let libraryUrl;
			try {
				// Restore token stored in `AsyncStorage`
				userToken = await AsyncStorage.getItem('@userToken');
				libraryUrl = await AsyncStorage.getItem('@pathUrl');
			} catch (e) {
				// Restoring token failed
				console.log(e);
			}
			console.log('Session found');
			console.log('Trying to connect to: ', libraryUrl);

			await checkCachedUrl(libraryUrl).then(async result => {
				if (result) {
					console.log("Connection successful. Continuing...");
					dispatch({type: 'RESTORE_TOKEN', token: userToken});
				} else {
					console.log("Connection failed, logging out.");
					await removeData().then(res => {
						dispatch({type: 'SIGN_OUT'});
					});
				}
			});
		};
		bootstrapAsync();
	}, []);

	const authContext = React.useMemo(
			() => ({
				signIn: async (data) => {
					let userToken;
					let patronsLibrary = data.patronsLibrary;

					try {
						const postBody = new FormData();
						postBody.append('username', data.valueUser);
						postBody.append('password', data.valueSecret);
						const api = create({
							baseURL: data.libraryUrl + '/API',
							timeout: 5000,
							headers: getHeaders(true),
							auth: createAuthTokens(),
						});
						const response = await api.post('/UserAPI?method=validateAccount', postBody);
						//console.log(response);
						if (response.ok) {
							let result = false;
							if (response.data.result) {
								result = response.data.result;
							}
							if (result) {
								result = result.success;
								if (result['id'] != null) {

									let patronName = result.firstname;
									// if patronName is in all uppercase, force it to sentence-case
									if (patronName === patronName.toUpperCase()) {
										patronName = patronName.toLowerCase();
										patronName = patronName.split(' ');
										for (var i = 0; i < patronName.length; i++) {
											patronName[i] = patronName[i].charAt(0).toUpperCase() + patronName[i].slice(1);
										}
										patronName = patronName.join(' ');
									}
									userToken = JSON.stringify(result.firstname + ' ' + result.lastname);
									console.log('Valid user: ' + userToken);
									// start an aspen discovery session
									// const loginResponse = await api.post('/UserAPI?method=login', postBody);
									// let aspenSession = null;
									// if(loginResponse.data.result.success) {
									//	await SecureStore.setItemAsync("aspenSession", loginResponse.data.result.session);
									// }

									global.libraryUrl = patronsLibrary['baseUrl'];
									global.libraryId = patronsLibrary['libraryId'];

									try {
										// prepare app data
										global.slug = Constants.manifest.slug;
										global.apiUrl = Constants.manifest.extra.apiUrl;
										global.patron = patronName;
										global.libraryId = patronsLibrary['libraryId'];
										global.libraryName = patronsLibrary['name'];
										global.locationId = patronsLibrary['locationId'];
										global.solrScope = patronsLibrary['solrScope'];
										global.libraryUrl = patronsLibrary['baseUrl'];
										global.logo = patronsLibrary['logo'];
										global.favicon = patronsLibrary['favicon'];
										global.aspen = patronsLibrary['version'];
									} catch (e) {
										console.log(e);
									}

									console.log('at Login: ' + userToken);
									//await AsyncStorage.setItem('@userToken', userToken);

									console.log(patronsLibrary);

									// update global variables for later
									GLOBALS.solrScope = patronsLibrary['solrScope'];
									GLOBALS.lastSeen = Constants.manifest.version;
									LIBRARY.url = data.libraryUrl;
									LIBRARY.name = patronsLibrary['name'];
									if (patronsLibrary['version']) {
										LIBRARY.version = formatDiscoveryVersion(patronsLibrary['version']);
									}
									LIBRARY.favicon = patronsLibrary['favicon'];
									PATRON.userToken = userToken;
									PATRON.scope = patronsLibrary['solrScope'];
									PATRON.library = patronsLibrary['libraryId'];
									PATRON.location = patronsLibrary['locationId'];

									try {
										await AsyncStorage.setItem('@userToken', userToken);
										await AsyncStorage.setItem('@pathUrl', data.libraryUrl);
										await AsyncStorage.setItem('@libName', patronsLibrary['name']);
										await SecureStore.setItemAsync('userKey', data.valueUser);
										await SecureStore.setItemAsync('secretKey', data.valueSecret);
										//await SecureStore.setItemAsync('userToken', userToken);
										// save variables in the Secure Store to access later on
										await SecureStore.setItemAsync('patronName', patronName);
										await SecureStore.setItemAsync('library', patronsLibrary['libraryId']);
										await AsyncStorage.setItem('@libraryId', patronsLibrary['libraryId']);
										await SecureStore.setItemAsync('libraryName', patronsLibrary['name']);
										await SecureStore.setItemAsync('locationId', patronsLibrary['locationId']);
										await AsyncStorage.setItem('@locationId', patronsLibrary['locationId']);
										await SecureStore.setItemAsync('solrScope', patronsLibrary['solrScope']);

										await AsyncStorage.setItem('@solrScope', patronsLibrary['solrScope']);
										await SecureStore.setItemAsync('pathUrl', data.libraryUrl);
										//await SecureStore.setItemAsync("logo", patronsLibrary['theme']['logo']);
										//await SecureStore.setItemAsync("favicon", patronsLibrary['theme']['favicon']);
										//await SecureStore.setItemAsync("discoveryVersion", patronsLibrary['version']);
										await AsyncStorage.setItem('@lastStoredVersion', Constants.manifest.version);
										await AsyncStorage.setItem('@patronLibrary', JSON.stringify(patronsLibrary));
										dispatch({type: 'SIGN_IN', token: userToken});

									} catch (e) {
										console.log('Unable to log in user.');
										console.log(e);
									}
								} else {
									console.log('Invalid user. Unable to store data.');
									popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), 'error');
									console.log(response);
								}
							} else {
								console.log('Unable to validate user account. ');
								popAlert(translate('error.no_server_connection'), 'We\'re unable to validate your account at this time.', 'warning');
								console.log(response);
							}

						} else {
							const result = response.problem;
							popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
							console.log(response);
						}
					} catch (error) {
						popAlert(translate('login.unable_to_login'), translate('login.not_enough_data'), 'error');
						console.log(error);
					}
				},
				signOut: async () => {
					await removeData().then(res => {
						dispatch({type: 'SIGN_OUT'});
					});
					console.log('Session ended.');
				},
			}),
			[],
	);

	const navigation = React.useRef();

	return (
			<AuthContext.Provider value={authContext}>
				<NavigationContainer theme={navigationTheme}
														 ref={navigationRef}
														 fallback={<Spinner/>}
														 linking={{
															 prefixes: [prefix],
															 config: {
																 screens: {
																	 Login: 'user/login',
																	 Drawer: {
																		 screens: {
																			 Tabs: {
																				 screens: {
																					 AccountScreenTab: {
																						 screens: {
																							 SavedSearches: 'user/saved_searches',
																							 LoadSavedSearch: 'user/saved_search',
																							 Lists: 'user/lists',
																							 List: 'user/list',
																							 LinkedAccounts: 'user/linked_accounts',
																							 Holds: 'user/holds',
																							 CheckedOut: 'user/checkouts',
																							 Preferences: 'user/preferences',
																							 ProfileScreen: 'user',
																						 },
																					 },
																					 LibraryCardTab: {
																						 screens: {
																							 LibraryCard: 'user/library_card',
																						 },
																					 },
																					 SearchTab: {
																						 screens: {
																							 SearchResults: 'search',
																						 },
																					 },
																					 HomeTab: {
																						 screens: {
																							 HomeScreen: 'home',
																							 GroupedWorkScreen: 'search/grouped_work',
																							 SearchByCategory: 'search/browse_category',
																							 SearchByAuthor: 'search/author',
																							 SearchByList: 'search/list',
																						 },
																					 },
																				 },
																			 },
																		 },
																	 },
																 },
															 },
															 async getInitialURL() {
																 let url = await Linking.getInitialURL();

																 if (url != null) {
																	 url = decodeURIComponent(url).replace(/\+/g, ' ');
																	 url = url.replace('aspen-lida://', prefix);
																	 return url;
																 }

																 const response = await Notifications.getLastNotificationResponseAsync();
																 url = decodeURIComponent(response?.notification.request.content.data.url).replace(/\+/g, ' ');
																 url = url.replace('aspen-lida://', prefix);
																 return url;
															 },
															 subscribe(listener) {
																 const linkingSubscription = Linking.addEventListener('url', ({url}) => {
																	 listener(url);
																 });
																 const subscription = Notifications.addNotificationResponseReceivedListener(response => {
																	 const url = response.notification.request.content.data.url;
																	 listener(url);
																 });

																 return () => {
																	 subscription.remove();
																	 linkingSubscription.remove();
																 };
															 },
														 }}
				>
					<Stack.Navigator
							screenOptions={{headerShown: false}}
							name="Root"
					>
						{state.userToken == null ? (
								// No token found, user isn't signed in
								<Stack.Screen
										name="Login"
										component={Login}
										options={{
											headerShown: false,
											animationTypeForReplace: state.isSignout ? 'pop' : 'push',
										}}
								/>
						) : (
								// User is signed in
								<Stack.Screen
										name="Drawer"
										component={AccountDrawer}
										screenOptions={{
											headerShown: false,
										}}
								/>
						)}
					</Stack.Navigator>
				</NavigationContainer>
			</AuthContext.Provider>
	);
}

async function getPermissions() {
	let {status} = await Location.requestForegroundPermissionsAsync();

	if (status !== 'granted') {
		await SecureStore.setItemAsync('latitude', '0');
		await SecureStore.setItemAsync('longitude', '0');
		PATRON.coords.lat = 0;
		PATRON.coords.long = 0;
		return;
	}

	let location = await Location.getLastKnownPositionAsync({});

	if (location != null) {
		let latitude = JSON.stringify(location.coords.latitude);
		let longitude = JSON.stringify(location.coords.longitude);
		await SecureStore.setItemAsync('latitude', latitude);
		await SecureStore.setItemAsync('longitude', longitude);
		PATRON.coords.lat = latitude;
		PATRON.coords.long = longitude;
	} else {
		await SecureStore.setItemAsync('latitude', '0');
		await SecureStore.setItemAsync('longitude', '0');
		PATRON.coords.lat = 0;
		PATRON.coords.long = 0;
	}
	return location;
}

async function setAppDetails() {
	try {
		global.releaseChannel = Updates.releaseChannel;
		global.version = Constants.manifest.version;
		global.build = Constants.nativeBuildVersion;

		try {
			await SecureStore.setItemAsync('slug', Constants.manifest.slug);
			await SecureStore.setItemAsync('apiUrl', Constants.manifest.extra.apiUrl);
		} catch (e) {
			console.log(e);
		}

		if (global.releaseChannel === 'production' || global.releaseChannel === 'beta') {
			await SecureStore.setItemAsync('releaseChannel', global.releaseChannel);
		} else {
			await SecureStore.setItemAsync('releaseChannel', 'any');
		}

		console.log('Release channel variable set.');
	} catch (e) {
		console.log(e);
		console.log('Error setting release channel variable.');
	}
}

export default Sentry.Native.wrap(App);