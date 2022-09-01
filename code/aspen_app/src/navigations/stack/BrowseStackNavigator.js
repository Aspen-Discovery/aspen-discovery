import React from "react";
import { createStackNavigator } from '@react-navigation/stack';
import BrowseCategoryHome from "../../screens/BrowseCategory/Home";
import {translate} from "../../translations/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import SearchByCategory from "../../screens/Search/SearchByCategory";
import SearchByList from "../../screens/Search/SearchByList";
import SearchBySavedSearch from "../../screens/Search/SearchBySavedSearch";
import Results from "../../screens/Search/Results";


const BrowseStackNavigator = () => {
	const Stack = createStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="HomeScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitleVisible: false,
				animation: 'none',
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
					title: translate('grouped_work.title'),
				}}
			/>
			<Stack.Screen
				name="SearchByCategory"
				component={SearchByCategory}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.categoryLabel,
				})}
			/>
			<Stack.Screen
				name="SearchByAuthor"
				component={Results}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.searchTerm,
				})}
			/>
			<Stack.Screen
				name="SearchByList"
				component={SearchByList}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.categoryLabel,
				})}
			/>
			<Stack.Screen
				name="ListResults"
				component={SearchByList}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.categoryLabel,
				})}
			/>
			<Stack.Screen
				name="SearchBySavedSearch"
				component={SearchBySavedSearch}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.categoryLabel,
				})}
			/>
		</Stack.Navigator>
	)
}

export default BrowseStackNavigator;