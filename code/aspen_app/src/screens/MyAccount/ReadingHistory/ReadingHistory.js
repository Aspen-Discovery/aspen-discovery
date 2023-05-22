import React from 'react';
import _ from 'lodash';
import { MaterialIcons } from '@expo/vector-icons';
import { useQuery, useQueryClient, useIsFetching } from '@tanstack/react-query';
import { Box, VStack, Button, Text, ScrollView, FlatList, Pressable, HStack, Image, InfoIcon, Center, AlertDialog, Icon, Actionsheet, Alert, CheckIcon, FormControl, Select } from 'native-base';
import { ListItem } from '@rneui/themed';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import CachedImage from 'expo-cached-image';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { deleteAllReadingHistory, deleteSelectedReadingHistory, fetchReadingHistory, getPatronCheckedOutItems, optIntoReadingHistory, optOutOfReadingHistory, refreshProfile, reloadProfile } from '../../../util/api/user';
import { SafeAreaView } from 'react-native';
import { getAuthor, getCleanTitle, getFormat, getTitle } from '../../../helpers/item';
import { loadError } from '../../../components/loadError';
import { navigateStack } from '../../../helpers/RootNavigator';
import AddToList from '../../Search/AddToList';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';

export const MyReadingHistory = () => {
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const [isLoading, setLoading] = React.useState(false);
     const [page, setPage] = React.useState(1);
     const [sort, setSort] = React.useState('checkedOut');
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { user, updateUser, readingHistory, updateReadingHistory } = React.useContext(UserContext);
     const url = library.baseUrl;
     const pageSize = 25;

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
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               updateReadingHistory(data);
          },
          onSettle: (data) => setLoading(false),
     });

     const { data: paginationLabel, isFetching: translationIsFetching } = useQuery({
          queryKey: ['totalPages', url, page, language],
          queryFn: () => getTranslationsWithValues('page_of_page', [page, data.totalPages], language, library.baseUrl),
          enabled: !!data,
     });

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    let tmp = checkoutsBy;
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
          }, [language])
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
                              <FormControl w={150}>
                                   <Select
                                        name="sortBy"
                                        selectedValue={sort}
                                        accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        onValueChange={(itemValue) => setSort(itemValue)}>
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
                                                  {getTermFromDictionary(language, 'close')}
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
                                                  {getTermFromDictionary(language, 'close')}
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
                                   <Button onPress={() => setPage(page - 1)} isDisabled={page === 1}>
                                        {getTermFromDictionary(language, 'previous')}
                                   </Button>
                                   <Button
                                        onPress={() => {
                                             if (!isPreviousData && data?.hasMore) {
                                                  console.log('Adding to page');
                                                  setPage(page + 1);
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

     return (
          <SafeAreaView style={{ flex: 1 }}>
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
                         {status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', '') : <FlatList data={data.history} ListEmptyComponent={Empty} ListFooterComponent={Paging} ListHeaderComponent={getDisclaimer} renderItem={({ item }) => <Item data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />}
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
     return (
          <Pressable onPress={toggle} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3}>
                    <VStack maxW="30%">
                         <CachedImage
                              cacheKey={item.permanentId}
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
                         <AddToList itemId={item.permanentId} btnStyle="sm" />
                    </VStack>
                    <VStack w="65%">
                         {getTitle(item.title)}
                         {getAuthor(item.author)}
                         {getFormat(item.format)}
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
};