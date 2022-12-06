import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import GroupedWork from '../../screens/GroupedWork/GroupedWork';
import { MyList } from '../../screens/MyAccount/Lists/MyList';
import { MyLists } from '../../screens/MyAccount/Lists/MyLists';
import Profile from '../../screens/MyAccount/Profile';
import LoadSavedSearch from '../../screens/MyAccount/SavedSearches/LoadSavedSearch';
import SavedSearchScreen from '../../screens/MyAccount/SavedSearches/MySavedSearch';
import MySavedSearches from '../../screens/MyAccount/SavedSearches/MySavedSearches';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import Settings_HomeScreen from '../../screens/MyAccount/Settings/HomeScreen';
import LinkedAccounts from '../../screens/MyAccount/Settings/LinkedAccounts/LinkedAccounts';
import Settings_Notifications from '../../screens/MyAccount/Settings/Notifications';
import Preferences from '../../screens/MyAccount/Settings/Preferences';
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { MyCheckouts } from '../../screens/MyAccount/CheckedOutTitles';
import { MyHolds } from '../../screens/MyAccount/TitlesOnHold';

const AccountStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="Preferences"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Screen name="Preferences" component={Preferences} options={{ title: 'Preferences' }} />
               <Stack.Screen name="ProfileScreen" component={Profile} options={{ title: 'Profile' }} />
               <Stack.Screen name="SettingsHomeScreen" component={Settings_HomeScreen} options={{ title: translate('user_profile.home_screen_settings') }} />
               <Stack.Screen name="SettingsBrowseCategories" component={Settings_BrowseCategories} options={{ title: translate('user_profile.home_screen_settings') }} />
               <Stack.Screen name="SettingsNotifications" component={Settings_Notifications} options={{ title: translate('user_profile.notification_settings') }} />
               <Stack.Screen
                    name="CheckedOut"
                    component={MyCheckouts}
                    options={{
                         title: translate('checkouts.title'),
                         libraryContext: React.useContext(LibrarySystemContext),
                         locationContext: React.useContext(LibraryBranchContext),
                         userContext: React.useContext(UserContext),
                    }}
               />
               <Stack.Screen
                    name="Holds"
                    component={MyHolds}
                    options={{
                         title: translate('holds.title'),
                    }}
               />
               <Stack.Screen
                    name="GroupedWork"
                    component={GroupedWork}
                    options={({ route }) => ({
                         title: translate('grouped_work.title'),
                    })}
               />
               <Stack.Screen name="LinkedAccounts" component={LinkedAccounts} options={{ title: 'Linked Accounts' }} />
               <Stack.Screen
                    name="Lists"
                    component={MyLists}
                    options={{
                         title: 'Lists',
                         libraryContext: React.useContext(LibrarySystemContext),
                         locationContext: React.useContext(LibraryBranchContext),
                         userContext: React.useContext(UserContext),
                    }}
               />
               <Stack.Screen name="List" component={MyList} options={({ route }) => ({ title: route.params.title })} />
               <Stack.Screen name="SavedSearches" component={MySavedSearches} options={{ title: 'Saved Searches' }} />
               <Stack.Screen name="SavedSearch" component={SavedSearchScreen} options={({ route }) => ({ title: route.params.title })} />
               <Stack.Screen
                    name="ItemDetails"
                    component={GroupedWork}
                    options={{
                         title: translate('grouped_work.title'),
                    }}
               />
               <Stack.Screen name="LoadSavedSearch" component={LoadSavedSearch} options={({ route }) => ({ title: route.params.name })} />
          </Stack.Navigator>
     );
};

const AddToListStack = () => {};

export default AccountStackNavigator;