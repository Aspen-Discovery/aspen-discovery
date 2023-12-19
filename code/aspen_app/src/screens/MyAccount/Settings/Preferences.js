import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import { Box, Center, Divider, FlatList, HStack, Icon, Pressable, Text, VStack } from 'native-base';
import React, { Component } from 'react';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';

// custom components and helper files
import { userContext } from '../../../context/user';
import { navigate } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { GLOBALS } from '../../../util/globals';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';

export const PreferencesScreen = () => {
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
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
          <Box pt={5}>
               <VStack space="4">
                    <FlatList data={defaultMenuItems} renderItem={({ item }) => menuItem(item)} keyExtractor={(item, index) => index.toString()} />
               </VStack>
               <Divider mt={5} />
               <Center>
                    <Text mt={10} fontSize="xs" bold>
                         {getTermFromDictionary(language, 'app_name')}
                         <Text color="coolGray.600" _dark={{ color: 'warmGray.400' }} ml={1}>
                              {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel}]
                         </Text>
                    </Text>
                    {library.discoveryVersion ? (
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'aspen_discovery')}
                              <Text color="coolGray.600" _dark={{ color: 'warmGray.400' }} ml={1}>
                                   {library.discoveryVersion}
                              </Text>
                         </Text>
                    ) : null}
               </Center>
          </Box>
     );
};

export default class Preferences extends Component {
     static contextType = userContext;

     constructor(props) {
          super(props);
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
               hasUpdated: false,
               isRefreshing: false,
               expoToken: null,
               aspenToken: null,
               defaultMenuItems: [
                    {
                         key: '1',
                         title: 'manage_browse_categories',
                         path: 'SettingsHomeScreen',
                         external: false,
                         icon: 'chevron-right',
                         version: '22.06.00',
                    },
                    {
                         key: '3',
                         title: 'notifications_manage',
                         path: 'SettingsNotifications',
                         external: false,
                         icon: 'chevron-right',
                         version: '22.09.00',
                    },
               ],
          };
     }

     componentDidMount = async () => {
          const { navigation } = this.props;
          if (Constants.isDevice) {
               const expoToken = (await Notifications.getExpoPushTokenAsync()).data;
               if (expoToken) {
                    if (!_.isEmpty(this.context.user.notification_preferences)) {
                         const tokenStorage = this.context.user.notification_preferences;
                         if (_.find(tokenStorage, _.matchesProperty('token', expoToken))) {
                              this.setState({
                                   expoToken,
                                   aspenToken: true,
                              });
                         }
                    }
               }
          }

          navigation.setOptions({
               headerLeft: () => <Box />,
          });

          this.setState({
               isLoading: false,
          });
     };

     renderItem = (item, patronId, library, language) => {
          if (item.external && library.version >= item.version) {
               return (
                    <Pressable
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         py="3"
                         onPress={() => {
                              this.openWebsite(item.path);
                         }}>
                         <HStack space="1" alignItems="center">
                              <Icon as={MaterialIcons} name={item.icon} size="7" />
                              <Text
                                   _dark={{ color: 'warmGray.50' }}
                                   color="coolGray.800"
                                   bold
                                   fontSize={{
                                        base: 'md',
                                        lg: 'lg',
                                   }}>
                                   {getTermFromDictionary(language, item.title)}
                              </Text>
                         </HStack>
                    </Pressable>
               );
          } else if (library.version >= item.version) {
               if (item.path === 'SettingsHomeScreen') {
                    let screenPath = item.path;
                    if (library.version >= '22.12.00') {
                         screenPath = 'SettingsBrowseCategories';
                    }
                    return (
                         <Pressable
                              borderBottomWidth="1"
                              _dark={{ borderColor: 'gray.600' }}
                              borderColor="coolGray.200"
                              py="3"
                              onPress={() => {
                                   this.onPressMenuItem(screenPath, patronId, library.url);
                              }}>
                              <HStack space="1" alignItems="center">
                                   <Icon as={MaterialIcons} name={item.icon} size="7" />
                                   <Text
                                        _dark={{ color: 'warmGray.50' }}
                                        color="coolGray.800"
                                        bold
                                        fontSize={{
                                             base: 'md',
                                             lg: 'lg',
                                        }}>
                                        {getTermFromDictionary(language, item.title)}
                                   </Text>
                              </HStack>
                         </Pressable>
                    );
               }
               if (item.path === 'SettingsNotifications') {
                    let screenPath = item.path;
                    if (library.version >= '23.01.00') {
                         screenPath = 'SettingsNotificationOptions';
                    }
                    return (
                         <Pressable
                              borderBottomWidth="1"
                              _dark={{ borderColor: 'gray.600' }}
                              borderColor="coolGray.200"
                              py="3"
                              onPress={() => {
                                   this.onPressMenuItem(screenPath, patronId, library.url);
                              }}>
                              <HStack space="1" alignItems="center">
                                   <Icon as={MaterialIcons} name={item.icon} size="7" />
                                   <Text
                                        _dark={{ color: 'warmGray.50' }}
                                        color="coolGray.800"
                                        bold
                                        fontSize={{
                                             base: 'md',
                                             lg: 'lg',
                                        }}>
                                        {getTermFromDictionary(language, item.title)}
                                   </Text>
                              </HStack>
                         </Pressable>
                    );
               }
               return (
                    <Pressable
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         py="3"
                         onPress={() => {
                              this.onPressMenuItem(item.path, patronId, library.url);
                         }}>
                         <HStack space="1" alignItems="center">
                              <Icon as={MaterialIcons} name={item.icon} size="7" />
                              <Text
                                   _dark={{ color: 'warmGray.50' }}
                                   color="coolGray.800"
                                   bold
                                   fontSize={{
                                        base: 'md',
                                        lg: 'lg',
                                   }}>
                                   {item.title}
                              </Text>
                         </HStack>
                    </Pressable>
               );
          } else {
               return null;
          }
     };

     onPressMenuItem = (path, patronId, libraryUrl) => {
          this.props.navigation.navigate(path, {
               libraryUrl,
               patronId,
               user: this.context.user,
               pushToken: this.state.expoToken,
               aspenToken: this.state.aspenToken,
          });
     };

     openWebsite = async (url) => {
          WebBrowser.openBrowserAsync(url);
     };

     render() {
          const user = this.context.user;
          return (
               <>
                    <LibrarySystemContext.Consumer>
                         {(library) => (
                              <LanguageContext.Consumer>
                                   {(language) => (
                                        <Box flex={1} safeArea={3}>
                                             <FlatList data={this.state.defaultMenuItems} renderItem={({ item }) => this.renderItem(item, user.id, library, language.language)} keyExtractor={(item, index) => index.toString()} />
                                        </Box>
                                   )}
                              </LanguageContext.Consumer>
                         )}
                    </LibrarySystemContext.Consumer>
               </>
          );
     }
}