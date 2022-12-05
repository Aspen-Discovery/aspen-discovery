import { createNativeStackNavigator } from "@react-navigation/native-stack";
import React from "react";

import Facet from "../../screens/Search/Facet";
import Filters from "../../screens/Search/Filters";

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
      <Stack.Group screenOptions={{ presentation: "modal" }}>
        <Stack.Screen name="Filters" component={Filters} />
        <Stack.Screen
          name="Facet"
          component={Facet}
          options={({ route }) => ({
            title: route.params.title,
          })}
        />
      </Stack.Group>
    </Stack.Navigator>
  );
};

export default FacetStackNavigator;