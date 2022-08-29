import React, {Component} from "react";
import {Box, Divider, HStack, Pressable, Button, Text, Heading, FlatList, Icon} from "native-base";
import * as WebBrowser from 'expo-web-browser';
import {MaterialIcons} from "@expo/vector-icons";

// custom components and helper files
import {translate} from "../../../translations/translations";
import {userContext} from "../../../context/user";

export default class Preferences extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			hasUpdated: false,
			isRefreshing: false,
			defaultMenuItems: [
				{
					key: '1',
					title: translate('user_profile.home_screen_settings'),
					path: 'SettingsHomeScreen',
					external: false,
					icon: 'chevron-right',
					version: '22.06.00'
				},
				{
					key: '2',
					title: translate('user_profile.manage_notifications'),
					path: 'SettingsNotifications',
					external: false,
					icon: 'chevron-right',
					version: '22.09.00'
				}
			]
		};

	}

	renderItem = (item, patronId, libraryUrl, discoveryVersion) => {
		const requiredVersion = item.version;
		if (item.external && (discoveryVersion >= requiredVersion)) {
			return (
				<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" py="3" onPress={() => {
					this.openWebsite(item.path)
				}}>
					<HStack space="1" alignItems="center">
						<Icon as={MaterialIcons} name={item.icon} size="7" />
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title}</Text>
					</HStack>
				</Pressable>
			);
		} else if(discoveryVersion >= requiredVersion) {
			return (
				<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" py="3" onPress={() => {
					this.onPressMenuItem(item.path, patronId, libraryUrl)
				}}>
					<HStack space="1" alignItems="center">
						<Icon as={MaterialIcons} name={item.icon} size="7" />
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title}</Text>
					</HStack>
				</Pressable>
			);
		} else {
			return null;
		}
	};

	onPressMenuItem = (path, patronId, libraryUrl) => {
		this.props.navigation.navigate(path, {libraryUrl: libraryUrl, patronId: patronId});
	};


	openWebsite = async (url) => {
		WebBrowser.openBrowserAsync(url);
	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

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

		return (
			<Box flex={1} safeArea={3}>
				<FlatList
					data={this.state.defaultMenuItems}
					renderItem={({item}) => this.renderItem(item, user.id, library.baseUrl, discoveryVersion)}
					keyExtractor={(item, index) => index.toString()}
				/>
			</Box>
		)
	}
}