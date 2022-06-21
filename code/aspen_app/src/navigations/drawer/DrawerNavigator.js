import React from "react";
import { createDrawerNavigator } from "@react-navigation/drawer";
import { useToken, useColorModeValue } from 'native-base';

import {DrawerContent} from "./DrawerContent";
import TabNavigator from "../tab/TabNavigator";

const Drawer = createDrawerNavigator();

function AccountDrawer() {
	const screenBackgroundColor = useToken("colors", useColorModeValue("warmGray.50", "coolGray.800"))

	return (
		<Drawer.Navigator
			initialRouteName="Tabs"
			screenOptions={{
				drawerType: "front",
				drawerHideStatusBarOnOpen: true,
				drawerPosition: "left",
				headerShown: false,
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
		</Drawer.Navigator>
	)
}

export default AccountDrawer;