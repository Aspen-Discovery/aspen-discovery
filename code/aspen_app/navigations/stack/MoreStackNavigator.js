import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import More from "../../screens/More";
import Contact from "../../screens/Library/Contact";
import {translate} from "../../util/translations";
import OpenAccountDrawer from "../AppHeader";


const MoreStackNavigator = () => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			initialRouteName="More"
			screenOptions={{
				headerShown: true
			}}
			options={{
				title: translate('navigation.more'),
				headerBackTitle: ""
			}}
		>
			<Stack.Screen name="More" component={More} />
			<Stack.Screen name="Contact" component={Contact} />
		</Stack.Navigator>
	)
}

export default MoreStackNavigator;