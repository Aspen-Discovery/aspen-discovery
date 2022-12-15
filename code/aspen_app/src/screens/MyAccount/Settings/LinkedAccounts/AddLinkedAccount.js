import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Button, Center, Modal, FormControl, Input, Icon } from 'native-base';
import React, { useState, useRef } from 'react';

import { translate } from '../../../../translations/translations';
import { addLinkedAccount } from '../../../../util/accountActions';
import { LibrarySystemContext, UserContext } from '../../../../context/initialContext';
import { getLinkedAccounts, getViewers } from '../../../../util/loadPatron';
import { refreshProfile } from '../../../../util/api/user';

// custom components and helper files

const AddLinkedAccount = () => {
     const { user, accounts, viewers, updateUser, updateLinkedAccounts, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [loading, setLoading] = useState(false);
     const [showModal, setShowModal] = useState(false);
     const [showPassword, setShowPassword] = useState(false);
     const [newUser, setNewUser] = useState('');
     const [password, setPassword] = useState('');

     const passwordRef = useRef();

     const toggle = () => {
          setShowModal(!showModal);
          setNewUser('');
          setPassword('');
          setLoading(false);
     };

     const refreshLinkedAccounts = async () => {
          await getLinkedAccounts(library.baseUrl).then((result) => {
               if (accounts !== result) {
                    updateLinkedAccounts(result);
               }
          });
          await getViewers(library.baseUrl).then((result) => {
               if (viewers !== result) {
                    updateLinkedViewerAccounts(result);
               }
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     return (
          <Center>
               <Button onPress={toggle}>{translate('linked_accounts.add_an_account')}</Button>
               <Modal isOpen={showModal} onClose={toggle} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="95%">
                         <Modal.CloseButton />
                         <Modal.Header>{translate('linked_accounts.account_to_manage')}</Modal.Header>
                         <Modal.Body>
                              <FormControl>
                                   <FormControl.Label>{translate('linked_accounts.username')}</FormControl.Label>
                                   <Input
                                        onChangeText={(text) => setNewUser(text)}
                                        autoCorrect={false}
                                        autoCapitalize="none"
                                        id="username"
                                        returnKeyType="next"
                                        textContentType="username"
                                        required
                                        size="lg"
                                        onSubmitEditing={() => {
                                             passwordRef.current.focus();
                                        }}
                                        blurOnSubmit={false}
                                   />
                              </FormControl>
                              <FormControl mt={3}>
                                   <FormControl.Label>{translate('linked_accounts.password')}</FormControl.Label>
                                   <Input onChangeText={(text) => setPassword(text)} autoCorrect={false} autoCapitalize="none" id="password" returnKeyType="next" textContentType="password" required size="lg" type={showPassword ? 'text' : 'password'} ref={passwordRef} InputRightElement={<Icon as={<MaterialCommunityIcons name={showPassword ? 'eye' : 'eye-off'} />} size="sm" w="1/6" h="full" mr={1} onPress={() => setShowPassword(!showPassword)} />} />
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="ghost" onPress={toggle}>
                                        {translate('general.close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={translate('general.adding')}
                                        onPress={async () => {
                                             setLoading(true);
                                             await addLinkedAccount(newUser, password, library.baseUrl).then((r) => {
                                                  refreshLinkedAccounts();
                                                  toggle();
                                             });
                                        }}>
                                        {translate('linked_accounts.add')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default AddLinkedAccount;