import { Badge, Box, FlatList, HStack, Image, Pressable, Stack, Text, VStack } from 'native-base';
import React from 'react';
import axios from 'axios';
import { SafeAreaView } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useRoute } from '@react-navigation/native';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { translate } from '../../translations/translations';
import AddToList from './AddToList';
import _ from 'lodash';
import {navigateStack} from '../../helpers/RootNavigator';
import { getCleanTitle } from '../../helpers/item';
import {formatDiscoveryVersion} from '../../util/loadLibrary';
import {LibrarySystemContext, UserContext} from '../../context/initialContext';
import {GLOBALS} from '../../util/globals';
import {createAuthTokens, getHeaders, postData} from '../../util/apiAuth';
import {loadError} from '../../components/loadError';

export const SearchResultsForList = () => {
     const id = useRoute().params.id;
     const [page, setPage] = React.useState(1);
     const { library } = React.useContext(LibrarySystemContext);
     const url = library.baseUrl;

     const { status, data, error, isFetching } = useQuery(['searchResultsForList', url, page, id], () => fetchSearchResults(id, page, url));

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
                       <FlatList data={data.items} ListEmptyComponent={NoResults} renderItem={({ item }) => <DisplayResult data={item} />} keyExtractor={(item, index) => index.toString()} />
                  </Box>
              )}
         </SafeAreaView>
     );
}

const DisplayResult = (data) => {
     const item = data.data;
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     let recordType = 'grouped_work';
     if(item.recordtype) {
          recordType = item.recordtype;
     }
     console.log(recordType);
     const imageUrl = library.baseUrl + '/bookcover.php?id=' + item.id + '&size=medium&type=' + recordType;

     const handlePressItem = () => {
          if(item) {
               if(recordType === 'list') {
                    navigateStack('SearchTab', 'ListResults', {
                         id: item.id,
                         title: item.title_display,
                         url: library.baseUrl,
                    })
               } else {
                    if(version >= '23.01.00') {
                         navigateStack('SearchTab','ListResultItem', {
                              id: item.id,
                              title: getCleanTitle(item.title_display),
                              url: library.baseUrl,
                              libraryContext: library,
                         });
                    } else {
                         navigateStack('SearchTab','ResultItem221200', {
                              id: item.id,
                              title: getCleanTitle(item.title_display),
                              url: library.baseUrl,
                              userContext: user,
                              libraryContext: library
                         });
                    }
               }
          }
     };

     return (
         <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={handlePressItem}>
              <HStack space={3}>
                   <VStack>
                        <Image
                            source={{ uri: imageUrl }}
                            fallbackSource={{
                                 bgColor: 'warmGray.50',
                            }}
                            alt={item.title_display}
                            bg="warmGray.50"
                            _dark={{
                                 bgColor: 'coolGray.800',
                            }}
                            borderRadius="md"
                            size={{
                                 base: '100px',
                                 lg: '120px',
                            }}
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
                        <AddToList itemId={item.id} btnStyle="sm" />
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
                                 {translate('grouped_work.by')} {item.author_display}
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
     )
}

async function fetchSearchResults(id, page, url) {
     const myArray = id.split('_');
     const listId = myArray[myArray.length - 1];

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
          },
     })

     const { data } = await instance.post('/SearchAPI?method=getListResults', postBody);

     return {
          id: data.result?.id ?? listId,
          items: Object.values(data.result?.items),
     };
}

/*
export class SearchByList extends Component {
     static contextType = userContext;

     constructor() {
          super();
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
               user: [],
               list: [],
               listDetails: [],
               id: null,
               lastListUsed: 0,
          };
          this.lastListUsed = 0;
          this.updateLastListUsed = this.updateLastListUsed.bind(this);
     }

     componentDidMount = async () => {
          const { route } = this.props;
          const givenList = route.params?.id ?? '';
          const libraryContext = route.params?.libraryContext ?? [];
          const libraryUrl = route.params?.url ?? libraryContext.baseUrl;

          this.setState({
               isLoading: false,
               listDetails: givenList,
               libraryUrl,
          });

          await this.loadList();
          this._getLastListUsed();
     };

     _getLastListUsed = () => {
          if (this.context.user) {
               const user = this.context.user;
               this.lastListUsed = user.lastListUsed;
          }
     };

     updateLastListUsed = (id) => {
          this.setState({
               isLoading: true,
          });

          this.lastListUsed = id;

          this.setState({
               isLoading: false,
          });
     };

     loadList = async () => {
          const { route } = this.props;
          const givenListId = route.params?.id ?? 0;
          const libraryContext = route.params?.libraryContext ?? [];
          const libraryUrl = libraryContext.baseUrl;

          await listofListSearchResults(givenListId, 25, 1, libraryUrl).then((response) => {
               if (_.isNull(route.params?.title)) {
                    this.props.navigation.setOptions({ title: translate('search.search_results_title') + response.title });
               }
               this.setState({
                    list: Object.values(response.items),
                    id: response.id,
               });
          });
     };

     renderItem = (item, library, user, lastListUsed) => {
          let recordType = 'grouped_work';
          if (item.recordtype) {
               recordType = item.recordtype;
          }
          const imageUrl = this.props.route.params.url + '/bookcover.php?id=' + item.id + '&size=large&type=' + recordType;
          //console.log(item);

          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, library, item.recordtype, item.title_display, item)}>
                    <HStack space={3} justifyContent="flex-start" alignItems="flex-start">
                         <VStack>
                              <Image
                                   source={{ uri: imageUrl }}
                                   alt={item.title_display}
                                   borderRadius="md"
                                   size={{
                                        base: '90px',
                                        lg: '120px',
                                   }}
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
                              <AddToList itemId={item.id} btnStyle="sm" />
                         </VStack>
                         <VStack w="65%">
                              <Text
                                   _dark={{ color: 'warmGray.50' }}
                                   color="coolGray.800"
                                   bold
                                   fontSize={{
                                        base: 'sm',
                                        lg: 'md',
                                   }}>
                                   {item.title_display}
                              </Text>
                              {item.author_display ? (
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" fontSize="xs">
                                        {translate('grouped_work.by')} {item.author_display}
                                   </Text>
                              ) : null}
                              {item.format ? (
                                   <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                                        {item.format.map((format, i) => {
                                             return (
                                                  <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
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

     // handles the on press action
     openItem = (item, library, recordtype, title, data) => {
          const { navigation, route } = this.props;
          const libraryContext = route.params?.libraryContext ?? [];
          const libraryUrl = libraryContext.baseUrl;
          const version = formatDiscoveryVersion(libraryContext.discoveryVersion);

          if (item) {
               if (recordtype === 'list') {
                    navigateStack('SearchTab', 'ListResults', {
                         id: item,
                         title: title,
                         libraryUrl,
                    });
               } else {
                    if(version >= '23.01.00') {
                         navigateStack('SearchTab', 'ListResultItem', {
                              id: item,
                              title: getCleanTitle(title),
                              url: libraryUrl,
                              libraryContext: library,
                         });
                    } else {
                         navigateStack('SearchTab', 'ListResultItem221200', {
                              id: item,
                              title: getCleanTitle(title),
                              url: libraryUrl,
                              libraryContext: library,
                         });
                    }
               }
          } else {
               console.log('no list id found');
               console.log(data);
          }
     };

     render() {
          const { list } = this.state;
          const user = this.context.user;
          const location = this.context.location;
          const library = this.context.library;
          const { route } = this.props;
          const givenListId = route.params?.id ?? 0;
          const libraryUrl = this.context.library.baseUrl;

          if (this.state.isLoading) {
               return loadingSpinner();
          }

          return (
               <SafeAreaView>
                    <Box safeArea={2}>
                         <FlatList data={list} renderItem={({ item }) => this.renderItem(item, library, user, this.lastListUsed)} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               </SafeAreaView>
          );
     }
}*/