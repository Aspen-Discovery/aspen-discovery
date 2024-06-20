import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import { AlertDialog, Button, Center, FormControl, Icon, Input, Spinner, Text, VStack, WarningOutlineIcon } from 'native-base';
import React from 'react';
import { popAlert } from '../../components/loadError';
import { AuthContext } from '../../components/navigation';
import { BrowseCategoryContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { createAuthTokens, getHeaders } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { getBrowseCategories, getLibraryBranch, getLibrarySystem, getUserProfile } from '../../util/login';

export const ResetExpiredPin = (props) => {
     const [resetSuccessful, setResetSuccessful] = React.useState(false);
     const [resetMessage, setResetMessage] = React.useState('');
     const { signIn } = React.useContext(AuthContext);
     const { updateLibrary } = React.useContext(LibrarySystemContext);
     const { updateLocation } = React.useContext(LibraryBranchContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateBrowseCategories } = React.useContext(BrowseCategoryContext);
     const { language } = React.useContext(LanguageContext);
     const { username, resetToken, url, pinValidationRules, setExpiredPin, patronsLibrary } = props;
     const [isOpen, setIsOpen] = React.useState(true);
     const onClose = () => {
          setExpiredPin(false);
          setIsOpen(false);
     };
     const cancelRef = React.useRef(null);

     const [pin, setPin] = React.useState('');
     const [pinConfirmed, setPinConfirmed] = React.useState('');
     const [errors, setErrors] = React.useState({});

     // show:hide data from password fields
     const [showPin, setShowPin] = React.useState(false);
     const [showPinConfirmed, setShowPinConfirmed] = React.useState(false);
     const toggleShowPin = () => setShowPin(!showPin);
     const toggleShowPinConfirmed = () => setShowPinConfirmed(!showPinConfirmed);

     const pinConfirmedRef = React.useRef();

     const valueUser = username;
     const valueSecret = pin;

     const validatePin = () => {
          if (pin === undefined) {
               setErrors({ ...errors, pin: 'Pin is required' });
               return false;
          } else if (_.size(pin) < pinValidationRules.minLength) {
               setErrors({ ...errors, pin: 'Pin should be greater than ' + pinValidationRules.minLength + ' characters' });
               return false;
          } else if (_.size(pin) > pinValidationRules.maxLength) {
               setErrors({ ...errors, pin: 'Pin should be less than ' + pinValidationRules.maxLength + ' characters' });
               return false;
          } else if (pin !== pinConfirmed) {
               setErrors({ ...errors, pin: 'Pins should match.' });
               return false;
          }
          setErrors({});
          return true;
     };

     const validatePinConfirmed = () => {
          if (pinConfirmed === undefined) {
               setErrors({ ...errors, pinConfirmed: 'Pin is required' });
               return false;
          } else if (_.size(pinConfirmed) < pinValidationRules.minLength) {
               setErrors({ ...errors, pinConfirmed: 'Pin should be greater than ' + pinValidationRules.minLength + ' characters' });
               return false;
          } else if (_.size(pinConfirmed) > pinValidationRules.maxLength) {
               setErrors({ ...errors, pinConfirmed: 'Pin should be less than ' + pinValidationRules.maxLength + ' characters' });
               return false;
          } else if (pinConfirmed !== pin) {
               setErrors({ ...errors, pinConfirmed: 'Pins should match.' });
               return false;
          }
          setErrors({});
          return true;
     };

     const updatePIN = async () => {
          if (validatePin() && validatePinConfirmed()) {
               await resetExpiredPin(pin, pinConfirmed, resetToken, url).then(async (result) => {
                    if (result.success) {
                         setResetMessage(result.message ?? 'Pin successfully reset.');
                         setResetSuccessful(true);
                         await setAsyncStorage();
                         await setContext();
                         signIn();
                         setExpiredPin(false);
                         setIsOpen(false);
                    } else {
                         popAlert(getTermFromDictionary('en', 'error'), result.message ?? 'Unable to update pin', 'error');
                    }
               });
          } else {
               console.log(errors);
          }
     };

     const setContext = async () => {
          const library = await getLibrarySystem({ patronsLibrary });
          updateLibrary(library);
          const location = await getLibraryBranch({ patronsLibrary });
          updateLocation(location);
          const user = await getUserProfile({ patronsLibrary }, { valueUser }, { valueSecret });
          updateUser(user);
          const categories = await getBrowseCategories({ patronsLibrary }, { valueUser }, { valueSecret });
          updateBrowseCategories(categories);
     };

     const setAsyncStorage = async () => {
          await SecureStore.setItemAsync('userKey', username);
          await SecureStore.setItemAsync('secretKey', pin);
          await SecureStore.setItemAsync('library', patronsLibrary['libraryId']);
          await AsyncStorage.setItem('@libraryId', patronsLibrary['libraryId']);
          await SecureStore.setItemAsync('libraryName', patronsLibrary['name']);
          await SecureStore.setItemAsync('locationId', patronsLibrary['locationId']);
          await AsyncStorage.setItem('@locationId', patronsLibrary['locationId']);
          await SecureStore.setItemAsync('solrScope', patronsLibrary['solrScope']);

          await AsyncStorage.setItem('@solrScope', patronsLibrary['solrScope']);
          await AsyncStorage.setItem('@pathUrl', patronsLibrary['baseUrl']);
          await SecureStore.setItemAsync('pathUrl', patronsLibrary['baseUrl']);
          await AsyncStorage.setItem('@lastStoredVersion', Constants.expoConfig.version);
          await AsyncStorage.setItem('@patronLibrary', JSON.stringify(patronsLibrary));
     };

     return (
          <Center>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose} avoidKeyboard>
                    <AlertDialog.Content>
                         <AlertDialog.Header>{resetSuccessful ? getTermFromDictionary(language, 'pin_updated') : getTermFromDictionary(language, 'reset_my_pin')}</AlertDialog.Header>
                         {resetSuccessful ? (
                              <>
                                   <AlertDialog.Body>
                                        <Center>
                                             <VStack>
                                                  <Text>{resetMessage}. Logging you in...</Text>
                                                  <Spinner accessibilityLabel="Loading..." />
                                             </VStack>
                                        </Center>
                                   </AlertDialog.Body>
                              </>
                         ) : (
                              <>
                                   <AlertDialog.CloseButton />
                                   <AlertDialog.Body>
                                        {getTermFromDictionary(language, 'pin_has_expired')}
                                        <FormControl isRequired isInvalid={'pin' in errors}>
                                             <FormControl.Label
                                                  _text={{
                                                       fontSize: 'sm',
                                                       fontWeight: 600,
                                                  }}>
                                                  {getTermFromDictionary(language, 'new_pin')}
                                             </FormControl.Label>
                                             <Input
                                                  keyboardType={pinValidationRules.onlyDigitsAllowed === '1' ? 'numeric' : 'default'}
                                                  autoCapitalize="none"
                                                  size="xl"
                                                  autoCorrect={false}
                                                  type={showPin ? 'text' : 'password'}
                                                  variant="filled"
                                                  id="pin"
                                                  returnKeyType="next"
                                                  enterKeyHint="next"
                                                  textContentType="password"
                                                  required
                                                  onChangeText={(text) => setPin(text)}
                                                  InputRightElement={<Icon as={<Ionicons name={showPin ? 'eye-outline' : 'eye-off-outline'} />} size="md" ml={1} mr={3} onPress={toggleShowPin} roundedLeft={0} roundedRight="md" />}
                                                  onSubmitEditing={() => pinConfirmedRef.current.focus()}
                                                  blurOnSubmit={false}
                                             />
                                             {'pin' in errors ? <FormControl.ErrorMessage leftIcon={<WarningOutlineIcon size="xs" />}>{errors.pin}</FormControl.ErrorMessage> : null}
                                        </FormControl>
                                        <FormControl isRequired isInvalid={'pinConfirmed' in errors}>
                                             <FormControl.Label
                                                  _text={{
                                                       fontSize: 'sm',
                                                       fontWeight: 600,
                                                  }}>
                                                  {getTermFromDictionary(language, 'new_pin_confirmed')}
                                             </FormControl.Label>
                                             <Input
                                                  keyboardType={pinValidationRules.onlyDigitsAllowed === '1' ? 'numeric' : 'default'}
                                                  autoCapitalize="none"
                                                  size="xl"
                                                  autoCorrect={false}
                                                  type={showPinConfirmed ? 'text' : 'password'}
                                                  variant="filled"
                                                  id="pinConfirmed"
                                                  enterKeyHint="done"
                                                  returnKeyType="done"
                                                  textContentType="password"
                                                  required
                                                  onChangeText={(text) => setPinConfirmed(text)}
                                                  InputRightElement={<Icon as={<Ionicons name={showPinConfirmed ? 'eye-outline' : 'eye-off-outline'} />} size="md" ml={1} mr={3} onPress={toggleShowPinConfirmed} roundedLeft={0} roundedRight="md" />}
                                                  onSubmitEditing={() => updatePIN()}
                                                  ref={pinConfirmedRef}
                                             />
                                             {'pinConfirmed' in errors ? <FormControl.ErrorMessage leftIcon={<WarningOutlineIcon size="xs" />}>{errors.pinConfirmed}</FormControl.ErrorMessage> : null}
                                        </FormControl>
                                   </AlertDialog.Body>

                                   <AlertDialog.Footer>
                                        <Button.Group space={3}>
                                             <Button onPress={onClose}>{getTermFromDictionary(language, 'cancel')}</Button>
                                             <Button colorScheme="primary" onPress={() => updatePIN()}>
                                                  {getTermFromDictionary(language, 'update')}
                                             </Button>
                                        </Button.Group>
                                   </AlertDialog.Footer>
                              </>
                         )}
                    </AlertDialog.Content>
               </AlertDialog>
          </Center>
     );
};

async function resetExpiredPin(pin1, pin2, token, url) {
     const postBody = new FormData();
     postBody.append('pin1', pin1);
     postBody.append('pin2', pin2);
     postBody.append('token', token);
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const results = await discovery.post('/UserAPI?method=resetExpiredPin', postBody);
     console.log(results);
     if (results.ok) {
          return results.data.result;
     } else {
          return {
               success: false,
               message: 'Unable to connect to library',
          };
     }
}