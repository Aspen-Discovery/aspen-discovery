import { useNavigation } from '@react-navigation/native';
import { useIsFetching, useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { Box, Button, Divider, FlatList, Heading, HStack, ScrollView, Text } from 'native-base';
import React from 'react';

import { DisplayMessage, DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { getLinkedAccounts, getViewerAccounts, removeLinkedAccount, removeViewerAccount } from '../../../util/api/user';
import AddLinkedAccount from './AddLinkedAccount';
import DisableAccountLinking from './DisableAccountLinking';
import EnableAccountLinking from './EnableAccountLinking';

export const MyLinkedAccounts = () => {
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(false);
     const { user, accounts, viewers, cards, updateLinkedAccounts, updateLinkedViewerAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const isFetchingAccounts = useIsFetching({ queryKey: ['linked_accounts', user.id] });
     const isFetchingViewers = useIsFetching({ queryKey: ['viewer_accounts', user.id] });
     const queryClient = useQueryClient();
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     let canUserLinkAccounts = true;
     let userDisabledLinking = false;
     let ptypeDisabledLinking = false;

     if ((user.disableAccountLinking !== '0' && user.disableAccountLinking !== 0) || user.addLinkedAccountRule === 3) {
          canUserLinkAccounts = false;

          if (user.disableAccountLinking === '1' && user.disableAccountLinking === 1) {
               userDisabledLinking = true;
          }

          if (user.addLinkedAccountRule === 3) {
               ptypeDisabledLinking = true;
          }
     }

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     useQuery(['linked_accounts', user.id, cards ?? [], library.baseUrl, language], () => getLinkedAccounts(user, cards, library.barcodeStyle, library.baseUrl, language), {
          initialData: accounts,
          onSuccess: (data) => {
               updateLinkedAccounts(data.accounts);
          },
          placeholderData: [],
     });

     useQuery(['library_cards', user, cards ?? [], library.baseUrl, language], () => getLinkedAccounts(user, cards, library.barcodeStyle, library.baseUrl, language), {
          initialData: cards,
          onSuccess: (data) => {
               updateLibraryCards(data.cards);
          },
          placeholderData: [],
     });

     useQuery(['viewer_accounts', user.id, library.baseUrl, language], () => getViewerAccounts(library.baseUrl, language), {
          initialData: viewers,
          onSuccess: (data) => {
               updateLinkedViewerAccounts(data);
          },
          placeholderData: [],
     });

     const Empty = () => {
          return (
               <Box pt={3} pb={5}>
                    <Text bold>{getTermFromDictionary(language, 'none')}</Text>
               </Box>
          );
     };

     /*if (isFetchingAccounts || isFetchingViewers) {
	 return loadingSpinner();
	 }*/

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     if (!canUserLinkAccounts) {
          return (
               <ScrollView p={5} flex={1}>
                    {showSystemMessage()}
                    {ptypeDisabledLinking ? (
                         <DisplayMessage type="info" message={getTermFromDictionary(language, 'linked_account_disabled_by_ptype')} />
                    ) : (
                         <Box>
                              <DisplayMessage type="info" message={getTermFromDictionary(language, 'linked_account_disabled_by_user')} />
                              <EnableAccountLinking />
                         </Box>
                    )}
               </ScrollView>
          );
     }

     return (
          <ScrollView p={2} flex={1}>
               {showSystemMessage()}
               <DisplayMessage type="info" message={getTermFromDictionary(language, 'linked_info_message')} />
               {user.addLinkedAccountRule !== 1 ? (
                    <Box>
                         <Heading fontSize="lg" pb={2}>
                              {getTermFromDictionary(language, 'linked_additional_accounts')}
                         </Heading>
                         <Text>{getTermFromDictionary(language, 'linked_following_accounts_can_manage')}</Text>
                         <FlatList data={accounts} renderItem={({ item }) => <Account account={item} type="linked" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
                         <AddLinkedAccount />
                         <Divider my={4} />
                    </Box>
               ) : null}

               {user.addLinkedAccountRule !== 2 ? (
                    <Box>
                         <Heading fontSize="lg" pb={2}>
                              {getTermFromDictionary(language, 'linked_other_accounts')}
                         </Heading>
                         <Text>{getTermFromDictionary(language, 'linked_following_accounts_can_view')}</Text>
                         <FlatList data={viewers} renderItem={({ item }) => <Account account={item} type="viewer" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               ) : null}

               {user.addLinkedAccountRule !== 2 && user.removeLinkedAccountRule !== 0 ? (
                    <Box pb={5}>
                         <Divider my={4} />
                         <DisableAccountLinking />
                    </Box>
               ) : null}
          </ScrollView>
     );
};

const Account = (data) => {
     const queryClient = useQueryClient();
     const account = data.account;
     const type = data.type;
     const [isRemoving, setIsRemoving] = React.useState(false);
     const { user, accounts, cards, viewers, updateUser, updateLinkedAccounts, updateLibraryCards, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const refreshLinkedAccounts = async () => {
          queryClient.invalidateQueries({ queryKey: ['linked_accounts', user.id, accounts, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['library_cards', user.id, cards, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['viewer_accounts', user.id, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
     };

     const removeAccount = async () => {
          if (type === 'viewer') {
               setIsRemoving(true);
               removeViewerAccount(account.id, library.baseUrl, language).then(async (res) => {
                    await refreshLinkedAccounts();
                    setIsRemoving(false);
               });
          } else {
               setIsRemoving(true);
               removeLinkedAccount(account.id, library.baseUrl, language).then(async (res) => {
                    await refreshLinkedAccounts();
                    setIsRemoving(false);
               });
          }
     };

     if (account) {
          return (
               <HStack justifyContent="space-around" pt={2} pb={2} alignItems="center" alignContent="flex-start">
                    <Text bold isTruncated w="60%" maxW="60%">
                         {account.displayName ? account.displayName : account.ils_barcode} - {account.homeLocation}
                    </Text>
                    {type === 'viewer' && user.removeLinkedAccountRule === 0 ? null : (
                         <Button isLoading={isRemoving} isLoadingText={getTermFromDictionary(language, 'removing', true)} colorScheme="warning" size="sm" onPress={() => removeAccount()}>
                              {getTermFromDictionary(language, 'remove')}
                         </Button>
                    )}
               </HStack>
          );
     }

     return null;
};