import { create } from 'apisauce';
import _ from 'lodash';
import { Button, Center, FormControl, Input, Modal, Text } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { loadingSpinner } from '../../components/loadingSpinner';
import { LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary, getTranslationsWithValues } from '../../translations/TranslationService';
import { createAuthTokens, getHeaders, postData, stripHTML } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { LIBRARY } from '../../util/loadLibrary';
import { useKeyboard } from '../../util/useKeyboard';

export const ResetPassword = (props) => {
     const isKeyboardOpen = useKeyboard();
     const { library } = React.useContext(LibrarySystemContext);
     const { ils, forgotPasswordType, usernameLabel, passwordLabel, showForgotPasswordModal, setShowForgotPasswordModal } = props;
     const [isProcessing, setIsProcessing] = React.useState(false);
     const [isLoading, setIsLoading] = React.useState(false);

     const language = 'en';
     let libraryUrl = library.baseUrl ?? LIBRARY.url;

     const [buttonLabel, setButtonLabel] = React.useState('Forgot PIN?');
     const [modalTitle, setModalTitle] = React.useState('Forgot PIN');
     const [modalButtonLabel, setModalButtonLabel] = React.useState('Reset My PIN');
     const [resetBody, setResetBody] = React.useState('To reset your PIN, enter your card number or your email address.  You must have an email associated with your account to reset your PIN.  If you do not, please contact the library.');

     React.useEffect(() => {
          setIsLoading(true);

          async function fetchTranslations() {
               await getTranslationsWithValues('forgot_password_link', passwordLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setButtonLabel(term);
                    }
               });
               await getTranslationsWithValues('forgot_password_title', passwordLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalTitle(term);
                    }
               });
               await getTranslationsWithValues('reset_my_password', passwordLabel, language, libraryUrl).then((result) => {
                    let term = _.toString(result);
                    if (!term.includes('%')) {
                         setModalButtonLabel(term);
                    }
               });
               if (ils === 'koha') {
                    await getTranslationsWithValues('koha_password_reset_body', [_.lowerCase(passwordLabel), _.lowerCase(usernameLabel)], language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
               } else if (ils === 'sirsi' || ils === 'horizon') {
                    await getTranslationsWithValues('sirsi_password_reset_body', _.lowerCase(passwordLabel), language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
               } else if (ils === 'evergreen') {
                    await getTranslationsWithValues('evergreen_password_reset_body', _.lowerCase(passwordLabel), language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
               } else if (ils === 'millennium') {
                    await getTranslationsWithValues('millennium_password_reset_body', [_.lowerCase(usernameLabel), _.lowerCase(passwordLabel)], language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
                    await getTranslationsWithValues('request_pin_reset', passwordLabel, language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setModalButtonLabel(term);
                         }
                    });
               } else if (ils === 'symphony') {
                    await getTranslationsWithValues('symphony_password_reset_body', _.lowerCase(usernameLabel), language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
               } else {
                    await getTranslationsWithValues('aspen_password_reset_body', [_.lowerCase(passwordLabel), _.lowerCase(usernameLabel)], language, libraryUrl).then((result) => {
                         let term = _.toString(result);
                         if (!term.includes('%')) {
                              setResetBody(term);
                         }
                    });
               }
               setIsLoading(false);
          }

          fetchTranslations();
     }, [language, libraryUrl]);

     const closeWindow = () => {
          setShowForgotPasswordModal(false);
          setIsProcessing(false);
     };

     if (isLoading) {
          return null;
     }

     return (
          <Center>
               <Button variant="ghost" onPress={() => setShowForgotPasswordModal(true)} colorScheme="primary">
                    <Text color="primary.600">{buttonLabel}</Text>
               </Button>
               <Modal isOpen={showForgotPasswordModal} size="lg" avoidKeyboard={true} onClose={() => setShowForgotPasswordModal(false)} pb={Platform.OS === 'android' && isKeyboardOpen ? '50%' : '0'}>
                    <Modal.Content bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton onPress={closeWindow} />
                         <Modal.Header>{modalTitle}</Modal.Header>

                         {isLoading ? (
                              <Modal.Body>{loadingSpinner()}</Modal.Body>
                         ) : ils === 'koha' && forgotPasswordType === 'emailResetLink' ? (
                              <KohaResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'sirsi' && forgotPasswordType === 'emailResetLink' ? (
                              <SirsiResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'horizon' && forgotPasswordType === 'emailResetLink' ? (
                              <SirsiResetPassword libraryUrl={libraryUrl} sernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'evergreen' && forgotPasswordType === 'emailResetLink' ? (
                              <EvergreenResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'millennium' && forgotPasswordType === 'emailResetLink' ? (
                              <MillenniumResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : ils === 'symphony' && forgotPasswordType === 'emailResetLink' ? (
                              <SymphonyResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : forgotPasswordType === 'emailAspenResetLink' ? (
                              <AspenResetPassword libraryUrl={libraryUrl} usernameLabel={usernameLabel} passwordLabel={passwordLabel} modalButtonLabel={modalButtonLabel} resetBody={resetBody} setShowForgotPasswordModal={setShowForgotPasswordModal} isProcessing={isProcessing} setIsProcessing={setIsProcessing} />
                         ) : null}
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

const AspenResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, '', false, 'aspen', libraryUrl).then((data) => {
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
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, email, resend, 'koha', libraryUrl).then((data) => {
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
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, '', false, 'sirsi', libraryUrl).then((data) => {
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
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, email, resend, 'evergreen', libraryUrl).then((data) => {
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

const SymphonyResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, '', false, 'symphony', libraryUrl).then((data) => {
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

const MillenniumResetPassword = (props) => {
     const { usernameLabel, setShowForgotPasswordModal, isProcessing, setIsProcessing, modalButtonLabel, resetBody, libraryUrl } = props;
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
          await resetPassword(username, '', false, 'millennium', libraryUrl).then((data) => {
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
                         <Text>{stripHTML(results.message)}</Text>
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

async function resetPassword(username = '', email = '', resendEmail = false, ils = null, url) {
     const postBody = await postData();
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
     } else if (ils === 'symphony') {
          params = {
               barcode: username,
          };
     } else {
          params = {
               reset_username: username,
          };
     }

     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const results = await discovery.get('/UserAPI?method=resetPassword', params);
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