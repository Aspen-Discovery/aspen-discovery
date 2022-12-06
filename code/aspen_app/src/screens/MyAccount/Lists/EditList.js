import { MaterialIcons } from '@expo/vector-icons';
import { AlertDialog, Button, Center, FormControl, Heading, Icon, Input, Modal, Radio, Stack, TextArea } from 'native-base';
import React, { useState } from 'react';
import _ from 'lodash';
import { popAlert } from '../../../components/loadError';
import { useNavigation, useFocusEffect, useRoute } from '@react-navigation/native';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { clearListTitles, deleteList, editList, getListDetails, getLists } from '../../../util/api/list';
import { reloadProfile } from '../../../util/api/user';

const EditList = (props) => {
     const { data, listId } = props;
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { lists, updateLists } = React.useContext(UserContext);
     const [showModal, setShowModal] = React.useState(false);
     const [loading, setLoading] = React.useState(false);
     const [title, setTitle] = React.useState(data.title);
     const [description, setDescription] = React.useState(data.description);
     const [list, setList] = React.useState([]);
     const [isPublic, setPublic] = React.useState(data.public);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getListDetails(data.id, library.baseUrl).then((result) => {
                         if (list !== result) {
                              setList(result);
                         }
                    });
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     return (
          <Center>
               <Button.Group size="sm" justifyContent="center" pb={5}>
                    <Button onPress={() => setShowModal(true)} leftIcon={<Icon as={MaterialIcons} name="edit" size="xs" />}>
                         Edit
                    </Button>
                    <DeleteList listId={listId} />
               </Button.Group>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="sm">Edit {data.title}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>Name</FormControl.Label>
                                   <Input id="title" defaultValue={data.title} autoComplete="off" onChangeText={(text) => setTitle(text)} />
                              </FormControl>
                              <FormControl pb={5}>
                                   <FormControl.Label>Description</FormControl.Label>
                                   <TextArea id="description" defaultValue={data.description} autoComplete="off" onChangeText={(text) => setDescription(text)} />
                              </FormControl>
                              <FormControl>
                                   <FormControl.Label>Access</FormControl.Label>
                                   <Radio.Group
                                        value={isPublic}
                                        onChange={(nextValue) => {
                                             setPublic(nextValue);
                                        }}>
                                        <Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px">
                                             <Radio value={false} my={1}>
                                                  Private
                                             </Radio>
                                             <Radio value={true} my={1}>
                                                  Public
                                             </Radio>
                                        </Stack>
                                   </Radio.Group>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="outline" onPress={() => setShowModal(false)}>
                                        Cancel
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Saving..."
                                        onPress={() => {
                                             setLoading(true);
                                             editList(data.id, title, description, isPublic, library.baseUrl).then((r) => {
                                                  setLoading(false);
                                                  if (!_.isNull(title)) {
                                                       navigation.setOptions({ title: title });
                                                  }
                                                  setShowModal(false);
                                             });
                                        }}>
                                        Save
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

const DeleteList = (props) => {
     const { listId } = props;
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser, lists, updateLists } = React.useContext(UserContext);
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
                                                  reloadProfile(library.baseUrl).then((result) => {
                                                       updateUser(result);
                                                  });
                                                  await getLists(library.baseUrl).then((result) => {
                                                       if (lists !== result) {
                                                            updateLists(result);
                                                       }
                                                  });
                                                  setLoading(false);
                                                  let status = 'success';
                                                  if (res.success === false) {
                                                       status = 'danger';
                                                       setIsOpen(!isOpen);
                                                       popAlert('Unable to delete list', res.message, status);
                                                  } else {
                                                       popAlert('List deleted', res.message, status);
                                                       navigation.navigate('AccountScreenTab', {
                                                            screen: 'Lists',
                                                            params: { libraryUrl: library.baseUrl },
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

const ClearList = (props) => {
     const { navigation, listId } = props;
     const { library } = React.useContext(LibrarySystemContext);
     const [isOpen, setIsOpen] = React.useState(false);
     const [loading, setLoading] = useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);

     return (
          <Center>
               <Button onPress={() => setIsOpen(!isOpen)} startIcon={<Icon as={MaterialIcons} name="delete" size="xs" />}>
                    Delete All Items
               </Button>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                    <AlertDialog.Content>
                         <AlertDialog.CloseButton />
                         <AlertDialog.Header>Delete All Items</AlertDialog.Header>
                         <AlertDialog.Body>This will remove all data relating to Alex. This action cannot be reversed. Deleted data can not be recovered.</AlertDialog.Body>
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
                                             clearListTitles(listId, library.baseUrl).then((r) => setLoading(false));
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