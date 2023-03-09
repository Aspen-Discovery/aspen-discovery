import React from 'react';
import _ from 'lodash';
import { Text, HStack, VStack, Badge, FlatList, Button, Box, Icon, Center, AlertDialog } from 'native-base';
import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';

// custom components and helper files
import { loadingSpinner } from '../../components/loadingSpinner';
import { getRecords } from '../../util/api/item';
import { loadError } from '../../components/loadError';
import { translate } from '../../translations/translations';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import {getStatusIndicator} from './StatusIndicator';
import {ActionButton} from '../../components/Action/ActionButton';
import {LanguageContext, LibrarySystemContext} from '../../context/initialContext';
import {getTermFromDictionary} from '../../translations/TranslationService';

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
          if (prevRoute === 'DiscoveryScreen' || prevRoute === 'SearchResults') {
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
              {isLoading || status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', ''):
                  <>
                       <FlatList data={Object.keys(data.records)} renderItem={({ item }) => <Edition records={data.records[item]} id={id} format={format} volumeInfo={volumeInfo} prevRoute={prevRoute} />} />
                       <Center>
                            <AlertDialog leastDestructiveRef={cancelResponseRef} isOpen={responseIsOpen} onClose={onResponseClose}>
                                 <AlertDialog.Content>
                                      <AlertDialog.Header>{response?.title}</AlertDialog.Header>
                                      <AlertDialog.Body>{response?.message}</AlertDialog.Body>
                                      <AlertDialog.Footer>
                                           <Button.Group space={3}>
                                            {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                            <Button variant="outline" colorScheme="primary" ref={cancelResponseRef} onPress={() => setResponseIsOpen(false)}>{translate('general.button_ok')}</Button>
                                       </Button.Group>
                                      </AlertDialog.Footer>
                                 </AlertDialog.Content>
                            </AlertDialog>
                       </Center>
                  </>
              }
         </Box>
     );
};

const Edition = (payload) => {
     const { language } = React.useContext(LanguageContext);
     const {response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef} = payload;
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

     const handleOnPress = () => {
          navigate('WhereIsIt', { id: id, format: format, prevRoute: prevRoute, type: 'record', recordId: fullRecordId });
     };

     const statusIndicator = getStatusIndicator(records.statusIndicator, language);

     return (
          <Box
               mt={2}
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
                         <Text fontSize="xs">
                              <Text bold>{records.publicationDate}</Text> {records.publisher}. {records.edition} {records.physical} {closedCaptioned === "1" ? (<Icon as={MaterialIcons} name="closed-caption" size="sm" mb={-1}/>) : null}
                         </Text>
                         <VStack space={1}>
                              <Badge colorScheme={statusIndicator.indicator} rounded="4px" _text={{ fontSize: 10 }}>
                                   {statusIndicator.label}
                              </Badge>
                              {records.source === 'ils' ? (
                                   <Button colorScheme="tertiary" variant="ghost" size="xs" leftIcon={<Icon as={MaterialIcons} name="location-pin" size="xs" mr="-1" />} onPress={handleOnPress}>
                                        {getTermFromDictionary(language, 'where_is_it')}
                                   </Button>
                              ) : null}
                         </VStack>
                    </VStack>
                    <Button.Group direction={_.size(records.actions) > 1 ? 'column' : 'row'} width="50%" justifyContent="center" alignItems="stretch">
                         <FlatList data={actions} renderItem={({ item }) => <ActionButton groupedWorkId={id} recordId={recordId} recordSource={source} fullRecordId={fullRecordId} actions={item} volumeInfo={volumeInfo} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse} />} />
                    </Button.Group>
               </HStack>
          </Box>
     );
};