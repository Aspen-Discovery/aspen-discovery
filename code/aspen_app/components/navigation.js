import React from "react";
import * as SecureStore from 'expo-secure-store';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useToken, useContrastText, useColorModeValue } from 'native-base';
import * as Location from "expo-location";
import * as Updates from 'expo-updates';
import Constants from "expo-constants";
import {Ionicons} from "@expo/vector-icons";
import {create} from 'apisauce';

import LoadingScreen from "../components/splash";
import Search from "../screens/Search/Search";
import GroupedWork from "../screens/GroupedWork/GroupedWork";
import Results from "../screens/Search/Results";
import More from "../screens/More";
import Contact from "../screens/Library/Contact";
import MyAccount from "../screens/MyAccount/MyAccount";
import CheckedOut from "../screens/MyAccount/CheckedOut";
import Holds from "../screens/MyAccount/Holds";
import Settings_HomeScreen from "../screens/MyAccount/Settings/HomeScreen";
import BrowseCategoryHome from "../screens/BrowseCategory/Home";
import Login from "../screens/Login";
import LibraryCard from "../screens/MyAccount/LibraryCard";

import {translate} from "../util/translations";
import {createAuthTokens, getHeaders} from "../util/apiAuth";
import {popAlert, popToast} from "./loadError";
import {removeData} from "../util/logout";
import {createTheme, saveTheme} from "../themes/theme";

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

export const AuthContext = React.createContext();

function HomeStack() {
	return (
		<Stack.Navigator>
			<Stack.Screen
				name="Home"
				component={BrowseCategoryHome}
				options={{
					title: translate('navigation.home'),
				}}
			/>
			<Stack.Screen
				name="GroupedWork"
				component={GroupedWork}
				options={{ title: translate('grouped_work.title') }}
			/>
		</Stack.Navigator>
	)
}

function CardStack() {
	return (
		<Stack.Navigator>
			<Stack.Screen
				name="Card"
				component={LibraryCard}
				options={{ title: translate('user_profile.library_card') }}
			/>
		</Stack.Navigator>
	)
}

function SearchStack({ route, navigation }) {
	return (
		<Stack.Navigator
			initialRouteName="Search"
		>
			<Stack.Screen
				name="Search"
				component={Search}
				options={{ title: translate('search.title') }}
			/>
			<Stack.Screen
				name="SearchResults"
				component={Results}
				options={({ route, navigation }) => ({
					title: translate('search.search_results_title') + route.params.searchTerm,
				})}
			/>
			<Stack.Screen
				name="GroupedWork"
				component={GroupedWork}
				options={{ title: translate('grouped_work.title') }}
			/>
		</Stack.Navigator>
	)
}

function AccountStack() {
	return (
		<Stack.Navigator
			initialRouteName="Account"
		>
			<Stack.Screen
				name="Account"
				component={MyAccount}
				options={{ title: translate('user_profile.title') }}
			/>
			<Stack.Screen
				name="CheckedOut"
				component={CheckedOut}
				options={{ title: translate('checkouts.title') }}
			/>
			<Stack.Screen
				name="Holds"
				component={Holds}
				options={{ title: translate('holds.title') }}
			/>
			<Stack.Screen
				name="GroupedWork"
				component={GroupedWork}
				options={{ title: translate('grouped_work.title') }}
			/>
			<Stack.Screen
				name="SettingsHomeScreen"
				component={Settings_HomeScreen}
				options={{ title: translate('user_profile.home_screen_settings') }}
			/>
		</Stack.Navigator>
	)
}

function MoreStack({ route, navigation }) {
	return (
		<Stack.Navigator
			initialRouteName="More"
		>
			<Stack.Screen
				name="More"
				component={More}
				options={{ title: translate('navigation.more') }}
			/>
			<Stack.Screen
				name="Contact"
				component={Contact}
			/>
		</Stack.Navigator>
	)
}

function AppStack() {
	const [activeIcon, inactiveIcon] = useToken("colors", [useColorModeValue("gray.800", "coolGray.200"), useColorModeValue("gray.500", "coolGray.600")]);
	const tabBarBackgroundColor = useToken("colors", useColorModeValue("warmGray.100", "coolGray.900"));
	return (
		<Tab.Navigator
			initialRouteName="Home"
			tabBarOptions={{
				activeTintColor: activeIcon,
				inactiveTintColor: inactiveIcon,
				labelStyle: {
					fontWeight: '400'
				},
				style: {
					backgroundColor: tabBarBackgroundColor
				}
			}}
			screenOptions={({ route }) => ({
				tabBarIcon: ({ focused, color, size }) => {
					let iconName;
					if(route.name === 'Home') {
						iconName = focused ? 'library' : 'library-outline';
					} else if (route.name === 'Search') {
						iconName = focused ? 'search' : 'search-outline';
					} else if (route.name === 'Library Card') {
						iconName = focused ? 'card' : 'card-outline';
					} else if (route.name === 'Account') {
						iconName = focused ? 'person' : 'person-outline';
					} else if (route.name === 'More') {
						iconName = focused ? 'ellipsis-horizontal' : 'ellipsis-horizontal-outline';
					}
					return <Ionicons name={iconName} size={size} color={color} />;
				},
			})}
		>
			<Tab.Screen
				name="Home"
				component={HomeStack}
				options={{
					tabBarLabel: translate('navigation.home'),
					unmountOnBlur: true,
				}}
			/>
			<Tab.Screen
				name="Search"
				component={SearchStack}
				options={{
					tabBarLabel: translate('navigation.search'),
				}}
			/>
			<Tab.Screen
				name="Library Card"
				component={CardStack}
				options={{
					tabBarLabel: translate('navigation.library_card'),
				}}
			/>
			<Tab.Screen
				name="Account"
				component={AccountStack}
				options={{
					tabBarLabel: translate('navigation.account'),
				}}
			/>
			<Tab.Screen
				name="More"
				component={MoreStack}
				options={{
					tabBarLabel: translate('navigation.more'),
				}}
			/>
		</Tab.Navigator>
	)
}

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
		const bootstrapAsync = async () => {

			await getPermissions();
			await setAppDetails();

			let userToken;
			try {
				// Restore token stored in `SecureStore` or any other encrypted storage
				userToken = await SecureStore.getItemAsync('userToken');
			} catch(e) {
				// Restoring token failed
				console.log(e);
			}
			dispatch({ type: 'RESTORE_TOKEN', token: userToken });
		};
		bootstrapAsync();
	}, [])

	const authContext = React.useMemo(
		() => ({
			signIn: async (data) => {
				let userToken;
				let libraryData = data.libraryData;
				try {
					const postBody = new FormData();
					postBody.append('username', data.valueUser);
					postBody.append('password', data.valueSecret);
					const api = create({
						baseURL: data.libraryUrl + '/API',
						timeout: 5000,
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
								// store login data for safe keeping
								await SecureStore.setItemAsync("userKey", data.valueUser);
								await SecureStore.setItemAsync("secretKey", data.valueSecret);
								await SecureStore.setItemAsync("userToken", userToken);
								// save variables in the Secure Store to access later on
								await SecureStore.setItemAsync("patronName", patronName);
								await SecureStore.setItemAsync("library", libraryData['libraryId']);
								await SecureStore.setItemAsync("libraryName", libraryData['name']);
								await SecureStore.setItemAsync("locationId", libraryData['locationId']);
								await SecureStore.setItemAsync("solrScope", libraryData['solrScope']);
								await SecureStore.setItemAsync("pathUrl", libraryData['baseUrl']);
								await SecureStore.setItemAsync("logo", libraryData['logo']);
								await SecureStore.setItemAsync("favicon", libraryData['favicon']);
								//await SecureStore.setItemAsync("aspenSession", result.session);
								dispatch({ type: 'SIGN_IN', token: userToken });
							} else {
								console.log("Invalid user. Unable to store data.");
								popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), "error");
							}
						} else {
							console.log("Unable to validate user account. ");
							popAlert(translate('error.no_server_connection'), 'We\'re unable to validate your account at this time.', "warning");
						}

					} else {
						const result = response.problem;
						popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
						console.log(result);
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

	return (
		<AuthContext.Provider value={authContext}>
		<NavigationContainer theme={navigationTheme}>
			<Stack.Navigator>
				{state.isLoading ? (
					<Stack.Screen
						name="Splash"
						component={LoadingScreen}
						options={{
							headerShown: false,
						}}
					/>
				) : state.userToken == null ? (
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
						component={AppStack}
						options={{
							headerShown: false,
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