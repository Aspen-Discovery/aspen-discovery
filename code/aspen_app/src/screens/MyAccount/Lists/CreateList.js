import { MaterialIcons } from '@expo/vector-icons';
import { useQueryClient } from '@tanstack/react-query';
import { Button, Center, FormControl, Heading, Icon, Input, Modal, Radio, Stack, TextArea } from 'native-base';
import React, { useState } from 'react';

import { popAlert } from '../../../components/loadError';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { createList } from '../../../util/api/list';

const CreateList = (props) => {
     const { setLoading } = props;
     const queryClient = useQueryClient();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { updateLists } = React.useContext(UserContext);
     const [loading, setAdding] = React.useState(false);
     const [showModal, setShowModal] = useState(false);

     const [title, setTitle] = React.useState('');
     const [description, setDescription] = React.useState('');
     const [isPublic, setPublic] = React.useState(false);

     const toggle = () => {
          setShowModal(!showModal);
          setTitle('');
          setDescription('');
          setPublic(false);
          setAdding(false);
     };

     return (
          <Center>
               <Button onPress={toggle} size="sm" leftIcon={<Icon as={MaterialIcons} name="add" size="xs" mr="-1" />}>
                    {getTermFromDictionary(language, 'create_new_list')}
               </Button>
               <Modal isOpen={showModal} onClose={toggle} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{getTermFromDictionary(language, 'create_new_list')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'title')}</FormControl.Label>
                                   <Input id="title" onChangeText={(text) => setTitle(text)} returnKeyType="next" defaultValue={title} />
                              </FormControl>
                              <FormControl pb={5}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'description')}</FormControl.Label>
                                   <TextArea id="description" onChangeText={(text) => setDescription(text)} defaultValue={description} returnKeyType="next" />
                              </FormControl>
                              <FormControl>
                                   <FormControl.Label>{getTermFromDictionary(language, 'access')}</FormControl.Label>
                                   <Radio.Group
                                        name="access"
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
                                   <Button variant="outline" onPress={toggle}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={getTermFromDictionary(language, 'creating_list', true)}
                                        onPress={async () => {
                                             setAdding(true);
                                             await createList(title, description, isPublic, library.baseUrl).then(async (res) => {
                                                  let status = 'success';
                                                  if (!res.success) {
                                                       status = 'danger';
                                                  }
                                                  queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                  queryClient.invalidateQueries({ queryKey: ['lists', user.id, library.baseUrl, language] });
                                                  toggle();
                                                  setLoading(true);
                                                  popAlert(getTermFromDictionary(language, 'list_created'), res.message, status);
                                             });
                                        }}>
                                        {getTermFromDictionary(language, 'create_list')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default CreateList;