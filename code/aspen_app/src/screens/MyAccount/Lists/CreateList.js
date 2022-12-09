import { MaterialIcons } from '@expo/vector-icons';
import { Button, Center, Modal, Stack, Icon, FormControl, Input, TextArea, Heading, Radio } from 'native-base';
import React, { useState } from 'react';

import { popAlert } from '../../../components/loadError';
import { createList, getLists } from '../../../util/api/list';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { translate } from '../../../translations/translations';
import { reloadProfile } from '../../../util/api/user';

const CreateList = () => {
     const { updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateLists } = React.useContext(UserContext);
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);

     const [title, setTitle] = React.useState('');
     const [description, setDescription] = React.useState('');
     const [isPublic, setPublic] = React.useState(false);

     const toggle = () => {
          setShowModal(!showModal);
          setTitle('');
          setDescription('');
          setPublic(false);
          setLoading(false);
     };

     return (
          <Center>
               <Button onPress={toggle} size="sm" leftIcon={<Icon as={MaterialIcons} name="add" size="xs" mr="-1" />}>
                    {translate('lists.create_new_list')}
               </Button>
               <Modal isOpen={showModal} onClose={toggle} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{translate('lists.create_new_list')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>{translate('general.title')}</FormControl.Label>
                                   <Input id="title" onChangeText={(text) => setTitle(text)} returnKeyType="next" defaultValue={title} />
                              </FormControl>
                              <FormControl pb={5}>
                                   <FormControl.Label>{translate('general.description')}</FormControl.Label>
                                   <TextArea id="description" onChangeText={(text) => setDescription(text)} defaultValue={description} returnKeyType="next" />
                              </FormControl>
                              <FormControl>
                                   <FormControl.Label>{translate('general.access')}</FormControl.Label>
                                   <Radio.Group
                                        name="access"
                                        value={isPublic}
                                        onChange={(nextValue) => {
                                             setPublic(nextValue);
                                        }}>
                                        <Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px">
                                             <Radio value={false} my={1}>
                                                  {translate('general.private')}
                                             </Radio>
                                             <Radio value={true} my={1}>
                                                  {translate('general.public')}
                                             </Radio>
                                        </Stack>
                                   </Radio.Group>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="outline" onPress={toggle}>
                                        {translate('general.cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={translate('lists.creating_list')}
                                        onPress={async () => {
                                             setLoading(true);
                                             await createList(title, description, isPublic, library.baseUrl).then(async (res) => {
                                                  let status = 'success';
                                                  if (!res.success) {
                                                       status = 'danger';
                                                  }
                                                  await reloadProfile(library.baseUrl).then((result) => {
                                                       updateUser(result);
                                                  });
                                                  await getLists(library.baseUrl).then((result) => {
                                                       updateLists(result);
                                                  });
                                                  toggle();
                                                  popAlert(translate('lists.list_created'), res.message, status);
                                             });
                                        }}>
                                        {translate('lists.create_list')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default CreateList;