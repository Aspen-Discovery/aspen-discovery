import { useRoute } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { BrowseCategoryContext, CheckoutsContext, HoldsContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, SearchContext, SystemMessagesContext, ThemeContext, UserContext } from '../context/initialContext';
import { LoadingScreen } from '../screens/Auth/Loading';
import AccountDrawer from './drawer/DrawerNavigator';

const LaunchStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     const route = useRoute();
     const refreshUserData = route.params?.refreshUserData ?? false;
     //console.log('refreshUserData: ' + route.params?.refreshUserData);
     return (
          <ThemeContext.Consumer>
               {(mode, updateColorMode, textColor, updateTextColor) => (
                    <SystemMessagesContext.Consumer>
                         {(systemMessages, updateSystemMessages) => (
                              <LanguageContext.Consumer>
                                   {(language, updateLanguage, languages, updateLanguages, dictionary, updateDictionary, languageDisplayName, updateLanguageDisplayName) => (
                                        <LibrarySystemContext.Consumer>
                                             {(library, version, url, menu, catalogStatus, catalogStatusMessage) => (
                                                  <LibraryBranchContext.Consumer>
                                                       {(location, locations) => (
                                                            <SearchContext.Consumer>
                                                                 {(currentIndex, updateCurrentIndex, currentSource, updateCurrentSource, indexes, updateIndexes, sources, updateSources, facets, updateFacets, query, updateQuery, sort, updateSort, resetSearch) => (
                                                                      <UserContext.Consumer>
                                                                           {(user, updateUser) => (
                                                                                <CheckoutsContext.Consumer>
                                                                                     {(checkouts) => (
                                                                                          <HoldsContext.Consumer>
                                                                                               {(holds, pendingSortMethod, readySortMethod, updatePendingSortMethod, updateReadySortMethod) => (
                                                                                                    <BrowseCategoryContext.Consumer>
                                                                                                         {(category, list, maxNum, updateMaxCategories) => (
                                                                                                              <Stack.Navigator
                                                                                                                   initialRouteName="LoadingScreen"
                                                                                                                   screenOptions={{
                                                                                                                        headerShown: false,
                                                                                                                        headerBackTitleVisible: false,
                                                                                                                        gestureEnabled: false,
                                                                                                                   }}>
                                                                                                                   {refreshUserData ? (
                                                                                                                        <Stack.Screen
                                                                                                                             name="LoadingScreen"
                                                                                                                             component={LoadingScreen}
                                                                                                                             options={{
                                                                                                                                  animationEnabled: false,
                                                                                                                                  header: () => null,
                                                                                                                             }}
                                                                                                                        />
                                                                                                                   ) : null}
                                                                                                                   <Stack.Screen
                                                                                                                        name="DrawerStack"
                                                                                                                        component={AccountDrawer}
                                                                                                                        options={{
                                                                                                                             libraryContext: {
                                                                                                                                  library,
                                                                                                                                  version,
                                                                                                                                  url,
                                                                                                                                  menu,
                                                                                                                             },
                                                                                                                             locationContext: location,
                                                                                                                             userContext: { user, updateUser },
                                                                                                                             browseCategoriesContext: {
                                                                                                                                  category,
                                                                                                                                  list,
                                                                                                                                  maxNum,
                                                                                                                                  updateMaxCategories,
                                                                                                                             },
                                                                                                                             checkoutsContext: checkouts,
                                                                                                                             holdsContext: holds,
                                                                                                                             languageContext: { language, updateLanguage, languages, updateLanguages, dictionary, updateDictionary, languageDisplayName, updateLanguageDisplayName },
                                                                                                                             systemMessagesContext: { systemMessages, updateSystemMessages },
                                                                                                                             themeContext: { mode, updateColorMode },
                                                                                                                        }}
                                                                                                                   />
                                                                                                              </Stack.Navigator>
                                                                                                         )}
                                                                                                    </BrowseCategoryContext.Consumer>
                                                                                               )}
                                                                                          </HoldsContext.Consumer>
                                                                                     )}
                                                                                </CheckoutsContext.Consumer>
                                                                           )}
                                                                      </UserContext.Consumer>
                                                                 )}
                                                            </SearchContext.Consumer>
                                                       )}
                                                  </LibraryBranchContext.Consumer>
                                             )}
                                        </LibrarySystemContext.Consumer>
                                   )}
                              </LanguageContext.Consumer>
                         )}
                    </SystemMessagesContext.Consumer>
               )}
          </ThemeContext.Consumer>
     );
};

export default LaunchStackNavigator;