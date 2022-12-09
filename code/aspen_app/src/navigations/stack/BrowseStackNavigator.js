import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';

import BrowseCategoryHome, { DiscoverHomeScreen } from '../../screens/BrowseCategory/Home';
import CreateVDXRequest from '../../screens/GroupedWork/CreateVDXRequest';
import GroupedWork from '../../screens/GroupedWork/GroupedWork';
import Results from '../../screens/Search/Results';
import SearchByCategory from '../../screens/Search/SearchByCategory';
import SearchByList from '../../screens/Search/SearchByList';
import SearchBySavedSearch from '../../screens/Search/SearchBySavedSearch';
import { translate } from '../../translations/translations';
import { BrowseCategoryContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';

const BrowseStackNavigator = () => {
     const Stack = createStackNavigator();
     return (
          <Stack.Navigator
               id="BrowseStack"
               initialRouteName="HomeScreen"
               screenOptions={({ navigation, route }) => ({
                    headerShown: true,
                    headerBackTitleVisible: false,
                    headerLeft: () => {
                         if (route.name !== 'HomeScreen') {
                              return (
                                   <Pressable onPress={() => navigation.goBack()}>
                                        <ChevronLeftIcon color="primary.baseContrast" />
                                   </Pressable>
                              );
                         } else {
                              return null;
                         }
                    },
               })}>
               <Stack.Screen
                    name="HomeScreen"
                    component={DiscoverHomeScreen}
                    options={{
                         title: translate('navigation.home'),
                    }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                         locationContext: JSON.stringify(React.useContext(LibraryBranchContext)),
                         userContext: JSON.stringify(React.useContext(UserContext)),
                         browseCategoriesContext: JSON.stringify(React.useContext(BrowseCategoryContext)),
                    }}
               />
               <Stack.Screen
                    name="GroupedWorkScreen"
                    component={GroupedWork}
                    options={({ route }) => ({
                         title: route.params.title ?? translate('grouped_work.title'),
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
                    name="CreateVDXRequest"
                    component={CreateVDXRequest}
                    options={{
                         title: 'Request Title',
                         presentation: 'modal',
                    }}
               />
          </Stack.Navigator>
     );
};

export default BrowseStackNavigator;