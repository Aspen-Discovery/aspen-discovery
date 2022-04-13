import React, {Component, useState} from "react";
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
import {getCheckedOutItems, getHolds, getILSMessages, getProfile, reloadProfile} from "../../util/loadPatron";
import {setGlobalVariables} from "../../util/setVariables";
import {saveLanguage} from "../../util/accountActions";
import {userContext} from "../../context/user";

export class DrawerContent extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			displayLanguage: "",
			user: {
				displayName: "",
				cat_username: "",
				numCheckedOut: 0,
				numOverdue: 0,
				numHolds: 0,
				numHoldsAvailable: 0,
				interfaceLanguage: "en"
			},
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
		};
			setGlobalVariables();
	}

	loadILSMessages = async () => {
		const tmp = await AsyncStorage.getItem('@ILSMessages');
		const content = JSON.parse(tmp);
		this.setState({
			messages: content,
			isLoading: false,
		})
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
					await getCheckedOutItems(libraryUrl);
					await getHolds(libraryUrl);
					await getILSMessages(libraryUrl);
					//await getPickupLocations(libraryUrl);
					//await getPatronBrowseCategories(libraryUrl);
					//await getLists(libraryUrl);

					await this.loadILSMessages();
					//await this.loadLanguages();

					this.setState({
						asyncLoaded: true,
					})
				}
			}
		}
	}

	checkContext = async (context) => {
		console.log(context.user);
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

		this.interval = setInterval(() => {
			this.loadILSMessages();
			//this.loadLanguages();
		}, 1000)

		return () => clearInterval(this.interval);

	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	handleNavigation = (stack, screen, libraryUrl) => {
		this.props.navigation.navigate(stack, {screen: screen, params: {libraryUrl: libraryUrl}});
	};

	displayILSMessages = (messages) => {
		return (
			messages.map((item) => {
				return showILSMessage(item.messageStyle, item.message);
			})
		)
	}

	handleRefreshProfile = async () => {
		await getProfile(true).then(response => {
			this.context.user = response;
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

		let icon;
		if(library.logoApp) {
			icon = library.logoApp;
		} else {
			icon = library.favicon;
		}

		//console.log(this.context.library);

		return (
			<DrawerContentScrollView>
				<VStack space="4" my="2" mx="1" divider={<Divider/>}>
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

								{library ? (<Text fontSize="12" fontWeight="500">{location.displayName}</Text>) : null}
								<HStack space={1} alignItems="center">
									<Icon as={MaterialIcons} name="credit-card" size="xs"/>
									{user ? (<Text fontSize="12" fontWeight="500">{user.cat_username}</Text>) : null}
								</HStack>
							</Box>
						</HStack>
					</Box>

					{messages ? this.displayILSMessages(messages) : null}

					<VStack divider={<Divider/>} space="4">
						<VStack>
							<Pressable px="2" py="2" rounded="md" onPress={() => {
								this.handleNavigation('AccountScreenTab', 'CheckedOut', library.baseUrl)
							}}>
								<HStack space="1" alignItems="center">
									<Icon as={MaterialIcons} name="chevron-right" size="7"/>
									<VStack w="100%">
										<Text fontWeight="500">{translate('checkouts.title')} {user ? (
											<Text bold>({user.numCheckedOut})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{user.numOverdue !== null && user.numOverdue > 0 ? (
									<Container>
										<Badge colorScheme="error" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('checkouts.overdue_summary', {count: user.numOverdue})}</Badge>
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
											<Text bold>({user.numHolds})</Text>) : null}</Text>
									</VStack>
								</HStack>
								{user.numHoldsAvailable !== null && user.numHoldsAvailable > 0 ? (
									<Container>
										<Badge colorScheme="success" ml={10} rounded="4px"
										       _text={{fontSize: "xs"}}>{translate('holds.ready_for_pickup', {count: user.numHoldsAvailable})}</Badge>
									</Container>
								) : null}
							</Pressable>

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
								{library.allowLinkedAccounts ? (
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
								{library.allowUserLists ? (
									<Pressable px="2" py="3" onPress={() => {this.handleNavigation('AccountScreenTab', 'Preferences', library.baseUrl)}}>
										<HStack space="1" alignItems="center">
											<Icon as={MaterialIcons} name="chevron-right" size="7"/>
											<Text fontWeight="500">
												{translate('user_profile.preferences')}
											</Text>
										</HStack>
									</Pressable>
								): null}
							</VStack>
						</VStack>
					</VStack>
					<VStack space={3} alignItems="center">
						<HStack space={2}>
							<LogOutButton/>
						</HStack>
						<UseColorMode/>
						<Button size="xs" colorScheme="tertiary" onPress={() => this.handleRefreshProfile(library.libraryUrl)} variant="ghost" leftIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>Refresh Account</Button>
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
		<Button size="xs" colorScheme="tertiary" onPress={() => reloadProfile(props.libraryUrl)} variant="ghost" leftIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>Refresh Account</Button>
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