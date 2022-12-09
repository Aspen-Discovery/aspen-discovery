import { CommonActions } from '@react-navigation/native';
import { Badge, Box, FlatList, HStack, Image, Pressable, Stack, Text, VStack } from 'native-base';
import React, { Component } from 'react';
import { SafeAreaView } from 'react-native';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { translate } from '../../translations/translations';
import { listofListSearchResults } from '../../util/search';
import AddToList from './AddToList';
import _ from 'lodash';

export default class SearchByList extends Component {
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
          const libraryUrl = libraryContext.baseUrl;

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

          if (item) {
               if (recordtype === 'list') {
                    navigation.dispatch(
                         CommonActions.navigate({
                              name: 'ListResults',
                              params: {
                                   id: item,
                                   title: title,
                                   libraryUrl,
                              },
                         })
                    );
               } else {
                    navigation.dispatch(
                         CommonActions.navigate({
                              name: 'GroupedWorkScreen',
                              params: {
                                   id: item,
                                   title: title,
                                   url: libraryUrl,
                                   libraryContext: library,
                              },
                         })
                    );
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
}