import React, {useState} from "react";
import { createDrawerNavigator } from "@react-navigation/drawer";
import { useToken, useColorModeValue } from 'native-base';

import {DrawerContent} from "./DrawerContent";
import TabNavigator from "../tab/TabNavigator";
import LoadingScreen from "../../screens/Auth/Loading";

const Drawer = createDrawerNavigator();

function AccountDrawer() {
	const screenBackgroundColor = useToken("colors", useColorModeValue("warmGray.50", "coolGray.800"));

	return (
		<Drawer.Navigator
			initialRouteName="LoadingScreen"
			screenOptions={{
				drawerType: "front",
				drawerHideStatusBarOnOpen: true,
				drawerPosition: "left",
				headerShown: false,
				backBehavior: "none",
				drawerStyle: {
					backgroundColor: screenBackgroundColor,
				},
			}}
			drawerContent={(props) => <DrawerContent {...props} />}
		>
			<Drawer.Screen
				name="Tabs"
				component={TabNavigator}
				screenOptions={{
					headerShown: false
				}}
			/>
			<Drawer.Screen
				name="LoadingScreen"
				component={LoadingScreen}
				options={{animationEnabled: false, header: () => null}}
			/>
		</Drawer.Navigator>
	)
}

export default AccountDrawer;