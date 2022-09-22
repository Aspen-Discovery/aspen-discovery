import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import {Box, HStack, Switch, Text, ScrollView, Button, FlatList} from "native-base";
import * as Notifications from 'expo-notifications';
import _ from "lodash";

// custom components and helper files
import {loadError} from "../../../components/loadError";
import {userContext} from "../../../context/user";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {
	deletePushToken, getNotificationPreference,
	getNotificationPreferences,
	getPushToken,
	registerForPushNotificationsAsync, savePushToken, setNotificationPreference
} from "../../../components/Notifications";
import {translate} from "../../../translations/translations";

export default class Settings_Notifications extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			pushToken: this.props.route.params?.pushToken ?? null,
			categories: {
				notifySavedSearch: {
					id: 3,
					label: 'Saved searches',
					option: 'notifySavedSearch',
					description: null,
					allow: false,
				},
				notifyCustom: {
					id: 2,
					label: 'Alerts from your library',
					option: 'notifyCustom',
					description: null,
					allow: false,
				},
			},
			unableToNotify: false,
		};
		this.getSavedPreferences = this.getSavedPreferences.bind(this);
		this.getSavedPreferencesForDevice();
	}

	componentDidMount = async () => {
		//await AsyncStorage.getItem('@pushToken')

		this.setState({
			isLoading: false,
		})

		let notificationStorage = await AsyncStorage.getItem('@notifications');
		if(notificationStorage) {
			notificationStorage = JSON.parse(notificationStorage);
		} else {
			notificationStorage = [];
		}

		//console.log(notificationStorage);
	}

	getSavedPreferencesForDevice = () => {
		if (Constants.isDevice) {
			const deviceToken = this.context.pushToken;
			const user = this.context.user;
			const notificationPreferences = user.notification_preferences ?? null;

			if(notificationPreferences && deviceToken) {
				const devicePreferences = _.filter(notificationPreferences, ['token', deviceToken]);
				if(devicePreferences && devicePreferences.length === 1) {
					this.state = {
						...this.state,
						allowNotifications: true,
						token: deviceToken,
						categories: {
							...this.state.categories,
							notifySavedSearch: {
								...this.state.categories.notifySavedSearch,
								allow: devicePreferences[0]['notifySavedSearch'] === "1",
							},
							notifyCustom: {
								...this.state.categories.notifyCustom,
								allow: devicePreferences[0]['notifyCustom'] === "1"
							},
						},
					};
				} else {
					this.state = {
						...this.state,
						allowNotifications: false,
						unableToNotify: true,
						token: null,
					};
				}
				//
				//if found, set state to match
				//if not found, set state to default
			}
		} else {
			this.state = {
				...this.state,
				allowNotifications: false,
				unableToNotify: true,
				token: null,
			};
		}
	}

	getSavedPreferences = async (token, libraryUrl) => {
		const route = this.props;
		const savedPreferences = route.params?.user.notification_preferences ?? null;
		if(savedPreferences) {
			// do something with them!
		}

		let currentPreferences = this.state.categories;
		currentPreferences = Object.keys(currentPreferences);
		for await(const pref of currentPreferences) {
			let savedValue = await getNotificationPreference(libraryUrl, token, pref)
			if(savedValue) {
				this.setState({
					categories: {
						...this.state.categories,
						[pref]: {
							...this.state.categories[pref],
							allow: savedValue.allow,
						}
					}
				})
			}
		}
	}

	updatePreference = async (option, newValue) => {
		let token = this.context.pushToken;
		if(token) {
			let updatedValue = await setNotificationPreference(this.context.library.baseUrl, token, option, newValue);
			this.setState({
				categories: {
					...this.state.categories,
					[option]: {
						...this.state.categories[option],
						allow: newValue,
					}
				}
			})

			this.updateContext(option, newValue);

		}
	}

	updateContext = (option, newValue) => {
		const deviceToken = this.context.pushToken;
		const user = this.context.user;
		const notificationPreferences = user.notification_preferences ?? null;

		let value = "0";
		if(newValue === true || newValue === "true") {
			value = "1";
		}

		if(notificationPreferences && deviceToken) {
			const i = _.findIndex(notificationPreferences, ['token', deviceToken]);
			_.set(this.context.user.notification_preferences[i], option, value);
		}
	}

	renderItem = (item) => {
		//console.log(item);
		return (
			<HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
				<Text>{item.label}</Text>
				<Switch onToggle={() => this.updatePreference(item.option, !item.allow)} isChecked={item.allow} />
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

	handleOptionToggle = (option) => {

	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const pushToken = this.context.pushToken;

		if(this.state.isLoading === true) {
			return(loadingSpinner());
		}

		return (
			<ScrollView>
				<Box flex={1} safeArea={5}>
					<HStack space={3} pb={5} alignItems="center" justifyContent="space-between">
						<Text bold>{translate('user_profile.allow_notifications')}</Text>
						<Switch onToggle={() => this.handleToggle} isChecked={this.state.allowNotifications} isDisabled={this.state.unableToNotify}/>
					</HStack>
					{this.state.allowNotifications ? (
						<FlatList
							data={Object.keys(this.state.categories)}
							renderItem={({ item }) => this.renderItem(this.state.categories[item])}
						/>
					) : null}
				</Box>
			</ScrollView>
		)
	}
}