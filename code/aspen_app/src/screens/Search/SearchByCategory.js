import { Box, Button, ButtonGroup, ButtonText, Center, FlatList, Heading, SafeAreaView, ScrollView, Text } from '@gluestack-ui/themed';
import { useRoute } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import _ from 'lodash';
import React from 'react';
import { Image } from 'expo-image';

// custom components and helper files
import { loadError } from '../../components/loadError';
import { LoadingSpinner, loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext } from '../../context/initialContext';
import { getTermFromDictionary, getTranslationsWithValues } from '../../translations/TranslationService';
import { fetchSearchResultsForBrowseCategory } from '../../util/api/search';
import { DisplayResult } from './DisplayResult';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const SearchResultsForBrowseCategory = () => {
     const [page, setPage] = React.useState(1);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const category = useRoute().params.id ?? '';
     const [paginationLabel, setPaginationLabel] = React.useState('Page 1 of 1');

     const { status, data, error, isFetching, isPreviousData } = useQuery({
          queryKey: ['searchResultsForBrowseCategory', category, page, 25, library.baseUrl, language],
          queryFn: () => fetchSearchResultsForBrowseCategory(category, page, 25, library.baseUrl, language),
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               if (data.totalPages) {
                    let tmp = getTermFromDictionary(language, 'page_of_page');
                    tmp = tmp.replace('%1%', page);
                    tmp = tmp.replace('%2%', data.totalPages);
                    console.log(tmp);
                    setPaginationLabel(tmp);
               }
          },
     });

     const systemMessagesForScreen = [];

     React.useEffect(() => {
          if (_.isArray(systemMessages)) {
               systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         systemMessagesForScreen.push(obj);
                    }
               });
          }
     }, [systemMessages]);

     const Paging = () => {
          if (data.totalPages > 1) {
               return (
                    <Box p="$2" bgColor={colorMode === 'light' ? theme['colors']['coolGray']['100'] : theme['colors']['coolGray']['700']} borderTopWidth={1} borderColor={colorMode === 'light' ? theme['colors']['coolGray']['200'] : theme['colors']['gray']['600']} flexWrap="nowrap" alignItems="center">
                         <ScrollView horizontal>
                              <ButtonGroup>
                                   <Button onPress={() => setPage(page - 1)} isDisabled={page === 1} size="sm" bgColor={theme['colors']['primary']['500']}>
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'previous')}</ButtonText>
                                   </Button>
                                   <Button
                                        bgColor={theme['colors']['primary']['500']}
                                        onPress={() => {
                                             if (!isPreviousData && data.hasMore) {
                                                  console.log('Adding to page');
                                                  setPage(page + 1);
                                             }
                                        }}
                                        isDisabled={isPreviousData || !data.hasMore}
                                        size="sm">
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'next')}</ButtonText>
                                   </Button>
                              </ButtonGroup>
                         </ScrollView>
                         <Text mt="$2" fontSize={10} color={textColor}>
                              {paginationLabel}
                         </Text>
                    </Box>
               );
          }

          return null;
     };

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     const NoResults = () => {
          return (
               <>
                    {_.size(systemMessagesForScreen) > 0 ? <Box p="$2">{showSystemMessage()}</Box> : null}
                    <Center flex={1}>
                         <Heading pt="$5">{getTermFromDictionary(language, 'no_results')}</Heading>
                    </Center>
               </>
          );
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessagesForScreen) > 0 ? <Box p="$2">{showSystemMessage()}</Box> : null}
               {status === 'loading' || isFetching ? (
                    LoadingSpinner('Fetching results...')
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <Box flex={1}>
                         <FlatList data={data.results} ListFooterComponent={Paging} ListEmptyComponent={NoResults} renderItem={({ item }) => <DisplayResult data={item} />} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               )}
          </SafeAreaView>
     );
};