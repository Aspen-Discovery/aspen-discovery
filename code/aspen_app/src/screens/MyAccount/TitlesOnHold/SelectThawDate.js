import { translate } from '../../../translations/translations';
import { freezeHold, freezeHolds } from '../../../util/accountActions';
import React from 'react';
import { MaterialIcons } from '@expo/vector-icons';
import { Actionsheet, Box, Button, CloseIcon, Icon, Pressable, HStack, VStack } from 'native-base';
import Modal from 'react-native-modal';
import DateTimePicker from '@react-native-community/datetimepicker';

export const SelectThawDate = (props) => {
     const { libraryContext, onClose, freezeId, recordId, source, userId, resetGroup } = props;
     let data = props.data;
     let count = props.count;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);

     const today = new Date();
     const minDate = today.setDate(today.getDate() + 7);
     const [date, setDate] = React.useState(new Date());
     const [show, setShow] = React.useState(false);

     const onChange = (event, selectedDate) => {
          const currentDate = selectedDate;
          setShow(false);
          setDate(currentDate);
     };

     const createActionsheetItem = () => {
          if (data) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              setShowModal(true);
                         }}>
                         {translate('holds.freeze_all_holds_count', { num: count })}
                    </Actionsheet.Item>
               );
          } else {
               return (
                    <Actionsheet.Item
                         startIcon={<Icon as={MaterialIcons} name="pause" color="trueGray.400" mr="1" size="6" />}
                         onPress={() => {
                              onClose();
                              setShowModal(true);
                         }}>
                         {translate('holds.freeze_hold')}
                    </Actionsheet.Item>
               );
          }
     };

     return (
          <>
               {createActionsheetItem()}
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
                                        {data ? translate('holds.freeze_all_holds') : translate('holds.freeze_hold')}
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
                                   <DateTimePicker testID="dateTimePicker" value={date} mode="date" display="default" minimumDate={minDate} onChange={onChange} />
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

                                   {data ? (
                                        <Button
                                             isLoading={loading}
                                             isLoadingText={translate('holds.freezing')}
                                             onPress={() => {
                                                  setLoading(true);
                                                  freezeHolds(data, libraryContext.baseUrl, date).then((result) => {
                                                       resetGroup();
                                                       setLoading(false);
                                                       onClose();
                                                       setShowModal(false);
                                                  });
                                             }}>
                                             {translate('holds.freeze_holds')}
                                        </Button>
                                   ) : (
                                        <Button
                                             isLoading={loading}
                                             isLoadingText={translate('holds.freezing')}
                                             onPress={() => {
                                                  setLoading(true);
                                                  freezeHold(freezeId, recordId, source, libraryContext.baseUrl, userId, date).then((result) => {
                                                       resetGroup();
                                                       setLoading(false);
                                                       onClose();
                                                       setShowModal(false);
                                                  });
                                             }}>
                                             {translate('holds.freeze_hold')}
                                        </Button>
                                   )}
                              </Button.Group>
                         </VStack>
                    </Box>
               </Modal>
          </>
     );
};