import React from 'react';
import { MaterialIcons } from '@expo/vector-icons';
import { useQuery } from '@tanstack/react-query';
import _ from 'lodash';
import { useNavigation, useRoute } from '@react-navigation/native';

import { Badge, Box, Button, HStack, Icon, Image, Pressable, Stack, Text, VStack, FlatList } from 'native-base';

import { LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';
import { getAppliedFilters, getAvailableFacets, getSortList, SEARCH } from '../../util/search';
import { translate } from '../../translations/translations';
import AddToList from './AddToList';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import { SafeAreaView, ScrollView } from 'react-native';
import { createAuthTokens, getHeaders } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import axios from 'axios';
import { formatDiscoveryVersion } from '../../util/loadLibrary';

export const SearchResults = () => {
     const [page, setPage] = React.useState(1);
     const {library} = React.useContext(LibrarySystemContext);
     const {scope} = React.useContext(LibraryBranchContext);
     const url = library.baseUrl;

     let term = useRoute().params.term ?? 'birds';
     term = term.replace(/" "/g, '%20');

     const params = useRoute().params.pendingParams ?? [];

     const {status, data, error, isFetching, isPreviousData} = useQuery(['searchResults', url, page, term, scope, params], () => fetchSearchResults(term, page, scope, url), {keepPreviousData: true, staleTime: 1000});

     const Header = () => {
          const num = _.toInteger(data?.totalResults);
          if (num > 0) {
               let label = translate('filters.results', {num: num});
               if (num === 1) {
                    label = translate('filters.result', {num: num});
               }
               return (
                   <Box bgColor="coolGray.100" borderBottomWidth="1" _dark={{borderColor: 'gray.600', bg: 'coolGray.700'}} borderColor="coolGray.200">
                        <HStack justifyContent="space-between" m={2}>
                             <Text>{label}</Text>
                             <Text>
                                  Page {page} of {data?.totalPages}
                             </Text>
                        </HStack>
                   </Box>
               );
          }

          return null;
     };

     const Paging = () => {
          return (
              <Box safeArea={2} bgColor="coolGray.100" borderTopWidth="1" _dark={{borderColor: 'gray.600', bg: 'coolGray.700'}} borderColor="coolGray.200" flexWrap="nowrap" alignItems="center">
                   <ScrollView horizontal>
                        <Button.Group size="sm">
                             <Button onPress={() => setPage(page - 1)} isDisabled={page === 1}>
                                  {translate('general.previous')}
                             </Button>
                             <Button
                                 onPress={() => {
                                      if (!isPreviousData && data?.hasMore) {
                                           console.log('Adding to page');
                                           setPage(page + 1);
                                      }
                                 }}
                                 isDisabled={isPreviousData || !data?.hasMore}>
                                  {translate('general.next')}
                             </Button>
                        </Button.Group>
                   </ScrollView>
                   <Text mt={2} fontSize="sm">
                        Page {page} of {data?.totalPages}
                   </Text>
              </Box>
          );
     };

     const NoResults = () => {
          return null;
     };

     return (
         <SafeAreaView style={{flex: 1}}>
              {status === 'loading' || isFetching ? (
                  loadingSpinner()
              ) : status === 'error' ? (
                  loadError('Error', '')
              ) : (
                  <Box flex={1}>
                       {data.totalResults > 0 ? <FilterBar/> : null}
                       <FlatList data={data.results} ListHeaderComponent={Header} ListFooterComponent={Paging} ListEmptyComponent={NoResults} renderItem={({item}) => <DisplayResult data={item}/>} keyExtractor={(item, index) => index.toString()}/>
                  </Box>
              )}
         </SafeAreaView>
     );
};

const DisplayResult = (data) => {
     const item = data.data;
     const navigation = useNavigation();
     const {library} = React.useContext(LibrarySystemContext);

     const handlePressItem = () => {
          navigation.navigate('SearchTab', {
               screen: 'GroupedWork',
               params: {
                    id: item.key,
                    title: item.title,
                    url: library.baseUrl,
                    libraryContext: library,
               }
          })
     }

     return (
         <Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={handlePressItem}>
              <HStack space={3}>
                   <VStack>
                        <Image
                            source={{uri: item.image}}
                            alt={item.title}
                            borderRadius="md"
                            size={{
                                 base: '90px',
                                 lg: '120px',
                            }}
                        />
                        <Badge
                            mt={1}
                            _text={{
                                 fontSize: 10,
                                 color: 'coolGray.600',
                            }}
                            bgColor="warmGray.200"
                            _dark={{
                                 bgColor: 'coolGray.900',
                                 _text: {color: 'warmGray.400'},
                            }}>
                             {item.language}
                        </Badge>
                        <AddToList itemId={item.key} btnStyle="sm"/>
                   </VStack>
                   <VStack w="65%">
                        <Text
                            _dark={{color: 'warmGray.50'}}
                            color="coolGray.800"
                            bold
                            fontSize={{
                                 base: 'md',
                                 lg: 'lg',
                            }}>
                             {item.title}
                        </Text>
                        {item.author ? (
                            <Text _dark={{color: 'warmGray.50'}} color="coolGray.800">
                                 {translate('grouped_work.by')} {item.author}
                            </Text>
                        ) : null}
                        <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                             {item.itemList.map((item, i) => {
                                  return (
                                      <Badge key={i} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{fontSize: 12}}>
                                           {item.name}
                                      </Badge>
                                  );
                             })}
                        </Stack>
                   </VStack>
              </HStack>
         </Pressable>
     );
};

const FilterBar = () => {
     const navigation = useNavigation();
     const {library} = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     if (version >= '22.11.00') {
          return (
              <Box safeArea={2} bgColor="coolGray.100" borderBottomWidth="1" _dark={{borderColor: 'gray.600', bg: 'coolGray.700'}} borderColor="coolGray.200" flexWrap="nowrap">
                   <ScrollView horizontal>
                        <Button
                            size="sm"
                            leftIcon={<Icon as={MaterialIcons} name="tune" size="sm"/>}
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
                             {translate('filters.title')}
                        </Button>
                        <CreateFilterButton/>
                   </ScrollView>
              </Box>
          );
     }
};

const CreateFilterButtonDefaults = () => {
     const navigation = useNavigation();
     const defaults = SEARCH.defaultFacets;
     return (
         <Button.Group size="sm" space={1} vertical variant="outline">
              {defaults.map((obj, index) => {
                   return (
                       <Button
                           key={index}
                           variant="outline"
                           onPress={() => {
                                navigation.push('modal', {
                                     screen: 'Facet',
                                     params: {
                                          navigation: navigation,
                                          key: obj['field'],
                                          title: obj['label'],
                                          facets: SEARCH.availableFacets.data[obj['label']].facets,
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
     const navigation = useNavigation();
     const appliedFacets = SEARCH.appliedFilters;
     const sort = _.find(appliedFacets['Sort By'], {
          field: 'sort_by',
          value: 'relevance',
     });

     if ((_.size(appliedFacets) > 0 && _.size(sort) === 0) || (_.size(appliedFacets) >= 2 && _.size(sort) > 1)) {
          return (
              <Button.Group size="sm" space={1} vertical variant="outline">
                   {_.map(appliedFacets, function (item, index, collection) {
                        const cluster = _.filter(SEARCH.availableFacets.data, ['field', item[0]['field']]);
                        let labels = '';
                        _.forEach(item, function (value, key) {
                             let label = value['display'];
                             if (value['display'] === 'year desc,title asc') {
                                  label = 'Publication Year Desc';
                             } else if (value['display'] === 'relevance') {
                                  label = 'Best Match';
                             } else if (value['display'] === 'author asc,title asc') {
                                  label = 'Author';
                             } else if (value['display'] === 'title') {
                                  label = 'Title';
                             } else if (value['display'] === 'days_since_added asc') {
                                  label = 'Date Purchased Desc';
                             } else if (value['display'] === 'sort_callnumber') {
                                  label = 'Call Number';
                             } else if (value['display'] === 'sort_popularity') {
                                  label = 'Total Checkouts';
                             } else if (value['display'] === 'sort_rating') {
                                  label = 'User Rating';
                             } else if (value['display'] === 'total_holds desc') {
                                  label = 'Number of Holds';
                             } else {
                                  // do nothing
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

     return <CreateFilterButtonDefaults/>;
};

async function fetchSearchResults(term, page, scope, url) {
     const {data} = await axios.get('/SearchAPI?method=searchLite' + SEARCH.appendedParams, {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(false),
          auth: createAuthTokens(),
          params: {
               library: scope,
               lookfor: term,
               pageSize: 25,
               page: page,
          },
     });

     let morePages = true;
     if (data.result?.page_current === data.result?.page_total) {
          morePages = false;
     }

     SEARCH.id = data.result.id;
     SEARCH.sortMethod = data.result.sort;
     SEARCH.term = data.result.lookfor;

     await getSortList();
     await getAvailableFacets();
     await getAppliedFilters();

     return {
          results: data.result?.items,
          totalResults: data.result?.totalResults ?? 0,
          curPage: data.result?.page_current ?? 0,
          totalPages: data.result?.page_total ?? 0,
          hasMore: morePages,
          term: term,
          message: data.data?.message ?? null,
          error: data.data?.error?.message ?? false,
     };
}