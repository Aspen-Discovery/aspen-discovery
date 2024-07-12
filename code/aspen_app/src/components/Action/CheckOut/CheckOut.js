import { Box, Button, ButtonSpinner, ButtonGroup, ButtonIcon, ButtonText, Text } from '@gluestack-ui/themed';
import React from 'react';
import _ from 'lodash';
import { useQueryClient } from '@tanstack/react-query';

// custom components and helper files
import { LanguageContext, LibraryBranchContext, LibrarySystemContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { completeAction } from '../../../util/recordActions';
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
     const { theme } = React.useContext(ThemeContext);

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
                         minWidth="100%"
                         maxWidth="100%"
                         bgColor={theme['colors']['primary']['500']}
                         variant="solid"
                         onPress={async () => {
                              setLoading(true);
                              await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
                                   setResponse(eContentResponse);
                                   if (eContentResponse.success) {
                                        queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language] });
                                        queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
                                   }
                                   setLoading(false);
                                   setResponseIsOpen(true);
                              });
                         }}>
                         {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} pr={2} /> : <ButtonText color={theme['colors']['primary']['500-text']}>{title}</ButtonText>}
                    </Button>
               </>
          );
     }
};