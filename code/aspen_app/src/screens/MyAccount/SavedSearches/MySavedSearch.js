import { useIsFocused, useRoute } from '@react-navigation/native';
import _ from 'lodash';
import { Badge, Box, FlatList, Container, Pressable, Text, Stack, HStack, VStack, Image, Center } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { useQuery } from '@tanstack/react-query';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import AddToList from '../../Search/AddToList';
import {LanguageContext, LibrarySystemContext} from '../../../context/initialContext';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getCleanTitle } from '../../../helpers/item';
import {formatDiscoveryVersion} from '../../../util/loadLibrary';
import {getSavedSearch} from '../../../util/api/user';
import {loadError} from '../../../components/loadError';
import {getTermFromDictionary} from '../../../translations/TranslationService';

export const MySavedSearch = () => {
     const route = useRoute();
     const id = route.params.id;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['savedSearch', id, language, library.baseUrl], () => getSavedSearch(id, language, library.baseUrl), {
          staleTime: 1000,
     });

     const Empty = () => {
          return (
              <Center mt={5} mb={5}>
                   <Text bold fontSize="lg">
                        {getTermFromDictionary(language, 'no_results_found')}
                   </Text>
              </Center>
          );
     };

     return (
         <SafeAreaView style={{ flex: 1 }}>
              <Box safeArea={2}>
                   {status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', '') : <FlatList data={data} ListEmptyComponent={Empty} renderItem={({ item }) => <SavedSearch data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />}
              </Box>
         </SafeAreaView>
     )
}

const SavedSearch = (data) => {
     const item = data.data;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const imageUrl = library.baseUrl + item.image;
     let formats = [];
     if (item.format) {
          formats = getFormats(item.format);
     }
     let isNew = false;
     if (typeof item.isNew !== 'undefined') {
          isNew = item.isNew;
     }

     const openGroupedWork = () => {
          const version = formatDiscoveryVersion(library.discoveryVersion);
          if(version >= '23.01.00') {
               navigateStack('AccountScreenTab', 'SavedSearchItem', {
                    id: item.id,
                    title: getCleanTitle(item.title),
               });
          } else {
               navigateStack('AccountScreenTab', 'SavedSearchItem221200', {
                    id: item.id,
                    title: getCleanTitle(item.title),
                    url: library.baseUrl,
               });
          }
     }

     return (
         <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => openGroupedWork()}>
              <HStack space={3} justifyContent="flex-start" alignItems="flex-start">
                   <VStack maxW="30%">
                        {isNew ? (
                            <Container zIndex={1}>
                                 <Badge colorScheme="warning" shadow={1} mb={-3} ml={-1} _text={{ fontSize: 9 }}>
                                      {getTermFromDictionary(language, 'flag_new')}
                                 </Badge>
                            </Container>
                        ) : null}
                        <Image source={{ uri: imageUrl }} alt={item.title} borderRadius="md" size="90px" />
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
                        <AddToList item={item.id} libraryUrl={library.baseUrl} />
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
                             {item.title}
                        </Text>
                        {item.author ? (
                            <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" fontSize="xs">
                                 {getTermFromDictionary(language, 'by')} {item.author}
                            </Text>
                        ) : null}
                        {item.format ? (
                            <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                                 {formats.map((format, i) => {
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
     )
}

function getFormats(data) {
     let formats = [];
     data.map((item) => {
          let thisFormat = item.split('#');
          thisFormat = thisFormat[thisFormat.length - 1];
          formats.push(thisFormat);
     });
     formats = _.uniq(formats);
     return formats;
}