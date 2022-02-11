import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { DrawerActions } from '@react-navigation/native';
import { Button, IconButton } from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";


import {translate} from "../../util/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import Search from "../../screens/Search/Search";
import Results from "../../screens/Search/Results";
import OpenAccountDrawer from "../AppHeader";

const SearchStackNavigator = ({ options, route, back, navigation }) => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="SearchScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitle: ""
			}}
		>
			<Stack.Screen
				name="SearchScreen"
				component={Search}
				options={{
					title: translate('search.title'),
				}}
			/>
			<Stack.Screen
				name="SearchResults"
				component={Results}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.searchTerm,
				})}
			/>
			<Stack.Screen
				name="GroupedWork"
				component={GroupedWork}
				options={{
					title: translate('grouped_work.title'),
				}}
			/>
		</Stack.Navigator>
	)
}

export default SearchStackNavigator;