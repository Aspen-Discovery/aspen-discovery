import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';

import { DiscoverHomeScreen } from '../../screens/BrowseCategory/Home';
import { CreateVDXRequest } from '../../screens/GroupedWork/CreateVDXRequest';
import { GroupedWorkScreen } from '../../screens/GroupedWork/GroupedWork';
import { translate } from '../../translations/translations';
import { BrowseCategoryContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { Editions } from '../../screens/GroupedWork/Editions';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';

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
               <Stack.Group>
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
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevRoute: 'Discovery' }}
                    />
                    <Stack.Screen
                         name="CopyDetails"
                         component={WhereIsIt}
                         options={({ navigation }) => ({
                              title: translate('copy_details.where_is_it'),
                              headerShown: true,
                              presentation: 'modal',
                              headerLeft: () => {
                                   return <></>;
                              },
                              headerRight: () => (
                                   <Pressable onPress={() => navigation.goBack()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                        <CloseIcon color="primary.baseContrast" />
                                   </Pressable>
                              ),
                         })}
                    />
               </Stack.Group>
               <Stack.Screen
                    name="EditionsModal"
                    component={EditionsModal}
                    options={{
                         headerShown: false,
                         presentation: 'modal',
                    }}
               />
               <Stack.Group>
                    <Stack.Screen
                         name="CreateVDXRequest"
                         component={CreateVDXRequest}
                         options={{
                              title: translate('ill.request_title'),
                              presentation: 'modal',
                         }}
                    />
               </Stack.Group>
          </Stack.Navigator>
     );
};

const EditionsStack = createStackNavigator();
export const EditionsModal = () => {
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
                                   <Pressable onPress={() => navigation.goBack()} ml={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                        <ChevronLeftIcon color="primary.baseContrast" />
                                   </Pressable>
                              );
                         } else {
                              return null;
                         }
                    },
                    headerRight: () => (
                         <Pressable onPress={() => navigation.getParent().pop()} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                              <CloseIcon color="primary.baseContrast" />
                         </Pressable>
                    ),
               })}>
               <EditionsStack.Screen
                    name="Editions"
                    component={Editions}
                    options={{
                         title: translate('grouped_work.editions'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
               <EditionsStack.Screen
                    name="WhereIsIt"
                    component={WhereIsIt}
                    options={{
                         title: translate('copy_details.where_is_it'),
                         headerShown: true,
                         presentation: 'card',
                    }}
               />
          </EditionsStack.Navigator>
     );
};

export default BrowseStackNavigator;