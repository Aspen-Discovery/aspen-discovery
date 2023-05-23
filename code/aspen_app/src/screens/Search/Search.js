import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';
import { Box, Button, Center, FlatList, FormControl, HStack, Icon, Input, Text } from 'native-base';
import React, { Component } from 'react';
import { SafeAreaView } from 'react-native';

import { formatDiscoveryVersion } from '../../util/loadLibrary';
import { getDefaultFacets } from '../../util/search';
import { LanguageContext, LibrarySystemContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';

export const SearchHome = () => {
     const [searchTerm, setSearchTerm] = React.useState('');
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const discoveryVersion = formatDiscoveryVersion(library.discoveryVersion) ?? '22.10.00';
     const quickSearchNum = _.size(library.quickSearches);

     React.useEffect(() => {
          async function preloadDefaultFacets() {
               if (discoveryVersion >= '22.11.00') {
                    await getDefaultFacets(language);
               }
          }
          preloadDefaultFacets();
     }, []);

     const clearText = () => {
          setSearchTerm('');
     };

     const search = async () => {
          navigate('SearchResults', { term: searchTerm, type: 'catalog', prevRoute: 'SearchHome' });
          clearText();
     };

     return (
          <SafeAreaView>
               <Box safeArea={5}>
                    <FormControl>
                         <Input variant="filled" autoCapitalize="none" onChangeText={(term) => setSearchTerm(term)} status="info" placeholder={getTermFromDictionary(language, 'search')} clearButtonMode="always" onSubmitEditing={search} value={searchTerm} size="xl" />
                    </FormControl>
                    {quickSearchNum > 0 ? (
                         <Box>
                              <Center>
                                   <Text mt={8} mb={2} fontSize="xl" bold>
                                        {getTermFromDictionary(language, 'quick_searches')}
                                   </Text>
                              </Center>
                              <FlatList data={_.sortBy(library.quickSearches, ['weight', 'label'])} keyExtractor={(item, index) => index.toString()} renderItem={({ item }) => <QuickSearch data={item} />} />
                         </Box>
                    ) : null}
               </Box>
          </SafeAreaView>
     );
};

const QuickSearch = (data) => {
     const quickSearch = data.data;
     return (
          <Button
               mb={3}
               onPress={() =>
                    navigate('SearchResults', {
                         term: quickSearch.searchTerm,
                    })
               }>
               {quickSearch.label}
          </Button>
     );
};