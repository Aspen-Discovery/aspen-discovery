import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import LibraryCard from "../../screens/MyAccount/LibraryCard";
import {translate} from "../../util/translations";
import OpenAccountDrawer from "../AppHeader";

const LibraryCardStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="LibraryCard"
			screenOptions={{
				headerShown: true,
				headerBackTitle: ""
			}}
			options={{ title: translate('user_profile.library_card') }}
		>
			<Stack.Screen name="LibraryCard" component={LibraryCard} />
		</Stack.Navigator>
	)
}

export default LibraryCardStackNavigator;