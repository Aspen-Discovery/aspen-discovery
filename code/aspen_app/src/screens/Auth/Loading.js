import AsyncStorage from '@react-native-async-storage/async-storage';
import { useIsFocused, useLinkTo, useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import * as Linking from 'expo-linking';
import * as Notifications from 'expo-notifications';
import _ from 'lodash';
import { Box, Center, Heading, Progress, VStack } from 'native-base';
import React from 'react';
import { checkVersion } from 'react-native-check-version';
import { BrowseCategoryContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../context/initialContext';
import { createGlueTheme } from '../../themes/theme';
import { getLanguageDisplayName, getTermFromDictionary, getTranslatedTermsForUserPreferredLanguage, translationsLibrary } from '../../translations/TranslationService';
import { getCatalogStatus, getLibraryInfo, getLibraryLanguages, getLibraryLinks, getSystemMessages } from '../../util/api/library';
import { getLocationInfo, getSelfCheckSettings } from '../../util/api/location';
import { fetchNotificationHistory, getAppPreferencesForUser, getLinkedAccounts, refreshProfile } from '../../util/api/user';
import { GLOBALS } from '../../util/globals';
import { LIBRARY, reloadBrowseCategories } from '../../util/loadLibrary';
import { getBrowseCategoryListForUser, PATRON } from '../../util/loadPatron';
import { CatalogOffline } from './CatalogOffline';
import { ForceLogout } from './ForceLogout';

const prefix = Linking.createURL('/');

Notifications.setNotificationHandler({
     handleNotification: async () => ({
          shouldShowAlert: true,
          shouldPlaySound: true,
          shouldSetBadge: false,
     }),
});

export const LoadingScreen = () => {
     const linkingUrl = Linking.useURL();
     const linkTo = useLinkTo();
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const isFocused = useIsFocused();
     //const state = useNavigationState((state) => state);
     const [progress, setProgress] = React.useState(0);
     const [isReloading, setIsReloading] = React.useState(false);
     const [hasError, setHasError] = React.useState(false);
     const [hasUpdate, setHasUpdate] = React.useState(false);
     const [incomingUrl, setIncomingUrl] = React.useState('');
     const [hasIncomingUrlChanged, setIncomingUrlChanged] = React.useState(false);

     const { user, updateUser, accounts, updateLinkedAccounts, cards, updateLibraryCards, updateAppPreferences, notificationHistory, updateNotificationHistory, updateInbox } = React.useContext(UserContext);
     const { library, updateLibrary, updateMenu, updateCatalogStatus, catalogStatus, catalogStatusMessage } = React.useContext(LibrarySystemContext);
     const { location, updateLocation, updateScope, updateEnableSelfCheck, updateSelfCheckSettings } = React.useContext(LibraryBranchContext);
     const { category, updateBrowseCategories, updateBrowseCategoryList, updateMaxCategories } = React.useContext(BrowseCategoryContext);
     const { language, updateLanguage, updateLanguages, updateDictionary, dictionary, languageDisplayName, updateLanguageDisplayName, languages } = React.useContext(LanguageContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const { theme, updateTheme, updateColorMode } = React.useContext(ThemeContext);

     const [loadingText, setLoadingText] = React.useState('');
     const [loadingTheme, setLoadingTheme] = React.useState(true);

     React.useEffect(() => {
          const unsubscribe = navigation.addListener('focus', async () => {
               // The screen is focused
               console.log('The screen is focused.');
               setIsReloading(true);
               setProgress(0);
               queryClient.queryCache.clear();
               //navigation.dispatch(StackActions.popToTop());
               try {
                    await AsyncStorage.getItem('@colorMode').then(async (mode) => {
                         if (mode === 'light' || mode === 'dark') {
                              updateColorMode(mode);
                         } else {
                              updateColorMode('light');
                         }
                    });
               } catch (e) {
                    // something went wrong (or the item didn't exist yet in storage)
                    // so just set it to the default: light
                    updateColorMode('light');
               }

               await createGlueTheme(LIBRARY.url).then((result) => {
                    updateTheme(result);
                    setLoadingTheme(false);
               });
          });

          return unsubscribe;
     }, [navigation]);

     const { status: catalogStatusQueryStatus, data: catalogStatusQuery } = useQuery(['catalog_status', LIBRARY.url], () => getCatalogStatus(LIBRARY.url), {
          enabled: !!LIBRARY.url && !loadingTheme,
          onSuccess: (data) => {
               updateCatalogStatus(data);
          },
     });

     const { status: translationQueryStatus, data: translationQuery } = useQuery(['active_language', PATRON.language, LIBRARY.url], () => getTranslatedTermsForUserPreferredLanguage(PATRON.language ?? 'en', LIBRARY.url), {
          enabled: !!LIBRARY.url && !!catalogStatusQuery,
          onSuccess: (data) => {
               setProgress(10);
               updateDictionary(translationsLibrary);
               setLoadingText(getTermFromDictionary(PATRON.language ?? 'en', 'loading_1'));
          },
     });

     const { status: languagesQueryStatus, data: languagesQuery } = useQuery(['languages', LIBRARY.url], () => getLibraryLanguages(LIBRARY.url), {
          enabled: !!translationQuery,
          onSuccess: (data) => {
               updateLanguages(data);
          },
     });

     /*const { status: translationsQueryStatus, data: translationsQuery } = useQuery(['translations', LIBRARY.url], () => getTranslatedTermsForAllLanguages(languagesQuery, LIBRARY.url), {
	 enabled: !!languagesQuery,
	 onSuccess: (data) => {
	 updateDictionary(translationsLibrary);
	 setLoadingText(getTermFromDictionary(language ?? 'en', 'loading_1'));
	 },
	 });*/

     React.useEffect(() => {
          const responseListener = Notifications.addNotificationResponseReceivedListener((response) => {
               const url = response?.notification?.request?.content?.data?.url ?? prefix;
               if (url !== incomingUrl) {
                    console.log('Incoming url changed');
                    console.log('OLD > ' + incomingUrl);
                    console.log('NEW > ' + url);
                    setIncomingUrl(response?.notification?.request?.content?.data?.url ?? prefix);
                    setIncomingUrlChanged(true);
               } else {
                    setIncomingUrlChanged(false);
               }
          });

          return () => {
               responseListener.remove();
          };
     }, []);

     const { status: librarySystemQueryStatus, data: librarySystemQuery } = useQuery(['library_system', LIBRARY.url], () => getLibraryInfo(LIBRARY.url), {
          enabled: !!languagesQuery,
          onSuccess: (data) => {
               setProgress(20);
               updateLibrary(data);
          },
     });

     const { status: userQueryStatus, data: userQuery } = useQuery(['user', LIBRARY.url, 'en'], () => refreshProfile(LIBRARY.url), {
          enabled: !!librarySystemQuery,
          onSuccess: (data) => {
               console.log(data);
               if (_.isUndefined(data) || _.isEmpty(data)) {
                    setHasError(true);
               } else {
                    if (data.success === false || data.success === 'false') {
                         setHasError(true);
                    } else {
                         setProgress(30);
                         updateUser(data);
                         updateLanguage(data.interfaceLanguage ?? 'en');
                         updateLanguageDisplayName(getLanguageDisplayName(data.interfaceLanguage ?? 'en', languages));
                         PATRON.language = data.interfaceLanguage ?? 'en';
                         setLoadingText(getTermFromDictionary(language ?? 'en', 'loading_2'));
                    }
               }
          },
     });

     const { status: libraryLinksQueryStatus, data: libraryLinksQuery } = useQuery(['library_links', LIBRARY.url], () => getLibraryLinks(LIBRARY.url), {
          enabled: hasError === false && !!userQueryStatus,
          onSuccess: (data) => {
               setProgress(50);
               updateMenu(data);
          },
     });

     const { status: browseCategoryQueryStatus, data: browseCategoryQuery } = useQuery(['browse_categories', LIBRARY.url, 'en', false], () => reloadBrowseCategories(5, LIBRARY.url), {
          enabled: hasError === false && !!libraryLinksQuery && !!userQueryStatus,
          onSuccess: (data) => {
               setProgress(60);
               updateBrowseCategories(data);
               updateMaxCategories(5);
          },
     });
     const { status: browseCategoryListQueryStatus, data: browseCategoryListQuery } = useQuery(['browse_categories_list', LIBRARY.url, 'en'], () => getBrowseCategoryListForUser(LIBRARY.url), {
          enabled: hasError === false && !!browseCategoryQuery && !!userQueryStatus,
          onSuccess: (data) => {
               setProgress(70);
               updateBrowseCategoryList(data);
          },
     });

     const { status: libraryBranchQueryStatus, data: libraryBranchQuery } = useQuery(['library_location', LIBRARY.url, 'en'], () => getLocationInfo(LIBRARY.url), {
          enabled: hasError === false && !!browseCategoryListQuery && !!userQueryStatus,
          onSuccess: (data) => {
               setProgress(80);
               updateLocation(data);
          },
     });

     const { status: selfCheckQueryStatus, data: selfCheckQuery } = useQuery(['self_check_settings', LIBRARY.url, 'en'], () => getSelfCheckSettings(LIBRARY.url), {
          enabled: hasError === false && !!userQuery && !!libraryBranchQuery && !!userQueryStatus,
          onSuccess: (data) => {
               setProgress(85);
               if (data.success) {
                    updateEnableSelfCheck(data.settings?.isEnabled ?? false);
                    updateSelfCheckSettings(data.settings);
               } else {
                    updateEnableSelfCheck(false);
               }
          },
     });

     const { status: linkedAccountQueryStatus, data: linkedAccountQuery } = useQuery(['linked_accounts', user ?? [], cards ?? [], LIBRARY.url, 'en'], () => getLinkedAccounts(user ?? [], cards ?? [], library.barcodeStyle, LIBRARY.url, 'en'), {
          enabled: hasError === false && !!selfCheckQuery,
          onSuccess: (data) => {
               updateLinkedAccounts(data.accounts);
               setIsReloading(false);
          },
     });

     const { status: libraryCardsQueryStatus, data: libraryCardsQuery } = useQuery(['library_cards', user ?? [], cards ?? [], LIBRARY.url, 'en'], () => getLinkedAccounts(user ?? [], cards ?? [], library.barcodeStyle, LIBRARY.url, 'en'), {
          enabled: hasError === false && !!linkedAccountQuery,
          onSuccess: (data) => {
               setProgress(90);
               updateLibraryCards(data.cards);
               setIsReloading(false);
          },
     });

     const { status: systemMessagesQueryStatus, data: systemMessagesQuery } = useQuery(['system_messages', LIBRARY.url], () => getSystemMessages(library.libraryId, location.locationId, LIBRARY.url), {
          enabled: hasError === false && !!libraryCardsQuery,
          onSuccess: (data) => {
               updateSystemMessages(data);
               setIsReloading(false);
          },
     });

     const { status: appPreferencesQueryStatus, data: appPreferencesQuery } = useQuery(['app_preferences', LIBRARY.url], () => getAppPreferencesForUser(LIBRARY.url, 'en'), {
          enabled: hasError === false && !!systemMessagesQuery,
          onSuccess: (data) => {
               updateAppPreferences(data);
               setProgress(100);
               setIsReloading(false);
          },
     });

     const { status: notificationHistoryQueryStatus, data: notificationHistoryQuery } = useQuery(['notification_history'], () => fetchNotificationHistory(1, 20, true, library.baseUrl, 'en'), {
          enabled: hasError === false && !!appPreferencesQuery,
          onSuccess: (data) => {
               updateNotificationHistory(data);
               updateInbox(data?.inbox ?? []);
               setIsReloading(false);
          },
     });

     if (hasError) {
          return <ForceLogout />;
     }

     if (catalogStatus > 0) {
          // catalog is offline
          return <CatalogOffline />;
     }

     if (
          (isReloading && librarySystemQueryStatus === 'loading') ||
          catalogStatusQueryStatus === 'loading' ||
          userQueryStatus === 'loading' ||
          browseCategoryQueryStatus === 'loading' ||
          browseCategoryListQueryStatus === 'loading' ||
          languagesQueryStatus === 'loading' ||
          libraryBranchQueryStatus === 'loading' ||
          linkedAccountQueryStatus === 'loading' ||
          libraryCardsQueryStatus === 'loading' ||
          systemMessagesQueryStatus === 'loading' ||
          appPreferencesQueryStatus === 'loading' ||
          notificationHistoryQueryStatus === 'loading'
     ) {
          return (
               <Center flex={1} px="3" w="100%">
                    <Box w="90%" maxW="400">
                         <VStack>
                              <Heading pb={5} color="primary.500" fontSize="md">
                                   {loadingText}
                              </Heading>
                              <Progress size="lg" value={progress} colorScheme="primary" />
                         </VStack>
                    </Box>
               </Center>
          );
     }

     if (
          (!isReloading && librarySystemQueryStatus === 'success') ||
          catalogStatusQueryStatus === 'success' ||
          userQueryStatus === 'success' ||
          browseCategoryQueryStatus === 'success' ||
          browseCategoryListQueryStatus === 'success' ||
          languagesQueryStatus === 'success' ||
          libraryBranchQueryStatus === 'success' ||
          linkedAccountQueryStatus === 'success' ||
          libraryCardsQueryStatus === 'success' ||
          systemMessagesQueryStatus === 'success' ||
          appPreferencesQueryStatus === 'success' ||
          notificationHistoryQueryStatus === 'success'
     ) {
          if (hasIncomingUrlChanged) {
               let url = decodeURIComponent(incomingUrl).replace(/\+/g, ' ');
               url = url.replace('aspen-lida://', prefix);
               console.log('incomingUrl > ' + url);
               setIncomingUrlChanged(false);
               try {
                    console.log('Trying to open screen based on incomingUrl...');
                    Linking.openURL(url);
               } catch (e) {
                    console.log(e);
               }
          } else if (linkingUrl) {
               if (linkingUrl !== prefix && linkingUrl !== incomingUrl) {
                    setIncomingUrl(linkingUrl);
                    console.log('Updated incoming url');
                    const { hostname, path, queryParams, scheme } = Linking.parse(linkingUrl);
                    console.log('linkingUrl > ' + linkingUrl);
                    console.log(`Linked to app with hostname: ${hostname}, path: ${path}, scheme: ${scheme} and data: ${JSON.stringify(queryParams)}`);
                    try {
                         if (scheme !== 'exp') {
                              console.log('Trying to open screen based on linkingUrl...');
                              const url = linkingUrl.replace('aspen-lida://', prefix);
                              console.log('url > ' + url);
                              linkTo('/' + url);
                         } else {
                              if (path) {
                                   console.log('Trying to open screen based on linkingUrl to Expo app...');
                                   let url = '/' + path;
                                   if (!_.isEmpty(queryParams)) {
                                        const params = new URLSearchParams(queryParams);
                                        const str = params.toString();
                                        url = url + '?' + str + '&url=' + library.baseUrl;
                                   }
                                   console.log('url > ' + url);
                                   console.log('linkingUrl > ' + linkingUrl);
                                   linkTo('/' + url);
                              }
                         }
                    } catch (e) {
                         console.log(e);
                    }
               }
          }

          navigation.navigate('DrawerStack', {
               user: user,
               library: library,
               location: location,
               prevRoute: 'LoadingScreen',
          });
     }
};

async function checkStoreVersion() {
     try {
          const version = await checkVersion({
               bundleId: GLOBALS.bundleId,
               currentVersion: GLOBALS.appVersion,
          });
          if (version.needsUpdate) {
               return {
                    needsUpdate: true,
                    url: version.url,
                    latest: version.version,
               };
          }
     } catch (e) {
          console.log(e);
     }

     return {
          needsUpdate: false,
          url: null,
          latest: GLOBALS.appVersion,
     };
}