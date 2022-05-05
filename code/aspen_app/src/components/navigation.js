import React from "react";
import * as ErrorRecovery from "expo-error-recovery";
import * as SecureStore from 'expo-secure-store';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useToken, useContrastText, useColorModeValue } from 'native-base';
import * as Location from "expo-location";
import * as Updates from 'expo-updates';
import Constants from "expo-constants";
import {create} from 'apisauce';
import * as Sentry from 'sentry-expo';

import Splash from "./splash";
import Login from "../screens/Auth/Login";

import LoadingScreen from "../screens/Auth/Loading";
import AccountDrawer from "../navigations/drawer/DrawerNavigator";
import {translate} from "../translations/translations";
import {createAuthTokens, getHeaders, postData} from "../util/apiAuth";
import {popAlert, popToast} from "./loadError";
import {removeData} from "../util/logout";
import {navigationRef} from "../helpers/RootNavigator";
import {GLOBALS} from "../util/globals";
import {getILSMessages} from "../util/loadPatron";

const Stack = createNativeStackNavigator();

export const AuthContext = React.createContext();

// Construct a new instrumentation instance. This is needed to communicate between the integration and React
const routingInstrumentation = new Sentry.Native.ReactNavigationInstrumentation();

Sentry.init({
	dsn: Constants.manifest.extra.sentryDSN,
	enableInExpoDevelopment: true,
	enableAutoSessionTracking: true,
	debug: false,
	tracesSampleRate: 1.0,
	environment: Updates.releaseChannel,
	release: Constants.manifest.version,
	dist: GLOBALS.appPatch,
});


export default function App() {

	const primaryColor = useToken("colors", "primary.base");
	const primaryColorContrast = useToken("colors", useContrastText(primaryColor));
	const screenBackgroundColor = useToken("colors", useColorModeValue("warmGray.50", "coolGray.800"));
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
		}
	);

	React.useEffect(() => {
		const timer = setInterval(async () => {
			if(!__DEV__){
				const update = await Updates.checkForUpdateAsync()
				if (update.isAvailable) {
					try {
						await Updates.fetchUpdateAsync().then(async r => {
							await Updates.reloadAsync();
						});
					} catch (e) {
						console.log(e);
						Sentry.Native.captureException(e);
					}
				}
			}
		}, 15000)
		return () => clearInterval(timer)
	}, [])

	React.useEffect(() => {
		const bootstrapAsync = async () => {

			await getPermissions();
			await setAppDetails();

			console.log("Checking existing session...");
			let userToken;
			try {
				// Restore token stored in `AsyncStorage`
				userToken = await AsyncStorage.getItem('@userToken');
			} catch(e) {
				// Restoring token failed
				console.log(e);
			}
			console.log("Session OK!")
			dispatch({ type: 'RESTORE_TOKEN', token: userToken });
		};
		bootstrapAsync();
	}, [])

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
						timeout: 6000,
						headers: getHeaders(true),
						auth: createAuthTokens()
					});
					const response = await api.post('/UserAPI?method=validateAccount', postBody);
					//console.log(response);
					if (response.ok) {
						let result = false;
						if(response.data.result) {
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
								userToken = JSON.stringify(result.firstname + " " + result.lastname)
								console.log("Valid user: " + userToken);
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

								console.log("at Login: " + userToken);
								//await AsyncStorage.setItem('@userToken', userToken);

								try {
									await AsyncStorage.setItem('@userToken', userToken);
									await AsyncStorage.setItem('@pathUrl', data.libraryUrl);
									await AsyncStorage.setItem('@libName', patronsLibrary['name']);
									await SecureStore.setItemAsync("userKey", data.valueUser);
									await SecureStore.setItemAsync("secretKey", data.valueSecret);
									await SecureStore.setItemAsync("userToken", userToken);
									// save variables in the Secure Store to access later on
									await SecureStore.setItemAsync("patronName", patronName);
									await SecureStore.setItemAsync("library", patronsLibrary['libraryId']);
									await AsyncStorage.setItem("@libraryId", patronsLibrary['libraryId']);
									await SecureStore.setItemAsync("libraryName", patronsLibrary['name']);
									await SecureStore.setItemAsync("locationId", patronsLibrary['locationId']);
									await AsyncStorage.setItem("@locationId", patronsLibrary['locationId']);
									await SecureStore.setItemAsync("solrScope", patronsLibrary['solrScope']);
									await AsyncStorage.setItem("@solrScope", patronsLibrary['solrScope']);
									await SecureStore.setItemAsync("pathUrl", data.libraryUrl);
									await SecureStore.setItemAsync("logo", patronsLibrary['logo']);
									await SecureStore.setItemAsync("favicon", patronsLibrary['favicon']);
									await SecureStore.setItemAsync("discoveryVersion", patronsLibrary['version']);
									await AsyncStorage.setItem("@lastStoredVersion", Constants.manifest.version);
									await AsyncStorage.setItem("@patronLibrary", JSON.stringify(patronsLibrary));
									dispatch( {type: 'SIGN_IN', token: userToken});

								} catch(e) {
									console.log("Unable to log in user.");
									console.log(e);
								}
							} else {
								console.log("Invalid user. Unable to store data.");
								popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), "error");
								console.log(response);
							}
						} else {
							console.log("Unable to validate user account. ");
							popAlert(translate('error.no_server_connection'), 'We\'re unable to validate your account at this time.', "warning");
							console.log(response);
						}

					} else {
						const result = response.problem;
						popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
						console.log(response);
					}
				} catch (error) {
					popAlert(translate('login.unable_to_login'), translate('login.not_enough_data'), "error");
					console.log(error);
				}
			},
			signOut: async () => {
				await removeData().then(res => {
					dispatch({ type: 'SIGN_OUT' });
				});
				console.log("Session ended.")
			},
		}),
		[]
	);

	const navigation = React.useRef();

	return (
		<AuthContext.Provider value={authContext}>
			<NavigationContainer theme={navigationTheme}
			                     ref={navigationRef}
			>
				<Stack.Navigator
					screenOptions={{ headerShown: false }}
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
							name={translate('navigation.home')}
							component={AccountDrawer}
							screenOptions={{
								headerShown: false
							}}
						/>
					)}
				</Stack.Navigator>
			</NavigationContainer>
		</AuthContext.Provider>
	)
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
		console.log(e);
		console.log("Error setting release channel variable.")
	}
}