import React, {Component} from "react";
import {Box, Divider, HStack, Pressable, Button, Text, Heading, FlatList, Avatar} from "native-base";
import * as WebBrowser from 'expo-web-browser';

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
			profile: global.patronProfile,
			defaultMenuItems: [
				{
					key: '3',
					title: translate('user_profile.home_screen_settings'),
					path: 'SettingsHomeScreen',
					external: false,
				}
			]
		};

	}

	renderItem = (item, patronId, libraryUrl) => {
		if (item.external) {
			return (
				<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => {
					this.openWebsite(item.path)
				}}>
					<HStack space={3}>
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "lg", lg: "xl"}}>{item.title}</Text>
					</HStack>
				</Pressable>
			);
		} else {
			return (
				<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => {
					this.onPressMenuItem(item.path, patronId, libraryUrl)
				}}>
					<HStack>
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "lg", lg: "xl"}}>{item.title}</Text>
					</HStack>
				</Pressable>
			);
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

		return (
			<Box flex={1} safeArea={5}>
				<FlatList
					data={this.state.defaultMenuItems}
					renderItem={({item}) => this.renderItem(item, user.id, library.baseUrl)}
					keyExtractor={(item, index) => index.toString()}
				/>
			</Box>
		)
	}
}