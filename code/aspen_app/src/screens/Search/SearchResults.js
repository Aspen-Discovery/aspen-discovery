import { loadingSpinner } from '../../components/loadingSpinner';
import { FlashList } from '@shopify/flash-list';
import { Badge, Box, Button, Center, Container, FlatList, Heading, HStack, Icon, Image, Pressable, Stack, Text, VStack } from 'native-base';
import useFetchSearchResults from '../../hooks/useFetchSearchResults';
import { SafeAreaView } from 'react-native';
import { loadError } from '../../components/loadError';
import React from 'react';
import { LIBRARY } from '../../util/loadLibrary';
import AddToList from './AddToList';
import { translate } from '../../translations/translations';

export const SearchResults = () => {
     const { data, isLoading, isError, hasNextPage, fetchNextPage } = useFetchSearchResults();
     const [lastUsedList, setLastUsedList] = React.useState();
     if (isLoading) {
          return loadingSpinner();
     }
     if (isError) {
          return loadError('An error occurred while fetching data', '');
     }

     //const results = data.pages[0].data.result.items;
     const flattenData = data.pages.flatMap((page) => page.data);

     console.log(flattenData);

     const loadNext = () => {
          if (hasNextPage) {
               fetchNextPage();
          }
     };

     const updateListLastUsed = (listId) => {
          setLastUsedList(listId);
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <FlashList keyExtractor={(item, index) => index.toString()} data={flattenData} renderItem={({ item }) => <Result item={item} lastUsedList={lastUsedList} updateListLastUsed={updateListLastUsed} />} onEndReached={loadNext} onEndReachThreshold={0.2} estimatedItemSize={100} />
          </SafeAreaView>
     );
};

const Result = (props) => {
     const { item, updateListLastUsed, lastUsedList } = props;
     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3}>
                    <VStack>
                         <Image
                              source={{ uri: item.image }}
                              alt={item.title}
                              borderRadius="md"
                              size={{
                                   base: '90px',
                                   lg: '120px',
                              }}
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
                         <AddToList item={item.key} libraryUrl={LIBRARY.url} lastListUsed={lastUsedList} updateLastListUsed={updateListLastUsed} />
                    </VStack>
                    <VStack>
                         <Text
                              _dark={{ color: 'warmGray.50' }}
                              color="coolGray.800"
                              bold
                              fontSize={{
                                   base: 'md',
                                   lg: 'lg',
                              }}>
                              {item.title}
                         </Text>
                         {item.author ? (
                              <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                   {translate('grouped_work.by')} {item.author}
                              </Text>
                         ) : null}
                         <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                              {item.itemList.map((item, i) => {
                                   return (
                                        <Badge key={i} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
                                             {item.name}
                                        </Badge>
                                   );
                              })}
                         </Stack>
                    </VStack>
               </HStack>
          </Pressable>
     );
};