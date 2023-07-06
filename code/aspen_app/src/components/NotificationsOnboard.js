import React from 'react';
import _ from 'lodash';
import { useFocusEffect } from '@react-navigation/native';
import { Button, AlertDialog } from 'native-base';
import { LanguageContext, LibrarySystemContext, UserContext } from '../context/initialContext';
import { getTermFromDictionary } from '../translations/TranslationService';
import { navigateStack } from '../helpers/RootNavigator';
import { getNotificationPreference } from './Notifications';
import { updateNotificationOnboardingStatus } from '../util/api/user';
import { useQueryClient } from '@tanstack/react-query';

export const NotificationsOnboard = (props) => {
     const queryClient = useQueryClient();
     const { setAlreadyCheckedNotifications } = props;
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { user, notificationSettings, expoToken } = React.useContext(UserContext);
     const [isOpen, setIsOpen] = React.useState(true);
     const [onboardingBody, setOnboardingBody] = React.useState('');
     const [onboardingButton, setOnboardingButton] = React.useState('');
     const onClose = async () => {
          setIsOpen(false);
          setAlreadyCheckedNotifications(true);
          await updateNotificationOnboardingStatus(false, library.baseUrl, language);
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
     };
     const cancelRef = React.useRef(null);

     useFocusEffect(
          React.useCallback(() => {
               const checkSettings = async () => {
                    const doNotificationSettingsExist = await checkIfAnyNotificationSettingsExist(notificationSettings, library.baseUrl, expoToken);
                    if (doNotificationSettingsExist) {
                         setOnboardingBody(getTermFromDictionary(language, 'onboard_notifications_body_update'));
                         setOnboardingButton(getTermFromDictionary(language, 'onboard_notifications_button_update'));
                    } else {
                         setOnboardingBody(getTermFromDictionary(language, 'onboard_notifications_body_new'));
                         setOnboardingButton(getTermFromDictionary(language, 'onboard_notifications_button_new'));
                    }
               };
               checkSettings().then(() => {
                    return () => checkSettings();
               });
          }, [])
     );

     return (
          <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
               <AlertDialog.Content>
                    <AlertDialog.Header>{getTermFromDictionary(language, 'onboard_notifications_title')}</AlertDialog.Header>
                    <AlertDialog.Body>{onboardingBody}</AlertDialog.Body>
                    <AlertDialog.Footer>
                         <Button.Group space={2}>
                              <Button variant="unstyled" colorScheme="coolGray" onPress={onClose} ref={cancelRef}>
                                   {getTermFromDictionary(language, 'onboard_notifications_button_cancel')}
                              </Button>
                              <Button
                                   colorScheme="danger"
                                   onPress={() => {
                                        onClose();
                                        navigateStack('AccountScreenTab', 'SettingsNotifications', {});
                                   }}>
                                   {onboardingButton}
                              </Button>
                         </Button.Group>
                    </AlertDialog.Footer>
               </AlertDialog.Content>
          </AlertDialog>
     );
};

async function checkIfAnyNotificationSettingsExist(notificationSettings, url, expoToken) {
     if (_.isObject(notificationSettings)) {
          const currentPreferences = Object.values(notificationSettings);
          for await (const pref of currentPreferences) {
               const i = _.findIndex(currentPreferences, ['option', pref.option]);
               const result = await getNotificationPreference(url, expoToken, pref.option);
               if (result && i !== -1) {
                    if (result.success) {
                         if (pref.option === 'notifySavedSearch' || pref.option === 'notifyCustom' || pref.option === 'notifyAccount') {
                              if (result.allow) {
                                   return true;
                              }
                         }
                    }
               }
          }
     }
     return false;
}