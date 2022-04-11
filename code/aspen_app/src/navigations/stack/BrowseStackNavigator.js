import React from "react";
import { createStackNavigator } from '@react-navigation/stack';
import BrowseCategoryHome from "../../screens/BrowseCategory/Home";
import {translate} from "../../translations/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import AppHeader from "../AppHeader";
import SearchByCategory from "../../screens/BrowseCategory/SearchByCategory";


const BrowseStackNavigator = () => {
	const Stack = createStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="HomeScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitleVisible: false,
			}}
		>
			<Stack.Screen
				name="HomeScreen"
				component={BrowseCategoryHome}
				options={{
					title: translate('navigation.home'),
				}}
			/>
			<Stack.Screen
				name="GroupedWorkScreen"
				component={GroupedWork}
				options={{
					title: translate('grouped_work.title') ,
				}}
			/>
			<Stack.Screen
				name="SearchByCategory"
				component={SearchByCategory}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.categoryLabel,
				})}
			/>
		</Stack.Navigator>
	)
}

export default BrowseStackNavigator;