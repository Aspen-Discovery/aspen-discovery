import React from 'react';
import { Platform } from 'react-native';
import { HoldsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { Button, Modal, Heading, FormControl, Select, CheckIcon } from 'native-base';
import _ from 'lodash';
import { completeAction } from '../../../screens/GroupedWork/Record';
import { getPatronHolds, refreshProfile } from '../../../util/api/user';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { SelectVolume } from './SelectVolume';
import { HoldNotificationPreferences } from './HoldNotificationPreferences';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { getCopies } from '../../../util/api/item';
import { SelectItem } from './SelectItem';

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
     } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);

     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateHolds } = React.useContext(HoldsContext);

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['copies', id, language, library.baseUrl],
          queryFn: () => getCopies(id, language, variationId, library.baseUrl),
          enabled: holdTypeForFormat === 'item' || holdTypeForFormat === 'either',
     });

     const isPlacingHold = action.includes('hold');
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

     if (volumeInfo.numItemsWithVolumes >= 1) {
          typeOfHold = 'item';
          promptForHoldType = true;
          if (volumeInfo.majorityOfItemsHaveVolumes) {
               typeOfHold = 'volume';
               promptForHoldType = true;
          }
          if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
               typeOfHold = 'volume';
               promptForHoldType = false;
          }
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);
     const [volume, setVolume] = React.useState('');
     const [item, setItem] = React.useState('');

     const [activeAccount, setActiveAccount] = React.useState(user.id ?? '');

     const userPickupLocation = _.filter(locations, { locationId: user.pickupLocationId });
     let pickupLocation = '';
     if (!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
          pickupLocation = userPickupLocation[0];
          if (_.isObject(pickupLocation)) {
               pickupLocation = pickupLocation.code;
          }
     }

     const [location, setLocation] = React.useState(pickupLocation);

     return (
          <>
               <Button onPress={() => setShowModal(true)}>{title}</Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
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
                                   />
                              ) : null}
                              {!isFetching && (holdTypeForFormat === 'either' || holdTypeForFormat === 'item') ? <SelectItem id={id} item={item} setItem={setItem} language={language} data={data} holdType={holdType} setHoldType={setHoldType} holdTypeForFormat={holdTypeForFormat} url={library.baseUrl} showModal={showModal} /> : null}
                              {promptForHoldType || holdType === 'volume' ? <SelectVolume id={id} language={language} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType} holdType={holdType} setHoldType={setHoldType} showModal={showModal} url={library.baseUrl} /> : null}
                              {_.isArray(locations) && _.size(locations) > 1 && !isEContent ? (
                                   <FormControl>
                                        <FormControl.Label>{getTermFromDictionary(language, 'select_pickup_location')}</FormControl.Label>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="pickupLocations"
                                             selectedValue={location}
                                             minWidth="200"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={2}
                                             onValueChange={(itemValue) => setLocation(itemValue)}>
                                             {locations.map((location, index) => {
                                                  return <Select.Item label={location.name} value={location.code} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                              {_.isArray(accounts) && _.size(accounts) > 0 ? (
                                   <FormControl>
                                        <FormControl.Label>{isPlacingHold ? getTermFromDictionary('en', 'linked_place_hold_for_account') : getTermFromDictionary('en', 'linked_checkout_to_account')}</FormControl.Label>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="linkedAccount"
                                             selectedValue={activeAccount}
                                             minWidth="200"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={3}
                                             onValueChange={(itemValue) => setActiveAccount(itemValue)}>
                                             <Select.Item label={user.displayName} value={user.id} />
                                             {accounts.map((item, index) => {
                                                  return <Select.Item label={item.displayName} value={item.id} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button
                                        colorScheme="muted"
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                             setLoading(false);
                                        }}>
                                        {getTermFromDictionary(language, 'close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
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
                                        {title}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};