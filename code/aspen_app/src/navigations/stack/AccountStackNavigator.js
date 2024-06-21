import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';
import React from 'react';
import { LanguageContext } from '../../context/initialContext';
import { EventScreen } from '../../screens/Event/Event';
import { CreateVDXRequest } from '../../screens/GroupedWork/CreateVDXRequest';

import { GroupedWorkScreen } from '../../screens/GroupedWork/GroupedWork';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import { MyCheckouts } from '../../screens/MyAccount/CheckedOutTitles/MyCheckouts';
import { MyEvents } from '../../screens/MyAccount/Events/Events';
import { MyList } from '../../screens/MyAccount/Lists/MyList';
import { MyLists } from '../../screens/MyAccount/Lists/MyLists';
import { MyProfile } from '../../screens/MyAccount/Profile/MyProfile';
import { MyReadingHistory } from '../../screens/MyAccount/ReadingHistory/ReadingHistory';
import { LoadSavedSearch } from '../../screens/MyAccount/SavedSearches/LoadSavedSearch';
import { MySavedSearch } from '../../screens/MyAccount/SavedSearches/MySavedSearch';
import { MySavedSearches } from '../../screens/MyAccount/SavedSearches/MySavedSearches';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import { MyLinkedAccounts } from '../../screens/MyAccount/LinkedAccounts/LinkedAccounts';
import { Settings_NotificationOptions } from '../../screens/MyAccount/Settings/NotificationOptions';
import { PreferencesScreen } from '../../screens/MyAccount/Settings/Preferences';
import { MyHolds } from '../../screens/MyAccount/TitlesOnHold/MyHolds';
import { BackIcon } from '../../themes/theme';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { EditionsModal } from './BrowseStackNavigator';

const AccountStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="MyPreferences"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
                    headerBackImage: () => <BackIcon />,
               }}>
               <Stack.Group>
                    <Stack.Screen name="MyPreferences" component={PreferencesScreen} options={{ title: getTermFromDictionary(language, 'preferences') }} />
                    <Stack.Screen name="SettingsBrowseCategories" component={Settings_BrowseCategories} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="SettingsNotificationOptions" component={Settings_NotificationOptions} options={{ title: getTermFromDictionary(language, 'notification_settings') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyProfile" component={MyProfile} options={{ title: getTermFromDictionary(language, 'contact_information') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyLinkedAccounts" component={MyLinkedAccounts} options={{ title: getTermFromDictionary(language, 'linked_accounts') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyHolds"
                         component={MyHolds}
                         options={{
                              title: getTermFromDictionary(language, 'titles_on_hold'),
                         }}
                    />
                    <Stack.Screen
                         name="MyHold"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MyHolds' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyCheckouts"
                         component={MyCheckouts}
                         options={{
                              title: getTermFromDictionary(language, 'checked_out_titles'),
                         }}
                    />
                    <Stack.Screen
                         name="MyCheckout"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MyCheckouts' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyLists"
                         component={MyLists}
                         options={{
                              title: getTermFromDictionary(language, 'lists'),
                         }}
                    />
                    <Stack.Screen name="MyList" component={MyList} options={({ route }) => ({ title: route.params.title })} />
                    <Stack.Screen
                         name="ListItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MyList' }}
                    />
                    <Stack.Screen
                         name="ListItemEvent"
                         component={EventScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'event_details'),
                         })}
                         initialParams={{ prevRoute: 'MyList' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MySavedSearches"
                         component={MySavedSearches}
                         options={{
                              title: getTermFromDictionary(language, 'saved_searches'),
                         }}
                    />
                    <Stack.Screen
                         name="MySavedSearch"
                         component={MySavedSearch}
                         options={({ navigation, route }) => ({
                              title: route.params.title,
                              headerLeft: () => {
                                   if (route.params.prevRoute === 'NONE') {
                                        return null;
                                   } else {
                                        return (
                                             <Pressable mr={3} onPress={() => navigation.goBack()} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                                  <ChevronLeftIcon size={6} color="primary.baseContrast" />
                                             </Pressable>
                                        );
                                   }
                              },
                         })}
                    />
                    <Stack.Screen
                         name="SavedSearchItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MySavedSearch' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyReadingHistory"
                         component={MyReadingHistory}
                         options={{
                              title: getTermFromDictionary(language, 'my_reading_history'),
                         }}
                    />
                    <Stack.Screen
                         name="ItemDetails"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MyReadingHistory' }}
                    />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyEvents"
                         component={MyEvents}
                         options={{
                              title: getTermFromDictionary(language, 'my_events'),
                         }}
                    />
                    <Stack.Screen
                         name="EventDetails"
                         component={EventScreen}
                         initialParams={{ prevRoute: 'MyEvents' }}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'event_details'),
                         })}
                    />
               </Stack.Group>
               <Stack.Screen name="LoadSavedSearch" component={LoadSavedSearch} options={({ route }) => ({ title: route.params.name })} />
               <Stack.Screen
                    name="CopyDetails"
                    component={WhereIsIt}
                    options={({ navigation }) => ({
                         title: getTermFromDictionary(language, 'where_is_it'),
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
                                   <CloseIcon size={5} color="primary.baseContrast" />
                              </Pressable>
                         ),
                    })}
               />
          </Stack.Navigator>
     );
};

export default AccountStackNavigator;