import { useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { CloseIcon, Modal, ModalBackdrop, ModalContent, ModalHeader, ModalCloseButton, ModalBody, ModalFooter, FormControl, FormControlLabel, FormControlLabelText, Heading, Select, Button, ButtonGroup, ButtonText, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, Icon, ChevronDownIcon, ButtonSpinner, SelectScrollView, Input, InputField, InputSlot, InputIcon } from '@gluestack-ui/themed';
import React from 'react';
import { EyeOff, Eye } from 'lucide-react-native';
import { useWindowDimensions } from 'react-native';
import RenderHtml from 'react-native-render-html';
import { HoldsContext, LibrarySystemContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { refreshProfile, updateAlternateLibraryCard } from '../../../util/api/user';
import { decodeHTML } from '../../../util/apiAuth';
import { completeAction } from '../../../util/recordActions';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { getCopies } from '../../../util/api/item';
import { HoldNotificationPreferences } from './HoldNotificationPreferences';
import { SelectItemHold } from './SelectItem';
import { SelectVolume } from './SelectVolume';

export const HoldPrompt = (props) => {
     const queryClient = useQueryClient();
     const {
          language,
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
     } = props;

     const [userHasAlternateLibraryCard, setUserHasAlternateLibraryCard] = React.useState(props.userHasAlternateLibraryCard ?? false);
     const [promptAlternateLibraryCard, setPromptAlternateLibraryCard] = React.useState(props.shouldPromptAlternateLibraryCard ?? false);
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);
     const [showAddAlternateLibraryCardModal, setShowAddAlternateLibraryCardModal] = React.useState(false);

     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const { theme, colorMode, textColor } = React.useContext(ThemeContext);

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['copies', id, variationId, language, library.baseUrl],
          queryFn: () => getCopies(id, language, variationId, library.baseUrl),
          enabled: holdTypeForFormat === 'item' || holdTypeForFormat === 'either',
     });

     let isPlacingHold = false;
     if (_.isObject(action)) {
          isPlacingHold = action.includes('hold');
     }

     let promptForHoldNotifications = user.promptForHoldNotifications ?? false;
     let holdNotificationInfo = user.holdNotificationInfo ?? [];

     let defaultEmailNotification = false;
     let defaultPhoneNotification = false;
     let defaultSMSNotification = false;
     if (promptForHoldNotifications && holdNotificationInfo?.preferences?.opac_hold_notify?.value) {
          const preferences = holdNotificationInfo.preferences.opac_hold_notify.value;
          defaultEmailNotification = _.includes(preferences, 'email');
          defaultPhoneNotification = _.includes(preferences, 'phone');
          defaultSMSNotification = _.includes(preferences, 'sms');
     }

     const [emailNotification, setEmailNotification] = React.useState(defaultEmailNotification);
     const [phoneNotification, setPhoneNotification] = React.useState(defaultPhoneNotification);
     const [smsNotification, setSMSNotification] = React.useState(defaultSMSNotification);
     const [smsCarrier, setSMSCarrier] = React.useState(holdNotificationInfo.preferences?.opac_default_sms_carrier?.value ?? -1);
     const [smsNumber, setSMSNumber] = React.useState(holdNotificationInfo.preferences?.opac_default_sms_notify?.value ?? null);
     const [phoneNumber, setPhoneNumber] = React.useState(holdNotificationInfo.preferences?.opac_default_phone?.value ?? null);
     const holdNotificationPreferences = {
          emailNotification: emailNotification,
          phoneNotification: phoneNotification,
          smsNotification: smsNotification,
          phoneNumber: phoneNumber,
          smsNumber: smsNumber,
          smsCarrier: smsCarrier,
     };

     let promptForHoldType = false;
     let typeOfHold = 'default';

     if (holdTypeForFormat) {
          typeOfHold = holdTypeForFormat;
     }

     if (volumeInfo.numItemsWithVolumes >= 1) {
          typeOfHold = 'item';
          promptForHoldType = true;
          if (volumeInfo.majorityOfItemsHaveVolumes) {
               typeOfHold = 'volume';
               promptForHoldType = true;
          }
          if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes)) {
               typeOfHold = 'volume';
               promptForHoldType = false;
          }
          if (volumeInfo.hasItemsWithoutVolumes) {
               promptForHoldType = true;
               typeOfHold = 'item';
          }
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);
     const [volume, setVolume] = React.useState('');
     const [item, setItem] = React.useState('');

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

     const [activeAccount, setActiveAccount] = React.useState(user.id ?? '');

     const updateActiveAccount = (newId) => {
          setActiveAccount(newId);
          if (newId !== user.id) {
               let newAccount = _.filter(accounts, ['id', newId]);
               if (newAccount[0]) {
                    newAccount = newAccount[0];

                    // we need to recalculate if the linked account is eligible for using alternate library cards
                    if (newAccount) {
                         if (typeof newAccount.alternateLibraryCard !== 'undefined') {
                              const alternateLibraryCardOptions = newAccount?.alternateLibraryCardOptions ?? [];
                              if (alternateLibraryCardOptions) {
                                   if (alternateLibraryCardOptions.showAlternateLibraryCard === '1' || alternateLibraryCardOptions.showAlternateLibraryCard === 1) {
                                        if (recordSource === 'cloud_library' && (alternateLibraryCardOptions.useAlternateLibraryCardForCloudLibrary === '1' || alternateLibraryCardOptions.useAlternateLibraryCardForCloudLibrary === 1)) {
                                             setPromptAlternateLibraryCard(true);
                                        }
                                   }

                                   if (newAccount.alternateLibraryCard && newAccount.alternateLibraryCard !== '') {
                                        if (alternateLibraryCardOptions?.showAlternateLibraryCardPassword === '1') {
                                             if (newAccount.alternateLibraryCardPassword !== '') {
                                                  setUserHasAlternateLibraryCard(true);
                                             } else {
                                                  setUserHasAlternateLibraryCard(false);
                                             }
                                        } else {
                                             setUserHasAlternateLibraryCard(true);
                                        }
                                   } else {
                                        setUserHasAlternateLibraryCard(false);
                                   }

                                   if (alternateLibraryCardOptions?.alternateLibraryCardLabel) {
                                        cardLabel = alternateLibraryCardOptions.alternateLibraryCardLabel;
                                   }

                                   if (alternateLibraryCardOptions?.alternateLibraryCardPasswordLabel) {
                                        passwordLabel = alternateLibraryCardOptions.alternateLibraryCardPasswordLabel;
                                   }

                                   if (alternateLibraryCardOptions?.alternateLibraryCardFormMessage) {
                                        formMessage = decodeHTML(alternateLibraryCardOptions.alternateLibraryCardFormMessage);
                                   }

                                   if (alternateLibraryCardOptions?.showAlternateLibraryCardPassword) {
                                        if (alternateLibraryCardOptions.showAlternateLibraryCardPassword === '1' || alternateLibraryCardOptions.showAlternateLibraryCardPassword === 1) {
                                             showAlternateLibraryCardPassword = true;
                                        }
                                   }
                              } else {
                                   setUserHasAlternateLibraryCard(false);
                                   setPromptAlternateLibraryCard(false);
                              }
                         } else {
                              setUserHasAlternateLibraryCard(false);
                              setPromptAlternateLibraryCard(false);
                         }
                    }
               }
          } else {
               //revert back to primary user id
               setUserHasAlternateLibraryCard(props.userHasAlternateLibraryCard);
               setPromptAlternateLibraryCard(props.shouldPromptAlternateLibraryCard);

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
          }
     };

     let userPickupLocationId = user.pickupLocationId ?? user.homeLocationId;
     if (_.isNumber(user.pickupLocationId)) {
          userPickupLocationId = _.toString(user.pickupLocationId);
     }

     let pickupLocation = '';
     if (_.size(locations) > 1) {
          const userPickupLocation = _.filter(locations, { locationId: userPickupLocationId });
          if (!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
               pickupLocation = userPickupLocation[0];
               if (_.isObject(pickupLocation)) {
                    pickupLocation = pickupLocation.code;
               }
          }
     } else {
          pickupLocation = locations[0];
          if (_.isObject(pickupLocation)) {
               pickupLocation = pickupLocation.code;
          }
     }

     // console.log(pickupLocation);

     const [location, setLocation] = React.useState(pickupLocation);

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

     if (showAddAlternateLibraryCardModal) {
          return (
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
                                                       setShowAddAlternateLibraryCardModal(false);
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
     }

     return (
          <>
               <Button minWidth="100%" maxWidth="100%" bgColor={theme['colors']['primary']['500']} onPress={() => setShowModal(true)}>
                    <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>
               </Button>
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
                              {promptForHoldNotifications ? (
                                   <HoldNotificationPreferences
                                        user={user}
                                        language={language}
                                        emailNotification={emailNotification}
                                        setEmailNotification={setEmailNotification}
                                        phoneNotification={phoneNotification}
                                        setPhoneNotification={setPhoneNotification}
                                        smsNotification={smsNotification}
                                        setSMSNotification={setSMSNotification}
                                        smsCarrier={smsCarrier}
                                        setSMSCarrier={setSMSCarrier}
                                        smsNumber={smsNumber}
                                        setSMSNumber={setSMSNumber}
                                        phoneNumber={phoneNumber}
                                        setPhoneNumber={setPhoneNumber}
                                        url={library.baseUrl}
                                        textColor={textColor}
                                        theme={theme}
                                   />
                              ) : null}
                              {!isFetching && (holdTypeForFormat === 'either' || holdTypeForFormat === 'item') ? <SelectItemHold theme={theme} id={id} item={item} setItem={setItem} language={language} data={data} holdType={holdType} setHoldType={setHoldType} holdTypeForFormat={holdTypeForFormat} url={library.baseUrl} showModal={showModal} textColor={textColor} /> : null}
                              {promptForHoldType || holdType === 'volume' ? <SelectVolume theme={theme} id={id} language={language} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType} holdType={holdType} setHoldType={setHoldType} showModal={showModal} url={library.baseUrl} textColor={textColor} /> : null}
                              {_.isArray(locations) && _.size(locations) > 1 && !isEContent ? (
                                   <FormControl mt="$1">
                                        <FormControlLabel>
                                             <FormControlLabelText size="sm" color={textColor}>
                                                  {getTermFromDictionary(language, 'select_pickup_location')}
                                             </FormControlLabelText>
                                        </FormControlLabel>
                                        <Select name="pickupLocations" selectedValue={location} minWidth={200} mt="$1" mb="$2" onValueChange={(itemValue) => setLocation(itemValue)}>
                                             <SelectTrigger variant="outline" size="md">
                                                  {locations.map((selectedLocation, index) => {
                                                       if (selectedLocation.code === location) {
                                                            return <SelectInput value={selectedLocation.name} color={textColor} />;
                                                       }
                                                  })}
                                                  <SelectIcon mr="$3" as={ChevronDownIcon} color={textColor} />
                                             </SelectTrigger>
                                             <SelectPortal>
                                                  <SelectBackdrop />
                                                  <SelectContent p="$5">
                                                       <SelectDragIndicatorWrapper>
                                                            <SelectDragIndicator />
                                                       </SelectDragIndicatorWrapper>
                                                       <SelectScrollView>
                                                            {locations.map((availableLocations, index) => {
                                                                 if (availableLocations.code === location) {
                                                                      return <SelectItem label={availableLocations.name} value={availableLocations.code} key={index} bgColor={theme['colors']['tertiary']['300']} />;
                                                                 }
                                                                 return <SelectItem label={availableLocations.name} value={availableLocations.code} key={index} />;
                                                            })}
                                                       </SelectScrollView>
                                                  </SelectContent>
                                             </SelectPortal>
                                        </Select>
                                   </FormControl>
                              ) : null}
                              {_.isArray(accounts) && _.size(accounts) > 0 ? (
                                   <FormControl>
                                        <FormControlLabel>
                                             <FormControlLabelText color={textColor}>{isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}</FormControlLabelText>
                                        </FormControlLabel>
                                        <Select name="linkedAccount" selectedValue={activeAccount} minWidth={200} mt="$1" mb="$3" onValueChange={(itemValue) => updateActiveAccount(itemValue)}>
                                             <SelectTrigger variant="outline" size="md">
                                                  {accounts.map((item, index) => {
                                                       if (item.id === activeAccount) {
                                                            return <SelectInput value={item.displayName} color={textColor} />;
                                                       } else if (user.id === activeAccount) {
                                                            return <SelectInput value={user.displayName} color={textColor} />;
                                                       }
                                                  })}
                                                  <SelectIcon mr="$3" as={ChevronDownIcon} color={textColor} />
                                             </SelectTrigger>
                                             <SelectPortal>
                                                  <SelectBackdrop />
                                                  <SelectContent>
                                                       <SelectDragIndicatorWrapper>
                                                            <SelectDragIndicator />
                                                       </SelectDragIndicatorWrapper>
                                                       <SelectScrollView>
                                                            <SelectItem label={user.displayName} value={user.id} color={textColor} />
                                                            {accounts.map((item, index) => {
                                                                 return <SelectItem label={item.displayName} value={item.id} key={index} color={textColor} />;
                                                            })}
                                                       </SelectScrollView>
                                                  </SelectContent>
                                             </SelectPortal>
                                        </Select>
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
                                   {promptAlternateLibraryCard && !userHasAlternateLibraryCard ? (
                                        <Button
                                             bgColor={theme['colors']['primary']['500']}
                                             onPress={() => {
                                                  setShowModal(false);
                                                  setShowAddAlternateLibraryCardModal(true);
                                             }}>
                                             <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'next')}</ButtonText>
                                        </Button>
                                   ) : (
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
                                   )}
                              </ButtonGroup>
                         </ModalFooter>
                    </ModalContent>
               </Modal>
          </>
     );
};