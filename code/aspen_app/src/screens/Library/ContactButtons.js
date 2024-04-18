import { MaterialIcons } from '@expo/vector-icons';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Box, Button, Center, Icon, useColorModeValue, useToken } from 'native-base';
import React from 'react';
import { showLocation } from 'react-native-map-link';
import { popToast } from '../../components/loadError';
import { LanguageContext, LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

// custom components and helper files
import { PATRON } from '../../util/loadPatron';

const ContactButtons = (data) => {
     const { library } = React.useContext(LibrarySystemContext);
     const location = data.data;
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
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };

          if (location.homeLink === '/') {
               await WebBrowser.openBrowserAsync(location.baseUrl, browserParams)
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
                                   await WebBrowser.openBrowserAsync(location.baseUrl, browserParams)
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
          } else {
               await WebBrowser.openBrowserAsync(location.homeLink, browserParams)
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
                                   await WebBrowser.openBrowserAsync(location.homeLink, browserParams)
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