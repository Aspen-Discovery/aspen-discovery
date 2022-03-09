import React, {Component} from "react";
import {Box, Divider, HStack, Pressable, Button, Text, Heading, FlatList, Avatar} from "native-base";
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {getProfile} from "../../../util/loadPatron";
import {translate} from "../../../translations/translations";

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

	renderItem = (item) => {
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
					this.onPressMenuItem(item.path)
				}}>
					<HStack>
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "lg", lg: "xl"}}>{item.title}</Text>
					</HStack>
				</Pressable>
			);
		}
	};

	onPressMenuItem = (item) => {
		this.props.navigation.navigate(item, {item});
	};


	openWebsite = async (url) => {
		WebBrowser.openBrowserAsync(url);
	}

	render() {
		return (
			<Box flex={1} safeArea={5}>
				<FlatList
					data={this.state.defaultMenuItems}
					renderItem={({item}) => this.renderItem(item)}
					keyExtractor={(item, index) => index.toString()}
				/>
			</Box>
		)
	}
}