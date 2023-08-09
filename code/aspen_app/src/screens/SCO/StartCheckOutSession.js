import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { AlertDialog, Box, Button, Center, CheckIcon, FormControl, Select } from 'native-base';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { navigateStack } from '../../helpers/RootNavigator';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { Platform } from 'react-native';
import _ from 'lodash';

export const StartCheckOutSession = () => {
     const navigation = useNavigation();
     const isFocused = useIsFocused();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { user, accounts } = React.useContext(UserContext);

     const [isOpen, setIsOpen] = React.useState(true);
     const cancelRef = React.useRef(null);

     const [activeAccount, setActiveAccount] = React.useState(user.id);
     let availableAccounts = [];
     if (_.size(accounts) > 0) {
          availableAccounts = Object.values(accounts);
     }

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     const goBackToHome = () => {
          setIsOpen(false);
          navigateStack('BrowseTab', 'HomeScreen');
     };

     const startNewSession = () => {
          setIsOpen(false);
          navigateStack('SelfCheckTab', 'SelfCheckOut', {
               activeAccount: activeAccount,
          });
     };

     /*useFocusEffect(
          React.useCallback(() => {
               const resubscribe = () => {
                    if (!isOpen) {
                         setIsOpen(true);
                    }
               };

               return () => resubscribe();
          }, [isFocused])
     );
     */

     return (
          <Center>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={() => goBackToHome()}>
                    <AlertDialog.Content>
                         <AlertDialog.Header>{getTermFromDictionary(language, 'start_checkout_session')}</AlertDialog.Header>
                         <AlertDialog.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>{getTermFromDictionary(language, 'select_an_account')}</FormControl.Label>
                                   <Select
                                        isReadOnly={Platform.OS === 'android'}
                                        name="linkedAccount"
                                        selectedValue={activeAccount}
                                        minWidth="200"
                                        accessibilityLabel={getTermFromDictionary(language, 'select_an_account')}
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        mt={1}
                                        mb={3}
                                        onValueChange={(itemValue) => setActiveAccount(itemValue)}>
                                        <Select.Item label={user.displayName} value={user.id} />
                                        {availableAccounts.map((item, index) => {
                                             return <Select.Item label={item.displayName} value={item.id} key={index} />;
                                        })}
                                   </Select>
                              </FormControl>
                         </AlertDialog.Body>
                         <AlertDialog.Footer>
                              <Button.Group space={3}>
                                   <Button ref={cancelRef} onPress={() => goBackToHome()}>
                                        {getTermFromDictionary(language, 'cancel')}
                                   </Button>
                                   <Button colorScheme="primary" onPress={() => startNewSession()}>
                                        {getTermFromDictionary(language, 'button_start')}
                                   </Button>
                              </Button.Group>
                         </AlertDialog.Footer>
                    </AlertDialog.Content>
               </AlertDialog>
          </Center>
     );
};