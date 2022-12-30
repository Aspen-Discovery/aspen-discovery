import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';

import { GroupedWorkScreen } from '../../screens/GroupedWork/GroupedWork';
import Profile from '../../screens/MyAccount/Profile';
import LoadSavedSearch from '../../screens/MyAccount/SavedSearches/LoadSavedSearch';
import SavedSearchScreen from '../../screens/MyAccount/SavedSearches/MySavedSearch';
import MySavedSearches from '../../screens/MyAccount/SavedSearches/MySavedSearches';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import Settings_HomeScreen from '../../screens/MyAccount/Settings/HomeScreen';
import { MyLinkedAccounts } from '../../screens/MyAccount/Settings/LinkedAccounts/LinkedAccounts';
import Settings_Notifications from '../../screens/MyAccount/Settings/Notifications';
import Preferences from '../../screens/MyAccount/Settings/Preferences';
import { translate } from '../../translations/translations';
import { MyReadingHistory } from '../../screens/MyAccount/ReadingHistory/ReadingHistory';
import { MyCheckouts } from '../../screens/MyAccount/CheckedOutTitles/MyCheckouts';
import { MyHolds } from '../../screens/MyAccount/TitlesOnHold/MyHolds';
import { MyLists } from '../../screens/MyAccount/Lists/MyLists';
import { MyList } from '../../screens/MyAccount/Lists/MyList';
import { Settings_NotificationOptions } from '../../screens/MyAccount/Settings/NotificationOptions';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import { EditionsModal } from './BrowseStackNavigator';

const AccountStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="MyPreferences"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Group>
                    <Stack.Screen name="MyPreferences" component={Preferences} options={{ title: translate('user_profile.preferences') }} />
                    <Stack.Screen name="SettingsHomeScreen" component={Settings_HomeScreen} options={{ title: translate('user_profile.home_screen_settings') }} />
                    <Stack.Screen name="SettingsBrowseCategories" component={Settings_BrowseCategories} options={{ title: translate('user_profile.home_screen_settings') }} />
                    <Stack.Screen name="SettingsNotificationOptions" component={Settings_NotificationOptions} options={{ title: translate('user_profile.notification_settings') }} />
                    <Stack.Screen name="SettingsNotifications" component={Settings_Notifications} options={{ title: translate('user_profile.notification_settings') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyProfile" component={Profile} options={{ title: translate('user_profile.profile') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyLinkedAccounts" component={MyLinkedAccounts} options={{ title: translate('linked_accounts.title') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyHolds"
                         component={MyHolds}
                         options={{
                              title: translate('holds.title'),
                         }}
                    />
                    <Stack.Screen
                         name="MyHold"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevScreen: 'MyHolds' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyCheckouts"
                         component={MyCheckouts}
                         options={{
                              title: translate('checkouts.title'),
                         }}
                    />
                    <Stack.Screen
                         name="MyCheckout"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevScreen: 'MyCheckouts' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyLists"
                         component={MyLists}
                         options={{
                              title: translate('lists.title'),
                         }}
                    />
                    <Stack.Screen name="MyList" component={MyList} options={({ route }) => ({ title: route.params.title })} />
                    <Stack.Screen
                         name="ListItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevScreen: 'MyList' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MySavedSearches"
                         component={MySavedSearches}
                         options={{
                              title: translate('saved_searches.title'),
                         }}
                    />
                    <Stack.Screen name="MySavedSearch" component={SavedSearchScreen} options={({ route }) => ({ title: route.params.title })} />
                    <Stack.Screen
                         name="SavedSearchItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevScreen: 'MySavedSearch' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyReadingHistory"
                         component={MyReadingHistory}
                         options={{
                              title: translate('reading_history.title'),
                         }}
                    />
                    <Stack.Screen
                         name="ItemDetails"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? translate('grouped_work.title'),
                         })}
                         initialParams={{ prevScreen: 'MyReadingHistory' }}
                    />
               </Stack.Group>
               <Stack.Screen name="LoadSavedSearch" component={LoadSavedSearch} options={({ route }) => ({ title: route.params.name })} />
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
                                   <CloseIcon color="primary.baseContrast" />
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
          </Stack.Navigator>
     );
};

export default AccountStackNavigator;