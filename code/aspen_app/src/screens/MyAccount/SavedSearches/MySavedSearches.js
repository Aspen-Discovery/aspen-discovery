import { Badge, Box, Center, FlatList, Pressable, Text, HStack, VStack } from 'native-base';
import React from 'react';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native';
import { useQuery, useQueries, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { fetchSavedSearches, getSavedSearch } from '../../../util/api/user';
import { loadError } from '../../../components/loadError';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getListTitles } from '../../../util/api/list';
import { DisplaySystemMessage } from '../../../components/Notifications';

export const MySavedSearches = () => {
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['saved_searches', user.id, library.baseUrl, language], () => fetchSavedSearches(library.baseUrl), {
          placeholderData: [],
     });

     useQueries({
          queries: data.map((savedSearch) => {
               return {
                    queryKey: ['saved_search', savedSearch.id, user.id],
                    queryFn: () => getSavedSearch(savedSearch.id, language, library.baseUrl),
               };
          }),
     });

     const Empty = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'saved_searches_empty')}
                    </Text>
               </Center>
          );
     };

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} h="100%">
                    {showSystemMessage()}
                    <FlatList data={data} ListEmptyComponent={Empty} renderItem={({ item }) => <Item data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
               </Box>
          </SafeAreaView>
     );
};

const Item = (data) => {
     const { language } = React.useContext(LanguageContext);
     const item = data.data;

     let hasNewResults = 0;
     if (!_.isUndefined(item.hasNewResults)) {
          hasNewResults = item.hasNewResults;
     }

     const openSavedSearch = () => {
          navigateStack('AccountScreenTab', 'MySavedSearch', {
               id: item.id,
               details: item,
               title: item.title,
          });
     };

     return (
          <Pressable
               onPress={() => {
                    openSavedSearch();
               }}
               borderBottomWidth="1"
               _dark={{ borderColor: 'gray.600' }}
               borderColor="coolGray.200"
               pl="1"
               pr="1"
               py="2">
               <HStack space={3} justifyContent="flex-start">
                    <VStack space={1}>{/*<Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain" />*/}</VStack>
                    <VStack space={1} justifyContent="space-between" maxW="80%">
                         <Box>
                              <Text bold fontSize="md">
                                   {item.title}{' '}
                                   {hasNewResults === 1 ? (
                                        <Badge mb="-0.5" colorScheme="warning">
                                             {getTermFromDictionary(language, 'flag_updated')}
                                        </Badge>
                                   ) : null}
                              </Text>
                              <Text fontSize="9px" italic>
                                   Created on {item.created}
                              </Text>
                         </Box>
                    </VStack>
               </HStack>
          </Pressable>
     );
};

/*
export default class MySavedSearches_Old extends Component {
     constructor() {
          super();
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
               libraryUrl: '',
               searches: [],
          };
          this._isMounted = false;
     }

     _fetchSearches = async () => {
          const { route } = this.props;
          const libraryUrl = this.context.library.baseUrl;

          await getSavedSearches(libraryUrl).then((response) =>
               this.setState({
                    searches: response,
               })
          );
     };

     componentDidMount = async () => {
          this._isMounted = true;

          this._isMounted &&
               (await this._fetchSearches().then((r) => {
                    this.setState({
                         isLoading: false,
                    });
               }));
     };

     componentWillUnmount() {
          this._isMounted = false;
     }

     // renders the items on the screen
     renderSearch = (item, libraryUrl) => {
          let hasNewResults = 0;
          if (typeof item.hasNewResults !== 'undefined') {
               hasNewResults = item.hasNewResults;
          }

          return (
               <Pressable
                    onPress={() => {
                         this.openList(item.id, item, libraryUrl);
                    }}
                    borderBottomWidth="1"
                    _dark={{ borderColor: 'gray.600' }}
                    borderColor="coolGray.200"
                    pl="1"
                    pr="1"
                    py="2">
                    <HStack space={3} justifyContent="flex-start">
                         <VStack space={1}>{/!*<Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain" />*!/}</VStack>
                         <VStack space={1} justifyContent="space-between" maxW="80%">
                              <Box>
                                   <Text bold fontSize="md">
                                        {item.title}{' '}
                                        {hasNewResults === 1 ? (
                                             <Badge mb="-0.5" colorScheme="warning">
                                                  Updated
                                             </Badge>
                                        ) : null}
                                   </Text>
                                   <Text fontSize="9px" italic>
                                        Created on {item.created}
                                   </Text>
                              </Box>
                         </VStack>
                    </HStack>
               </Pressable>
          );
     };

     openList = (id, item, libraryUrl) => {
          this.props.navigation.navigate('AccountScreenTab', {
               screen: 'MySavedSearch',
               params: {
                    id,
                    details: item,
                    title: item.title,
                    libraryUrl,
               },
          });
     };

     _listEmptyComponent = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         You have no saved searches.
                    </Text>
               </Center>
          );
     };

     static contextType = userContext;

     render() {
          const { searches } = this.state;
          const user = this.context.user;
          const location = this.context.location;
          const library = this.context.library;

          if (this.state.isLoading) {
               return loadingSpinner();
          }

          return (
               <SafeAreaView style={{ flex: 1 }}>
                    <Box safeArea={2} h="100%">
                         <FlatList data={searches} ListEmptyComponent={this._listEmptyComponent()} renderItem={({ item }) => this.renderSearch(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               </SafeAreaView>
          );
     }
}*/