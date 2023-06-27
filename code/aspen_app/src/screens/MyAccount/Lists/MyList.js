import { MaterialIcons } from '@expo/vector-icons';
import _ from 'lodash';
import { Box, Button, FlatList, HStack, Icon, Image, Pressable, Text, VStack, ScrollView, FormControl, CheckIcon, Select } from 'native-base';
import React from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import EditList from './EditList';
import { useRoute } from '@react-navigation/native';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getListTitles, removeTitlesFromList } from '../../../util/api/list';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getCleanTitle } from '../../../helpers/item';
import { loadError } from '../../../components/loadError';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';

export const MyList = () => {
     const providedList = useRoute().params.details;
     const id = providedList.id;
     const [page, setPage] = React.useState(1);
     const [sort, setSort] = React.useState('dateAdded');
     const [pageSize, setPageSize] = React.useState(25);
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [list] = React.useState(providedList);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const { language } = React.useContext(LanguageContext);
     const [sortBy, setSortBy] = React.useState({
          title: 'Sort By Title',
          dateAdded: 'Sort By Date Added',
          recentlyAdded: 'Sort By Recently Added',
          custom: 'Sort By User Defined',
     });

     React.useEffect(() => {
          async function fetchTranslations() {
               let tmp = sortBy;
               let term = '';

               term = getTermFromDictionary(language, 'sort_by_title');
               if (!term.includes('%1%')) {
                    tmp = _.set(tmp, 'title', term);
                    setSortBy(tmp);
               }

               term = getTermFromDictionary(language, 'sort_by_date_added');
               if (!term.includes('%1%')) {
                    tmp = _.set(tmp, 'dateAdded', term);
                    setSortBy(tmp);
               }

               term = getTermFromDictionary(language, 'sort_by_recently_added');
               if (!term.includes('%1%')) {
                    tmp = _.set(tmp, 'recentlyAdded', term);
                    setSortBy(tmp);
               }

               term = getTermFromDictionary(language, 'sort_by_user_defined');
               if (!term.includes('%1%')) {
                    tmp = _.set(tmp, 'custom', term);
                    setSortBy(tmp);
               }
          }
          fetchTranslations();
     }, [language]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['list', id, user.id], () => getListTitles(id, library.baseUrl, page, pageSize, pageSize, sort), {
          keepPreviousData: true,
     });

     const { data: paginationLabel, isFetching: translationIsFetching } = useQuery({
          queryKey: ['totalPages', library.baseUrl, page, id],
          queryFn: () => getTranslationsWithValues('page_of_page', [page, data?.totalPages], language, library.baseUrl),
          enabled: !!data,
     });

     const handleOpenItem = (id, title) => {
          if (version >= '23.01.00') {
               navigateStack('AccountScreenTab', 'ListItem', {
                    id: id,
                    url: library.baseUrl,
                    title: getCleanTitle(title),
               });
          } else {
               navigateStack('AccountScreenTab', 'ListItem221200', {
                    id: id,
                    title: getCleanTitle(title),
                    url: library.baseUrl,
                    userContext: user,
                    libraryContext: library,
               });
          }
     };

     if (status !== 'loading') {
          if (!_.isUndefined(data.defaultSort)) {
               setSort(data.defaultSort);
          }
     }

     const queryClient = useQueryClient();

     const renderItem = (item) => {
          const imageUrl = item.image;
          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => handleOpenItem(item.id, item.title)}>
                    <HStack space={3}>
                         <VStack maxW="35%">
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
                              <Button
                                   onPress={() => {
                                        removeTitlesFromList(id, item.id, library.baseUrl).then(async () => {
                                             queryClient.invalidateQueries({ queryKey: ['list', id] });
                                        });
                                   }}
                                   colorScheme="danger"
                                   leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" />}
                                   size="sm"
                                   variant="ghost">
                                   {getTermFromDictionary(language, 'delete')}
                              </Button>
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
                         </VStack>
                    </HStack>
               </Pressable>
          );
     };

     const Paging = () => {
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
                                        isReadOnly={Platform.OS === 'android'}
                                        name="sortBy"
                                        selectedValue={sort}
                                        accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        onValueChange={(itemValue) => setSort(itemValue)}>
                                        <Select.Item label={sortBy.title} value="title" key={0} />
                                        <Select.Item label={sortBy.dateAdded} value="dateAdded" key={1} />
                                        <Select.Item label={sortBy.recentlyAdded} value="recentlyAdded" key={2} />
                                        <Select.Item label={sortBy.custom} value="custom" key={3} />
                                   </Select>
                              </FormControl>
                              <EditList data={list} listId={id} />
                         </HStack>
                    </ScrollView>
               </Box>
          );
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {status === 'loading' || isFetching || translationIsFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <>
                         <Box style={{ paddingBottom: 100 }}>
                              {getActionButtons()}
                              <FlatList data={data.listTitles} ListFooterComponent={Paging} renderItem={({ item }) => renderItem(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
                         </Box>
                    </>
               )}
          </SafeAreaView>
     );
};