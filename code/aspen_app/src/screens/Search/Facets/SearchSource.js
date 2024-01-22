import { MaterialIcons } from '@expo/vector-icons';
import _ from 'lodash';
import { Box, HStack, Icon, Pressable, ScrollView, Text, View } from 'native-base';
import React from 'react';

import { LanguageContext, LibrarySystemContext, SearchContext } from '../../../context/initialContext';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getSearchIndexes, SEARCH } from '../../../util/search';

// custom components and helper files

export const SearchSourceScreen = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { currentSource, sources, updateCurrentSource, updateIndexes, updateCurrentIndex } = React.useContext(SearchContext);

     console.log('currentSource: ' + currentSource);

     const search = async () => {
          navigateStack('BrowseTab', 'SearchResults', {
               term: SEARCH.term,
               type: 'catalog',
               prevRoute: 'DiscoveryScreen',
               scannerSearch: false,
          });
     };

     const updateSource = async (source) => {
          SEARCH.sortMethod = 'relevance';
          SEARCH.appliedFilters = [];
          SEARCH.sortList = [];
          SEARCH.availableFacets = [];
          SEARCH.defaultFacets = [];
          SEARCH.pendingFilters = [];
          SEARCH.appendedParams = '';
          updateCurrentSource(source);
          if (source === 'events') {
               updateCurrentIndex('EventsKeyword');
          } else {
               updateCurrentIndex('Keyword');
          }
          await search();
          await getSearchIndexes(library.baseUrl, language, source).then((indexes) => {
               updateIndexes(indexes);
          });
     };

     return (
          <View pt={5} style={{ flex: 1 }}>
               <ScrollView>
                    <Box safeAreaX={5}>
                         {_.map(sources, function (source, index, array) {
                              if (index === 'events' || index === 'local') {
                                   return (
                                        <Pressable p={0.5} py={2} onPress={() => updateSource(index)}>
                                             {currentSource === index ? (
                                                  <HStack space={3} justifyContent="flex-start" alignItems="center">
                                                       <Icon as={MaterialIcons} name="radio-button-checked" size="lg" color="primary.600" />
                                                       <Text _light={{ color: 'darkText' }} _dark={{ color: 'lightText' }} ml={2}>
                                                            {source.name}
                                                       </Text>
                                                  </HStack>
                                             ) : (
                                                  <HStack space={3} justifyContent="flex-start" alignItems="center">
                                                       <Icon as={MaterialIcons} name="radio-button-unchecked" size="lg" color="muted.400" />
                                                       <Text _light={{ color: 'darkText' }} _dark={{ color: 'lightText' }} ml={2}>
                                                            {source.name}
                                                       </Text>
                                                  </HStack>
                                             )}
                                        </Pressable>
                                   );
                              }
                         })}
                    </Box>
               </ScrollView>
          </View>
     );
};