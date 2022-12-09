import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createStackNavigator } from '@react-navigation/stack';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';
import React from 'react';
import { enableScreens } from 'react-native-screens';

import GroupedWork from '../../screens/GroupedWork/GroupedWork';
import Facet from '../../screens/Search/Facet';
import { FiltersScreen } from '../../screens/Search/Filters';
import Results from '../../screens/Search/Results';
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import Search from '../../screens/Search/Search';
import { SearchResults } from '../../screens/Search/SearchResults';
import SearchByCategory from '../../screens/Search/SearchByCategory';
import SearchByList from '../../screens/Search/SearchByList';
import SearchBySavedSearch from '../../screens/Search/SearchBySavedSearch';

enableScreens();

const SearchStackNavigator = ({ options, route, back, navigation }) => {
     const Stack = createStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="SearchScreen"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}
               id="SearchNavigator">
               <Stack.Screen
                    name="SearchScreen"
                    component={Search}
                    options={{
                         title: translate('search.title'),
                    }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                         locationContext: JSON.stringify(React.useContext(LibraryBranchContext)),
                         userContext: JSON.stringify(React.useContext(UserContext)),
                    }}
               />
               <Stack.Screen
                    name="SearchResults"
                    component={SearchResults}
                    options={({ route }) => ({
                         title: translate('search.search_results_title') + route.params.term,
                         params: {
                              pendingParams: [],
                         },
                    })}
               />
               <Stack.Screen
                    name="SearchByCategory"
                    component={SearchByCategory}
                    options={({ route }) => ({
                         title: translate('search.search_results_title') + route.params.title,
                    })}
               />
               <Stack.Screen
                    name="SearchByList"
                    component={SearchByList}
                    options={({ route }) => ({
                         title: translate('search.search_results_title') + route.params.title,
                         libraryContext: React.useContext(LibrarySystemContext),
                         locationContext: React.useContext(LibraryBranchContext),
                         userContext: React.useContext(UserContext),
                    })}
               />
               <Stack.Screen
                    name="ListResults"
                    component={SearchByList}
                    options={({ route }) => ({
                         title: translate('search.search_results_title') + route.params.title,
                         libraryContext: React.useContext(LibrarySystemContext),
                         locationContext: React.useContext(LibraryBranchContext),
                         userContext: React.useContext(UserContext),
                    })}
               />
               <Stack.Screen
                    name="SearchBySavedSearch"
                    component={SearchBySavedSearch}
                    options={({ route }) => ({
                         title: translate('search.search_results_title') + route.params.title,
                         libraryContext: React.useContext(LibrarySystemContext),
                         locationContext: React.useContext(LibraryBranchContext),
                         userContext: React.useContext(UserContext),
                    })}
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
                    name="GroupedWork"
                    component={GroupedWork}
                    options={{
                         title: translate('grouped_work.title'),
                    }}
               />
          </Stack.Navigator>
     );
};

const FilterModalStack = createNativeStackNavigator();
const FilterModal = () => {
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
                                   <Pressable onPress={() => navigation.goBack()}>
                                        <ChevronLeftIcon color="primary.baseContrast" />
                                   </Pressable>
                              );
                         } else {
                              return null;
                         }
                    },
                    headerRight: () => <CloseIcon color="primary.baseContrast" onPress={() => navigation.getParent().pop()} />,
               })}>
               <FilterModalStack.Screen
                    name="Filters"
                    component={FiltersScreen}
                    options={{
                         title: 'Filters',
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
          </FilterModalStack.Navigator>
     );
};

export default SearchStackNavigator;