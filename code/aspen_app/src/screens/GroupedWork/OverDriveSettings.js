import { Button, Checkbox, FormControl, Input, Modal, Stack } from 'native-base';
import React from 'react';

// custom components and helper files
import { updateOverDriveEmail } from '../../util/accountActions';
import { getTermFromDictionary } from '../../translations/TranslationService';

export const GetOverDriveSettings = (props) => {
     const { promptTitle, promptItemId, promptSource, promptPatronId, promptForOverdriveEmail, libraryUrl, showOverDriveSettings, handleOverDriveSettings, showAlert, setEmail, setRememberPrompt, overdriveEmail, language } = props;

     return (
          <Modal isOpen={showOverDriveSettings} onClose={() => handleOverDriveSettings(false)} avoidKeyboard closeOnOverlayClick={false}>
               <Modal.Content>
                    <Modal.CloseButton />
                    <Modal.Header>{promptTitle}</Modal.Header>
                    <Modal.Body mt={4}>
                         <FormControl>
                              <Stack>
                                   <FormControl.Label>{getTermFromDictionary(language, 'overdrive_email_field')}</FormControl.Label>
                                   <Input autoCapitalize="none" autoCorrect={false} id="overdriveEmail" onChangeText={(text) => setEmail(text)} />
                                   <Checkbox value="yes" my={2} id="promptForOverdriveEmail" onChange={(isSelected) => setRememberPrompt(isSelected)}>
                                        {getTermFromDictionary(language, 'remember_settings')}
                                   </Checkbox>
                              </Stack>
                         </FormControl>
                    </Modal.Body>
                    <Modal.Footer>
                         <Button.Group space={2} size="md">
                              <Button colorScheme="primary" variant="ghost" onPress={() => handleOverDriveSettings(false)}>
                                   {getTermFromDictionary(language, 'close_window')}
                              </Button>
                              <Button
                                   onPress={async () => {
                                        await updateOverDriveEmail(promptItemId, promptSource, promptPatronId, overdriveEmail, promptForOverdriveEmail, libraryUrl, language).then((response) => {
                                             showAlert(response);
                                        });
                                   }}>
                                   {getTermFromDictionary(language, 'place_hold')}
                              </Button>
                         </Button.Group>
                    </Modal.Footer>
               </Modal.Content>
          </Modal>
     );
};