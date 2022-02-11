import React from "react";
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useToken, useColorModeValue, IconButton } from 'native-base';
import {Ionicons, MaterialIcons} from "@expo/vector-icons";


import {translate} from "../../util/translations";

import BrowseStackNavigator from "../stack/BrowseStackNavigator";
import SearchStackNavigator from "../stack/SearchStackNavigator";
import LibraryCardStackNavigator from "../stack/LibraryCardStackNavigator";
import AccountStackNavigator from "../stack/AccountStackNavigator";
import MoreStackNavigator from "../stack/MoreStackNavigator";

export default function TabNavigator() {
	const Tab = createBottomTabNavigator();
	const [activeIcon, inactiveIcon] = useToken("colors", [useColorModeValue("gray.800", "coolGray.200"), useColorModeValue("gray.500", "coolGray.600")]);
	const tabBarBackgroundColor = useToken("colors", useColorModeValue("warmGray.100", "coolGray.900"));
	return (
		<Tab.Navigator
			initialRouteName="HomeTab"
			screenOptions={({ route }) => ({
				headerShown: false,
				tabBarHideOnKeyboard: true,
				tabBarIcon: ({ focused, color, size }) => {
					let iconName;
					if(route.name === 'HomeTab') {
						iconName = focused ? 'library' : 'library-outline';
					} else if (route.name === 'SearchTab') {
						iconName = focused ? 'search' : 'search-outline';
					} else if (route.name === 'LibraryCardTab') {
						iconName = focused ? 'card' : 'card-outline';
					} else if (route.name === 'AccountTab') {
						iconName = focused ? 'person' : 'person-outline';
					} else if (route.name === 'MoreTab') {
						iconName = focused ? 'ellipsis-horizontal' : 'ellipsis-horizontal-outline';
					}
					return <Ionicons name={iconName} size={size} color={color} />;
				},
				tabBarActiveTintColor: activeIcon,
				tabBarInactiveTintColor: inactiveIcon,
				tabBarLabelStyle: {
					fontWeight: '400'
				},
				tabBarStyle: {
					backgroundColor: tabBarBackgroundColor
				},
			})}
		>
			<Tab.Screen
				name="HomeTab"
				component={BrowseStackNavigator}
				options={{
					tabBarLabel: translate('navigation.home'),
					unmountOnBlur: true,
				}}
				screenOptions={{
					headerShown: false,
				}}
			/>
			<Tab.Screen
				name="SearchTab"
				component={SearchStackNavigator}
				options={{
					tabBarLabel: translate('navigation.search'),
				}}
			/>
			<Tab.Screen
				name="LibraryCardTab"
				component={LibraryCardStackNavigator}
				options={{
					tabBarLabel: translate('navigation.library_card'),
				}}
			/>
			<Tab.Screen
				name="AccountTab"
				component={AccountStackNavigator}
				options={{
					tabBarLabel: translate('navigation.account'),
				}}
			/>
			<Tab.Screen
				name="MoreTab"
				component={MoreStackNavigator}
				options={{
					tabBarLabel: translate('navigation.more'),
				}}
			/>
		</Tab.Navigator>
	)
}