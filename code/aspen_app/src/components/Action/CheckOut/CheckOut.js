import { Box, Button, ButtonSpinner, ButtonGroup, ButtonIcon, ButtonText, Text, Heading, Icon, CloseIcon, Modal, ModalBackdrop, ModalContent, ModalHeader, ModalCloseButton, ModalBody, ModalFooter, FormControl, FormControlLabel, FormControlLabelText, Input, InputField, InputSlot, InputIcon } from '@gluestack-ui/themed';
import React from 'react';
import _ from 'lodash';
import { useQueryClient } from '@tanstack/react-query';
import { EyeOff, Eye } from 'lucide-react-native';
import { useWindowDimensions } from 'react-native';
import RenderHtml from 'react-native-render-html';

// custom components and helper files
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { decodeHTML } from '../../../util/apiAuth';
import { completeAction } from '../../../util/recordActions';
import { refreshProfile, updateAlternateLibraryCard } from '../../../util/api/user';
import { HoldPrompt } from '../Holds/HoldPrompt';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const CheckOut = (props) => {
     const queryClient = useQueryClient();
     const { id, title, type, record, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef, holdConfirmationResponse, setHoldConfirmationResponse, holdConfirmationIsOpen, setHoldConfirmationIsOpen, onHoldConfirmationClose, cancelHoldConfirmationRef, userHasAlternateLibraryCard, shouldPromptAlternateLibraryCard } = props;
     const { user, updateUser, accounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [loading, setLoading] = React.useState(false);
     const { theme, colorMode, textColor } = React.useContext(ThemeContext);

     const volumeInfo = {
          numItemsWithVolumes: 0,
          numItemsWithoutVolumes: 1,
          hasItemsWithoutVolumes: true,
          majorityOfItemsHaveVolumes: false,
     };

     if (_.size(accounts) > 0) {
          return (
               <HoldPrompt
                    id={record}
                    title={title}
                    action={type}
                    volumeInfo={volumeInfo}
                    prevRoute={prevRoute}
                    isEContent={true}
                    setResponseIsOpen={setResponseIsOpen}
                    responseIsOpen={responseIsOpen}
                    onResponseClose={onResponseClose}
                    cancelResponseRef={cancelResponseRef}
                    response={response}
                    setResponse={setResponse}
                    setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                    holdConfirmationIsOpen={holdConfirmationIsOpen}
                    onHoldConfirmationClose={onHoldConfirmationClose}
                    cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                    holdConfirmationResponse={holdConfirmationResponse}
                    setHoldConfirmationResponse={setHoldConfirmationResponse}
                    userHasAlternateLibraryCard={userHasAlternateLibraryCard}
                    shouldPromptAlternateLibraryCard={shouldPromptAlternateLibraryCard}
               />
          );
     } else if (shouldPromptAlternateLibraryCard && !userHasAlternateLibraryCard) {
          const [showAddAlternateLibraryCardModal, setShowAddAlternateLibraryCardModal] = React.useState(false);

          let cardLabel = getTermFromDictionary(language, 'alternate_library_card');
          let passwordLabel = getTermFromDictionary(language, 'password');
          let formMessage = '';
          let showAlternateLibraryCardPassword = false;

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

          const { width } = useWindowDimensions();
          const [card, setCard] = React.useState(user?.alternateLibraryCard ?? '');
          const [password, setPassword] = React.useState(user?.alternateLibraryCardPassword ?? '');
          const [showPassword, setShowPassword] = React.useState(false);
          const toggleShowPassword = () => setShowPassword(!showPassword);

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

          const updateCard = async () => {
               await updateAlternateLibraryCard(card, password, false, library.baseUrl, language);
               await refreshProfile(library.baseUrl).then(async (result) => {
                    updateUser(result);
               });
               setCard('');
               setPassword('');
          };
          return (
               <>
                    <Button minWidth="100%" maxWidth="100%" bgColor={theme['colors']['primary']['500']} onPress={() => setShowAddAlternateLibraryCardModal(true)}>
                         <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>
                    </Button>
                    <Modal isOpen={showAddAlternateLibraryCardModal} onClose={() => setShowAddAlternateLibraryCardModal(false)} closeOnOverlayClick={false} size="lg">
                         <ModalBackdrop />
                         <ModalContent maxWidth="90%" bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                              <ModalHeader borderBottomWidth="$1" borderBottomColor={colorMode === 'light' ? theme['colors']['warmGray']['300'] : theme['colors']['coolGray']['500']}>
                                   <Heading size="md" color={textColor}>
                                        {getTermFromDictionary(language, 'add_alternate_library_card')}
                                   </Heading>
                                   <ModalCloseButton hitSlop={{ top: 30, bottom: 30, left: 30, right: 30 }}>
                                        <Icon as={CloseIcon} color={textColor} />
                                   </ModalCloseButton>
                              </ModalHeader>
                              <ModalBody mt="$3">
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
                              </ModalBody>
                              <ModalFooter borderTopWidth="$1" borderTopColor={colorMode === 'light' ? theme['colors']['warmGray']['300'] : theme['colors']['coolGray']['500']}>
                                   <ButtonGroup space="sm">
                                        <Button
                                             variant="outline"
                                             borderColor={colorMode === 'light' ? theme['colors']['warmGray']['300'] : theme['colors']['coolGray']['500']}
                                             onPress={() => {
                                                  setShowAddAlternateLibraryCardModal(false);
                                                  setLoading(false);
                                             }}>
                                             <ButtonText color={colorMode === 'light' ? theme['colors']['warmGray']['500'] : theme['colors']['coolGray']['300']}>{getTermFromDictionary(language, 'close_window')}</ButtonText>
                                        </Button>
                                        <Button
                                             bgColor={theme['colors']['primary']['500']}
                                             isDisabled={loading}
                                             onPress={async () => {
                                                  setLoading(true);
                                                  await updateCard();
                                                  await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
                                                       setResponse(eContentResponse);
                                                       if (eContentResponse.success) {
                                                            queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language] });
                                                            queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                       }
                                                       setLoading(false);
                                                       setResponseIsOpen(true);
                                                       setShowAddAlternateLibraryCardModal(false);
                                                  });
                                             }}>
                                             {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} /> : <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>}
                                        </Button>
                                   </ButtonGroup>
                              </ModalFooter>
                         </ModalContent>
                    </Modal>
               </>
          );
     } else {
          return (
               <>
                    <Button
                         minWidth="100%"
                         maxWidth="100%"
                         bgColor={theme['colors']['primary']['500']}
                         variant="solid"
                         onPress={async () => {
                              setLoading(true);
                              await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
                                   setResponse(eContentResponse);
                                   if (eContentResponse.success) {
                                        queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language] });
                                        queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                   }
                                   setLoading(false);
                                   setResponseIsOpen(true);
                              });
                         }}>
                         {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} pr={2} /> : <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>}
                    </Button>
               </>
          );
     }
};