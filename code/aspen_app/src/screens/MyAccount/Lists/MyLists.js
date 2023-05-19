import moment from 'moment';
import { Badge, Box, Center, FlatList, HStack, Image, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { useQuery, useQueries } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import CreateList from './CreateList';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getListDetails, getLists, getListTitles } from '../../../util/api/list';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const MyLists = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { lists, updateLists } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const [loading, setLoading] = React.useState(false);

     useQuery(['lists', user.id, library.baseUrl, language], () => getLists(library.baseUrl), {
          onSuccess: (data) => {
               updateLists(data);
               setLoading(false);
          },
          placeholderData: [],
     });

     useQueries({
          queries: lists.map((list) => {
               return {
                    queryKey: ['list', list.id, user.id],
                    queryFn: () => getListTitles(list.id, library.baseUrl, 1, 25, 25, 'dateAdded'),
               };
          }),
     });

     useQueries({
          queries: lists.map((list) => {
               return {
                    queryKey: ['list-details', list.id, user.id],
                    queryFn: () => getListDetails(list.id, library.baseUrl),
               };
          }),
     });

     const handleOpenList = (item) => {
          navigateStack('AccountScreenTab', 'MyList', {
               id: item.id,
               details: item,
               title: item.title,
               libraryUrl: library.baseUrl,
          });
     };

     const listEmptyComponent = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'no_lists_yet')}
                    </Text>
               </Center>
          );
     };

     const renderList = (item) => {
          let lastUpdated = moment.unix(item.dateUpdated);
          lastUpdated = moment(lastUpdated).format('MMM D, YYYY');
          const listLastUpdatedOn = getTermFromDictionary(language, 'last_updated_on') + ' ' + lastUpdated;
          const numListItems = item.numTitles + ' ' + getTermFromDictionary(language, 'items');
          let privacy = getTermFromDictionary(language, 'private');
          if (item.public === 1 || item.public === true || item.public === 'true') {
               privacy = getTermFromDictionary(language, 'public');
          }
          const imageUrl = item.cover;
          if (item.id !== 'recommendations') {
               return (
                    <Pressable
                         onPress={() => {
                              handleOpenList(item);
                         }}
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         pl="1"
                         pr="1"
                         py="2">
                         <HStack space={3} justifyContent="flex-start">
                              <VStack space={1}>
                                   <CachedImage
                                        cacheKey={item.id}
                                        alt={item.title}
                                        source={{
                                             uri: `${imageUrl}`,
                                             expiresIn: 86400,
                                        }}
                                        style={{
                                             width: 100,
                                             height: 150,
                                             borderRadius: 4,
                                        }}
                                        resizeMode="cover"
                                        placeholderContent={
                                             <Box
                                                  bg="warmGray.50"
                                                  _dark={{
                                                       bgColor: 'coolGray.800',
                                                  }}
                                                  width={{
                                                       base: 100,
                                                       lg: 200,
                                                  }}
                                                  height={{
                                                       base: 150,
                                                       lg: 250,
                                                  }}
                                             />
                                        }
                                   />
                                   <Badge mt={1}>{privacy}</Badge>
                              </VStack>
                              <VStack space={1} justifyContent="space-between" maxW="80%">
                                   <Box>
                                        <Text bold fontSize="md">
                                             {item.title}
                                        </Text>
                                        {item.description ? (
                                             <Text fontSize="xs" mb={2}>
                                                  {item.description}
                                             </Text>
                                        ) : null}
                                        <Text fontSize="9px" italic>
                                             {listLastUpdatedOn}
                                        </Text>
                                        <Text fontSize="9px" italic>
                                             {numListItems}
                                        </Text>
                                   </Box>
                              </VStack>
                         </HStack>
                    </Pressable>
               );
          }
     };

     if (loading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} t={10} pb={10}>
                    <CreateList />
                    <FlatList data={lists} ListEmptyComponent={listEmptyComponent} renderItem={({ item }) => renderList(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
               </Box>
          </SafeAreaView>
     );
};