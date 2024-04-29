import { AlertDialog, Button, Center } from 'native-base';
import React from 'react';

import { AuthContext } from '../../components/navigation';
import { LanguageContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

export const InvalidCredentials = () => {
     const { language } = React.useContext(LanguageContext);
     const { signOut } = React.useContext(AuthContext);
     const [isOpen, setIsOpen] = React.useState(true);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);

     return (
          <Center>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                    <AlertDialog.Content>
                         <AlertDialog.Header>{getTermFromDictionary(language, 'error')}</AlertDialog.Header>
                         <AlertDialog.Body>{getTermFromDictionary(language, 'error_invalid_credentials')}</AlertDialog.Body>
                         <AlertDialog.Footer>
                              <Button.Group space={3}>
                                   <Button colorScheme="primary" onPress={signOut} ref={cancelRef}>
                                        {getTermFromDictionary(language, 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </AlertDialog.Footer>
                    </AlertDialog.Content>
               </AlertDialog>
          </Center>
     );
};