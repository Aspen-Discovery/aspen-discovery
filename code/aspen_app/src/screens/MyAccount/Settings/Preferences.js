import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import _ from 'lodash';
import { Box, Divider, HStack, Icon, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';

// custom components and helper files
import { navigate } from '../../../helpers/RootNavigator';
import { UseColorMode } from '../../../themes/theme';
import { getTermFromDictionary, LanguageSwitcher } from '../../../translations/TranslationService';

export const PreferencesScreen = () => {
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { user, expoToken, aspenToken, updateExpoToken, updateAspenToken } = React.useContext(UserContext);

     React.useEffect(() => {
          const updateTokens = navigation.addListener('focus', async () => {
               if (Constants.isDevice) {
                    const token = (
                         await Notifications.getExpoPushTokenAsync({
                              projectId: Constants.expoConfig.extra.eas.projectId,
                         })
                    ).data;
                    if (token) {
                         if (!_.isEmpty(user.notification_preferences)) {
                              const tokenStorage = user.notification_preferences;
                              if (_.find(tokenStorage, _.matchesProperty('token', token))) {
                                   updateAspenToken(true);
                                   updateExpoToken(token);
                              }
                         }
                    }
               }
          });
          return updateTokens;
     }, [navigation]);

     return (
          <Box safeArea={5}>
               <VStack divider={<Divider />} space="4">
                    <VStack space="3">
                         <VStack>
                              <Pressable py="3" onPress={() => navigate('MyPreferences_ManageBrowseCategories')}>
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'manage_browse_categories')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3" onPress={() => navigate('PermissionDashboard')}>
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'device_permissions')}</Text>
                                   </HStack>
                              </Pressable>
                              <Pressable py="3" onPress={() => navigate('MyDevice_Support')}>
                                   <HStack space="1" alignItems="center">
                                        <Icon as={MaterialIcons} name="chevron-right" size="7" />
                                        <Text fontWeight="500">{getTermFromDictionary(language, 'support')}</Text>
                                   </HStack>
                              </Pressable>
                         </VStack>
                    </VStack>
                    <VStack>
                         <HStack justifyContent="space-between" alignItems="center">
                              <Text bold>{getTermFromDictionary(language, 'language')}</Text>
                              <LanguageSwitcher />
                         </HStack>
                         <HStack justifyContent="space-between" alignItems="center">
                              <Text bold>{getTermFromDictionary(language, 'appearance')}</Text>
                              <UseColorMode showText={true} />
                         </HStack>
                    </VStack>
               </VStack>
          </Box>
     );
};