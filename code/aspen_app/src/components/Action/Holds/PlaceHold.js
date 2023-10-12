import { Button } from 'native-base';
import React from 'react';
import _ from 'lodash';
import { useQueryClient } from '@tanstack/react-query';

// custom components and helper files
import { HoldsContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { completeAction } from '../../../screens/GroupedWork/Record';
import { getPatronHolds, refreshProfile } from '../../../util/api/user';
import { HoldPrompt } from './HoldPrompt';

export const PlaceHold = (props) => {
     const queryClient = useQueryClient();
     const {
          id,
          type,
          volumeInfo,
          title,
          record,
          holdTypeForFormat,
          variationId,
          prevRoute,
          response,
          setResponse,
          responseIsOpen,
          setResponseIsOpen,
          onResponseClose,
          cancelResponseRef,
          holdConfirmationResponse,
          setHoldConfirmationResponse,
          holdConfirmationIsOpen,
          setHoldConfirmationIsOpen,
          onHoldConfirmationClose,
          cancelHoldConfirmationRef,
          language,
          holdSelectItemResponse,
          setHoldSelectItemResponse,
          holdItemSelectIsOpen,
          setHoldItemSelectIsOpen,
          onHoldItemSelectClose,
          cancelHoldItemSelectRef,
     } = props;
     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const [loading, setLoading] = React.useState(false);
     const { updateHolds } = React.useContext(HoldsContext);

     const userPickupLocation = _.filter(locations, { locationId: user.pickupLocationId });
     let pickupLocation = '';
     if (!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
          pickupLocation = userPickupLocation[0];
          if (_.isObject(pickupLocation)) {
               pickupLocation = pickupLocation.code;
          }
     }

     let promptForHoldNotifications = user.promptForHoldNotifications ?? false;

     let loadHoldPrompt = false;
     if (volumeInfo.numItemsWithVolumes >= 1 || _.size(accounts) > 0 || _.size(locations) > 1 || promptForHoldNotifications || holdTypeForFormat === 'item' || holdTypeForFormat === 'either') {
          loadHoldPrompt = true;
     }

     if (loadHoldPrompt) {
          return (
               <HoldPrompt
                    language={language}
                    id={record}
                    title={title}
                    action={type}
                    holdTypeForFormat={holdTypeForFormat}
                    variationId={variationId}
                    volumeInfo={volumeInfo}
                    prevRoute={prevRoute}
                    isEContent={false}
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
          );
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
                              await completeAction(record, type, user.id, null, null, pickupLocation, library.baseUrl, null, 'default').then(async (ilsResponse) => {
                                   setResponse(ilsResponse);
                                   if (ilsResponse?.confirmationNeeded && ilsResponse.confirmationNeeded) {
                                        setHoldConfirmationResponse({
                                             message: ilsResponse.message,
                                             title: ilsResponse.title,
                                             confirmationNeeded: ilsResponse.confirmationNeeded ?? false,
                                             confirmationId: ilsResponse.confirmationId ?? null,
                                             recordId: record ?? null,
                                        });
                                   }
                                   if (ilsResponse?.shouldBeItemHold && ilsResponse.shouldBeItemHold) {
                                        setHoldSelectItemResponse({
                                             message: ilsResponse.message,
                                             title: 'Select an Item',
                                             patronId: user.id,
                                             pickupLocation: pickupLocation,
                                             bibId: record ?? null,
                                             items: ilsResponse.items ?? [],
                                        });
                                   }
                                   queryClient.invalidateQueries({ queryKey: ['holds', user.id, library.baseUrl, language] });
                                   queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                   /*await refreshProfile(library.baseUrl).then((result) => {
                                        updateUser(result);
                                   });*/
                                   setLoading(false);
                                   if (ilsResponse?.confirmationNeeded && ilsResponse.confirmationNeeded) {
                                        setHoldConfirmationIsOpen(true);
                                   } else if (ilsResponse?.shouldBeItemHold && ilsResponse.shouldBeItemHold) {
                                        setHoldItemSelectIsOpen(true);
                                   } else {
                                        setResponseIsOpen(true);
                                   }
                              });
                         }}>
                         {title}
                    </Button>
               </>
          );
     }
};