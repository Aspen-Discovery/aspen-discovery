import { create } from 'apisauce';
import _ from 'lodash';
import { Button, Center, FormControl, Input, Modal, Text } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary, getTranslation, getTranslationsWithValues } from '../../translations/TranslationService';
import { createAuthTokens, getHeaders, stripHTML } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { LIBRARY } from '../../util/loadLibrary';
import { useKeyboard } from '../../util/useKeyboard';

export const ForgotBarcode = (props) => {
     const isKeyboardOpen = useKeyboard();
     const { library } = React.useContext(LibrarySystemContext);
     const { usernameLabel, showForgotBarcodeModal, setShowForgotBarcodeModal } = props;
     const [isProcessing, setIsProcessing] = React.useState(false);
     const language = 'en';
     const [isLoading, setIsLoading] = React.useState(false);

     let libraryUrl = library.baseUrl ?? LIBRARY.url;

     const [phoneNumber, setPhoneNumber] = React.useState('');
     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const [buttonLabel, setButtonLabel] = React.useState('Forgot Barcode?');
     const [modalTitle, setModalTitle] = React.useState('Forgot Barcode');
     const [fieldLabel, setFieldLabel] = React.useState('Phone Number');
     const [modalBody, setModalBody] = React.useState('');
     const [modalButtonLabel, setModalButtonLabel] = React.useState('Send My Barcode');

     React.useEffect(() => {
          setIsLoading(true);

          async function fetchTranslations() {
               await getTranslationsWithValues('forgot_barcode_link', usernameLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setButtonLabel(term);
                    }
               });
               await getTranslationsWithValues('forgot_barcode_title', usernameLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalTitle(term);
                    }
               });
               await getTranslation('Phone Number', language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalButtonLabel(term);
                    }
               });
               await getTranslationsWithValues('send_my_barcode', usernameLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalButtonLabel(term);
                    }
               });
               await getTranslationsWithValues('forgot_barcode_body', usernameLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalBody(term);
                    }
               });
               setIsLoading(false);
          }

          fetchTranslations();
     }, [language, libraryUrl]);

     const closeWindow = () => {
          setShowForgotBarcodeModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };

     const initiateForgotBarcode = async () => {
          setIsProcessing(true);
          await forgotBarcode(phoneNumber, libraryUrl).then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (isLoading) {
          return null;
     }

     return (
          <Center>
               <Button variant="ghost" onPress={() => setShowForgotBarcodeModal(true)} colorScheme="primary">
                    <Text color="primary.600">{buttonLabel}</Text>
               </Button>
               <Modal isOpen={showForgotBarcodeModal} size="md" avoidKeyboard onClose={() => setShowForgotBarcodeModal(false)} pb={Platform.OS === 'android' && isKeyboardOpen ? '50%' : '0'}>
                    <Modal.Content bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton onPress={closeWindow} />
                         <Modal.Header>{modalTitle}</Modal.Header>
                         <Modal.Body>
                              {showResults && !results.success ? (
                                   <>
                                        <Text>{stripHTML(results.message)}</Text>
                                        <Button colorScheme="primary" onPress={resetWindow}>
                                             {getTermFromDictionary('en', 'try_again')}
                                        </Button>
                                   </>
                              ) : showResults ? (
                                   <Text>{stripHTML(results.message)}</Text>
                              ) : (
                                   <>
                                        <Text>{modalBody}</Text>
                                        <FormControl.Label
                                             _text={{
                                                  fontSize: 'sm',
                                                  fontWeight: 600,
                                             }}>
                                             {fieldLabel}
                                        </FormControl.Label>
                                        <Input id="phoneNumber" variant="filled" size="xl" returnKeyType="done" enterKeyHint="done" onChangeText={(text) => setPhoneNumber(text)} onSubmitEditing={() => initiateForgotBarcode()} textContentType="telephoneNumber" />
                                   </>
                              )}
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   {showResults ? (
                                        <Button variant="ghost" onPress={closeWindow}>
                                             {getTermFromDictionary('en', 'button_ok')}
                                        </Button>
                                   ) : (
                                        <>
                                             <Button variant="ghost" onPress={closeWindow}>
                                                  {getTermFromDictionary('en', 'cancel')}
                                             </Button>
                                             <Button
                                                  style={{
                                                       flexShrink: 1,
                                                  }}
                                                  isLoading={isProcessing}
                                                  isLoadingText={getTermFromDictionary('en', 'button_processing', true)}
                                                  colorScheme="primary"
                                                  onPress={initiateForgotBarcode}>
                                                  {modalButtonLabel}
                                             </Button>
                                        </>
                                   )}
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

async function forgotBarcode(phone, url) {
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const results = await discovery.get('/RegistrationAPI?method=lookupAccountByPhoneNumber', {
          phone: phone,
     });
     if (results.ok) {
          if (results.data.result) {
               return results.data.result;
          }
          return results.data;
     } else {
          return {
               success: false,
               message: 'Unable to connect to library',
          };
     }
}