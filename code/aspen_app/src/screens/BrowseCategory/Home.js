import { Ionicons, MaterialIcons } from '@expo/vector-icons';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import _ from 'lodash';
import { Badge, Box, Button, Container, FormControl, HStack, Icon, Input, Pressable, ScrollView, Text } from 'native-base';
import React from 'react';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { NotificationsOnboard } from '../../components/NotificationsOnboard';
import { BrowseCategoryContext, CheckoutsContext, HoldsContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getLists } from '../../util/api/list';
import { fetchReadingHistory, fetchSavedSearches, getLinkedAccounts, getPatronCheckedOutItems, getPatronHolds, getViewerAccounts, reloadProfile, validateSession } from '../../util/api/user';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion, getPickupLocations, reloadBrowseCategories } from '../../util/loadLibrary';
import { getBrowseCategoryListForUser, getILSMessages, PATRON, updateBrowseCategoryStatus } from '../../util/loadPatron';
import { getDefaultFacets } from '../../util/search';
import DisplayBrowseCategory from './Category';

let maxCategories = 5;

export const DiscoverHomeScreen = () => {
     const isFocused = useIsFocused();
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(false);
     const [showNotificationsOnboarding, setShowNotificationsOnboarding] = React.useState(false);
     const [alreadyCheckedNotifications, setAlreadyCheckedNotifications] = React.useState(true);
     const { user, locations, accounts, cards, lists, updateUser, updateLanguage, updatePickupLocations, updateLinkedAccounts, updateLists, updateLibraryCards, updateLinkedViewerAccounts, updateReadingHistory, notificationSettings, expoToken, updateNotificationOnboard, notificationOnboard } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { category, updateBrowseCategories, updateBrowseCategoryList, updateMaxCategories } = React.useContext(BrowseCategoryContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const { holds, updateHolds, pendingSortMethod, readySortMethod } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const [searchTerm, setSearchTerm] = React.useState('');
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const [unlimited, setUnlimitedCategories] = React.useState(false);

     navigation.setOptions({
          headerLeft: () => {
               return null;
          },
     });

     useQuery(['browse_categories', library.baseUrl, language], () => reloadBrowseCategories(maxCategories, library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          onSuccess: (data) => {
               if (maxCategories === 9999) {
                    setUnlimitedCategories(true);
               }
               updateBrowseCategories(data);
               setLoading(false);
          },
          placeholderData: [],
     });

     useQuery(['holds', user.id, library.baseUrl, language], () => getPatronHolds(readySortMethod, pendingSortMethod, 'all', library.baseUrl, true, language), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => updateHolds(data),
          placeholderData: [],
     });

     useQuery(['checkouts', user.id, library.baseUrl, language], () => getPatronCheckedOutItems('all', library.baseUrl, true, language), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => updateCheckouts(data),
          placeholderData: [],
     });

     useQuery(['lists', user.id, library.baseUrl, language], () => getLists(library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => updateLists(data),
          placeholderData: [],
     });

     useQuery(['user', library.baseUrl, language], () => reloadProfile(library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => {
               updateUser(data);
               updateLanguage(data.interfaceLanguage ?? 'en');
               PATRON.language = data.interfaceLanguage ?? 'en';
          },
          placeholderData: [],
     });

     useQuery(['linked_accounts', user, cards ?? [], library.baseUrl, language], () => getLinkedAccounts(user, cards, library.barcodeStyle, library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => {
               updateLinkedAccounts(data.accounts);
               updateLibraryCards(data.cards);
          },
          placeholderData: [],
     });

     useQuery(['viewer_accounts', user.id, library.baseUrl, language], () => getViewerAccounts(library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => {
               updateLinkedViewerAccounts(data);
          },
          placeholderData: [],
     });

     useQuery(['ils_messages', user.id, library.baseUrl, language], () => getILSMessages(library.baseUrl), {
          refetchInterval: 60 * 1000 * 5,
          refetchIntervalInBackground: true,
          placeholderData: [],
     });

     useQuery(['pickup_locations', library.baseUrl, language], () => getPickupLocations(library.baseUrl), {
          refetchInterval: 60 * 1000 * 30,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updatePickupLocations(data);
          },
     });

     useQuery(['saved_searches', user.id, library.baseUrl, language], () => fetchSavedSearches(library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 5,
          refetchIntervalInBackground: true,
          placeholderData: [],
     });

     useQuery(['reading_history', user.id, library.baseUrl, 1, 'checkedOut'], () => fetchReadingHistory(1, 25, 'checkedOut', library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 30,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateReadingHistory(data);
          },
     });

     useQuery(['browse_categories_list', library.baseUrl, language], () => getBrowseCategoryListForUser(library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateBrowseCategoryList(data);
          },
     });

     useQuery(['session', library.baseUrl, user.id], () => validateSession(library.baseUrl), {
          refetchInterval: 60 * 1000 * 5, // 86400000 = run once per day, temporarily set to every 5 minutes to make sure it's working
          refetchIntervalInBackground: true,
          onSuccess: (data) => {
               if (typeof data.result?.session !== 'undefined') {
                    GLOBALS.appSessionId = data.result.session;
               }
          },
     });

     useFocusEffect(
          React.useCallback(() => {
               const checkSettings = async () => {
                    if (version >= '22.11.00') {
                         await getDefaultFacets(language);
                    }

                    console.log('notificationOnboard: ' + notificationOnboard);
                    if (!_.isUndefined(notificationOnboard)) {
                         if (notificationOnboard === 1 || notificationOnboard === 2 || notificationOnboard === '1' || notificationOnboard === '2') {
                              setShowNotificationsOnboarding(true);
                              //setAlreadyCheckedNotifications(false);
                         } else {
                              setShowNotificationsOnboarding(false);
                              //setAlreadyCheckedNotifications(true);
                         }
                    } else {
                         updateNotificationOnboard(1);
                         setShowNotificationsOnboarding(true);
                         //setAlreadyCheckedNotifications(false);
                    }
               };
               checkSettings().then(() => {
                    return () => checkSettings();
               });
          }, [language, notificationOnboard])
     );

     const clearText = () => {
          setSearchTerm('');
     };

     const search = async () => {
          navigateStack('BrowseTab', 'SearchResults', { term: searchTerm, type: 'catalog', prevRoute: 'DiscoveryScreen', scannerSearch: false });
          clearText();
     };

     const openScanner = async () => {
          navigateStack('BrowseTab', 'Scanner');
     };

     // load notification onboarding prompt
     if (notificationOnboard !== '0' && notificationOnboard !== 0) {
          if (isFocused) {
               return <NotificationsOnboard />;
          }
     }

     const renderHeader = (title, key, user, url) => {
          return (
               <Box>
                    <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
                         <Text
                              maxWidth="80%"
                              bold
                              mb={1}
                              fontSize={{
                                   base: 'lg',
                                   lg: '2xl',
                              }}>
                              {title}
                         </Text>
                         <Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => onHideCategory(url, key)} startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5} />}>
                              {getTermFromDictionary(language, 'hide')}
                         </Button>
                    </HStack>
               </Box>
          );
     };

     const renderRecord = (data, url, version, index) => {
          const item = data.item;
          let type = 'grouped_work';
          if (!_.isUndefined(item.source)) {
               type = item.source;
          }

          if (!_.isUndefined(item.recordtype)) {
               type = item.recordtype;
          }

          const imageUrl = library.baseUrl + '/bookcover.php?id=' + item.id + '&size=medium&type=' + type.toLowerCase();

          let isNew = false;
          if (typeof item.isNew !== 'undefined') {
               isNew = item.isNew;
          }

          return (
               <Pressable
                    ml={1}
                    mr={3}
                    onPress={() => onPressItem(item.id, type, item.title_display, version)}
                    width={{
                         base: 100,
                         lg: 200,
                    }}
                    height={{
                         base: 150,
                         lg: 250,
                    }}>
                    {version >= '22.08.00' && isNew ? (
                         <Container zIndex={1}>
                              <Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{ fontSize: 9 }}>
                                   {getTermFromDictionary(language, 'flag_new')}
                              </Badge>
                         </Container>
                    ) : null}
                    <CachedImage
                         cacheKey={item.id}
                         alt={item.title_display}
                         source={{
                              uri: `${imageUrl}`,
                              expiresIn: 3600,
                         }}
                         style={{
                              width: '100%',
                              height: '100%',
                              borderRadius: 4,
                         }}
                         resizeMode="cover"
                    />
               </Pressable>
          );
     };

     const onPressItem = (key, type, title, version) => {
          if (version >= '22.07.00') {
               if (type === 'List' || type === 'list') {
                    navigateStack('BrowseTab', 'SearchByList', {
                         id: key,
                         url: library.baseUrl,
                         title: title,
                         userContext: user,
                         libraryContext: library,
                         prevRoute: 'HomeScreen',
                    });
               } else if (type === 'SavedSearch') {
                    navigateStack('BrowseTab', 'SearchBySavedSearch', {
                         id: key,
                         url: library.baseUrl,
                         title: title,
                         userContext: user,
                         libraryContext: library,
                         prevRoute: 'HomeScreen',
                    });
               } else {
                    if (version >= '23.01.00') {
                         navigateStack('BrowseTab', 'GroupedWorkScreen', {
                              id: key,
                              title: title,
                              prevRoute: 'HomeScreen',
                         });
                    } else {
                         navigateStack('BrowseTab', 'GroupedWorkScreen221200', {
                              id: key,
                              title: title,
                              url: library.baseUrl,
                              userContext: user,
                              libraryContext: library,
                              prevRoute: 'HomeScreen',
                         });
                    }
               }
          } else {
               navigateStack('BrowseTab', 'GroupedWorkScreen', {
                    id: key,
                    url: library.baseUrl,
                    title: title,
                    userContext: user,
                    libraryContext: library,
                    prevRoute: 'HomeScreen',
               });
          }
     };

     const renderLoadMore = () => {};

     const onHideCategory = async (url, category) => {
          setLoading(true);
          await updateBrowseCategoryStatus(category);
          queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
     };

     const onRefreshCategories = () => {
          setLoading(true);
          queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
     };

     const onLoadAllCategories = () => {
          setLoading(true);
          maxCategories = 9999;
          updateMaxCategories(9999);
          setUnlimitedCategories(true);
          queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
     };

     const onPressSettings = (url, patronId) => {
          const version = formatDiscoveryVersion(library.discoveryVersion);
          let screen = 'SettingsHomeScreen';
          if (version >= '22.12.00') {
               screen = 'SettingsBrowseCategories';
          }
          navigateStack('AccountScreenTab', screen, {
               url,
               patronId,
          });
     };

     const handleOnPressCategory = (label, key, source) => {
          let screen = 'SearchByCategory';
          if (source === 'List') {
               screen = 'SearchByList';
          } else if (source === 'SavedSearch') {
               screen = 'SearchBySavedSearch';
          }

          navigateStack('BrowseTab', screen, {
               title: label,
               id: key,
               url: library.baseUrl,
               libraryContext: library,
               userContext: user,
               language: language,
          });
     };

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     if (loading === true) {
          return loadingSpinner();
     }

     return (
          <ScrollView>
               <Box safeArea={5}>
                    {showSystemMessage()}
                    <FormControl pb={5}>
                         <Input
                              returnKeyType="search"
                              variant="outline"
                              autoCapitalize="none"
                              onChangeText={(term) => setSearchTerm(term)}
                              status="info"
                              placeholder={getTermFromDictionary(language, 'search')}
                              clearButtonMode="while-editing"
                              onSubmitEditing={search}
                              value={searchTerm}
                              size="xl"
                              InputLeftElement={<Icon as={<Ionicons name="search" />} size={5} ml="2" color="muted.800" />}
                              InputRightElement={
                                   <Pressable onPress={() => openScanner()}>
                                        <Icon as={<Ionicons name="barcode-outline" />} size={6} mr="2" color="muted.800" />
                                   </Pressable>
                              }
                         />
                    </FormControl>
                    {category.map((item, index) => {
                         return <DisplayBrowseCategory language={language} key={index} categoryLabel={item.title} categoryKey={item.key} id={item.id} records={item.records} isHidden={item.isHidden} categorySource={item.source} renderRecords={renderRecord} header={renderHeader} hideCategory={onHideCategory} user={user} libraryUrl={library.baseUrl} loadMore={renderLoadMore} discoveryVersion={library.version} onPressCategory={handleOnPressCategory} categoryList={category} />;
                    })}
                    <ButtonOptions language={language} libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={onPressSettings} onRefreshCategories={onRefreshCategories} discoveryVersion={library.discoveryVersion} loadAll={unlimited} onLoadAllCategories={onLoadAllCategories} />
               </Box>
          </ScrollView>
     );
};

const ButtonOptions = (props) => {
     const [loading, setLoading] = React.useState(false);
     const [refreshing, setRefreshing] = React.useState(false);
     const { language, onPressSettings, onRefreshCategories, libraryUrl, patronId, discoveryVersion, loadAll, onLoadAllCategories } = props;

     const version = formatDiscoveryVersion(discoveryVersion);

     if (version >= '22.07.00') {
          return (
               <Box>
                    {!loadAll ? (
                         <Button
                              isLoading={loading}
                              size="md"
                              colorScheme="primary"
                              onPress={() => {
                                   setLoading(true);
                                   onLoadAllCategories(libraryUrl, patronId);
                                   setTimeout(function () {
                                        setLoading(false);
                                   }, 5000);
                              }}
                              startIcon={<Icon as={MaterialIcons} name="schedule" size="sm" />}>
                              {getTermFromDictionary(language, 'browse_categories_load_all')}
                         </Button>
                    ) : null}
                    <Button
                         size="md"
                         mt="3"
                         colorScheme="primary"
                         onPress={() => {
                              onPressSettings(libraryUrl, patronId);
                         }}
                         startIcon={<Icon as={MaterialIcons} name="settings" size="sm" />}>
                         {getTermFromDictionary(language, 'browse_categories_manage')}
                    </Button>
                    <Button
                         isLoading={refreshing}
                         size="md"
                         mt="3"
                         colorScheme="primary"
                         onPress={() => {
                              setRefreshing(true);
                              onRefreshCategories();
                              setTimeout(function () {
                                   setRefreshing(false);
                              });
                         }}
                         startIcon={<Icon as={MaterialIcons} name="refresh" size="sm" />}>
                         {getTermFromDictionary(language, 'browse_categories_refresh')}
                    </Button>
               </Box>
          );
     }

     return (
          <Box>
               <Button
                    size="md"
                    colorScheme="primary"
                    onPress={() => {
                         onPressSettings(libraryUrl, patronId);
                    }}
                    startIcon={<Icon as={MaterialIcons} name="settings" size="sm" />}>
                    {getTermFromDictionary(language, 'browse_categories_manage')}
               </Button>
               <Button
                    size="md"
                    mt="3"
                    colorScheme="primary"
                    onPress={() => {
                         onRefreshCategories(libraryUrl);
                    }}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size="sm" />}>
                    {getTermFromDictionary(language, 'browse_categories_refresh')}
               </Button>
          </Box>
     );
};