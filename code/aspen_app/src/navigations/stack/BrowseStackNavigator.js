import React from "react";
import { createStackNavigator } from '@react-navigation/stack';
import BrowseCategoryHome from "../../screens/BrowseCategory/Home";
import {translate} from "../../translations/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import SearchByCategory from "../../screens/Search/SearchByCategory";
import SearchByList from "../../screens/Search/SearchByList";
import SearchBySavedSearch from "../../screens/Search/SearchBySavedSearch";
import Results from "../../screens/Search/Results";
import CreateVDXRequest from "../../screens/GroupedWork/CreateVDXRequest";


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
				options={({ route }) => ({
					title: route.params.title ?? translate('grouped_work.title'),
				})}
			/>
			<Stack.Screen
				name="SearchByCategory"
				component={SearchByCategory}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.title,
				})}
			/>
			<Stack.Screen
				name="SearchByAuthor"
				component={Results}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.term,
				})}
			/>
			<Stack.Screen
				name="SearchByList"
				component={SearchByList}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.title,
				})}
			/>
			<Stack.Screen
				name="ListResults"
				component={SearchByList}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.title,
				})}
			/>
			<Stack.Screen
				name="SearchBySavedSearch"
				component={SearchBySavedSearch}
				options={({ route }) => ({
					title: translate('search.search_results_title') + route.params.title,
				})}
			/>
			<Stack.Screen
				name="CreateVDXRequest"
				component={CreateVDXRequest}
				options={{
					title: 'Request Title',
					presentation: 'modal',
				}}
			/>
		</Stack.Navigator>
	)
}

export default BrowseStackNavigator;