import { useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import moment from 'moment';
import { Badge, Box, Button, Center, FlatList, HStack, Pressable, ScrollView, Stack, Text, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { loadError } from '../../../components/loadError';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getCleanTitle } from '../../../helpers/item';
import { navigate } from '../../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { fetchSavedEvents } from '../../../util/api/event';
import AddToList from '../../Search/AddToList';

export const MyEvents = () => {
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const [isLoading, setLoading] = React.useState(false);
     const [page, setPage] = React.useState(1);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { user } = React.useContext(UserContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const url = library.baseUrl;
     const pageSize = 25;

     const [filterBy, setFilterBy] = React.useState('upcoming');

     const [events, updateEvents] = React.useState([]);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['saved_events', user.id, library.baseUrl, page, filterBy], () => fetchSavedEvents(page, pageSize, filterBy, library.baseUrl), {
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               updateEvents(data.events);
          },
          onSettle: (data) => setLoading(false),
     });

     const { data: paginationLabel, isFetching: translationIsFetching } = useQuery({
          queryKey: ['totalPages', url, page, language],
          queryFn: () => getTranslationsWithValues('page_of_page', [page, data.totalPages], language, library.baseUrl),
          enabled: !!data,
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
                         <Button variant={filterBy === 'all' ? 'solid' : 'outline'} onPress={() => setFilterBy('all')}>
                              {getTermFromDictionary(language, 'all_events')}
                         </Button>
                         <Button variant={filterBy === 'upcoming' ? 'solid' : 'outline'} onPress={() => setFilterBy('upcoming')}>
                              {getTermFromDictionary(language, 'upcoming_events')}
                         </Button>
                         <Button variant={filterBy === 'past' ? 'solid' : 'outline'} onPress={() => setFilterBy('past')}>
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
               {_.size(systemMessages) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
               {getActionButtons()}
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <>
                         <FlatList data={Object.keys(events)} ListEmptyComponent={Empty} ListFooterComponent={Paging} renderItem={({ item }) => <Item data={events[item]} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                    </>
               )}
          </SafeAreaView>
     );
};

const Item = (data) => {
     const event = data.data;
     if (_.isUndefined(event.invalid)) {
          const { language } = React.useContext(LanguageContext);
          const { library } = React.useContext(LibrarySystemContext);
          const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
          const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

          const coverUrl = event.cover;

          let registrationRequired = false;
          if (!_.isUndefined(event.registrationRequired)) {
               registrationRequired = event.registrationRequired;
          }

          const startTime = event.startDate.date;
          const endTime = event.endDate.date;

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

          const key = 'medium_' + event.sourceId;

          let source = event.source;
          if (event.source === 'lc') {
               source = 'library_calendar';
          }
          if (event.source === 'libcal') {
               source = 'springshare';
          }

          const openEvent = () => {
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
          };

          const openURL = async (url) => {
               const browserParams = {
                    enableDefaultShareMenuItem: false,
                    presentationStyle: 'popover',
                    showTitle: false,
                    toolbarColor: backgroundColor,
                    controlsColor: textColor,
                    secondaryToolbarColor: backgroundColor,
               };
               WebBrowser.openBrowserAsync(url, browserParams);
          };

          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={openEvent}>
                    <HStack space={3}>
                         <VStack maxW="35%">
                              <CachedImage
                                   cacheKey={key}
                                   alt={event.title}
                                   source={{
                                        uri: `${coverUrl}`,
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
                              <AddToList itemId={event.sourceId} btnStyle="sm" />
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
                                   {event.title}
                              </Text>
                              {event.startDate && event.endDate ? (
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

     return null;
};