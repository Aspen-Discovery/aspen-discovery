import { ScanBarcode, SearchIcon, XIcon, Settings, RotateCwIcon, ClockIcon } from 'lucide-react-native';
import { Center, Box, Button, ButtonGroup, ButtonIcon, ButtonText, ButtonSpinner, HStack, Icon, Badge, BadgeText, FormControl, Input, InputField, InputSlot, InputIcon, Pressable, ScrollView, Text } from '@gluestack-ui/themed';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { useIsFetching, useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import React from 'react';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { NotificationsOnboard } from '../../components/NotificationsOnboard';
import { BrowseCategoryContext, CheckoutsContext, HoldsContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, SearchContext, SystemMessagesContext, ThemeContext, UserContext } from '../../context/initialContext';
import { navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { fetchSavedEvents } from '../../util/api/event';
import { getLists } from '../../util/api/list';
import { getLocations } from '../../util/api/location';
import { fetchReadingHistory, fetchSavedSearches, getLinkedAccounts, getPatronCheckedOutItems, getPatronHolds, getViewerAccounts, reloadProfile, revalidateUser, validateSession } from '../../util/api/user';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion, getPickupLocations, reloadBrowseCategories } from '../../util/loadLibrary';
import { getBrowseCategoryListForUser, getILSMessages, PATRON, updateBrowseCategoryStatus } from '../../util/loadPatron';
import { getDefaultFacets, getSearchIndexes, getSearchSources } from '../../util/search';
import { ForceLogout } from '../Auth/ForceLogout';
import DisplayBrowseCategory from './Category';

let maxCategories = 5;

export const DiscoverHomeScreen = () => {
     const isFocused = useIsFocused();
     const isQueryFetching = useIsFetching();
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const [invalidSession, setInvalidSession] = React.useState(false);
     const [loading, setLoading] = React.useState(false);
     const [userLatitude, setUserLatitude] = React.useState(0);
     const [userLongitude, setUserLongitude] = React.useState(0);
     const [showNotificationsOnboarding, setShowNotificationsOnboarding] = React.useState(false);
     const [alreadyCheckedNotifications, setAlreadyCheckedNotifications] = React.useState(true);
     const { user, accounts, cards, lists, updateUser, updateLanguage, updatePickupLocations, updateLinkedAccounts, updateLists, updateSavedEvents, updateLibraryCards, updateLinkedViewerAccounts, updateReadingHistory, notificationSettings, expoToken, updateNotificationOnboard, notificationOnboard } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [preliminaryLoadingCheck, setPreliminaryCheck] = React.useState(false);
     const { location, locations, updateLocations } = React.useContext(LibraryBranchContext);
     const { category, list, updateBrowseCategories, updateBrowseCategoryList, updateMaxCategories } = React.useContext(BrowseCategoryContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const { holds, updateHolds, pendingSortMethod, readySortMethod } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const [searchTerm, setSearchTerm] = React.useState('');
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const { updateIndexes, updateSources, updateCurrentIndex, updateCurrentSource } = React.useContext(SearchContext);
     const { theme, mode, textColor } = React.useContext(ThemeContext);
     const [unlimited, setUnlimitedCategories] = React.useState(false);

     navigation.setOptions({
          headerLeft: () => {
               return null;
          },
     });

     useQuery(['user', library.baseUrl, language], () => reloadProfile(library.baseUrl), {
          initialData: user,
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => {
               if (user) {
                    if (data !== user) {
                         updateUser(data);
                         updateLanguage(data.interfaceLanguage ?? 'en');
                         PATRON.language = data.interfaceLanguage ?? 'en';
                    }
               } else {
                    updateUser(data);
                    updateLanguage(data.interfaceLanguage ?? 'en');
                    PATRON.language = data.interfaceLanguage ?? 'en';
               }
          },
     });

     const { status, data, error, isFetching, isPreviousData } = useQuery(['browse_categories', library.baseUrl, language], () => reloadBrowseCategories(maxCategories, library.baseUrl), {
          initialData: category,
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          onSuccess: (data) => {
               if (maxCategories === 9999) {
                    setUnlimitedCategories(true);
               }
               updateBrowseCategories(data);
               setLoading(false);
          },
          onSettle: (data) => {
               setLoading(false);
          },
          placeholderData: [],
     });

     useQuery(['holds', user.id, library.baseUrl, language], () => getPatronHolds(readySortMethod, pendingSortMethod, 'all', library.baseUrl, false, language), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          notifyOnChangeProps: ['data'],
          onSuccess: (data) => updateHolds(data),
          placeholderData: [],
     });

     useQuery(['checkouts', user.id, library.baseUrl, language], () => getPatronCheckedOutItems('all', library.baseUrl, false, language), {
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

     useQuery(['locations', library.baseUrl, language, userLatitude, userLongitude], () => getLocations(library.baseUrl, language, userLatitude, userLongitude), {
          refetchInterval: 60 * 1000 * 30,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateLocations(data);
          },
     });

     useQuery(['saved_searches', user?.id ?? 'unknown', library.baseUrl, language], () => fetchSavedSearches(library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 5,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               console.log(data);
          },
     });

     useQuery(['reading_history', user.id, library.baseUrl, 1, 'checkedOut'], () => fetchReadingHistory(1, 25, 'checkedOut', library.baseUrl, language), {
          refetchInterval: 60 * 1000 * 30,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateReadingHistory(data);
          },
     });

     useQuery(['saved_events', user.id, library.baseUrl, 1, 'upcoming'], () => fetchSavedEvents(1, 25, 'upcoming', library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateSavedEvents(data.events);
          },
     });

     useQuery(['saved_events', user?.id ?? 'unknown', library.baseUrl, 1, 'all'], () => fetchSavedEvents(1, 25, 'all', library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateSavedEvents(data.events);
          },
     });

     useQuery(['saved_events', user.id, library.baseUrl, 1, 'past'], () => fetchSavedEvents(1, 25, 'past', library.baseUrl), {
          refetchInterval: 60 * 1000 * 15,
          refetchIntervalInBackground: true,
          placeholderData: [],
          onSuccess: (data) => {
               updateSavedEvents(data.events);
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
          refetchInterval: 86400000,
          refetchIntervalInBackground: true,
          onSuccess: (data) => {
               if (typeof data.result?.session !== 'undefined') {
                    GLOBALS.appSessionId = data.result.session;
               }
          },
     });

     useQuery(['valid_user', library.baseUrl, user.id], () => revalidateUser(library.baseUrl), {
          refetchInterval: 60 * 1000 * 5,
          refetchIntervalInBackground: true,
          onSuccess: (data) => {
               if (data === false || data === 'false') {
                    setInvalidSession(true);
               }
          },
     });

     useFocusEffect(
          React.useCallback(() => {
               const checkSettings = async () => {
                    let latitude = await SecureStore.getItemAsync('latitude');
                    let longitude = await SecureStore.getItemAsync('longitude');
                    setUserLatitude(latitude);
                    setUserLongitude(longitude);

                    if (version >= '24.02.00') {
                         updateCurrentIndex('Keyword');
                         updateCurrentSource('local');
                         await getSearchIndexes(library.baseUrl, language, 'local').then((result) => {
                              updateIndexes(result);
                         });
                         await getSearchSources(library.baseUrl, language).then((result) => {
                              updateSources(result);
                         });
                    }

                    if (version >= '22.11.00') {
                         await getDefaultFacets(library.baseUrl, 5, language);
                    }

                    setPreliminaryCheck(true);

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
                         setShowNotificationsOnboarding(true);
                         //setAlreadyCheckedNotifications(false);
                    }
               };
               checkSettings().then(() => {
                    return () => checkSettings();
               });
          }, [language, notificationOnboard, mode])
     );

     const clearText = () => {
          setSearchTerm('');
     };

     const search = () => {
          navigateStack('BrowseTab', 'SearchResults', {
               term: searchTerm,
               type: 'catalog',
               prevRoute: 'DiscoveryScreen',
               scannerSearch: false,
          });
          clearText();
     };

     const openScanner = async () => {
          navigateStack('BrowseTab', 'Scanner');
     };

     // load notification onboarding prompt
     if (isQueryFetching === 0 && preliminaryLoadingCheck) {
          if (notificationOnboard !== '0' && notificationOnboard !== 0) {
               if (isFocused) {
                    return <NotificationsOnboard />;
               }
          }
     }

     const renderHeader = (title, key, user, url) => {
          return (
               <Box>
                    <HStack space="$3" alignItems="center" justifyContent="space-between" pb="$2">
                         <Text
                              maxWidth="80%"
                              bold
                              mb="$1"
                              $dark-color="$textLight50"
                              sx={{
                                   '@base': {
                                        fontSize: '$16',
                                   },
                                   '@lg': {
                                        fontSize: '$22',
                                   },
                              }}>
                              {title}
                         </Text>
                         <Button size="xs" variant="link" onPress={() => onHideCategory(url, key)}>
                              <ButtonIcon as={XIcon} color={textColor} mr="$1" />
                              <ButtonText fontWeight="$medium" sx={{ color: textColor }}>
                                   {getTermFromDictionary(language, 'hide')}
                              </ButtonText>
                         </Button>
                    </HStack>
               </Box>
          );
     };

     const renderRecord = (data, url, version, index) => {
          const item = data.item;
          let id = item.key ?? item.id;

          let type = 'grouped_work';
          if (!_.isUndefined(item.source)) {
               type = item.source;
          }

          if (!_.isUndefined(item.recordtype)) {
               type = item.recordtype;
          }

          if (type === 'Event') {
               if (_.includes(id, 'lc_')) {
                    type = 'library_calendar_event';
               }
               if (_.includes(id, 'libcal_')) {
                    type = 'springshare_libcal_event';
               }
               if (_.includes(id, 'communico_')) {
                    type = 'communico_event';
               }
          }

          const imageUrl = library.baseUrl + '/bookcover.php?id=' + id + '&size=medium&type=' + type.toLowerCase();

          let isNew = false;
          if (typeof item.isNew !== 'undefined') {
               isNew = item.isNew;
          }

          const key = 'medium_' + id;

          return (
               <Pressable
                    ml="$1"
                    mr="$3"
                    onPress={() => onPressItem(id, type, item.title_display, version)}
                    sx={{
                         '@base': {
                              width: 100,
                              height: 150,
                         },
                         '@lg': {
                              width: 180,
                              height: 250,
                         },
                    }}>
                    {version >= '22.08.00' && isNew ? (
                         <Box zIndex={1}>
                              <Badge colorScheme="warning" shadow={1} mb={-2} ml={-1}>
                                   <BadgeText size="$9">{getTermFromDictionary(language, 'flag_new')}</BadgeText>
                              </Badge>
                         </Box>
                    ) : null}
                    <CachedImage
                         cacheKey={key}
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
               console.log('type: ' + type);
               console.log('key: ' + key);
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
               } else if (type === 'Event' || _.includes(type, '_event')) {
                    let eventSource = 'unknown';
                    if (type === 'communico_event') {
                         eventSource = 'communico';
                    } else if (type === 'library_calendar_event') {
                         eventSource = 'library_calendar';
                    } else if (type === 'springshare_libcal_event') {
                         eventSource = 'springshare';
                    }

                    navigateStack('BrowseTab', 'EventScreen', {
                         id: key,
                         title: title,
                         source: eventSource,
                         prevRoute: 'HomeScreen',
                    });
               } else {
                    navigateStack('BrowseTab', 'GroupedWorkScreen', {
                         id: key,
                         title: title,
                         prevRoute: 'HomeScreen',
                    });
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

     const onPressSettings = () => {
          navigateStack('MoreTab', 'MyPreferences_ManageBrowseCategories', {});
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
                    console.log(obj);
                    if (obj.showOn === '0') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     if (loading === true || isFetching) {
          return loadingSpinner();
     }

     if (invalidSession === true || invalidSession === 'true') {
          return <ForceLogout />;
     }

     const clearSearch = () => {
          setSearchTerm('');
     };

     return (
          <ScrollView>
               <Box p="$5">
                    {showSystemMessage()}
                    <FormControl pb="$5">
                         <Input>
                              <InputSlot>
                                   <InputIcon as={SearchIcon} ml="$2" color={textColor} />
                              </InputSlot>
                              <InputField returnKeyType="search" variant="outline" autoCapitalize="none" onChangeText={(term) => setSearchTerm(term)} status="info" placeholder={getTermFromDictionary(language, 'search')} onSubmitEditing={search} value={searchTerm} size="lg" sx={{ color: textColor, borderColor: textColor, ':focus': { borderColor: textColor } }} />
                              {searchTerm ? (
                                   <InputSlot onPress={() => clearSearch()}>
                                        <InputIcon as={XIcon} mr="$2" color={textColor} />
                                   </InputSlot>
                              ) : null}
                              <InputSlot onPress={() => openScanner()}>
                                   <InputIcon as={ScanBarcode} mr="$2" color={textColor} />
                              </InputSlot>
                         </Input>
                    </FormControl>
                    {category.map((item, index) => {
                         return <DisplayBrowseCategory textColor={textColor} language={language} key={index} categoryLabel={item.title} categoryKey={item.key} id={item.id} records={item.records} isHidden={item.isHidden} categorySource={item.source} renderRecords={renderRecord} header={renderHeader} hideCategory={onHideCategory} user={user} libraryUrl={library.baseUrl} loadMore={renderLoadMore} discoveryVersion={library.version} onPressCategory={handleOnPressCategory} categoryList={category} />;
                    })}
                    <ButtonOptions language={language} libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={onPressSettings} onRefreshCategories={onRefreshCategories} discoveryVersion={library.discoveryVersion} loadAll={unlimited} onLoadAllCategories={onLoadAllCategories} />
               </Box>
          </ScrollView>
     );
};

const ButtonOptions = (props) => {
     const { theme } = React.useContext(ThemeContext);
     const [loading, setLoading] = React.useState(false);
     const [refreshing, setRefreshing] = React.useState(false);
     const { language, onPressSettings, onRefreshCategories, libraryUrl, patronId, discoveryVersion, loadAll, onLoadAllCategories } = props;

     const version = formatDiscoveryVersion(discoveryVersion);

     if (version >= '22.07.00') {
          return (
               <Center>
                    <ButtonGroup
                         sx={{
                              '@base': {
                                   flexDirection: 'column',
                              },
                              '@lg': {
                                   flexDirection: 'row',
                              },
                         }}>
                         {!loadAll ? (
                              <Button
                                   isDisabled={loading}
                                   sx={{
                                        bg: theme['colors']['primary']['500'],
                                        size: 'md',
                                   }}
                                   onPress={() => {
                                        setLoading(true);
                                        onLoadAllCategories(libraryUrl, patronId);
                                        setTimeout(function () {
                                             setLoading(false);
                                        }, 5000);
                                   }}>
                                   {loading ? <ButtonSpinner /> : <ButtonIcon as={ClockIcon} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />}
                                   <ButtonText
                                        sx={{
                                             color: theme['colors']['primary']['500-text'],
                                        }}
                                        size="sm"
                                        fontWeight="$medium">
                                        {getTermFromDictionary(language, 'browse_categories_load_all')}
                                   </ButtonText>
                              </Button>
                         ) : null}

                         <Button
                              sx={{
                                   bg: theme['colors']['primary']['500'],
                              }}
                              onPress={() => {
                                   onPressSettings();
                              }}>
                              <ButtonIcon as={Settings} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />
                              <ButtonText
                                   sx={{
                                        color: theme['colors']['primary']['500-text'],
                                   }}
                                   size="sm"
                                   fontWeight="$medium">
                                   {getTermFromDictionary(language, 'browse_categories_manage')}
                              </ButtonText>
                         </Button>

                         <Button
                              isDisabled={refreshing}
                              sx={{
                                   bg: theme['colors']['primary']['500'],
                              }}
                              onPress={() => {
                                   setRefreshing(true);
                                   onRefreshCategories();
                                   setTimeout(function () {
                                        setRefreshing(false);
                                   });
                              }}>
                              {refreshing ? <ButtonSpinner /> : <ButtonIcon as={RotateCwIcon} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />}

                              <ButtonText size="sm" fontWeight="$medium" sx={{ color: theme['colors']['primary']['500-text'] }}>
                                   {getTermFromDictionary(language, 'browse_categories_refresh')}
                              </ButtonText>
                         </Button>
                    </ButtonGroup>
               </Center>
          );
     }

     return (
          <Center>
               <ButtonGroup flexDirection="column">
                    <Button
                         sx={{
                              bg: theme['colors']['primary']['500'],
                         }}
                         onPress={() => {
                              onPressSettings(libraryUrl, patronId);
                         }}>
                         <ButtonIcon as={Settings} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />
                         <ButtonText fontSize={10} fontWeight="$medium" sx={{ color: theme['colors']['primary']['500-text'] }}>
                              {getTermFromDictionary(language, 'browse_categories_manage')}
                         </ButtonText>
                    </Button>
                    <Button
                         sx={{
                              bg: theme['colors']['primary']['500'],
                         }}
                         onPress={() => {
                              onRefreshCategories(libraryUrl);
                         }}>
                         <ButtonIcon as={RotateCwIcon} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />
                         <ButtonText fontSize={10} fontWeight="$medium" sx={{ color: theme['colors']['primary']['500-text'] }}>
                              {getTermFromDictionary(language, 'browse_categories_refresh')}
                         </ButtonText>
                    </Button>
               </ButtonGroup>
          </Center>
     );
};