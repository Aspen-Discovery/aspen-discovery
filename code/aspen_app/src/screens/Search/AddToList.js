import { MaterialIcons } from '@expo/vector-icons';
import { useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { Box, Button, Center, CloseIcon, FormControl, HStack, Icon, Input, Pressable, Radio, Select, Stack, Text, TextArea, VStack } from 'native-base';
import React, { useState } from 'react';
import { Platform } from 'react-native';
import Modal from 'react-native-modal';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { addTitlesToList, createListFromTitle } from '../../util/api/list';

import { PATRON } from '../../util/loadPatron';

export const AddToList = (props) => {
     const item = props.itemId;
     const btnStyle = props.btnStyle;
     const source = props.source ?? 'GroupedWork';
     const btnWidth = props.btnWidth ?? 'auto';
     const [open, setOpen] = React.useState(false);
     const [screen, setScreen] = React.useState('add-new');
     const [loading, setLoading] = React.useState(false);
     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const lists = PATRON.lists;
     const [listId, setListId] = useState();
     const [description, saveDescription] = useState();
     const [title, saveTitle] = useState();
     const [isPublic, saveIsPublic] = useState();
     const queryClient = useQueryClient();

     const toggleModal = () => {
          setOpen(!open);
          if (!open === true) {
               setListId(PATRON.listLastUsed);
          }
     };

     const updateLastListUsed = async (id) => {
          queryClient.invalidateQueries({ queryKey: ['list', id] });
          queryClient.invalidateQueries({ queryKey: ['lists', user.id, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          PATRON.listLastUsed = id;
          setListId(id);
     };

     const SelectLists = () => {
          return (
               <Select
                    isReadOnly={Platform.OS === 'android'}
                    selectedValue={listId}
                    onValueChange={(itemValue) => {
                         setListId(itemValue);
                    }}
                    _actionSheet={{
                         useRNModal: Platform.OS === 'ios',
                    }}>
                    {_.map(lists, function (item, index, array) {
                         return <Select.Item key={index} value={item.id} label={item.title} />;
                    })}
               </Select>
          );
     };

     const LargeButton = () => {
          return (
               <Center>
                    <Button mt={3} onPress={toggleModal} colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="sm" />}>
                         {getTermFromDictionary(language, 'add_to_list')}
                    </Button>
               </Center>
          );
     };

     const SmallButton = () => {
          return (
               <Button size="sm" variant="ghost" colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="xs" mr="-1" />} onPress={toggleModal} style={{ flex: 1, flexWrap: 'wrap' }}>
                    {getTermFromDictionary(language, 'add_to_list')}
               </Button>
          );
     };

     const RegularButton = () => {
          return (
               <Button width={btnWidth} onPress={toggleModal}>
                    {getTermFromDictionary(language, 'add_to_list')}
               </Button>
          );
     };

     return (
          <>
               <Modal
                    isVisible={open}
                    avoidKeyboard={true}
                    onBackdropPress={() => {
                         setOpen(false);
                         setScreen('add-new');
                    }}>
                    <Box
                         bgColor="muted.50"
                         rounded="md"
                         p={1}
                         _text={{ color: 'text.900' }}
                         _dark={{
                              bg: 'muted.800',
                              _text: { color: 'text.50' },
                         }}>
                         <VStack space={3}>
                              {screen === 'add-new' && !_.isEmpty(lists) ? (
                                   <>
                                        <HStack
                                             p={4}
                                             borderBottomWidth="1"
                                             bg="muted.50"
                                             justifyContent="space-between"
                                             alignItems="flex-start"
                                             borderColor="muted.300"
                                             _dark={{
                                                  bg: 'muted.800',
                                                  borderColor: 'muted.700',
                                             }}>
                                             <Box
                                                  _text={{
                                                       color: 'text.900',
                                                       fontSize: 'md',
                                                       fontWeight: 'semibold',
                                                       lineHeight: 'sm',
                                                  }}
                                                  _dark={{
                                                       _text: { color: 'text.50' },
                                                  }}>
                                                  {getTermFromDictionary(language, 'add_to_list')}
                                             </Box>
                                             <Pressable onPress={() => setOpen(false)}>
                                                  <CloseIcon
                                                       zIndex="1"
                                                       colorScheme="coolGray"
                                                       p="2"
                                                       bg="transparent"
                                                       borderRadius="sm"
                                                       _icon={{
                                                            color: 'muted.500',
                                                            size: '4',
                                                       }}
                                                       _dark={{
                                                            _icon: { color: 'muted.400' },
                                                            _hover: { bg: 'muted.700' },
                                                            _pressed: { bg: 'muted.600' },
                                                       }}
                                                  />
                                             </Pressable>
                                        </HStack>
                                        <Box p={4} _text={{ color: 'text.900' }} _hover={{ bg: 'muted.200' }} _pressed={{ bg: 'muted.300' }} _dark={{ _text: { color: 'text.50' } }}>
                                             <FormControl>
                                                  <VStack space={4}>
                                                       <Box>
                                                            <FormControl.Label>{getTermFromDictionary(language, 'choose_a_list')}</FormControl.Label>
                                                            <SelectLists />
                                                       </Box>
                                                       <HStack space={2} alignItems="center">
                                                            <Text>{getTermFromDictionary(language, 'or')}</Text>
                                                            <Button
                                                                 size="sm"
                                                                 onPress={() => {
                                                                      setScreen('create-new');
                                                                 }}>
                                                                 {getTermFromDictionary(language, 'create_new_list')}
                                                            </Button>
                                                       </HStack>
                                                  </VStack>
                                             </FormControl>
                                        </Box>

                                        <Button.Group
                                             p={4}
                                             flexDirection="row"
                                             justifyContent="flex-end"
                                             flexWrap="wrap"
                                             bg="muted.50"
                                             borderColor="muted.300"
                                             borderTopWidth="1"
                                             _dark={{
                                                  bg: 'muted.800',
                                                  borderColor: 'muted.700',
                                             }}>
                                             <Button
                                                  variant="outline"
                                                  onPress={() => {
                                                       setOpen(false);
                                                       setScreen('add-new');
                                                  }}>
                                                  {getTermFromDictionary(language, 'cancel')}
                                             </Button>
                                             {!_.isEmpty(lists) ? (
                                                  <Button
                                                       isLoading={loading}
                                                       onPress={() => {
                                                            setLoading(true);
                                                            addTitlesToList(listId, item, library.baseUrl, source, language).then((res) => {
                                                                 updateLastListUsed(listId);
                                                                 queryClient.invalidateQueries({ queryKey: ['list', listId] });
                                                                 setLoading(false);
                                                                 setOpen(false);
                                                            });
                                                       }}>
                                                       {getTermFromDictionary(language, 'save_to_list')}
                                                  </Button>
                                             ) : (
                                                  <Button>{getTermFromDictionary(language, 'create_new_list')}</Button>
                                             )}
                                        </Button.Group>
                                   </>
                              ) : (
                                   <>
                                        <HStack
                                             justifyContent="space-between"
                                             alignItems="flex-start"
                                             p={4}
                                             borderBottomWidth="1"
                                             bg="muted.50"
                                             borderColor="muted.300"
                                             _dark={{
                                                  bg: 'muted.800',
                                                  borderColor: 'muted.700',
                                             }}>
                                             <Box
                                                  _text={{
                                                       color: 'text.900',
                                                       fontSize: 'md',
                                                       fontWeight: 'semibold',
                                                       lineHeight: 'sm',
                                                  }}
                                                  _dark={{
                                                       _text: { color: 'text.50' },
                                                  }}>
                                                  {getTermFromDictionary(language, 'create_new_list_item')}
                                             </Box>
                                             <Pressable onPress={() => setOpen(false)}>
                                                  <CloseIcon
                                                       zIndex="1"
                                                       colorScheme="coolGray"
                                                       p="2"
                                                       bg="transparent"
                                                       borderRadius="sm"
                                                       _icon={{
                                                            color: 'muted.500',
                                                            size: '4',
                                                       }}
                                                       _dark={{
                                                            _icon: { color: 'muted.400' },
                                                            _hover: { bg: 'muted.700' },
                                                            _pressed: { bg: 'muted.600' },
                                                       }}
                                                  />
                                             </Pressable>
                                        </HStack>
                                        <Box p={4} _text={{ color: 'text.900' }} _dark={{ _text: { color: 'text.50' } }}>
                                             <VStack space={4}>
                                                  <FormControl>
                                                       <FormControl.Label>{getTermFromDictionary(language, 'title')}</FormControl.Label>
                                                       <Input id="title" onChangeText={(text) => saveTitle(text)} returnKeyType="next" />
                                                  </FormControl>
                                                  <FormControl>
                                                       <FormControl.Label>{getTermFromDictionary(language, 'description')}</FormControl.Label>
                                                       <TextArea id="description" onChangeText={(text) => saveDescription(text)} returnKeyType="next" />
                                                  </FormControl>
                                                  <FormControl>
                                                       <FormControl.Label>{getTermFromDictionary(language, 'access')}</FormControl.Label>
                                                       <Radio.Group
                                                            defaultValue="1"
                                                            onChange={(nextValue) => {
                                                                 saveIsPublic(nextValue);
                                                            }}>
                                                            <Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px">
                                                                 <Radio value="1" my={1}>
                                                                      {getTermFromDictionary(language, 'private')}
                                                                 </Radio>
                                                                 <Radio value="0" my={1}>
                                                                      {getTermFromDictionary(language, 'public')}
                                                                 </Radio>
                                                            </Stack>
                                                       </Radio.Group>
                                                  </FormControl>
                                             </VStack>
                                        </Box>
                                        <Button.Group
                                             p={4}
                                             flexDirection="row"
                                             justifyContent="flex-end"
                                             flexWrap="wrap"
                                             bg="muted.50"
                                             borderColor="muted.300"
                                             borderTopWidth="1"
                                             _dark={{
                                                  bg: 'muted.800',
                                                  borderColor: 'muted.700',
                                             }}>
                                             <Button
                                                  variant="outline"
                                                  onPress={() => {
                                                       setOpen(false);
                                                       setScreen('add-new');
                                                  }}>
                                                  {getTermFromDictionary(language, 'cancel')}
                                             </Button>
                                             <Button
                                                  isLoading={loading}
                                                  isLoadingText={getTermFromDictionary(language, 'saving', true)}
                                                  onPress={() => {
                                                       setLoading(true);
                                                       createListFromTitle(title, description, isPublic, item, library.baseUrl, source).then((res) => {
                                                            updateLastListUsed(res.listId);
                                                            setOpen(false);
                                                            setLoading(false);
                                                            setScreen('add-new');
                                                       });
                                                  }}>
                                                  {getTermFromDictionary(language, 'create_list')}
                                             </Button>
                                        </Button.Group>
                                   </>
                              )}
                         </VStack>
                    </Box>
               </Modal>
               {btnStyle === 'lg' ? LargeButton() : btnStyle === 'reg' ? RegularButton() : SmallButton()}
          </>
     );
};

export default AddToList;