import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { LoadingScreen } from '../screens/Auth/Loading';
import AccountDrawer from './drawer/DrawerNavigator';
import {LibrarySystemContext, LibraryBranchContext, UserContext, BrowseCategoryContext, CheckoutsContext, HoldsContext, LanguageContext} from '../context/initialContext';

const LaunchStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
         <LanguageContext.Consumer>
              {(language, updateLanguage, languages, updateLanguages) => (
                    <LibrarySystemContext.Consumer>
                         {(library, version, url) => (
                              <LibraryBranchContext.Consumer>
                                   {(location) => (
                                        <UserContext.Consumer>
                                             {(user, updateUser) => (
                                                  <CheckoutsContext.Consumer>
                                                       {(checkouts) => (
                                                            <HoldsContext.Consumer>
                                                                 {(holds) => (
                                                                      <BrowseCategoryContext.Consumer>
                                                                           {(category, list, maxNum, updateMaxCategories) => (
                                                                                <Stack.Navigator
                                                                                     initialRouteName="LoadingScreen"
                                                                                     screenOptions={{
                                                                                          headerShown: false,
                                                                                          headerBackTitleVisible: false,
                                                                                     }}>
                                                                                     <Stack.Screen
                                                                                          name="LoadingScreen"
                                                                                          component={LoadingScreen}
                                                                                          options={{
                                                                                               animationEnabled: false,
                                                                                               header: () => null,
                                                                                          }}
                                                                                     />
                                                                                     <Stack.Screen
                                                                                          name="DrawerStack"
                                                                                          component={AccountDrawer}
                                                                                          options={{
                                                                                               libraryContext: {
                                                                                                    library,
                                                                                                    version,
                                                                                                    url,
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
                                                                                               languageContext: { language, updateLanguage, languages, updateLanguages }
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
                              </LibraryBranchContext.Consumer>
                         )}
                    </LibrarySystemContext.Consumer>
              )}
         </LanguageContext.Consumer>
     );
};

export default LaunchStackNavigator;