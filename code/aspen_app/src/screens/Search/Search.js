import { useNavigation } from '@react-navigation/native';
import _ from 'lodash';
import { Box, Button, Center, FlatList, FormControl, Input, Text } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { LanguageContext, LibrarySystemContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';

import { formatDiscoveryVersion } from '../../util/loadLibrary';
import { getDefaultFacets } from '../../util/search';

export const SearchHome = () => {
     const navigation = useNavigation();
     const [searchTerm, setSearchTerm] = React.useState('');
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const discoveryVersion = formatDiscoveryVersion(library.discoveryVersion) ?? '22.10.00';
     const quickSearchNum = _.size(library.quickSearches);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     React.useEffect(() => {
          async function preloadDefaultFacets() {
               if (discoveryVersion >= '22.11.00') {
                    await getDefaultFacets(library.baseUrl, 5, language);
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