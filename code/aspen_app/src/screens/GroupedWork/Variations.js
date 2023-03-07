import { Center, HStack, VStack, Badge, Icon, Button, Box, Text, AlertDialog, FlatList } from 'native-base';
import { useRoute } from '@react-navigation/native';
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import _ from 'lodash';
import { MaterialIcons } from '@expo/vector-icons';

// custom components and helper files
import { translate } from '../../translations/translations';
import {LanguageContext, LibrarySystemContext} from '../../context/initialContext';
import { getFirstRecord, getVariations } from '../../util/api/item';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import { getStatusIndicator } from './StatusIndicator';
import {ActionButton} from '../../components/Action/ActionButton';
import {decodeHTML, stripHTML} from '../../util/apiAuth';
import {getTermFromDictionary} from '../../translations/TranslationService';

export const Variations = (props) => {
     const route = useRoute();
     const id = route.params.id;
     const prevRoute = route.params.prevRoute;
     const format = props.format;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setLoading] = React.useState(false);

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

     const decodeMessage = (string) => {
          return stripHTML(string)
     }

     return (
         <>{isLoading || status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError(error, '') :
             <>
                  <FlatList data={Object.keys(data.variations)} renderItem={({ item }) => <Variation records={data.variations[item]} format={format} volumeInfo={data.volumeInfo} id={id} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse}/>} />
                  <Center>
                       <AlertDialog leastDestructiveRef={cancelResponseRef} isOpen={responseIsOpen} onClose={onResponseClose}>
                            <AlertDialog.Content>
                                 <AlertDialog.Header>{response?.title}</AlertDialog.Header>
                                 <AlertDialog.Body>{response?.message ? decodeMessage(response.message) : null}</AlertDialog.Body>
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
         </>
     );
};

const Variation = (payload) => {
     const { language } = React.useContext(LanguageContext);
     const {id, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef, prevRoute, format, volumeInfo} = payload;
     const variation = payload.records;
     const actions = variation.actions;
     const source = variation.source;
     const status = getStatusIndicator(variation.statusIndicator, language);

     let fullRecordId = _.split(variation.id, ':');
     const recordId = _.toString(fullRecordId[1]);

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
                         <FlatList data={actions} renderItem={({ item }) => <ActionButton groupedWorkId={id} recordId={recordId} recordSource={source} fullRecordId={variation.id} actions={item} volumeInfo={volumeInfo} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse}/>} />
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