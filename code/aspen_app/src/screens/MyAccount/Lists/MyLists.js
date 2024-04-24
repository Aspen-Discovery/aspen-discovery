import { useIsFocused, useNavigation, useRoute } from '@react-navigation/native';
import { useQueries, useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import moment from 'moment';
import { Badge, Box, Center, FlatList, HStack, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { getListDetails, getLists, getListTitles } from '../../../util/api/list';
import CreateList from './CreateList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MyLists = () => {
     const navigation = useNavigation();
     const hasPendingChanges = useRoute().params.hasPendingChanges ?? false;
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { lists, updateLists } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);

     const [loading, setLoading] = React.useState(false);

     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const isFocused = useIsFocused();

     React.useEffect(() => {
          if (isFocused) {
               if (hasPendingChanges) {
                    setLoading(true);
                    queryClient.invalidateQueries({ queryKey: ['lists', user.id, library.baseUrl, language] });
                    navigation.setParams({
                         hasPendingChanges: false,
                    });
               }
          }
     }, [isFocused]);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     useQuery(['lists', user.id, library.baseUrl, language], () => getLists(library.baseUrl), {
          initialData: lists,
          onSuccess: (data) => {
               updateLists(data);
               setLoading(false);
          },
          onSettle: (data) => {
               setLoading(false);
          },
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

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     if (loading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} t={10} pb={10}>
                    {showSystemMessage()}
                    <CreateList setLoading={setLoading} />
                    <FlatList data={lists} ListEmptyComponent={listEmptyComponent} renderItem={({ item }) => renderList(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
               </Box>
          </SafeAreaView>
     );
};