import { MaterialIcons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import * as Location from 'expo-location';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import moment from 'moment';
import { Box, Button, Divider, FlatList, HStack, Icon, Pressable, ScrollView, Text, VStack } from 'native-base';
import React from 'react';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getLocations } from '../../util/api/location';
import { PATRON } from '../../util/loadPatron';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const AllLocations = () => {
     const [isLoading, setLoading] = React.useState(false);
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location, locations, updateLocations } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const queryClient = useQueryClient();
     const [sort, setSort] = React.useState('alphabetical');
     const [userLatitude, setUserLatitude] = React.useState(0);
     const [userLongitude, setUserLongitude] = React.useState(0);
     const [sortedLocations, setSortedLocations] = React.useState(_.sortBy(locations, ['displayName']));

     const { status, data, error, isFetching } = useQuery(['locations', user.id, library.baseUrl, language, userLatitude, userLongitude, sort], () => getLocations(library.baseUrl, language, userLatitude, userLongitude), {
          initialData: locations,
          onSuccess: (data) => {
               updateLocations(data);
               if (sort === 'distance') {
                    const tmpSortedLocations = _.sortBy(data, ['distance', 'displayName']);
                    setSortedLocations(tmpSortedLocations);
               } else {
                    const tmpSortedLocations = _.sortBy(data, ['displayName']);
                    setSortedLocations(tmpSortedLocations);
               }
               setLoading(false);
          },
          onSettle: (data) => {
               if (sort === 'distance') {
                    const tmpSortedLocations = _.sortBy(data, ['distance', 'displayName']);
                    setSortedLocations(tmpSortedLocations);
               } else {
                    const tmpSortedLocations = _.sortBy(data, ['displayName']);
                    setSortedLocations(tmpSortedLocations);
               }
               setLoading(false);
          },
          placeholderData: [],
     });

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    let latitude = await SecureStore.getItemAsync('latitude');
                    let longitude = await SecureStore.getItemAsync('longitude');
                    setUserLatitude(latitude);
                    setUserLongitude(longitude);

                    if (sort === 'distance') {
                         const { status } = await Location.requestForegroundPermissionsAsync();
                         if (status === 'granted') {
                              let location = await Location.getLastKnownPositionAsync({});
                              if (location != null) {
                                   const latitude = JSON.stringify(location.coords.latitude);
                                   const longitude = JSON.stringify(location.coords.longitude);
                                   await SecureStore.setItemAsync('latitude', latitude);
                                   await SecureStore.setItemAsync('longitude', longitude);
                                   PATRON.coords.lat = latitude;
                                   PATRON.coords.long = longitude;
                                   setUserLatitude(latitude);
                                   setUserLongitude(longitude);
                              }
                         }

                         const tmpSortedLocations = _.sortBy(locations, ['distance', 'displayName']);
                         setSortedLocations(tmpSortedLocations);
                    }

                    if (sort === 'alphabetical') {
                         const tmpSortedLocations = _.sortBy(locations, ['displayName']);
                         setSortedLocations(tmpSortedLocations);
                    }
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [sort])
     );

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

     const updateSort = (sort) => {
          setLoading(true);
          setSort(sort);
     };

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
                    <Button.Group alignItems="center" isAttached colorScheme="secondary">
                         <Button variant={sort === 'alphabetical' ? 'solid' : 'outline'} onPress={() => updateSort('alphabetical')}>
                              {getTermFromDictionary(language, 'a_to_z')}
                         </Button>
                         <Button variant={sort === 'distance' ? 'solid' : 'outline'} onPress={() => updateSort('distance')}>
                              {getTermFromDictionary(language, 'distance')}
                         </Button>
                    </Button.Group>
               </Box>
          );
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <ScrollView style={{ flex: 1 }}>
               {_.size(systemMessages) > 0 ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
               {getActionButtons()}
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <Box safeArea={5}>
                         <FlatList data={Object.keys(sortedLocations)} renderItem={({ item }) => <DisplayLocation data={sortedLocations[item]} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                    </Box>
               )}
          </ScrollView>
     );
};

const DisplayLocation = (data) => {
     const location = data.data;
     const { language } = React.useContext(LanguageContext);
     const key = 'location_' + location.locationId;

     let units = false;
     if (location.unit === 'Mi') {
          units = 'miles';
     } else if (location.unit === 'Km') {
          units = 'kilometers';
     }

     let distanceText = false;
     if (units && location.distance) {
          distanceText = location.distance + ' ' + units + ' away';
     }

     let isClosedToday = false;
     let hoursLabel = '';
     let hasHours = false;
     if (location.hours) {
          if (_.size(location.hours) > 0) {
               hasHours = true;
          }
          const day = moment().day();
          if (_.find(location.hours, _.matchesProperty('day', day))) {
               let todaysHours = _.filter(location.hours, { day: day });
               if (todaysHours[0]) {
                    todaysHours = todaysHours[0];
                    if (todaysHours.isClosed) {
                         isClosedToday = true;
                         hoursLabel = getTermFromDictionary(language, 'location_closed');
                    } else {
                         const closingText = todaysHours.close;
                         const time1 = closingText.split(':');
                         const openingText = todaysHours.open;
                         const time2 = openingText.split(':');
                         const closeTime = moment().set({ hour: time1[0], minute: time1[1] });
                         const openTime = moment().set({ hour: time2[0], minute: time2[1] });
                         const nowTime = moment();
                         const stillOpen = moment(nowTime).isBefore(closeTime);
                         const stillClosed = moment(openTime).isBefore(nowTime);
                         if (!stillOpen) {
                              isClosedToday = true;
                              hoursLabel = getTermFromDictionary(language, 'location_closed');
                         }
                         if (!stillClosed) {
                              isClosedToday = true;
                              let openingTime = moment(openTime).format('h:mm A');
                              hoursLabel = 'Closed until ' + openingTime;
                         } else {
                              isClosedToday = false;
                              let closingTime = moment(closeTime).format('h:mm A');
                              hoursLabel = 'Open until ' + closingTime;
                         }
                    }
               }
          } else {
               isClosedToday = true;
               hoursLabel = getTermFromDictionary(language, 'location_closed');
          }
     }

     const goToLocation = () => {
          navigate('Location', {
               data: location,
               title: location.displayName,
          });
     };

     console.log(key + ':' + location.locationImage);

     return (
          <>
               <Pressable onPress={goToLocation}>
                    <HStack justifyContent="space-between" alignItems="center">
                         {location.locationImage ? (
                              <Box width="30%" mr={2}>
                                   <Image alt={location.displayName} source={location.locationImage} style={{ width: '100%', height: 90, borderRadius: 4 }} placeholder={blurhash} transition={1000} contentFit="cover" />
                              </Box>
                         ) : null}
                         <VStack width={location.locationImage ? '60%' : '85%'}>
                              <Text bold>{location.displayName}</Text>
                              <Text fontSize="xs" mb={2}>
                                   {location.address}
                              </Text>
                              {hasHours ? (
                                   <HStack alignItems="center" space={1}>
                                        <Icon as={MaterialIcons} name="access-time" size="4" />
                                        <Text fontSize="xs">{hoursLabel}</Text>
                                   </HStack>
                              ) : null}
                              {distanceText ? (
                                   <HStack alignItems="center" space={1}>
                                        <Icon as={MaterialIcons} name="pin-drop" size="4" />
                                        <Text fontSize="xs">{distanceText}</Text>
                                   </HStack>
                              ) : null}
                         </VStack>
                         <Icon as={MaterialIcons} name="chevron-right" size="7" />
                    </HStack>
               </Pressable>
               <Divider mt={3} mb={3} />
          </>
     );
};