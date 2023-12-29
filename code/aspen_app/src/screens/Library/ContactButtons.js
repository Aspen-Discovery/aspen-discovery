import { MaterialIcons } from '@expo/vector-icons';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Box, Button, Center, Icon, useColorModeValue, useToken } from 'native-base';
import React from 'react';
import { showLocation } from 'react-native-map-link';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

// custom components and helper files
import { PATRON } from '../../util/loadPatron';

const ContactButtons = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);

     const backgroundColor = useToken('colors', useColorModeValue('warmGray.200', 'coolGray.900'));
     const textColor = useToken('colors', useColorModeValue('gray.800', 'coolGray.200'));

     const callLibrary = () => {
          /* location.phone */
          const phoneNumber = `tel:${location.phone}`;
          Linking.openURL(phoneNumber);
     };

     const emailLibrary = () => {
          /* location.email */
          const emailAddress = `mailto:${location.email}`;
          Linking.openURL(emailAddress);
     };

     const visitWebsite = async () => {
          /* location.homeLink */

          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'popover',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };

          if (location.homeLink === '/') {
               WebBrowser.openBrowserAsync(library.baseUrl, browserParams);
          } else {
               WebBrowser.openBrowserAsync(location.homeLink, browserParams);
          }
     };

     const getDirections = async () => {
          /* location.latitude & location.longitude */
          if (PATRON.coords.lat && PATRON.coords.long && PATRON.coords.lat !== 0 && PATRON.coords.long !== 0) {
               showLocation({
                    latitude: location.latitude,
                    longitude: location.longitude,
                    sourceLatitude: PATRON.coords.lat,
                    sourceLongitude: PATRON.coords.long,
                    googleForceLatLon: true,
               });
          } else {
               showLocation({
                    latitude: location.latitude,
                    longitude: location.longitude,
                    googleForceLatLon: true,
               });
          }
     };

     if (location.phone || location.email || location.homeLink || location.latitude !== 0) {
          return (
               <Box mb={4}>
                    <Button.Group flexWrap="wrap" size="sm" justifyContent="space-between" variant="outline">
                         {location.phone ? (
                              <Button
                                   width="23%"
                                   onPress={() => callLibrary()}
                                   _text={{
                                        padding: 0,
                                        textAlign: 'center',
                                        fontSize: 'xs',
                                        color: useColorModeValue('coolGray.600', 'warmGray.200'),
                                   }}
                                   style={{
                                        flex: 1,
                                        flexWrap: 'wrap',
                                        alignContent: 'center',
                                   }}>
                                   <Center>
                                        <Icon as={MaterialIcons} name="call" size="md" color="coolGray.600" _dark={{ color: 'warmGray.200' }} />
                                   </Center>
                                   {getTermFromDictionary(language, 'call_the_library')}
                              </Button>
                         ) : null}
                         {location.email ? (
                              <Button
                                   width="23%"
                                   onPress={() => emailLibrary()}
                                   _text={{
                                        padding: 0,
                                        textAlign: 'center',
                                        fontSize: 'xs',
                                        color: useColorModeValue('coolGray.600', 'warmGray.200'),
                                   }}
                                   style={{
                                        flex: 1,
                                        flexWrap: 'wrap',
                                        alignContent: 'center',
                                   }}>
                                   <Center>
                                        <Icon as={MaterialIcons} name="email" size="md" color="coolGray.600" _dark={{ color: 'warmGray.200' }} />
                                   </Center>
                                   {getTermFromDictionary(language, 'email_a_librarian')}
                              </Button>
                         ) : null}
                         {location.latitude !== 0 ? (
                              <Button
                                   width="23%"
                                   onPress={() => getDirections()}
                                   _text={{
                                        padding: 0,
                                        textAlign: 'center',
                                        fontSize: 'xs',
                                        color: useColorModeValue('coolGray.600', 'warmGray.200'),
                                   }}
                                   style={{
                                        flex: 1,
                                        flexWrap: 'wrap',
                                        alignContent: 'center',
                                   }}>
                                   <Center>
                                        <Icon as={MaterialIcons} name="map" size="md" color="coolGray.600" _dark={{ color: 'warmGray.200' }} />
                                   </Center>
                                   {getTermFromDictionary(language, 'get_directions')}
                              </Button>
                         ) : null}
                         {location.homeLink ? (
                              <Button
                                   width="23%"
                                   onPress={() => visitWebsite()}
                                   _text={{
                                        padding: 0,
                                        textAlign: 'center',
                                        fontSize: 'xs',
                                        color: useColorModeValue('coolGray.600', 'warmGray.200'),
                                   }}
                                   style={{
                                        flex: 1,
                                        flexWrap: 'wrap',
                                        alignContent: 'center',
                                   }}>
                                   <Center>
                                        <Icon as={MaterialIcons} name="home" size="md" color="coolGray.600" _dark={{ color: 'warmGray.200' }} />
                                   </Center>
                                   {getTermFromDictionary(language, 'visit_our_website')}
                              </Button>
                         ) : null}
                    </Button.Group>
               </Box>
          );
     }

     return null;
};

export default ContactButtons;