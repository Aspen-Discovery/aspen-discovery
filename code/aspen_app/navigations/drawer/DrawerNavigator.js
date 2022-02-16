import React from "react";
import { getFocusedRouteNameFromRoute } from '@react-navigation/native';
import { createDrawerNavigator } from "@react-navigation/drawer";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useToken, useContrastText, useColorModeValue } from 'native-base';

import CustomDrawerContent from "./DrawerContent";
import AccountStackNavigator from "../stack/AccountStackNavigator";

import TabNavigator from "../tab/TabNavigator";
import {translate} from "../../util/translations";

const Drawer = createDrawerNavigator();
const Stack = createNativeStackNavigator();

function AccountDrawer() {
	const screenBackgroundColor = useToken("colors", useColorModeValue("warmGray.50", "coolGray.800"))


	return (
		<Drawer.Navigator
			initialRouteName="Tabs"
			hideStatusBar
			backBehavior="history"
			screenOptions={{
				drawerType: "front",
				drawerStyle: {
					backgroundColor: screenBackgroundColor,
				},
			}}
			options={({ route }) => ({
				title: route.params.name,
			})}
			drawerContent={(props) => <CustomDrawerContent {...props} />}
		>
			<Stack.Screen
				name="Tabs"
				component={TabNavigator}
				screenOptions={{
					headerShown: false
				}}
			/>
		</Drawer.Navigator>
	)
}

function getHeaderTitle(route) {
	// If the focused route is not found, we need to assume it's the initial screen
	// This can happen during if there hasn't been any navigation inside the screen
	// In our case, it's "Feed" as that's the first screen inside the navigator
	const routeName = getFocusedRouteNameFromRoute(route) ?? 'HomeScreen';
	switch (routeName) {
		case 'HomeScreen':
			return translate('navigation.home');
		case 'GroupedWorkScreen':
			return translate('grouped_work.title');
		case 'Search':
			return translate('navigation.search');
		case 'AccountScreen':
			return translate('navigation.account');
		case 'More':
			return translate('navigation.more');
	}
}

export default AccountDrawer;