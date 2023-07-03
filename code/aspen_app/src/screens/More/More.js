import * as WebBrowser from 'expo-web-browser';
import { Box, Center, FlatList, HStack, Pressable, Text } from 'native-base';
import React from 'react';

// custom components and helper files
import { GLOBALS } from '../../util/globals';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';
import { LIBRARY } from '../../util/loadLibrary';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { navigate } from '../../helpers/RootNavigator';

export const MoreScreen = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);

     const defaultMenuItems = [
          {
               key: '0',
               title: getTermFromDictionary(language, 'contact'),
               path: 'Contact',
               external: false,
          },
          {
               key: '1',
               title: getTermFromDictionary(language, 'privacy_policy'),
               path: LIBRARY.appSettings.privacyPolicy ?? GLOBALS.privacyPolicy,
               external: true,
          },
     ];

     const openWebsite = async (url) => {
          WebBrowser.openBrowserAsync(url);
     };

     const onPressMenuItem = (item) => {
          navigate(item, { item });
     };

     const menuItem = (item) => {
          if (item.external) {
               return (
                    <Pressable
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         pl="4"
                         pr="5"
                         py="2"
                         onPress={() => {
                              openWebsite(item.path);
                         }}>
                         <HStack space={3}>
                              <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" bold fontSize={{ base: 'lg', lg: 'xl' }}>
                                   {item.title}
                              </Text>
                         </HStack>
                    </Pressable>
               );
          } else {
               return (
                    <Pressable
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         pl="4"
                         pr="5"
                         py="2"
                         onPress={() => {
                              onPressMenuItem(item.path);
                         }}>
                         <HStack>
                              <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" bold fontSize={{ base: 'lg', lg: 'xl' }}>
                                   {item.title}
                              </Text>
                         </HStack>
                    </Pressable>
               );
          }
     };

     return (
          <Box>
               <FlatList data={defaultMenuItems} renderItem={({ item }) => menuItem(item)} keyExtractor={(item, index) => index.toString()} />

               <Center mt={5}>
                    <Text mt={10} fontSize="xs" bold>
                         {getTermFromDictionary(language, 'app_name')}
                         <Text color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                              {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel}]
                         </Text>
                    </Text>
                    {library.discoveryVersion ? (
                         <Text fontSize="xs" bold>
                              {getTermFromDictionary(language, 'aspen_discovery')}
                              <Text color="coolGray.600" _dark={{ color: 'warmGray.400' }}>
                                   {library.discoveryVersion}
                              </Text>
                         </Text>
                    ) : null}
               </Center>
          </Box>
     );
};