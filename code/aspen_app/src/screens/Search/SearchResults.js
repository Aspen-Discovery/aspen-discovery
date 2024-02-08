import { MaterialIcons } from '@expo/vector-icons';
import { CommonActions, useNavigation, useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import CachedImage from 'expo-cached-image';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import moment from 'moment';

import { Badge, Box, Button, Center, Container, FlatList, Heading, HStack, Icon, Pressable, Stack, Text, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView, ScrollView } from 'react-native';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';

import { LanguageContext, LibraryBranchContext, LibrarySystemContext, SearchContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { getCleanTitle } from '../../helpers/item';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../translations/TranslationService';
import { createAuthTokens, getHeaders } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion } from '../../util/loadLibrary';
import { getAppliedFilters, getAvailableFacetsKeys, getSortList, SEARCH, setDefaultFacets } from '../../util/search';
import AddToList from './AddToList';

export const SearchResults = () => {
     const navigation = useNavigation();
     const route = useRoute();
     const [page, setPage] = React.useState(1);
     const [storedTerm, setStoredTerm] = React.useState('');
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { scope } = React.useContext(LibraryBranchContext);
     const { currentIndex, currentSource, updateCurrentIndex, updateCurrentSource, updateIndexes, updateSources } = React.useContext(SearchContext);
     const url = library.baseUrl;

     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     let term = useRoute().params.term ?? '%';
     term = term.replace(/" "/g, '%20');

     let isScannerSearch = useRoute().params.scannerSearch ?? false;

     let params = useRoute().params.pendingParams ?? [];

     const prevRoute = useRoute().params.prevRoute ?? 'SearchHome';

     const type = useRoute().params.type ?? 'catalog';
     const id = useRoute().params.id ?? null;

     const systemMessagesForScreen = [];

     if (term && term !== storedTerm) {
          console.log('Search term changed. Clearing previous search options...');
          setStoredTerm(term);
          SEARCH.pendingFilters = [];
          SEARCH.sortMethod = 'relevance';
          SEARCH.appliedFilters = [];
          SEARCH.sortList = [];
          SEARCH.availableFacets = [];
          SEARCH.defaultFacets = [];
          SEARCH.pendingFilters = [];
          SEARCH.appendedParams = '';
          params = [];
     }

     React.useEffect(() => {
          if (_.isArray(systemMessages)) {
               systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         systemMessagesForScreen.push(obj);
                    }
               });
          }
     }, [systemMessages]);

     const { status, data, error, isFetching, isPreviousData } = useQuery({
          queryKey: ['searchResults', url, page, term, scope, params, type, id, language, currentIndex, currentSource],
          queryFn: () => fetchSearchResults(term, page, scope, url, type, id, language, currentIndex, currentSource),
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               if ((data.totalResults === 1 || data.totalResults === '1') && isScannerSearch) {
                    const result = data.results[0];
                    if (result.key) {
                         navigate('GroupedWorkScreen', {
                              id: result.key,
                              title: getCleanTitle(result.title),
                              url: library.baseUrl,
                              libraryContext: library,
                         });
                    }
               }
          },
     });

     const { data: paginationLabel, isFetching: translationIsFetching } = useQuery({
          queryKey: ['totalPages', url, page, term, scope, params, language, currentIndex, currentSource],
          queryFn: () => getTranslationsWithValues('page_of_page', [page, data?.totalPages], language, library.baseUrl),
          enabled: !!data,
     });

     const Header = () => {
          const num = _.toInteger(data?.totalResults);
          if (num > 0) {
               let label = num + ' ' + getTermFromDictionary(language, 'results');
               if (num === 1) {
                    label = num + ' ' + getTermFromDictionary(language, 'result');
               }
               return (
                    <Box
                         bgColor="coolGray.100"
                         borderBottomWidth="1"
                         _dark={{
                              borderColor: 'gray.600',
                              bg: 'coolGray.700',
                         }}
                         borderColor="coolGray.200">
                         <Container m={2}>
                              <Text>{label}</Text>
                         </Container>
                    </Box>
               );
          }

          return null;
     };

     const Paging = () => {
          if (data.totalPages > 1) {
               return (
                    <Box
                         safeArea={2}
                         bgColor="coolGray.100"
                         borderTopWidth="1"
                         _dark={{
                              borderColor: 'gray.600',
                              bg: 'coolGray.700',
                         }}
                         borderColor="coolGray.200"
                         flexWrap="nowrap"
                         alignItems="center">
                         <ScrollView horizontal>
                              <Button.Group size="sm">
                                   <Button onPress={() => setPage(page - 1)} isDisabled={page === 1}>
                                        {getTermFromDictionary(language, 'previous')}
                                   </Button>
                                   <Button
                                        onPress={() => {
                                             if (!isPreviousData && data.hasMore) {
                                                  console.log('Adding to page');
                                                  setPage(page + 1);
                                             }
                                        }}
                                        isDisabled={isPreviousData || !data.hasMore}>
                                        {getTermFromDictionary(language, 'next')}
                                   </Button>
                              </Button.Group>
                         </ScrollView>
                         <Text mt={2} fontSize="sm">
                              {paginationLabel}
                         </Text>
                    </Box>
               );
          }

          return null;
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

     const NoResults = () => {
          return (
               <>
                    {_.size(systemMessagesForScreen) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
                    <Center flex={1}>
                         <Heading pt={5}>{getTermFromDictionary(language, 'no_results')}</Heading>
                         <Text bold w="75%" textAlign="center">
                              {route.params?.term}
                         </Text>
                         <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>
                              {getTermFromDictionary(language, 'new_search_button')}
                         </Button>
                    </Center>
               </>
          );
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessagesForScreen) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
               {status === 'loading' || isFetching || translationIsFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <Box flex={1}>
                         {data.totalResults > 0 ? <FilterBar /> : null}
                         <FlatList data={data.results} ListHeaderComponent={Header} ListFooterComponent={Paging} ListEmptyComponent={NoResults} renderItem={({ item }) => <DisplayResult data={item} />} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               )}
          </SafeAreaView>
     );
};

const DisplayResult = (data) => {
     const item = data.data;
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const { language } = React.useContext(LanguageContext);
     const { currentIndex, currentSource } = React.useContext(SearchContext);
     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

     const handlePressItem = () => {
          if (currentSource === 'events') {
               let eventSource = item.source;
               if (item.source === 'lc') {
                    eventSource = 'library_calendar';
               }
               if (item.source === 'libcal' || item.source === 'springshare_libcal') {
                    eventSource = 'springshare';
               }

               if (item.bypass) {
                    openURL(item.url);
               } else {
                    navigate('EventScreen', {
                         id: item.key,
                         title: getCleanTitle(item.title),
                         url: library.baseUrl,
                         source: eventSource,
                    });
               }
          } else {
               if (version >= '23.01.00') {
                    navigate('GroupedWorkScreen', {
                         id: item.key,
                         title: getCleanTitle(item.title),
                         url: library.baseUrl,
                         libraryContext: library,
                    });
               } else {
                    navigate('GroupedWorkScreen221200', {
                         id: item.key,
                         title: getCleanTitle(item.title),
                         url: library.baseUrl,
                         userContext: user,
                         libraryContext: library,
                    });
               }
          }
     };

     const formats = item?.itemList ?? [];

     function getFormat(n) {
          return (
               <Badge key={n.key} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
                    {n.name}
               </Badge>
          );
     }

     const openURL = async (url) => {
          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };
          WebBrowser.openBrowserAsync(url, browserParams);
     };

     const imageUrl = item.image;

     const key = 'medium_' + item.key;
     let url = library.baseUrl + '/bookcover.php?id=' + item.key + '&size=medium';

     if (currentSource === 'events') {
          //console.log(item);
          url = imageUrl;
          let registrationRequired = false;
          if (!_.isUndefined(item.registration_required)) {
               registrationRequired = item.registration_required;
          }

          const startTime = item.start_date.date;
          const endTime = item.end_date.date;

          let time1 = startTime.split(' ');
          let day = time1[0];
          let time2 = endTime.split(' ');

          let time1arr = time1[1].split(':');
          let time2arr = time2[1].split(':');

          let displayDay = moment(day);
          let displayStartTime = moment().set({ hour: time1arr[0], minute: time1arr[1] });
          let displayEndTime = moment().set({ hour: time2arr[0], minute: time2arr[1] });

          displayDay = moment(displayDay).format('dddd, MMMM D, YYYY');
          displayStartTime = moment(displayStartTime).format('h:mm A');
          displayEndTime = moment(displayEndTime).format('h:mm A');

          let locationData = item?.location ?? [];
          let roomData = item?.room ?? null;

          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={handlePressItem}>
                    <HStack space={3}>
                         <VStack maxW="35%">
                              <CachedImage
                                   cacheKey={key}
                                   alt={item.title}
                                   source={{
                                        uri: `${url}`,
                                        expiresIn: 86400,
                                   }}
                                   style={{
                                        width: 100,
                                        height: 150,
                                        borderRadius: 4,
                                   }}
                                   resizeMode="cover"
                                   placeholderContent={
                                        <Box
                                             bg="warmGray.50"
                                             _dark={{
                                                  bgColor: 'coolGray.800',
                                             }}
                                             width={{
                                                  base: 100,
                                                  lg: 200,
                                             }}
                                             height={{
                                                  base: 150,
                                                  lg: 250,
                                             }}
                                        />
                                   }
                              />
                              {item.canAddToList ? <AddToList source="Events" itemId={item.key} btnStyle="sm" /> : null}
                         </VStack>
                         <VStack w="65%">
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
                              {item.start_date && item.end_date ? (
                                   <>
                                        <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                             {displayDay}
                                        </Text>
                                        <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                             {displayStartTime} - {displayEndTime}
                                        </Text>
                                   </>
                              ) : null}
                              {locationData.name ? (
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                        {locationData.name}
                                   </Text>
                              ) : null}
                              {registrationRequired ? (
                                   <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                                        <Badge key={0} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
                                             {getTermFromDictionary(language, 'registration_required')}
                                        </Badge>
                                   </Stack>
                              ) : null}
                         </VStack>
                    </HStack>
               </Pressable>
          );
     }

     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={handlePressItem}>
               <HStack space={3}>
                    <VStack maxW="35%">
                         <CachedImage
                              cacheKey={key}
                              alt={item.title}
                              source={{
                                   uri: `${url}`,
                                   expiresIn: 86400,
                              }}
                              style={{
                                   width: 100,
                                   height: 150,
                                   borderRadius: 4,
                              }}
                              resizeMode="cover"
                              placeholderContent={
                                   <Box
                                        bg="warmGray.50"
                                        _dark={{
                                             bgColor: 'coolGray.800',
                                        }}
                                        width={{
                                             base: 100,
                                             lg: 200,
                                        }}
                                        height={{
                                             base: 150,
                                             lg: 250,
                                        }}
                                   />
                              }
                         />
                         {item.language ? (
                              <Badge
                                   mt={1}
                                   _text={{
                                        fontSize: 10,
                                        color: 'coolGray.600',
                                   }}
                                   bgColor="warmGray.200"
                                   _dark={{
                                        bgColor: 'coolGray.900',
                                        _text: { color: 'warmGray.400' },
                                   }}>
                                   {item.language}
                              </Badge>
                         ) : null}
                         <AddToList itemId={item.key} btnStyle="sm" />
                    </VStack>
                    <VStack w="65%">
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
                         {item.author ? (
                              <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                   {getTermFromDictionary(language, 'by')} {item.author}
                              </Text>
                         ) : null}
                         <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                              {_.map(formats, getFormat)}
                         </Stack>
                    </VStack>
               </HStack>
          </Pressable>
     );
};

const FilterBar = () => {
     const navigation = useNavigation();
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const type = useRoute().params.type ?? 'catalog';

     if (version >= '22.11.00' && type === 'catalog') {
          return (
               <Box
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderBottomWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200"
                    flexWrap="nowrap">
                    <ScrollView horizontal>
                         <Button
                              size="sm"
                              leftIcon={<Icon as={MaterialIcons} name="tune" size="sm" />}
                              variant="solid"
                              mr={1}
                              onPress={() => {
                                   navigation.push('modal', {
                                        screen: 'Filters',
                                        params: {
                                             pendingUpdates: [],
                                        },
                                   });
                              }}>
                              {getTermFromDictionary(language, 'filters')}
                         </Button>
                         <CreateFilterButton />
                    </ScrollView>
               </Box>
          );
     }
};

const CreateFilterButtonDefaults = () => {
     const navigation = useNavigation();
     const defaults = SEARCH.defaultFacets;
     const { location } = React.useContext(LibraryBranchContext);
     const { library } = React.useContext(LibrarySystemContext);

     const locationGroupedWorkDisplaySettings = location.groupedWorkDisplaySettings ?? [];
     const libraryGroupedWorkDisplaySettings = library.groupedWorkDisplaySettings ?? [];

     let defaultAvailabilityToggleLabel = 'Entire Collection';
     let defaultAvailabilityToggleValue = 'global';
     if (locationGroupedWorkDisplaySettings.availabilityToggleValue) {
          defaultAvailabilityToggleValue = locationGroupedWorkDisplaySettings.availabilityToggleValue;
     } else if (libraryGroupedWorkDisplaySettings.availabilityToggleValue) {
          defaultAvailabilityToggleValue = libraryGroupedWorkDisplaySettings.availabilityToggleValue;
     }

     if (defaultAvailabilityToggleValue === 'global') {
          if (locationGroupedWorkDisplaySettings.superScopeLabel || _.isEmpty(locationGroupedWorkDisplaySettings.superScopeLabel)) {
               defaultAvailabilityToggleLabel = locationGroupedWorkDisplaySettings.superScopeLabel;
          } else if (libraryGroupedWorkDisplaySettings.superScopeLabel || _.isEmpty(libraryGroupedWorkDisplaySettings.superScopeLabel)) {
               defaultAvailabilityToggleLabel = libraryGroupedWorkDisplaySettings.superScopeLabel;
          }
     } else if (defaultAvailabilityToggleValue === 'local') {
          if (locationGroupedWorkDisplaySettings.localLabel || _.isEmpty(locationGroupedWorkDisplaySettings.localLabel)) {
               defaultAvailabilityToggleLabel = locationGroupedWorkDisplaySettings.localLabel;
          } else if (libraryGroupedWorkDisplaySettings.localLabel || _.isEmpty(libraryGroupedWorkDisplaySettings.localLabel)) {
               defaultAvailabilityToggleLabel = libraryGroupedWorkDisplaySettings.localLabel;
          }
     } else if (defaultAvailabilityToggleValue === 'available') {
          if (locationGroupedWorkDisplaySettings.availableLabel || _.isEmpty(locationGroupedWorkDisplaySettings.availableLabel)) {
               defaultAvailabilityToggleLabel = locationGroupedWorkDisplaySettings.availableLabel;
          } else if (libraryGroupedWorkDisplaySettings.availableLabel || _.isEmpty(libraryGroupedWorkDisplaySettings.availableLabel)) {
               defaultAvailabilityToggleLabel = libraryGroupedWorkDisplaySettings.availableLabel;
          }
     } else if (defaultAvailabilityToggleValue === 'available_online') {
          if (locationGroupedWorkDisplaySettings.availableOnlineLabel || _.isEmpty(locationGroupedWorkDisplaySettings.availableOnlineLabel)) {
               defaultAvailabilityToggleLabel = locationGroupedWorkDisplaySettings.availableOnlineLabel;
          } else if (libraryGroupedWorkDisplaySettings.availableOnlineLabel || _.isEmpty(libraryGroupedWorkDisplaySettings.availableOnlineLabel)) {
               defaultAvailabilityToggleLabel = libraryGroupedWorkDisplaySettings.availableOnlineLabel;
          }
     }

     return (
          <Button.Group size="sm" space={1} vertical variant="outline">
               {defaults.map((obj, index) => {
                    if (obj['field'] === 'availability_toggle') {
                         const label = obj['label'] + ': ' + defaultAvailabilityToggleLabel;
                         return (
                              <Button
                                   key={index}
                                   variant="outline"
                                   _dark={{
                                        borderColor: 'gray.400',
                                   }}
                                   onPress={() => {
                                        navigation.push('modal', {
                                             screen: 'Facet',
                                             params: {
                                                  navigation: navigation,
                                                  key: obj['field'],
                                                  title: obj['label'],
                                                  facets: SEARCH.availableFacets[obj['label']].facets,
                                                  pendingUpdates: [],
                                                  extra: obj,
                                             },
                                        });
                                   }}>
                                   {label}
                              </Button>
                         );
                    }

                    return (
                         <Button
                              key={index}
                              variant="outline"
                              _dark={{
                                   borderColor: 'gray.400',
                              }}
                              onPress={() => {
                                   navigation.push('modal', {
                                        screen: 'Facet',
                                        params: {
                                             navigation: navigation,
                                             key: obj['field'],
                                             title: obj['label'],
                                             facets: SEARCH.availableFacets[obj['label']].facets,
                                             pendingUpdates: [],
                                             extra: obj,
                                        },
                                   });
                              }}>
                              {obj['label']}
                         </Button>
                    );
               })}
          </Button.Group>
     );
};

const CreateFilterButton = () => {
     const { currentSource } = React.useContext(SearchContext);
     const navigation = useNavigation();
     const appliedFacets = SEARCH.appliedFilters;
     const sort = _.find(appliedFacets['Sort By'], {
          field: 'sort_by',
          value: 'relevance',
     });

     if ((_.size(appliedFacets) > 0 && _.size(sort) === 0) || (_.size(appliedFacets) >= 1 && _.size(sort) > 1) || (_.size(appliedFacets) >= 1 && currentSource === 'events')) {
          return (
               <Button.Group size="sm" space={1} vertical variant="outline">
                    {_.map(appliedFacets, function (item, index, collection) {
                         const cluster = _.filter(SEARCH.availableFacets, ['field', item[0]['field']]);
                         let labels = '';
                         _.forEach(item, function (value, key) {
                              let label = value['display'];
                              if (item[0].field === 'sort_by') {
                                   label = getSortLabel(label);
                              }
                              if (labels.length === 0) {
                                   labels = labels.concat(_.toString(label));
                              } else {
                                   labels = labels.concat(', ', _.toString(label));
                              }
                         });
                         const label = _.truncate(index + ': ' + labels);
                         return (
                              <Button
                                   key={index}
                                   _dark={{
                                        borderColor: 'gray.400',
                                   }}
                                   onPress={() => {
                                        navigation.push('modal', {
                                             screen: 'Facet',
                                             params: {
                                                  data: item,
                                                  navigation,
                                                  defaultValues: [],
                                                  key: item[0]['field'],
                                                  title: cluster[0]['label'],
                                                  facets: item[0]['facets'],
                                                  pendingUpdates: [],
                                                  extra: cluster[0],
                                             },
                                        });
                                   }}>
                                   {label}
                              </Button>
                         );
                    })}
               </Button.Group>
          );
     }

     return <CreateFilterButtonDefaults />;
};

async function fetchSearchResults(term, page, scope, url, type, id, language, index, source) {
     console.log(SEARCH.appendedParams);
     const { data } = await axios.get('/SearchAPI?method=searchLite' + SEARCH.appendedParams, {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(false),
          auth: createAuthTokens(),
          params: {
               library: scope ?? null,
               lookfor: term ?? null,
               pageSize: 25,
               page: page ?? 1,
               type: type ?? 'catalog',
               id: id,
               language: language,
               includeSortList: true,
               source: source,
               searchIndex: index,
          },
     });

     let morePages = true;
     if (data.result?.page_current === data.result?.page_total) {
          morePages = false;
     } else if (data.result?.page_total === 1) {
          morePages = false;
     }

     SEARCH.id = data?.result?.id ?? null;
     SEARCH.sortMethod = data?.result?.sort ?? '';
     SEARCH.term = data?.result?.lookfor ?? '';
     SEARCH.availableFacets = data?.result?.options ?? [];

     await getSortList(url, language);
     await getAvailableFacetsKeys(url, language);
     await getAppliedFilters(url, language);

     setDefaultFacets(data?.result?.options ?? []);

     return {
          results: data.result?.items ?? [],
          totalResults: data.result?.totalResults ?? 0,
          curPage: data.result?.page_current ?? 0,
          totalPages: data.result?.page_total ?? 0,
          hasMore: morePages,
          source: data?.result?.searchSource ?? 'local',
          index: data?.result?.searchIndex ?? 'Keyword',
          term: term,
          message: data.data?.message ?? null,
          error: data.data?.error?.message ?? false,
     };
}

function getSortLabel(payload = '') {
     let label = payload;
     if (payload) {
          if (payload === 'year desc,title asc') {
               label = 'Publication Year Desc';
          } else if (payload === 'relevance') {
               label = 'Best Match';
          } else if (payload === 'author asc,title asc') {
               label = 'Author';
          } else if (payload === 'title') {
               label = 'Title';
          } else if (payload === 'days_since_added asc') {
               label = 'Date Purchased Desc';
          } else if (payload === 'sort_callnumber') {
               label = 'Call Number';
          } else if (payload === 'sort_popularity') {
               label = 'Total Checkouts';
          } else if (payload === 'sort_rating') {
               label = 'User Rating';
          } else if (payload === 'total_holds desc') {
               label = 'Number of Holds';
          }
     }
     return label;
}