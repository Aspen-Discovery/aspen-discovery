import _ from 'lodash';
import { EyeOff, Eye } from 'lucide-react-native';
import { Pressable, ChevronLeftIcon, Box, ScrollView, ButtonGroup, Button, ButtonText, FormControl, FormControlLabel, FormControlLabelText, Input, InputField, InputSlot, InputIcon } from '@gluestack-ui/themed';
import React from 'react';
import { useWindowDimensions } from 'react-native';
import RenderHtml from 'react-native-render-html';
import { useQueryClient } from '@tanstack/react-query';
import { useRoute, useNavigation, CommonActions, StackActions } from '@react-navigation/native';
import { LoadingSpinner } from '../../../components/loadingSpinner';

// custom components and helper files
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { BackIcon } from '../../../themes/theme';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { refreshProfile, updateAlternateLibraryCard } from '../../../util/api/user';
import { decodeHTML } from '../../../util/apiAuth';

export const MyAlternateLibraryCard = () => {
     const navigation = useNavigation();
     const route = useRoute();
     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);
     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const { width } = useWindowDimensions();
     const [card, setCard] = React.useState(user?.alternateLibraryCard ?? '');
     const [password, setPassword] = React.useState(user?.alternateLibraryCardPassword ?? '');

     const [isLoading, setIsLoading] = React.useState(false);
     const [showPassword, setShowPassword] = React.useState(false);
     const toggleShowPassword = () => setShowPassword(!showPassword);

     const handleGoBack = () => {
          console.log(route?.params);
          if (route?.params?.prevRoute === 'AccountDrawer') {
               navigation.dispatch(CommonActions.setParams({ prevRoute: null }));
               navigation.dispatch(StackActions.replace('LibraryCard'));
          } else {
               navigation.goBack();
          }
     };

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => (
                    <Pressable onPress={handleGoBack} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                         <ChevronLeftIcon size="md" ml={1} color={theme['colors']['primary']['baseContrast']} />
                    </Pressable>
               ),
          });
     }, [navigation]);
     let cardLabel = getTermFromDictionary(language, 'alternate_library_card');
     let passwordLabel = getTermFromDictionary(language, 'password');
     let formMessage = '';
     let showAlternateLibraryCardPassword = false;
     let alternateLibraryCardStyle = 'none';

     if (library?.alternateLibraryCardConfig?.alternateLibraryCardLabel) {
          cardLabel = library.alternateLibraryCardConfig.alternateLibraryCardLabel;
     }

     if (library?.alternateLibraryCardConfig?.alternateLibraryCardPasswordLabel) {
          passwordLabel = library.alternateLibraryCardConfig.alternateLibraryCardPasswordLabel;
     }

     if (library?.alternateLibraryCardConfig?.alternateLibraryCardFormMessage) {
          formMessage = decodeHTML(library.alternateLibraryCardConfig.alternateLibraryCardFormMessage);
     }

     if (library?.alternateLibraryCardConfig?.showAlternateLibraryCardPassword) {
          if (library.alternateLibraryCardConfig.showAlternateLibraryCardPassword === '1' || library.alternateLibraryCardConfig.showAlternateLibraryCardPassword === 1) {
               showAlternateLibraryCardPassword = true;
          }
     }

     if (library?.alternateLibraryCardConfig?.alternateLibraryCardStyle) {
          alternateLibraryCardStyle = library.alternateLibraryCardConfig.alternateLibraryCardStyle;
     }

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1' || obj.showOn === '5') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     const source = {
          baseUrl: library.baseUrl,
          html: formMessage,
     };

     const tagsStyles = {
          body: {
               color: textColor,
          },
          a: {
               color: textColor,
               textDecorationColor: textColor,
          },
     };

     const deleteCard = async () => {
          await updateAlternateLibraryCard('', '', true, library.baseUrl, language);
          await refreshProfile(library.baseUrl).then(async (result) => {
               updateUser(result);
          });
     };

     const updateCard = async () => {
          await updateAlternateLibraryCard(card, password, false, library.baseUrl, language);
          await refreshProfile(library.baseUrl).then(async (result) => {
               updateUser(result);
          });
          setCard('');
          setPassword('');
     };

     return (
          <ScrollView>
               {isLoading ? (
                    LoadingSpinner()
               ) : (
                    <Box p="$5">
                         {showSystemMessage()}
                         <Box>
                              {formMessage ? <RenderHtml contentWidth={width} source={source} tagsStyles={tagsStyles} /> : null}
                              <FormControl mb="$2">
                                   <FormControlLabel>
                                        <FormControlLabelText color={textColor} size="sm">
                                             {cardLabel}
                                        </FormControlLabelText>
                                   </FormControlLabel>
                                   <Input>
                                        <InputField textContentType="none" color={textColor} name="card" defaultValue={card} accessibilityLabel={cardLabel} onChangeText={(value) => setCard(value)} />
                                   </Input>
                              </FormControl>
                              {showAlternateLibraryCardPassword ? (
                                   <FormControl mb="$2">
                                        <FormControlLabel>
                                             <FormControlLabelText color={textColor} size="sm">
                                                  {passwordLabel}
                                             </FormControlLabelText>
                                        </FormControlLabel>
                                        <Input>
                                             <InputField textContentType="none" type={showPassword ? 'text' : 'password'} color={textColor} name="password" defaultValue={password} accessibilityLabel={passwordLabel} onChangeText={(value) => setPassword(value)} />
                                             <InputSlot onPress={toggleShowPassword}>
                                                  <InputIcon as={showPassword ? Eye : EyeOff} mr="$2" color={textColor} />
                                             </InputSlot>
                                        </Input>
                                   </FormControl>
                              ) : null}
                              <ButtonGroup>
                                   <Button
                                        bgColor={theme['colors']['primary']['500']}
                                        onPress={() => {
                                             setIsLoading(true);
                                             updateCard().then(() => {
                                                  setIsLoading(false);
                                             });
                                        }}>
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'update')}</ButtonText>
                                   </Button>
                                   <Button
                                        bgColor={theme['colors']['danger']['700']}
                                        onPress={() => {
                                             setIsLoading(true);
                                             deleteCard().then(() => {
                                                  setIsLoading(false);
                                             });
                                        }}>
                                        <ButtonText color={theme['colors']['white']}>{getTermFromDictionary(language, 'delete')}</ButtonText>
                                   </Button>
                              </ButtonGroup>
                         </Box>
                    </Box>
               )}
          </ScrollView>
     );
};