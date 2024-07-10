import { ScanBarcode, SearchIcon, XIcon, Settings, RotateCwIcon, ClockIcon } from 'lucide-react-native';
import { Center, Box, Button, ButtonGroup, ButtonIcon, ButtonText, ButtonSpinner, HStack, Badge, BadgeText, FormControl, Input, InputField, InputSlot, InputIcon, Pressable, ScrollView, Text } from '@gluestack-ui/themed';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { useIsFetching, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import React from 'react';
import { Image } from 'expo-image';
import * as Device from 'expo-device';
import { Platform } from 'react-native';
import { compareVersions } from 'compare-versions';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplayAndroidEndOfSupportMessage, DisplaySystemMessage } from '../../components/Notifications';
import { NotificationsOnboard } from '../../components/NotificationsOnboard';
import { BrowseCategoryContext, LanguageContext, LibrarySystemContext, SearchContext, SystemMessagesContext, ThemeContext, UserContext } from '../../context/initialContext';
import { navigateStack, pushNavigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { formatDiscoveryVersion, reloadBrowseCategories } from '../../util/loadLibrary';
import { updateBrowseCategoryStatus } from '../../util/loadPatron';
import { getDefaultFacets, getSearchIndexes, getSearchSources } from '../../util/search';
import DisplayBrowseCategory from './Category';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const DiscoverHomeScreen = () => {
     const isQueryFetching = useIsFetching();
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const isFetchingBrowseCategories = useIsFetching({ queryKey: ['browse_categories'] });
     const isFocused = useIsFocused();
     const [loading, setLoading] = React.useState(false);

     const { theme, textColor } = React.useContext(ThemeContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const { updateIndexes, updateSources, updateCurrentIndex, updateCurrentSource } = React.useContext(SearchContext);
     const { notificationOnboard } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { category, updateMaxCategories, maxNum, updateBrowseCategories } = React.useContext(BrowseCategoryContext);
     const { language } = React.useContext(LanguageContext);

     const [preliminaryLoadingCheck, setPreliminaryCheck] = React.useState(false);

     const version = formatDiscoveryVersion(library.discoveryVersion);
     const [searchTerm, setSearchTerm] = React.useState('');

     const [promptOpen, setPromptOpen] = React.useState('');

     const [showAndroidEndSupportMessage, setShowAndroidEndSupportMessage] = React.useState(false);
     const [androidEndSupportMessageIsOpen, setAndroidEndSupportMessageIsOpen] = React.useState(false);

     navigation.setOptions({
          headerLeft: () => {
               return null;
          },
     });

     useFocusEffect(
          React.useCallback(() => {
               const checkSettings = async () => {
                    if (Platform.OS === 'android') {
                         if (Device.platformApiLevel <= 30) {
                              // SDK 30 == Android 11
                              console.log('Android SDK is 30 or older');
                              setShowAndroidEndSupportMessage(true);
                              setAndroidEndSupportMessageIsOpen(true);
                         }
                    }

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

                    console.log('notificationOnboard: ' + notificationOnboard);
                    if (!_.isUndefined(notificationOnboard)) {
                         if (notificationOnboard === 1 || notificationOnboard === 2 || notificationOnboard === '1' || notificationOnboard === '2') {
                              console.log('Notification onboarding preferences found. Set to 1 or 2. Show onboard prompt.');
                              setPreliminaryCheck(true);
                              setPromptOpen('yes');
                         } else {
                              console.log('Notification onboarding preferences found. Set to 0. Do not show onboard prompt.');
                              setPreliminaryCheck(true);
                              setPromptOpen('');
                         }
                    } else {
                         console.log('No notification onboarding preferences found. Show onboard prompt.');
                         setPreliminaryCheck(true);
                         setPromptOpen('yes');
                    }
               };
               checkSettings().then(() => {
                    return () => checkSettings();
               });
          }, [language])
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
                                        fontSize: 16,
                                   },
                                   '@lg': {
                                        fontSize: 22,
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
               if (item.source === 'library_calendar' || item.source === 'springshare_libcal' || item.source === 'communico' || item.source === 'assabet') {
                    type = 'Event';
               } else {
                    type = item.source;
               }
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
               if (_.includes(id, 'assabet_')) {
                    type = 'assabet_event';
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
                    <Image
                         alt={item.title_display}
                         source={imageUrl}
                         style={{
                              width: '100%',
                              height: '100%',
                              borderRadius: 4,
                         }}
                         placeholder={blurhash}
                         transition={1000}
                         contentFit="cover"
                    />
                    {isNew ? (
                         <Box zIndex={1} alignItems="center">
                              <Badge bgColor={theme['colors']['warning']['500']} mx={5} mt={-8}>
                                   <BadgeText bold color={theme['colors']['white']} textTransform="none">
                                        {getTermFromDictionary(language, 'flag_new')}
                                   </BadgeText>
                              </Badge>
                         </Box>
                    ) : null}
               </Pressable>
          );
     };

     const onPressItem = (key, type, title, version) => {
          if (type === 'List' || type === 'list') {
               navigateStack('BrowseTab', 'SearchByList', {
                    id: key,
                    title: title,
                    prevRoute: 'HomeScreen',
               });
          } else if (type === 'SavedSearch') {
               navigateStack('BrowseTab', 'SearchBySavedSearch', {
                    id: key,
                    title: title,
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
               } else if (type === 'assabet_event') {
                    eventSource = 'assabet';
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
     };

     const renderLoadMore = () => {};

     const onHideCategory = async (url, category) => {
          setLoading(true);
          await updateBrowseCategoryStatus(category);
          await queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language, maxNum] });
          await queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
          setLoading(false);
     };

     const onRefreshCategories = async () => {
          setLoading(true);
          await queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language, maxNum] });
          await queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
          setLoading(false);
     };

     const onLoadAllCategories = async () => {
          updateMaxCategories(9999);
          setLoading(true);
          await reloadBrowseCategories(9999, library.baseUrl).then((result) => {
               updateBrowseCategories(result);
               queryClient.setQueryData(['browse_categories', library.baseUrl, language, maxNum], result);
               queryClient.setQueryData(['browse_categories', library.baseUrl, language, 9999], result);
          });
          setLoading(false);
     };

     const onPressSettings = () => {
          navigateStack('MoreTab', 'MyPreferences_ManageBrowseCategories', { prevRoute: 'HomeScreen' });
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

     const androidEndSupportMessage = () => {
          if (showAndroidEndSupportMessage && androidEndSupportMessageIsOpen) {
               return <DisplayAndroidEndOfSupportMessage language={language} setIsOpen={setAndroidEndSupportMessageIsOpen} isOpen={androidEndSupportMessageIsOpen} />;
          }
     };

     if (loading === true || isFetchingBrowseCategories) {
          return loadingSpinner();
     }

     /* 
     // load notification onboarding prompt
     if (isQueryFetching === 0 && preliminaryLoadingCheck) {
          if (notificationOnboard !== '0' && notificationOnboard !== 0) {
               if (isFocused && promptOpen === 'yes') {
                    return <NotificationsOnboard isFocused={isFocused} promptOpen={promptOpen} setPromptOpen={setPromptOpen} />;
               }
          }
     }*/

     const clearSearch = () => {
          setSearchTerm('');
     };

     return (
          <ScrollView>
               <Box p="$5">
                    {androidEndSupportMessage()}
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
                         return <DisplayBrowseCategory textColor={textColor} language={language} key={index} categoryLabel={item.title} categoryKey={item.key} id={item.id} records={item.records} isHidden={item.isHidden} categorySource={item.source} renderRecords={renderRecord} header={renderHeader} hideCategory={onHideCategory} libraryUrl={library.baseUrl} loadMore={renderLoadMore} discoveryVersion={library.version} onPressCategory={handleOnPressCategory} categoryList={category} />;
                    })}
                    <ButtonOptions language={language} onPressSettings={onPressSettings} onRefreshCategories={onRefreshCategories} discoveryVersion={library.discoveryVersion} maxNum={maxNum} onLoadAllCategories={onLoadAllCategories} />
               </Box>
          </ScrollView>
     );
};

const ButtonOptions = (props) => {
     const { theme } = React.useContext(ThemeContext);
     const [loading, setLoading] = React.useState(false);
     const [refreshing, setRefreshing] = React.useState(false);
     const { language, onPressSettings, onRefreshCategories, discoveryVersion, maxNum, onLoadAllCategories } = props;

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
                         <Button
                              isDisabled={maxNum === 9999}
                              sx={{
                                   bg: theme['colors']['primary']['500'],
                                   size: 'md',
                              }}
                              onPress={() => {
                                   setLoading(true);
                                   onLoadAllCategories();
                                   setTimeout(function () {
                                        setLoading(false);
                                   }, 2500);
                              }}>
                              {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} mr="$1" /> : <ButtonIcon as={ClockIcon} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />}
                              <ButtonText
                                   sx={{
                                        color: theme['colors']['primary']['500-text'],
                                   }}
                                   size="sm"
                                   fontWeight="$medium">
                                   {getTermFromDictionary(language, 'browse_categories_load_all')}
                              </ButtonText>
                         </Button>

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
                              {refreshing ? <ButtonSpinner color={theme['colors']['primary']['500-text']} /> : <ButtonIcon as={RotateCwIcon} color={theme['colors']['primary']['500-text']} mr="$1" size="sm" />}

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
                              onPressSettings();
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
                              onRefreshCategories();
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