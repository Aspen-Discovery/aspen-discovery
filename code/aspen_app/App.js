import React, { Component, setState, useState, useEffect } from "react";
import { View } from "react-native";
import { NativeBaseProvider, HStack, Spinner, Center, extendTheme, StatusBar, Text, ScrollView } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { createAppContainer, createSwitchNavigator } from "react-navigation";
import { createStackNavigator } from "react-navigation-stack";
import { createBottomTabNavigator } from "react-navigation-tabs";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as Location from "expo-location";
import * as SplashScreen from "expo-splash-screen";
import Constants from "expo-constants";
import * as Updates from "expo-updates";
import { SSRProvider } from "@react-aria/ssr";

// import helper files
import AccountDetails from "./screens/AccountDetails";
import AccountCheckouts from "./screens/AccountCheckouts";
import Discovery from "./screens/Discovery";
import ItemDetails from "./screens/ItemDetails";
import LibraryCard from "./screens/LibraryCard";
import Login from "./screens/Login";
import More from "./screens/More";
import Search from "./screens/Search";
import SearchResults from "./screens/SearchResults";
import ContactUs from "./screens/ContactUs";

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
		ItemDetails: ItemDetails,
		SearchResults: SearchResults,
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
		ContactUs: ContactUs,
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
		Account: AccountDetails,
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
		Discover: Discovery,
		ItemDetails: ItemDetails
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
			headerVisible: true,
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
	state = { appIsReady: false };

	async componentDidMount() {
		// fetch version to compare
		await AsyncStorage.getItem("version");

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

		this.setState({ appIsReady: true }, async () => {
			await SplashScreen.hideAsync();
		});

		if (Updates.releaseChannel == "production" || Updates.releaseChannel == "beta") {
		    await SecureStore.setItemAsync("releaseChannel", Updates.releaseChannel);
		} else {
			await SecureStore.setItemAsync("releaseChannel", "any");
		}

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

		return this.props.navigation.navigate(this.state.loginToken ? 'App' : 'Auth')
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

class AuthLoadingScreen extends Component {
	constructor(props) {
		super(props);
		this._loadData();
	}

	render() {
		return (
			<HStack>
				<Spinner accessibilityLabel="Loading..." />
			</HStack>
		);
	}



	_loadData = async () => {
        let result = await SecureStore.getItemAsync("userToken");
        if (result) {
            console.log("Keys found");
            this.props.navigation.navigate("App");
        } else {
            console.log("No keys found");
            this.props.navigation.navigate("Auth");
        }
	};
}

const AppNavigator = createSwitchNavigator(
	{
		Permissions: PermissionsScreen,
		Auth: LoginNavigator,
		App: MainNavigator,
	},
	{
		initialRouteName: "Permissions",
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
