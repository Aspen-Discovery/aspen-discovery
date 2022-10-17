import React from "react";
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import Filters from "../../screens/Search/Filters";
import Facet from "../../screens/Search/Facet";

const FacetStackNavigator = ({ options, route, back, navigation }) => {
	const Stack = createNativeStackNavigator();
	return (
		<Stack.Navigator
			id="FacetStackNavigator"
			initialRouteName="Filters"
			screenOptions={{
				headerShown: false,
				headerBackTitleVisible: false,
			}}
		>
			<Stack.Group screenOptions={{ presentation: 'modal' }}>
				<Stack.Screen
					name="Filters"
					component={Filters}
				/>
				<Stack.Screen
					name="Facet"
					component={Facet}
					options={({ route }) => ({
						title: route.params.title,
					})}
				/>
			</Stack.Group>
		</Stack.Navigator>
	)
}

export default FacetStackNavigator;
