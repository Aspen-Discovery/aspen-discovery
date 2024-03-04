import { useQueryClient } from '@tanstack/react-query';
import { Button, Center, Modal, Text } from 'native-base';
import React, { useState } from 'react';

import { LanguageContext, LibrarySystemContext } from '../../../context/initialContext';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { disableAccountLinking } from '../../../util/api/user';

// custom components and helper files

const DisableAccountLinking = () => {
     const queryClient = useQueryClient();
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [loading, setLoading] = useState(false);
     const [showModal, setShowModal] = useState(false);

     const toggle = () => {
          setShowModal(!showModal);
          setLoading(false);
     };

     const refreshLinkedAccounts = async () => {
          queryClient.invalidateQueries({ queryKey: ['linked_accounts', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['viewer_accounts', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
     };

     return (
          <Center>
               <Button onPress={toggle}>{getTermFromDictionary(language, 'disable_linked_accounts')}</Button>
               <Modal isOpen={showModal} onClose={toggle} size="lg">
                    <Modal.Content maxWidth="95%">
                         <Modal.CloseButton />
                         <Modal.Header>{getTermFromDictionary(language, 'disable_linked_accounts_title')}</Modal.Header>
                         <Modal.Body>
                              <Text>{getTermFromDictionary(language, 'disable_linked_accounts_body')}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button variant="ghost" onPress={toggle}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={getTermFromDictionary(language, 'updating', true)}
                                        onPress={async () => {
                                             setLoading(true);
                                             await disableAccountLinking(language, library.baseUrl).then(async (r) => {
                                                  await refreshLinkedAccounts();
                                                  toggle();
                                             });
                                        }}>
                                        {getTermFromDictionary(language, 'accept')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default DisableAccountLinking;