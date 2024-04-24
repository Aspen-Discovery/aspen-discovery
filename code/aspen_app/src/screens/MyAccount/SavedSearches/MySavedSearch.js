import { useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import { Badge, Box, Center, Container, FlatList, HStack, Pressable, Stack, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { loadError } from '../../../components/loadError';

// custom components and helper files
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getCleanTitle } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { getSavedSearch } from '../../../util/api/user';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import AddToList from '../../Search/AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MySavedSearch = () => {
     const route = useRoute();
     const id = route.params.id;
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['saved_search', id, user.id], () => getSavedSearch(id, language, library.baseUrl), {
          staleTime: 1000,
          placeholderData: [],
     });

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

     const Empty = () => {
          return (
               <>
                    {_.size(systemMessages) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
                    <Center mt={5} mb={5}>
                         <Text bold fontSize="lg">
                              {getTermFromDictionary(language, 'no_results_found')}
                         </Text>
                    </Center>
               </>
          );
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessages) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
               <Box safeArea={2}>{status === 'error' ? loadError('Error', '') : <FlatList data={data} ListEmptyComponent={Empty} renderItem={({ item }) => <SavedSearch data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />}</Box>
          </SafeAreaView>
     );
};

const SavedSearch = (data) => {
     const item = data.data;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const imageUrl = library.baseUrl + item.image;
     const key = 'medium_' + item.id;

     let formats = [];
     if (item.format) {
          formats = getFormats(item.format);
     }
     let isNew = false;
     if (typeof item.isNew !== 'undefined') {
          isNew = item.isNew;
     }

     const openGroupedWork = () => {
          navigateStack('AccountScreenTab', 'SavedSearchItem', {
               id: item.id,
               title: getCleanTitle(item.title),
          });
     };

     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => openGroupedWork()}>
               <HStack space={3}>
                    <VStack maxW="35%">
                         {isNew ? (
                              <Container zIndex={1}>
                                   <Badge colorScheme="warning" shadow={1} mb={-3} ml={-1} _text={{ fontSize: 9 }}>
                                        {getTermFromDictionary(language, 'flag_new')}
                                   </Badge>
                              </Container>
                         ) : null}
                         <Image
                              alt={item.title}
                              source={imageUrl}
                              style={{
                                   width: 100,
                                   height: 150,
                                   borderRadius: 4,
                              }}
                              placeholder={blurhash}
                              transition={1000}
                              contentFit="cover"
                         />
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
     );
};

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