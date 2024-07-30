import { MaterialIcons } from '@expo/vector-icons';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { ListItem } from '@rneui/themed';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import { Actionsheet, Alert, AlertDialog, Box, Button, Center, CheckIcon, FlatList, FormControl, HStack, Icon, Pressable, ScrollView, Select, Text, VStack } from 'native-base';
import React from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { loadError } from '../../../components/loadError';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { getAuthor, getCleanTitle, getDateLastUsed, getFormat, getTitle } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { deleteAllReadingHistory, deleteSelectedReadingHistory, fetchReadingHistory, optIntoReadingHistory, optOutOfReadingHistory } from '../../../util/api/user';
import AddToList from '../../Search/AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MyReadingHistory = () => {
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const [isLoading, setLoading] = React.useState(false);
     const [page, setPage] = React.useState(1);
     const [sort, setSort] = React.useState('checkedOut');
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { user, updateUser, readingHistory, updateReadingHistory } = React.useContext(UserContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const url = library.baseUrl;
     const pageSize = 20;
     const systemMessagesForScreen = [];
     const [paginationLabel, setPaginationLabel] = React.useState('Page 1 of 1');
     const { textColor } = React.useContext(ThemeContext);

     const [sortBy, setSortBy] = React.useState({
          title: 'Sort by Title',
          author: 'Sort by Author',
          format: 'Sort by Format',
          last_used: 'Sort by Last Used',
     });

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['reading_history', user.id, library.baseUrl, page, sort], () => fetchReadingHistory(page, pageSize, sort, library.baseUrl), {
          initialData: readingHistory,
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               updateReadingHistory(data);
               if (data.totalPages) {
                    let tmp = getTermFromDictionary(language, 'page_of_page');
                    tmp = tmp.replace('%1%', page);
                    tmp = tmp.replace('%2%', data.totalPages);
                    console.log(tmp);
                    setPaginationLabel(tmp);
               }
          },
          onSettle: (data) => setLoading(false),
     });

     const state = queryClient.getQueryState(['reading_history']);

     useFocusEffect(
          React.useCallback(() => {
               if (_.isArray(systemMessages)) {
                    systemMessages.map((obj, index, collection) => {
                         if (obj.showOn === '0') {
                              systemMessagesForScreen.push(obj);
                         }
                    });
               }
               const update = async () => {
                    let tmp = sortBy;
                    let term = '';

                    term = getTermFromDictionary(language, 'sort_by_title');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'title', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_author');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'author', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_format');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'format', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_last_used');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'last_used', term);
                         setSortBy(tmp);
                    }

                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [language, systemMessages])
     );

     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [optingOut, setOptingOut] = React.useState(false);

     const [deleteAllIsOpen, setDeleteAllIsOpen] = React.useState(false);
     const onCloseDeleteAll = () => setDeleteAllIsOpen(false);
     const deleteAllCancelRef = React.useRef(null);
     const [deleting, setDeleting] = React.useState(false);

     const [optingIn, setOptingIn] = React.useState();

     const optIn = async () => {
          setOptingIn(true);
          await optIntoReadingHistory(library.baseUrl);
          queryClient.invalidateQueries({ queryKey: ['user'] });
          queryClient.invalidateQueries({ queryKey: ['reading_history'] });
          setOptingIn(false);
     };

     const optOut = async () => {
          setOptingOut(true);
          await optOutOfReadingHistory(library.baseUrl);
          await deleteAllReadingHistory(library.baseUrl);
          queryClient.invalidateQueries({ queryKey: ['user'] });
          queryClient.invalidateQueries({ queryKey: ['reading_history'] });
          setIsOpen(false);
          setOptingOut(false);
     };

     const deleteAll = async () => {
          setDeleting(true);
          await deleteAllReadingHistory(library.baseUrl);
          queryClient.invalidateQueries({ queryKey: ['user'] });
          queryClient.invalidateQueries({ queryKey: ['reading_history'] });
          setDeleteAllIsOpen(false);
          setDeleting(false);
     };

     const updateSort = async (value) => {
          console.log('updateSort: ' + value);
          setLoading(true);
          setSort(value);
          await queryClient.invalidateQueries({ queryKey: ['reading_history', user.id, library.baseUrl, page, sort] });
          await queryClient.refetchQueries({ queryKey: ['reading_history', user.id, library.baseUrl, page, value] });
          setLoading(false);
     };

     const updatePage = async (value) => {
          console.log('updatePage: ' + value);
          setLoading(true);
          setPage(value);
          await queryClient.invalidateQueries({ queryKey: ['reading_history', user.id, library.baseUrl, page, sort] });
          await queryClient.refetchQueries({ queryKey: ['reading_history', user.id, library.baseUrl, value, sort] });
          setLoading(false);
     };

     const [expanded, setExpanded] = React.useState(false);
     const getDisclaimer = () => {
          return (
               <ListItem.Accordion
                    containerStyle={{
                         backgroundColor: 'transparent',
                         paddingBottom: 2,
                    }}
                    content={
                         <>
                              <ListItem.Content
                                   containerStyle={{
                                        width: '100%',
                                        padding: 0,
                                   }}>
                                   <Alert status="info" colorScheme="info" w="100%" p={1}>
                                        <HStack flexShrink={1} space={2} alignItems="center">
                                             <Alert.Icon />
                                             <Text fontSize="xs" bold color="coolGray.800">
                                                  {getTermFromDictionary(language, 'reading_history_privacy_notice')}
                                             </Text>
                                        </HStack>
                                   </Alert>
                              </ListItem.Content>
                         </>
                    }
                    isExpanded={expanded}
                    onPress={() => {
                         setExpanded(!expanded);
                    }}>
                    <ListItem
                         key={0}
                         borderBottom
                         containerStyle={{
                              backgroundColor: 'transparent',
                              paddingTop: 1,
                         }}>
                         <ListItem.Content containerStyle={{ padding: 0 }}>
                              <Text fontSize="xs" color="coolGray.600">
                                   {getTermFromDictionary(language, 'reading_history_disclaimer')}
                              </Text>
                         </ListItem.Content>
                    </ListItem>
               </ListItem.Accordion>
          );
     };

     const getActionButtons = () => {
          let sortLength = 8 * sortBy.last_used.length + 80;
          if (sort === 'author') {
               sortLength = 8 * sortBy.author.length + 80;
          } else if (sort === 'format') {
               sortLength = 8 * sortBy.format.length + 80;
          } else if (sort === 'title') {
               sortLength = 8 * sortBy.title.length + 80;
          } else if (sort === 'checkedOut') {
               sortLength = 8 * sortBy.last_used.length + 80;
          }
          return (
               <Box
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderBottomWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200"
                    flexWrap="nowrap">
                    <ScrollView horizontal>
                         <HStack space={2}>
                              <FormControl w={sortLength}>
                                   <Select
                                        _dark={{
                                             borderWidth: '1',
                                             borderColor: 'gray.400',
                                        }}
                                        isReadOnly={Platform.OS === 'android'}
                                        name="sortBy"
                                        selectedValue={sort}
                                        accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        onValueChange={(itemValue) => updateSort(itemValue)}>
                                        <Select.Item label={sortBy.title} value="title" key={0} />
                                        <Select.Item label={sortBy.author} value="author" key={1} />
                                        <Select.Item label={sortBy.last_used} value="checkedOut" key={2} />
                                        <Select.Item label={sortBy.format} value="format" key={3} />
                                   </Select>
                              </FormControl>
                              <Button.Group size="sm" variant="solid" colorScheme="danger">
                                   <Button onPress={() => setDeleteAllIsOpen(true)}>{getTermFromDictionary(language, 'reading_history_delete_all')}</Button>
                                   <Button onPress={() => setIsOpen(true)}>{getTermFromDictionary(language, 'reading_history_opt_out')}</Button>
                              </Button.Group>
                         </HStack>
                    </ScrollView>

                    <Center>
                         <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                              <AlertDialog.Content>
                                   <AlertDialog.Header>{getTermFromDictionary(language, 'reading_history_opt_out')}</AlertDialog.Header>
                                   <AlertDialog.Body>{getTermFromDictionary(language, 'reading_history_opt_out_warning')}</AlertDialog.Body>
                                   <AlertDialog.Footer>
                                        <Button.Group space={3}>
                                             <Button colorScheme="muted" variant="outline" onPress={onClose}>
                                                  {getTermFromDictionary(language, 'cancel')}
                                             </Button>
                                             <Button isLoading={optingOut} isLoadingText={getTermFromDictionary(language, 'updating', true)} colorScheme="danger" onPress={optOut} ref={cancelRef}>
                                                  {getTermFromDictionary(language, 'button_ok')}
                                             </Button>
                                        </Button.Group>
                                   </AlertDialog.Footer>
                              </AlertDialog.Content>
                         </AlertDialog>
                    </Center>

                    <Center>
                         <AlertDialog leastDestructiveRef={deleteAllCancelRef} isOpen={deleteAllIsOpen} onClose={onCloseDeleteAll}>
                              <AlertDialog.Content>
                                   <AlertDialog.Header>{getTermFromDictionary(language, 'reading_history_delete_all')}</AlertDialog.Header>
                                   <AlertDialog.Body>{getTermFromDictionary(language, 'reading_history_delete_all_warning')}</AlertDialog.Body>
                                   <AlertDialog.Footer>
                                        <Button.Group space={3}>
                                             <Button colorScheme="muted" variant="outline" onPress={onCloseDeleteAll}>
                                                  {getTermFromDictionary(language, 'cancel')}
                                             </Button>
                                             <Button isLoading={deleting} isLoadingText={getTermFromDictionary(language, 'deleting', true)} colorScheme="danger" onPress={deleteAll} ref={cancelRef}>
                                                  {getTermFromDictionary(language, 'button_ok')}
                                             </Button>
                                        </Button.Group>
                                   </AlertDialog.Footer>
                              </AlertDialog.Content>
                         </AlertDialog>
                    </Center>
               </Box>
          );
     };

     const Empty = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'reading_history_empty')}
                    </Text>
               </Center>
          );
     };

     const Paging = () => {
          if (data?.totalResults > 0) {
               return (
                    <Box
                         safeArea={2}
                         bgColor="coolGray.100"
                         borderTopWidth="1"
                         _dark={{
                              borderColor: 'gray.600',
                              bg: 'coolGray.700',
                         }}
                         borderColor="coolGray.200"
                         flexWrap="nowrap"
                         alignItems="center">
                         <ScrollView horizontal>
                              <Button.Group size="sm">
                                   <Button onPress={() => updatePage(page - 1)} isDisabled={page === 1}>
                                        {getTermFromDictionary(language, 'previous')}
                                   </Button>
                                   <Button
                                        onPress={async () => {
                                             if (!isPreviousData && data?.hasMore) {
                                                  console.log('Adding to page');
                                                  let newPage = page + 1;
                                                  updatePage(newPage);
                                                  setLoading(true);
                                                  await fetchReadingHistory(newPage, pageSize, sort, library.baseUrl).then((result) => {
                                                       updateReadingHistory(data);
                                                       if (data.totalPages) {
                                                            let tmp = getTermFromDictionary(language, 'page_of_page');
                                                            tmp = tmp.replace('%1%', newPage);
                                                            tmp = tmp.replace('%2%', data.totalPages);
                                                            console.log(tmp);
                                                            setPaginationLabel(tmp);
                                                       }
                                                       queryClient.setQueryData(['reading_history', user.id, library.baseUrl, page, sort], result);
                                                       queryClient.setQueryData(['reading_history', user.id, library.baseUrl, newPage, sort], result);
                                                  });
                                                  setLoading(false);
                                             }
                                        }}
                                        isDisabled={isPreviousData || !data?.hasMore}>
                                        {getTermFromDictionary(language, 'next')}
                                   </Button>
                              </Button.Group>
                         </ScrollView>
                         <Text mt={2} fontSize="sm">
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
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessagesForScreen) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
               {user.trackReadingHistory !== '1' ? (
                    <Box safeArea={5}>
                         <Button onPress={optIn} isLoading={optingIn} isLoadingText={getTermFromDictionary(language, 'updating', true)}>
                              {getTermFromDictionary(language, 'reading_history_opt_in')}
                         </Button>
                         {getDisclaimer()}
                    </Box>
               ) : (
                    <>
                         {getActionButtons()}
                         {status === 'loading' || isFetching || isLoading ? (
                              loadingSpinner()
                         ) : status === 'error' ? (
                              loadError('Error', '')
                         ) : (
                              <>
                                   <FlatList data={data.history} ListEmptyComponent={Empty} ListFooterComponent={Paging} ListHeaderComponent={getDisclaimer} renderItem={({ item }) => <Item data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                              </>
                         )}
                    </>
               )}
          </SafeAreaView>
     );
};

const Item = (data) => {
     const queryClient = useQueryClient();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const item = data.data;

     const [deleting, setDelete] = React.useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const toggle = () => {
          setIsOpen(!isOpen);
     };

     const openGroupedWork = (item, title) => {
          navigateStack('AccountScreenTab', 'ItemDetails', {
               id: item,
               title: getCleanTitle(title),
               url: library.baseUrl,
               userContext: user,
               libraryContext: library,
          });
     };

     const deleteFromHistory = async (item) => {
          await deleteSelectedReadingHistory(item, library.baseUrl).then(async (result) => {
               if (result) {
                    queryClient.invalidateQueries({ queryKey: ['user'] });
                    queryClient.invalidateQueries({ queryKey: ['reading_history'] });
               }
          });
     };

     const imageUrl = library.baseUrl + encodeURI(item.coverUrl);
     ///bookcover.php?id=af5d146c-d9d8-130b-9857-03d4126be9fd-eng&size=small&type=grouped_work&category=Books"
     const key = 'medium_' + item.permanentId;
     let url = library.baseUrl + '/bookcover.php?id=' + item.permanentId + '&size=medium';
     if (item.title) {
          return (
               <Pressable onPress={toggle} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
                    <HStack space={3}>
                         <VStack maxW="30%">
                              <Image
                                   alt={item.title}
                                   source={url}
                                   style={{
                                        width: 100,
                                        height: 150,
                                        borderRadius: 4,
                                   }}
                                   placeholder={blurhash}
                                   transition={1000}
                                   contentFit="cover"
                              />
                              <AddToList itemId={item.permanentId} btnStyle="sm" />
                         </VStack>
                         <VStack w="65%">
                              {getTitle(item.title)}
                              {getAuthor(item.author)}
                              {getFormat(item.format)}
                              {getDateLastUsed(item.checkout, item.checkedOut)}
                         </VStack>
                    </HStack>
                    <Actionsheet isOpen={isOpen} onClose={toggle} size="full">
                         <Actionsheet.Content>
                              <Box w="100%" h={60} px={4} justifyContent="center">
                                   <Text
                                        fontSize="18"
                                        color="gray.500"
                                        _dark={{
                                             color: 'gray.300',
                                        }}>
                                        {getTitle(item.title)}
                                   </Text>
                              </Box>
                              {item.existsInCatalog ? (
                                   <Actionsheet.Item
                                        onPress={() => {
                                             openGroupedWork(item.permanentId, item.title);
                                             toggle();
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6" />}>
                                        {getTermFromDictionary(language, 'view_item_details')}
                                   </Actionsheet.Item>
                              ) : null}
                              <Actionsheet.Item
                                   isLoading={deleting}
                                   isLoadingText={getTermFromDictionary(language, 'removing', true)}
                                   onPress={async () => {
                                        setDelete(true);
                                        await deleteFromHistory(item.permanentId).then((r) => {
                                             setDelete(false);
                                        });
                                        toggle();
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="delete" color="trueGray.400" mr="1" size="6" />}>
                                   {getTermFromDictionary(language, 'reading_history_delete')}
                              </Actionsheet.Item>
                         </Actionsheet.Content>
                    </Actionsheet>
               </Pressable>
          );
     }
     return null;
};