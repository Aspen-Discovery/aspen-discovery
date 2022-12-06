import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { LoadingScreen } from '../screens/Auth/Loading';
import AccountDrawer from './drawer/DrawerNavigator';
import { LibrarySystemContext, LibraryBranchContext, UserContext, BrowseCategoryContext, CheckoutsContext, HoldsContext } from '../context/initialContext';

const LaunchStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
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
                                                                                name="Drawer"
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
     );
};

export default LaunchStackNavigator;