import { MaterialIcons } from '@expo/vector-icons';
import { useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import moment from 'moment';
import { Badge, Box, Button, CheckIcon, FlatList, FormControl, HStack, Icon, Pressable, ScrollView, Select, Stack, Text, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { loadError } from '../../../components/loadError';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getCleanTitle } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { getListTitles, removeTitlesFromList } from '../../../util/api/list';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import EditList from './EditList';

export const MyList = () => {
     const providedList = useRoute().params.details;
     const id = providedList.id;
     const [page, setPage] = React.useState(1);
     const [sort, setSort] = React.useState('dateAdded');
     const [pageSize, setPageSize] = React.useState(20);
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
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));
     const systemMessagesForScreen = [];

     React.useEffect(() => {
          if (_.isArray(systemMessages)) {
               systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         systemMessagesForScreen.push(obj);
                    }
               });
          }

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
     }, [language, systemMessages]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['list', id, user.id, sort, page], () => getListTitles(id, library.baseUrl, page, pageSize, pageSize, sort), {
          keepPreviousData: false,
          staleTime: 1000,
     });

     const { data: paginationLabel, isFetching: translationIsFetching } = useQuery({
          queryKey: ['totalPages', library.baseUrl, page, id],
          queryFn: () => getTranslationsWithValues('page_of_page', [page, data?.totalPages], language, library.baseUrl),
          enabled: !!data,
     });

     const handleOpenItem = (id, title) => {
          navigateStack('AccountScreenTab', 'ListItem', {
               id: id,
               url: library.baseUrl,
               title: getCleanTitle(title),
          });
     };

     const handleOpenEvent = (item) => {
          if (item.bypass) {
               openURL(item.url);
          } else {
               navigateStack('AccountScreenTab', 'ListItemEvent', {
                    id: item.id,
                    url: library.baseUrl,
                    title: getCleanTitle(item.title),
                    source: item.source,
               });
          }
     };

     const openURL = async (url) => {
          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };
          WebBrowser.openBrowserAsync(url, browserParams);
     };

     if (status !== 'loading') {
          if (!_.isUndefined(data.defaultSort)) {
               setSort(data.defaultSort);
          }
     }

     const queryClient = useQueryClient();

     const renderItem = (item) => {
          const imageUrl = item.image;
          const key = 'medium_' + item.id;

          if (item.recordType === 'event') {
               let registrationRequired = false;
               if (!_.isUndefined(item.registration_required)) {
                    registrationRequired = item.registration_required;
               }

               const startTime = item.start_date.date;
               const endTime = item.end_date.date;

               let time1 = startTime.split(' ');
               let day = time1[0];
               let time2 = endTime.split(' ');

               let time1arr = time1[1].split(':');
               let time2arr = time2[1].split(':');

               let displayDay = moment(day);
               let displayStartTime = moment().set({ hour: time1arr[0], minute: time1arr[1] });
               let displayEndTime = moment().set({ hour: time2arr[0], minute: time2arr[1] });

               displayDay = moment(displayDay).format('dddd, MMMM D, YYYY');
               displayStartTime = moment(displayStartTime).format('h:mm A');
               displayEndTime = moment(displayEndTime).format('h:mm A');

               return (
                    <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => handleOpenEvent(item)}>
                         <HStack space={3}>
                              <VStack maxW="35%">
                                   <CachedImage
                                        cacheKey={key}
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
                                             removeTitlesFromList(id, item.id, library.baseUrl, 'Events').then(async () => {
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
                                             base: 'md',
                                             lg: 'lg',
                                        }}>
                                        {item.title}
                                   </Text>
                                   {item.start_date && item.end_date ? (
                                        <>
                                             <Text>{displayDay}</Text>
                                             <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                                  {displayStartTime} - {displayEndTime}
                                             </Text>
                                        </>
                                   ) : null}
                                   {registrationRequired ? (
                                        <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                                             <Badge key={0} colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>
                                                  {getTermFromDictionary(language, 'registration_required')}
                                             </Badge>
                                        </Stack>
                                   ) : null}
                              </VStack>
                         </HStack>
                    </Pressable>
               );
          }

          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => handleOpenItem(item.id, item.title)}>
                    <HStack space={3}>
                         <VStack maxW="35%">
                              <CachedImage
                                   cacheKey={key}
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
                                        removeTitlesFromList(id, item.id, library.baseUrl, 'GroupedWork').then(async () => {
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

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessagesForScreen) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
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