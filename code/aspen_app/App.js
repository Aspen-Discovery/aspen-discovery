import React, { Component, setState, useState, useEffect } from "react";
import { View } from "react-native";
import { NativeBaseProvider, HStack, Spinner, Center, extendTheme, StatusBar, Text, ScrollView } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { createAppContainer, createSwitchNavigator } from "react-navigation";
import { createStackNavigator } from "react-navigation-stack";
import { createBottomTabNavigator } from "react-navigation-tabs";
import NavigationService from './components/NavigationService';
import { fadeOut } from 'react-navigation-transitions';
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as Location from "expo-location";
import * as SplashScreen from "expo-splash-screen";
import Constants from "expo-constants";
import * as Updates from "expo-updates";
import { SSRProvider } from "@react-aria/ssr";
import base64 from 'react-native-base64';

// import helper files
import Login from "./screens/Login";
import More from "./screens/More";

// account screens
import MyAccount from "./screens/MyAccount/MyAccount";
import CheckedOut from "./screens/MyAccount/CheckedOut";
import Holds from "./screens/MyAccount/Holds";
import LibraryCard from "./screens/MyAccount/LibraryCard";

// browse category screens
import BrowseCategoryHome from "./screens/BrowseCategory/Home";

// grouped work screens
import GroupedWork from "./screens/GroupedWork/GroupedWork";

// search screens
import Search from "./screens/Search/Search";
import Results from "./screens/Search/Results";

// library screens
import Contact from "./screens/Library/Contact";

// defines the Card tab and how it is handled
const CardTab = createStackNavigator(
	{
		Card: LibraryCard,
	},
	{
		defaultNavigationOptions: {
			headerStyle: {
				backgroundColor: "#4cc3cd",
			},
			headerTintColor: "#30373b",
			title: "Library Card",
		},
	}
);

// defines the Search tab and how it is handled
const SearchTab = createStackNavigator(
	{
		Search: Search,
		GroupedWork: GroupedWork,
		SearchResults: Results,
	},
	{
		defaultNavigationOptions: {
			headerStyle: {
				backgroundColor: "#4cc3cd",
			},
			headerTintColor: "#30373b",
			headerBackTitle: "",
			title: "Search",
		},
	}
);

// defines the News tab and how it is handled
const MoreTab = createStackNavigator(
	{
		More: More,
		Contact: Contact,
	},
	{
		defaultNavigationOptions: {
			headerStyle: {
				backgroundColor: "#4cc3cd",
			},
			headerTintColor: "#30373b",
			title: "More",
		},
	}
);

// defines the Account tab and how it is handled
const AccountTab = createStackNavigator(
	{
		Account: MyAccount,
		CheckedOut: CheckedOut,
		Holds: Holds,
		GroupedWork: GroupedWork,
	},
	{
		defaultNavigationOptions: {
			headerStyle: {
				backgroundColor: "#4cc3cd",
			},
			headerTintColor: "#30373b",
			title: "Account",
		},
	}
);

// defines the Account tab and how it is handled
const DiscoveryTab = createStackNavigator(
	{
		BrowseCategoryHome: BrowseCategoryHome,
		GroupedWork: GroupedWork
	},
	{
		defaultNavigationOptions: {
			headerStyle: {
				backgroundColor: "#4cc3cd",
			},
			headerTintColor: "#30373b",
			title: "Discover",
		},
	}
);

// establishes the flow for the MainApp
const MainApp = createBottomTabNavigator(
	{
		Discover: DiscoveryTab,
		Search: SearchTab,
		Card: CardTab,
		Account: AccountTab,
		More: MoreTab,
	},
	{
		resetOnBlur: true,
		defaultNavigationOptions: ({ navigation }) => ({
			tabBarIcon: ({ focused, horizontal, tintColor }) => {
				const { routeName } = navigation.state;
				let iconName;
				if (routeName === "Discover") {
					iconName = "library-outline";
				} else if (routeName === "Search") {
					iconName = "search";
				} else if (routeName === "Account") {
					iconName = "person-outline";
				} else if (routeName === "More") {
					iconName = "ellipsis-horizontal";
				} else if (routeName === "Card") {
					iconName = "card";
				}

				return <Ionicons name={iconName} size={25} color={tintColor} />;
			},
		}),
		tabBarOptions: {
			activeTintColor: "#956dab",
			inactiveTintColor: "#30373b",
		},
	}
);

const MainNavigator = createStackNavigator(
	{
		Home: { screen: MainApp },
	},
	{
		headerMode: "none",
		navigationOptions: {
			headerVisible: false,
		},
	}
);

// provides a login screen path to ensure that the account is logged into and can't be backed out of
const LoginNavigator = createStackNavigator(
	{
		Home: { screen: Login },
	},
	{
		headerMode: "none",
		navigationOptions: {
			headerVisible: false,
		},
	}
);

class PermissionsScreen extends Component {
	constructor(props) {
		super(props);
		this.state = {
			appIsReady: false,
			loginToken: false,
		};
	}

	async componentDidMount() {
        this.setState({
            loginToken: await SecureStore.getItemAsync("userToken"),
        })

		// Prevent native splash screen from autohiding
		try {
			await SplashScreen.preventAutoHideAsync();
		} catch (e) {
			console.warn(e);
		}
		this.prepareResources();
	}

	prepareResources = async () => {
		await getPermissions();
		await getAppDetails();

		this.setState({ appIsReady: true }, async () => {
			await SplashScreen.hideAsync();
		});

	};

	makeSession = async () => {
        var S4 = function () { return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1); };
        var sessionId = S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4();
        global.sessionId = sessionId;
	};

	render() {
		if (!this.state.appIsReady) {
			return null;
		}

		return this.props.navigation.navigate(this.state.loginToken ? 'Loading' : 'Auth')
	}
}

async function getPermissions() {
	let { status } = await Location.requestForegroundPermissionsAsync();

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

async function getAppDetails() {
    try {
        global.version = Constants.manifest.version;

        if (global.version == "production" || global.version == "beta") {
            await SecureStore.setItemAsync("releaseChannel", global.version);
        } else {
            await SecureStore.setItemAsync("releaseChannel", "any");
        }

        console.log("Release channel variable set.")
    } catch (e) {
        console.log("Error setting release channel variable.")
    }
}

class LoadingScreen extends Component {

	constructor() {
		super();
		this.state = {
            sessionReady: true,
		};
	}

	render() {
		if (!this.state.sessionReady) {
			return null;
		}

		return this.props.navigation.navigate(this.state.sessionReady ? 'App' : 'Loading')
	}
}


async function setGlobalVariables() {
    try {
        // prepare app data
        global.version = Constants.manifest.version;

        // prepare user data
        const userKey = await SecureStore.getItemAsync("userKey");
        global.userKey = base64.encode(userKey);
        const secretKey = await SecureStore.getItemAsync("secretKey");
        global.secretKey = base64.encode(secretKey);
        global.sessionId = await SecureStore.getItemAsync("sessionId");
        global.pickUpLocation = await SecureStore.getItemAsync("pickUpLocation");
        global.patron = await SecureStore.getItemAsync("patronName");

        // prepare library data
        global.libraryId = await SecureStore.getItemAsync("library");
        global.libraryName = await SecureStore.getItemAsync("libraryName");
        global.locationId = await SecureStore.getItemAsync("locationId");
        global.solrScope = await SecureStore.getItemAsync("solrScope");
        global.libraryUrl = await SecureStore.getItemAsync("pathUrl");
        global.logo = await SecureStore.getItemAsync("logo");
        global.favicon = await SecureStore.getItemAsync("favicon");

        // prepare urls for API calls
        global.aspenBrowseCategory = global.libraryUrl + "/app/aspenBrowseCategory.php?library=" + global.solrScope;
        global.aspenDiscover = global.libraryUrl + "/app/aspenDiscover.php?library=" + global.solrScope + "&lida=true";
        global.aspenAccountDetails = global.libraryUrl + "/app/aspenAccountDetails.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        global.aspenRenew = global.libraryUrl + '/app/aspenRenew.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        global.aspenListCKO = global.libraryUrl + '/app/aspenListCKO.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        global.aspenMoreDetails = global.libraryUrl + "/app/aspenMoreDetails.php?id=" + global.locationId + "&library=" + global.solrScope + "&version=" + global.version + "&index=";
        global.aspenListHolds = global.libraryUrl + '/app/aspenListHolds.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId + '&action=ilsCKO';
        global.aspenPickupLocations = global.libraryUrl + "/app/aspenPickUpLocations.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        global.aspenSearch = global.libraryUrl + "/app/aspenSearchLists.php?library=" + global.solrScope;
        global.aspenSearchResults = global.libraryUrl + "/app/aspenSearchResults.php?library=" + global.solrScope + "&lida=true";
        // we won't use this one by the time globals are set, but lets build it just in case we need to verify later on in the app
        global.aspenLogin = global.libraryUrl + "/app/aspenLogin.php?barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;

        console.log("Global variables set.")

    } catch(e) {
        console.log("Error setting global variables.");
        console.log(e);
    }
};

async function setSession() {
    var S4 = function () {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
    };

    var guid = S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4();

    try {
        await SecureStore.setItemAsync("sessionId", guid);
    } catch {
        const random = new Date().getTime()
        await SecureStore.setItemAsync("sessionId", random);
    }

    console.log("Session created.")

};

const AuthStack = createStackNavigator(
    {
        Permissions: {
            screen: PermissionsScreen
        },
        Auth: {
            screen: LoginNavigator
        },
        Loading: {
            screen: LoadingScreen
        }
    },
    {
        headerMode: "none",
        transitionConfig: () => fadeOut(),
        navigationOptions: {
            headerVisible: false,
        },
    }
);

const AppNavigator = createSwitchNavigator(
	{
		Auth: AuthStack,
		App: MainNavigator,
	},
	{
		initialRouteName: "Auth",
	},
    {
        headerMode: "none",
        navigationOptions: {
            headerVisible: false,
        },
    }
);

// Create the main app container and config
const AppContainer = createAppContainer(AppNavigator);
export default function App() {
    const aspenTheme = extendTheme({
        /**
        // Generate color swatches based on single hex @ https://smart-swatch.netlify.app
        // Generate color scheme off single hex @ https://palx.jxnblk.com
        // Based off of default theme on Model
        **/
       colors: {
            primary: {
              50: '#ddfbfe',
              100: '#bbedf0',
              200: '#98dfe4',
              300: '#71d1d9',
              400: '#4cc3cd',
              500: '#32aab3',
              600: '#22848c',
              700: '#135f64',
              800: '#003a3e',
              900: '#001618',
            },
            secondary: {
              50: '#f6fae4',
              100: '#e6edc2',
              200: '#d5e19e',
              300: '#c4d578',
              400: '#b5c953',
              500: '#9baf39',
              600: '#78882c',
              700: '#56611e',
              800: '#333a0f',
              900: '#101400',
            },
            tertiary: {
              50: '#f6edfc',
              100: '#ddcde6',
              200: '#c4add1',
              300: '#ad8cbe',
              400: '#956dab',
              500: '#7b5391',
              600: '#604072',
              700: '#452e52',
              800: '#2a1a32',
              900: '#110715',
            },
            darkGrey: {
                500: '#30373b'
            }
        },
        components: {
            Text: {
                baseStyle: {
                    _text: {
                        color: 'darkGrey',
                    },
                },
            },
            Button: {
                defaultProps: {
                    colorScheme: 'secondary',
                    size: 'lg'
                }
            },
            Spinner: {
                baseStyle: {
                    color: 'tertiary.500',
                },
                defaultProps: {
                    size: "lg"
                }
            }
        },
    });
	return (
		<SSRProvider>
			<NativeBaseProvider theme={aspenTheme}>
				<StatusBar barStyle="dark-content" />
				    <AppContainer />
			</NativeBaseProvider>
		</SSRProvider>
	);
}
