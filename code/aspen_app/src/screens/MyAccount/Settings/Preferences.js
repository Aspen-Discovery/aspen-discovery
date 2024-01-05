import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import { Box, Divider, Heading, HStack, Icon, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';

// custom components and helper files
import { navigate } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';

export const PreferencesScreen = () => {
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { user, expoToken, aspenToken, updateExpoToken, updateAspenToken } = React.useContext(UserContext);

     const defaultMenuItems = [
          {
               key: '0',
               title: getTermFromDictionary(language, 'manage_browse_categories'),
               path: 'SettingsHomeScreen',
               external: false,
               minVersion: '22.06.00',
               icon: 'chevron-right',
          },
          {
               key: '1',
               title: getTermFromDictionary(language, 'notifications_manage'),
               path: 'SettingsNotifications',
               external: false,
               minVersion: '22.09.00',
               icon: 'chevron-right',
          },
     ];

     React.useEffect(() => {
          const updateTokens = navigation.addListener('focus', async () => {
               if (Constants.isDevice) {
                    const token = (await Notifications.getExpoPushTokenAsync()).data;
                    if (token) {
                         if (!_.isEmpty(user.notification_preferences)) {
                              const tokenStorage = user.notification_preferences;
                              if (_.find(tokenStorage, _.matchesProperty('token', token))) {
                                   updateAspenToken(true);
                                   updateExpoToken(token);
                              }
                         }
                    }
               }
          });
          return updateTokens;
     }, [navigation]);

     const openWebsite = async (url, minVersion) => {
          WebBrowser.openBrowserAsync(url);
     };

     const onPressMenuItem = (path, minVersion) => {
          const thisVersion = formatDiscoveryVersion(library.discoveryVersion);
          let screen = path;
          if (thisVersion >= minVersion) {
               if (path === 'SettingsHomeScreen' && thisVersion >= '22.12.00') {
                    screen = 'SettingsBrowseCategories';
               }
               if (path === 'SettingsNotifications' && thisVersion >= '23.01.00') {
                    screen = 'SettingsNotificationOptions';
               }
          }
          navigate(screen, {
               libraryUrl: library.baseUrl,
               patronId: user.id,
               screen,
               user,
               pushToken: expoToken,
               aspenToken,
          });
     };

     const menuItem = (item) => {
          if (item.external) {
               return (
                    <Pressable
                         px="2"
                         py="3"
                         onPress={() => {
                              openWebsite(item.path, item.minVersion);
                         }}>
                         <HStack space="1" alignItems="center">
                              <Icon as={MaterialIcons} name={item.icon} size="7" />
                              <Text bold>{item.title}</Text>
                         </HStack>
                    </Pressable>
               );
          } else {
               return (
                    <Pressable
                         px="2"
                         py="3"
                         onPress={() => {
                              onPressMenuItem(item.path, item.minVersion);
                         }}>
                         <HStack space="1" alignItems="center">
                              <Icon as={MaterialIcons} name={item.icon} size="7" />
                              <Text bold>{item.title}</Text>
                         </HStack>
                    </Pressable>
               );
          }
     };

     return (
          <Box safeArea={5}>
               <VStack divider={<Divider />} space="4">
                    <VStack space="3">
                         <Heading>{getTermFromDictionary(language, 'preferences')}</Heading>
                         <VStack>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'manage_browse_categories')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'language')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'appearance')}</Text>
                                   </HStack>
                              </Pressable>
                         </VStack>
                    </VStack>
                    <VStack space="3">
                         <Heading>{getTermFromDictionary(language, 'device_settings')}</Heading>
                         <VStack>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'app_settings')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'notifications')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3">
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'permissions')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3" onPress={() => navigate('Support')}>
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'support')}</Text>
                                   </HStack>
                              </Pressable>
                         </VStack>
                    </VStack>
               </VStack>
          </Box>
     );
};