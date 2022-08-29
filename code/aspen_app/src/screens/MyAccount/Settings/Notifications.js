import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import {Box, HStack, Switch, Text, ScrollView, Button, SectionList} from "native-base";
import * as Notifications from 'expo-notifications';
import _ from "lodash";

// custom components and helper files
import {loadError} from "../../../components/loadError";
import {userContext} from "../../../context/user";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {deletePushToken, getPushToken, registerForPushNotificationsAsync} from "../../../components/Notifications";
import {translate} from "../../../translations/translations";

export default class Settings_Notifications extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			notificationTypes: [
				{
					category: 'Updates',
					data: [
						{
							id: 'saved_search',
							label: 'Saved searches'
						}
					]
				}
			],
			allowNotifications: false,
			unableToNotify: false,
		}
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		})

		if (Constants.isDevice) {
			let expoToken = (await Notifications.getExpoPushTokenAsync()).data;
			let token = (await getPushToken(this.context.library.baseUrl));
			// get tokens from Discovery and look for token in array
			if(token){
				token = _.values(token);
				if (token.includes(expoToken)) {
					console.log("Found matching token.");
					this.setState({
						allowNotifications: true,
					})
				} else {
					console.log("Unable to find matching token.");
					if(expoToken) {
						await deletePushToken(this.context.library.baseUrl, expoToken);
						console.log("Removed from database.")
					}
				}
			}
		} else {
			this.setState({
				unableToNotify: true
			})
		}

		let notificationStorage = await AsyncStorage.getItem('@notifications');
		if(notificationStorage) {
			notificationStorage = JSON.parse(notificationStorage);
		} else {
			notificationStorage = [];
		}

		//console.log(notificationStorage);
	}

	renderItem = ({id, label}) => {
		return (
			<HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
				<Text>{label}</Text>
				<Switch />
			</HStack>
		)
	}

	renderHeader = () => {
		const libraryUrl = this.context.library.baseUrl;
		return (
			<HStack space={3} pb={5} alignItems="center" justifyContent="space-between">
				<Text bold>{translate('user_profile.allow_notifications')}</Text>
				<Switch isChecked={this.state.allowNotifications} isDisabled={this.state.unableToNotify}/>
			</HStack>
		)
	}

	handleToggle = () => {
		this.setState({
			allowNotifications: !this.state.allowNotifications,
		}, async () => {
			if (this.state.allowNotifications === true) {
				await registerForPushNotificationsAsync(this.context.library.baseUrl);
			} else {
				let token = (await Notifications.getExpoPushTokenAsync()).data;
				await deletePushToken(this.context.library.baseUrl, token, true)
			}
		})
	}
	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if(this.state.isLoading === true) {
			return(loadingSpinner());
		}

		return (
			<ScrollView>
				<Box flex={1} safeArea={5}>
					<HStack space={3} pb={5} alignItems="center" justifyContent="space-between">
						<Text bold>{translate('user_profile.allow_notifications')}</Text>
						<Switch onToggle={this.handleToggle} isChecked={this.state.allowNotifications} isDisabled={this.state.unableToNotify}/>
					</HStack>
					{/*<SectionList
						sections={this.state.notificationTypes}
						keyExtractor={(item, index) => item + index}
						renderItem={({ item }) => this.renderItem(item)}
						ListHeaderComponent={this.renderHeader()}
						renderSectionHeader={({ section: { category } }) => (
							<Text bold>{category}</Text>
						)}
					/>*/}
				</Box>
			</ScrollView>
		)
	}
}