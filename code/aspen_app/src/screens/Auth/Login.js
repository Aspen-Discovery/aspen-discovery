import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import Constants from 'expo-constants';
import * as Location from 'expo-location';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import { Box, Button, Center, Icon, Image, KeyboardAvoidingView, Text } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getLibraryInfo } from '../../util/api/library';

// custom components and helper files
import { GLOBALS } from '../../util/globals';
import { fetchAllLibrariesFromGreenhouse, fetchNearbyLibrariesFromGreenhouse } from '../../util/greenhouse';
import { LIBRARY } from '../../util/loadLibrary';
import { PATRON } from '../../util/loadPatron';
import { ForgotBarcode } from './ForgotBarcode';
import { GetLoginForm } from './LoginForm';
import { ResetPassword } from './ResetPassword';
import { SelectYourLibrary } from './SelectYourLibrary';
import { SplashScreen } from './Splash';

export const LoginScreen = () => {
     const [isLoading, setIsLoading] = React.useState(true);
     const [permissionRequested, setPermissionRequested] = React.useState(false);
     const [shouldRequestPermissions, setShouldRequestPermissions] = React.useState(false);
     const [permissionStatus, setPermissionStatus] = React.useState(null);
     const [selectedLibrary, setSelectedLibrary] = React.useState(null);
     const [libraries, setLibraries] = React.useState([]);
     const [allLibraries, setAllLibraries] = React.useState([]);
     const [shouldShowSelectLibrary, setShowShouldSelectLibrary] = React.useState(true);
     const [usernameLabel, setUsernameLabel] = React.useState('Library Barcode');
     const [passwordLabel, setPasswordLabel] = React.useState('Password/PIN');
     const [showModal, setShowModal] = React.useState(false);
     const [query, setQuery] = React.useState('');
     const [allowBarcodeScanner, setAllowBarcodeScanner] = React.useState(false);
     const [allowCode39, setAllowCode39] = React.useState(false);
     const [enableForgotPasswordLink, setEnableForgotPasswordLink] = React.useState(false);
     const [enableForgotBarcode, setEnableForgotBarcode] = React.useState(false);
     const [forgotPasswordType, setForgotPasswordType] = React.useState(false);
     const [showForgotPasswordModal, setShowForgotPasswordModal] = React.useState(false);
     const [showForgotBarcodeModal, setShowForgotBarcodeModal] = React.useState(false);
     const [ils, setIls] = React.useState('koha');
     const { updateLibrary } = React.useContext(LibrarySystemContext);
     let isCommunity = true;
     if (!_.includes(GLOBALS.slug, 'aspen-lida') || GLOBALS.slug === 'aspen-lida-bws') {
          isCommunity = false;
     }

     const logoImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;

     useFocusEffect(
          React.useCallback(() => {
               const bootstrapAsync = async () => {
                    await getPermissions('statusCheck').then(async (result) => {
                         if (result.success === false && result.status === 'undetermined' && GLOBALS.releaseChannel !== 'DEV' && Platform.OS === 'android') {
                              setShouldRequestPermissions(true);
                              setPermissionStatus(result.status);
                         }

                         if (result.status !== 'granted' && Platform.OS === 'ios') {
                              setPermissionRequested(true);
                              setPermissionStatus(result.status);
                              await getPermissions('request');
                         }
                    });

                    await fetchNearbyLibrariesFromGreenhouse().then((result) => {
                         if (result.success) {
                              setLibraries(result.libraries);
                              setShowShouldSelectLibrary(result.shouldShowSelectLibrary);
                              if (!result.shouldShowSelectLibrary) {
                                   updateSelectedLibrary(result.libraries[0]);
                              }
                         }
                    });

                    if (_.includes(GLOBALS.slug, 'aspen-lida') && GLOBALS.slug !== 'aspen-lida-bws') {
                         await fetchAllLibrariesFromGreenhouse().then((result) => {
                              if (result.success) {
                                   setAllLibraries(result.libraries);
                              }
                         });
                    }

                    setIsLoading(false);
               };
               bootstrapAsync().then(() => {
                    return () => bootstrapAsync();
               });
          }, [])
     );

     const updateSelectedLibrary = async (data) => {
          setSelectedLibrary(data);
          LIBRARY.url = data.baseUrl; // used in some cases before library context is set
          await getLibraryInfo(data.baseUrl, data.libraryId).then(async (result) => {
               if (_.isObject(result)) {
                    updateLibrary(result);
                    if (result.barcodeStyle) {
                         setAllowBarcodeScanner(true);
                         if (result.barcodeStyle === 'CODE39') {
                              setAllowCode39(true);
                         }
                    } else {
                         setAllowBarcodeScanner(false);
                    }

                    if (result.usernameLabel) {
                         setUsernameLabel(result.usernameLabel);
                    }

                    if (result.passwordLabel) {
                         setPasswordLabel(result.passwordLabel);
                    }

                    if (result.enableForgotPasswordLink) {
                         setEnableForgotPasswordLink(result.enableForgotPasswordLink);
                    }

                    if (result.enableForgotBarcode) {
                         setEnableForgotBarcode(result.enableForgotBarcode);
                    }

                    if (result.forgotPasswordType) {
                         setForgotPasswordType(result.forgotPasswordType);
                    }

                    if (result.ils) {
                         setIls(result.ils);
                    }
               }
          });
          /*await getLibraryLoginLabels(data.libraryId, data.baseUrl).then(async (labels) => {
		 try {
		 const username = await getTranslation('Your Name', 'en', data.baseUrl);
		 const password = await getTranslation('Library Card Number', 'en', data.baseUrl);
		 if (username !== 'Your Name') {
		 setUsernameLabel(username);
		 }
		 if (password !== 'Library Card Number') {
		 setPasswordLabel(password);
		 }
		 } catch (e) {
		 // couldn't fetch translated login terms for some reason, just use the default as backup
		 }
		 });*/
          setShowModal(false);
     };

     if (isLoading) {
          return <SplashScreen />;
     }

     return (
          <Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
               <Image source={{ uri: logoImage }} rounded={25} size="xl" alt="" fallbackSource={require('../../themes/default/aspenLogo.png')} />
               {isCommunity || shouldShowSelectLibrary ? <SelectYourLibrary updateSelectedLibrary={updateSelectedLibrary} selectedLibrary={selectedLibrary} query={query} setQuery={setQuery} showModal={showModal} setShowModal={setShowModal} isCommunity={isCommunity} setShouldRequestPermissions={setShouldRequestPermissions} shouldRequestPermissions={shouldRequestPermissions} permissionRequested={permissionRequested} libraries={libraries} allLibraries={allLibraries} /> : null}
               <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'padding'} width="100%">
                    {selectedLibrary ? <GetLoginForm selectedLibrary={selectedLibrary} usernameLabel={usernameLabel} passwordLabel={passwordLabel} allowBarcodeScanner={allowBarcodeScanner} allowCode39={allowCode39} /> : null}
                    <Button.Group space={3} justifyContent="center" pt={5}>
                         {enableForgotPasswordLink === '1' || enableForgotPasswordLink === 1 ? <ResetPassword ils={ils} enableForgotPasswordLink={enableForgotPasswordLink} usernameLabel={usernameLabel} passwordLabel={passwordLabel} forgotPasswordType={forgotPasswordType} showForgotPasswordModal={showForgotPasswordModal} setShowForgotPasswordModal={setShowForgotPasswordModal} /> : null}
                         {enableForgotBarcode === '1' || enableForgotBarcode === 1 ? <ForgotBarcode usernameLabel={usernameLabel} showForgotBarcodeModal={showForgotBarcodeModal} setShowForgotBarcodeModal={setShowForgotBarcodeModal} /> : null}
                    </Button.Group>
                    {isCommunity && Platform.OS !== 'android' ? (
                         <Button mt={5} size="xs" variant="ghost" colorScheme="tertiary" startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5} />}>
                              {getTermFromDictionary('en', 'reset_geolocation')}
                         </Button>
                    ) : null}
                    <Center>
                         <Text mt={5} fontSize="xs" color="coolGray.600">
                              {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel ?? 'Development'}]
                         </Text>
                    </Center>
               </KeyboardAvoidingView>
          </Box>
     );
};

async function getPermissions(kind = 'statusCheck') {
     if (kind === 'statusCheck') {
          const { status } = await Location.getForegroundPermissionsAsync();
          if (status !== 'granted') {
               await SecureStore.setItemAsync('latitude', '0');
               await SecureStore.setItemAsync('longitude', '0');
               PATRON.coords.lat = 0;
               PATRON.coords.long = 0;
               return {
                    success: false,
                    status: status,
               };
          }
     } else {
          const { status } = await Location.requestForegroundPermissionsAsync();
          if (status !== 'granted') {
               await SecureStore.setItemAsync('latitude', '0');
               await SecureStore.setItemAsync('longitude', '0');
               PATRON.coords.lat = 0;
               PATRON.coords.long = 0;
               return {
                    success: false,
                    status: status,
               };
          }

          let location = await Location.getLastKnownPositionAsync({});

          if (location != null) {
               const latitude = JSON.stringify(location.coords.latitude);
               const longitude = JSON.stringify(location.coords.longitude);
               await SecureStore.setItemAsync('latitude', latitude);
               await SecureStore.setItemAsync('longitude', longitude);
               PATRON.coords.lat = latitude;
               PATRON.coords.long = longitude;
          } else {
               await SecureStore.setItemAsync('latitude', '0');
               await SecureStore.setItemAsync('longitude', '0');
               PATRON.coords.lat = 0;
               PATRON.coords.long = 0;
          }
          return {
               success: true,
               status: 'granted',
          };
     }

     return {
          success: false,
     };
}