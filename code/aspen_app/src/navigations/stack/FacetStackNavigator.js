import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import Facet from '../../screens/Search/Facet';
import { SearchIndexScreen } from '../../screens/Search/Facets/SearchIndex';
import { SearchSourceScreen } from '../../screens/Search/Facets/SearchSource';
import Filters from '../../screens/Search/Filters';
import { getTermFromDictionary } from '../../translations/TranslationService';

const FacetStackNavigator = ({ options, route, back, navigation }) => {
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               id="FacetStackNavigator"
               initialRouteName="Filters"
               screenOptions={{
                    headerShown: false,
                    headerBackTitleVisible: false,
                    gestureEnabled: false,
               }}>
               <Stack.Group screenOptions={{ presentation: 'modal' }}>
                    <Stack.Screen name="Filters" component={Filters} />
                    <Stack.Screen
                         name="Facet"
                         component={Facet}
                         options={({ route }) => ({
                              title: route.params.title,
                         })}
                    />
                    <Stack.Screen
                         name="SearchSource"
                         component={SearchSourceScreen}
                         options={{
                              title: getTermFromDictionary(language, 'search_in'),
                         }}
                    />
                    <Stack.Screen
                         name="SearchIndex"
                         component={SearchIndexScreen}
                         options={{
                              title: getTermFromDictionary(language, 'search_by'),
                         }}
                    />
               </Stack.Group>
          </Stack.Navigator>
     );
};

export default FacetStackNavigator;