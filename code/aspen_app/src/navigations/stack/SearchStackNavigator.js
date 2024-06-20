import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createStackNavigator } from '@react-navigation/stack';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';
import React from 'react';
import { enableScreens } from 'react-native-screens';
import { LanguageContext } from '../../context/initialContext';
import { CreateVDXRequest } from '../../screens/GroupedWork/CreateVDXRequest';

import { GroupedWorkScreen } from '../../screens/GroupedWork/GroupedWork';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import Facet from '../../screens/Search/Facet';
import { SearchIndexScreen } from '../../screens/Search/Facets/SearchIndex';
import { SearchSourceScreen } from '../../screens/Search/Facets/SearchSource';
import { FiltersScreen } from '../../screens/Search/Filters';
import { SearchHome } from '../../screens/Search/Search';
import { BackIcon } from '../../themes/theme';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { EditionsModal } from './BrowseStackNavigator';

enableScreens();

const SearchStackNavigator = ({ options, route, back, navigation }) => {
     const { language } = React.useContext(LanguageContext);
     const Stack = createStackNavigator();
     return (
          <Stack.Navigator
               id="SearchNavigator"
               initialRouteName="SearchScreen"
               screenOptions={({ navigation, route }) => ({
                    headerShown: true,
                    headerBackTitleVisible: false,
                    gestureEnabled: false,
                    headerBackImage: () => <BackIcon />,
               })}>
               <Stack.Group>
                    <Stack.Screen
                         name="SearchScreen"
                         component={SearchHome}
                         options={{
                              title: getTermFromDictionary(language, 'search'),
                         }}
                    />
                    <Stack.Screen
                         name="ResultItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'SearchResults' }}
                    />
               </Stack.Group>

               <Stack.Screen
                    name="CopyDetails"
                    component={WhereIsIt}
                    options={({ navigation }) => ({
                         title: getTermFromDictionary(language, 'where_is_it'),
                         headerShown: true,
                         presentation: 'modal',
                         headerLeft: () => {
                              return null;
                         },
                         headerRight: () => (
                              <Pressable onPress={() => navigation.goBack()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                   <CloseIcon size={5} color="primary.baseContrast" />
                              </Pressable>
                         ),
                    })}
               />
               <Stack.Screen
                    name="EditionsModal"
                    component={EditionsModal}
                    options={{
                         headerShown: false,
                         presentation: 'modal',
                    }}
               />
               <Stack.Screen
                    name="modal"
                    component={FilterModal}
                    options={{
                         headerShown: false,
                         presentation: 'modal',
                    }}
               />
               <Stack.Screen
                    name="CreateVDXRequest"
                    component={CreateVDXRequest}
                    options={({ navigation }) => ({
                         title: getTermFromDictionary(language, 'ill_request_title'),
                         presentation: 'modal',
                         headerLeft: () => {
                              return <></>;
                         },
                         headerRight: () => (
                              <Pressable onPress={() => navigation.goBack()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                   <CloseIcon size={5} color="primary.baseContrast" />
                              </Pressable>
                         ),
                    })}
               />
          </Stack.Navigator>
     );
};

const FilterModalStack = createNativeStackNavigator();
const FilterModal = () => {
     const { language } = React.useContext(LanguageContext);
     return (
          <FilterModalStack.Navigator
               id="SearchFilters"
               screenOptions={({ navigation, route }) => ({
                    headerShown: false,
                    animationTypeForReplace: 'push',
                    gestureEnabled: false,
                    headerLeft: () => {
                         if (route.name !== 'Filters') {
                              return (
                                   <Pressable onPress={() => navigation.goBack()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                        <ChevronLeftIcon size={5} color="primary.baseContrast" />
                                   </Pressable>
                              );
                         } else {
                              return null;
                         }
                    },
                    headerRight: () => (
                         <Pressable onPress={() => navigation.getParent().pop()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                              <CloseIcon size={5} color="primary.baseContrast" />
                         </Pressable>
                    ),
               })}>
               <FilterModalStack.Screen
                    name="Filters"
                    component={FiltersScreen}
                    options={{
                         title: getTermFromDictionary(language, 'filters'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
               <FilterModalStack.Screen
                    name="Facet"
                    component={Facet}
                    options={({ route }) => ({
                         title: route.params.title,
                         headerShown: true,
                         presentation: 'card',
                    })}
               />
               <FilterModalStack.Screen
                    name="SearchSource"
                    component={SearchSourceScreen}
                    options={{
                         title: getTermFromDictionary(language, 'search_in'),
                    }}
               />
               <FilterModalStack.Screen
                    name="SearchIndex"
                    component={SearchIndexScreen}
                    options={{
                         title: getTermFromDictionary(language, 'search_by'),
                    }}
               />
          </FilterModalStack.Navigator>
     );
};

export default SearchStackNavigator;