import { MaterialIcons } from '@expo/vector-icons';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import { Box, FlatList, HStack, Icon, Pressable, Text } from 'native-base';
import React, { Component } from 'react';

// custom components and helper files
import { userContext } from '../../../context/user';
import { translate } from '../../../translations/translations';
import { LIBRARY } from '../../../util/loadLibrary';
import { LibrarySystemContext } from '../../../context/initialContext';

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
                         title: translate('user_profile.home_screen_settings'),
                         path: 'SettingsHomeScreen',
                         external: false,
                         icon: 'chevron-right',
                         version: '22.06.00',
                    },
                    {
                         key: '3',
                         title: translate('user_profile.manage_notifications'),
                         path: 'SettingsNotifications',
                         external: false,
                         icon: 'chevron-right',
                         version: '22.09.00',
                    },
               ],
          };
     }

     componentDidMount = async () => {
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
          this.setState({
               isLoading: false,
          });
     };

     renderItem = (item, patronId, library) => {
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
                                   {item.title}
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
                                        {item.title}
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
                                        {item.title}
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
                              <Box flex={1} safeArea={3}>
                                   <FlatList data={this.state.defaultMenuItems} renderItem={({ item }) => this.renderItem(item, user.id, library)} keyExtractor={(item, index) => index.toString()} />
                              </Box>
                         )}
                    </LibrarySystemContext.Consumer>
               </>
          );
     }
}