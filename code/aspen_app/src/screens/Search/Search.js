import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';
import { Box, Button, Center, FlatList, FormControl, HStack, Icon, Input, Text } from 'native-base';
import React, { Component } from 'react';
import { SafeAreaView } from 'react-native';

import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { translate } from '../../translations/translations';
import { formatDiscoveryVersion, LIBRARY } from '../../util/loadLibrary';
import { getDefaultFacets } from '../../util/search';

export default class Search extends Component {
     static contextType = userContext;

     constructor() {
          super();
          this.state = {
               isLoading: true,
               searchTerm: '',
               showRecentSearches: false,
               recentSearches: {},
               user: [],
               library: [],
               location: [],
          };
          this._isMounted = false;
          this._getRecentSearches = this._getRecentSearches.bind(this);
     }

     componentDidMount = async () => {
          this._isMounted = true;

          const userContext = JSON.parse(this.props.route.params.userContext);
          const libraryContext = JSON.parse(this.props.route.params.libraryContext);
          const locationContext = JSON.parse(this.props.route.params.locationContext);

          this.setState({
               isLoading: false,
               user: userContext.user,
               library: libraryContext.library,
               location: locationContext.location,
          });

          let discoveryVersion = '22.10.00';
          if (typeof this.state.library !== 'undefined') {
               if (this.state.library.discoveryVersion) {
                    discoveryVersion = formatDiscoveryVersion(this.state.library.discoveryVersion);
               }
          }

          if (LIBRARY.version >= '22.11.00') {
               this._isMounted && (await getDefaultFacets());
          }

          this.setState({
               isLoading: false,
               discoveryVersion,
          });

          this._isMounted && (await this._getRecentSearches());
     };

     componentWillUnmount() {
          this._isMounted = false;
     }

     initiateSearch = async () => {
          const { searchTerm } = this.state;
          const { navigation } = this.props;
          navigation.navigate('SearchResults', {
               term: searchTerm,
               userContext: this.state.user,
               libraryContext: this.state.library,
          });
          await this._addRecentSearch(searchTerm).then((res) => {
               this.clearText();
          });
     };

     renderItem = (item, libraryUrl) => {
          const { navigation } = this.props;
          return (
               <Button
                    mb={3}
                    onPress={() =>
                         navigation.navigate('SearchResults', {
                              term: item.searchTerm,
                              userContext: this.state.user,
                              libraryContext: this.state.library,
                         })
                    }>
                    {item.label}
               </Button>
          );
     };

     _getCurrentParams = async () => {
          try {
               const searchParams = await AsyncStorage.getItem('@searchParams');
               return searchParams != null ? JSON.parse(searchParams) : null;
          } catch (e) {
               console.log(e);
          }
     };

     _recentSearchItem = (search) => {
          const { navigation } = this.props;
          return (
               <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
                    <Button
                         size="sm"
                         onPress={() =>
                              navigation.navigate('SearchResults', {
                                   term: search,
                                   userContext: this.state.user,
                                   libraryContext: this.state.library,
                              })
                         }>
                         {search}
                    </Button>
                    <Button variant="ghost" onPress={() => this._removeRecentSearch(search)} startIcon={<Icon as={MaterialIcons} name="close" size="sm" mr={-1} mt={0.5} />}>
                         Remove
                    </Button>
               </HStack>
          );
     };

     _recentSearchFooter = () => {
          return <Button onPress={() => this._clearRecentSearches()}>Remove all</Button>;
     };

     clearText = () => {
          this.setState({ searchTerm: '' });
     };

     _getRecentSearches = async () => {
          try {
               const recentSearches = await AsyncStorage.getItem('@recentSearches');
               this.setState({
                    recentSearches: JSON.parse(recentSearches),
               });
               return recentSearches != null ? JSON.parse(recentSearches) : null;
          } catch (e) {
               console.log(e);
          }
     };

     _createRecentSearches = async (searchTerm) => {
          try {
               const searches = [];
               const search = {
                    [searchTerm]: searchTerm,
               };
               searches.push(searchTerm);
               await AsyncStorage.setItem('@recentSearches', JSON.stringify(searches));
          } catch (e) {
               console.log(e);
          }
     };

     _addRecentSearch = async (searchTerm) => {
          const storage = await this._getRecentSearches().then(async (response) => {
               if (response) {
                    const search = {
                         [searchTerm]: searchTerm,
                    };
                    response.push(searchTerm);
                    try {
                         await AsyncStorage.setItem('@recentSearches', JSON.stringify(response));
                    } catch (e) {
                         console.log(e);
                    }
               } else {
                    await this._createRecentSearches(searchTerm);
               }
          });
     };

     _removeRecentSearch = async (needle) => {
          const storage = await this._getRecentSearches().then(async (response) => {
               if (response) {
                    const haystack = response;
                    if (haystack.includes(needle)) {
                         _.pull(haystack, needle);
                         try {
                              await AsyncStorage.setItem('@recentSearches', JSON.stringify(haystack));
                         } catch (e) {
                              console.log(e);
                         }
                    }
               }
          });

          await this._getRecentSearches();
     };

     _clearRecentSearches = async () => {
          await AsyncStorage.removeItem('@recentSearches');
          await this._getRecentSearches();
     };

     render() {
          const library = this.state.library;

          const quickSearchNum = _.size(library.quickSearches);
          const recentSearchNum = _.size(this.state.recentSearches);

          if (this.state.isLoading) {
               return loadingSpinner();
          }

          return (
               <SafeAreaView>
                    <Box safeArea={5}>
                         <FormControl>
                              <Input variant="filled" autoCapitalize="none" onChangeText={(searchTerm) => this.setState({ searchTerm, libraryUrl: library.baseUrl })} status="info" placeholder={translate('search.title')} clearButtonMode="always" onSubmitEditing={this.initiateSearch} value={this.state.searchTerm} size="xl" />
                         </FormControl>

                         {quickSearchNum > 0 ? (
                              <Box>
                                   <Center>
                                        <Text mt={8} mb={2} fontSize="xl" bold>
                                             {translate('search.quick_search_title')}
                                        </Text>
                                   </Center>
                                   <FlatList data={_.sortBy(library.quickSearches, ['weight', 'label'])} keyExtractor={(item, index) => index.toString()} renderItem={({ item }) => this.renderItem(item, library.baseUrl)} />
                              </Box>
                         ) : null}

                         {this.state.showRecentSearches && recentSearchNum > 0 ? (
                              <Box>
                                   <Center>
                                        <Text mt={8} mb={2} fontSize="xl" bold>
                                             Recent Searches
                                        </Text>
                                   </Center>
                                   <FlatList data={this.state.recentSearches} renderItem={({ item }) => this._recentSearchItem(item)} keyExtractor={(item, index) => index.toString()} ListFooterComponent={this._recentSearchFooter} />
                              </Box>
                         ) : null}
                    </Box>
               </SafeAreaView>
          );
     }
}