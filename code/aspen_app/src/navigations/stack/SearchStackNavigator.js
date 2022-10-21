import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import {translate} from "../../translations/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import Search from "../../screens/Search/Search";
import Results from "../../screens/Search/Results";
import Filters from "../../screens/Search/Filters";
import Facet from "../../screens/Search/Facet";
import {CloseIcon} from "native-base";

const SearchStackNavigator = ({ options, route, back, navigation }) => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="SearchScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitleVisible: false,
			}}
			id="SearchNavigator"
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
					title: translate('search.search_results_title') + route.params.term,
				})}
			/>
			<Stack.Screen
				name="modal"
				component={FilterModal}
				options={{
					headerShown: false,
					presentation: "modal"
				}}
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

const FilterModalStack = createNativeStackNavigator();
const FilterModal = () => {
	return (
		<FilterModalStack.Navigator
			id="SearchFilters"
			screenOptions={({ navigation, route }) => ({
				headerShown: false,
				animationTypeForReplace: "push",
				headerRight: () => (
					<CloseIcon color="primary.baseContrast" onPress={()=> navigation.getParent().pop()}/>
				),
			})}>
			<FilterModalStack.Screen
				name="Filters"
				component={Filters}
				options={{
					title: "Filters",
					headerShown: true,
					presentation: "modal"
				}}
			/>
			<FilterModalStack.Screen
				name="Facet"
				component={Facet}
				options={({ route }) => ({
					title: route.params.title,
					headerShown: true,
					presentation: "card"
				})}
			/>
		</FilterModalStack.Navigator>
	)
}

export default SearchStackNavigator;