import { ScrollView, AlertDialog, AlertDialogBackdrop, Icon, Pressable, HStack, VStack, Text, Center, Button, ButtonText, ButtonIcon, ButtonGroup, Heading, Box, Accordion, AlertDialogBody, AlertDialogContent, AlertDialogFooter, AlertDialogHeader, AccordionItem, AccordionContent, AccordionContentText, AccordionHeader, AccordionTrigger, AccordionTitleText, AccordionIcon } from '@gluestack-ui/themed';
import React from 'react';
import { Camera } from 'expo-camera';
import { useRoute } from '@react-navigation/native';
import * as Linking from 'expo-linking';
import { AppState, Platform } from 'react-native';

import { LanguageContext, ThemeContext } from '../../../../context/initialContext';
import { navigate } from '../../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../../translations/TranslationService';
import { ChevronRight, ChevronUp, ChevronDown } from 'lucide-react-native';
import Constants from 'expo-constants';

export const CameraPermissionStatus = () => {
     const { language } = React.useContext(LanguageContext);
     const { colorMode, textColor } = React.useContext(ThemeContext);
     const [permissionStatus, setPermissionStatus] = React.useState(false);

     const appState = React.useRef(AppState.currentState);
     const [appStateVisible, setAppStateVisible] = React.useState(appState.current);

     React.useEffect(() => {
          (async () => {
               const { status } = await Camera.getCameraPermissionsAsync();
               setPermissionStatus(status === 'granted');
          })();

          const subscription = AppState.addEventListener('change', async (nextAppState) => {
               if (appState.current.match(/inactive|background/) && nextAppState === 'active') {
                    const { status } = await Camera.getCameraPermissionsAsync();
                    setPermissionStatus(status === 'granted');
               }

               appState.current = nextAppState;
               setAppStateVisible(appState.current);
          });

          return () => {
               subscription.remove();
          };
     }, []);

     return (
          <Pressable onPress={() => navigate('PermissionCameraDescription', { permissionStatus })} pb="$3">
               <HStack space="md" justifyContent="space-between" alignItems="center">
                    <Text bold color={textColor}>
                         {getTermFromDictionary(language, 'camera_permission')}
                    </Text>
                    <HStack alignItems="center">
                         <Text color={textColor}>{permissionStatus === true ? getTermFromDictionary(language, 'allowed') : getTermFromDictionary(language, 'not_allowed')}</Text>
                         <Icon ml="$1" as={ChevronRight} color={textColor} />
                    </HStack>
               </HStack>
          </Pressable>
     );
};

export const CameraPermissionDescription = () => {
     const { colorMode, textColor } = React.useContext(ThemeContext);
     const [permissionStatus, setPermissionStatus] = React.useState(useRoute().params?.permissionStatus ?? false);
     const { language } = React.useContext(LanguageContext);

     return (
          <ScrollView p="$5">
               <VStack alignItems="stretch">
                    <Box>
                         <Text color={textColor}>{getTermFromDictionary(language, 'device_set_to')}</Text>

                         <Heading mb="$1" color={textColor}>
                              {permissionStatus === true ? getTermFromDictionary(language, 'allowed') : getTermFromDictionary(language, 'not_allowed')}
                         </Heading>
                         <Text color={textColor}>
                              {Constants.expoConfig.name} {permissionStatus === true ? getTermFromDictionary(language, 'allowed_camera') : getTermFromDictionary(language, 'not_allowed_camera')}
                         </Text>

                         <Text color={textColor} mt="$5">
                              {getTermFromDictionary(language, 'to_update_settings')}
                         </Text>
                         <CameraPermissionUsage />
                    </Box>
                    <CameraPermissionUpdate permissionStatus={permissionStatus} setPermissionStatus={setPermissionStatus} />
               </VStack>
          </ScrollView>
     );
};

const CameraPermissionUsage = () => {
     const { language } = React.useContext(LanguageContext);
     const { textColor } = React.useContext(ThemeContext);

     return (
          <Accordion variant="unfilled" w="100%" size="sm">
               <AccordionItem value="description">
                    <AccordionHeader>
                         <AccordionTrigger px="$0">
                              {({ isExpanded }) => {
                                   return (
                                        <>
                                             <AccordionTitleText color={textColor}>{getTermFromDictionary(language, 'how_we_use_camera_title')}</AccordionTitleText>
                                             {isExpanded ? <AccordionIcon as={ChevronUp} ml="$3" color={textColor} /> : <AccordionIcon as={ChevronDown} ml="$3" color={textColor} />}
                                        </>
                                   );
                              }}
                         </AccordionTrigger>
                    </AccordionHeader>
                    <AccordionContent px="$0">
                         <AccordionContentText color={textColor}>
                              {Constants.expoConfig.name} {getTermFromDictionary(language, 'how_we_use_camera_body')}
                         </AccordionContentText>
                    </AccordionContent>
               </AccordionItem>
          </Accordion>
     );
};

const CameraPermissionUpdate = (payload) => {
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     const { language } = React.useContext(LanguageContext);
     const [showAlertDialog, setShowAlertDialog] = React.useState(false);
     const [manuallyPromptPermission, setManuallyPromptPermission] = React.useState(false);
     const setPermissionStatus = payload.setPermissionStatus;
     const permissionStatus = payload.permissionStatus;

     const manuallyRequestPermission = async () => {
          await Camera.requestCameraPermissionsAsync().then(async () => {
               setManuallyPromptPermission(false);
               const { status } = await Camera.getCameraPermissionsAsync();
               setPermissionStatus(status === 'granted');
          });
     };

     React.useEffect(() => {
          (async () => {
               const { status } = await Camera.getCameraPermissionsAsync();
               setPermissionStatus(status === 'granted');
               if (status === 'undetermined') {
                    setManuallyPromptPermission(true);
               }
          })();
     }, []);

     return (
          <Center>
               <Button
                    onPress={async () => {
                         if (manuallyPromptPermission) {
                              await manuallyRequestPermission();
                         } else {
                              setShowAlertDialog(true);
                         }
                    }}
                    bgColor={theme['colors']['primary']['500']}>
                    <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'update_device_settings')}</ButtonText>
               </Button>
               <AlertDialog
                    isOpen={showAlertDialog}
                    onClose={() => {
                         setShowAlertDialog(false);
                    }}>
                    <AlertDialogBackdrop />
                    <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                         <AlertDialogHeader>
                              <Heading color={textColor}>{getTermFromDictionary(language, 'update_device_settings')}</Heading>
                         </AlertDialogHeader>
                         <AlertDialogBody>
                              <Text color={textColor}>{Platform.OS === 'android' ? getTermFromDictionary(language, 'update_camera_android') : getTermFromDictionary(language, 'update_camera_ios')}</Text>
                         </AlertDialogBody>
                         <AlertDialogFooter>
                              <ButtonGroup flexDirection="column" alignItems="stretch" w="100%">
                                   <Button
                                        onPress={() => {
                                             Linking.openSettings();
                                             setShowAlertDialog(false);
                                        }}
                                        bgColor={theme['colors']['primary']['500']}>
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'open_device_settings')}</ButtonText>
                                   </Button>
                                   <Button variant="link" onPress={() => setShowAlertDialog(false)}>
                                        <ButtonText color={textColor}>{getTermFromDictionary(language, 'not_now')}</ButtonText>
                                   </Button>
                              </ButtonGroup>
                         </AlertDialogFooter>
                    </AlertDialogContent>
               </AlertDialog>
          </Center>
     );
};