import React from "react";
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {DrawerActions} from '@react-navigation/native';
import {useToken, useColorModeValue} from 'native-base';
import {Ionicons} from "@expo/vector-icons";

import {translate} from "../../translations/translations";

import BrowseStackNavigator from "../stack/BrowseStackNavigator";
import SearchStackNavigator from "../stack/SearchStackNavigator";
import LibraryCardStackNavigator from "../stack/LibraryCardStackNavigator";
import AccountStackNavigator from "../stack/AccountStackNavigator";
import MoreStackNavigator from "../stack/MoreStackNavigator";
import DrawerNavigator from "../drawer/DrawerNavigator";

export default function TabNavigator() {
	const Tab = createBottomTabNavigator();
	const [activeIcon, inactiveIcon] = useToken("colors", [useColorModeValue("gray.800", "coolGray.200"), useColorModeValue("gray.500", "coolGray.400")]);
	const tabBarBackgroundColor = useColorModeValue("light", "dark");
	return (
		<Tab.Navigator
			initialRouteName="HomeTab"
			screenOptions={({ route }) => ({
				headerShown: false,
				backBehavior: "none",
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
					backgroundColor: tabBarBackgroundColor,
					elevation: 0
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
				component={DrawerNavigator}
				options={{
					tabBarLabel: translate('navigation.account'),
				}}
				listeners={({navigation}) => ({
					tabPress: e => {
						navigation.dispatch(DrawerActions.toggleDrawer())
						e.preventDefault()
					}
				})}
			/>
			<Tab.Screen
				name="AccountScreenTab"
				component={AccountStackNavigator}
				options={{
					tabBarButton: () => null
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