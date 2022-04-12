import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import {translate} from "../../translations/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import Search from "../../screens/Search/Search";
import Results from "../../screens/Search/Results";
import AppHeader from "../AppHeader";

const SearchStackNavigator = ({ options, route, back, navigation }) => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="SearchScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitleVisible: false,
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