import { Badge, Box, FlatList, HStack, Image, Pressable, Stack, Text, VStack, ChevronLeftIcon, Icon, Button } from 'native-base';
import React from 'react';
import axios from 'axios';
import { SafeAreaView } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';

import { useRoute, useNavigation } from '@react-navigation/native';
import CachedImage from 'expo-cached-image';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import AddToList from './AddToList';
import _ from 'lodash';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import { getCleanTitle } from '../../helpers/item';
import { formatDiscoveryVersion } from '../../util/loadLibrary';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { GLOBALS } from '../../util/globals';
import { createAuthTokens, getHeaders, postData } from '../../util/apiAuth';
import { loadError } from '../../components/loadError';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { SEARCH } from '../../util/search';
import { removeTitlesFromList } from '../../util/api/list';
import { MaterialIcons } from '@expo/vector-icons';

export const SearchResultsForList = () => {
     const id = useRoute().params?.id;

     const navigation = useNavigation();
     const prevRoute = useRoute().params?.prevRoute ?? 'HomeScreen';
     const screenTitle = useRoute().params?.title ?? '';
     //console.log(useRoute().params);
     const [page, setPage] = React.useState(1);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const url = library.baseUrl;

     let isUserList = false;
     if (screenTitle.includes('Your List')) {
          isUserList = true;
     }

     const { status, data, error, isFetching } = useQuery(['searchResultsForList', url, page, id, language], () => fetchSearchResults(id, page, url, language));

     const NoResults = () => {
          return null;
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <Box flex={1}>
                         <FlatList data={data.items} ListEmptyComponent={NoResults} renderItem={({ item }) => <DisplayResult listId={id} data={item} isUserList={isUserList} />} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               )}
          </SafeAreaView>
     );
};

const DisplayResult = (data) => {
     const item = data.data;
     const isUserList = data.isUserList;
     const listId = data.listId;
     const { user } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const queryClient = useQueryClient();

     let recordType = 'grouped_work';
     if (item.recordtype) {
          recordType = item.recordtype;
     }
     const imageUrl = library.baseUrl + '/bookcover.php?id=' + item.id + '&size=medium&type=' + recordType;

     const handlePressItem = () => {
          if (item) {
               if (recordType === 'list') {
                    navigateStack('BrowseTab', 'ListResults', {
                         id: item.id,
                         title: item.title_display,
                         url: library.baseUrl,
                         prevRoute: 'SearchByList',
                    });
               } else {
                    if (version >= '23.01.00') {
                         navigateStack('BrowseTab', 'ListResultItem', {
                              id: item.id,
                              title: getCleanTitle(item.title_display),
                              url: library.baseUrl,
                              libraryContext: library,
                              prevRoute: 'SearchByList',
                         });
                    } else {
                         navigateStack('BrowseTab', 'ResultItem221200', {
                              id: item.id,
                              title: getCleanTitle(item.title_display),
                              url: library.baseUrl,
                              userContext: user,
                              libraryContext: library,
                              prevRoute: 'SearchByList',
                         });
                    }
               }
          }
     };

     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={handlePressItem}>
               <HStack space={3}>
                    <VStack maxW="30%">
                         <CachedImage
                              cacheKey={item.id}
                              alt={item.title_display}
                              source={{
                                   uri: `${imageUrl}`,
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
                         {isUserList ? (
                              <Button
                                   onPress={() => {
                                        removeTitlesFromList(listId, item.id, library.baseUrl).then(async () => {
                                             queryClient.invalidateQueries({ queryKey: ['list', listId] });
                                             queryClient.invalidateQueries({ queryKey: ['searchResultsForList', library.baseUrl, 1, listId, language] });
                                        });
                                   }}
                                   colorScheme="danger"
                                   leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" />}
                                   size="sm"
                                   variant="ghost">
                                   {getTermFromDictionary(language, 'delete')}
                              </Button>
                         ) : (
                              <AddToList itemId={item.id} btnStyle="sm" />
                         )}
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
                              {item.title_display}
                         </Text>
                         {item.author_display ? (
                              <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                   {getTermFromDictionary(language, 'by')} {item.author_display}
                              </Text>
                         ) : null}
                         {item.format ? (
                              <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                                   {item.format.map((format, i) => {
                                        return (
                                             <Badge key={i} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
                                                  {format}
                                             </Badge>
                                        );
                                   })}
                              </Stack>
                         ) : null}
                    </VStack>
               </HStack>
          </Pressable>
     );
};

async function fetchSearchResults(id, page, url, language) {
     let listId = id;
     if (listId.includes('system_user_list')) {
          const myArray = id.split('_');
          listId = myArray[myArray.length - 1];
     }

     const postBody = await postData();
     const instance = axios.create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id: listId,
               limit: 25,
               page: page,
               language,
          },
     });

     const { data } = await instance.post('/SearchAPI?method=getListResults', postBody);
     console.log(data);

     return {
          id: data.result?.id ?? listId,
          items: Object.values(data.result?.items),
     };
}