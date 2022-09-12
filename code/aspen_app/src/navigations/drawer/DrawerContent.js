import React, {Component, useState} from "react";
import {Linking} from "react-native";
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import {DrawerContentScrollView} from "@react-navigation/drawer";
import {Badge, Box, Button, Container, Divider, HStack, Icon, Image, Menu, Pressable, Text, VStack, CircleIcon} from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import {translate} from "../../translations/translations";
import {UseColorMode} from "../../themes/theme";
import {AuthContext} from "../../components/navigation";
import _ from "lodash";
import {showILSMessage} from "../../components/Notifications";
import {getILSMessages, getProfile, reloadProfile} from "../../util/loadPatron";
import {setGlobalVariables} from "../../util/setVariables";
import {saveLanguage} from "../../util/accountActions";
import {userContext} from "../../context/user";
import * as Notifications from 'expo-notifications';
import * as ExpoLinking from 'expo-linking';
import {Platform} from "react-native";

Notifications.setNotificationHandler({
	handleNotification: async () => ({
		shouldShowAlert: true,
		shouldPlaySound: true,
		shouldSetBadge: true,
	}),
});

const prefix = ExpoLinking.createURL("/");
console.log(prefix);

//console.log(redirectUrl);

export class DrawerContent extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			displayLanguage: "",
			user: this.context.user,
			location: {
				name: "",
			},
			library: {
				name: "",
				logoApp: global.favicon,
			},
			messages: [],
			languages: [],
			langB: [],
			asyncLoaded: false,
			notification: {},
		};
			//setGlobalVariables();
	}

	loadILSMessages = async () => {
		let libraryUrl;
		try {
			libraryUrl = await AsyncStorage.getItem('@pathUrl');
		} catch (e) {
			console.log(e);
		}

		if(libraryUrl) {
			await getILSMessages(libraryUrl).then(response => {
				this.setState({
					messages: response,
					isLoading: false,
				})
			})
		}
	}

	loadLanguages = async () => {
		const tmp = await AsyncStorage.getItem('@libraryLanguages');
		let languages = JSON.parse(tmp);
		languages = _.values(languages);
		this.setState({
			languages: languages,
			langB: JSON.parse(tmp),
			isLoading: false,
		})
	}

	bootstrapAsync = async (libraryUrl) => {
		let userToken;
		try {
			userToken = await AsyncStorage.getItem('@userToken');
		} catch (e) {
			console.log(e);
		}

		if(userToken) {
			if(this.state.asyncLoaded === false) {
				if(typeof libraryUrl !== "undefined") {
					//await setGlobalVariables()
					//await getLanguages(libraryUrl);
					//await getBrowseCategories(libraryUrl);
					//await getCheckedOutItems(libraryUrl);
					//await getHolds(libraryUrl);
					//await getILSMessages(libraryUrl);
					//await getPickupLocations(libraryUrl);
					//await getPatronBrowseCategories(libraryUrl);
					//await getLists(libraryUrl);

					await this.loadILSMessages(libraryUrl);
					//await this.loadLanguages();

					this.setState({
						asyncLoaded: true,
					})
				}
			}
		}
	}

	checkContext = async (context) => {
		if(_.isEmpty(context.user)) {
			await AsyncStorage.removeItem('@userToken');
			await AsyncStorage.removeItem('@pathUrl');
			await SecureStore.deleteItemAsync("userToken");
			await AsyncStorage.removeItem('@patronProfile');
			await AsyncStorage.removeItem('@libraryInfo');
			await AsyncStorage.removeItem('@locationInfo');
			this.props.navigation.navigate("Login");
		}
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		//await this.loadLanguages();

		Notifications.addNotificationReceivedListener(this._handleNotification);
		Notifications.addNotificationResponseReceivedListener(this._handleNotificationResponse);

		this.interval = setInterval(() => {
			this.loadILSMessages();
			this.loadProfile();
			//this.loadLanguages();
		}, 300000)

		return () => {
			clearInterval(this.interval);
		};
	}

	_handleNotification = notification => {
		this.setState({notification: notification});
	};

	_handleNotificationResponse = async response => {
		await this._addStoredNotification(response);
		//console.log("encoded", response.notification.request.content.data.url)
		let url = decodeURIComponent(response.notification.request.content.data.url).replace( /\+/g, ' ' );
		//console.log("decoded", url);
		url = url.concat("&results=[]");

		console.log(prefix);
		url = url.replace("aspen-lida://", prefix)
		console.log("response", url);

		//const parsedUrl = await Linking.parse(encodeURI(url));
		//console.log("parsedUrl", parsedUrl);


		console.log("Checking url...");
		const supported = await Linking.canOpenURL(url);
		if(supported) {
			try {
				console.log("Opening url...");
				await Linking.openURL(url);
			} catch(e) {
				console.log("Could not open url");
				console.log(e);
			}
		} else {
			console.log("Could not open url");
		}
		//Linking.openURL(url);
	};

	_getStoredNotifications = async () => {
		try {
			const notifications = await AsyncStorage.getItem('@notifications');
			return notifications != null ? JSON.parse(notifications) : null;
		} catch (e) {
			console.log(e);
		}
	}

	_createNotificationStorage = async (message) => {
		try {
			let array = [];
			array.push(message);
			const notification = JSON.stringify(array);
			await AsyncStorage.setItem('@notifications', notification);
		} catch (e) {
			console.log(e);
		}
	}

	_addStoredNotification = async (message) => {
		let storage = await this._getStoredNotifications().then(async response => {
			if (response) {
				//console.log(response);
				response.push(message);
				try {
					await AsyncStorage.setItem('@notifications', JSON.stringify(response));
				} catch (e) {
					console.log(e);
				}
			} else {
				await this._createNotificationStorage(message);
			}
		});
	}


	componentWillUnmount() {
		clearInterval(this.interval);
	}

	componentDidUpdate(prevProps, prevState) {
		if (prevState.user !== this.state.user) {
			this.context.user(this.state.user);
		}
	}

	handleNavigation = (stack, screen, libraryUrl) => {
		this.props.navigation.navigate(stack, {screen: screen, params: {libraryUrl: libraryUrl}});
	};

	loadProfile = async () => {
		await getProfile().then(response => {
			this.context.user = response;
		})
	}

	displayILSMessages = (messages) => {
		if (_.isArray(messages) === true) {
			return (
				messages.map((item) => {
					if(item.message) {
						return showILSMessage(item.messageStyle, item.message);
					}
				})
			)
		} else {
			return null;
		}
	}

	handleRefreshProfile = async (libraryUrl) => {
		await reloadProfile(libraryUrl).then(response => {
			this.context.user = response;
		});
		await getILSMessages(libraryUrl).then(response => {
			this.setState({
				messages: response,
			})
		})
	}

	static contextType = userContext;

	render() {
		const {messages} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if(this.state.asyncLoaded === false && library.baseUrl !== null) {
			this.bootstrapAsync(library.baseUrl);
		}

		let discoveryVersion;
		if(typeof library !== "undefined") {
			if(library.discoveryVersion) {
				let version = library.discoveryVersion;
				version = version.split(" ");
				discoveryVersion = version[0];
			} else {
				discoveryVersion = "22.06.00";
			}
		} else {
			discoveryVersion = "22.06.00";
		}

		let icon;
		if(typeof library !== "undefined") {
			if(library.logoApp) {
				icon = library.logoApp;
			} else {
				icon = library.favicon;
			}
		}

		let numOverdue;
		if(typeof user !== "undefined") {
			if(typeof user.numOverdue !== "undefined") {
				if(user.numOverdue !== null) {
					numOverdue = user.numOverdue;
				} else {
					numOverdue = 0;
				}
			} else {
				numOverdue = 0;
			}
		} else {
			numOverdue = 0;
		}

		let numCheckedOut;
		if(typeof user !== "undefined") {
			if(typeof user.numCheckedOut !== "undefined") {
				if(user.numCheckedOut !== null) {
					numCheckedOut = user.numCheckedOut;
				} else {
					numCheckedOut = 0;
				}
			} else {
				numCheckedOut = 0;
			}
		} else {
			numCheckedOut = 0;
		}

		let numHolds;
		if(typeof user !== "undefined") {
			if(typeof user.numHolds !== "undefined") {
				if(user.numHolds !== null) {
					numHolds = user.numHolds;
				} else {
					numHolds = 0;
				}
			} else {
				numHolds = 0;
			}
		} else {
			numHolds = 0;
		}

		let numHoldsAvailable;
		if(typeof user !== "undefined") {
			if(typeof user.numHoldsAvailable !== "undefined") {
				if(user.numHoldsAvailable !== null) {
					numHoldsAvailable = user.numHoldsAvailable;
				} else {
					numHoldsAvailable = 0;
				}
			} else {
				numHoldsAvailable = 0;
			}
		} else {
			numHoldsAvailable = 0;
		}

		let numLists;
		if(typeof user !== "undefined") {
			if(typeof user.numLists !== "undefined") {
				if(user.numLists !== null) {
					numLists = user.numLists;
				} else {
					numLists = 0;
				}
			} else {
				numLists = 0;
			}
		} else {
			numLists = 0;
		}

		let numSavedSearches;
		if(typeof user !== "undefined") {
			if(typeof user.numSavedSearches !== "undefined") {
				if(user.numSavedSearches !== null) {
					numSavedSearches = user.numSavedSearches;
				} else {
					numSavedSearches = 0;
				}
			} else {
				numSavedSearches = 0;
			}
		} else {
			numSavedSearches = 0;
		}

		let numSavedSearchesNew;
		if(typeof user !== "undefined") {
			if(typeof user.numSavedSearchesNew !== "undefined") {
				if(user.numSavedSearchesNew !== null) {
					numSavedSearchesNew = user.numSavedSearchesNew;
				} else {
					numSavedSearchesNew = 0;
				}
			} else {
				numSavedSearchesNew = 0;
			}
		} else {
			numSavedSearchesNew = 0;
		}

		console.log(library);

		return (
			<DrawerContentScrollView>
				<VStack space="4" my="2" mx="1">
					<Box px="4">
						<HStack space={3} alignItems="center">
							<Image
								source={{uri: icon}}
								fallbackSource={require("../../themes/default/aspenLogo.png")}
								w={42}
								h={42}
								alt={translate('user_profile.library_card')}
								rounded="8"
							/>
							<Box>
								{user ? (<Text bold fontSize="14">{user.displayName}</Text>) : null}

								{library ? (<Text fontSize="12" fontWeight="500">{library.displayName}</Text>) : null}
								<HStack space={1} alignItems="center">
									<Icon as={MaterialIcons} name="credit-card" size="xs"/>
									{user ? (<Text fontSize="12" fontWeight="500">{user.cat_username}</Text>) : null}
								</HStack>
							</Box>
						</HStack>
					</Box>

					{messages ? this.displayILSMessages(messages) : null}

					<Divider />

					<VStack divider={<Divider/>} space="4">
						<VStack>
							<Pressable px="2" py="2" rounded="md" onPress={() => {
								this.handleNavigation('AccountScreenTab', 'CheckedOut', library.baseUrl)
							}}>
								<HStack space="1" alignItems="center">
									<Icon as={MaterialIcons} name="chevron-right" size="7"/>
									<VStack w="100%">
										<Text fontWeight="500">{translate('checkouts.title')} {user ? (
											<Text bold>({numCheckedOut})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{numOverdue > 0 ? (
									<Container>
										<Badge colorScheme="error" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('checkouts.overdue_summary', {count: numOverdue})}</Badge>
									</Container>
								) : null}

							</Pressable>

							<Pressable px="2" py="3" rounded="md" onPress={() => {
								this.handleNavigation('AccountScreenTab', 'Holds', library.baseUrl)
							}}>
								<HStack space="1" alignItems="center">
									<Icon as={MaterialIcons} name="chevron-right" size="7"/>
									<VStack w="100%">
										<Text fontWeight="500">{translate('holds.title')} {user ? (
											<Text bold>({numHolds})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{numHoldsAvailable > 0 ? (
									<Container>
										<Badge colorScheme="success" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('holds.ready_for_pickup', {count: numHoldsAvailable})}</Badge>
									</Container>
								) : null}
							</Pressable>

							{discoveryVersion >= "22.08.00" ? (
								<Pressable px="2" py="3" rounded="md" onPress={() => {
									this.handleNavigation('AccountScreenTab', 'Lists', library.baseUrl)
								}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7"/>
										<VStack w="100%">
											<Text fontWeight="500">{translate('user_profile.my_lists')} {user ? (
												<Text bold>({numLists})</Text>) : null}</Text>
										</VStack>
									</HStack>
								</Pressable>
							) : (
								<Pressable px="2" py="3" rounded="md" onPress={() => {
									this.handleNavigation('AccountScreenTab', 'Lists', library.baseUrl)
								}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7"/>
										<VStack w="100%">
											<Text fontWeight="500">{translate('user_profile.my_lists')}</Text>
										</VStack>
									</HStack>
								</Pressable>
							) }

							{discoveryVersion >= "22.08.00" ? (
								<Pressable px="2" py="3" rounded="md" onPress={() => {
									this.handleNavigation('AccountScreenTab', 'SavedSearches', library.baseUrl)
								}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7"/>
										<VStack w="100%">
											<Text fontWeight="500">{translate('user_profile.saved_searches')} {user ? (
												<Text bold>({numSavedSearches})</Text>) : null}</Text>
										</VStack>
									</HStack>
									{numSavedSearchesNew > 0 ? (
										<Container>
											<Badge colorScheme="warning" ml={10} rounded="4px"
											       _text={{fontSize: "xs"}}>{translate('user_profile.saved_searches_updated', {count: numSavedSearchesNew})}</Badge>
										</Container>
									) : null}
								</Pressable>
							) : null}

						</VStack>
						<VStack space="3">
							<VStack>
								<Pressable px="2" py="3" onPress={() => {
									this.handleNavigation('AccountScreenTab', 'ProfileScreen', library.baseUrl)
								}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7"/>
										<Text fontWeight="500">
											{translate('user_profile.profile')}
										</Text>
									</HStack>
								</Pressable>
								{library.allowLinkedAccounts === "1" ? (
									<Pressable px="2" py="2"
									           onPress={() => this.handleNavigation('AccountScreenTab', 'LinkedAccounts', library.baseUrl)}>
										<HStack space="1" alignItems="center">
											<Icon as={MaterialIcons} name="chevron-right" size="7"/>
											<Text fontWeight="500">
												{translate('user_profile.linked_accounts')}
											</Text>
										</HStack>
									</Pressable>
								) : null}
								<Pressable px="2" py="3" onPress={() => {this.handleNavigation('AccountScreenTab', 'Preferences', library.baseUrl)}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7"/>
										<Text fontWeight="500">
											{translate('user_profile.preferences')}
										</Text>
									</HStack>
								</Pressable>
							</VStack>
						</VStack>
					</VStack>
					<VStack space={3} alignItems="center">
						<HStack space={2}>
							<LogOutButton/>
						</HStack>
						<UseColorMode/>
					</VStack>
				</VStack>
			</DrawerContentScrollView>
		)
	}
}

function LogOutButton() {
	const { signOut } = React.useContext(AuthContext);

	return(
		<Button size="md" colorScheme="secondary" onPress={signOut} leftIcon={<Icon as={MaterialIcons} name="logout" size="xs" />}>{translate('general.logout')}</Button>
	)
}

const ReloadProfileButton = (props) => {

	return(
		<Button size="xs" colorScheme="tertiary" onPress={() => props.handleRefreshProfile(props.libraryUrl)} variant="ghost" leftIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>{translate('general.refresh_account')}</Button>
	)
}

const LanguageSwitcher = (props) => {
	const userLanguage = props.userLanguage;
	const [language, setLanguage] = useState(props.userLanguage);
	const [label, setLabel] = useState(props.userLanguage);

	return <Box>
		<Menu closeOnSelect={true} w="190" trigger={triggerProps => {
			return <Pressable {...triggerProps}>
				<Button size="sm" colorScheme="secondary" leftIcon={<Icon as={MaterialIcons} name="language" size="xs" />} {...triggerProps}>{getLanguageDisplayName(label, props.allLanguages)}</Button>
			</Pressable>;
		}}>
			<Menu.OptionGroup defaultValue={userLanguage} title="Select a Language" type="radio" onChange={(val) => {
				setLanguage(val);
				setLabel(val);
				saveLanguage(val);
			}}>
				{props.availableLanguages.map((language) => {
					return (
						<Menu.ItemOption value={language.code}>{language.displayName}</Menu.ItemOption>
					)
				})}
			</Menu.OptionGroup>
		</Menu>
	</Box>;
};

function getLanguageDisplayName(code, languages) {
	let result = _.filter(languages, ['code', code]);
	result = _.values(result[0]);
	return result[2];
}

export default DrawerContent