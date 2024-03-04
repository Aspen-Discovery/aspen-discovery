import _ from 'lodash';
import { Button, Center, CheckIcon, FormControl, Heading, Modal, Select } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { HoldsContext, LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { refreshProfile } from '../../util/api/user';
import { completeAction } from '../../util/recordActions';
import { SelectVolume } from './SelectVolume';

const SelectLinkedAccount = (props) => {
     const { id, action, title, volumeInfo, prevRoute, isEContent, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = props;
     const [loading, setResponseLoading] = React.useState(false);
     const [showPrompt, setShowPrompt] = React.useState(false);

     const isPlacingHold = action.includes('hold');

     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);

     let shouldDisplayVolumes = false;
     let typeOfHold = 'default';
     let promptForHoldType = false;

     if (volumeInfo.numItemsWithVolumes > 0) {
          typeOfHold = 'item';
          shouldDisplayVolumes = true;
          promptForHoldType = true;

          if (volumeInfo.majorityOfItemsHaveVolumes) {
               typeOfHold = 'volume';
          }

          if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
               typeOfHold = 'volume';
               promptForHoldType = false;
          }
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);
     const [volume, setVolume] = React.useState(null);

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

     const [activeAccount, setActiveAccount] = React.useState(user.id);
     let availableAccounts = [];
     if (_.size(accounts) > 0) {
          availableAccounts = Object.values(accounts);
     }

     return (
          <Center>
               <Button
                    size="md"
                    colorScheme="primary"
                    variant="solid"
                    _text={{
                         padding: 0,
                         textAlign: 'center',
                    }}
                    onPress={() => setShowPrompt(true)}>
                    {title}
               </Button>
               <Modal isOpen={showPrompt} onClose={() => setShowPrompt(false)} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              {shouldDisplayVolumes ? <SelectVolume language={language} id={id} holdType={holdType} setHoldType={setHoldType} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType} /> : null}
                              {_.size(locations) > 1 && !isEContent ? (
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
                              <FormControl pb={5}>
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
                                        {availableAccounts.map((item, index) => {
                                             return <Select.Item label={item.displayName} value={item.id} key={index} />;
                                        })}
                                   </Select>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button
                                        variant="outline"
                                        onPress={() => {
                                             setShowPrompt(false);
                                             setResponseLoading(false);
                                        }}>
                                        {getTermFromDictionary(language, 'close_button')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={isPlacingHold ? getTermFromDictionary(language, 'placing_hold', true) : getTermFromDictionary(language, 'checking_out', true)}
                                        onPress={async () => {
                                             setResponseLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, location, library.baseUrl, volume, holdType).then(async (result) => {
                                                  setResponse(result);
                                                  setShowPrompt(false);
                                                  if (result) {
                                                       setResponseIsOpen(true);
                                                       if (result.success) {
                                                            await refreshProfile(library.baseUrl).then((profile) => {
                                                                 updateUser(profile);
                                                            });
                                                       }
                                                  }
                                             });
                                             setResponseLoading(false);
                                        }}>
                                        {title}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default SelectLinkedAccount;