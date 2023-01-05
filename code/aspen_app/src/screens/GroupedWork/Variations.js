import { Center, HStack, VStack, Badge, Icon, Button, Box, Text, AlertDialog, FlatList } from 'native-base';
import { useRoute, useNavigation } from '@react-navigation/native';
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import _ from 'lodash';
import { MaterialIcons } from '@expo/vector-icons';

// custom components and helper files
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getFirstRecord, getVariations } from '../../util/api/item';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import { getStatusIndicator } from './StatusIndicator';
import { completeAction } from './Record';
import { openSideLoad } from '../../util/recordActions';
import { reloadProfile } from '../../util/api/user';
import SelectVolumeHold from './SelectVolumeHold';
import SelectLinkedAccount from './SelectLinkedAccount';
import SelectPickupLocation from './SelectPickupLocation';

export const Variations = (props) => {
     const route = useRoute();
     const id = route.params.id;
     const prevRoute = route.params.prevRoute;
     const format = props.format;
     const { library } = React.useContext(LibrarySystemContext);
     const [isLoading, setLoading] = React.useState(false);

     console.log('*******************************');
     console.log('cachedGroupedWork: ' + id);
     console.log('format: ' + format);
     console.log('*******************************');

     const { data: record } = useQuery({
          queryKey: ['recordId', id, format, library.baseUrl],
          queryFn: () => getFirstRecord(id, format, library.baseUrl),
     });
     const recordId = record;
     const { status, data, error, isFetching } = useQuery({
          queryKey: ['variation', id, format, library.baseUrl],
          queryFn: () => getVariations(id, format, library.baseUrl),
          enabled: !!recordId,
     });

     return <>{isLoading || status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError(error, '') : <FlatList data={Object.keys(data.variations)} renderItem={({ item }) => <Variation records={data.variations[item]} format={format} volumeInfo={data.volumeInfo} id={id} prevRoute={prevRoute} />} />}</>;
};

const Variation = (payload) => {
     const { library } = React.useContext(LibrarySystemContext);
     const route = useRoute();
     const id = payload.id;
     const prevRoute = payload.prevRoute;
     const variation = payload.records;
     const actions = variation.actions;
     const format = payload.format;
     const volumeInfo = payload.volumeInfo;
     const source = variation.source;
     const closedCaptioned = variation.closedCaptioned;
     const status = getStatusIndicator(variation.statusIndicator);

     let fullRecordId = _.split(variation.id, ':');
     const recordId = _.toString(fullRecordId[1]);

     console.log('*******************************');
     console.log(variation);
     console.log(_.size(variation.actions));
     console.log('*******************************');

     const handleOnPress = () => {
          navigate('CopyDetails', { id: id, format: format, prevRoute: prevRoute });
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
                                   {translate('copy_details.where_is_it')}
                              </Button>
                         ) : null}
                    </VStack>
                    <Button.Group width="50%" justifyContent="center" alignItems="stretch" direction={_.size(variation.actions) > 1 ? 'column' : 'row'}>
                         <FlatList data={actions} renderItem={({ item }) => <ActionButton groupedWorkId={id} recordId={recordId} recordSource={source} fullRecordId={variation.id} actions={item} volumeInfo={volumeInfo} prevRoute={prevRoute} />} />
                    </Button.Group>
               </HStack>
               <Center mt={2}>
                    <Button size="xs" colorScheme="tertiary" variant="outline" onPress={handleOpenEditions}>
                         {translate('grouped_work.show_editions')}
                    </Button>
               </Center>
          </Box>
     );
};

const ActionButton = (data) => {
     const action = data.actions;
     const { volumeInfo, groupedWorkId, fullRecordId, recordSource, prevRoute } = data;
     if (_.isObject(action)) {
          if (action.type === 'overdrive_sample') {
               return <OverDriveSample title={action.title} prevRoute={prevRoute} id={fullRecordId} type={action.type} sampleNumber={action.sampleNumber} formatId={action.formatId} />;
          } else if (action.url === '/MyAccount/CheckedOut') {
               return <CheckedOutToYou title={action.title} prevRoute={prevRoute} />;
          } else if (action.url === '/MyAccount/Holds') {
               return <OnHoldForYou title={action.title} prevRoute={prevRoute} />;
          } else if (action.type === 'ils_hold') {
               return <PlaceHold title={action.title} id={groupedWorkId} type={action.type} record={fullRecordId} volumeInfo={volumeInfo} prevRoute={prevRoute} />;
          } else if (action.type === 'vdx_request') {
               return <VDXRequest title={action.title} id={groupedWorkId} prevRoute={prevRoute} />;
          } else if (!_.isUndefined(action.redirectUrl)) {
               return <OpenSideLoad title={action.title} url={action.redirectUrl} prevRoute={prevRoute} />;
          } else {
               return <CheckOut title={action.title} type={action.type} id={groupedWorkId} record={fullRecordId} volumeInfo={volumeInfo} prevRoute={prevRoute} />;
          }
     }

     return null;
};

const PlaceHold = (props) => {
     const { id, type, volumeInfo, title, record, prevRoute } = props;
     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const [loading, setLoading] = React.useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [response, setResponse] = React.useState('');
     const handleNavigation = (action) => {
          if (prevRoute === 'Discovery' || prevRoute === 'SearchResults') {
               if (action.includes('Checkouts')) {
                    navigateStack('AccountScreenTab', 'MyCheckouts', {});
               } else {
                    navigateStack('AccountScreenTab', 'MyHolds', {});
               }
          } else {
               if (action.includes('Checkouts')) {
                    navigate('MyCheckouts', {});
               } else {
                    navigate('MyHolds', {});
               }
          }
     };
     if (volumeInfo.majorityOfItemsHaveVolumes || volumeInfo.numItemsWithVolumes >= 1) {
          return <SelectVolumeHold id={record} title={title} action={type} volumeInfo={volumeInfo} prevRoute={prevRoute} />;
     } else if (_.size(accounts) > 0) {
          return <SelectLinkedAccount id={record} title={title} action={type} volumeInfo={volumeInfo} prevRoute={prevRoute} isEContent={false} />;
     } else if (_.size(locations) > 1) {
          return <SelectPickupLocation id={record} title={title} action={type} volumeInfo={volumeInfo} prevRoute={prevRoute} />;
     } else {
          return (
               <>
                    <Button
                         size="md"
                         colorScheme="primary"
                         variant="solid"
                         _text={{
                              padding: 0,
                              textAlign: 'center',
                         }}
                         isLoading={loading}
                         isLoadingText="Placing hold..."
                         style={{
                              flex: 1,
                              flexWrap: 'wrap',
                         }}
                         onPress={async () => {
                              setLoading(true);
                              await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (ilsResponse) => {
                                   setResponse(ilsResponse);
                                   if (ilsResponse.success) {
                                        await reloadProfile(library.baseUrl).then((result) => {
                                             updateUser(result);
                                        });
                                   }
                                   setLoading(false);
                                   setIsOpen(true);
                              });
                         }}>
                         {title}
                    </Button>
                    <Center>
                         <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                              <AlertDialog.Content>
                                   <AlertDialog.Header>{response?.title}</AlertDialog.Header>
                                   <AlertDialog.Body>{response?.message}</AlertDialog.Body>
                                   <AlertDialog.Footer>
                                        <Button.Group space={3}>
                                             {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                             <Button variant="outline" colorScheme="primary" ref={cancelRef} onPress={() => setIsOpen(false)}>
                                                  {translate('general.button_ok')}
                                             </Button>
                                        </Button.Group>
                                   </AlertDialog.Footer>
                              </AlertDialog.Content>
                         </AlertDialog>
                    </Center>
               </>
          );
     }
};

const CheckedOutToYou = (props) => {
     console.log(props);
     const handleNavigation = () => {
          if (props.prevRoute === 'Discovery' || props.prevRoute === 'SearchResults') {
               navigateStack('AccountScreenTab', 'MyCheckouts', {});
          } else {
               navigate('MyCheckouts', {});
          }
     };

     return (
          <Button
               size="md"
               colorScheme="primary"
               variant="solid"
               _text={{
                    padding: 0,
                    textAlign: 'center',
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               onPress={handleNavigation}>
               {props.title}
          </Button>
     );
};

const OnHoldForYou = (props) => {
     const handleNavigation = () => {
          if (props.prevRoute === 'Discovery' || props.prevRoute === 'SearchResults') {
               navigateStack('AccountScreenTab', 'MyHolds', {});
          } else {
               navigate('MyHolds', {});
          }
     };

     return (
          <Button
               size="md"
               colorScheme="primary"
               variant="solid"
               _text={{
                    padding: 0,
                    textAlign: 'center',
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               onPress={handleNavigation}>
               {props.title}
          </Button>
     );
};

const VDXRequest = (props) => {
     const { user } = React.useContext(UserContext);
     const openVDXRequest = () => {
          navigate('CreateVDXRequest', {
               record: props.id
          });
     };

     return (
          <Button
               size="md"
               colorScheme="primary"
               variant="solid"
               _text={{
                    padding: 0,
                    textAlign: 'center',
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               onPress={openVDXRequest}>
               {props.title}
          </Button>
     );
};

const OverDriveSample = (props) => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [loading, setLoading] = React.useState(false);

     return (
          <Button
               size="xs"
               colorScheme="primary"
               variant="outline"
               _text={{
                    padding: 0,
                    textAlign: 'center',
                    fontSize: 12,
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               isLoading={loading}
               isLoadingText="Opening..."
               onPress={() => {
                    setLoading(true);
                    completeAction(props.id, props.type, user.id, props.formatId, props.sampleNumber, null, library.baseUrl, null, null).then((r) => {
                         setLoading(false);
                    });
               }}>
               {props.title}
          </Button>
     );
};

const OpenSideLoad = (props) => {
     const [loading, setLoading] = React.useState(false);

     return (
          <Button
               size="md"
               colorScheme="primary"
               variant="solid"
               _text={{
                    padding: 0,
                    textAlign: 'center',
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               isLoading={loading}
               isLoadingText="Opening..."
               onPress={async () => {
                    setLoading(true);
                    await openSideLoad(props.url).then((r) => setLoading(false));
               }}>
               {props.title}
          </Button>
     );
};

const CheckOut = (props) => {
     const { id, title, type, record, prevRoute } = props;
     const { user, updateUser, accounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const [loading, setLoading] = React.useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [response, setResponse] = React.useState('');
     const handleNavigation = (action) => {
          if (prevRoute === 'Discovery' || prevRoute === 'SearchResults') {
               if (action.includes('Checkouts')) {
                    navigateStack('AccountScreenTab', 'MyCheckouts', {});
               } else {
                    navigateStack('AccountScreenTab', 'MyHolds', {});
               }
          } else {
               if (action.includes('Checkouts')) {
                    navigate('MyCheckouts', {});
               } else {
                    navigate('MyHolds', {});
               }
          }
     };

     const volumeInfo = {
          numItemsWithVolumes: 0,
          numItemsWithoutVolumes: 1,
          hasItemsWithoutVolumes: true,
          majorityOfItemsHaveVolumes: false,
     }

     if(_.size(accounts) > 0) {
          return <SelectLinkedAccount id={record} title={title} action={type} volumeInfo={volumeInfo} prevRoute={prevRoute} isEContent={true} />
     } else {
          return (
              <>
                   <Button
                       size="md"
                       colorScheme="primary"
                       variant="solid"
                       _text={{
                            padding: 0,
                            textAlign: 'center',
                       }}
                       isLoading={loading}
                       isLoadingText="Checking out..."
                       style={{
                            flex: 1,
                            flexWrap: 'wrap',
                       }}
                       onPress={async () => {
                            setLoading(true);
                            await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
                                 setResponse(eContentResponse);
                                 if (eContentResponse.success) {
                                      await reloadProfile(library.baseUrl).then((result) => {
                                           updateUser(result);
                                      });
                                 }
                                 setLoading(false);
                                 setIsOpen(true);
                            });
                       }}>
                        {title}
                   </Button>
                   <Center>
                        <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                             <AlertDialog.Content>
                                  <AlertDialog.Header>{response?.title}</AlertDialog.Header>
                                  <AlertDialog.Body>{response?.message}</AlertDialog.Body>
                                  <AlertDialog.Footer>
                                       <Button.Group space={3}>
                                            {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                            <Button variant="outline" colorScheme="primary" ref={cancelRef} onPress={() => setIsOpen(false)}>
                                                 {translate('general.button_ok')}
                                            </Button>
                                       </Button.Group>
                                  </AlertDialog.Footer>
                             </AlertDialog.Content>
                        </AlertDialog>
                   </Center>
              </>
          );
     }
};

export default Variations;