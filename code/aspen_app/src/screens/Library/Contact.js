import { MaterialIcons } from '@expo/vector-icons';
import * as Linking from 'expo-linking';
import { useQueryClient } from '@tanstack/react-query';
import * as WebBrowser from 'expo-web-browser';
import { Box, Button, Center, Icon, Heading, Text, Divider } from 'native-base';
import React, { Component } from 'react';
import { showLocation } from 'react-native-map-link';

// custom components and helper files
import HoursAndLocation from './HoursAndLocation';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { PATRON } from '../../util/loadPatron';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { GLOBALS } from '../../util/globals';
import { PermissionsPrompt } from '../../components/PermissionsPrompt';
import _ from 'lodash';
import { DisplaySystemMessage } from '../../components/Notifications';

export const ContactLibrary = () => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const dialCall = (number) => {
          const phoneNumber = `tel:${number}`;
          Linking.openURL(phoneNumber);
     };

     const sendEmail = (email) => {
          const emailAddress = `mailto:${email}`;
          Linking.openURL(emailAddress);
     };

     const openWebsite = async (url, libraryUrl) => {
          if (url === '/') {
               WebBrowser.openBrowserAsync(libraryUrl);
          } else {
               WebBrowser.openBrowserAsync(url);
          }
     };

     const getDirections = async (locationLatitude, locationLongitude) => {
          if (PATRON.coords.lat && PATRON.coords.long && PATRON.coords.lat !== 0 && PATRON.coords.long !== 0) {
               showLocation({
                    latitude: locationLatitude,
                    longitude: locationLongitude,
                    sourceLatitude: PATRON.coords.lat,
                    sourceLongitude: PATRON.coords.long,
                    googleForceLatLon: true,
               });
          } else {
               showLocation({
                    latitude: locationLatitude,
                    longitude: locationLongitude,
                    googleForceLatLon: true,
               });
          }
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
          <Box safeArea={5}>
               {showSystemMessage()}
               <Center>
                    <Heading mb={1}>{library.displayName}</Heading>
                    {library.displayName !== location.displayName ? <Text mb={2}>{location.displayName}</Text> : null}
                    <Divider mb={2} />
                    {location.showInLocationsAndHoursList === '1' ? <HoursAndLocation hoursMessage={location.hoursMessage} hours={location.hours} description={location.description} /> : null}
                    <Box>
                         {location.phone ? (
                              <Button
                                   mb={3}
                                   onPress={() => {
                                        dialCall(location.phone);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="call" size="sm" />}>
                                   {getTermFromDictionary(language, 'call_the_library')}
                              </Button>
                         ) : null}
                         {location.email ? (
                              <Button
                                   mb={3}
                                   onPress={() => {
                                        sendEmail(location.email);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="email" size="sm" />}>
                                   {getTermFromDictionary(language, 'email_a_librarian')}
                              </Button>
                         ) : null}
                         {location.latitude !== 0 ? (
                              <Button
                                   mb={3}
                                   onPress={() => {
                                        getDirections(location.latitude, location.longitude);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="map" size="sm" />}>
                                   {getTermFromDictionary(language, 'get_directions')}
                              </Button>
                         ) : null}
                         {location.homeLink ? (
                              <Button
                                   onPress={() => {
                                        openWebsite(location.homeLink, library.baseUrl);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="home" size="sm" />}>
                                   {getTermFromDictionary(language, 'visit_our_website')}
                              </Button>
                         ) : null}
                    </Box>
               </Center>
          </Box>
     );
};