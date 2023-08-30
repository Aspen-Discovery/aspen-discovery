import React from 'react';
import { Button, Center, FormControl, Input, Text, Modal } from 'native-base';
import { getTermFromDictionary, getTranslation, getTranslationsWithValues } from '../../translations/TranslationService';
import { create } from 'apisauce';
import { GLOBALS } from '../../util/globals';
import { createAuthTokens, getHeaders, stripHTML } from '../../util/apiAuth';
import _ from 'lodash';
import { LIBRARY } from '../../util/loadLibrary';
export const ResetPassword = (props) => {
     const { ils, forgotPasswordType, usernameLabel, passwordLabel, showForgotPasswordModal, setShowForgotPasswordModal } = props;
     const [isProcessing, setIsProcessing] = React.useState(false);

     const language = 'en';

     const [buttonLabel, setButtonLabel] = React.useState('Forgot PIN?');
     const [modalTitle, setModalTitle] = React.useState('Forgot PIN');
     const [modalButtonLabel, setModalButtonLabel] = React.useState('Reset My PIN');
     const [resetBody, setResetBody] = React.useState('To reset your PIN, enter your card number or your email address.  You must have an email associated with your account to reset your PIN.  If you do not, please contact the library.');

     React.useEffect(() => {
          async function fetchTranslations() {
               await getTranslationsWithValues('forgot_password_link', passwordLabel, language, LIBRARY.url).then((result) => {
                    setButtonLabel(_.toString(result));
               });
               await getTranslationsWithValues('forgot_password_title', passwordLabel, language, LIBRARY.url).then((result) => {
                    setModalTitle(_.toString(result));
               });
               await getTranslationsWithValues('reset_my_password', passwordLabel, language, LIBRARY.url).then((result) => {
                    setModalButtonLabel(_.toString(result));
               });
               if (ils === 'koha') {
                    await getTranslationsWithValues('koha_password_reset_body', [_.lowerCase(passwordLabel), _.lowerCase(usernameLabel)], language, LIBRARY.url).then((result) => {
                         setResetBody(_.toString(result));
                    });
               } else if (ils === 'sirsi' || ils === 'horizon') {
                    await getTranslationsWithValues('sirsi_password_reset_body', _.lowerCase(passwordLabel), language, LIBRARY.url).then((result) => {
                         setResetBody(_.toString(result));
                    });
               } else if (ils === 'evergreen') {
                    await getTranslationsWithValues('evergreen_password_reset_body', _.lowerCase(passwordLabel), language, LIBRARY.url).then((result) => {
                         setResetBody(_.toString(result));
                    });
               } else if (ils === 'millennium') {
                    await getTranslationsWithValues('millennium_password_reset_body', [_.lowerCase(usernameLabel), _.lowerCase(passwordLabel)], language, LIBRARY.url).then((result) => {
                         setResetBody(_.toString(result));
                    });
                    await getTranslationsWithValues('request_pin_reset', passwordLabel, language, LIBRARY.url).then((result) => {
                         setModalButtonLabel(_.toString(result));
                    });
               } else {
                    await getTranslationsWithValues('aspen_password_reset_body', [_.lowerCase(passwordLabel), _.lowerCase(usernameLabel)], language, LIBRARY.url).then((result) => {
                         setResetBody(_.toString(result));
                    });
               }
          }
          fetchTranslations();
     }, [language, LIBRARY.url]);

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
     };

     return (
          <Center>
               <Button variant="ghost" onPress={() => setShowForgotPasswordModal(true)} colorScheme="primary">
                    <Text color="primary.600">{buttonLabel}</Text>
               </Button>
               <Modal isOpen={showForgotPasswordModal} size="md" avoidKeyboard onClose={() => setShowForgotPasswordModal(false)}>
                    <Modal.Content bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton onPress={closeWindow} />
                         <Modal.Header>{modalTitle}</Modal.Header>
                         {ils === 'koha' && forgotPasswordType === 'emailResetLink' ? (
                              <KohaResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'sirsi' && forgotPasswordType === 'emailResetLink' ? (
                              <SirsiResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'horizon' && forgotPasswordType === 'emailResetLink' ? (
                              <SirsiResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'evergreen' && forgotPasswordType === 'emailResetLink' ? (
                              <EvergreenResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'millennium' && forgotPasswordType === 'emailResetLink' ? (
                              <MillenniumResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : forgotPasswordType === 'emailAspenResetLink' ? (
                              <AspenResetPassword usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : null}
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

const AspenResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody } = props;
     const [username, setUsername] = React.useState('');

     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };
     const initiateResetPassword = async () => {
          setIsProcessing(true);
          await resetPassword(username, '', false, 'aspen').then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (results && showResults) {
          if (_.isEmpty(results.success) && results.error) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.error}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                                   <Button colorScheme="primary" onPress={resetWindow}>
                                        {getTermFromDictionary('en', 'try_again')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else if (!_.isEmpty(results.message)) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.message}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'cancel')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else {
               return (
                    <>
                         <Modal.Body>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_1')}</Text>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_2')}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          }
     }

     return (
          <>
               <Modal.Body>
                    <Text>{resetBody}</Text>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input id="username" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" returnKeyType="done" enterKeyHint="done" onChangeText={(text) => setUsername(text)} onSubmitEditing={() => initiateResetPassword()} textContentType="username" />
               </Modal.Body>
               <Modal.Footer>
                    <Button.Group space={2}>
                         <Button variant="ghost" onPress={closeWindow}>
                              {getTermFromDictionary('en', 'cancel')}
                         </Button>
                         <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateResetPassword}>
                              {modalButtonLabel}
                         </Button>
                    </Button.Group>
               </Modal.Footer>
          </>
     );
};
const KohaResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody } = props;
     const [email, setEmail] = React.useState('');
     const [username, setUsername] = React.useState('');
     const [resend, setResend] = React.useState(false);

     const fieldRef = React.useRef();

     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };
     const initiateResetPassword = async () => {
          setIsProcessing(true);
          await resetPassword(username, email, resend, 'koha').then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (results && showResults) {
          if (_.isEmpty(results.success) && results.error) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{stripHTML(results.error)}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                                   <Button colorScheme="primary" onPress={resetWindow}>
                                        {getTermFromDictionary('en', 'try_again')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else if (!_.isEmpty(results.message)) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{stripHTML(results.message)}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else {
               return (
                    <>
                         <Modal.Body>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_1')}</Text>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_2')}</Text>
                              <Center>
                                   <Button
                                        size="sm"
                                        onPress={() => {
                                             setResend(true);
                                             initiateResetPassword();
                                        }}>
                                        {getTermFromDictionary('en', 'resend_email')}
                                   </Button>
                              </Center>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          }
     }

     return (
          <>
               <Modal.Body>
                    <Text>{resetBody}</Text>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input id="username" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" returnKeyType="next" enterKeyHint="next" onChangeText={(text) => setUsername(text)} onSubmitEditing={() => fieldRef.current.focus()} blurOnSubmit={false} textContentType="username" />
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {getTermFromDictionary('en', 'patron_email')}
                    </FormControl.Label>
                    <Input id="email" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" enterKeyHint="done" returnKeyType="done" onChangeText={(text) => setEmail(text)} textContentType="emailAddress" ref={fieldRef} onSubmitEditing={() => initiateResetPassword()} />
               </Modal.Body>
               <Modal.Footer>
                    <Button.Group space={2}>
                         <Button variant="ghost" onPress={closeWindow}>
                              {getTermFromDictionary('en', 'cancel')}
                         </Button>
                         <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateResetPassword}>
                              {modalButtonLabel}
                         </Button>
                    </Button.Group>
               </Modal.Footer>
          </>
     );
};

const SirsiResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody } = props;
     const [username, setUsername] = React.useState('');

     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };
     const initiateResetPassword = async () => {
          setIsProcessing(true);
          await resetPassword(username, '', false, 'sirsi').then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (results && showResults) {
          if (_.isEmpty(results.success) && results.error) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.error}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                                   <Button colorScheme="primary" onPress={resetWindow}>
                                        {getTermFromDictionary('en', 'try_again')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else if (!_.isEmpty(results.message)) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.message}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'cancel')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else {
               return (
                    <>
                         <Modal.Body>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_1')}</Text>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_2')}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          }
     }

     return (
          <>
               <Modal.Body>
                    <Text>{resetBody}</Text>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input id="username" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" returnKeyType="done" enterKeyHint="done" onChangeText={(text) => setUsername(text)} onSubmitEditing={() => initiateResetPassword()} textContentType="username" />
               </Modal.Body>
               <Modal.Footer>
                    <Button.Group space={2}>
                         <Button variant="ghost" onPress={closeWindow}>
                              {getTermFromDictionary('en', 'cancel')}
                         </Button>
                         <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateResetPassword}>
                              {modalButtonLabel}
                         </Button>
                    </Button.Group>
               </Modal.Footer>
          </>
     );
};

const EvergreenResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody } = props;
     const [email, setEmail] = React.useState('');
     const [username, setUsername] = React.useState('');
     const [resend, setResend] = React.useState(false);

     const fieldRef = React.useRef();

     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };
     const initiateResetPassword = async () => {
          setIsProcessing(true);
          await resetPassword(username, email, resend, 'evergreen').then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (results && showResults) {
          if (_.isEmpty(results.success) && results.error) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.error}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                                   <Button colorScheme="primary" onPress={resetWindow}>
                                        {getTermFromDictionary('en', 'try_again')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else if (!_.isEmpty(results.message)) {
               return (
                    <>
                         <Modal.Body>
                              <Text>{results.message}</Text>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          } else {
               return (
                    <>
                         <Modal.Body>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_1')}</Text>
                              <Text>{getTermFromDictionary('en', 'password_reset_success_body_2')}</Text>
                              <Center>
                                   <Button
                                        size="sm"
                                        onPress={() => {
                                             setResend(true);
                                             initiateResetPassword();
                                        }}>
                                        {getTermFromDictionary('en', 'resend_email')}
                                   </Button>
                              </Center>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2}>
                                   <Button variant="ghost" onPress={closeWindow}>
                                        {getTermFromDictionary('en', 'button_ok')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </>
               );
          }
     }

     return (
          <>
               <Modal.Body>
                    <Text>{resetBody}</Text>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input id="username" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" returnKeyType="next" enterKeyHint="next" onChangeText={(text) => setUsername(text)} onSubmitEditing={() => fieldRef.current.focus()} blurOnSubmit={false} textContentType="username" />
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {getTermFromDictionary('en', 'patron_email')}
                    </FormControl.Label>
                    <Input id="email" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" enterKeyHint="done" returnKeyType="done" onChangeText={(text) => setEmail(text)} textContentType="emailAddress" ref={fieldRef} onSubmitEditing={() => initiateResetPassword()} />
               </Modal.Body>
               <Modal.Footer>
                    <Button.Group space={2}>
                         <Button variant="ghost" onPress={closeWindow}>
                              {getTermFromDictionary('en', 'cancel')}
                         </Button>
                         <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateResetPassword}>
                              {modalButtonLabel}
                         </Button>
                    </Button.Group>
               </Modal.Footer>
          </>
     );
};

const MillenniumResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody } = props;
     const [username, setUsername] = React.useState('');

     const [showResults, setShowResults] = React.useState(false);
     const [results, setResults] = React.useState('');

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
          setShowResults(false);
          setResults('');
     };
     const initiateResetPassword = async () => {
          setIsProcessing(true);
          await resetPassword(username, '', false, 'millennium').then((data) => {
               setResults(data);
               setShowResults(true);
          });
          setIsProcessing(false);
     };

     const resetWindow = () => {
          setShowResults(false);
          setResults('');
     };

     if (results && showResults) {
          return (
               <>
                    <Modal.Body>
                         <Text>{results.message}</Text>
                    </Modal.Body>
                    <Modal.Footer>
                         <Button.Group space={2}>
                              <Button variant="ghost" onPress={closeWindow}>
                                   {getTermFromDictionary('en', 'button_ok')}
                              </Button>
                              {!_.isEmpty(results.error) ? (
                                   <Button colorScheme="primary" onPress={resetWindow}>
                                        {getTermFromDictionary('en', 'try_again')}
                                   </Button>
                              ) : null}
                         </Button.Group>
                    </Modal.Footer>
               </>
          );
     }

     return (
          <>
               <Modal.Body>
                    <Text>{resetBody}</Text>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input id="username" variant="filled" autoCorrect={false} autoCapitalize="none" size="xl" returnKeyType="done" enterKeyHint="done" onChangeText={(text) => setUsername(text)} onSubmitEditing={() => initiateResetPassword()} textContentType="username" />
               </Modal.Body>
               <Modal.Footer>
                    <Button.Group space={2}>
                         <Button variant="ghost" onPress={closeWindow}>
                              {getTermFromDictionary('en', 'cancel')}
                         </Button>
                         <Button isLoading={isProcessing} isLoadingText={getTermFromDictionary('en', 'button_processing', true)} colorScheme="primary" onPress={initiateResetPassword}>
                              {modalButtonLabel}
                         </Button>
                    </Button.Group>
               </Modal.Footer>
          </>
     );
};

async function resetPassword(username = '', email = '', resendEmail = false, ils = null) {
     const postBody = new FormData();
     let params = {};
     if (ils === 'koha') {
          params = {
               username: username,
               email: email,
               resendEmail: resendEmail,
          };
     } else if (ils === 'sirsi') {
          params = {
               barcode: username,
          };
     } else if (ils === 'evergreen' || ils === 'horizon') {
          params = {
               username: username,
               email: email,
               resendEmail: resendEmail,
          };
     } else if (ils === 'millennium') {
          params = {
               barcode: username,
          };
     } else {
          params = {
               reset_username: username,
          };
     }

     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: params,
     });
     const results = await discovery.post('/UserAPI?method=resetPassword', postBody);
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