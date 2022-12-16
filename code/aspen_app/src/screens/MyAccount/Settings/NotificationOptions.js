import _ from 'lodash';
import Constants from 'expo-constants';
import { useFocusEffect } from '@react-navigation/native';
import * as Notifications from 'expo-notifications';

import { Box, FlatList, HStack, Switch, Text } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { deletePushToken, getNotificationPreference, registerForPushNotificationsAsync } from '../../../components/Notifications';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import { refreshProfile } from '../../../util/api/user';

export const NotificationOptions = () => {
     const isDevice = Constants.isDevice;
     const [isLoading, setLoading] = React.useState(true);
     const [expoToken, setExpoToken] = React.useState('');
     const [hasAspenToken, setAspenToken] = React.useState(false);
     const [allowNotifications, setAllowNotifications] = React.useState(isDevice);
     const [notifySavedSearch, setNotifySavedSearch] = React.useState(false);
     const [notifyCustom, setNotifyCustom] = React.useState(false);
     const [notifyAccount, setNotifyAccount] = React.useState(false);
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     const [preferences, setPreferences] = React.useState({
          notifySavedSearch: {
               id: 0,
               label: 'Saved searches',
               option: 'notifySavedSearch',
               description: null,
               allow: notifySavedSearch,
          },
          notifyCustom: {
               id: 1,
               label: 'Alerts from your library',
               option: 'notifyCustom',
               description: null,
               allow: notifyCustom,
          },
          notifyAccount: {
               id: 2,
               label: 'Alerts about my library account',
               option: 'notifyAccount',
               description: null,
               allow: notifyAccount,
          },
     });

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    const token = checkForExpoToken();
                    if (token) {
                         checkForAspenToken();
                         await getPreferences();
                    }
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const checkForExpoToken = async () => {
          const token = (await Notifications.getExpoPushTokenAsync()).data;
          if (token) {
               setExpoToken(token);
               return true;
          }
          return false;
     };

     const checkForAspenToken = () => {
          if (!_.isUndefined(user.notification_preferences)) {
               const tokenStorage = user.notification_preferences;
               if (_.find(tokenStorage, _.matchesProperty('token', expoToken))) {
                    setAspenToken(true);
                    return true;
               }
          }
          return false;
     };

     const [toggled, setToggle] = React.useState(hasAspenToken);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const updateAspenToken = async () => {
          if (toggled) {
               await registerForPushNotificationsAsync(library.baseUrl);
          } else {
               await deletePushToken(library.baseUrl, expoToken, true);
          }

          await refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });

          await getPreferences();
     };

     const getPreferences = async () => {
          const currentPreferences = Object.keys(preferences);
          for await (const pref of currentPreferences) {
               const discoveryValue = await getNotificationPreference(library.baseUrl, expoToken, pref);
               if (discoveryValue) {
                    if (pref === 'notifySavedSearch') {
                         setNotifySavedSearch(discoveryValue);
                    }
                    if (pref === 'notifyCustom') {
                         setNotifyCustom(discoveryValue);
                    }
                    if (pref === 'notifyAccount') {
                         setNotifyAccount(discoveryValue);
                    }
               }
          }
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
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
                    {toggled ? <FlatList data={Object.keys(preferences)} renderItem={({ item }) => <DisplayPreference data={preferences[item]} />} keyExtractor={(item, index) => index.toString()} /> : null}
               </Box>
          </SafeAreaView>
     );
};

const DisplayPreference = (data) => {
     const preference = data.data;
     const [toggled, setToggle] = React.useState(preference.allow);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const updatePreference = (option, value) => {};

     return (
          <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
               <Text>{preference.label}</Text>
               <Switch
                    onToggle={() => {
                         toggleSwitch();
                         updatePreference(preference.option, !preference.allow);
                    }}
                    isChecked={toggled}
               />
          </HStack>
     );
};