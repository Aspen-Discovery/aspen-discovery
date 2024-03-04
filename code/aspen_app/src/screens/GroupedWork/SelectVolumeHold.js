import { useQuery } from '@tanstack/react-query';
import _ from 'lodash';
import { Button, CheckIcon, FormControl, Heading, Modal, Radio, Select } from 'native-base';
import React, { useState } from 'react';
import { Platform } from 'react-native';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { HoldsContext, LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getVolumes } from '../../util/api/item';
import { refreshProfile } from '../../util/api/user';
import { completeAction } from '../../util/recordActions';

const SelectVolumeHold = (props) => {
     const { id, title, action, volumeInfo, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState('');

     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);

     const isPlacingHold = action.includes('hold');

     let promptForHoldType = true;
     let typeOfHold = 'item';
     if (volumeInfo.majorityOfItemsHaveVolumes) {
          typeOfHold = 'volume';
     }
     if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
          typeOfHold = 'volume';
          promptForHoldType = false;
     }

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['volumes', id, library.baseUrl],
          queryFn: () => getVolumes(id, library.baseUrl),
          enabled: !!showModal,
     });

     const [holdType, setHoldType] = React.useState(typeOfHold);

     const [activeAccount, setActiveAccount] = React.useState(user.id);

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

     //console.log(pickupLocation);

     const [location, setLocation] = React.useState(pickupLocation);

     return (
          <>
               <Button
                    onPress={() => setShowModal(true)}
                    colorScheme="primary"
                    size="md"
                    _text={{
                         padding: 0,
                         textAlign: 'center',
                    }}>
                    {title}
               </Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              {status === 'loading' || isFetching ? (
                                   loadingSpinner()
                              ) : status === 'error' ? (
                                   loadError('Error', '')
                              ) : (
                                   <>
                                        {promptForHoldType ? (
                                             <FormControl>
                                                  <Radio.Group
                                                       name="holdTypeGroup"
                                                       defaultValue={holdType}
                                                       value={holdType}
                                                       onChange={(nextValue) => {
                                                            setHoldType(nextValue);
                                                       }}
                                                       accessibilityLabel="">
                                                       <Radio value="item" my={1} size="sm">
                                                            {getTermFromDictionary(language, 'first_available')}
                                                       </Radio>
                                                       <Radio value="volume" my={1} size="sm">
                                                            {getTermFromDictionary(language, 'specific_volume')}
                                                       </Radio>
                                                  </Radio.Group>
                                             </FormControl>
                                        ) : null}
                                        {holdType === 'volume' ? (
                                             <FormControl>
                                                  <FormControl.Label>{getTermFromDictionary(language, 'select_volume')}</FormControl.Label>
                                                  <Select
                                                       isReadOnly={Platform.OS === 'android'}
                                                       name="volumeForHold"
                                                       selectedValue={volume}
                                                       minWidth="200"
                                                       accessibilityLabel={getTermFromDictionary(language, 'select_volume')}
                                                       _selectedItem={{
                                                            bg: 'tertiary.300',
                                                            endIcon: <CheckIcon size="5" />,
                                                       }}
                                                       mt={1}
                                                       mb={2}
                                                       onValueChange={(itemValue) => setVolume(itemValue)}>
                                                       {_.map(data, function (item, index, array) {
                                                            return <Select.Item label={item.label} value={item.volumeId} key={index} />;
                                                       })}
                                                  </Select>
                                             </FormControl>
                                        ) : null}
                                        {_.size(locations) > 1 ? (
                                             <FormControl>
                                                  <FormControl.Label>{getTermFromDictionary(language, 'select_pickup_location')}</FormControl.Label>
                                                  <Select
                                                       isReadOnly={Platform.OS === 'android'}
                                                       name="pickupLocations"
                                                       selectedValue={location}
                                                       minWidth="200"
                                                       accessibilityLabel={getTermFromDictionary(language, 'select_pickup_location')}
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
                                        {_.size(accounts) > 0 ? (
                                             <FormControl>
                                                  <FormControl.Label>{isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}</FormControl.Label>
                                                  <Select
                                                       isReadOnly={Platform.OS === 'android'}
                                                       name="linkedAccount"
                                                       selectedValue={activeAccount}
                                                       minWidth="200"
                                                       accessibilityLabel={isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}
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
                                   </>
                              )}
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
                                        isLoadingText={isPlacingHold ? getTermFromDictionary(language, 'placing_hold', true) : getTermFromDictionary(language, 'checking_out', true)}
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, '', '', location, library.baseUrl, volume, holdType).then(async (result) => {
                                                  setResponse(result);
                                                  setShowModal(false);
                                                  if (result) {
                                                       setResponseIsOpen(true);
                                                       if (result.success) {
                                                            await refreshProfile(library.baseUrl).then((profile) => {
                                                                 updateUser(profile);
                                                            });
                                                       }
                                                  }
                                             });
                                             setLoading(false);
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

export default SelectVolumeHold;