import React, {Component, useState} from "react";
import {Linking} from "react-native";
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import {DrawerContentScrollView} from "@react-navigation/drawer";
import {Badge, Box, Button, Container, Divider, HStack, Icon, Image, Menu, Pressable, Text, VStack} from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import {translate} from "../../translations/translations";
import {UseColorMode} from "../../themes/theme";
import {AuthContext} from "../../components/navigation";
import _ from "lodash";
import {showILSMessage} from "../../components/Notifications";
import {getILSMessages, getProfile, PATRON, reloadProfile} from "../../util/loadPatron";
import {saveLanguage} from "../../util/accountActions";
import {userContext} from "../../context/user";
import * as Notifications from 'expo-notifications';
import * as ExpoLinking from 'expo-linking';
import Constants from "expo-constants";
import {GLOBALS} from "../../util/globals";
import {LIBRARY} from "../../util/loadLibrary";
import {getLanguageDisplayName} from "../../translations/TranslationService";

Notifications.setNotificationHandler({
	handleNotification: async () => ({
		shouldShowAlert: true,
		shouldPlaySound: true,
		shouldSetBadge: true,
	}),
});

const prefix = ExpoLinking.createURL("/");

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
			languageDisplayLabel: "English",
			asyncLoaded: false,
			notification: {},
			fines: 0,
			language: this.context.user.interfaceLanguage,
			num: {
				'checkedOut': this.context.user.numCheckedOut ?? 0,
				'holds': this.context.user.numHolds ?? 0,
				'lists': this.context.user.numLists ?? 0,
				'overdue': this.context.user.numOverdue ?? 0,
				'ready': this.context.user.numHoldsAvailable ?? 0,
				'savedSearches': this.context.user.numSavedSearches ?? 0,
				'updatedSearches': this.context.user.numSavedSearchesNew ?? 0,
			}
		};
		this._isMounted = false;
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
		this._isMounted = true;
		const languageDisplay = getLanguageDisplayName(PATRON.language);

		if (this._isMounted) {
			this._getLastListUsed();
		}

		this.setState({
			isLoading: false,
			messages: PATRON.messages,
			languageDisplayLabel: languageDisplay
		});

		Notifications.addNotificationReceivedListener(this._handleNotification);
		Notifications.addNotificationResponseReceivedListener(this._handleNotificationResponse);

		this.interval = setInterval(() => {
			if (this._isMounted) {
				this.loadProfile();
				//this.loadLanguages();
			}
		}, GLOBALS.timeoutSlow)

		return () => {
			clearInterval(this.interval);
		};
	}

	_handleNotification = notification => {
		this.setState({notification: notification});
	};

	_handleNotificationResponse = async response => {
		await this._addStoredNotification(response);
		let url = decodeURIComponent(response.notification.request.content.data.url).replace( /\+/g, ' ' );
		url = url.concat("&results=[]");
		url = url.replace("aspen-lida://", prefix)

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

	_getLastListUsed = () => {
		if (this.context.user) {
			PATRON.listLastUsed = this.context.user.lastListUsed;
		}
	}

	componentWillUnmount() {
		this._isMounted = false;
		clearInterval(this.interval);
	}

	componentDidUpdate(prevProps, prevState) {
		if (prevState.user !== this.state.user) {
			this.context.user(this.state.user);
		}

		if (prevState.messages !== PATRON.messages) {
			this.setState({
				messages: PATRON.messages,
			})
		}

		if (prevState.fines !== PATRON.fines) {
			this.setState({
				fines: PATRON.fines,
			})
		}

		if (prevState.num !== PATRON.num) {
			this.setState({
				num: PATRON.num,
			})
		}

		if (prevState.language !== PATRON.language) {
			this.setState({
				language: PATRON.language,
			})
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

	displayFinesMessage = () => {
		if (this.state.fines !== 0) {
			const message = "Your accounts have " + this.state.fines + " in fines.";
			return showILSMessage('warning', message)
		}
	}

	displayILSMessages = (messages) => {
		if (_.isArray(messages) === true) {
			return (
				messages.map((item) => {
					if (item.message) {
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
		const {messages, fines} = this.state;
		const {checkedOut, holds, overdue, ready, lists, savedSearches, updatedSearches} = this.state.num;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		let discoveryVersion;
		if (typeof library !== "undefined") {
			if (library.discoveryVersion) {
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
			} else if(library.favicon) {
				icon = library.favicon;
			} else {
				icon = Constants.manifest.ios.icon;
			}
		}

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
								{user && user.displayName ? (<Text bold fontSize="14">{user.displayName}</Text>) : null}

								{library && library.displayName ? (<Text fontSize="12" fontWeight="500">{library.displayName}</Text>) : null}
								<HStack space={1} alignItems="center">
									<Icon as={MaterialIcons} name="credit-card" size="xs"/>
									{user ? (<Text fontSize="12" fontWeight="500">{user.cat_username}</Text>) : null}
								</HStack>
							</Box>
						</HStack>
					</Box>

					{fines ? this.displayFinesMessage() : null}
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
											<Text bold>({checkedOut})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{overdue > 0 ? (
									<Container>
										<Badge colorScheme="error" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('checkouts.overdue_summary', {count: overdue})}</Badge>
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
											<Text bold>({holds})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{ready > 0 ? (
									<Container>
										<Badge colorScheme="success" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('holds.ready_for_pickup', {count: ready})}</Badge>
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
												<Text bold>({lists})</Text>) : null}</Text>
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
												<Text bold>({savedSearches})</Text>) : null}</Text>
										</VStack>
									</HStack>
									{updatedSearches > 0 ? (
										<Container>
											<Badge colorScheme="warning" ml={10} rounded="4px"
											       _text={{fontSize: "xs"}}>{translate('user_profile.saved_searches_updated', {count: updatedSearches})}</Badge>
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
	const initialLabel = props.initial;
	const [language, setLanguage] = useState(PATRON.language);
	const [label, setLabel] = useState(initialLabel);

	const updateLanguage = async (newVal) => {
		await saveLanguage(newVal);
		setLanguage(newVal);
		setLabel(getLanguageDisplayName(newVal));
	};


	return <Box>
		<Menu closeOnSelect={true} w="190" trigger={triggerProps => {
			return <Pressable {...triggerProps}>
				<Button size="md" colorScheme="secondary" leftIcon={<Icon as={MaterialIcons} name="language"
				                                                          size="xs"/>} {...triggerProps}>{label}</Button>
			</Pressable>;
		}}>
			<Menu.OptionGroup defaultValue={PATRON.language} title="Select a Language" type="radio"
			                  onChange={(val) => updateLanguage(val)}>
				{LIBRARY.languages.map((language) => {
					return (
						<Menu.ItemOption value={language.code}>{language.displayName}</Menu.ItemOption>
					)
				})}
			</Menu.OptionGroup>
		</Menu>
	</Box>;
};

export default DrawerContent