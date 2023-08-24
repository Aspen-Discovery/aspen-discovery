import React from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { Button, AlertDialog } from 'native-base';
import { LanguageContext, LibrarySystemContext, UserContext } from '../context/initialContext';
import { getTermFromDictionary } from '../translations/TranslationService';
import { navigateStack } from '../helpers/RootNavigator';
import { updateNotificationOnboardingStatus } from '../util/api/user';
import { useQueryClient } from '@tanstack/react-query';

export const NotificationsOnboard = (props) => {
     const queryClient = useQueryClient();
     const { setAlreadyCheckedNotifications, setShowNotificationsOnboarding } = props;
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { user, notificationSettings, expoToken, notificationOnboard, updateNotificationOnboard } = React.useContext(UserContext);
     const [isOpen, setIsOpen] = React.useState(true);
     const [onboardingBody, setOnboardingBody] = React.useState('');
     const [onboardingButton, setOnboardingButton] = React.useState('');
     const onClose = async () => {
          updateNotificationOnboard(0);
          try {
               await updateNotificationOnboardingStatus(false, expoToken, library.baseUrl, language);
          } catch (e) {
               // onboarding isn't setup yet (Discovery older than 23.07.00)
          }
          setIsOpen(false);
          //setAlreadyCheckedNotifications(true);
          //setShowNotificationsOnboarding(false);
     };
     const cancelRef = React.useRef(null);

     useFocusEffect(
          React.useCallback(() => {
               const getTranslations = async () => {
                    if (notificationOnboard === 2 || notificationOnboard === '2') {
                         setOnboardingBody(getTermFromDictionary(language, 'onboard_notifications_body_update'));
                         setOnboardingButton(getTermFromDictionary(language, 'onboard_notifications_button_update'));
                    } else if (notificationOnboard === 1 || notificationOnboard === '1') {
                         setOnboardingBody(getTermFromDictionary(language, 'onboard_notifications_body_new'));
                         setOnboardingButton(getTermFromDictionary(language, 'onboard_notifications_button_new'));
                    } else {
                         setIsOpen(false);
                         //setAlreadyCheckedNotifications(true);
                         updateNotificationOnboard(0);
                         try {
                              await updateNotificationOnboardingStatus(false, expoToken, library.baseUrl, language);
                         } catch (e) {
                              // onboarding isn't setup yet (Discovery older than 23.07.00)
                         }
                    }
               };
               getTranslations().then(() => {
                    return () => getTranslations();
               });
          }, [])
     );

     return (
          <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={() => onClose()}>
               <AlertDialog.Content>
                    <AlertDialog.Header>{getTermFromDictionary(language, 'onboard_notifications_title')}</AlertDialog.Header>
                    <AlertDialog.Body>{onboardingBody}</AlertDialog.Body>
                    <AlertDialog.Footer>
                         <Button.Group space={2}>
                              <Button variant="unstyled" colorScheme="coolGray" onPress={() => onClose()} ref={cancelRef}>
                                   {getTermFromDictionary(language, 'onboard_notifications_button_cancel')}
                              </Button>
                              <Button
                                   colorScheme="danger"
                                   onPress={() => {
                                        onClose().then(() => navigateStack('AccountScreenTab', 'SettingsNotificationOptions', {}));
                                   }}>
                                   {onboardingButton}
                              </Button>
                         </Button.Group>
                    </AlertDialog.Footer>
               </AlertDialog.Content>
          </AlertDialog>
     );
};