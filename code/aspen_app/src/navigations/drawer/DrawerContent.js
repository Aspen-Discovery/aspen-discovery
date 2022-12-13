import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { DrawerContentScrollView } from '@react-navigation/drawer';
import Constants from 'expo-constants';
import * as ExpoLinking from 'expo-linking';
import * as Notifications from 'expo-notifications';
import _ from 'lodash';
import { Badge, Box, Button, Container, Divider, HStack, Icon, Image, Menu, Pressable, Text, VStack } from 'native-base';
import React, { Component, useState } from 'react';
import { Linking } from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';

// custom components and helper files
import { showILSMessage } from '../../components/Notifications';
import { AuthContext } from '../../components/navigation';
import { UseColorMode } from '../../themes/theme';
import { getLanguageDisplayName } from '../../translations/TranslationService';
import { translate } from '../../translations/translations';
import { saveLanguage } from '../../util/accountActions';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion, LIBRARY } from '../../util/loadLibrary';
import { refreshProfile, reloadProfile } from '../../util/api/user';
import { getILSMessages, PATRON } from '../../util/loadPatron';
import { LibrarySystemContext, UserContext } from '../../context/initialContext';
import { navigateStack } from '../../helpers/RootNavigator';

Notifications.setNotificationHandler({
     handleNotification: async () => ({
          shouldShowAlert: true,
          shouldPlaySound: true,
          shouldSetBadge: true,
     }),
});

const prefix = ExpoLinking.createURL('/');

export const DrawerContent = () => {
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [notifications, setNotifications] = React.useState([]);
     const [messages, setILSMessages] = React.useState([]);

     React.useEffect(() => {
          const subscription = Notifications.addNotificationReceivedListener((notification) => {
               handleNewNotification(notification);
          });
          return () => subscription.remove();
     }, []);

     React.useEffect(() => {
          const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
               handleNewNotificationResponse(response);
          });
          return () => subscription.remove();
     }, []);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await reloadProfile(library.baseUrl).then((result) => {
                         if (user !== result) {
                              updateUser(result);
                         }
                    });

                    await getILSMessages(library.baseUrl).then((result) => {
                         setILSMessages(result);
                    });
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const handleNewNotification = (notification) => {
          setNotifications(notification);
     };

     const handleNewNotificationResponse = async (response) => {
          await addStoredNotification(response);
          let url = decodeURIComponent(response.notification.request.content.data.url).replace(/\+/g, ' ');
          url = url.concat('&results=[]');
          url = url.replace('aspen-lida://', prefix);

          const supported = await Linking.canOpenURL(url);
          if (supported) {
               try {
                    console.log('Opening url...');
                    await Linking.openURL(url);
               } catch (e) {
                    console.log('Could not open url');
                    console.log(e);
               }
          } else {
               console.log('Could not open url');
          }
     };

     const displayFinesAlert = () => {
          if (user.fines) {
               if (!_.includes(user.fines, '0.00')) {
                    const message = 'Your accounts have ' + user.fines + ' in fines.';
                    return showILSMessage('warning', message);
               }
          }

          return null;
     };

     const displayILSMessages = () => {
          if (messages) {
               if (_.isArray(messages)) {
                    return messages.map((obj) => {
                         if (obj.message) {
                              return showILSMessage(obj.messageStyle, obj.message);
                         }
                    });
               }
          }

          return null;
     };

     return (
          <DrawerContentScrollView>
               <VStack space="4" my="2" mx="1">
                    <UserProfileOverview />

                    {displayFinesAlert()}
                    {displayILSMessages()}

                    <Divider />

                    <VStack divider={<Divider />} space="4">
                         <VStack>
                              <Checkouts />
                              <Holds />
                              <UserLists />
                              <SavedSearches />
                              <ReadingHistory />
                         </VStack>

                         <VStack space="3">
                              <VStack>
                                   <UserProfile />
                                   <LinkedAccounts />
                                   <UserPreferences />
                              </VStack>
                         </VStack>
                    </VStack>

                    {/* logout button, color mode switcher, language switcher */}
                    <VStack space={3} alignItems="center">
                         <HStack space={2}>
                              <LogOutButton />
                         </HStack>
                         <UseColorMode />
                    </VStack>
               </VStack>
          </DrawerContentScrollView>
     );
};

const UserProfileOverview = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     let icon;
     if (!_.isNull(library.logoApp)) {
          icon = library.logoApp;
     } else if (!_.isUndefined(library.favicon)) {
          icon = library.favicon;
     } else {
          icon = Constants.manifest2?.extra?.expoClient?.ios?.icon ?? Contants.manifest.ios.icon;
     }

     return (
          <Box px="4">
               <HStack space={3} alignItems="center">
                    <Image source={{ uri: icon }} fallbackSource={require('../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} rounded="8" />
                    <Box>
                         {user && user.displayName ? (
                              <Text bold fontSize="14">
                                   {user.displayName}
                              </Text>
                         ) : null}

                         {library && library.displayName ? (
                              <Text fontSize="12" fontWeight="500">
                                   {library.displayName}
                              </Text>
                         ) : null}
                         <HStack space={1} alignItems="center">
                              <Icon as={MaterialIcons} name="credit-card" size="xs" />
                              {user && user.cat_username ? (
                                   <Text fontSize="12" fontWeight="500">
                                        {user.cat_username}
                                   </Text>
                              ) : null}
                         </HStack>
                    </Box>
               </HStack>
          </Box>
     );
};

const Checkouts = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     return (
          <Pressable
               px="2"
               py="2"
               rounded="md"
               onPress={() => {
                    navigateStack('AccountScreenTab', 'MyCheckouts', {
                         libraryUrl: library.baseUrl,
                         hasPendingChanges: false,
                    });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <VStack w="100%">
                         <Text fontWeight="500">
                              {translate('checkouts.title')} {user ? <Text bold>({user.numCheckedOut})</Text> : null}
                         </Text>
                    </VStack>
               </HStack>
               {user.numOverdue > 0 ? (
                    <Container>
                         <Badge colorScheme="error" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                              {translate('checkouts.overdue_summary', {
                                   count: user.numOverdue,
                              })}
                         </Badge>
                    </Container>
               ) : null}
          </Pressable>
     );
};

const Holds = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     return (
          <Pressable
               px="2"
               py="3"
               rounded="md"
               onPress={() => {
                    navigateStack('AccountScreenTab', 'MyHolds', { libraryUrl: library.baseUrl, hasPendingChanges: false });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <VStack w="100%">
                         <Text fontWeight="500">
                              {translate('holds.title')} {user ? <Text bold>({user.numHolds})</Text> : null}
                         </Text>
                    </VStack>
               </HStack>
               {user.numHoldsAvailable > 0 ? (
                    <Container>
                         <Badge colorScheme="success" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                              {translate('holds.ready_for_pickup', { count: user.numHoldsAvailable })}
                         </Badge>
                    </Container>
               ) : null}
          </Pressable>
     );
};

const UserLists = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (version >= '22.08.00') {
          return (
               <Pressable
                    px="2"
                    py="3"
                    rounded="md"
                    onPress={() => {
                         navigateStack('AccountScreenTab', 'MyLists', {
                              libraryUrl: library.baseUrl,
                              hasPendingChanges: false,
                         });
                    }}>
                    <HStack space="1" alignItems="center">
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                         <VStack w="100%">
                              <Text fontWeight="500">
                                   {translate('user_profile.my_lists')} {user ? <Text bold>({user.numLists})</Text> : null}
                              </Text>
                         </VStack>
                    </HStack>
               </Pressable>
          );
     }

     return (
          <Pressable
               px="2"
               py="3"
               rounded="md"
               onPress={() => {
                    navigateStack('MyListsStack', 'MyLists', { libraryUrl: library.baseUrl, hasPendingChanges: false });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <VStack w="100%">
                         <Text fontWeight="500">{translate('user_profile.my_lists')}</Text>
                    </VStack>
               </HStack>
          </Pressable>
     );
};

const SavedSearches = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (version >= '22.08.00') {
          return (
               <Pressable
                    px="2"
                    py="3"
                    rounded="md"
                    onPress={() => {
                         navigateStack('AccountScreenTab', 'MySavedSearches', {
                              libraryUrl: library.baseUrl,
                              hasPendingChanges: false,
                         });
                    }}>
                    <HStack space="1" alignItems="center">
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                         <VStack w="100%">
                              <Text fontWeight="500">
                                   {translate('user_profile.saved_searches')} {user ? <Text bold>({user.numSavedSearches})</Text> : null}
                              </Text>
                         </VStack>
                    </HStack>
                    {user.numSavedSearchesNew > 0 ? (
                         <Container>
                              <Badge colorScheme="warning" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                                   {translate('user_profile.saved_searches_updated', {
                                        count: user.numSavedSearchesNew,
                                   })}
                              </Badge>
                         </Container>
                    ) : null}
               </Pressable>
          );
     }

     return null;
};

const ReadingHistory = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (version >= '22.12.00') {
          return (
               <Pressable
                    px="2"
                    py="3"
                    rounded="md"
                    onPress={() => {
                         navigateStack('AccountScreenTab', 'MyReadingHistory', {
                              libraryUrl: library.baseUrl,
                              hasPendingChanges: false,
                         });
                    }}>
                    <HStack space="1" alignItems="center">
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                         <VStack w="100%">
                              <Text fontWeight="500">
                                   {translate('reading_history.title')} <Text bold>(500)</Text>
                              </Text>
                         </VStack>
                    </HStack>
               </Pressable>
          );
     }

     return null;
};

const UserProfile = () => {
     const { library } = React.useContext(LibrarySystemContext);

     return (
          <Pressable
               px="2"
               py="3"
               onPress={() => {
                    navigateStack('AccountScreenTab', 'MyProfile', {
                         libraryUrl: library.baseUrl,
                         hasPendingChanges: false,
                    });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <Text fontWeight="500">{translate('user_profile.profile')}</Text>
               </HStack>
          </Pressable>
     );
};

const LinkedAccounts = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (library.allowLinkedAccounts === '1') {
          return (
               <Pressable
                    px="2"
                    py="2"
                    onPress={() =>
                         navigateStack('AccountScreenTab', 'MyLinkedAccounts', {
                              libraryUrl: library.baseUrl,
                              hasPendingChanges: false,
                         })
                    }>
                    <HStack space="1" alignItems="center">
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                         <Text fontWeight="500">{translate('user_profile.linked_accounts')}</Text>
                    </HStack>
               </Pressable>
          );
     }

     return null;
};

const UserPreferences = () => {
     const { library } = React.useContext(LibrarySystemContext);

     return (
          <Pressable
               px="2"
               py="3"
               onPress={() => {
                    navigateStack('AccountScreenTab', 'MyPreferences', {
                         libraryUrl: library.baseUrl,
                         hasPendingChanges: false,
                    });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <Text fontWeight="500">{translate('user_profile.preferences')}</Text>
               </HStack>
          </Pressable>
     );
};

async function getStoredNotifications() {
     try {
          const notifications = await AsyncStorage.getItem('@notifications');
          return notifications != null ? JSON.parse(notifications) : null;
     } catch (e) {
          console.log(e);
     }
}

async function createNotificationStorage(message) {
     try {
          const array = [];
          array.push(message);
          const notification = JSON.stringify(array);
          await AsyncStorage.setItem('@notifications', notification);
     } catch (e) {
          console.log(e);
     }
}

async function addStoredNotification(message) {
     await getStoredNotifications().then(async (response) => {
          if (response) {
               response.push(message);
               try {
                    await AsyncStorage.setItem('@notifications', JSON.stringify(response));
               } catch (e) {
                    console.log(e);
               }
          } else {
               await createNotificationStorage(message);
          }
     });
}

function LogOutButton() {
     const { signOut } = React.useContext(AuthContext);

     return (
          <Button size="md" colorScheme="secondary" onPress={signOut} leftIcon={<Icon as={MaterialIcons} name="logout" size="xs" />}>
               {translate('general.logout')}
          </Button>
     );
}

const ReloadProfileButton = (props) => {
     return (
          <Button size="xs" colorScheme="tertiary" onPress={() => props.handleRefreshProfile(props.libraryUrl)} variant="ghost" leftIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>
               {translate('general.refresh_account')}
          </Button>
     );
};

const LanguageSwitcher = (props) => {
     const initialLabel = props.initial;
     const [language, setLanguage] = useState(PATRON.language);
     const [label, setLabel] = useState(initialLabel);

     const updateLanguage = async (newVal) => {
          await saveLanguage(newVal);
          setLanguage(newVal);
          setLabel(getLanguageDisplayName(newVal));
     };

     return (
          <Box>
               <Menu
                    closeOnSelect
                    w="190"
                    trigger={(triggerProps) => {
                         return (
                              <Pressable {...triggerProps}>
                                   <Button size="md" colorScheme="secondary" leftIcon={<Icon as={MaterialIcons} name="language" size="xs" />} {...triggerProps}>
                                        {label}
                                   </Button>
                              </Pressable>
                         );
                    }}>
                    <Menu.OptionGroup defaultValue={PATRON.language} title="Select a Language" type="radio" onChange={(val) => updateLanguage(val)}>
                         {LIBRARY.languages.map((language) => {
                              return <Menu.ItemOption value={language.code}>{language.displayName}</Menu.ItemOption>;
                         })}
                    </Menu.OptionGroup>
               </Menu>
          </Box>
     );
};

/*
export class DrawerContentOld extends Component {
     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               displayLanguage: '',
               user: this.context.user,
               location: this.props.locationContext ?? [],
               library: this.props.libraryContext ?? [],
               messages: [],
               languages: [],
               languageDisplayLabel: 'English',
               asyncLoaded: false,
               notification: {},
               fines: this.context.user.fines ?? 0,
               language: 'en',
               num: {
                    checkedOut: this.context.user.numCheckedOut ?? 0,
                    holds: this.context.user.numHolds ?? 0,
                    lists: this.context.user.numLists ?? 0,
                    overdue: this.context.user.numOverdue ?? 0,
                    ready: this.context.user.numHoldsAvailable ?? 0,
                    savedSearches: this.context.user.numSavedSearches ?? 0,
                    updatedSearches: this.context.user.numSavedSearchesNew ?? 0,
                    linkedAccounts: this.context.user.numLinkedAccounts ?? 0,
               },
          };
          this._isMounted = false;
     }

     componentDidMount = async () => {
          this._isMounted = true;
          const languageDisplay = getLanguageDisplayName(PATRON.language);

          if (this._isMounted) {
               this._getLastListUsed();
          }

          if (this.state.library) {
               libraryUrl = this.state.library.baseUrl;
          }

          this.setState({
               isLoading: false,
               messages: PATRON.messages,
               languageDisplayLabel: languageDisplay,
          });

          Notifications.addNotificationReceivedListener(this._handleNotification);
          Notifications.addNotificationResponseReceivedListener(this._handleNotificationResponse);

          this.interval = setInterval(() => {
               if (this._isMounted) {
                    this.loadProfile();
                    //this.loadLanguages();
               }
          }, GLOBALS.timeoutSlow);

          return () => {
               clearInterval(this.interval);
          };
     };

     _handleNotification = (notification) => {
          this.setState({notification});
     };

     _handleNotificationResponse = async (response) => {
          await this._addStoredNotification(response);
          let url = decodeURIComponent(response.notification.request.content.data.url).replace(/\+/g, ' ');
          url = url.concat('&results=[]');
          url = url.replace('aspen-lida://', prefix);

          const supported = await Linking.canOpenURL(url);
          if (supported) {
               try {
                    console.log('Opening url...');
                    await Linking.openURL(url);
               } catch (e) {
                    console.log('Could not open url');
                    console.log(e);
               }
          } else {
               console.log('Could not open url');
          }
     };

     _getStoredNotifications = async () => {
          try {
               const notifications = await AsyncStorage.getItem('@notifications');
               return notifications != null ? JSON.parse(notifications) : null;
          } catch (e) {
               console.log(e);
          }
     };

     _createNotificationStorage = async (message) => {
          try {
               const array = [];
               array.push(message);
               const notification = JSON.stringify(array);
               await AsyncStorage.setItem('@notifications', notification);
          } catch (e) {
               console.log(e);
          }
     };

     _addStoredNotification = async (message) => {
          const storage = await this._getStoredNotifications().then(async (response) => {
               if (response) {
                    response.push(message);
                    try {
                         await AsyncStorage.setItem('@notifications', JSON.stringify(response));
                    } catch (e) {
                         console.log(e);
                    }
               } else {
                    await this._createNotificationStorage(message);
               }
          });
     };

     _getLastListUsed = () => {
          if (this.context.user) {
               PATRON.listLastUsed = this.context.user.lastListUsed;
          }
     };

     componentWillUnmount() {
          this._isMounted = false;
          clearInterval(this.interval);
     }

     componentDidUpdate(prevProps, prevState) {
          if (prevState.messages !== PATRON.messages) {
               this.setState({
                    messages: PATRON.messages,
               });
          }

          if (prevState.language !== PATRON.language) {
               this.setState({
                    language: PATRON.language,
               });
          }
     }

     handleNavigation = (stack, screen) => {
          this.props.navigation.navigate(stack, {
               screen,
               params: {
                    libraryUrl: libraryUrl,
                    hasPendingChanges: false,
               },
          });
     };

     loadProfile = async () => {
          await refreshProfile(libraryUrl).then((response) => {
               this.context.user = response;
               this.setState({
                    user: response,
               });
          });
     };

     displayFinesMessage = () => {
          if (!_.includes(this.state.fines, '0.00')) {
               const message = 'Your accounts have ' + this.state.fines + ' in fines.';
               return showILSMessage('warning', message);
          }
     };

     displayILSMessages = (messages) => {
          if (_.isArray(messages) === true) {
               return messages.map((item) => {
                    if (item.message) {
                         return showILSMessage(item.messageStyle, item.message);
                    }
               });
          } else {
               return null;
          }
     };

     handleRefreshProfile = async (libraryUrl) => {
          await reloadProfile(libraryUrl).then((response) => {
               this.context.user = response;
          });
          await getILSMessages(libraryUrl).then((response) => {
               this.setState({
                    messages: response,
               });
          });
     };

     render() {
          const { messages, fines } = this.state;
          const { checkedOut, holds, overdue, ready, lists, savedSearches, updatedSearches, linkedAccounts } = this.state.num;
          const user = this.context.user;

          let library = JSON.parse(this.props.libraryContext);
          library = library.library;
          let discoveryVersion = LIBRARY.version;
          let icon;
          if (typeof library !== 'undefined') {
               if (library.logoApp) {
                    icon = library.logoApp;
               } else if (library.favicon) {
                    icon = library.favicon;
               } else {
                    icon = Constants.manifest2?.extra?.expoClient?.ios?.icon ?? Contants.manifest.ios.icon;
               }
          }

          return (
              <DrawerContentScrollView>
                   <VStack space="4" my="2" mx="1">
                        <Box px="4">
                             <HStack space={3} alignItems="center">
                                  <Image source={{uri: icon}} fallbackSource={require('../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} rounded="8"/>
                                  <Box>
                                       {user && user.displayName ? (
                                           <Text bold fontSize="14">
                                                {user.displayName}
                                           </Text>
                                       ) : null}

                                       {library && library.displayName ? (
                                           <Text fontSize="12" fontWeight="500">
                                                {library.displayName}
                                           </Text>
                                       ) : null}
                                       <HStack space={1} alignItems="center">
                                            <Icon as={MaterialIcons} name="credit-card" size="xs"/>
                                            {user ? (
                                                <Text fontSize="12" fontWeight="500">
                                                     {user.cat_username}
                                                </Text>
                                            ) : null}
                                       </HStack>
                                  </Box>
                             </HStack>
                        </Box>

                        {fines ? this.displayFinesMessage() : null}
                        {messages ? this.displayILSMessages(messages) : null}

                        <Divider/>

                        <VStack divider={<Divider/>} space="4">
                             <VStack>
                                  <Pressable
                                      px="2"
                                      py="2"
                                      rounded="md"
                                      onPress={() => {
                                           this.handleNavigation('AccountScreenTab', 'CheckedOut', libraryUrl);
                                      }}>
                                       <HStack space="1" alignItems="center">
                                            <Icon as={MaterialIcons} name="chevron-right" size="7"/>
                                            <VStack w="100%">
                                                 <Text fontWeight="500">
                                                      {translate('checkouts.title')} {user ? <Text bold>({checkedOut})</Text> : null}
                                                 </Text>
                                            </VStack>
                                       </HStack>
                                       {overdue > 0 ? (
                                           <Container>
                                                <Badge colorScheme="error" ml={10} rounded="4px" _text={{fontSize: 'xs'}}>
                                                     {translate('checkouts.overdue_summary', {
                                                          count: overdue,
                                                     })}
                                                </Badge>
                                           </Container>
                                       ) : null}
                                  </Pressable>

                                  <Pressable
                                      px="2"
                                      py="3"
                                      rounded="md"
                                      onPress={() => {
                                           this.handleNavigation('AccountScreenTab', 'Holds', LIBRARY.url);
                                      }}>
                                       <HStack space="1" alignItems="center">
                                            <Icon as={MaterialIcons} name="chevron-right" size="7"/>
                                            <VStack w="100%">
                                                 <Text fontWeight="500">
                                                      {translate('holds.title')} {user ? <Text bold>({holds})</Text> : null}
                                                 </Text>
                                            </VStack>
                                       </HStack>
                                       {ready > 0 ? (
                                           <Container>
                                                <Badge colorScheme="success" ml={10} rounded="4px" _text={{fontSize: 'xs'}}>
                                                     {translate('holds.ready_for_pickup', {count: ready})}
                                                </Badge>
                                           </Container>
                                       ) : null}
                                  </Pressable>

                                  {discoveryVersion >= '22.08.00' ? (
                                      <Pressable
                                          px="2"
                                          py="3"
                                          rounded="md"
                                          onPress={() => {
                                               this.handleNavigation('AccountScreenTab', 'Lists', LIBRARY.url);
                                          }}>
                                           <HStack space="1" alignItems="center">
                                                <Icon as={MaterialIcons} name="chevron-right" size="7"/>
                                                <VStack w="100%">
                                                     <Text fontWeight="500">
                                                          {translate('user_profile.my_lists')} {user ? <Text bold>({lists})</Text> : null}
                                                     </Text>
                                                </VStack>
                                           </HStack>
                                      </Pressable>
                                  ) : (
                                      <Pressable
                                          px="2"
                                          py="3"
                                          rounded="md"
                                          onPress={() => {
                                               this.handleNavigation('AccountScreenTab', 'Lists', LIBRARY.url);
                                          }}>
                                           <HStack space="1" alignItems="center">
                                                <Icon as={MaterialIcons} name="chevron-right" size="7"/>
                                                <VStack w="100%">
                                                     <Text fontWeight="500">{translate('user_profile.my_lists')}</Text>
                                                </VStack>
                                           </HStack>
                                      </Pressable>
                                  )}

                                   {discoveryVersion >= '22.08.00' ? (
                                        <Pressable
                                             px="2"
                                             py="3"
                                             rounded="md"
                                             onPress={() => {
                                                  this.handleNavigation('AccountScreenTab', 'SavedSearches', LIBRARY.url);
                                             }}>
                                             <HStack space="1" alignItems="center">
                                                  <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                                  <VStack w="100%">
                                                       <Text fontWeight="500">
                                                            {translate('user_profile.saved_searches')} {user ? <Text bold>({savedSearches})</Text> : null}
                                                       </Text>
                                                  </VStack>
                                             </HStack>
                                             {updatedSearches > 0 ? (
                                                  <Container>
                                                       <Badge colorScheme="warning" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                                                            {translate('user_profile.saved_searches_updated', {
                                                                 count: updatedSearches,
                                                            })}
                                                       </Badge>
                                                  </Container>
                                             ) : null}
                                        </Pressable>
                                   ) : null}
                              </VStack>
                              <VStack space="3">
                                   <VStack>
                                        <Pressable
                                             px="2"
                                             py="3"
                                             onPress={() => {
                                                  this.handleNavigation('AccountScreenTab', 'ProfileScreen', LIBRARY.url);
                                             }}>
                                             <HStack space="1" alignItems="center">
                                                  <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                                  <Text fontWeight="500">{translate('user_profile.profile')}</Text>
                                             </HStack>
                                        </Pressable>
                                        {library.allowLinkedAccounts === '1' ? (
                                             <Pressable px="2" py="2" onPress={() => this.handleNavigation('AccountScreenTab', 'LinkedAccounts', LIBRARY.url)}>
                                                  <HStack space="1" alignItems="center">
                                                       <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                                       <Text fontWeight="500">{translate('user_profile.linked_accounts')}</Text>
                                                  </HStack>
                                                  {linkedAccounts > 0 ? (
                                                       <Container>
                                                            <Badge colorScheme="warning" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                                                                 ({linkedAccounts})
                                                            </Badge>
                                                       </Container>
                                                  ) : null}
                                             </Pressable>
                                        ) : null}
                                        <Pressable
                                             px="2"
                                             py="3"
                                             onPress={() => {
                                                  this.handleNavigation('AccountScreenTab', 'Preferences', LIBRARY.url);
                                             }}>
                                             <HStack space="1" alignItems="center">
                                                  <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                                  <Text fontWeight="500">{translate('user_profile.preferences')}</Text>
                                             </HStack>
                                        </Pressable>
                                   </VStack>
                              </VStack>
                         </VStack>
                         <VStack space={3} alignItems="center">
                              <HStack space={2}>
                                   <LogOutButton />
                              </HStack>
                              <UseColorMode />
                         </VStack>
                    </VStack>
               </DrawerContentScrollView>
          );
     }
}*/