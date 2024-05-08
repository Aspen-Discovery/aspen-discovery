import { useFocusEffect } from '@react-navigation/native';
import { useQueryClient } from '@tanstack/react-query';
import { Text, CheckIcon, Heading, HStack, VStack, Badge, BadgeText, FlatList, Button, ButtonGroup, ButtonText, ButtonIcon, Box, Icon, Center, AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogBody, AlertDialogFooter, AlertDialogBackdrop, Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, SelectScrollView, ButtonSpinner } from '@gluestack-ui/themed';
import React from 'react';
import { LanguageContext, LibrarySystemContext, ThemeContext, UserContext } from '../context/initialContext';
import { navigateStack } from '../helpers/RootNavigator';
import { getTermFromDictionary } from '../translations/TranslationService';
import { getAppPreferencesForUser, refreshProfile, updateNotificationOnboardingStatus } from '../util/api/user';

export const NotificationsOnboard = (props) => {
     const queryClient = useQueryClient();
     const { isFocused, promptOpen, setPromptOpen } = props;
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { expoToken, notificationOnboard, updateNotificationOnboard, updateNotificationSettings, updateAppPreferences } = React.useContext(UserContext);
     const [isOpen, setIsOpen] = React.useState(isFocused);
     const [onboardingBody, setOnboardingBody] = React.useState('');
     const [onboardingButton, setOnboardingButton] = React.useState('');
     const [isLoading, setIsLoading] = React.useState(false);
     const [isCanceling, setIsCanceling] = React.useState(false);
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     const onClose = async () => {
          await updateNotificationOnboardingStatus(false, expoToken, library.baseUrl, language);
          await getAppPreferencesForUser(library.baseUrl, language).then((data) => {
               updateAppPreferences(data);
               setIsOpen(false);
          });

          updateNotificationOnboard(0);
          setIsLoading(false);
          setIsCanceling(false);
          setPromptOpen('');

          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
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
                         await refreshProfile(library.baseUrl).then((profile) => {
                              updateNotificationSettings(profile.notification_preferences, language, false);
                         });
                    }
               };
               getTranslations().then(() => {
                    return () => getTranslations();
               });
          }, [])
     );

     return (
          <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={() => onClose()}>
               <AlertDialogBackdrop />
               <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                    <AlertDialogHeader>
                         <Heading color={textColor}>{getTermFromDictionary(language, 'onboard_notifications_title')}</Heading>
                    </AlertDialogHeader>
                    <AlertDialogBody>
                         <Text color={textColor}>{onboardingBody}</Text>
                    </AlertDialogBody>
                    <AlertDialogFooter>
                         <ButtonGroup space="md">
                              <Button
                                   isLoading={isCanceling}
                                   isLoadingText={getTermFromDictionary(language, 'canceling', true)}
                                   variant="link"
                                   bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}
                                   onPress={() => {
                                        setIsCanceling(true);
                                        onClose();
                                   }}
                                   ref={cancelRef}>
                                   {isCanceling ? <ButtonSpinner color={textColor} /> : <ButtonText color={textColor}>{getTermFromDictionary(language, 'onboard_notifications_button_cancel')}</ButtonText>}
                              </Button>
                              <Button
                                   isLoading={isLoading}
                                   isLoadingText={getTermFromDictionary(language, 'updating', true)}
                                   bgColor={theme['colors']['danger']['700']}
                                   onPress={() => {
                                        setIsLoading(true);
                                        onClose().then(() => navigateStack('MoreTab', 'PermissionNotificationDescription', { prevRoute: 'notifications_onboard' }));
                                   }}>
                                   {isLoading ? <ButtonSpinner color={theme['colors']['white']} /> : <ButtonText color={theme['colors']['white']}>{onboardingButton}</ButtonText>}
                              </Button>
                         </ButtonGroup>
                    </AlertDialogFooter>
               </AlertDialogContent>
          </AlertDialog>
     );
};