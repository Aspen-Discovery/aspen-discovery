import { Button } from 'native-base';
import React from 'react';
import _ from 'lodash';
import { useQueryClient } from '@tanstack/react-query';

// custom components and helper files
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { completeAction } from '../../../screens/GroupedWork/Record';
import { refreshProfile } from '../../../util/api/user';
import { HoldPrompt } from '../Holds/HoldPrompt';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const CheckOut = (props) => {
     const queryClient = useQueryClient();
     const { id, title, type, record, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef, holdConfirmationResponse, setHoldConfirmationResponse, holdConfirmationIsOpen, setHoldConfirmationIsOpen, onHoldConfirmationClose, cancelHoldConfirmationRef } = props;
     const { user, updateUser, accounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [loading, setLoading] = React.useState(false);

     const volumeInfo = {
          numItemsWithVolumes: 0,
          numItemsWithoutVolumes: 1,
          hasItemsWithoutVolumes: true,
          majorityOfItemsHaveVolumes: false,
     };

     if (_.size(accounts) > 0) {
          return (
               <HoldPrompt
                    id={record}
                    title={title}
                    action={type}
                    volumeInfo={volumeInfo}
                    prevRoute={prevRoute}
                    isEContent={true}
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
                         isLoadingText={getTermFromDictionary(language, 'checking_out', true)}
                         style={{
                              flex: 1,
                              flexWrap: 'wrap',
                         }}
                         onPress={async () => {
                              setLoading(true);
                              await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
                                   setResponse(eContentResponse);
                                   if (eContentResponse.success) {
                                        queryClient.invalidateQueries({ queryKey: ['checkouts', library.baseUrl, language] });
                                        await refreshProfile(library.baseUrl).then((result) => {
                                             updateUser(result);
                                        });
                                   }
                                   setLoading(false);
                                   setResponseIsOpen(true);
                              });
                         }}>
                         {title}
                    </Button>
               </>
          );
     }
};