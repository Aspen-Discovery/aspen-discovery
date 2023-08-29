import React from 'react';
import { Button, Center, FormControl, Input, Text, Modal } from 'native-base';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { create } from 'apisauce';
import { GLOBALS } from '../../util/globals';
import { createAuthTokens, getHeaders } from '../../util/apiAuth';
import { LIBRARY } from '../../util/loadLibrary';
export const ForgotBarcode = (props) => {
     const { usernameLabel, showForgotBarcodeModal, setShowForgotBarcodeModal } = props;
     const [isProcessing, setIsProcessing] = React.useState(false);

     const [phoneNumber, setPhoneNumber] = React.useState('');
     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotBarcodeModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };

     const initiateForgotBarcode = async () => {
          setIsProcessing(true);
          await forgotBarcode(phoneNumber).then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     return (
          <Center>
               <Button variant="ghost" onPress={() => setShowForgotBarcodeModal(true)} colorScheme="primary">
                    <Text color="primary.600">Forgot {usernameLabel}?</Text>
               </Button>
               <Modal isOpen={showForgotBarcodeModal} size="md" avoidKeyboard onClose={() => setShowForgotBarcodeModal(false)}>
                    <Modal.Content bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton onPress={closeWindow} />
                         <Modal.Header>Forgot Barcode</Modal.Header>
                         <Modal.Body>
                              {showResults && !results.success ? (
                                   <>
                                        <Text>{results.message}</Text>
                                        <Button colorScheme="primary" onPress={resetWindow}>
                                             {getTermFromDictionary('en', 'try_again')}
                                        </Button>
                                   </>
                              ) : showResults ? (
                                   <Text>{results.message}</Text>
                              ) : (
                                   <>
                                        <FormControl.Label
                                             _text={{
                                                  fontSize: 'sm',
                                                  fontWeight: 600,
                                             }}>
                                             {usernameLabel}
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
                                             <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateForgotBarcode}>
                                                  {getTermFromDictionary('en', 'send_my_barcode')}
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

async function forgotBarcode(phone) {
     const postBody = new FormData();
     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               phone: phone,
          },
     });
     const results = await discovery.post('/RegistrationAPI?method=lookupAccountByPhoneNumber', postBody);
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