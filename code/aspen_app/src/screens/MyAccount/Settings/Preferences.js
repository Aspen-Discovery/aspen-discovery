import React, {Component} from 'react';
import {Box, FlatList, HStack, Icon, Pressable, Text} from 'native-base';
import * as WebBrowser from 'expo-web-browser';
import Constants from 'expo-constants';
import {MaterialIcons} from '@expo/vector-icons';
import * as Notifications from 'expo-notifications';
import _ from 'lodash';

// custom components and helper files
import {translate} from '../../../translations/translations';
import {userContext} from '../../../context/user';

export default class Preferences extends Component {
	static contextType = userContext;

	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			hasUpdated: false,
			isRefreshing: false,
			expoToken: null,
			aspenToken: null,
			defaultMenuItems: [
				{
					key: '1',
					title: translate('user_profile.home_screen_settings'),
					path: 'SettingsHomeScreen',
					external: false,
					icon: 'chevron-right',
					version: '22.06.00',
				},
				{
					key: '2',
					title: translate('user_profile.manage_notifications'),
					path: 'SettingsNotifications',
					external: false,
					icon: 'chevron-right',
					version: '22.09.00',
				},
			],
		};

	}

	componentDidMount = async () => {
		if (Constants.isDevice) {
			let expoToken = (await Notifications.getExpoPushTokenAsync()).data;
			if (expoToken) {
				if (!_.isEmpty(this.context.user.notification_preferences)) {
					const tokenStorage = this.context.user.notification_preferences;
					if (_.find(tokenStorage, _.matchesProperty('token', expoToken))) {
						this.setState({
							expoToken: expoToken,
							aspenToken: true,
						});
					}
				}
			}
		}
		this.setState({
			isLoading: false,
		});
	};

	renderItem = (item, patronId, libraryUrl, discoveryVersion) => {
		const requiredVersion = item.version;
		if (item.external && (discoveryVersion >= requiredVersion)) {
			return (
					<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" py="3" onPress={() => {
						this.openWebsite(item.path);
					}}>
						<HStack space="1" alignItems="center">
							<Icon as={MaterialIcons} name={item.icon} size="7"/>
							<Text _dark={{color: 'warmGray.50'}} color="coolGray.800" bold fontSize={{base: 'md', lg: 'lg'}}>{item.title}</Text>
						</HStack>
					</Pressable>
			);
		} else if (discoveryVersion >= requiredVersion) {
			return (
					<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" py="3" onPress={() => {
						this.onPressMenuItem(item.path, patronId, libraryUrl);
					}}>
						<HStack space="1" alignItems="center">
							<Icon as={MaterialIcons} name={item.icon} size="7"/>
							<Text _dark={{color: 'warmGray.50'}} color="coolGray.800" bold fontSize={{base: 'md', lg: 'lg'}}>{item.title}</Text>
						</HStack>
					</Pressable>
			);
		} else {
			return null;
		}
	};

	onPressMenuItem = (path, patronId, libraryUrl) => {
		this.props.navigation.navigate(path, {libraryUrl: libraryUrl, patronId: patronId, user: this.context.user, pushToken: this.state.expoToken, aspenToken: this.state.aspenToken});
	};

	openWebsite = async (url) => {
		WebBrowser.openBrowserAsync(url);
	};

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		let discoveryVersion;
		if (typeof library !== 'undefined') {
			if (library.discoveryVersion) {
				let version = library.discoveryVersion;
				version = version.split(' ');
				discoveryVersion = version[0];
			} else {
				discoveryVersion = '22.06.00';
			}
		} else {
			discoveryVersion = '22.06.00';
		}

		return (
				<Box flex={1} safeArea={3}>
					<FlatList
							data={this.state.defaultMenuItems}
							renderItem={({item}) => this.renderItem(item, user.id, library.baseUrl, discoveryVersion)}
							keyExtractor={(item, index) => index.toString()}
					/>
				</Box>
		);
	}
}