import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { AlertDialog, Button, Center, FormControl, Heading, Icon, Input, Modal, Radio, Stack, TextArea, ChevronLeftIcon, Pressable } from 'native-base';
import React, { useState } from 'react';
import { popAlert } from '../../../components/loadError';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { navigate, navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { deleteList, editList, getListDetails } from '../../../util/api/list';

const EditList = (props) => {
     const queryClient = useQueryClient();
     const { data, listId } = props;
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [showModal, setShowModal] = React.useState(false);
     const [loading, setLoading] = React.useState(false);
     const [title, setTitle] = React.useState(data.title);
     const [description, setDescription] = React.useState(data.description);
     const [list, setList] = React.useState([]);
     const [isPublic, setPublic] = React.useState(data.public);

     useQuery(['list-details', data.id], () => getListDetails(data.id, library.baseUrl), {
          onSuccess: (data) => {
               setList(data);
               setLoading(false);
          },
     });

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => (
                    <Pressable
                         onPress={() => {
                              navigateStack('AccountScreenTab', 'MyLists', {
                                   hasPendingChanges: true,
                              });
                         }}
                         mr={3}
                         hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                         <ChevronLeftIcon size={5} color="primary.baseContrast" />
                    </Pressable>
               ),
          });
     }, [navigation]);

     return (
          <>
               <Button.Group size="sm" justifyContent="center">
                    <Button onPress={() => setShowModal(true)} leftIcon={<Icon as={MaterialIcons} name="edit" size="xs" />}>
                         {getTermFromDictionary(language, 'edit')}
                    </Button>
                    <DeleteList listId={listId} />
               </Button.Group>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="sm">
                                   {getTermFromDictionary(language, 'edit')} {data.title}
                              </Heading>
                         </Modal.Header>
                         <Modal.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'title')}</FormControl.Label>
                                   <Input id="title" defaultValue={data.title} autoComplete="off" onChangeText={(text) => setTitle(text)} />
                              </FormControl>
                              <FormControl pb={5}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'description')}</FormControl.Label>
                                   <TextArea id="description" defaultValue={data.description} autoComplete="off" onChangeText={(text) => setDescription(text)} />
                              </FormControl>
                              <FormControl>
                                   <FormControl.Label>{getTermFromDictionary(language, 'access')}</FormControl.Label>
                                   <Radio.Group
                                        value={isPublic}
                                        onChange={(nextValue) => {
                                             setPublic(nextValue);
                                        }}>
                                        <Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px">
                                             <Radio value={false} my={1}>
                                                  {getTermFromDictionary(language, 'private')}
                                             </Radio>
                                             <Radio value={true} my={1}>
                                                  {getTermFromDictionary(language, 'public')}
                                             </Radio>
                                        </Stack>
                                   </Radio.Group>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="outline" onPress={() => setShowModal(false)}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={getTermFromDictionary(language, 'saving', true)}
                                        onPress={() => {
                                             setLoading(true);
                                             editList(data.id, title, description, isPublic, library.baseUrl).then((r) => {
                                                  setLoading(false);
                                                  if (!_.isNull(title)) {
                                                       navigation.setOptions({ title: title });
                                                  }
                                                  setShowModal(false);
                                                  queryClient.invalidateQueries({ queryKey: ['list-details', data.id, library.baseUrl, language] });
                                                  queryClient.invalidateQueries({ queryKey: ['lists', user.id, library.baseUrl, language] });
                                             });
                                        }}>
                                        {getTermFromDictionary(language, 'save')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

const DeleteList = (props) => {
     const queryClient = useQueryClient();
     const { listId } = props;
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [isOpen, setIsOpen] = React.useState(false);
     const [loading, setLoading] = useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);

     return (
          <Center>
               <Button onPress={() => setIsOpen(!isOpen)} startIcon={<Icon as={MaterialIcons} name="delete" size="xs" />} size="sm" colorScheme="danger">
                    Delete List
               </Button>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                    <AlertDialog.Content>
                         <AlertDialog.CloseButton />
                         <AlertDialog.Header>Delete List</AlertDialog.Header>
                         <AlertDialog.Body>Are you sure you want to delete this list?</AlertDialog.Body>
                         <AlertDialog.Footer>
                              <Button.Group space={2}>
                                   <Button variant="unstyled" colorScheme="coolGray" onPress={onClose} ref={cancelRef}>
                                        Cancel
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Deleting..."
                                        colorScheme="danger"
                                        onPress={() => {
                                             setLoading(true);
                                             deleteList(listId, library.baseUrl).then(async (res) => {
                                                  queryClient.invalidateQueries({ queryKey: ['lists', user.id, library.baseUrl, language] });
                                                  queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                  setLoading(false);
                                                  let status = 'success';
                                                  setIsOpen(!isOpen);
                                                  if (res.success === false) {
                                                       status = 'danger';
                                                       popAlert(res.title, res.message, status);
                                                  } else {
                                                       popAlert(res.title, res.message, status);
                                                       navigateStack('AccountScreenTab', 'MyLists', {
                                                            libraryUrl: library.baseUrl,
                                                            hasPendingChanges: true,
                                                       });
                                                  }
                                             });
                                        }}>
                                        Delete
                                   </Button>
                              </Button.Group>
                         </AlertDialog.Footer>
                    </AlertDialog.Content>
               </AlertDialog>
          </Center>
     );
};

export default EditList;