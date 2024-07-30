import { ChevronLeftIcon, FlatList, Switch, ScrollView, AlertDialog, AlertDialogBackdrop, HStack, VStack, Pressable, Icon, Text, Center, Button, ButtonText, ButtonIcon, ButtonGroup, Heading, Box, Accordion, AlertDialogBody, AlertDialogContent, AlertDialogFooter, AlertDialogHeader, AccordionItem, AccordionContent, AccordionContentText, AccordionHeader, AccordionTrigger, AccordionTitleText, AccordionIcon } from '@gluestack-ui/themed';
import { useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import React from 'react';
import * as Notifications from 'expo-notifications';
import * as Linking from 'expo-linking';
import { AppState, Platform } from 'react-native';

import { useNavigation, useRoute, StackActions, useFocusEffect } from '@react-navigation/native';
import { loadingSpinner } from '../../../../components/loadingSpinner';
import { createChannelsAndCategories, deletePushToken, getNotificationPreference, registerForPushNotificationsAsync, savePushToken, setNotificationPreference } from '../../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, ThemeContext, UserContext } from '../../../../context/initialContext';
import { navigate } from '../../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../../translations/TranslationService';
import { ChevronRight, ChevronUp, ChevronDown } from 'lucide-react-native';
import Constants from 'expo-constants';
import { getAppPreferencesForUser, refreshProfile, reloadProfile } from '../../../../util/api/user';

export const NotificationPermissionStatus = () => {
     const { language } = React.useContext(LanguageContext);
     const { colorMode, textColor } = React.useContext(ThemeContext);
     const [permissionStatus, setPermissionStatus] = React.useState(false);
     const appState = React.useRef(AppState.currentState);
     const [appStateVisible, setAppStateVisible] = React.useState(appState.current);

     React.useEffect(() => {
          (async () => {
               const { status } = await Notifications.getPermissionsAsync();
               setPermissionStatus(status === 'granted');
          })();

          const subscription = AppState.addEventListener('change', async (nextAppState) => {
               if (appState.current.match(/inactive|background/) && nextAppState === 'active') {
                    const { status } = await Notifications.getPermissionsAsync();
                    setPermissionStatus(status === 'granted');
               }

               appState.current = nextAppState;
               setAppStateVisible(appState.current);
          });

          const subscriptionAndroid = AppState.addEventListener('focus', async (nextAppState) => {
               const { status } = await Notifications.getPermissionsAsync();
               setPermissionStatus(status === 'granted');
               appState.current = nextAppState;
               setAppStateVisible(appState.current);
          });

          return () => {
               subscription.remove();
               subscriptionAndroid.remove();
          };
     }, []);

     return (
          <Pressable onPress={() => navigate('PermissionNotificationDescription', { permissionStatus })} pb="$3">
               <HStack space="md" justifyContent="space-between" alignItems="center">
                    <Text bold color={textColor}>
                         {getTermFromDictionary(language, 'notification_permission')}
                    </Text>
                    <HStack alignItems="center">
                         <Text color={textColor}>{permissionStatus === true ? getTermFromDictionary(language, 'allowed') : getTermFromDictionary(language, 'not_allowed')}</Text>
                         <Icon ml="$1" as={ChevronRight} color={textColor} />
                    </HStack>
               </HStack>
          </Pressable>
     );
};

export const NotificationPermissionDescription = () => {
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const prevRoute = useRoute().params?.prevRoute ?? null;
     const { colorMode, textColor, theme } = React.useContext(ThemeContext);
     const [permissionStatus, setPermissionStatus] = React.useState(useRoute().params?.permissionStatus ?? false);
     const { language } = React.useContext(LanguageContext);
     const [shouldRequestPermissions, setShouldRequestPermissions] = React.useState(false);
     const [isLoading, setLoading] = React.useState(false);
     const { user, updateUser, notificationSettings, updateNotificationSettings, expoToken, updateExpoToken, aspenToken, updateAspenToken } = React.useContext(UserContext);
     const [notifySavedSearch, setNotifySavedSearch] = React.useState(false);
     const [notifyCustom, setNotifyCustom] = React.useState(false);
     const [notifyAccount, setNotifyAccount] = React.useState(false);
     const { library } = React.useContext(LibrarySystemContext);
     const appState = React.useRef(AppState.currentState);
     const [appStateVisible, setAppStateVisible] = React.useState(appState.current);

     React.useLayoutEffect(() => {
          if (prevRoute === 'notifications_onboard') {
               navigation.setOptions({
                    headerLeft: () => (
                         <Button bg="transparent" onPress={() => navigate('MoreMenu')} mr="$3" hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                              <ButtonIcon size="lg" variant="outline" borderWidth={0} color={theme['colors']['primary']['baseContrast']} as={ChevronLeftIcon} />
                         </Button>
                    ),
               });
          }
     }, [navigation]);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await createChannelsAndCategories();
                    if (expoToken) {
                         if (aspenToken) {
                              await getPreferences();
                         }
                    }
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     React.useEffect(() => {
          (async () => {
               const { status } = await Notifications.getPermissionsAsync();
               setPermissionStatus(status === 'granted');

               if (status === 'granted') {
                    const token = (
                         await Notifications.getExpoPushTokenAsync({
                              projectId: Constants.expoConfig.extra.eas.projectId,
                         })
                    ).data;

                    if (token) {
                         if (!_.isEmpty(user.notification_preferences)) {
                              const tokenStorage = user.notification_preferences;
                              if (_.find(tokenStorage, _.matchesProperty('token', token))) {
                                   updateAspenToken(true);
                                   updateExpoToken(token);
                              }
                         }
                    }
                    await getPreferences();
               }
          })();

          const subscription = AppState.addEventListener('change', async (nextAppState) => {
               if (appState.current.match(/inactive|background/) && nextAppState === 'active') {
                    console.log('app is active again!');
                    const { status } = await Notifications.getPermissionsAsync();
                    console.log('new status: ' + status);
                    setPermissionStatus(status === 'granted');
                    if (status === 'granted') {
                         const token = (
                              await Notifications.getExpoPushTokenAsync({
                                   projectId: Constants.expoConfig.extra.eas.projectId,
                              })
                         ).data;

                         if (token) {
                              if (!_.isEmpty(user.notification_preferences)) {
                                   const tokenStorage = user.notification_preferences;
                                   if (_.find(tokenStorage, _.matchesProperty('token', token))) {
                                        updateAspenToken(true);
                                        updateExpoToken(token);
                                   }
                              }
                         }
                         await addNotificationPermissions();
                         await getPreferences();
                    }

                    if (status === 'denied') {
                         console.log('removing notification preferences from Discovery...');
                         await revokeNotificationPermissions();
                         await getPreferences();
                         updateExpoToken(false);
                         updateAspenToken(false);
                    }
               }

               appState.current = nextAppState;
               setAppStateVisible(appState.current);
          });

          return () => {
               subscription.remove();
          };
     }, []);

     const addNotificationPermissions = async () => {
          console.log('Adding notification permissions...');
          await createChannelsAndCategories();

          console.log('Registering push notifications...');
          await registerForPushNotificationsAsync(library.baseUrl).then(async (result) => {
               if (!result) {
                    console.log('Unable to register push notifications!');
               } else {
                    console.log('Registered for push notifications!');
                    console.log('Saving push token to Discovery...');
                    await savePushToken(library.baseUrl, result);
                    updateExpoToken(result);
                    updateAspenToken(true);
               }
          });

          await refreshProfile(library.baseUrl).then(async (result) => {
               console.log('Refreshing user profile...');
               updateUser(result);
               await getPreferences();
          });
     };

     const revokeNotificationPermissions = async () => {
          setLoading(true);
          await deletePushToken(library.baseUrl, expoToken);
          await setNotificationPreference(library.baseUrl, expoToken, 'notifySavedSearch', false, false);
          await setNotificationPreference(library.baseUrl, expoToken, 'notifyCustom', false, false);
          await setNotificationPreference(library.baseUrl, expoToken, 'notifyAccount', false, false);
          setNotifySavedSearch(false);
          setNotifyCustom(false);
          setNotifyAccount(false);
          await reloadProfile(library.baseUrl).then((data) => {
               updateUser(data);
               updateNotificationSettings(data.notification_preferences, language);
          });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          setLoading(false);
     };

     const getPreferences = async () => {
          console.log('Getting notification preference options...');
          if (_.isObject(notificationSettings)) {
               console.log('Notification preferences found as object!');
               const currentPreferences = Object.values(notificationSettings);
               console.log(currentPreferences);
               setLoading(true);
               for await (const pref of currentPreferences) {
                    const i = _.findIndex(currentPreferences, ['option', pref.option]);
                    const result = await getNotificationPreference(library.baseUrl, expoToken, pref.option);
                    if (result && i !== -1) {
                         let prevSettings = notificationSettings[i];
                         if (result.success) {
                              if (pref.option === 'notifySavedSearch') {
                                   console.log('Updated saved search notifications');
                                   setNotifySavedSearch(result.allow);
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                              }
                              if (pref.option === 'notifyCustom') {
                                   console.log('Updated custom notifications');
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                                   setNotifyCustom(result.allow);
                              }
                              if (pref.option === 'notifyAccount') {
                                   console.log('Updated account notifications');
                                   _.set(prevSettings, prevSettings.allow, result.allow);
                                   setNotifyAccount(result.allow);
                              }
                         }
                    }
               }
               setLoading(false);
          }
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <ScrollView p="$5">
               <VStack alignItems="stretch">
                    <Box>
                         <Text color={textColor}>{getTermFromDictionary(language, 'device_set_to')}</Text>

                         <Heading mb="$1" color={textColor}>
                              {permissionStatus === true ? getTermFromDictionary(language, 'allowed') : getTermFromDictionary(language, 'not_allowed')}
                         </Heading>
                         <Text color={textColor}>
                              {Constants.expoConfig.name} {permissionStatus === true ? getTermFromDictionary(language, 'allowed_notification') : getTermFromDictionary(language, 'not_allowed_notification')}
                         </Text>

                         <Text color={textColor} mt="$5">
                              {getTermFromDictionary(language, 'to_update_settings')}
                         </Text>
                         <NotificationPermissionUsage />
                         {permissionStatus === true && _.isObject(notificationSettings) && !_.isEmpty(notificationSettings) ? (
                              <Box mb="$5">
                                   <NotificationToggleAll revokeNotificationPermissions={revokeNotificationPermissions} addNotificationPermissions={addNotificationPermissions} setLoading={setLoading} notifySavedSearch={notifySavedSearch} setNotifySavedSearch={setNotifySavedSearch} notifyCustom={notifyCustom} setNotifyCustom={setNotifyCustom} notifyAccount={notifyAccount} setNotifyAccount={setNotifyAccount} />
                                   <FlatList
                                        data={Object.keys(notificationSettings)}
                                        renderItem={({ item }) => <NotificationToggle revokeNotificationPermissions={revokeNotificationPermissions} addNotificationPermissions={addNotificationPermissions} data={notificationSettings[item]} notifySavedSearch={notifySavedSearch} setNotifySavedSearch={setNotifySavedSearch} notifyCustom={notifyCustom} setNotifyCustom={setNotifyCustom} notifyAccount={notifyAccount} setNotifyAccount={setNotifyAccount} />}
                                        keyExtractor={(item, index) => index.toString()}
                                   />
                              </Box>
                         ) : null}
                    </Box>
                    <NotificationPermissionUpdate permissionStatus={permissionStatus} setPermissionStatus={setPermissionStatus} />
               </VStack>
          </ScrollView>
     );
};

const NotificationPermissionUsage = () => {
     const { language } = React.useContext(LanguageContext);
     const { textColor } = React.useContext(ThemeContext);

     return (
          <Accordion variant="unfilled" w="100%" size="sm">
               <AccordionItem value="description">
                    <AccordionHeader>
                         <AccordionTrigger px="$0">
                              {({ isExpanded }) => {
                                   return (
                                        <>
                                             <AccordionTitleText color={textColor}>{getTermFromDictionary(language, 'how_we_use_notification_title')}</AccordionTitleText>
                                             {isExpanded ? <AccordionIcon as={ChevronUp} ml="$3" color={textColor} /> : <AccordionIcon as={ChevronDown} ml="$3" color={textColor} />}
                                        </>
                                   );
                              }}
                         </AccordionTrigger>
                    </AccordionHeader>
                    <AccordionContent px="$0">
                         <AccordionContentText color={textColor}>
                              {Constants.expoConfig.name} {getTermFromDictionary(language, 'how_we_use_notification_body')}
                         </AccordionContentText>
                    </AccordionContent>
               </AccordionItem>
          </Accordion>
     );
};

const NotificationPermissionUpdate = (payload) => {
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     const { language } = React.useContext(LanguageContext);
     const [showAlertDialog, setShowAlertDialog] = React.useState(false);
     const [manuallyPromptPermission, setManuallyPromptPermission] = React.useState(false);
     const setPermissionStatus = payload.setPermissionStatus;
     const permissionStatus = payload.permissionStatus;
     const addNotificationPermissions = payload.addNotificationPermissions;
     const revokeNotificationPermissions = payload.revokeNotificationPermissions;

     const manuallyRequestPermission = async () => {
          await Notifications.requestPermissionsAsync().then(async () => {
               setManuallyPromptPermission(false);
               const { status } = await Notifications.getPermissionsAsync();
               setPermissionStatus(status === 'granted');
               if (status === 'granted') {
                    console.log('Status manually granted. Adding permissions.');
                    await addNotificationPermissions();
               }

               if (status === 'denied') {
                    console.log('Status manually denied. Revoking permissions.');
                    await revokeNotificationPermissions();
               }
          });
     };

     React.useEffect(() => {
          (async () => {
               const { status } = await Notifications.getPermissionsAsync();
               setPermissionStatus(status === 'granted');
               if (status === 'undetermined') {
                    console.log('Status undetermined. Manually requesting permissions.');
                    setManuallyPromptPermission(true);
               }
               if (status === 'granted') {
                    await addNotificationPermissions();
               }

               if (status === 'denied') {
                    await revokeNotificationPermissions();
               }
          })();
     }, []);

     return (
          <Center>
               <Button
                    onPress={async () => {
                         if (manuallyPromptPermission) {
                              await manuallyRequestPermission();
                         } else {
                              setShowAlertDialog(true);
                         }
                    }}
                    bgColor={theme['colors']['primary']['500']}>
                    <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'update_device_settings')}</ButtonText>
               </Button>
               <AlertDialog
                    isOpen={showAlertDialog}
                    onClose={() => {
                         setShowAlertDialog(false);
                    }}>
                    <AlertDialogBackdrop />
                    <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                         <AlertDialogHeader>
                              <Heading color={textColor}>{getTermFromDictionary(language, 'update_device_settings')}</Heading>
                         </AlertDialogHeader>
                         <AlertDialogBody>
                              <Text color={textColor}>{Platform.OS === 'android' ? getTermFromDictionary(language, 'update_notification_android') : getTermFromDictionary(language, 'update_notification_ios')}</Text>
                         </AlertDialogBody>
                         <AlertDialogFooter>
                              <ButtonGroup flexDirection="column" alignItems="stretch" w="100%">
                                   <Button
                                        onPress={() => {
                                             Linking.openSettings();
                                             setShowAlertDialog(false);
                                        }}
                                        bgColor={theme['colors']['primary']['500']}>
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'open_device_settings')}</ButtonText>
                                   </Button>
                                   <Button variant="link" onPress={() => setShowAlertDialog(false)}>
                                        <ButtonText color={textColor}>{getTermFromDictionary(language, 'not_now')}</ButtonText>
                                   </Button>
                              </ButtonGroup>
                         </AlertDialogFooter>
                    </AlertDialogContent>
               </AlertDialog>
          </Center>
     );
};

const NotificationToggle = (data) => {
     const queryClient = useQueryClient();
     const { updateAppPreferences, expoToken } = React.useContext(UserContext);
     const { textColor } = React.useContext(ThemeContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const preference = data.data;
     const { notifySavedSearch, setNotifySavedSearch, notifyCustom, setNotifyCustom, notifyAccount, setNotifyAccount, revokeNotificationPermissions, addNotificationPermissions } = data;

     console.log(preference);
     let defaultToggleState = preference.allow === 1 || preference.allow === '1' || preference.allow === true || preference.allow === 'true';

     if (preference.option === 'notifySavedSearch') {
          defaultToggleState = notifySavedSearch;
          console.log('notifySavedSearch: ' + notifySavedSearch);
     } else if (preference.option === 'notifyCustom') {
          defaultToggleState = notifyCustom;
     } else if (preference.option === 'notifyAccount') {
          defaultToggleState = notifyAccount;
     }

     console.log(preference.option + ': ' + preference.allow + '|' + defaultToggleState);

     const [toggled, setToggle] = React.useState(defaultToggleState);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const updatePreference = async (pref, value) => {
          let allowNotification = true;
          if (value === 0 || value === 'false' || value === false) {
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
               await getAppPreferencesForUser(library.baseUrl, language).then((result) => {
                    updateAppPreferences(data);
               });

               queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          }
     };

     return (
          <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
               <Text color={textColor}>{preference.label}</Text>
               <Switch
                    onToggle={() => {
                         toggleSwitch();
                         updatePreference(preference.option, preference.allow).then((r) => {
                              console.log(r);
                         });
                    }}
                    value={toggled}
               />
          </HStack>
     );
};

const NotificationToggleAll = (data) => {
     const queryClient = useQueryClient();
     const { language } = React.useContext(LanguageContext);
     const { updateAppPreferences, expoToken } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { textColor } = React.useContext(ThemeContext);
     const { notifySavedSearch, setNotifySavedSearch, notifyCustom, setNotifyCustom, notifyAccount, setNotifyAccount, setLoading, revokeNotificationPermissions, addNotificationPermissions } = data;

     let defaultToggleState = notifyCustom && notifyAccount && notifySavedSearch;
     const [toggled, setToggle] = React.useState(defaultToggleState);
     const toggleSwitch = () => setToggle((previousState) => !previousState);

     const enableAllNotifications = async (value) => {
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
               await getAppPreferencesForUser(library.baseUrl, language).then((result) => {
                    updateAppPreferences(data);
               });

               queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          }
          setLoading(false);
     };

     return (
          <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
               <Text bold color={textColor}>
                    {getTermFromDictionary(language, 'notifications_enable_all')}
               </Text>
               <Switch
                    onToggle={() => {
                         toggleSwitch();
                         enableAllNotifications(!toggled).then((r) => {
                              console.log(r);
                         });
                    }}
                    value={toggled}
               />
          </HStack>
     );
};