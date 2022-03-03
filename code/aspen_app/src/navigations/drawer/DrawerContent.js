import React, {Component, useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import { DrawerContentScrollView } from "@react-navigation/drawer";
import { Container, Badge, VStack, Box, Text, HStack, Icon, Pressable, Divider, Image, Button, Modal, HamburgerIcon, Menu } from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import {translate} from "../../translations/translations";
import {UseColorMode} from "../../themes/theme";
import {AuthContext} from "../../components/navigation";
import Constants from 'expo-constants';
import _ from "lodash";
import i18n from 'i18n-js';
import {showILSMessage} from "../../components/Notifications";
import {getCheckedOutItems, getHolds, getPatronBrowseCategories, getProfile} from "../../util/loadPatron";
import {
	getBrowseCategories, getLanguages,
	getLibraryInfo,
	getLocationInfo,
	getPickupLocations
} from "../../util/loadLibrary";
import {setGlobalVariables} from "../../util/setVariables";
import {GLOBALS} from "../../util/globals";
import {saveLanguage} from "../../util/accountActions";
import {getDefaultTranslations} from "../../translations/TranslationService";
import {removeData} from "../../util/logout";

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
			}
			,
			library: {
				name: "",
			}
			,
			messages: [],
			languages: [],
			langB: [],
		};
		setGlobalVariables();
		getLocationInfo();
		getLanguages();
		getBrowseCategories();
		getProfile();
		getCheckedOutItems();
		getHolds();
		getPickupLocations();
		getPatronBrowseCategories();
		getDefaultTranslations();
		this.loadUser();
		this.loadLibrary();
		this.loadILSMessages();
		this.loadLanguages();
	}

	loadUser = async () => {
		try {
			const tmp = await AsyncStorage.getItem('@patronProfile');
			const profile = JSON.parse(tmp);
			this.setState({
				user: profile,
				isLoading: false,
			})
		} catch (err) {
			await removeData().then(res => {
				console.log("Patron not found. Ending session to try again.")
				console.log(err);
			});
		}
	}

	loadLibrary = async () => {
		try {
			const tmp = await AsyncStorage.getItem('@patronLibrary');
			const profile = JSON.parse(tmp);
			this.setState({
				library: profile,
				isLoading: false,
			})
		} catch (err) {
			await removeData().then(res => {
				console.log("Library not found. Ending session to try again.")
				console.log(err);
			});
		}
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

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this.loadUser();
		await this.loadLibrary();
		await this.loadLanguages();

		this.interval = setInterval(() => {
			this.loadUser()
			this.loadLibrary();
			this.loadLanguages();
		}, 1000)

		return () => clearInterval(this.interval)
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	handleNavigation = (stack, screen) => {
		this.props.navigation.navigate(stack, {screen: screen});
	};

	displayILSMessages = (messages) => {
		return (
			messages.map((item) => {
				return showILSMessage(item.messageStyle, item.message);
			})
		)
	}

	render() {
		const {user, library, messages} = this.state;

		return (
			<DrawerContentScrollView>
					<VStack space="4" my="2" mx="1" divider={<Divider />}>
						<Box px="4">
							<HStack space={3} alignItems="center">
								<Image
									source={{
										uri: Constants.manifest.extra.libraryCardLogo
									}}
									fallbackSource={require("../../themes/default/aspenLogo.png")}
									w={42}
									h={42}
									alt={translate('user_profile.library_card')}
									rounded="8"
								/>
								<Box>
									<Text bold fontSize="14">{user.displayName}</Text>
									<Text fontSize="12" fontWeight="500">{library.name}</Text>
									<HStack space={1} alignItems="center" >
										<Icon as={MaterialIcons} name="credit-card" size="xs"/>
										<Text fontSize="12" fontWeight="500">{user.cat_username}</Text>
									</HStack>
								</Box>
							</HStack>
						</Box>

						{messages ? this.displayILSMessages(messages) : null}

						<VStack divider={<Divider />} space="4">
							<VStack>
								<Pressable px="2" py="2" rounded="md" onPress={() => { this.handleNavigation('AccountScreenTab', 'CheckedOut')}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7" />
										<VStack w="100%">
											<Text fontWeight="500">{translate('checkouts.title')} <Text bold>({user.numCheckedOut})</Text></Text>
										</VStack>
									</HStack>
									{user.numOverdue > 0 ? (
										<Container>
											<Badge colorScheme="error" ml={10} rounded="4px" _text={{ fontSize: "xs" }}>{translate('checkouts.overdue_summary', {count: user.numOverdue})}</Badge>
										</Container>
									) : null}

								</Pressable>

								<Pressable px="2" py="3" rounded="md" onPress={() => { this.handleNavigation('AccountScreenTab', 'Holds')}}>
									<HStack space="1" alignItems="center">
										<Icon as={MaterialIcons} name="chevron-right" size="7" />
										<VStack w="100%">
											<Text fontWeight="500">{translate('holds.title')} <Text bold>({user.numHolds})</Text></Text>
										</VStack>
									</HStack>
									{user.numHoldsAvailable > 0 ? (
										<Container>
											<Badge colorScheme="success" ml={10} rounded="4px" _text={{ fontSize: "xs" }}>{translate('holds.ready_for_pickup', {count: user.numHoldsAvailable})}</Badge>
										</Container>
									) : null}
								</Pressable>

								{/*						<Pressable px="2" py="3" rounded="md" bg="transparent">
							<HStack space="1" alignItems="center">
								<Icon as={MaterialIcons} name="chevron-right" size="7" />
								<VStack w="100%">
									<Text fontWeight="500">{translate('user_profile.my_lists')}</Text>
								</VStack>
							</HStack>
						</Pressable>*/}

							</VStack>
							<VStack space="3">
								<VStack>
									<Pressable px="2" py="3" onPress={() => { this.handleNavigation('AccountScreenTab', 'ProfileScreen')}}>
										<HStack space="1" alignItems="center">
											<Icon as={MaterialIcons} name="chevron-right" size="7" />
											<Text fontWeight="500">
												{translate('user_profile.profile')}
											</Text>
										</HStack>
									</Pressable>
									<Pressable px="2" py="2" onPress={() => this.handleNavigation('AccountScreenTab', 'LinkedAccounts')}>
										<HStack space="1" alignItems="center">
											<Icon as={MaterialIcons} name="chevron-right" size="7" />
											<Text fontWeight="500">
												{translate('user_profile.linked_accounts')}
											</Text>
										</HStack>
									</Pressable>
									<Pressable px="2" py="3" onPress={() => { this.handleNavigation('AccountScreenTab', 'Preferences')}}>
										<HStack space="1" alignItems="center">
											<Icon as={MaterialIcons} name="chevron-right" size="7" />
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
		<Button size="sm" colorScheme="secondary" onPress={signOut} leftIcon={<Icon as={MaterialIcons} name="logout" size="xs" />}>{translate('general.logout')}</Button>
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