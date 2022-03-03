import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import LibraryCard from "../../screens/MyAccount/MyLibraryCard/LibraryCard";
import {translate} from "../../translations/translations";
import AppHeader from "../AppHeader";

const LibraryCardStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="LibraryCard"
			screenOptions={{
				headerShown: true,
				backBehavior: "history",
				headerBackTitleVisible: false,
			}}
		>
			<Stack.Screen name="LibraryCard" component={LibraryCard} options={{ title: translate('user_profile.library_card') }} />
		</Stack.Navigator>
	)
}

export default LibraryCardStackNavigator;