import { Ionicons } from '@expo/vector-icons';
import _ from 'lodash';
import { Actionsheet, Box, Button, CheckIcon, CloseIcon, FormControl, HStack, Icon, Pressable, Select, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import Modal from 'react-native-modal';
import { getTermFromDictionary } from '../../../translations/TranslationService';

import { changeHoldPickUpLocation } from '../../../util/accountActions';

export const SelectPickupLocation = (props) => {
     const { locations, onClose, currentPickupId, holdId, userId, libraryContext, holdsContext, resetGroup, language } = props;
     let pickupLocation = _.findIndex(locations, function (o) {
          return o.locationId === currentPickupId;
     });

     let pickupId = currentPickupId;
     if (_.isNumber(pickupId)) {
          pickupId = _.toString(pickupId);
     }

     pickupLocation = _.nth(locations, pickupLocation);
     let pickupLocationCode = _.get(pickupLocation, 'code', '');
     if (_.isNumber(pickupLocationCode)) {
          pickupLocationCode = _.toString(pickupLocationCode);
     }
     pickupLocation = pickupId.concat('_', pickupLocationCode);

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
                    {getTermFromDictionary(language, 'change_location')}
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
                                        {getTermFromDictionary(language, 'change_hold_location')}
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
                                        <FormControl.Label>{getTermFromDictionary(language, 'select_new_pickup')}</FormControl.Label>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="pickupLocations"
                                             selectedValue={location}
                                             minWidth="200"
                                             accessibilityLabel={getTermFromDictionary(language, 'select_new_pickup')}
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
                                        {getTermFromDictionary(language, 'cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={getTermFromDictionary(language, 'updating', true)}
                                        onPress={() => {
                                             setLoading(true);
                                             changeHoldPickUpLocation(holdId, location, libraryContext.baseUrl, userId, language).then((r) => {
                                                  setShowModal(false);
                                                  resetGroup();
                                                  onClose(onClose);
                                                  setLoading(false);
                                             });
                                        }}>
                                        {getTermFromDictionary(language, 'change_location')}
                                   </Button>
                              </Button.Group>
                         </VStack>
                    </Box>
               </Modal>
          </>
     );
};