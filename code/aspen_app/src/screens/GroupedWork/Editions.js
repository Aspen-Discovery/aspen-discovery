import React from 'react';
import _ from 'lodash';
import { Text, Heading, HStack, VStack, Badge, BadgeText, FlatList, Button, ButtonGroup, ButtonText, ButtonIcon, Box, Icon, Center, AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogBody, AlertDialogFooter, AlertDialogBackdrop } from '@gluestack-ui/themed';
import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { getRecords } from '../../util/api/item';
import { loadError } from '../../components/loadError';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import { getStatusIndicator } from './StatusIndicator';
import { ActionButton } from '../../components/Action/ActionButton';
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

export const Editions = () => {
     const navigation = useNavigation();
     let route = navigation.getParent().getState().routes;
     route = _.filter(route, { name: 'EditionsModal' });
     const params = route[0].params;
     const { id, recordId, format, source, volumeInfo, prevRoute } = params;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setLoading] = React.useState(false);

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['records', id, source, format, language, library.baseUrl],
          queryFn: () => getRecords(id, format, source, language, library.baseUrl),
     });

     const [responseIsOpen, setResponseIsOpen] = React.useState(false);
     const onResponseClose = () => setResponseIsOpen(false);
     const cancelResponseRef = React.useRef(null);
     const [response, setResponse] = React.useState('');

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

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <Box safeArea={5}>
               {isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <>
                         <FlatList data={Object.keys(data.records)} renderItem={({ item }) => <Edition records={data.records[item]} id={id} format={format} volumeInfo={volumeInfo} prevRoute={prevRoute} />} />
                         <Center>
                              <AlertDialog leastDestructiveRef={cancelResponseRef} isOpen={responseIsOpen} onClose={onResponseClose}>
                                   <AlertDialogBackdrop />
                                   <AlertDialogContent>
                                        <AlertDialogHeader>
                                             <Heading>{response?.title}</Heading>
                                        </AlertDialogHeader>
                                        <AlertDialogBody>
                                             <Text>{response?.message}</Text>
                                        </AlertDialogBody>
                                        <AlertDialogFooter>
                                             <ButtonGroup space="sm">
                                                  {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                                  <Button variant="outline" colorScheme="primary" ref={cancelResponseRef} onPress={() => setResponseIsOpen(false)}>
                                                       <ButtonText>{getTermFromDictionary(language, 'button_ok')}</ButtonText>
                                                  </Button>
                                             </ButtonGroup>
                                        </AlertDialogFooter>
                                   </AlertDialogContent>
                              </AlertDialog>
                         </Center>
                    </>
               )}
          </Box>
     );
};

const Edition = (payload) => {
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor } = React.useContext(ThemeContext);
     const { response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = payload;
     const prevRoute = payload.prevRoute;
     const records = payload.records;
     const id = payload.id;
     const format = payload.format;
     const actions = records.actions;
     const source = records.source;
     const recordId = records.recordId;
     const fullRecordId = records.id;
     const volumeInfo = payload.volumeInfo;
     const closedCaptioned = records.closedCaptioned;
     const title = records.title ?? null;
     const author = records.author ?? null;
     const publisher = records.publisher ?? null;
     const isbn = records.isbn ?? null;
     const oclcNumber = records.oclcNumber ?? null;

     const handleOnPress = () => {
          navigate('WhereIsIt', { id: id, format: format, prevRoute: prevRoute, type: 'record', recordId: fullRecordId });
     };

     const statusIndicator = getStatusIndicator(records.statusIndicator, language);

     return (
          <Box mt="$2" mb="$0" p="$3">
               <HStack justifyContent="space-between" alignItems="center" space="sm" flex={1}>
                    <VStack space="sm" maxW="40%" flex={1} justifyContent="center">
                         <Text size="xs" color={textColor}>
                              <Text bold size="xs" color={textColor}>
                                   {records.publicationDate}
                              </Text>{' '}
                              {records.publisher}. {records.edition} {records.physical} {closedCaptioned === '1' ? <Icon as={MaterialIcons} name="closed-caption" size="sm" color={textColor} /> : null}
                         </Text>
                         <VStack space="sm">
                              <Center>
                                   <Badge action={statusIndicator.indicator} borderRadius="$sm" variant="solid">
                                        <BadgeText textTransform="none">{statusIndicator.label}</BadgeText>
                                   </Badge>
                              </Center>
                              {records.source === 'ils' ? (
                                   <Button variant="link" size="xs" onPress={handleOnPress}>
                                        <ButtonIcon as={MaterialIcons} name="location-pin" size="xs" color={theme['colors']['tertiary']['500']} />
                                        <ButtonText color={theme['colors']['tertiary']['500']}>{getTermFromDictionary(language, 'where_is_it')}</ButtonText>
                                   </Button>
                              ) : null}
                         </VStack>
                    </VStack>
                    <ButtonGroup direction={_.size(records.actions) > 1 ? 'column' : 'row'} width="50%" justifyContent="center" alignItems="stretch">
                         <FlatList
                              data={actions}
                              renderItem={({ item }) => <ActionButton groupedWorkId={id} recordId={recordId} recordSource={source} fullRecordId={fullRecordId} title={title} author={author} publisher={publisher} isbn={isbn} oclcNumber={oclcNumber} actions={item} volumeInfo={volumeInfo} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse} />}
                         />
                    </ButtonGroup>
               </HStack>
          </Box>
     );
};