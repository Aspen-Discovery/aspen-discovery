import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import More from "../../screens/More/More";
import Contact from "../../screens/Library/Contact";
import {translate} from "../../translations/translations";
import AppHeader from "../AppHeader";


const MoreStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="More"
			screenOptions={{
				headerShown: true,
				backBehavior: "history",
				headerBackTitleVisible: false,
			}}
		>
			<Stack.Screen name="More" component={More} options={{ title: translate('navigation.more') }} />
			<Stack.Screen name="Contact" component={Contact} options={{ title: translate('general.contact') }} />
		</Stack.Navigator>
	)
}

export default MoreStackNavigator;