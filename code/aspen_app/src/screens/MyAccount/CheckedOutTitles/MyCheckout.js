import { MaterialIcons } from '@expo/vector-icons';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import { Actionsheet, Box, HStack, Icon, Pressable, Text, VStack } from 'native-base';
import React, { useState } from 'react';

// custom components and helper files
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getAuthor, getCheckedOutTo, getCleanTitle, getDueDate, getFormat, getRenewalCount, getTitle, isOverdue, willAutoRenew } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { renewCheckout, returnCheckout, viewOnlineItem, viewOverDriveItem } from '../../../util/accountActions';
import { stripHTML } from '../../../util/apiAuth';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MyCheckout = (props) => {
     const checkout = props.data;
     const setRenewConfirmationIsOpen = props.setRenewConfirmationIsOpen;
     const setRenewConfirmationResponse = props.setRenewConfirmationResponse;
     const reloadCheckouts = props.reloadCheckouts;
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     const openGroupedWork = (item, title) => {
          navigateStack('AccountScreenTab', 'MyCheckout', {
               id: item,
               title: getCleanTitle(title),
               url: library.baseUrl,
               userContext: user,
               libraryContext: library,
               prevRoute: 'MyCheckouts',
          });
     };

     const [access, setAccess] = useState(false);
     const [returning, setReturn] = useState(false);
     const [renewing, setRenew] = useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const [label, setAccessLabel] = React.useState('Access Online');
     const toggle = () => {
          setIsOpen(!isOpen);
     };

     let canRenew = !checkout.canRenew;
     let allowLinkedAccountAction = true;
     if (version < '22.05.00') {
          if (checkout.userId !== user.id) {
               allowLinkedAccountAction = false;
          }
     }

     let formatId;
     React.useEffect(() => {
          async function preloadTranslations() {
               if (checkout?.checkoutSource) {
                    if (checkout.checkoutSource === 'OverDrive') {
                         if (checkout.overdriveRead === 1) {
                              formatId = 'ebook-overdrive';
                              await getTranslationsWithValues('checkout_read_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveListen === 1) {
                              formatId = 'audiobook-overdrive';
                              await getTranslationsWithValues('checkout_listen_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveVideo === 1) {
                              formatId = 'video-streaming';
                              await getTranslationsWithValues('checkout_watch_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveMagazine === 1) {
                              formatId = 'magazine-overdrive';
                              await getTranslationsWithValues('checkout_read_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else {
                              await getTranslationsWithValues('checkout_access_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         }
                    } else {
                         await getTranslationsWithValues('checkout_access_online', checkout.checkoutSource, language, library.baseUrl).then((term) => {
                              setAccessLabel(_.toString(term));
                         });
                    }
               }
          }

          preloadTranslations();
     }, [language]);

     let returnEarly = false;
     if (checkout.canReturnEarly === 1 || checkout.canReturnEarly === '1' || checkout.canReturnEarly === true || checkout.canReturnEarly === 'true') {
          returnEarly = true;
     }

     let renewMessage = false;
     if (checkout.canRenew) {
          renewMessage = getTermFromDictionary(language, 'checkout_renew');
     } else {
          renewMessage = getTermFromDictionary(language, 'not_eligible_for_renewals');
     }
     if (checkout.autoRenew === '1' || checkout.autoRenew === 1) {
          renewMessage = getTermFromDictionary(language, 'if_eligible_auto_renew');
     }
     if (checkout.autoRenewError) {
          renewMessage = checkout.autoRenewError;
     }
     if (checkout.renewError) {
          renewMessage = checkout.renewError;
     }

     const key = 'medium_' + checkout.source + '_' + checkout.groupedWorkId;
     let url = library.baseUrl + '/bookcover.php?id=' + checkout.fullId + '&size=medium';

     let itemId = checkout.itemId;
     if (checkout.renewalId) {
          itemId = checkout.renewalId;
     }

     return (
          <Pressable onPress={toggle} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} maxW="75%">
                    <Image
                         alt={checkout.title}
                         source={url}
                         style={{
                              width: 100,
                              height: 150,
                              borderRadius: 4,
                         }}
                         placeholder={blurhash}
                         transition={1000}
                         contentFit="cover"
                    />
                    <VStack>
                         {getTitle(checkout.title)}
                         {isOverdue(checkout.overdue)}
                         {getAuthor(checkout.author)}
                         {getFormat(checkout.format, checkout.source)}
                         {getCheckedOutTo(checkout.user)}
                         {getDueDate(checkout.dueDate)}
                         {getRenewalCount(checkout.renewCount ?? 0, checkout.maxRenewals ?? null)}
                         {willAutoRenew(checkout.autoRenew ?? false, checkout.renewalDate)}
                    </VStack>
               </HStack>
               <Actionsheet isOpen={isOpen} onClose={toggle} size="full">
                    <Actionsheet.Content>
                         <Box w="100%" h={60} px={4} justifyContent="center">
                              <Text
                                   fontSize="18"
                                   color="gray.500"
                                   maxW="100%"
                                   flexWrap="wrap"
                                   isTruncated
                                   _dark={{
                                        color: 'gray.300',
                                   }}>
                                   {getTitle(checkout.title)}
                              </Text>
                         </Box>
                         <Actionsheet.Item
                              onPress={() => {
                                   openGroupedWork(checkout.groupedWorkId, checkout.title);
                                   toggle();
                              }}
                              startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6" />}>
                              {getTermFromDictionary(language, 'view_item_details')}
                         </Actionsheet.Item>
                         {renewMessage ? (
                              <Actionsheet.Item
                                   maxW="100%"
                                   isTruncated
                                   isDisabled={canRenew}
                                   isLoading={renewing}
                                   isLoadingText={getTermFromDictionary(language, 'renewing', true)}
                                   onPress={() => {
                                        setRenew(true);
                                        renewCheckout(checkout.barcode, checkout.recordId, checkout.source, itemId, library.baseUrl, checkout.userId).then((result) => {
                                             setRenew(false);

                                             if (result?.confirmRenewalFee && result.confirmRenewalFee) {
                                                  setRenewConfirmationResponse({
                                                       message: result.api.message,
                                                       title: result.api.title,
                                                       confirmRenewalFee: result.confirmRenewalFee ?? false,
                                                       action: result.api.action,
                                                       recordId: checkout.recordId ?? null,
                                                       barcode: checkout.barcode ?? null,
                                                       source: checkout.source ?? null,
                                                       itemId: itemId ?? null,
                                                       userId: checkout.userId ?? null,
                                                       renewType: 'single',
                                                  });
                                             }

                                             if (result?.confirmRenewalFee && result.confirmRenewalFee) {
                                                  setRenewConfirmationIsOpen(true);
                                             } else {
                                                  reloadCheckouts();
                                             }

                                             toggle();
                                        });
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="autorenew" color="trueGray.400" mr="1" size="6" />}>
                                   {stripHTML(renewMessage)}
                              </Actionsheet.Item>
                         ) : null}
                         {checkout.source === 'overdrive' ? (
                              <Actionsheet.Item
                                   isLoading={access}
                                   isLoadingText={getTermFromDictionary(language, 'accessing', true)}
                                   onPress={() => {
                                        setAccess(true);
                                        viewOverDriveItem(checkout.userId, formatId, checkout.overDriveId, library.baseUrl).then((result) => {
                                             setAccess(false);
                                             toggle();
                                        });
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6" />}>
                                   {label}
                              </Actionsheet.Item>
                         ) : null}
                         {checkout.accessOnlineUrl != null ? (
                              <>
                                   <Actionsheet.Item
                                        isLoading={access}
                                        isLoadingText={getTermFromDictionary(language, 'accessing', true)}
                                        onPress={() => {
                                             setAccess(true);
                                             viewOnlineItem(checkout.userId, checkout.recordId, checkout.source, checkout.accessOnlineUrl, library.baseUrl).then((result) => {
                                                  setAccess(false);
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6" />}>
                                        {label}
                                   </Actionsheet.Item>
                                   <Actionsheet.Item
                                        isLoading={returning}
                                        isLoadingText={getTermFromDictionary(language, 'returning', true)}
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl, version, checkout.transactionId).then((result) => {
                                                  setReturn(false);
                                                  reloadCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {getTermFromDictionary(language, 'checkout_return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                         {returnEarly && allowLinkedAccountAction ? (
                              <>
                                   <Actionsheet.Item
                                        isLoading={returning}
                                        isLoadingText={getTermFromDictionary(language, 'returning', true)}
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl, version, checkout.transactionId).then((result) => {
                                                  setReturn(false);
                                                  reloadCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {getTermFromDictionary(language, 'checkout_return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                    </Actionsheet.Content>
               </Actionsheet>
          </Pressable>
     );
};