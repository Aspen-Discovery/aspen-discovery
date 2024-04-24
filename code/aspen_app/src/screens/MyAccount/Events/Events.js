import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import moment from 'moment';
import { Badge, Box, Button, Center, Container, FlatList, HStack, Icon, Pressable, ScrollView, Stack, Text, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { loadError, popAlert, popToast } from '../../../components/loadError';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getCleanTitle } from '../../../helpers/item';
import { navigate } from '../../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { fetchSavedEvents, removeSavedEvent } from '../../../util/api/event';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MyEvents = () => {
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const [isLoading, setLoading] = React.useState(false);
     const [page, setPage] = React.useState(1);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { user, savedEvents, updateSavedEvents } = React.useContext(UserContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const url = library.baseUrl;
     const pageSize = 25;
     const systemMessagesForScreen = [];

     const [filterBy, setFilterBy] = React.useState('upcoming');
     const [paginationLabel, setPaginationLabel] = React.useState('Page 1 of 1');
     const [events, updateEvents] = React.useState([]);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     React.useEffect(() => {
          if (_.isArray(systemMessages)) {
               systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         systemMessagesForScreen.push(obj);
                    }
               });
          }
     }, [systemMessages]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['saved_events', user.id, library.baseUrl, page, filterBy], () => fetchSavedEvents(page, pageSize, filterBy, library.baseUrl), {
          initialData: savedEvents,
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               updateSavedEvents(data.events);
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

     const getActionButtons = () => {
          return (
               <Box
                    alignItems="center"
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderBottomWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200">
                    <Button.Group alignItems="center" isAttached size="sm" pb={1}>
                         <Button
                              variant={filterBy === 'all' ? 'solid' : 'outline'}
                              onPress={() => setFilterBy('all')}
                              _dark={{
                                   borderWidth: '1',
                                   borderColor: 'gray.400',
                              }}>
                              {getTermFromDictionary(language, 'all_events')}
                         </Button>
                         <Button
                              variant={filterBy === 'upcoming' ? 'solid' : 'outline'}
                              _dark={{
                                   borderWidth: '1',
                                   borderColor: 'gray.400',
                              }}
                              onPress={() => setFilterBy('upcoming')}>
                              {getTermFromDictionary(language, 'upcoming_events')}
                         </Button>
                         <Button
                              _dark={{
                                   borderWidth: '1',
                                   borderColor: 'gray.400',
                              }}
                              variant={filterBy === 'past' ? 'solid' : 'outline'}
                              onPress={() => setFilterBy('past')}>
                              {getTermFromDictionary(language, 'past_events')}
                         </Button>
                    </Button.Group>
               </Box>
          );
     };

     const Empty = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {filterBy === 'upcoming' ? getTermFromDictionary(language, 'no_events_upcoming') : filterBy === 'past' ? getTermFromDictionary(language, 'no_events_past') : getTermFromDictionary(language, 'no_events_all')}
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
               {getActionButtons()}
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <>
                         <FlatList data={Object.keys(savedEvents)} ListEmptyComponent={Empty} ListFooterComponent={Paging} renderItem={({ item }) => <Item data={savedEvents[item]} filterBy={filterBy} setLoading={setLoading} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                    </>
               )}
          </SafeAreaView>
     );
};

const Item = (data) => {
     const filterBy = data.filterBy;
     const setLoading = data.setLoading;
     const event = data.data;
     const queryClient = useQueryClient();
     const { user } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

     let coverUrl = event.cover;
     if (_.isNull(event.cover)) {
          coverUrl = library.baseUrl + '/bookcover.php?size=medium&id=' + event.sourceId;
     }

     let registrationRequired = false;
     if (!_.isUndefined(event.registrationRequired)) {
          registrationRequired = event.registrationRequired;
     }

     let hasPassed = false;
     if (typeof event.pastEvent !== 'undefined') {
          hasPassed = event.pastEvent;
     }

     const start = event.startDate ?? null;
     const end = event.endDate ?? null;
     let displayDay = false;
     let displayStartTime = false;
     let displayEndTime = false;
     let day = '';
     let time1arr = '';
     let time2arr = '';
     let startTime = null;
     let endTime = null;

     if (start) {
          startTime = start.date;
          let time1 = startTime.split(' ');
          day = time1[0];
          time1arr = time1[1].split(':');
          displayDay = moment(day);
          displayStartTime = moment().set({ hour: time1arr[0], minute: time1arr[1] });
          displayDay = moment(displayDay).format('dddd, MMMM D, YYYY');
          displayStartTime = moment(displayStartTime).format('h:mm A');
     }

     if (end) {
          endTime = end.date;
          let time2 = endTime.split(' ');
          time2arr = time2[1].split(':');
          displayEndTime = moment().set({ hour: time2arr[0], minute: time2arr[1] });
          displayEndTime = moment(displayEndTime).format('h:mm A');
     }

     const key = 'medium_' + event.sourceId;

     let source = event.source;
     if (event.source === 'lc') {
          source = 'library_calendar';
     }
     if (event.source === 'libcal') {
          source = 'springshare';
     }

     const openEvent = () => {
          if (!event.pastEvent && event.endDate) {
               if (event.bypass) {
                    openURL(event.url);
               } else {
                    navigate('EventDetails', {
                         id: event.sourceId,
                         title: getCleanTitle(event.title),
                         url: library.baseUrl,
                         source: source,
                    });
               }
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
          await WebBrowser.openBrowserAsync(url, browserParams)
               .then((res) => {
                    console.log(res);
                    if (res.type === 'cancel' || res.type === 'dismiss') {
                         console.log('User closed or dismissed window.');
                         WebBrowser.dismissBrowser();
                         WebBrowser.coolDownAsync();
                    }
               })
               .catch(async (err) => {
                    if (err.message === 'Another WebBrowser is already being presented.') {
                         try {
                              WebBrowser.dismissBrowser();
                              WebBrowser.coolDownAsync();
                              await WebBrowser.openBrowserAsync(url, browserParams)
                                   .then((response) => {
                                        console.log(response);
                                        if (response.type === 'cancel') {
                                             console.log('User closed window.');
                                        }
                                   })
                                   .catch(async (error) => {
                                        console.log('Unable to close previous browser session.');
                                   });
                         } catch (error) {
                              console.log('Really borked.');
                         }
                    } else {
                         popToast(getTermFromDictionary('en', 'error_no_open_resource'), getTermFromDictionary('en', 'error_device_block_browser'), 'error');
                         console.log(err);
                    }
               });
     };

     const removeEvent = async () => {
          setLoading(true);
          await removeSavedEvent(event.sourceId, language, library.baseUrl).then((result) => {
               setLoading(false);
               queryClient.invalidateQueries({ queryKey: ['saved_events', user.id, library.baseUrl, 1, filterBy] });
               queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
               queryClient.invalidateQueries({ queryKey: ['event', event.sourceId, source, language, library.baseUrl] });
               if (result.success || result.success === 'true') {
                    popAlert(getTermFromDictionary(language, 'removed_successfully'), result.message, 'success');
               } else {
                    popAlert(getTermFromDictionary(language, 'error'), result.message, 'error');
               }
          });
     };

     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={openEvent}>
               <HStack space={3}>
                    {event.cover ? (
                         <VStack maxW="35%">
                              {hasPassed ? (
                                   <Container zIndex={1}>
                                        <Badge colorScheme="warning" shadow={1} mb={-3} ml={-1} _text={{ fontSize: 9 }}>
                                             {getTermFromDictionary(language, 'flag_past')}
                                        </Badge>
                                   </Container>
                              ) : null}
                              <Image
                                   alt={event.title}
                                   source={coverUrl}
                                   style={{
                                        width: 100,
                                        height: 150,
                                        borderRadius: 4,
                                   }}
                                   placeholder={blurhash}
                                   transition={1000}
                                   contentFit="cover"
                              />

                              <Button size="sm" variant="ghost" colorScheme="danger" leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" mr="-1" />} style={{ flex: 1, flexWrap: 'wrap' }} onPress={() => removeEvent()}>
                                   {getTermFromDictionary(language, 'remove')}
                              </Button>
                         </VStack>
                    ) : null}

                    <VStack w={event.cover ? '65%' : '100%'}>
                         <Text
                              _dark={{ color: 'warmGray.50' }}
                              color="coolGray.800"
                              bold
                              fontSize={{
                                   base: 'md',
                                   lg: 'lg',
                              }}>
                              {event.title}
                         </Text>
                         {event.startDate && event.endDate ? (
                              <>
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                        {displayDay}
                                   </Text>
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                        {displayStartTime} - {displayEndTime}
                                   </Text>
                              </>
                         ) : event.startDate && !event.endDate ? (
                              <>
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                        {displayDay}
                                   </Text>
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800">
                                        {displayStartTime}
                                   </Text>
                              </>
                         ) : null}
                         {!event.cover ? (
                              <Box alignItems="start" pt={2}>
                                   <Button padding={0} size="sm" variant="ghost" colorScheme="danger" leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" mr="-1" />} onPress={() => removeEvent()}>
                                        {getTermFromDictionary(language, 'remove')}
                                   </Button>
                              </Box>
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
};