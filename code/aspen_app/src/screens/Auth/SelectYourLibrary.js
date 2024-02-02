import { MaterialCommunityIcons, MaterialIcons } from '@expo/vector-icons';
import _ from 'lodash';
import { Box, Button, Center, FlatList, HStack, Icon, Image, Input, Modal, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { PermissionsPrompt } from '../../components/PermissionsPrompt';

// custom components and helper files
import { getTermFromDictionary } from '../../translations/TranslationService';
import { PATRON } from '../../util/loadPatron';
import { useKeyboard } from '../../util/useKeyboard';

export const SelectYourLibrary = (payload) => {
     const isKeyboardOpen = useKeyboard();
     const { isCommunity, showModal, setShowModal, updateSelectedLibrary, selectedLibrary, shouldRequestPermissions, permissionRequested, libraries, allLibraries, setShouldRequestPermissions } = payload;
     const [query, setQuery] = React.useState('');

     function FilteredLibraries() {
          let haystack = [];

          // we were able to get coordinates from the device
          if (PATRON.coords.lat !== 0 && PATRON.coords.long !== 0) {
               haystack = libraries;
          }

          if (!_.isEmpty(query) && query !== ' ') {
               haystack = allLibraries;

               if (!isCommunity) {
                    haystack = libraries;
               }
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

     const updateStatus = async () => {};

     if (shouldRequestPermissions && showModal) {
          return <PermissionsPrompt promptTitle="permissions_location_title" promptBody="permissions_location_body" setShouldRequestPermissions={setShouldRequestPermissions} updateStatus={updateStatus} />;
     }

     const clearSearch = () => {
          setQuery('');
     };

     return (
          <Center>
               <Button onPress={() => setShowModal(true)} colorScheme="primary" m={5} size="md" startIcon={<Icon as={MaterialIcons} name="place" size={5} />}>
                    {selectedLibrary?.name ? selectedLibrary.name : getTermFromDictionary('en', 'select_your_library')}
               </Button>
               <Modal isOpen={showModal} size="lg" avoidKeyboard onClose={() => setShowModal(false)} pb={Platform.OS === 'android' && isKeyboardOpen ? '50%' : '0'}>
                    <Modal.Content bg="warmGray.50" _dark={{ bg: 'coolGray.800' }} maxH="350">
                         <Modal.CloseButton />
                         <Modal.Header>{getTermFromDictionary('en', 'find_your_library')}</Modal.Header>
                         <Box bg="warmGray.50" _dark={{ bg: 'coolGray.800' }} p={2} pb={query ? 0 : 5}>
                              <Input
                                   variant="filled"
                                   size="lg"
                                   autoCorrect={false}
                                   status="info"
                                   placeholder={getTermFromDictionary('en', 'search')}
                                   value={query}
                                   onChangeText={(text) => setQuery(text)}
                                   InputRightElement={
                                        query ? (
                                             <Pressable onPress={() => clearSearch()}>
                                                  <Icon as={MaterialCommunityIcons} name="close-circle" size={5} mr="2" />
                                             </Pressable>
                                        ) : null
                                   }
                              />
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
                    {libraryIcon ? (
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
                    ) : (
                         <Box
                              borderRadius={100}
                              bg="warmGray.200"
                              _dark={{ bgColor: 'coolGray.800' }}
                              size={{
                                   base: '25px',
                              }}
                         />
                    )}
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