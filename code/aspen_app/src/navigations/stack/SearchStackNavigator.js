import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createStackNavigator } from '@react-navigation/stack';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';
import React from 'react';
import { enableScreens } from 'react-native-screens';

import {GroupedWork221200, GroupedWorkScreen} from '../../screens/GroupedWork/GroupedWork';
import Facet from '../../screens/Search/Facet';
import { FiltersScreen } from '../../screens/Search/Filters';
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import Search from '../../screens/Search/Search';
import { SearchResults } from '../../screens/Search/SearchResults';
import SearchByCategory from '../../screens/Search/SearchByCategory';
import {SearchResultsForList} from '../../screens/Search/SearchByList';
import SearchBySavedSearch from '../../screens/Search/SearchBySavedSearch';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import { EditionsModal } from './BrowseStackNavigator';
import {CreateVDXRequest} from '../../screens/GroupedWork/CreateVDXRequest';

enableScreens();

const SearchStackNavigator = ({ options, route, back, navigation }) => {
     const Stack = createStackNavigator();
     return (
          <Stack.Navigator
               id="SearchNavigator"
               initialRouteName="SearchScreen"
               screenOptions={({ navigation, route }) => ({
                   headerShown: true,
                   headerBackTitleVisible: false,

               })}>
               <Stack.Group>
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
                         name="ResultItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevRoute: 'SearchResults' }}
                    />
                   <Stack.Screen
                       name="ResultItem221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? translate('grouped_work.title'),
                       })}
                   />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="SearchByCategory"
                         component={SearchByCategory}
                         options={({ route }) => ({
                              title: translate('search.search_results_title') + route.params.title,
                         })}
                    />
                    <Stack.Screen
                         name="CategoryResultItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevRoute: 'SearchResults' }}
                    />
                   <Stack.Screen
                       name="CategoryResultItem221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? translate('grouped_work.title'),
                       })}
                   />
               </Stack.Group>

               <Stack.Group>
                    <Stack.Screen
                         name="SearchByList"
                         component={SearchResultsForList}
                         options={({ route }) => ({
                              title: translate('search.search_results_title') + route.params.title,
                         })}
                    />
                    <Stack.Screen
                         name="ListResults"
                         component={SearchResultsForList}
                         options={({ route }) => ({
                              title: translate('search.search_results_title') + route.params.title,
                         })}
                    />
                    <Stack.Screen
                         name="ListResultItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevRoute: 'SearchResults' }}
                    />
                   <Stack.Screen
                       name="ListResultItem221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? translate('grouped_work.title'),
                       })}
                   />
               </Stack.Group>
               <Stack.Group>
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
                         name="SavedSearchResultItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevRoute: 'SearchResults' }}
                    />
                   <Stack.Screen
                       name="SavedSearchResultItem221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? translate('grouped_work.title'),
                       })}
                   />
               </Stack.Group>
               <Stack.Screen
                    name="CopyDetails"
                    component={WhereIsIt}
                    options={({ navigation }) => ({
                         title: translate('copy_details.where_is_it'),
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
                      title: translate('ill.request_title'),
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