import { MaterialIcons } from '@expo/vector-icons';
import * as Linking from 'expo-linking';
import * as SecureStore from 'expo-secure-store';
import * as WebBrowser from 'expo-web-browser';
import { Box, Button, Center, Icon, Heading, Text, Divider } from 'native-base';
import React, { Component } from 'react';
import { showLocation } from 'react-native-map-link';
import { useNavigation } from '@react-navigation/native';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { translate } from '../../translations/translations';
import HoursAndLocation from './HoursAndLocation';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { PATRON } from '../../util/loadPatron';

export const ContactLibrary = () => {
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);

     const [userLat, setUserLat] = React.useState(0);
     const [userLon, setUserLon] = React.useState(0);

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

     const setUserCoordinates = () => {
          if (PATRON.coords.lat && PATRON.coords.long) {
               setUserLat(PATRON.coords.lat);
               setUserLon(PATRON.coords.long);
          }
     };

     const getDirections = async (locationLatitude, locationLongitude) => {
          setUserCoordinates();
          showLocation({
               latitude: locationLatitude,
               longitude: locationLongitude,
               sourceLatitude: userLat,
               sourceLongitude: userLon,
               googleForceLatLon: true,
          });
     };

     return (
          <Box safeArea={5}>
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
                                   {translate('library_contact.call_button')}
                              </Button>
                         ) : null}
                         {location.email ? (
                              <Button
                                   mb={3}
                                   onPress={() => {
                                        sendEmail(location.email);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="email" size="sm" />}>
                                   {translate('library_contact.email_button')}
                              </Button>
                         ) : null}
                         {location.latitude !== 0 ? (
                              <Button
                                   mb={3}
                                   onPress={() => {
                                        getDirections(location.latitude, location.longitude);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="map" size="sm" />}>
                                   {translate('library_contact.directions_button')}
                              </Button>
                         ) : null}
                         {location.homeLink ? (
                              <Button
                                   onPress={() => {
                                        openWebsite(location.homeLink, library.baseUrl);
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="home" size="sm" />}>
                                   {translate('library_contact.website_button')}
                              </Button>
                         ) : null}
                    </Box>
               </Center>
          </Box>
     );
};

/*
 export class Contact extends Component {
 constructor() {
 super();
 this.state = {
 isLoading: true,
 hasError: false,
 error: null,
 userLatitude: 0,
 userLongitude: 0,
 };
 }

 componentDidMount = async () => {
 this.setState({
 userLatitude: await SecureStore.getItemAsync("latitude"),
 userLongitude: await SecureStore.getItemAsync("longitude"),
 isLoading: false,
 });
 };

 dialCall = (number) => {
 let phoneNumber = "";
 phoneNumber = `tel:${number}`;
 Linking.openURL(phoneNumber);
 };

 sendEmail = (email) => {
 let emailAddress = "";
 emailAddress = `mailto:${email}`;
 Linking.openURL(emailAddress);
 };

 openWebsite = async (url, libraryUrl) => {
 if (url === "/") {
 WebBrowser.openBrowserAsync(libraryUrl);
 } else {
 WebBrowser.openBrowserAsync(url);
 }
 };

 getDirections = async (locationLatitude, locationLongitude) => {
 showLocation({
 latitude: locationLatitude,
 longitude: locationLongitude,
 sourceLatitude: this.state.userLatitude,
 sourceLongitude: this.state.userLongitude,
 googleForceLatLon: true,
 });
 };

 static contextType = userContext;

 render() {
 const location = this.context.location;
 const library = this.context.library;

 if (this.state.isLoading) {
 return loadingSpinner();
 }

 return (
 <Box safeArea={5}>
 <Center>
 <Heading mb={1}>{library.displayName}</Heading>
 {library.displayName != location.displayName ? (
 <Text mb={2}>{location.displayName}</Text>
 ) : null}
 <Divider mb={2} />
 {location.showInLocationsAndHoursList === "1" ? (
 <HoursAndLocation
 hoursMessage={location.hoursMessage}
 hours={location.hours}
 description={location.description}
 />
 ) : null}
 <Box>
 {location.phone ? (
 <Button
 mb={3}
 onPress={() => {
 this.dialCall(location.phone);
 }}
 startIcon={<Icon as={MaterialIcons} name="call" size="sm" />}
 >
 {translate("library_contact.call_button")}
 </Button>
 ) : null}
 {location.email ? (
 <Button
 mb={3}
 onPress={() => {
 this.sendEmail(location.email);
 }}
 startIcon={<Icon as={MaterialIcons} name="email" size="sm" />}
 >
 {translate("library_contact.email_button")}
 </Button>
 ) : null}
 {location.latitude !== 0 ? (
 <Button
 mb={3}
 onPress={() => {
 this.getDirections(location.latitude, location.longitude);
 }}
 startIcon={<Icon as={MaterialIcons} name="map" size="sm" />}
 >
 {translate("library_contact.directions_button")}
 </Button>
 ) : null}
 {location.homeLink ? (
 <Button
 onPress={() => {
 this.openWebsite(location.homeLink, library.baseUrl);
 }}
 startIcon={<Icon as={MaterialIcons} name="home" size="sm" />}
 >
 {translate("library_contact.website_button")}
 </Button>
 ) : null}
 </Box>
 </Center>
 </Box>
 );
 }
 }*/