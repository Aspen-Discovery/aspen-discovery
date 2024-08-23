import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogBody, AlertDialogFooter, AlertDialogBackdrop, Badge, BadgeText, FlatList, Heading, Select, VStack, Button, ButtonGroup, ButtonIcon, ButtonText, Box, Center, HStack, Text, SafeAreaView, ScrollView, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, Icon, SelectScrollView } from '@gluestack-ui/themed';
import { MapPinIcon } from 'lucide-react-native';
import { useRoute } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { CheckIcon } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { ActionButton } from '../../components/Action/ActionButton';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';

// custom components and helper files
import { HoldsContext, LanguageContext, LibrarySystemContext, ThemeContext, UserContext } from '../../context/initialContext';
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
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
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
                    <Box padding="$5">{loadingSpinner()}</Box>
               ) : status === 'error' ? (
                    <Box padding="$5">{loadError(error, '')}</Box>
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
                                   <AlertDialogBackdrop />
                                   <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                                        <AlertDialogHeader>
                                             <Heading color={textColor}>{response?.title ? response.title : 'Unknown Error'}</Heading>
                                        </AlertDialogHeader>
                                        <AlertDialogBody>
                                             <Text color={textColor}>{response?.message ? decodeMessage(response.message) : 'Unable to place hold for unknown error. Please contact the library.'}</Text>
                                        </AlertDialogBody>
                                        <AlertDialogFooter>
                                             <ButtonGroup space="sm">
                                                  {response?.action ? (
                                                       <Button bgColor={theme['colors']['primary']['500']} onPress={() => handleNavigation(response.action)}>
                                                            <ButtonText color={theme['colors']['primary']['500-text']}>{response.action}</ButtonText>
                                                       </Button>
                                                  ) : null}
                                                  <Button variant="link" onPress={() => setResponseIsOpen(false)}>
                                                       <ButtonText color={theme['colors']['primary']['500']}>{getTermFromDictionary(language, 'button_ok')}</ButtonText>
                                                  </Button>
                                             </ButtonGroup>
                                        </AlertDialogFooter>
                                   </AlertDialogContent>
                              </AlertDialog>
                              <AlertDialog leastDestructiveRef={cancelHoldConfirmationRef} isOpen={holdConfirmationIsOpen} onClose={onHoldConfirmationClose}>
                                   <AlertDialogBackdrop />
                                   <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                                        <AlertDialogHeader>
                                             <Heading color={textColor}>{holdConfirmationResponse?.title ? holdConfirmationResponse.title : 'Unknown Error'}</Heading>
                                        </AlertDialogHeader>
                                        <AlertDialogBody>
                                             <Text color={textColor}>{holdConfirmationResponse?.message ? decodeMessage(holdConfirmationResponse.message) : 'Unable to place hold for unknown error. Please contact the library.'}</Text>
                                        </AlertDialogBody>
                                        <AlertDialogFooter>
                                             <ButtonGroup space="md">
                                                  <Button variant="link" onPress={() => setHoldConfirmationIsOpen(false)}>
                                                       <ButtonText color={theme['colors']['primary']['500']}>{getTermFromDictionary(language, 'close_window')}</ButtonText>
                                                  </Button>
                                                  <Button
                                                       isLoading={confirmingHold}
                                                       isLoadingText="Placing hold..."
                                                       variant="solid"
                                                       bgColor={theme['colors']['primary']['500']}
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
                                                       <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'confirm_place_hold')}</ButtonText>
                                                  </Button>
                                             </ButtonGroup>
                                        </AlertDialogFooter>
                                   </AlertDialogContent>
                              </AlertDialog>
                              <AlertDialog leastDestructiveRef={cancelHoldItemSelectRef} isOpen={holdItemSelectIsOpen} onClose={onHoldItemSelectClose}>
                                   <AlertDialogBackdrop />
                                   <AlertDialogContent bgColor={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['700']}>
                                        <AlertDialogHeader>
                                             <Heading color={textColor}>{holdSelectItemResponse?.title ? holdSelectItemResponse.title : 'Unknown Error'}</Heading>
                                        </AlertDialogHeader>
                                        <AlertDialogBody>
                                             <Text color={textColor}>{holdSelectItemResponse?.message ? decodeMessage(holdSelectItemResponse.message) : 'Unable to place hold for unknown error. Please contact the library.'}</Text>
                                             {holdSelectItemResponse?.items ? (
                                                  <Select name="itemForHold" minWidth={200} accessibilityLabel={getTermFromDictionary(language, 'select_item')} mt="$1" mb="$2" onValueChange={(itemValue) => setSelectedItem(itemValue)}>
                                                       <SelectTrigger>
                                                            <SelectInput placeholder="Select option" />
                                                            <SelectIcon mr="$3">
                                                                 <Icon as={CheckIcon} />
                                                            </SelectIcon>
                                                       </SelectTrigger>
                                                       <SelectPortal>
                                                            <SelectBackdrop />
                                                            <SelectContent>
                                                                 <SelectDragIndicatorWrapper>
                                                                      <SelectDragIndicator />
                                                                 </SelectDragIndicatorWrapper>
                                                                 <SelectScrollView>
                                                                      {_.map(holdSelectItemResponse.items, function (item, index, array) {
                                                                           return <SelectItem label={item.callNumber} value={item.itemNumber} key={index} />;
                                                                      })}
                                                                 </SelectScrollView>
                                                            </SelectContent>
                                                       </SelectPortal>
                                                  </Select>
                                             ) : null}
                                        </AlertDialogBody>
                                        <AlertDialogFooter>
                                             <ButtonGroup space="md">
                                                  <Button variant="link" onPress={() => setHoldItemSelectIsOpen(false)}>
                                                       <ButtonText color={theme['colors']['primary']['500']}>{getTermFromDictionary(language, 'close_window')}</ButtonText>
                                                  </Button>
                                                  <Button
                                                       isLoading={placingItemHold}
                                                       isLoadingText="Placing hold..."
                                                       variant="solid"
                                                       bgColor={theme['colors']['primary']['500']}
                                                       onPress={async () => {
                                                            setPlacingItemHold(true);
                                                            await placeHold(library.baseUrl, selectedItem, 'ils', holdSelectItemResponse.patronId, holdSelectItemResponse.pickupLocation, '', 'item', null, null, null, holdSelectItemResponse.bibId, language).then(async (result) => {
                                                                 setResponse(result);
                                                                 queryClient.invalidateQueries({ queryKey: ['holds', holdSelectItemResponse.patronId, library.baseUrl, language] });
                                                                 queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                                                 setHoldItemSelectIsOpen(false);
                                                                 setPlacingItemHold(false);
                                                                 if (result) {
                                                                      setResponseIsOpen(true);
                                                                 }
                                                            });
                                                       }}>
                                                       <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'place_hold')}</ButtonText>
                                                  </Button>
                                             </ButtonGroup>
                                        </AlertDialogFooter>
                                   </AlertDialogContent>
                              </AlertDialog>
                         </Center>
                    </>
               )}
          </>
     );
};

const Variation = (payload) => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { textColor, colorMode, theme } = React.useContext(ThemeContext);
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

     let shouldPromptAlternateLibraryCard = false;
     let shouldShowAlternateLibraryCard = false;
     let useAlternateCardForCloudLibrary = false;
     let userHasAlternateLibraryCard = false;

     if (typeof library.showAlternateLibraryCard !== 'undefined') {
          if (library.showAlternateLibraryCard === '1' || library.showAlternateLibraryCard === 1) {
               shouldShowAlternateLibraryCard = true;
          }
     }

     if (typeof library.useAlternateCardForCloudLibrary !== 'undefined') {
          if (library.useAlternateCardForCloudLibrary === '1' || library.useAlternateCardForCloudLibrary === 1) {
               useAlternateCardForCloudLibrary = true;
          }
     }

     if (shouldShowAlternateLibraryCard && useAlternateCardForCloudLibrary && source === 'cloud_library') {
          shouldPromptAlternateLibraryCard = true;
     }

     if (typeof user.alternateLibraryCard !== 'undefined') {
          if (user.alternateLibraryCard && user.alternateLibraryCard !== '') {
               if (library.alternateLibraryCardConfig?.showAlternateLibraryCardPassword === '1') {
                    if (user.alternateLibraryCardPassword !== '') {
                         userHasAlternateLibraryCard = true;
                    }
               } else {
                    userHasAlternateLibraryCard = true;
               }
          }
     }

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

     console.log(status);
     return (
          <Box mt="$5" mb="$0">
               <Center m="$1" softShadow="5" p="$3" bgColor={colorMode === 'light' ? theme['colors']['white'] : theme['colors']['coolGray']['900']} borderRadius="$md" alignSelf="center" sx={{ '@base': { width: '100%' }, '@lg': { width: '75%' } }}>
                    <VStack mb="$3" width="100%" space="md">
                         <HStack width="100%" space="sm" justifyContent="space-around" alignItems="center">
                              <Badge variant="solid" action={status.indicator} borderRadius="$sm" p="$1">
                                   <BadgeText textTransform="none" sx={{ '@base': { fontSize: 12, lineHeight: 13 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                                        {status.label}
                                   </BadgeText>
                              </Badge>
                              {source === 'ils' ? (
                                   <Button variant="link" size="xs" onPress={handleOnPress}>
                                        <ButtonIcon as={MapPinIcon} size="xs" color={theme['colors']['tertiary']['500']} mr="$1" />
                                        <ButtonText color={theme['colors']['tertiary']['500']}>{getTermFromDictionary(language, 'where_is_it')}</ButtonText>
                                   </Button>
                              ) : null}
                         </HStack>
                         {status.message ? (
                              <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 14 }, '@lg': { fontSize: 12, lineHeight: 14 } }} textAlign="center" italic>
                                   {status.message}
                              </Text>
                         ) : null}
                    </VStack>
                    <ButtonGroup width="100%" direction={_.size(variation.actions) > 1 ? 'column' : 'row'}>
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
                                        userHasAlternateLibraryCard={userHasAlternateLibraryCard}
                                        shouldPromptAlternateLibraryCard={shouldPromptAlternateLibraryCard}
                                   />
                              )}
                         />
                    </ButtonGroup>
                    <Button width="100%" mt="$2" size="xs" variant="solid" bgColor={theme['colors']['gray']['200']} onPress={handleOpenEditions}>
                         <ButtonText color={theme['colors']['gray']['900']}>{getTermFromDictionary(language, 'show_editions')}</ButtonText>
                    </Button>
               </Center>
          </Box>
     );
};

export default Variations;