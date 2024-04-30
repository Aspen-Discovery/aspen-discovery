import { MaterialIcons } from '@expo/vector-icons';
import { useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import * as Calendar from 'expo-calendar';
import * as WebBrowser from 'expo-web-browser';

import _ from 'lodash';
import moment from 'moment';
import { Alert, Box, Button, Center, Divider, Heading, HStack, Icon, Modal, Pressable, ScrollView, Text, Toast, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { showLocation } from 'react-native-map-link';

// custom components and helper files
import { loadError, popAlert, popToast } from '../../components/loadError';
import { LoadingSpinner, loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getEventDetails, saveEvent } from '../../util/api/event';
import { decodeHTML, stripHTML } from '../../util/apiAuth';
import { PATRON } from '../../util/loadPatron';
import AddToList from '../Search/AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const EventScreen = () => {
     const route = useRoute();
     const queryClient = useQueryClient();
     const id = route.params.id;
     const source = route.params.source;
     const { user, locations, accounts, cards, updatePickupLocations, updateLinkedAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const [hasValidImage, setHasValidImage] = React.useState(false);
     const { status, data, error, isFetching } = useQuery(['event', id, source, language, library.baseUrl], () => getEventDetails(id, source, language, library.baseUrl));

     React.useEffect(() => {
          if (!_.isEmpty(data) && !_.isUndefined(data.results)) {
               const update = async () => {
                    if (!_.isUndefined(data.results.cover)) {
                         if (data.results.cover) {
                              console.log('url: ' + data.results.cover);
                              const urlResult = checkImageUrl(data.results.cover);
                              console.log('result: ' + urlResult);
                              setHasValidImage(urlResult);
                         }
                    }
               };
               update();
          }
     }, [data]);

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
          <ScrollView>
               {status === 'loading' || isFetching ? (
                    <Box pt={50}>{LoadingSpinner('Fetching data...')}</Box>
               ) : status === 'error' ? (
                    <Box pt={50}>{loadError(error, '')}</Box>
               ) : (
                    <>
                         {_.size(systemMessages) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
                         <DisplayEvent data={data.results} source={source} />
                    </>
               )}
          </ScrollView>
     );
};

const DisplayEvent = (payload) => {
     const event = payload.data;
     const hasValidImage = payload.hasValidImage;
     const route = useRoute();
     const id = route.params.id;
     const source = route.params.source;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const key = 'large_' + event.id;

     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

     const openLink = async () => {
          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };

          await WebBrowser.openBrowserAsync(event.url, browserParams)
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
                              await WebBrowser.openBrowserAsync(event.url, browserParams)
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

     return (
          <>
               {event.cover ? <Box h={{ base: 125, lg: 200 }} w="100%" bgColor="warmGray.200" _dark={{ bgColor: 'coolGray.900' }} zIndex={-1} position="absolute" left={0} top={0} /> : null}
               <Box safeArea={5} w="100%">
                    <Center mt={event.cover ? 5 : 0} width="100%">
                         {event.cover ? (
                              <Image
                                   alt={event.title}
                                   source={event.cover}
                                   style={{
                                        width: '100%',
                                        height: 150,
                                        borderRadius: 4,
                                   }}
                                   placeholder={blurhash}
                                   transition={1000}
                                   contentFit="cover"
                              />
                         ) : null}
                         {getTitle(event.title, hasValidImage)}
                    </Center>
                    <VStack divider={<Divider />}>
                         {getAddToCalendar(event.startDate, event.endDate, event.location, event)}
                         {getDirections(event.location, event.room ?? false)}
                    </VStack>
                    {event.registrationRequired && event.registrationBody ? getRegistrationModal(event) : null}
                    {event.inUserEvents ? getInYourEvents() : getAddToYourEvents(event.id, source)}
                    <HStack justifyContent="space-between" space={2}>
                         {event.canAddToList ? <AddToList source="Events" itemId={event.id} btnStyle="reg" btnWidth="48%" /> : null}
                         <Button w={event.canAddToList ? '49%' : '100%'} onPress={() => openLink()}>
                              {getTermFromDictionary(language, 'more_info')}
                         </Button>
                    </HStack>
                    {getDescription(event.description)}
                    <HStack justifyContent="space-between" space={5} mt={5} flexWrap="wrap">
                         {getAudiences(event.audiences)}
                         {getCategories(event.categories)}
                         {getProgramTypes(event.programTypes)}
                    </HStack>
               </Box>
          </>
     );
};

const getTitle = (title, hasCoverImage) => {
     if (title) {
          return (
               <>
                    <Heading pt={hasCoverImage ? 5 : 0} pb={3} alignText="center">
                         {title}
                    </Heading>
               </>
          );
     } else {
          return null;
     }
};

const getDescription = (description) => {
     const { language } = React.useContext(LanguageContext);
     if (description) {
          return (
               <Box mt={5}>
                    <Text fontSize={{ base: 'lg', lg: '2xl' }} bold alignText="center">
                         {getTermFromDictionary(language, 'about')}
                    </Text>
                    <Text fontSize={{ base: 'md', lg: 'lg' }} lineHeight={{ base: '22px', lg: '26px' }}>
                         {decodeHTML(description)}
                    </Text>
               </Box>
          );
     } else {
          return null;
     }
};

const getAudiences = (audiences) => {
     const { language } = React.useContext(LanguageContext);
     if (audiences) {
          return (
               <Box>
                    <Text fontSize={{ base: 'lg', lg: '2xl' }} bold alignText="center">
                         {getTermFromDictionary(language, 'audiences')}
                    </Text>
                    {_.map(audiences, function (item, index, array) {
                         return <Text>{item}</Text>;
                    })}
               </Box>
          );
     } else {
          return null;
     }
};

const getCategories = (categories) => {
     const { language } = React.useContext(LanguageContext);
     if (categories) {
          return (
               <Box>
                    <Text fontSize={{ base: 'lg', lg: '2xl' }} bold alignText="center">
                         {getTermFromDictionary(language, 'categories')}
                    </Text>
                    {_.map(categories, function (item, index, array) {
                         return <Text>{item}</Text>;
                    })}
               </Box>
          );
     } else {
          return null;
     }
};

const getProgramTypes = (programTypes) => {
     const { language } = React.useContext(LanguageContext);
     if (programTypes) {
          return (
               <Box>
                    <Text fontSize={{ base: 'lg', lg: '2xl' }} bold alignText="center">
                         {getTermFromDictionary(language, 'program_types')}
                    </Text>
                    {_.map(programTypes, function (item, index, array) {
                         return <Text>{item}</Text>;
                    })}
               </Box>
          );
     } else {
          return null;
     }
};

const getAddToCalendar = (start, end, location, event) => {
     const { language } = React.useContext(LanguageContext);
     const [showModal, setShowModal] = React.useState(false);
     const [modalBodyText, setModalBodyText] = React.useState('');
     const [modalBodyHeading, setModalBodyHeading] = React.useState('');
     const [calendarId, setCalendarId] = React.useState();
     const [confirmAdd, setConfirmAdd] = React.useState(false);

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

     const handleAddToCalendar = async () => {
          const { status } = await Calendar.requestCalendarPermissionsAsync();
          if (status === 'granted') {
               const defaultCalendarSource =
                    Platform.OS !== 'android'
                         ? await Calendar.getDefaultCalendarAsync()
                         : {
                                isLocalAccount: true,
                                name: location.name + ' Events',
                           };

               const calendars = await Calendar.getCalendarsAsync();

               let id = null;
               if (_.find(calendars, _.matchesProperty('title', location.name + ' Events'))) {
                    const deviceCalendar = _.find(calendars, _.matchesProperty('title', location.name + ' Events'));
                    id = deviceCalendar.id;
               } else {
                    id = await Calendar.createCalendarAsync({
                         title: location.name + ' Events',
                         color: 'yellow',
                         entityType: Calendar.EntityTypes.EVENT,
                         sourceId: defaultCalendarSource?.source?.id,
                         source: defaultCalendarSource,
                         name: 'libraryCalendarEvents',
                         ownerAccount: 'personal',
                         accessLevel: Calendar.CalendarAccessLevel.OWNER,
                    });
               }

               console.log('calendarId: ' + calendarId);
               setCalendarId(id);
               setConfirmAdd(true);
               setModalBodyHeading(getTermFromDictionary(language, 'add_to_calendar'));
               setModalBodyText(getTermFromDictionary(language, 'add_to_calendar_body'));
               setShowModal(true);
          } else {
               setModalBodyHeading(getTermFromDictionary(language, 'error'));
               setModalBodyText(getTermFromDictionary(language, 'event_no_permissions'));
               setShowModal(true);
          }
     };

     const createCalendarEvent = async () => {
          const starts = moment(day).set({ hour: time1arr[0], minute: time1arr[1] });
          const ends = moment(day).set({ hour: time2arr[0], minute: time2arr[1] });
          let eventLocation = location.name;
          if (location.address) {
               eventLocation = eventLocation + ' ' + location.address;
          }
          if (calendarId) {
               try {
                    await Calendar.createEventAsync(calendarId, {
                         title: event.title,
                         startDate: moment(starts, "YYYY-MM-DD'T'HH:mm:ss.sssZ").toDate(),
                         endDate: moment(ends, "YYYY-MM-DD'T'HH:mm:ss.sssZ").toDate(),
                         id: event.id,
                         location: eventLocation,
                         allDay: event.isAllDay ?? false,
                         url: event.url,
                    }).then(async (result) => {
                         console.log(result);
                         return Toast.show({
                              duration: 5000,
                              accessibilityAnnouncement: getTermFromDictionary(language, 'event_added_to_calendar'),
                              avoidKeyboard: true,
                              render: () => {
                                   return (
                                        <Center>
                                             <Alert maxW="400" status="success" colorScheme="success">
                                                  <VStack space={2} flexShrink={1} w="100%">
                                                       <HStack flexShrink={1} space={2} alignItems="center" justifyContent="space-between">
                                                            <HStack flexShrink={1} space={2}>
                                                                 <Alert.Icon />
                                                                 <Text fontSize="md" fontWeight="medium" _dark={{ color: 'coolGray.800' }}>
                                                                      {getTermFromDictionary(language, 'added_successfully')}
                                                                 </Text>
                                                            </HStack>
                                                       </HStack>
                                                       <Box pl="6" _dark={{ _text: { color: 'coolGray.600' } }}>
                                                            <Text>{getTermFromDictionary(language, 'event_added_to_calendar')}</Text>
                                                       </Box>
                                                  </VStack>
                                             </Alert>
                                        </Center>
                                   );
                              },
                         });
                    });
               } catch (e) {
                    console.log(e);
               }
          }
     };

     return (
          <>
               <Pressable py="3" onPress={() => handleAddToCalendar()}>
                    <HStack space="1" alignItems="center" justifyContent="space-between">
                         <HStack space="3" alignItems="center">
                              <Icon as={MaterialIcons} name="calendar-today" size="5" />
                              <VStack>
                                   <Text bold>{displayDay}</Text>
                                   <Text>
                                        {displayStartTime} - {displayEndTime}
                                   </Text>
                              </VStack>
                         </HStack>
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    </HStack>
               </Pressable>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="md">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{modalBodyHeading}</Heading>
                         </Modal.Header>
                         <Modal.Body>{modalBodyText}</Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button
                                        colorScheme="muted"
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                             setConfirmAdd(false);
                                             setModalBodyText('');
                                             setModalBodyHeading('');
                                        }}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   {confirmAdd ? (
                                        <Button
                                             onPress={() =>
                                                  createCalendarEvent().then((result) => {
                                                       setShowModal(false);
                                                       setConfirmAdd(false);
                                                       setModalBodyText('');
                                                       setModalBodyHeading('');
                                                  })
                                             }>
                                             {getTermFromDictionary(language, 'add_event')}
                                        </Button>
                                   ) : null}
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

const getDirections = (location, room) => {
     let hasCoordinates = false;
     if (location) {
          if (!_.isUndefined(location.coordinates) && _.isObject(location.coordinates)) {
               if (location.coordinates.latitude !== 0 && location.coordinates.longitude !== 0) {
                    hasCoordinates = true;
               }
          }
     }

     const handleGetDirections = async () => {
          if (hasCoordinates) {
               if (PATRON.coords.lat && PATRON.coords.long && PATRON.coords.lat !== 0 && PATRON.coords.long !== 0) {
                    showLocation({
                         latitude: location.coordinates.latitude,
                         longitude: location.coordinates.longitude,
                         sourceLatitude: PATRON.coords.lat,
                         sourceLongitude: PATRON.coords.long,
                         googleForceLatLon: true,
                    });
               } else {
                    showLocation({
                         latitude: location.coordinates.latitude,
                         longitude: location.coordinates.longitude,
                         googleForceLatLon: true,
                    });
               }
          }
     };

     if (location) {
          return (
               <Pressable py="3" mb={5} onPress={() => handleGetDirections()}>
                    <HStack space="1" alignItems="center" justifyContent="space-between">
                         <HStack space="3" alignItems="center">
                              <Icon as={MaterialIcons} name="location-pin" size="5" />
                              <VStack>
                                   {location.name ? <Text bold>{location.name}</Text> : null}
                                   {room ? <Text>{room}</Text> : null}
                                   {location.address ? <Text>{location.address}</Text> : null}
                              </VStack>
                         </HStack>
                         {hasCoordinates ? <Icon as={MaterialIcons} name="chevron-right" size="7" /> : null}
                    </HStack>
               </Pressable>
          );
     }

     return null;
};

const getAddToYourEvents = (id, source) => {
     const queryClient = useQueryClient();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setIsLoading] = React.useState(false);

     const addToEvents = async () => {
          setIsLoading(true);
          await saveEvent(id, language, library.baseUrl).then((result) => {
               setIsLoading(false);
               queryClient.invalidateQueries({ queryKey: ['saved_events', user.id, library.baseUrl, 1, 'upcoming'] });
               queryClient.invalidateQueries({ queryKey: ['saved_events', user.id, library.baseUrl, 1, 'all'] });
               queryClient.invalidateQueries({ queryKey: ['saved_events', user.id, library.baseUrl, 1, 'past'] });
               queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
               queryClient.invalidateQueries({ queryKey: ['event', id, source, language, library.baseUrl] });
               if (result.success || result.success === 'true') {
                    popAlert(getTermFromDictionary(language, 'added_successfully'), result.message, 'success');
               } else {
                    popAlert(getTermFromDictionary(language, 'error'), result.message, 'error');
               }
          });
     };

     return (
          <Button onPress={() => addToEvents()} mb={2} colorScheme="tertiary" isLoading={isLoading} isLoadingText={getTermFromDictionary(language, 'adding', true)}>
               {getTermFromDictionary(language, 'add_to_events')}
          </Button>
     );
};

const getInYourEvents = () => {
     const { language } = React.useContext(LanguageContext);
     return (
          <Button mb={2} colorScheme="tertiary" onPress={() => navigateStack('AccountScreenTab', 'MyEvents')}>
               {getTermFromDictionary(language, 'in_your_events')}
          </Button>
     );
};

const getRegistrationModal = (event) => {
     const { language } = React.useContext(LanguageContext);
     const [showRegistrationModal, setShowRegistrationModal] = React.useState(false);

     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

     const openLink = async () => {
          /* location.homeLink */

          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };

          setShowRegistrationModal(false);
          WebBrowser.openBrowserAsync(event.url, browserParams);
     };

     return (
          <>
               <Button onPress={() => setShowRegistrationModal(true)} mb={2} colorScheme="tertiary">
                    {getTermFromDictionary(language, 'registration_information')}
               </Button>
               <Modal isOpen={showRegistrationModal} onClose={() => setShowRegistrationModal(false)} closeOnOverlayClick={false} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{getTermFromDictionary(language, 'registration_information')}</Heading>
                         </Modal.Header>
                         <Modal.Body>{stripHTML(decodeHTML(event.registrationBody))}</Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button
                                        colorScheme="muted"
                                        variant="outline"
                                        onPress={() => {
                                             setShowRegistrationModal(false);
                                        }}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button onPress={() => openLink()}>{getTermFromDictionary(language, 'go_to_registration')}</Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

async function checkImageUrl(url) {
     fetch(url).then((response) => {
          if (!_.isUndefined(response.status)) {
               console.log(response.status);
               if (response.status === 200 || response.status === 201) {
                    console.log('its valid');
                    return true;
               }
          }
          return false;
     });
}