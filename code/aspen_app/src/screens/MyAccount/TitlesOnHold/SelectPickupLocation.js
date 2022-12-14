import _ from 'lodash';
import { translate } from '../../../translations/translations';
import { Platform } from 'react-native';
import { Actionsheet, Box, Button, FormControl, Select, CheckIcon, CloseIcon, Icon, Pressable, HStack, VStack } from 'native-base';
import Modal from 'react-native-modal';
import { Ionicons } from '@expo/vector-icons';

import { changeHoldPickUpLocation } from '../../../util/accountActions';
import React from 'react';

export const SelectPickupLocation = (props) => {
     const { locations, onClose, currentPickupId, holdId, userId, libraryContext, holdsContext, resetGroup } = props;
     let pickupLocation = _.findIndex(locations, function (o) {
          return o.locationId === currentPickupId;
     });
     pickupLocation = _.nth(locations, pickupLocation);
     let pickupLocationCode = _.get(pickupLocation, 'code', '');
     pickupLocation = currentPickupId.concat('_', pickupLocationCode);

     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);
     let [location, setLocation] = React.useState(pickupLocation);

     return (
          <>
               <Actionsheet.Item
                    startIcon={<Icon as={Ionicons} name="location" color="trueGray.400" mr="1" size="6" />}
                    onPress={() => {
                         setShowModal(true);
                    }}>
                    {translate('pickup_locations.change_location')}
               </Actionsheet.Item>
               <Modal
                    isVisible={showModal}
                    avoidKeyboard={true}
                    onBackdropPress={() => {
                         setShowModal(false);
                    }}>
                    <Box
                         bgColor="muted.50"
                         rounded="md"
                         p={1}
                         _text={{ color: 'text.900' }}
                         _dark={{
                              bg: 'muted.800',
                              _text: { color: 'text.50' },
                         }}>
                         <VStack space={3}>
                              <HStack
                                   p={4}
                                   borderBottomWidth="1"
                                   bg="muted.50"
                                   justifyContent="space-between"
                                   alignItems="flex-start"
                                   borderColor="muted.300"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Box
                                        _text={{
                                             color: 'text.900',
                                             fontSize: 'md',
                                             fontWeight: 'semibold',
                                             lineHeight: 'sm',
                                        }}
                                        _dark={{
                                             _text: { color: 'text.50' },
                                        }}>
                                        {translate('pickup_locations.change_hold_location')}
                                   </Box>
                                   <Pressable onPress={() => setShowModal(false)}>
                                        <CloseIcon
                                             zIndex="1"
                                             colorScheme="coolGray"
                                             p="2"
                                             bg="transparent"
                                             borderRadius="sm"
                                             _icon={{
                                                  color: 'muted.500',
                                                  size: '4',
                                             }}
                                             _dark={{
                                                  _icon: { color: 'muted.400' },
                                                  _hover: { bg: 'muted.700' },
                                                  _pressed: { bg: 'muted.600' },
                                             }}
                                        />
                                   </Pressable>
                              </HStack>
                              <Box p={4} _text={{ color: 'text.900' }} _hover={{ bg: 'muted.200' }} _pressed={{ bg: 'muted.300' }} _dark={{ _text: { color: 'text.50' } }}>
                                   <FormControl>
                                        <FormControl.Label>{translate('pickup_locations.select_new_pickup')}</FormControl.Label>
                                        <Select
                                             name="pickupLocations"
                                             selectedValue={location}
                                             minWidth="200"
                                             accessibilityLabel="Select a new pickup location"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={3}
                                             _actionSheet={{
                                                  useRNModal: Platform.OS === 'ios',
                                             }}
                                             onValueChange={(itemValue) => setLocation(itemValue)}>
                                             {locations.map((item, index) => {
                                                  const locationId = item.locationId;
                                                  const code = item.code;
                                                  const id = locationId.concat('_', code);
                                                  return <Select.Item value={id} label={item.name} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              </Box>
                              <Button.Group
                                   p={4}
                                   flexDirection="row"
                                   justifyContent="flex-end"
                                   flexWrap="wrap"
                                   bg="muted.50"
                                   borderColor="muted.300"
                                   borderTopWidth="1"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Button
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                        }}>
                                        {translate('general.cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Updating..."
                                        onPress={() => {
                                             setLoading(true);
                                             changeHoldPickUpLocation(holdId, location, libraryContext.baseUrl, userId).then((r) => {
                                                  setShowModal(false);
                                                  resetGroup();
                                                  onClose(onClose);
                                                  setLoading(false);
                                             });
                                        }}>
                                        {translate('pickup_locations.change_location')}
                                   </Button>
                              </Button.Group>
                         </VStack>
                    </Box>
               </Modal>
          </>
     );
};