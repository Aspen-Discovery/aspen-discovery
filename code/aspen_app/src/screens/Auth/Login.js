import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import Constants from 'expo-constants';
import _ from 'lodash';
import { MaterialIcons } from '@expo/vector-icons';
import { Box, Button, Center, HStack, Icon, Image, Input, KeyboardAvoidingView, Pressable, Text, VStack, Modal, FlatList } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import * as Location from 'expo-location';
import * as SecureStore from 'expo-secure-store';

// custom components and helper files
import { GLOBALS } from '../../util/globals';
import { GetLoginForm } from './LoginForm';
import { LIBRARY } from '../../util/loadLibrary';
import { getLibraryLoginLabels } from '../../util/api/library';
import { getTermFromDictionary, getTranslation } from '../../translations/TranslationService';
import { PATRON } from '../../util/loadPatron';
import { PermissionsPrompt } from '../../components/PermissionsPrompt';
import { fetchAllLibrariesFromGreenhouse, fetchNearbyLibrariesFromGreenhouse } from '../../util/greenhouse';
import { SplashScreen } from './Splash';

export const LoginScreen = () => {
     const [isLoading, setIsLoading] = React.useState(true);
     const [permissionRequested, setPermissionRequested] = React.useState(false);
     const [shouldRequestPermissions, setShouldRequestPermissions] = React.useState(false);
     const [selectedLibrary, setSelectedLibrary] = React.useState(null);
     const [libraries, setLibraries] = React.useState([]);
     const [allLibraries, setAllLibraries] = React.useState([]);
     const [shouldShowSelectLibrary, setShowShouldSelectLibrary] = React.useState(true);
     const [usernameLabel, setUsernameLabel] = React.useState('Library Barcode');
     const [passwordLabel, setPasswordLabel] = React.useState('Password/PIN');
     const [showModal, setShowModal] = React.useState(false);
     const [query, setQuery] = React.useState('');
     let isCommunity = true;
     if (!_.includes(GLOBALS.slug, 'aspen-lida')) {
          isCommunity = false;
     }

     const logoImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;

     useFocusEffect(
          React.useCallback(() => {
               const bootstrapAsync = async () => {
                    await getPermissions().then((result) => {
                         console.log(result);
                         if (result.success === false && result.status === 'undetermined' && GLOBALS.releaseChannel !== 'DEV') {
                              setShouldRequestPermissions(true);
                         }
                         setPermissionRequested(true);
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

                    if (_.includes(GLOBALS.slug, 'aspen-lida')) {
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
          await getLibraryLoginLabels(data.libraryId, data.baseUrl).then(async (labels) => {
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
          });
          setShowModal(false);
     };

     if (isLoading) {
          return <SplashScreen />;
     }

     return (
          <Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
               <Image source={{ uri: logoImage }} rounded={25} size="xl" alt="" fallbackSource={require('../../themes/default/aspenLogo.png')} />
               {isCommunity || shouldShowSelectLibrary ? <SelectYourLibraryModal updateSelectedLibrary={updateSelectedLibrary} selectedLibrary={selectedLibrary} query={query} setQuery={setQuery} showModal={showModal} setShowModal={setShowModal} isCommunity={isCommunity} setShouldRequestPermissions={setShouldRequestPermissions} shouldRequestPermissions={shouldRequestPermissions} permissionRequested={permissionRequested} libraries={libraries} allLibraries={allLibraries} /> : null}
               <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'padding'} width="100%">
                    {selectedLibrary ? <GetLoginForm selectedLibrary={selectedLibrary} usernameLabel={usernameLabel} passwordLabel={passwordLabel} /> : null}
                    {isCommunity && Platform.OS !== 'android' ? (
                         <Button mt={8} size="xs" variant="ghost" colorScheme="secondary" startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5} />}>
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

const SelectYourLibraryModal = (payload) => {
     const { isCommunity, showModal, setShowModal, updateSelectedLibrary, selectedLibrary, shouldRequestPermissions, permissionRequested, libraries, allLibraries, setShouldRequestPermissions } = payload;
     const [query, setQuery] = React.useState('');

     function FilteredLibraries() {
          let haystack = libraries;

          if (!_.isEmpty(query)) {
               haystack = allLibraries;
          }

          if (!isCommunity) {
               haystack = libraries;
          }

          if (!isCommunity) {
               return _.filter(haystack, function (branch) {
                    return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
               });
          }

          return _.filter(haystack, function (branch) {
               return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1 || branch.librarySystem.toLowerCase().indexOf(query.toLowerCase()) > -1;
          });
     }

     if (shouldRequestPermissions && showModal) {
          return <PermissionsPrompt promptTitle="permissions_location_title" promptBody="permissions_location_body" setShouldRequestPermissions={setShouldRequestPermissions} />;
     }

     return (
          <Center>
               <Button onPress={() => setShowModal(true)} colorScheme="primary" m={5} size="md" startIcon={<Icon as={MaterialIcons} name="place" size={5} />}>
                    {selectedLibrary?.name ? selectedLibrary.name : getTermFromDictionary('en', 'select_your_library')}
               </Button>
               <Modal isOpen={showModal} size="lg" avoidKeyboard onClose={() => setShowModal(false)}>
                    <Modal.Content bg="white" _dark={{ bg: 'coolGray.800' }} maxH="350">
                         <Modal.CloseButton />
                         <Modal.Header>{getTermFromDictionary('en', 'find_your_library')}</Modal.Header>
                         <Box bg="white" _dark={{ bg: 'coolGray.800' }}>
                              <Input variant="filled" size="lg" autoCorrect={false} status="info" placeholder={getTermFromDictionary('en', 'search')} clearButtonMode="always" value={query} onChangeText={(text) => setQuery(text)} />
                         </Box>
                         <FlatList keyboardShouldPersistTaps="handled" keyExtractor={(item, index) => index.toString()} renderItem={({ item }) => <Item data={item} isCommunity={isCommunity} setShowModal={setShowModal} updateSelectedLibrary={updateSelectedLibrary} />} data={FilteredLibraries(libraries)} />
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

const Item = (data) => {
     const library = data.data;
     const libraryIcon = library.favicon;
     const { isCommunity, setShowModal, updateSelectedLibrary } = data;

     const handleSelect = () => {
          updateSelectedLibrary(library);
          setShowModal(false);
     };

     return (
          <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" onPress={handleSelect} pl="4" pr="5" py="2">
               <HStack space={3} alignItems="center">
                    <Image
                         key={library.name}
                         borderRadius={100}
                         source={{ uri: libraryIcon }}
                         fallbackSource={require('../../themes/default/aspenLogo.png')}
                         bg="warmGray.200"
                         _dark={{ bgColor: 'coolGray.800' }}
                         size={{
                              base: '25px',
                         }}
                         alt={library.name}
                    />
                    <VStack>
                         <Text
                              bold
                              fontSize={{
                                   base: 'sm',
                                   lg: 'md',
                              }}>
                              {library.name}
                         </Text>
                         {isCommunity ? (
                              <Text
                                   fontSize={{
                                        base: 'xs',
                                        lg: 'sm',
                                   }}>
                                   {library.librarySystem}
                              </Text>
                         ) : null}
                    </VStack>
               </HStack>
          </Pressable>
     );
};

async function getPermissions() {
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

     const location = await Location.getLastKnownPositionAsync({});

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