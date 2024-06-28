import React from 'react';
import { CheckoutsContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { Box, Button, Text, Heading, Center, HStack, Icon, FlatList, AlertDialog } from 'native-base';
import { useIsFocused, useNavigation, useRoute } from '@react-navigation/native';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { navigateStack } from '../../helpers/RootNavigator';
import { Ionicons } from '@expo/vector-icons';
import _ from 'lodash';
import { loadingSpinner } from '../../components/loadingSpinner';
import { checkoutItem, placeHold } from '../../util/recordActions';
import { confirmHold } from '../../util/api/circulation';
import { getPatronCheckedOutItems, refreshProfile } from '../../util/api/user';
import { Platform } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';

export const SelfCheckOut = () => {
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const { user, cards, updateUser } = React.useContext(UserContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const [items, setItems] = React.useState([]);

     let startNew = useRoute().params?.startNew ?? false;
     let activeAccount = useRoute().params?.activeAccount ?? user;

     let barcode = useRoute().params?.barcode ?? null;
     let barcodeType = useRoute().params?.type ?? null;
     let sessionCheckouts = [];

     let checkoutResult = null;
     let checkoutHasError = false;
     let checkoutErrorMessageBody = null;
     let checkoutErrorMessageTitle = null;
     const [isProcessingCheckout, setIsProcessingCheckout] = React.useState(false);

     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [hasError, setHasError] = React.useState(false);
     const [errorBody, setErrorBody] = React.useState(null);
     const [errorTitle, setErrorTitle] = React.useState(null);

     //console.log(activeAccount);
     if (_.find(cards, ['ils_barcode', activeAccount])) {
          activeAccount = _.find(cards, ['ils_barcode', activeAccount]);
     } else if (_.find(cards, ['cat_username', activeAccount])) {
          activeAccount = _.find(cards, ['cat_username', activeAccount]);
     }

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     React.useEffect(() => {
          const updateCheckouts = navigation.addListener('focus', async () => {
               if (startNew) {
                    setItems([]);
                    startNew = false;
                    checkoutHasError = false;
               } else {
                    if (barcode) {
                         console.log('barcode: ' + barcode);
                         console.log('items:');
                         console.log(items);
                         console.log('session checkouts: ');
                         console.log(sessionCheckouts);
                         console.log('matching items: ');
                         console.log(_.find(sessionCheckouts, ['barcode', barcode]) ?? false);
                         //console.log(checkouts);
                         //console.log(_.find(checkouts, ['barcode', barcode]));
                         // check if item is already checked out
                         if (_.find(sessionCheckouts, ['barcode', barcode]) || _.find(checkouts, ['barcode', barcode])) {
                              // prompt error
                              setHasError(true);
                              setErrorBody(getTermFromDictionary(language, 'item_already_checked_out'));
                              setErrorTitle(getTermFromDictionary(language, 'unable_to_checkout_title'));
                              setIsOpen(true);
                         } else {
                              // do the checkout
                              setIsProcessingCheckout(true);
                              await checkoutItem(library.baseUrl, barcode, 'ils', activeAccount.userId ?? user.id, barcode, location.locationId, barcodeType, language).then((result) => {
                                   if (!result.success) {
                                        // prompt error
                                        setHasError(true);
                                        setErrorBody(result.message ?? getTermFromDictionary(language, 'unknown_error_checking_out'));
                                        setErrorTitle(result.title ?? getTermFromDictionary(language, 'unable_to_checkout_title'));
                                        setIsOpen(true);
                                   } else {
                                        let tmp = result.itemData;
                                        let updatedSession = _.concat(tmp, items);
                                        //console.log(tmp);
                                        //setItems(tmp);
                                        setItems([...items, tmp]);
                                        sessionCheckouts = updatedSession;

                                        queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language] });
                                        queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                        /*useQuery(['checkouts', user.id, library.baseUrl, language], () => getPatronCheckedOutItems('all', library.baseUrl, true, language), {
                                             onSuccess: (data) => {
                                                  updateCheckouts(data);
                                             },
                                        });*/
                                   }
                                   setIsProcessingCheckout(false);
                              });
                         }
                    }
               }
          });

          return updateCheckouts;
     }, [navigation, barcode, startNew]);

     const openScanner = async () => {
          barcode = null;
          navigateStack('SelfCheckTab', 'SelfCheckOutScanner', {
               activeAccount,
          });
     };

     const finishSession = () => {
          barcode = null;
          navigateStack('SelfCheckTab', 'FinishCheckOutSession');
     };

     const currentCheckoutHeader = () => {
          if (_.size(items) >= 1) {
               return (
                    <HStack space={4} justifyContent="space-between" pb={2}>
                         <Text bold fontSize="xs" w="70%">
                              {getTermFromDictionary(language, 'title')}
                         </Text>
                         <Text bold fontSize="xs" w="25%">
                              {getTermFromDictionary(language, 'checkout_due')}
                         </Text>
                    </HStack>
               );
          }
          return null;
     };

     const currentCheckOutItem = (item) => {
          if (item) {
               let title = item?.title ?? getTermFromDictionary(language, 'unknown_title');
               let barcode = item?.barcode ?? '';
               let dueDate = item?.due ?? '';
               return (
                    <HStack space={4} justifyContent="space-between">
                         <Text fontSize="xs" w="70%">
                              <Text bold>{title}</Text> ({barcode})
                         </Text>
                         <Text fontSize="xs" w="25%">
                              {dueDate}
                         </Text>
                    </HStack>
               );
          }
          return null;
     };

     const currentCheckOutEmpty = () => {
          return <Text>{getTermFromDictionary(language, 'no_items_checked_out')}</Text>;
     };

     const currentCheckOutFooter = () => {};

     return (
          <Box safeArea={5} w="100%">
               <Center pb={5}>
                    {activeAccount?.displayName ? (
                         <Text pb={3}>
                              {getTermFromDictionary(language, 'checking_out_as')} {activeAccount.displayName}
                         </Text>
                    ) : null}
                    <Button leftIcon={<Icon as={<Ionicons name="barcode-outline" />} size={6} mr="1" />} colorScheme="secondary" onPress={() => openScanner()}>
                         {getTermFromDictionary(language, 'add_new_item')}
                    </Button>
               </Center>
               <Heading fontSize="md" pb={2}>
                    {getTermFromDictionary(language, 'checked_out_during_session')}
               </Heading>
               {isProcessingCheckout ? loadingSpinner() : <FlatList data={items} keyExtractor={(item, index) => index.toString()} ListEmptyComponent={currentCheckOutEmpty()} ListHeaderComponent={currentCheckoutHeader()} renderItem={({ item }) => currentCheckOutItem(item)} />}
               <Center pt={5}>
                    <Button onPress={() => finishSession()} colorScheme="primary" size="sm">
                         {getTermFromDictionary(language, 'button_finish')}
                    </Button>
               </Center>
               <Center>
                    <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                         <AlertDialog.Content>
                              <AlertDialog.Header>{errorTitle}</AlertDialog.Header>
                              <AlertDialog.Body>{errorBody}</AlertDialog.Body>
                              <AlertDialog.Footer>
                                   <Button.Group space={3}>
                                        <Button variant="outline" colorScheme="primary" onPress={() => setIsOpen(false)}>
                                             {getTermFromDictionary(language, 'button_ok')}
                                        </Button>
                                   </Button.Group>
                              </AlertDialog.Footer>
                         </AlertDialog.Content>
                    </AlertDialog>
               </Center>
          </Box>
     );
};