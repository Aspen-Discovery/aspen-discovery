import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createStackNavigator } from '@react-navigation/stack';
import { ChevronLeftIcon, CloseIcon, Pressable, Icon } from 'native-base';
import React from 'react';
import Scanner from '../../components/Scanner';
import { LanguageContext, ThemeContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';

import { DiscoverHomeScreen } from '../../screens/BrowseCategory/Home';
import { EventScreen } from '../../screens/Event/Event';
import { CreateVDXRequest } from '../../screens/GroupedWork/CreateVDXRequest';
import { Editions } from '../../screens/GroupedWork/Editions';
import { GroupedWorkScreen } from '../../screens/GroupedWork/GroupedWork';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import Facet from '../../screens/Search/Facet';
import { SearchIndexScreen } from '../../screens/Search/Facets/SearchIndex';
import { SearchSourceScreen } from '../../screens/Search/Facets/SearchSource';
import { FiltersScreen } from '../../screens/Search/Filters';
import { SearchResultsForBrowseCategory } from '../../screens/Search/SearchByCategory';
import { SearchResultsForList } from '../../screens/Search/SearchByList';
import { SearchResultsForSavedSearch } from '../../screens/Search/SearchBySavedSearch';
import { SearchResults } from '../../screens/Search/SearchResults';
import { BackIcon } from '../../themes/theme';
import { getTermFromDictionary } from '../../translations/TranslationService';

const BrowseStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
     const Stack = createStackNavigator();
     return (
          <Stack.Navigator
               id="BrowseStack"
               initialRouteName="HomeScreen"
               screenOptions={({ navigation, route }) => ({
                    headerShown: true,
                    gestureEnabled: false,
                    headerBackTitleVisible: false,
                    headerBackImage: () => <BackIcon />,
               })}>
               <Stack.Screen
                    name="HomeScreen"
                    component={DiscoverHomeScreen}
                    options={{
                         title: getTermFromDictionary(language, 'nav_discover'),
                    }}
               />
               <Stack.Screen
                    name="GroupedWorkScreen"
                    component={GroupedWorkScreen}
                    options={({ route }) => ({
                         title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                    })}
                    initialParams={{ prevRoute: 'HomeScreen' }}
               />
               <Stack.Screen
                    name="CopyDetails"
                    component={WhereIsIt}
                    options={({ navigation }) => ({
                         title: getTermFromDictionary(language, 'where_is_it'),
                         headerShown: true,
                         presentation: 'modal',
                         headerLeft: () => {
                              return <></>;
                         },
                         headerRight: () => (
                              <Pressable onPress={() => navigation.goBack()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                   <CloseIcon color="primary.baseContrast" size={5} />
                              </Pressable>
                         ),
                    })}
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
                                   <CloseIcon color="primary.baseContrast" size={5} />
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
                    name="SearchByCategory"
                    component={SearchResultsForBrowseCategory}
                    options={({ route }) => ({
                         title: getTermFromDictionary(language, 'results_for') + ' ' + route.params.title,
                    })}
               />
               <Stack.Screen
                    name="CategoryResultItem"
                    component={GroupedWorkScreen}
                    options={({ route }) => ({
                         title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                    })}
                    initialParams={{ prevRoute: 'SearchResults' }}
               />
               <Stack.Screen
                    name="SearchByList"
                    component={SearchResultsForList}
                    options={({ route }) => ({
                         title: route.params?.title ? getTermFromDictionary(language, 'results_for') + ' ' + route.params.title : getTermFromDictionary(language, 'search_results'),
                    })}
               />
               <Stack.Screen
                    name="ListResults"
                    component={SearchResultsForList}
                    options={({ route }) => ({
                         title: route.params?.title ? getTermFromDictionary(language, 'results_for') + ' ' + route.params.title : getTermFromDictionary(language, 'search_results'),
                    })}
               />
               <Stack.Screen
                    name="ListResultItem"
                    component={GroupedWorkScreen}
                    options={({ route }) => ({
                         title: route.params?.title ?? getTermFromDictionary(language, 'item_details'),
                    })}
                    initialParams={{ prevRoute: 'SearchResults' }}
               />

               <Stack.Screen
                    name="SearchBySavedSearch"
                    component={SearchResultsForSavedSearch}
                    options={({ route }) => ({
                         title: getTermFromDictionary(language, 'results_for') + ' ' + route.params.title,
                    })}
               />
               <Stack.Screen
                    name="SavedSearchResultItem"
                    component={GroupedWorkScreen}
                    options={({ route }) => ({
                         title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                    })}
                    initialParams={{ prevRoute: 'SearchResults' }}
               />
               <Stack.Screen
                    name="SearchResults"
                    component={SearchResults}
                    options={({ route }) => ({
                         title: getTermFromDictionary(language, 'results_for') + ' ' + route.params.term,
                         params: {
                              pendingParams: [],
                         },
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
                    name="Scanner"
                    component={Scanner}
                    options={{
                         gestureEnabled: false,
                         presentation: 'modal',
                    }}
               />
               <Stack.Screen
                    name="EventScreen"
                    component={EventScreen}
                    options={({ route }) => ({
                         title: route.params.title ?? getTermFromDictionary(language, 'event_details'),
                    })}
                    initialParams={{ prevRoute: 'HomeScreen' }}
               />
          </Stack.Navigator>
     );
};

const EditionsStack = createStackNavigator();
export const EditionsModal = () => {
     const { language } = React.useContext(LanguageContext);
     return (
          <EditionsStack.Navigator
               id="EditionsStack"
               screenOptions={({ navigation, route }) => ({
                    headerShown: false,
                    animationTypeForReplace: 'push',
                    gestureEnabled: false,
                    headerLeft: () => {
                         if (route.name !== 'Editions') {
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
               <EditionsStack.Screen
                    name="Editions"
                    component={Editions}
                    options={{
                         title: getTermFromDictionary(language, 'editions'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
               <EditionsStack.Screen
                    name="WhereIsIt"
                    component={WhereIsIt}
                    options={{
                         title: getTermFromDictionary(language, 'where_is_it'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
          </EditionsStack.Navigator>
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
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
               <FilterModalStack.Screen
                    name="SearchIndex"
                    component={SearchIndexScreen}
                    options={{
                         title: getTermFromDictionary(language, 'search_by'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
          </FilterModalStack.Navigator>
     );
};

export default BrowseStackNavigator;