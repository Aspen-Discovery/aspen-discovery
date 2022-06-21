import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import {translate} from "../../translations/translations";
import MyAccount from "../../screens/MyAccount/MyAccount";
import CheckedOut from "../../screens/MyAccount/CheckedOut";
import Holds from "../../screens/MyAccount/Holds";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import Settings_HomeScreen from "../../screens/MyAccount/Settings/HomeScreen";
import LinkedAccounts from "../../screens/MyAccount/Settings/LinkedAccounts/LinkedAccounts";
import AppHeader from "../AppHeader";
import Profile from "../../screens/MyAccount/Profile/Profile";
import Preferences from "../../screens/MyAccount/Settings/Preferences";


const AccountStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="AccountScreen"
			screenOptions={{
				headerShown: true,
				backBehavior: "history",
				headerBackTitleVisible: false,
			}}
		>
			<Stack.Screen
				name="AccountScreen"
				component={MyAccount}
				options={{ title: translate('user_profile.title') }}
			/>
			<Stack.Screen
				name="ProfileScreen"
				component={Profile}
				options={{ title: "Profile" }}
			/>
			<Stack.Screen
				name="Preferences"
				component={Preferences}
				options={{ title: "Preferences" }}
			/>
			<Stack.Screen
				name="CheckedOut"
				component={CheckedOut}
				options={{ title: translate('checkouts.title') }}
			/>
			<Stack.Screen
				name="Holds"
				component={Holds}
				options={{ title: translate('holds.title') }}
			/>
			<Stack.Screen
				name="GroupedWork"
				component={GroupedWork}
				options={{ title: translate('grouped_work.title') }}
			/>
			<Stack.Screen
				name="SettingsHomeScreen"
				component={Settings_HomeScreen}
				options={{ title: translate('user_profile.home_screen_settings') }}
			/>
			<Stack.Screen
				name="LinkedAccounts"
				component={LinkedAccounts}
				options={{ title: "Linked Accounts" }}
			/>
		</Stack.Navigator>
	)
}

export default AccountStackNavigator;