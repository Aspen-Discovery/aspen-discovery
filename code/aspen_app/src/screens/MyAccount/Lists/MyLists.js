import moment from 'moment';
import { useFocusEffect } from '@react-navigation/native';
import { Badge, Box, Center, FlatList, HStack, Image, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import CreateList from './CreateList';
import {LanguageContext, LibrarySystemContext, UserContext} from '../../../context/initialContext';
import { getLists } from '../../../util/api/list';
import { navigateStack } from '../../../helpers/RootNavigator';
import {getTermFromDictionary} from '../../../translations/TranslationService';

export const MyLists = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { lists, updateLists } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const [loading, setLoading] = React.useState(true);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getLists(library.baseUrl).then((result) => {
                         if (lists !== result) {
                              updateLists(result);
                         }
                    });
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

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
          if (item.id !== 'recommendations') {
               return (
                   <Pressable
                       onPress={() => {
                            handleOpenList(item);
                       }}
                       borderBottomWidth="1"
                       _dark={{borderColor: 'gray.600'}}
                       borderColor="coolGray.200"
                       pl="1"
                       pr="1"
                       py="2">
                        <HStack space={3} justifyContent="flex-start">
                             <VStack space={1}>
                                  <Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain"/>
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