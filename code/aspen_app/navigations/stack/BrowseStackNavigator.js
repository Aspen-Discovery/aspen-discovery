import React from "react";
import { createStackNavigator } from '@react-navigation/stack';
import BrowseCategoryHome from "../../screens/BrowseCategory/Home";
import {translate} from "../../util/translations";
import GroupedWork from "../../screens/GroupedWork/GroupedWork";
import OpenAccountDrawer from "../AppHeader";


const BrowseStackNavigator = () => {
	const Stack = createStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="HomeScreen"
			screenOptions={{
				headerShown: true,
				headerBackTitle: ""
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
		</Stack.Navigator>
	)
}

export default BrowseStackNavigator;