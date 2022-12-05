import { MaterialIcons } from '@expo/vector-icons';
import _ from 'lodash';
import { Platform } from 'react-native';
import { CloseIcon, VStack, Box, Button, Center, FormControl, HStack, Icon, Input, Radio, Select, Stack, Text, TextArea, Pressable } from 'native-base';
import React, { useState } from 'react';
import Modal from 'react-native-modal';

import { translate } from '../../translations/translations';
import { PATRON } from '../../util/loadPatron';
import { addTitlesToList, createListFromTitle } from '../../util/api/list';
import { LibrarySystemContext } from '../../context/initialContext';

export const AddToList = (props) => {
     const item = props.itemId;
     const btnStyle = props.btnStyle;
     const [open, setOpen] = React.useState(false);
     const [screen, setScreen] = React.useState('add-new');
     const toggleModal = () => {
          setOpen(!open);
     };
     const [loading, setLoading] = React.useState(false);
     const { library } = React.useContext(LibrarySystemContext);
     const lists = PATRON.lists;
     const [listId, setListId] = useState(PATRON.listLastUsed);
     const [description, saveDescription] = useState();
     const [title, saveTitle] = useState();
     const [isPublic, saveIsPublic] = useState();

     const updateLastListUsed = (id) => {
          PATRON.lastListUsed = id;
     };

     const SelectLists = () => {
          return (
               <Select
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
                         {translate('lists.add_to_list')}
                    </Button>
               </Center>
          );
     };

     const SmallButton = () => {
          return (
               <Button size="sm" variant="ghost" colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="xs" mr="-1" />} onPress={toggleModal}>
                    {translate('lists.add_to_list')}
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
                                                  {translate('lists.add_to_list')}
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
                                                            <FormControl.Label>{translate('lists.choose_a_list')}</FormControl.Label>
                                                            <SelectLists />
                                                       </Box>
                                                       <HStack space={2} alignItems="center">
                                                            <Text>{translate('general.or')}</Text>
                                                            <Button
                                                                 size="sm"
                                                                 onPress={() => {
                                                                      setScreen('create-new');
                                                                 }}>
                                                                 {translate('lists.create_new_list')}
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
                                                  {translate('general.cancel')}
                                             </Button>
                                             {!_.isEmpty(lists) ? (
                                                  <Button
                                                       isLoading={loading}
                                                       onPress={() => {
                                                            setLoading(true);
                                                            addTitlesToList(listId, item, library.baseUrl).then((res) => {
                                                                 PATRON.lastListUsed = listId;
                                                                 setLoading(false);
                                                                 setOpen(false);
                                                            });
                                                       }}>
                                                       {translate('lists.save_to_list')}
                                                  </Button>
                                             ) : (
                                                  <Button>{translate('lists.create_new_list')}</Button>
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
                                                  {translate('lists.create_new_list_item')}
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
                                                       <FormControl.Label>{translate('general.title')}</FormControl.Label>
                                                       <Input id="title" onChangeText={(text) => saveTitle(text)} returnKeyType="next" />
                                                  </FormControl>
                                                  <FormControl>
                                                       <FormControl.Label>{translate('general.description')}</FormControl.Label>
                                                       <TextArea id="description" onChangeText={(text) => saveDescription(text)} returnKeyType="next" />
                                                  </FormControl>
                                                  <FormControl>
                                                       <FormControl.Label>{translate('general.access')}</FormControl.Label>
                                                       <Radio.Group
                                                            defaultValue="1"
                                                            onChange={(nextValue) => {
                                                                 saveIsPublic(nextValue);
                                                            }}>
                                                            <Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px">
                                                                 <Radio value="1" my={1}>
                                                                      {translate('general.private')}
                                                                 </Radio>
                                                                 <Radio value="0" my={1}>
                                                                      {translate('general.public')}
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
                                                  {translate('general.cancel')}
                                             </Button>
                                             <Button
                                                  isLoading={loading}
                                                  isLoadingText="Saving..."
                                                  onPress={() => {
                                                       setLoading(true);
                                                       createListFromTitle(title, description, isPublic, item, library.baseUrl).then((res) => {
                                                            updateLastListUsed(res.listId);
                                                            setOpen(false);
                                                            setScreen('add-new');
                                                       });
                                                  }}>
                                                  {translate('lists.create_list')}
                                             </Button>
                                        </Button.Group>
                                   </>
                              )}
                         </VStack>
                    </Box>
               </Modal>
               {btnStyle === 'lg' ? LargeButton() : SmallButton()}
          </>
     );
};

/* export const AddToList = (props) => {
 const {library} = React.useContext(LibrarySystemContext);
 const {
 lists,
 updateLists
 } = React.useContext(UserContext);
 const {
 item,
 updateLastListUsed
 } = props;
 const [showUseExistingModal, setShowUseExistingModal] = useState(false);
 const [showCreateNewModal, setShowCreateNewModal] = useState(false);
 const [loading, setLoading] = useState(false);

 const [listId, setListId] = useState(PATRON.listLastUsed);

 const [title, setTitle] = React.useState('');
 const [description, setDescription] = React.useState('');
 const [access, setAccess] = React.useState(false);

 return (
 <Center>
 <Button onPress={() => setShowUseExistingModal(true)} colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="xs" mr="-1"/>} size="sm" variant="ghost">
 {translate('lists.add_to_list')}
 </Button>
 <Modal isOpen={showUseExistingModal} onClose={() => setShowUseExistingModal(false)} size="full" avoidKeyboard>
 <Modal.Content maxWidth="90%" bg="white" _dark={{bg: 'coolGray.800'}}>
 <Modal.CloseButton/>
 <Modal.Header>
 <Heading size="sm">{translate('lists.add_to_list')}</Heading>
 </Modal.Header>
 <Modal.Body>
 <FormControl pb={5}>
 {!_.isUndefined(lists) ? (
 <Box>
 <FormControl.Label>{translate('lists.choose_a_list')}</FormControl.Label>
 <Select
 selectedValue={listId}
 onValueChange={(itemValue) => {
 setListId(itemValue);
 }}>
 {lists.map((item, index) => {
 return <Select.Item key={index} value={item.id} label={item.title}/>;
 })}
 </Select>
 <HStack space={3} alignItems="center" pt={2}>
 <Text>{translate('general.or')}</Text>
 <Button
 size="sm"
 onPress={() => {
 setShowUseExistingModal(false);
 setShowCreateNewModal(true);
 }}>
 {translate('lists.create_new_list')}
 </Button>
 </HStack>
 </Box>
 ) : (
 <Text>{translate('lists.no_lists_yet')}</Text>
 )}
 </FormControl>
 </Modal.Body>
 <Modal.Footer>
 <Button.Group>
 <Button variant="outline" onPress={() => setShowUseExistingModal(false)}>
 {translate('general.cancel')}
 </Button>
 {!_.isEmpty(lists) ? (
 <Button
 isLoading={loading}
 onPress={() => {
 setLoading(true);
 addTitlesToList(listId, item, library.baseUrl).then((res) => {
 updateLastListUsed(listId);
 setLoading(false);
 setShowUseExistingModal(false);
 });
 }}>
 {translate('lists.save_to_list')}
 </Button>
 ) : (
 <Button
 onPress={() => {
 setShowUseExistingModal(false);
 setShowCreateNewModal(true);
 }}>
 {translate('lists.create_new_list')}
 </Button>
 )}
 </Button.Group>
 </Modal.Footer>
 </Modal.Content>
 </Modal>
 </Center>
 );
 }; */

const CreateNewList = () => {
     const [showModal, setShowModal] = React.useState();
     return (
          <Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full" avoidKeyboard>
               <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                    <Modal.CloseButton />
                    <Modal.Header>
                         <Heading size="sm">{translate('lists.create_new_list_item')}</Heading>
                    </Modal.Header>
                    <Modal.Body>
                         <FormControl pb={5}>
                              <FormControl.Label>{translate('general.title')}</FormControl.Label>
                              <Input id="title" onChangeText={(text) => setTitle(text)} returnKeyType="next" />
                         </FormControl>
                         <FormControl pb={5}>
                              <FormControl.Label>{translate('general.description')}</FormControl.Label>
                              <TextArea id="description" onChangeText={(text) => setDescription(text)} returnKeyType="next" />
                         </FormControl>
                         <FormControl>
                              <FormControl.Label>{translate('general.access')}</FormControl.Label>
                              <Radio.Group defaultValue="1">
                                   <Stack
                                        direction="row"
                                        alignItems="center"
                                        space={4}
                                        w="75%"
                                        maxW="300px"
                                        onChange={(nextValue) => {
                                             setAccess(nextValue);
                                        }}>
                                        <Radio value="1" my={1}>
                                             {translate('general.private')}
                                        </Radio>
                                        <Radio value="0" my={1}>
                                             {translate('general.public')}
                                        </Radio>
                                   </Stack>
                              </Radio.Group>
                         </FormControl>
                    </Modal.Body>
                    <Modal.Footer>
                         <Button.Group>
                              <Button variant="outline" onPress={() => setShowCreateNewModal(false)}>
                                   {translate('general.cancel')}
                              </Button>
                              <Button
                                   isLoading={loading}
                                   onPress={() => {
                                        setLoading(true);
                                        createListFromTitle(title, description, access, item, library.baseUrl).then((res) => {
                                             updateLastListUsed(res.listId);
                                             setLoading(false);
                                             setShowCreateNewModal(false);
                                        });
                                   }}>
                                   {translate('lists.create_list')}
                              </Button>
                         </Button.Group>
                    </Modal.Footer>
               </Modal.Content>
          </Modal>
     );
};

export default AddToList;