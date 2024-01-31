import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import _ from 'lodash';
import { Box, HStack, Icon, Pressable, ScrollView, Text, View } from 'native-base';
import React from 'react';

import { LanguageContext, LibraryBranchContext, LibrarySystemContext, SearchContext, UserContext } from '../../../context/initialContext';
import { navigateStack } from '../../../helpers/RootNavigator';
import { SEARCH } from '../../../util/search';

// custom components and helper files

export const SearchIndexScreen = () => {
     const [isLoading, setIsLoading] = React.useState(false);
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(false);
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { currentIndex, indexes, updateCurrentSource, updateIndexes, updateCurrentIndex } = React.useContext(SearchContext);

     console.log('currentIndex: ' + currentIndex);

     const search = async () => {
          navigateStack('BrowseTab', 'SearchResults', {
               term: SEARCH.term,
               type: 'catalog',
               prevRoute: 'DiscoveryScreen',
               scannerSearch: false,
          });
     };

     const updateIndex = async (index) => {
          updateCurrentIndex(index);
          await search();
     };

     return (
          <View pt={5} style={{ flex: 1 }}>
               <ScrollView>
                    <Box safeAreaX={5}>
                         {_.map(indexes, function (obj, index, array) {
                              console.log(obj);
                              return (
                                   <Pressable p={0.5} py={2} onPress={() => updateIndex(index)}>
                                        {currentIndex === index ? (
                                             <HStack space={3} justifyContent="flex-start" alignItems="center">
                                                  <Icon as={MaterialIcons} name="radio-button-checked" size="lg" color="primary.600" />
                                                  <Text _light={{ color: 'darkText' }} _dark={{ color: 'lightText' }} ml={2}>
                                                       {obj}
                                                  </Text>
                                             </HStack>
                                        ) : (
                                             <HStack space={3} justifyContent="flex-start" alignItems="center">
                                                  <Icon as={MaterialIcons} name="radio-button-unchecked" size="lg" color="muted.400" />
                                                  <Text _light={{ color: 'darkText' }} _dark={{ color: 'lightText' }} ml={2}>
                                                       {obj}
                                                  </Text>
                                             </HStack>
                                        )}
                                   </Pressable>
                              );
                         })}
                    </Box>
               </ScrollView>
          </View>
     );
};