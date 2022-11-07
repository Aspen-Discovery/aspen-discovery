import React, {Component} from 'react';
import {Box, Center, FlatList, HStack, Pressable, Text} from 'native-base';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from '../../components/loadingSpinner';
import {loadError} from '../../components/loadError';
import {GLOBALS} from '../../util/globals';
import {userContext} from '../../context/user';

export default class More extends Component {
	static contextType = userContext;

	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = async () => {
		let privacyPolicy;

		try {
			let tmp = await AsyncStorage.getItem('@appSettings');
			let appSettings = JSON.parse(tmp);
			privacyPolicy = appSettings.privacyPolicy;
		} catch (e) {
			console.log(e);
		}

		this.setState({
			defaultMenuItems: [
				{
					key: '0',
					title: translate('general.contact'),
					path: 'Contact',
					external: false,
				},
				{
					key: '1',
					title: translate('general.privacy_policy'),
					path: privacyPolicy,
					external: true,
				},
			],
			isLoading: false,
		});
	};

	renderNativeItem = (item) => {
		if (item.external) {
			return (
					<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => {
						this.openWebsite(item.path);
					}}>
						<HStack space={3}>
							<Text _dark={{color: 'warmGray.50'}} color="coolGray.800" bold fontSize={{base: 'lg', lg: 'xl'}}>{item.title}</Text>
						</HStack>
					</Pressable>
			);
		} else {
			return (
					<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => {
						this.onPressMenuItem(item.path);
					}}>
						<HStack>
							<Text _dark={{color: 'warmGray.50'}} color="coolGray.800" bold fontSize={{base: 'lg', lg: 'xl'}}>{item.title}</Text>
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
	};

	render() {
		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		return (
				<Box>
					<FlatList
							data={this.state.defaultMenuItems}
							renderItem={({item}) => this.renderNativeItem(item)}
							keyExtractor={(item, index) => index.toString()}
					/>

					<Center mt={5}>
						<Text mt={10} fontSize="xs" bold>Aspen LiDA <Text color="coolGray.600" _dark={{color: 'warmGray.400'}}>{GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel}]</Text></Text>
						{library.discoveryVersion ? (<Text fontSize="xs" bold>Aspen Discovery <Text color="coolGray.600" _dark={{color: 'warmGray.400'}}>{library.discoveryVersion}</Text></Text>) : null}
					</Center>
				</Box>
		);
	}
}