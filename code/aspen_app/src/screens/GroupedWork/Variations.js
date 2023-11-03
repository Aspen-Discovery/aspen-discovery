import { MaterialIcons } from '@expo/vector-icons';
import { useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { AlertDialog, Badge, Box, Button, Center, CheckIcon, FlatList, HStack, Icon, Select, Text, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { ActionButton } from '../../components/Action/ActionButton';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';

// custom components and helper files
import { HoldsContext, LanguageContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { confirmHold } from '../../util/api/circulation';
import { getFirstRecord, getRecords, getVariations } from '../../util/api/item';
import { refreshProfile } from '../../util/api/user';
import { stripHTML } from '../../util/apiAuth';
import { placeHold } from '../../util/recordActions';
import { getStatusIndicator } from './StatusIndicator';

export const Variations = (props) => {
     const queryClient = useQueryClient();
     const route = useRoute();
     const id = route.params.id;
     const prevRoute = route.params.prevRoute;
     const format = props.format;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const [isLoading, setLoading] = React.useState(false);
     const [confirmingHold, setConfirmingHold] = React.useState(false);
     const [responseIsOpen, setResponseIsOpen] = React.useState(false);
     const onResponseClose = () => setResponseIsOpen(false);
     const cancelResponseRef = React.useRef(null);
     const [response, setResponse] = React.useState('');
     const [holdConfirmationIsOpen, setHoldConfirmationIsOpen] = React.useState(false);
     const onHoldConfirmationClose = () => setHoldConfirmationIsOpen(false);
     const cancelHoldConfirmationRef = React.useRef(null);
     const [holdConfirmationResponse, setHoldConfirmationResponse] = React.useState('');

     const [holdItemSelectIsOpen, setHoldItemSelectIsOpen] = React.useState(false);
     const onHoldItemSelectClose = () => setHoldItemSelectIsOpen(false);
     const cancelHoldItemSelectRef = React.useRef(null);
     const [holdSelectItemResponse, setHoldSelectItemResponse] = React.useState('');
     const [placingItemHold, setPlacingItemHold] = React.useState(false);
     const [selectedItem, setSelectedItem] = React.useState('');

     const { data: record } = useQuery({
          queryKey: ['recordId', id, format, language, library.baseUrl],
          queryFn: () => getFirstRecord(id, format, language, library.baseUrl),
     });
     const recordId = record;
     const { status, data, error, isFetching } = useQuery({
          queryKey: ['variation', id, format, language, library.baseUrl],
          queryFn: () => getVariations(id, format, language, library.baseUrl),
          enabled: !!recordId,
     });

     const handleNavigation = (action) => {
          if (prevRoute === 'DiscoveryScreen' || prevRoute === 'SearchResults' || prevRoute === 'HomeScreen') {
               if (action.includes('Checkouts')) {
                    setResponseIsOpen(false);
                    navigateStack('AccountScreenTab', 'MyCheckouts', {});
               } else {
                    setResponseIsOpen(false);
                    navigateStack('AccountScreenTab', 'MyHolds', {});
               }
          } else {
               if (action.includes('Checkouts')) {
                    setResponseIsOpen(false);
                    navigate('MyCheckouts', {});
               } else {
                    setResponseIsOpen(false);
                    navigate('MyHolds', {});
               }
          }
     };

     const decodeMessage = (string) => {
          return stripHTML(string);
     };

     return (
          <>
               {isLoading || status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError(error, '')
               ) : (
                    <>
                         <FlatList
                              data={Object.keys(data.variations)}
                              renderItem={({ item }) => (
                                   <Variation
                                        records={data.variations[item]}
                                        format={format}
                                        volumeInfo={data.volumeInfo}
                                        id={id}
                                        prevRoute={prevRoute}
                                        setResponseIsOpen={setResponseIsOpen}
                                        responseIsOpen={responseIsOpen}
                                        onResponseClose={onResponseClose}
                                        cancelResponseRef={cancelResponseRef}
                                        response={response}
                                        setResponse={setResponse}
                                        setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                                        holdConfirmationIsOpen={holdConfirmationIsOpen}
                                        onHoldConfirmationClose={onHoldConfirmationClose}
                                        cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                                        holdConfirmationResponse={holdConfirmationResponse}
                                        setHoldConfirmationResponse={setHoldConfirmationResponse}
                                        setHoldItemSelectIsOpen={setHoldItemSelectIsOpen}
                                        holdItemSelectIsOpen={holdItemSelectIsOpen}
                                        onHoldItemSelectClose={onHoldItemSelectClose}
                                        cancelHoldItemSelectRef={cancelHoldItemSelectRef}
                                        holdSelectItemResponse={holdSelectItemResponse}
                                        setHoldSelectItemResponse={setHoldSelectItemResponse}
                                   />
                              )}
                         />
                         <Center>
                              <AlertDialog leastDestructiveRef={cancelResponseRef} isOpen={responseIsOpen} onClose={onResponseClose}>
                                   <AlertDialog.Content>
                                        <AlertDialog.Header>{response?.title ? response.title : 'Unknown Error'}</AlertDialog.Header>
                                        <AlertDialog.Body>{response?.message ? decodeMessage(response.message) : 'Unable to place hold for unknown error. Please contact the library.'}</AlertDialog.Body>
                                        <AlertDialog.Footer>
                                             <Button.Group space={3}>
                                                  {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                                  <Button variant="outline" colorScheme="primary" onPress={() => setResponseIsOpen(false)}>
                                                       {getTermFromDictionary(language, 'button_ok')}
                                                  </Button>
                                             </Button.Group>
                                        </AlertDialog.Footer>
                                   </AlertDialog.Content>
                              </AlertDialog>
                              <AlertDialog leastDestructiveRef={cancelHoldConfirmationRef} isOpen={holdConfirmationIsOpen} onClose={onHoldConfirmationClose}>
                                   <AlertDialog.Content>
                                        <AlertDialog.Header>{holdConfirmationResponse?.title ? holdConfirmationResponse.title : 'Unknown Error'}</AlertDialog.Header>
                                        <AlertDialog.Body>{holdConfirmationResponse?.message ? decodeMessage(holdConfirmationResponse.message) : 'Unable to place hold for unknown error. Please contact the library.'}</AlertDialog.Body>
                                        <AlertDialog.Footer>
                                             <Button.Group space={3}>
                                                  <Button variant="outline" colorScheme="primary" onPress={() => setHoldConfirmationIsOpen(false)}>
                                                       {getTermFromDictionary(language, 'close_window')}
                                                  </Button>
                                                  <Button
                                                       isLoading={confirmingHold}
                                                       isLoadingText="Placing hold..."
                                                       onPress={async () => {
                                                            setConfirmingHold(true);
                                                            await confirmHold(holdConfirmationResponse.recordId, holdConfirmationResponse.confirmationId, language, library.baseUrl).then(async (result) => {
                                                                 setResponse(result);
                                                                 queryClient.invalidateQueries({ queryKey: ['holds', library.baseUrl, language] });
                                                                 await refreshProfile(library.baseUrl).then((result) => {
                                                                      updateUser(result);
                                                                 });

                                                                 setHoldConfirmationIsOpen(false);
                                                                 setConfirmingHold(false);
                                                                 if (result) {
                                                                      setResponseIsOpen(true);
                                                                 }
                                                            });
                                                       }}>
                                                       Yes, Place Hold
                                                  </Button>
                                             </Button.Group>
                                        </AlertDialog.Footer>
                                   </AlertDialog.Content>
                              </AlertDialog>
                              <AlertDialog leastDestructiveRef={cancelHoldItemSelectRef} isOpen={holdItemSelectIsOpen} onClose={onHoldItemSelectClose}>
                                   <AlertDialog.Content>
                                        <AlertDialog.Header>{holdSelectItemResponse?.title ? holdSelectItemResponse.title : 'Unknown Error'}</AlertDialog.Header>
                                        <AlertDialog.Body>
                                             {holdSelectItemResponse?.message ? decodeMessage(holdSelectItemResponse.message) : 'Unable to place hold for unknown error. Please contact the library.'}
                                             {holdSelectItemResponse?.items ? (
                                                  <Select
                                                       isReadOnly={Platform.OS === 'android'}
                                                       name="itemForHold"
                                                       minWidth="200"
                                                       accessibilityLabel={getTermFromDictionary(language, 'select_item')}
                                                       _selectedItem={{
                                                            bg: 'tertiary.300',
                                                            endIcon: <CheckIcon size="5" />,
                                                       }}
                                                       mt={1}
                                                       mb={2}
                                                       onValueChange={(itemValue) => setSelectedItem(itemValue)}>
                                                       {_.map(holdSelectItemResponse.items, function (item, index, array) {
                                                            //let copy = copies[item];
                                                            console.log(item);
                                                            return <Select.Item label={item.callNumber} value={item.itemNumber} key={index} />;
                                                       })}
                                                  </Select>
                                             ) : null}
                                        </AlertDialog.Body>
                                        <AlertDialog.Footer>
                                             <Button.Group space={3}>
                                                  <Button variant="outline" colorScheme="primary" onPress={() => setHoldItemSelectIsOpen(false)}>
                                                       {getTermFromDictionary(language, 'close_window')}
                                                  </Button>
                                                  <Button
                                                       isLoading={placingItemHold}
                                                       isLoadingText="Placing hold..."
                                                       onPress={async () => {
                                                            setPlacingItemHold(true);
                                                            await placeHold(library.baseUrl, selectedItem, 'ils', holdSelectItemResponse.patronId, holdSelectItemResponse.pickupLocation, '', 'item', null, null, null, holdSelectItemResponse.bibId).then(async (result) => {
                                                                 setResponse(result);
                                                                 queryClient.invalidateQueries({ queryKey: ['holds', holdSelectItemResponse.patronId, library.baseUrl, language] });
                                                                 queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                                 /*await refreshProfile(library.baseUrl).then((result) => {
													 updateUser(result);
													 });*/

                                                                 setHoldItemSelectIsOpen(false);
                                                                 setPlacingItemHold(false);
                                                                 if (result) {
                                                                      setResponseIsOpen(true);
                                                                 }
                                                            });
                                                       }}>
                                                       Place Hold
                                                  </Button>
                                             </Button.Group>
                                        </AlertDialog.Footer>
                                   </AlertDialog.Content>
                              </AlertDialog>
                         </Center>
                    </>
               )}
          </>
     );
};

const Variation = (payload) => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { id, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef, prevRoute, format, volumeInfo, holdConfirmationResponse, setHoldConfirmationResponse, holdConfirmationIsOpen, setHoldConfirmationIsOpen, onHoldConfirmationClose, cancelHoldConfirmationRef, holdSelectItemResponse, setHoldSelectItemResponse, holdItemSelectIsOpen, setHoldItemSelectIsOpen, onHoldItemSelectClose, cancelHoldItemSelectRef } = payload;
     const variation = payload.records;
     const actions = variation.actions;
     const source = variation.source;
     const status = getStatusIndicator(variation.statusIndicator, language);
     const holdTypeForFormat = variation.holdType ?? 'default';
     const variationId = variation.variationId ?? null;
     const title = variation.title ?? null;
     const author = variation.author ?? null;
     const publisher = variation.publisher ?? null;
     const isbn = variation.isbn ?? null;
     const oclcNumber = variation.oclcNumber ?? null;

     let fullRecordId = _.split(variation.id, ':');
     const recordId = _.toString(fullRecordId[1]);

     useQuery(['records', id, source, format, language, library.baseUrl], () => getRecords(id, format, source, language, library.baseUrl), {
          placeholderData: [],
     });

     const handleOnPress = () => {
          navigate('CopyDetails', { id: id, format: format, prevRoute: prevRoute, type: 'groupedWork', recordId: null });
     };

     const handleOpenEditions = () => {
          navigate('EditionsModal', {
               id: id,
               format: format,
               recordId: recordId,
               source: source,
               volumeInfo: volumeInfo,
               prevRoute: prevRoute,
          });
     };

     return (
          <Box
               mt={5}
               mb={0}
               bgColor="white"
               _dark={{ bgColor: 'coolGray.900' }}
               p={3}
               rounded="8px"
               width={{
                    base: '100%',
                    lg: '100%',
               }}>
               <HStack justifyContent="space-between" alignItems="center" space={2} flex={1}>
                    <VStack space={1} maxW="40%" flex={1} justifyContent="center">
                         <Badge colorScheme={status.indicator} rounded="4px" _text={{ fontSize: 14 }} mb={0.5}>
                              {status.label}
                         </Badge>
                         {status.message ? (
                              <Text fontSize={8} textAlign="center" italic={1} maxW="75%">
                                   {status.message}
                              </Text>
                         ) : null}
                         {source === 'ils' ? (
                              <Button colorScheme="tertiary" variant="ghost" size="sm" leftIcon={<Icon as={MaterialIcons} name="location-pin" size="xs" mr="-1" />} onPress={handleOnPress}>
                                   {getTermFromDictionary(language, 'where_is_it')}
                              </Button>
                         ) : null}
                    </VStack>
                    <Button.Group width="50%" justifyContent="center" alignItems="stretch" direction={_.size(variation.actions) > 1 ? 'column' : 'row'}>
                         <FlatList
                              data={actions}
                              renderItem={({ item }) => (
                                   <ActionButton
                                        language={language}
                                        groupedWorkId={id}
                                        recordId={recordId}
                                        recordSource={source}
                                        fullRecordId={variation.id}
                                        variationId={variationId}
                                        holdTypeForFormat={holdTypeForFormat}
                                        title={title}
                                        author={author}
                                        publisher={publisher}
                                        isbn={isbn}
                                        oclcNumber={oclcNumber}
                                        actions={item}
                                        volumeInfo={volumeInfo}
                                        prevRoute={prevRoute}
                                        setResponseIsOpen={setResponseIsOpen}
                                        responseIsOpen={responseIsOpen}
                                        onResponseClose={onResponseClose}
                                        cancelResponseRef={cancelResponseRef}
                                        response={response}
                                        setResponse={setResponse}
                                        setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                                        holdConfirmationIsOpen={holdConfirmationIsOpen}
                                        onHoldConfirmationClose={onHoldConfirmationClose}
                                        cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                                        holdConfirmationResponse={holdConfirmationResponse}
                                        setHoldConfirmationResponse={setHoldConfirmationResponse}
                                        setHoldItemSelectIsOpen={setHoldItemSelectIsOpen}
                                        holdItemSelectIsOpen={holdItemSelectIsOpen}
                                        onHoldItemSelectClose={onHoldItemSelectClose}
                                        cancelHoldItemSelectRef={cancelHoldItemSelectRef}
                                        holdSelectItemResponse={holdSelectItemResponse}
                                        setHoldSelectItemResponse={setHoldSelectItemResponse}
                                   />
                              )}
                         />
                    </Button.Group>
               </HStack>
               <Center mt={2}>
                    <Button size="xs" colorScheme="tertiary" variant="outline" onPress={handleOpenEditions}>
                         {getTermFromDictionary(language, 'show_editions')}
                    </Button>
               </Center>
          </Box>
     );
};

export default Variations;