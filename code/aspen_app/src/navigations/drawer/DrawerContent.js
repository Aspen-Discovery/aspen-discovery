import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { DrawerContentScrollView } from '@react-navigation/drawer';
import Constants from 'expo-constants';
import * as Linking from 'expo-linking';
import * as Notifications from 'expo-notifications';
import _ from 'lodash';
import { Badge, Box, Button, Container, Divider, HStack, Icon, Image, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { useFocusEffect, useLinkTo } from '@react-navigation/native';

// custom components and helper files
import { showILSMessage } from '../../components/Notifications';
import { AuthContext } from '../../components/navigation';
import { UseColorMode } from '../../themes/theme';
import {getTermFromDictionary, getTranslationsWithValues, LanguageSwitcher} from '../../translations/TranslationService';
import { translate } from '../../translations/translations';
import { formatDiscoveryVersion } from '../../util/loadLibrary';
import { reloadProfile } from '../../util/api/user';
import { getILSMessages } from '../../util/loadPatron';
import {LanguageContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import { navigateStack } from '../../helpers/RootNavigator';

Notifications.setNotificationHandler({
     handleNotification: async () => ({
          shouldShowAlert: true,
          shouldPlaySound: true,
          shouldSetBadge: false,
     }),
});

const prefix = Linking.createURL('/');

export const DrawerContent = () => {
     const linkTo = useLinkTo();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [notifications, setNotifications] = React.useState([]);
     const [messages, setILSMessages] = React.useState([]);
     const { language } = React.useContext(LanguageContext);
     const [finesSummary, setFinesSummary] = React.useState('');

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
          url = url.replace('aspen-lida://', prefix);

          const supported = await Linking.canOpenURL(url);
          if (supported) {
               try {
                    url = url.replace(prefix, '/');
                    console.log('Opening url in DrawerContent...');
                    console.log(url);
                    linkTo(url);
               } catch (e) {
                    console.log('Could not open url in DrawerContent');
                    console.log(e);
               }
          } else {
               console.log('Could not open url in DrawerContent');
               console.log(url);
          }
     };

     const displayFinesAlert = () => {
          if (user.fines) {
               if (!_.includes(user.fines, '0.00') && (user.fines > 0.01)) {
                    const message = 'Your accounts have ' + user.fines + ' in fines.';
                    return showILSMessage('warning', message);
               }
          }

          return null;
     };

     const displayILSMessages = () => {
          if (messages) {
               if (_.isArray(messages)) {
                    return messages.map((obj, index) => {
                         if (obj.message) {
                              return showILSMessage(obj.messageStyle, obj.message, index);
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
                         <HStack space={2}>
                              <UseColorMode />
                              <LanguageSwitcher />
                         </HStack>
                    </VStack>
               </VStack>
          </DrawerContentScrollView>
     );
};

const UserProfileOverview = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

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
     const { language } = React.useContext(LanguageContext);

     const [checkoutSummary, setCheckoutSummary] = React.useState('');
     React.useEffect(() => {
          async function fetchTranslations() {
               await getTranslationsWithValues('checkouts_overdue_summary', user.numOverdue ?? 0, language, library.baseUrl).then(result => {
                    console.log(result);
                    setCheckoutSummary(result);
               });
          }
          fetchTranslations()
     }, []);

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
                              {getTermFromDictionary(language, "checked_out_titles")} {user ? <Text bold>({user.numCheckedOut})</Text> : null}
                         </Text>
                    </VStack>
               </HStack>
               {user.numOverdue > 0 ? (
                    <Container>
                         <Badge colorScheme="error" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                              {checkoutSummary}
                         </Badge>
                    </Container>
               ) : null}
          </Pressable>
     );
};

const Holds = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const [holdSummary, setHoldSummary] = React.useState('');
     React.useEffect(() => {
          async function fetchTranslations() {
               await getTranslationsWithValues('num_holds_ready_for_pickup', user.numHoldsAvailable ?? 0, language, library.baseUrl).then(result => {
                    setHoldSummary(result);
               });
          }
          fetchTranslations()
     }, []);

     return (
          <Pressable
               px="2"
               py="3"
               rounded="md"
               onPress={() => {
                    navigateStack('AccountScreenTab', 'MyHolds', {
                         libraryUrl: library.baseUrl,
                         hasPendingChanges: false,
                    });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <VStack w="100%">
                         <Text fontWeight="500">
                              {getTermFromDictionary(language, "titles_on_hold")} {user ? <Text bold>({user.numHolds})</Text> : null}
                         </Text>
                    </VStack>
               </HStack>
               {user.numHoldsAvailable > 0 ? (
                    <Container>
                         <Badge colorScheme="success" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                              {holdSummary}
                         </Badge>
                    </Container>
               ) : null}
          </Pressable>
     );
};

const UserLists = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
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
                                   {getTermFromDictionary(language, "my_lists")} {user ? <Text bold>({user.numLists})</Text> : null}
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
                    navigateStack('MyListsStack', 'MyLists', {
                         libraryUrl: library.baseUrl,
                         hasPendingChanges: false,
                    });
               }}>
               <HStack space="1" alignItems="center">
                    <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    <VStack w="100%">
                         <Text fontWeight="500">{getTermFromDictionary(language, "my_lists")}</Text>
                    </VStack>
               </HStack>
          </Pressable>
     );
};

const SavedSearches = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     const [savedSearchSummary, setSavedSearchSummary] = React.useState('');
     React.useEffect(() => {
          async function fetchTranslations() {
               await getTranslationsWithValues('num_saved_searches_with_updates', user.numSavedSearchesNew ?? 0, language, library.baseUrl).then(result => {
                    setSavedSearchSummary(result);
               });
          }
          fetchTranslations()
     }, []);

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
                                   {getTermFromDictionary(language, "saved_searches")} {user ? <Text bold>({user.numSavedSearches})</Text> : null}
                              </Text>
                         </VStack>
                    </HStack>
                    {user.numSavedSearchesNew > 0 ? (
                         <Container>
                              <Badge colorScheme="warning" ml={10} rounded="4px" _text={{ fontSize: 'xs' }}>
                                   {savedSearchSummary}
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
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (version >= '23.01.00') {
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
                                   {getTermFromDictionary(language, "reading_history")} <Text bold>({user.numReadingHistory ?? 0})</Text>
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
     const { language } = React.useContext(LanguageContext);

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
                    <Text fontWeight="500">{getTermFromDictionary(language, "profile")}</Text>
               </HStack>
          </Pressable>
     );
};

const LinkedAccounts = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
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
                         <Text fontWeight="500">
                              {getTermFromDictionary(language, "linked_accounts")} <Text bold>({user.numLinkedAccounts ?? 0})</Text>
                         </Text>
                    </HStack>
               </Pressable>
          );
     }

     return null;
};

const UserPreferences = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

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
                    <Text fontWeight="500">{getTermFromDictionary(language, "preferences")}</Text>
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
     const { language } = React.useContext(LanguageContext);
     const { signOut } = React.useContext(AuthContext);

     return (
          <Button size="md" colorScheme="secondary" onPress={signOut} leftIcon={<Icon as={MaterialIcons} name="logout" size="xs" />}>
               {getTermFromDictionary(language, "logout")}
          </Button>
     );
}

const ReloadProfileButton = (props) => {
     const { language } = React.useContext(LanguageContext);

     return (
          <Button size="xs" colorScheme="tertiary" onPress={() => props.handleRefreshProfile(props.libraryUrl)} variant="ghost" leftIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>
               {getTermFromDictionary(language, 'refresh_account')}
          </Button>
     );
};