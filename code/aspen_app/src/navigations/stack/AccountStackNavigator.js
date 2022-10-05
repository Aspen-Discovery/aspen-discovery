import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import {translate} from "../../translations/translations";
import CheckedOut from "../../screens/MyAccount/CheckedOut";
import Holds from "../../screens/MyAccount/Holds";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import Settings_HomeScreen from "../../screens/MyAccount/Settings/HomeScreen";
import Settings_Notifications from "../../screens/MyAccount/Settings/Notifications";
import LinkedAccounts from "../../screens/MyAccount/Settings/LinkedAccounts/LinkedAccounts";
import Profile from "../../screens/MyAccount/Profile/Profile";
import Preferences from "../../screens/MyAccount/Settings/Preferences";
import MyLists from "../../screens/MyAccount/Lists/MyLists";
import MyList from "../../screens/MyAccount/Lists/MyList";
import MySavedSearches from "../../screens/MyAccount/SavedSearches/MySavedSearches";
import SavedSearchScreen from "../../screens/MyAccount/SavedSearches/MySavedSearch";
import LoadSavedSearch from "../../screens/MyAccount/SavedSearches/LoadSavedSearch";

const AccountStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="ProfileScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitleVisible: false,
			}}
		>
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
				name="SettingsHomeScreen"
				component={Settings_HomeScreen}
				options={{ title: translate('user_profile.home_screen_settings') }}
			/>
			<Stack.Screen
				name="SettingsNotifications"
				component={Settings_Notifications}
				options={{ title: translate('user_profile.notification_settings') }}
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
				name="LinkedAccounts"
				component={LinkedAccounts}
				options={{ title: "Linked Accounts" }}
			/>
			<Stack.Screen
				name="Lists"
				component={MyLists}
				options={{ title: "Lists" }}
			/>
			<Stack.Screen
				name="List"
				component={MyList}
				options={({ route }) => ({ title: route.params.title })}
			/>
			<Stack.Screen
				name="SavedSearches"
				component={MySavedSearches}
				options={{ title: "Saved Searches" }}
			/>
			<Stack.Screen
				name="SavedSearch"
				component={SavedSearchScreen}
				options={({ route }) => ({ title: route.params.title })}
			/>
			<Stack.Screen
				name="ItemDetails"
				component={GroupedWork}
				options={{
					title: translate('grouped_work.title') ,
				}}
			/>
			<Stack.Screen
				name="LoadSavedSearch"
				component={LoadSavedSearch}
				options={({ route }) => ({ title: route.params.name })}
			/>
		</Stack.Navigator>
	)
}

export default AccountStackNavigator;