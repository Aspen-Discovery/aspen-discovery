import { useFocusEffect } from '@react-navigation/native';
import { useIsFetching, useQueryClient } from '@tanstack/react-query';
import * as Device from 'expo-device';
import _ from 'lodash';
import { Box, FlatList, HStack, Switch, Text } from 'native-base';
import React from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { createChannelsAndCategories, deletePushToken, getNotificationPreference, registerForPushNotificationsAsync, setNotificationPreference } from '../../../components/Notifications';
import { PermissionsPrompt } from '../../../components/PermissionsPrompt';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { refreshProfile, reloadProfile } from '../../../util/api/user';

export const Settings_NotificationOptions = () => {
     const isFetchingUserProfile = useIsFetching({ queryKey: ['user'] });
     const [isLoading, setLoading] = React.useState(false);
     const [error, showError] = React.useState(false);
     const [shouldRequestPermissions, setShouldRequestPermissions] = React.useState(false);
     const [allowNotifications, setAllowNotifications] = React.useState(!Device.isDevice);
     const [notifySavedSearch, setNotifySavedSearch] = React.useState(false);
     const [notifyCustom, setNotifyCustom] = React.useState(false);
     const [notifyAccount, setNotifyAccount] = React.useState(false);
     const { user, updateUser, notificationSettings, updateNotificationSettings, expoToken, aspenToken } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [toggled, setToggle] = React.useState(aspenToken);
     const toggleSwitch = () => setToggle((previousState) => !previousState);
     const { language } = React.useContext(LanguageContext);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    setLoading(true);
                    await createChannelsAndCategories();
                    if (expoToken) {
                         if (aspenToken) {
                              setToggle(true);
                              await getPreferences();
                         } else {
                              setToggle(false);
                         }
                    }
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const updateAspenToken = async () => {
          setLoading(true);
          if (!toggled) {
               await registerForPushNotificationsAsync(library.baseUrl).then(async (result) => {
                    if (!result) {
                         setToggle(false);
                         console.log('unable to update preference');
                         setLoading(false);
                         if (Platform.OS === 'android') {
                              if (Device.osVersion < 13) {
                                   setShouldRequestPermissions(true);
                              }
                         }

                         if (Platform.OS === 'ios') {
                              setShouldRequestPermissions(true);
                         }
                         return false;
                    } else {
                         await refreshProfile(library.baseUrl).then(async (result) => {
                              updateUser(result);
                              await getPreferences();
                         });
                         setLoading(false);
                         return true;
                    }
               });
          } else {
               await deletePushToken(library.baseUrl, expoToken, true);
               await refreshProfile(library.baseUrl).then(async (result) => {
                    updateUser(result);
                    await getPreferences();
               });
               setToggle(false);
               setLoading(false);
               return true;
          }
          setLoading(false);
          return false;
     };

     const getPreferences = async () => {
          setLoading(true);
          if (_.isObject(notificationSettings)) {
               const currentPreferences = Object.values(notificationSettings);
               for await (const pref of currentPreferences) {
                    console.log(pref.option);
                    const i = _.findIndex(currentPreferences, ['option', pref.option]);
                    const deviceSettings = _.filter(notificationSettings, { option: pref.option });
                    const result = await getNotificationPreference(library.baseUrl, expoToken, pref.option);
                    if (result && i !== -1) {
                         let prevSettings = notificationSettings[i];
                         console.log(prevSettings.allow);
                         if (result.success) {
                              if (pref.option === 'notifySavedSearch') {
                                   setNotifySavedSearch(result.allow);
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                                   //setPreferences(newSettings);
                              }
                              if (pref.option === 'notifyCustom') {
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                                   //setPreferences(newSettings);
                                   setNotifyCustom(result.allow);
                              }
                              if (pref.option === 'notifyAccount') {
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                                   //setPreferences(newSettings);
                                   setNotifyAccount(result.allow);
                              }
                              console.log(prevSettings.allow);
                         }
                    }
               }
          }
          setLoading(false);
     };

     const updateStatus = async () => {
          await reloadProfile(library.baseUrl).then(async (result) => {
               updateUser(result);
               await getPreferences();
          });
     };

     if (isLoading) {
          return loadingSpinner();
     }

     if (shouldRequestPermissions) {
          return <PermissionsPrompt promptTitle="permissions_notifications_title" promptBody="permissions_notifications_body" setShouldRequestPermissions={setShouldRequestPermissions} updateStatus={updateStatus} />;
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box flex={1} safeArea={5}>
                    <HStack space={3} pb={3} alignItems="center" justifyContent="space-between">
                         <Text bold>{getTermFromDictionary(language, 'notifications_allow')}</Text>
                         <Switch
                              onToggle={() => {
                                   toggleSwitch();
                                   updateAspenToken().then((r) => console.log(r));
                              }}
                              defaultValue={toggled}
                              isDisabled={allowNotifications}
                         />
                    </HStack>
                    {toggled && !error && _.isObject(notificationSettings) ? (
                         <>
                              <EnableAllNotifications setLoading={setLoading} notifySavedSearch={notifySavedSearch} setNotifySavedSearch={setNotifySavedSearch} notifyCustom={notifyCustom} setNotifyCustom={setNotifyCustom} notifyAccount={notifyAccount} setNotifyAccount={setNotifyAccount} />
                              <FlatList data={Object.keys(notificationSettings)} renderItem={({ item }) => <DisplayPreference data={notificationSettings[item]} notifySavedSearch={notifySavedSearch} setNotifySavedSearch={setNotifySavedSearch} notifyCustom={notifyCustom} setNotifyCustom={setNotifyCustom} notifyAccount={notifyAccount} setNotifyAccount={setNotifyAccount} />} keyExtractor={(item, index) => index.toString()} />
                         </>
                    ) : null}
               </Box>
          </SafeAreaView>
     );
};

const EnableAllNotifications = (data) => {
     const queryClient = useQueryClient();
     const { language } = React.useContext(LanguageContext);
     const { user, updateUser, notificationSettings, updateNotificationSettings, expoToken } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { notifySavedSearch, setNotifySavedSearch, notifyCustom, setNotifyCustom, notifyAccount, setNotifyAccount, setLoading } = data;

     let defaultToggleState = notifyCustom && notifyAccount && notifySavedSearch;
     const [toggled, setToggle] = React.useState(defaultToggleState);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const enableAllNotifications = async (value) => {
          console.log(value);
          setLoading(true);
          let allowAllNotifications = true;
          if (value === 0 || value === 'false' || value === false) {
               allowAllNotifications = false;
          }
          if (expoToken) {
               await setNotificationPreference(library.baseUrl, expoToken, 'notifySavedSearch', allowAllNotifications, false);
               await setNotificationPreference(library.baseUrl, expoToken, 'notifyCustom', allowAllNotifications, false);
               await setNotificationPreference(library.baseUrl, expoToken, 'notifyAccount', allowAllNotifications, false);
               setNotifySavedSearch(allowAllNotifications);
               setNotifyCustom(allowAllNotifications);
               setNotifyAccount(allowAllNotifications);
               /*
			 _.set(notificationSettings.notifySavedSearch, notificationSettings.notifySavedSearch.allow, allowAllNotifications);
			 _.set(notificationSettings.notifyCustom, notificationSettings.notifyCustom.allow, allowAllNotifications);
			 _.set(notificationSettings.notifyAccount, notificationSettings.notifyAccount.allow, allowAllNotifications);

			 */
               await reloadProfile(library.baseUrl).then((data) => {
                    updateUser(data);
                    updateNotificationSettings(data.notification_preferences, language);
                    setLoading(false);
               });
               queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });

               //updateNotificationSettings
          }
     };

     return (
          <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
               <Text bold>{getTermFromDictionary(language, 'notifications_enable_all')}</Text>
               <Switch
                    onToggle={() => {
                         toggleSwitch();
                         enableAllNotifications(!toggled).then((r) => {
                              console.log(r);
                         });
                    }}
                    defaultValue={toggled}
                    isChecked={toggled}
               />
          </HStack>
     );
};

const DisplayPreference = (data) => {
     const { user, updateUser, notificationSettings, updateNotificationSettings, expoToken } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const preference = data.data;
     const { notifySavedSearch, setNotifySavedSearch, notifyCustom, setNotifyCustom, notifyAccount, setNotifyAccount } = data;

     let defaultToggleState = false;
     console.log(preference.allow);
     defaultToggleState = preference.allow === 1 || preference.allow === '1' || preference.allow === true || preference.allow === 'true';

     if (preference.option === 'notifySavedSearch') {
          defaultToggleState = notifySavedSearch;
     } else if (preference.option === 'notifyCustom') {
          defaultToggleState = notifyCustom;
     } else if (preference.option === 'notifyAccount') {
          defaultToggleState = notifyAccount;
     }

     const [toggled, setToggle] = React.useState(defaultToggleState);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const updatePreference = async (pref, value) => {
          console.log(pref);
          console.log(value);
          let allowNotification = true;
          if (value === 0) {
               allowNotification = true;
          } else {
               allowNotification = false;
          }
          if (expoToken) {
               await setNotificationPreference(library.baseUrl, expoToken, pref, allowNotification);
               if (pref === 'notifySavedSearch') {
                    setNotifySavedSearch(value);
               }
               if (pref === 'notifyCustom') {
                    setNotifyCustom(value);
               }
               if (pref === 'notifyAccount') {
                    setNotifyAccount(value);
               }
               await reloadProfile(library.baseUrl).then((result) => {
                    updateUser(result);
               });
          }
     };

     return (
          <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
               <Text>{preference.label}</Text>
               <Switch
                    onToggle={() => {
                         toggleSwitch();
                         updatePreference(preference.option, preference.allow).then((r) => {
                              console.log(r);
                         });
                    }}
                    isChecked={toggled}
               />
          </HStack>
     );
};