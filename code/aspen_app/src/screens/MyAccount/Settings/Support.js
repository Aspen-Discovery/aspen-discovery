import * as Device from 'expo-device';
import * as Linking from 'expo-linking';
import _ from 'lodash';
import { Alert, Box, Center, HStack, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { checkVersion } from 'react-native-check-version';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
// custom components and helper files
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { GLOBALS } from '../../../util/globals';

export const SupportScreen = () => {
     const { accounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const [status, setStatus] = React.useState({
          needsUpdate: false,
          url: null,
          latest: GLOBALS.appVersion,
          canOpenUrl: false,
     });

     const numLinkedAccounts = _.size(accounts) ?? 0;

     React.useEffect(() => {
          (async () => {
               let tmp = await checkStoreVersion();
               if (tmp.url) {
                    if (Linking.canOpenURL(tmp.url)) {
                         tmp = _.set(tmp, 'canOpenUrl', true);
                    }
               }
               setStatus(tmp);
          })();
     }, []);

     const openAppStore = () => {
          const supported = Linking.canOpenURL(status.url);
          if (supported) {
               Linking.openURL(status.url);
          } else {
               console.log(supported);
          }
     };

     return (
          <Box safeArea={5}>
               <VStack space={1}>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'app_name')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel}]
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'aspen_discovery')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {library.discoveryVersion}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'os_information')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {Device.osName} {Device.osVersion}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'device_information')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {Device.brand} {Device.modelName}, {Device.deviceYearClass}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'current_location')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {location.displayName}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'current_library')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {library.displayName}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'connected_to')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {library.baseUrl}
                         </Text>
                    </HStack>
                    <HStack justifyContent="space-between">
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'num_linked_accounts')}
                         </Text>
                         <Text fontSize="xs" color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {numLinkedAccounts}
                         </Text>
                    </HStack>
               </VStack>
               {status.needsUpdate ? (
                    <Center mt={5}>
                         <Alert variant="left-accent" w="100%" status="warning">
                              <VStack space={2} flexShrink={1} w="100%">
                                   <HStack flexShrink={1} space={2} alignItems="center" justifyContent="space-between">
                                        <HStack flexShrink={1} space={2} alignItems="center">
                                             <Alert.Icon />
                                             <Text fontSize="md" fontWeight="medium" color="coolGray.800">
                                                  {status.latest} Is Available
                                             </Text>
                                        </HStack>
                                   </HStack>
                                   <Box
                                        pl="6"
                                        _text={{
                                             color: 'coolGray.600',
                                        }}>
                                        Please update your app for the latest features and fixes.
                                        {status.canOpenUrl ? (
                                             <Pressable mt={3} variant="ghost" onPress={() => openAppStore(status.url)}>
                                                  <Text bold>Update now</Text>
                                             </Pressable>
                                        ) : null}
                                   </Box>
                              </VStack>
                         </Alert>
                    </Center>
               ) : null}
          </Box>
     );
};

async function checkStoreVersion() {
     try {
          const version = await checkVersion({
               bundleId: GLOBALS.bundleId,
               currentVersion: GLOBALS.appVersion,
          });
          if (version.needsUpdate) {
               let url = (url = GLOBALS.iosStoreUrl);
               if (Platform.OS === 'android') {
                    url = GLOBALS.androidStoreUrl;
               }
               return {
                    needsUpdate: true,
                    url: url,
                    latest: version.version,
               };
          }
     } catch (e) {
          console.log(e);
     }

     return {
          needsUpdate: false,
          url: null,
          latest: GLOBALS.appVersion,
     };
}