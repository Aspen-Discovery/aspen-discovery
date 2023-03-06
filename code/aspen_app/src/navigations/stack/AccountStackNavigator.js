import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { ChevronLeftIcon, CloseIcon, Pressable } from 'native-base';

import {GroupedWork221200, GroupedWorkScreen} from '../../screens/GroupedWork/GroupedWork';
import {MyProfile} from '../../screens/MyAccount/Profile';
import { LoadSavedSearch } from '../../screens/MyAccount/SavedSearches/LoadSavedSearch';
import SavedSearchScreen from '../../screens/MyAccount/SavedSearches/MySavedSearch';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import Settings_HomeScreen from '../../screens/MyAccount/Settings/HomeScreen';
import { MyLinkedAccounts } from '../../screens/MyAccount/Settings/LinkedAccounts/LinkedAccounts';
import Settings_Notifications from '../../screens/MyAccount/Settings/Notifications';
import Preferences from '../../screens/MyAccount/Settings/Preferences';
import { MyReadingHistory } from '../../screens/MyAccount/ReadingHistory/ReadingHistory';
import { MyCheckouts } from '../../screens/MyAccount/CheckedOutTitles/MyCheckouts';
import { MyHolds } from '../../screens/MyAccount/TitlesOnHold/MyHolds';
import { MyLists } from '../../screens/MyAccount/Lists/MyLists';
import { MyList } from '../../screens/MyAccount/Lists/MyList';
import { Settings_NotificationOptions } from '../../screens/MyAccount/Settings/NotificationOptions';
import { WhereIsIt } from '../../screens/GroupedWork/WhereIsIt';
import { EditionsModal } from './BrowseStackNavigator';
import {CreateVDXRequest} from '../../screens/GroupedWork/CreateVDXRequest';
import {LanguageContext} from '../../context/initialContext';
import {getTermFromDictionary} from '../../translations/TranslationService';
import {MySavedSearches} from '../../screens/MyAccount/SavedSearches/MySavedSearches';

const AccountStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="MyPreferences"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Group>
                    <Stack.Screen name="MyPreferences" component={Preferences} options={{ title: getTermFromDictionary(language, 'preferences') }} />
                    <Stack.Screen name="SettingsHomeScreen" component={Settings_HomeScreen} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="SettingsBrowseCategories" component={Settings_BrowseCategories} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="SettingsNotificationOptions" component={Settings_NotificationOptions} options={{ title: getTermFromDictionary(language, 'notification_settings') }} />
                    <Stack.Screen name="SettingsNotifications" component={Settings_Notifications} options={{ title: getTermFromDictionary(language, 'notification_settings') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyProfile" component={MyProfile} options={{ title: getTermFromDictionary(language, 'profile') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="MyLinkedAccounts" component={MyLinkedAccounts} options={{ title: getTermFromDictionary(language, 'linked_accounts') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen
                         name="MyHolds"
                         component={MyHolds}
                         options={{
                             title: getTermFromDictionary(language, 'titles_on_hold')
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
                   <Stack.Screen
                       name="MyHold221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                       })}
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
                   <Stack.Screen
                       name="MyCheckout221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                       })}
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
                       name="ListItem221200"
                       component={GroupedWork221200}
                       options={({ route }) => ({
                           title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                       })}
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
                        component={SavedSearchScreen}
                        options={({ navigation, route }) => ({
                            title: route.params.title,
                            headerLeft: () => {
                                if(route.params.prevRoute === 'NONE') {
                                    return null;
                                } else {
                                    return (
                                        <Pressable mr={3} onPress={() => navigation.goBack()} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                                            <ChevronLeftIcon size={6} color="primary.baseContrast" />
                                        </Pressable>
                                    );
                                }
                            }
                        }
                        )}
                    />
                    <Stack.Screen
                         name="SavedSearchItem"
                         component={GroupedWorkScreen}
                         options={({ route }) => ({
                              title: route.params.title ?? getTermFromDictionary(language, 'item_details'),
                         })}
                         initialParams={{ prevRoute: 'MySavedSearch' }}
                    />
                   <Stack.Screen
                       name="SavedSearchItem221200"
                       component={GroupedWork221200}
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
                              title: getTermFromDictionary(language, 'reading_history'),
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