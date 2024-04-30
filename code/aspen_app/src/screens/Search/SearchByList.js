import { MaterialIcons } from '@expo/vector-icons';

import { useNavigation, useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import { Badge, Box, Button, FlatList, HStack, Icon, Pressable, Stack, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { loadError } from '../../components/loadError';

// custom components and helper files
import { LoadingSpinner, loadingSpinner } from '../../components/loadingSpinner';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getCleanTitle } from '../../helpers/item';
import { navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { removeTitlesFromList } from '../../util/api/list';
import { createAuthTokens, getHeaders } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion } from '../../util/loadLibrary';
import AddToList from './AddToList';
import { DisplayResult } from './DisplayResult';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

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
                    LoadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <Box flex={1}>
                         <FlatList data={data.items} ListEmptyComponent={NoResults} renderItem={({ item }) => <DisplayResult data={item} />} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               )}
          </SafeAreaView>
     );
};

async function fetchSearchResults(id, page, url, language) {
     let listId = id;
     console.log(listId);
     if (_.isString(listId)) {
          if (listId.includes('system_user_list')) {
               const myArray = id.split('_');
               listId = myArray[myArray.length - 1];
          }
     }

     const { data } = await axios.get('/SearchAPI?method=getListResults', {
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

     return {
          id: data.result?.id ?? listId,
          items: Object.values(data.result?.items),
     };
}