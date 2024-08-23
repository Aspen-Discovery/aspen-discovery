import React from 'react';
import _ from 'lodash';
import { CloseIcon, Modal, ModalBackdrop, ModalContent, ModalHeader, ModalCloseButton, ModalBody, ModalFooter, FormControl, FormControlLabel, FormControlLabelText, Heading, Button, ButtonGroup, ButtonText, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, Icon, ChevronDownIcon, ButtonSpinner, Input, InputField, InputSlot, InputIcon } from '@gluestack-ui/themed';
import { LanguageContext, LibrarySystemContext, ThemeContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { refreshProfile, updateAlternateLibraryCard } from '../../util/api/user';
import { decodeHTML } from '../../util/apiAuth';
import { completeAction } from '../../util/recordActions';
import { useWindowDimensions } from 'react-native';
import RenderHtml from 'react-native-render-html';
import { EyeOff, Eye } from 'lucide-react-native';

export const AddAlternateLibraryCard = (props) => {
     const {
          id,
          title,
          action,
          volumeInfo,
          holdTypeForFormat,
          variationId,
          prevRoute,
          isEContent,
          response,
          setResponse,
          responseIsOpen,
          setResponseIsOpen,
          onResponseClose,
          cancelResponseRef,
          holdConfirmationResponse,
          setHoldConfirmationResponse,
          holdConfirmationIsOpen,
          setHoldConfirmationIsOpen,
          onHoldConfirmationClose,
          cancelHoldConfirmationRef,
          holdSelectItemResponse,
          setHoldSelectItemResponse,
          holdItemSelectIsOpen,
          setHoldItemSelectIsOpen,
          onHoldItemSelectClose,
          cancelHoldItemSelectRef,
          recordSource,
          activeAccount,
     } = props;

     let isPlacingHold = false;
     if (_.isObject(action)) {
          isPlacingHold = action.includes('hold');
     }

     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);
     const queryClient = useQueryClient();
     const { width } = useWindowDimensions();
     const [card, setCard] = React.useState(user?.alternateLibraryCard ?? '');
     const [password, setPassword] = React.useState(user?.alternateLibraryCardPassword ?? '');
     const [showModal, setShowModal] = React.useState(true);
     const [loading, setLoading] = React.useState(false);

     const [showPassword, setShowPassword] = React.useState(false);
     const toggleShowPassword = () => setShowPassword(!showPassword);

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
     };

     return (
          <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
               <ModalBackdrop />
               <ModalContent maxWidth="90%" bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                    <ModalHeader borderBottomWidth="$1" borderBottomColor={colorMode === 'light' ? theme['colors']['warmGray']['300'] : theme['colors']['coolGray']['500']}>
                         <Heading size="md" color={textColor}>
                              {isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}
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
                                        setShowModal(false);
                                        setLoading(false);
                                   }}>
                                   <ButtonText color={colorMode === 'light' ? theme['colors']['warmGray']['500'] : theme['colors']['coolGray']['300']}>{getTermFromDictionary(language, 'close_window')}</ButtonText>
                              </Button>
                              <Button
                                   bgColor={theme['colors']['primary']['500']}
                                   isDisabled={loading}
                                   onPress={async () => {
                                        setLoading(true);
                                        await completeAction(id, action, activeAccount, '', '', location, library.baseUrl, volume, holdType, holdNotificationPreferences, item).then(async (result) => {
                                             setResponse(result);
                                             if (result) {
                                                  if (result.success === true || result.success === 'true') {
                                                       queryClient.invalidateQueries({ queryKey: ['holds', activeAccount, library.baseUrl, language] });
                                                       queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                       /*await refreshProfile(library.baseUrl).then((profile) => {
                                               updateUser(profile);
                                               });*/
                                                  }

                                                  if (result?.confirmationNeeded && result.confirmationNeeded === true) {
                                                       let tmp = holdConfirmationResponse;
                                                       const obj = {
                                                            message: result.message,
                                                            title: result.title,
                                                            confirmationNeeded: result.confirmationNeeded ?? false,
                                                            confirmationId: result.confirmationId ?? null,
                                                            recordId: id ?? null,
                                                       };
                                                       tmp = _.merge(obj, tmp);
                                                       setHoldConfirmationResponse(tmp);
                                                  }

                                                  if (result?.shouldBeItemHold && result.shouldBeItemHold === true) {
                                                       let tmp = holdSelectItemResponse;
                                                       const obj = {
                                                            message: result.message,
                                                            title: 'Select an Item',
                                                            patronId: activeAccount,
                                                            pickupLocation: location,
                                                            bibId: id ?? null,
                                                            items: result.items ?? [],
                                                       };

                                                       tmp = _.merge(obj, tmp);
                                                       setHoldSelectItemResponse(tmp);
                                                  }

                                                  setLoading(false);
                                                  setShowModal(false);
                                                  if (result?.confirmationNeeded && result.confirmationNeeded) {
                                                       setHoldConfirmationIsOpen(true);
                                                  } else if (result?.shouldBeItemHold && result.shouldBeItemHold) {
                                                       setHoldItemSelectIsOpen(true);
                                                  } else {
                                                       setResponseIsOpen(true);
                                                  }
                                             }
                                        });
                                   }}>
                                   {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} /> : <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>}
                              </Button>
                         </ButtonGroup>
                    </ModalFooter>
               </ModalContent>
          </Modal>
     );
};