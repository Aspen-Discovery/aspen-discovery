import _ from 'lodash';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import {useFocusEffect} from '@react-navigation/native';
import * as Notifications from 'expo-notifications';

import {Box, FlatList, HStack, Switch, Text} from 'native-base';
import React from 'react';
import {SafeAreaView} from 'react-native';
import {LibrarySystemContext, UserContext} from '../../../context/initialContext';
import {
     deletePushToken,
     getNotificationPreference,
     registerForPushNotificationsAsync,
     setNotificationPreference
} from '../../../components/Notifications';
import {loadingSpinner} from '../../../components/loadingSpinner';
import {translate} from '../../../translations/translations';
import {refreshProfile, reloadProfile} from '../../../util/api/user';

export const Settings_NotificationOptions = () => {
     const [isLoading, setLoading] = React.useState(true);
     const [allowNotifications, setAllowNotifications] = React.useState(!Constants.isDevice);
     const [notifySavedSearch, setNotifySavedSearch] = React.useState(false);
     const [notifyCustom, setNotifyCustom] = React.useState(false);
     const [notifyAccount, setNotifyAccount] = React.useState(false);
     const {
          user,
          updateUser,
          notificationSettings,
          updateNotificationSettings,
          expoToken,
          aspenToken
     } = React.useContext(UserContext);
     const {library} = React.useContext(LibrarySystemContext);
     const [toggled, setToggle] = React.useState(aspenToken);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     useFocusEffect(
         React.useCallback(() => {
              const update = async () => {
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
          if (!toggled) {
               await registerForPushNotificationsAsync(library.baseUrl).then(async (result) => {
                    if (!result) {
                         setToggle(false);
                         console.log('unable to update preference');
                         return false;
                    } else {
                         await reloadProfile(library.baseUrl).then(async (result) => {
                              updateUser(result);
                              await getPreferences();
                         });
                         return true;
                    }
               });
          } else {
               await deletePushToken(library.baseUrl, expoToken, true);
               await reloadProfile(library.baseUrl).then(async (result) => {
                    updateUser(result);
                    await getPreferences();
               });
               return true;
          }
          return false;
     };

     const getPreferences = async () => {
          const currentPreferences = Object.values(notificationSettings);
          for await (const pref of currentPreferences) {
               console.log(pref.option);
               const i = _.findIndex(currentPreferences, ['option', pref.option]);
               const deviceSettings = _.filter(notificationSettings, {option: pref.option});
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
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
         <SafeAreaView style={{flex: 1}}>
              <Box flex={1} safeArea={5}>
                   <HStack space={3} pb={5} alignItems="center" justifyContent="space-between">
                        <Text bold>{translate('user_profile.allow_notifications')}</Text>
                        <Switch
                            onToggle={() => {
                                 toggleSwitch();
                                 updateAspenToken().then((r) => console.log(r));
                            }}
                            isChecked={toggled}
                            isDisabled={allowNotifications}
                        />
                   </HStack>
                   {toggled ? <FlatList data={Object.keys(notificationSettings)}
                                        renderItem={({item}) => <DisplayPreference data={notificationSettings[item]}
                                                                                   notifySavedSearch={notifySavedSearch}
                                                                                   setNotifySavedSearch={setNotifySavedSearch}
                                                                                   notifyCustom={notifyCustom}
                                                                                   setNotifyCustom={setNotifyCustom}
                                                                                   notifyAccount={notifyAccount}
                                                                                   setNotifyAccount={setNotifyAccount}/>}
                                        keyExtractor={(item, index) => index.toString()}/> : null}
              </Box>
         </SafeAreaView>
     );
};

const DisplayPreference = (data) => {
     const {
          user,
          updateUser,
          notificationSettings,
          updateNotificationSettings,
          expoToken
     } = React.useContext(UserContext);
     const {library} = React.useContext(LibrarySystemContext);
     const preference = data.data;
     const {
          notifySavedSearch,
          setNotifySavedSearch,
          notifyCustom,
          setNotifyCustom,
          notifyAccount,
          setNotifyAccount
     } = data;

     let defaultToggleState = false;
     defaultToggleState = preference.allow === 1 || preference.allow === '1' || preference.allow === true;

     //console.log('defaultToggleState > ' + defaultToggleState);
     //console.log(notifyAccount);
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
          console.log((allowNotification = true));
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