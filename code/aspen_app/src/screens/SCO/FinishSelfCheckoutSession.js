import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { useIsFocused, useNavigation, useRoute } from '@react-navigation/native';
import { navigateStack } from '../../helpers/RootNavigator';
import { AlertDialog, Button, Center, Text } from 'native-base';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { Platform } from 'react-native';
import _ from 'lodash';

export const FinishCheckOutSession = () => {
     const navigation = useNavigation();
     const isFocused = useIsFocused();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { user, accounts } = React.useContext(UserContext);

     const [isOpen, setIsOpen] = React.useState(useRoute().params?.startNew ?? true);
     const cancelRef = React.useRef(null);

     const StartNewSession = () => {
          setIsOpen(false);
          if (_.size(accounts) >= 1) {
               navigation.replace('StartCheckOutSession', {
                    startNew: true,
               });
          } else {
               navigation.replace('SelfCheckOut', {
                    startNew: true,
                    barcode: null,
               });
          }
     };

     const GoToCheckouts = () => {
          setIsOpen(false);
          navigateStack('AccountScreenTab', 'MyCheckouts');
     };

     return (
          <Center>
               <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={() => StartNewSession()} size="lg">
                    <AlertDialog.Content>
                         <AlertDialog.Header>{getTermFromDictionary(language, 'finish_checkout_session')}</AlertDialog.Header>
                         <AlertDialog.Body>
                              <Text>{getTermFromDictionary(language, 'finish_checkout_session_body')}</Text>
                         </AlertDialog.Body>
                         <AlertDialog.Footer>
                              <Button.Group space={3}>
                                   <Button size="sm" onPress={() => StartNewSession()}>
                                        {getTermFromDictionary(language, 'start_new_session')}
                                   </Button>
                                   <Button size="sm" colorScheme="primary" onPress={() => GoToCheckouts()}>
                                        {getTermFromDictionary(language, 'view_checkouts')}
                                   </Button>
                              </Button.Group>
                         </AlertDialog.Footer>
                    </AlertDialog.Content>
               </AlertDialog>
          </Center>
     );
};