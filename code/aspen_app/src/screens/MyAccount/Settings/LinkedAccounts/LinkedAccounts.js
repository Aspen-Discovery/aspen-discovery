import { useNavigation } from '@react-navigation/native';
import { useQueryClient, useQuery, useIsFetching } from '@tanstack/react-query';
import { Box, Divider, HStack, Button, Text, Heading, FlatList, ScrollView } from 'native-base';
import React from 'react';

import { DisplayMessage } from '../../../../components/Notifications';
import { loadingSpinner } from '../../../../components/loadingSpinner';
import AddLinkedAccount from './AddLinkedAccount';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../../context/initialContext';
import { getLinkedAccounts, getViewerAccounts, removeLinkedAccount, removeViewerAccount, reloadProfile } from '../../../../util/api/user';
import { getTermFromDictionary } from '../../../../translations/TranslationService';
import { getLists } from '../../../../util/api/list';

export const MyLinkedAccounts = () => {
     const isFetchingAccounts = useIsFetching({ queryKey: ['linked_accounts'] });
     const isFetchingViewers = useIsFetching({ queryKey: ['viewer_accounts'] });
     const [loading, setLoading] = React.useState(false);
     const { user, accounts, viewers, cards, updateLinkedAccounts, updateLinkedViewerAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     useQuery(['linked_accounts', library.baseUrl, language], () => getLinkedAccounts(user, cards, library.baseUrl, language), {
          onSuccess: (data) => {
               updateLinkedAccounts(data.accounts);
               updateLibraryCards(data.cards);
          },
          placeholderData: [],
     });

     useQuery(['viewer_accounts', library.baseUrl, language], () => getViewerAccounts(library.baseUrl, language), {
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

     return (
          <ScrollView p={5} flex={1}>
               <DisplayMessage type="info" message={getTermFromDictionary(language, 'linked_info_message')} />
               <Heading fontSize="lg" pb={2}>
                    {getTermFromDictionary(language, 'linked_additional_accounts')}
               </Heading>
               <Text>{getTermFromDictionary(language, 'linked_following_accounts_can_manage')}</Text>
               <FlatList data={accounts} renderItem={({ item }) => <Account account={item} type="linked" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
               <AddLinkedAccount />
               <Divider my={4} />
               <Heading fontSize="lg" pb={2}>
                    {getTermFromDictionary(language, 'linked_other_accounts')}
               </Heading>
               <Text>{getTermFromDictionary(language, 'linked_following_accounts_can_view')}</Text>
               <FlatList data={viewers} renderItem={({ item }) => <Account account={item} type="viewer" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
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
          queryClient.invalidateQueries({ queryKey: ['linked_accounts', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['viewer_accounts', library.baseUrl, language] });
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
               <HStack justifyContent="space-between" pt={2} pb={2} alignItems="center" alignContent="flex-start">
                    <Text bold isTruncated w="75%" maxW="75%">
                         {account.displayName} - {account.homeLocation}
                    </Text>
                    <Button isLoading={isRemoving} isLoadingText={getTermFromDictionary(language, 'removing', true)} colorScheme="warning" size="sm" onPress={() => removeAccount()}>
                         {getTermFromDictionary(language, 'remove')}
                    </Button>
               </HStack>
          );
     }

     return null;
};