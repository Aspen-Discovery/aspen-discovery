import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Button, Center, Modal, FormControl, Input, Icon } from 'native-base';
import React, { useState, useRef } from 'react';

import {LanguageContext, LibrarySystemContext, UserContext} from '../../../../context/initialContext';
import {reloadProfile, getLinkedAccounts, getViewerAccounts, addLinkedAccount} from '../../../../util/api/user';
import {getTermFromDictionary} from '../../../../translations/TranslationService';

// custom components and helper files

const AddLinkedAccount = () => {
     const { user, accounts, viewers, cards, updateUser, updateLinkedAccounts, updateLinkedViewerAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
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
          await getLinkedAccounts(user, cards, library, language).then((result) => {
               if (accounts !== result.accounts) {
                    updateLinkedAccounts(result.accounts);
               }
               if(cards !== result.cards) {
                    updateLibraryCards(result.cards);
               }
          });
          await getViewerAccounts(library.baseUrl, language).then((result) => {
               if (viewers !== result) {
                    updateLinkedViewerAccounts(result);
               }
          });
          reloadProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     return (
          <Center>
               <Button onPress={toggle}>{getTermFromDictionary(language, 'linked_add_an_account')}</Button>
               <Modal isOpen={showModal} onClose={toggle} size="full" avoidKeyboard>
                    <Modal.Content maxWidth="95%">
                         <Modal.CloseButton />
                         <Modal.Header>{getTermFromDictionary(language, 'linked_account_to_manage')}</Modal.Header>
                         <Modal.Body>
                              <FormControl>
                                   <FormControl.Label>{getTermFromDictionary(language, 'username')}</FormControl.Label>
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
                                        value={newUser}
                                   />
                              </FormControl>
                              <FormControl mt={3}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'password')}</FormControl.Label>
                                   <Input onChangeText={(text) => setPassword(text)} value={password} autoCorrect={false} autoCapitalize="none" id="password" returnKeyType="next" textContentType="password" required size="lg" type={showPassword ? 'text' : 'password'} ref={passwordRef} InputRightElement={<Icon as={<MaterialCommunityIcons name={showPassword ? 'eye' : 'eye-off'} />} size="sm" w="1/6" h="full" mr={1} onPress={() => setShowPassword(!showPassword)} />} />
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="ghost" onPress={toggle}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={getTermFromDictionary(language, 'adding', true)}
                                        onPress={async () => {
                                             setLoading(true);
                                             await addLinkedAccount(newUser, password, library.baseUrl).then(async (r) => {
                                                  await refreshLinkedAccounts();
                                                  toggle();
                                             });
                                        }}>
                                        {getTermFromDictionary(language, 'linked_add_account')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default AddLinkedAccount;