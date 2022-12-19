import { useFocusEffect } from '@react-navigation/native';

import { Box, Divider, HStack, Button, Text, Heading, FlatList } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';

import { DisplayMessage } from '../../../../components/Notifications';
import { loadingSpinner } from '../../../../components/loadingSpinner';
import { translate } from '../../../../translations/translations';
import { removeLinkedAccount, removeLinkedViewerAccount } from '../../../../util/accountActions';
import { getLinkedAccounts, getViewers } from '../../../../util/loadPatron';
import AddLinkedAccount from './AddLinkedAccount';
import { LibrarySystemContext, UserContext } from '../../../../context/initialContext';
import { refreshProfile } from '../../../../util/api/user';

export const MyLinkedAccounts = () => {
     const [isLoading, setLoading] = React.useState(true);
     const { accounts, viewers, updateLinkedAccounts, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getLinkedAccounts(library.baseUrl).then((result) => {
                         if (accounts !== result) {
                              updateLinkedAccounts(result);
                         }
                    });
                    await getViewers(library.baseUrl).then((result) => {
                         if (viewers !== result) {
                              updateLinkedViewerAccounts(result);
                         }
                    });
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const Empty = () => {
          return (
               <Box pt={3} pb={5}>
                    <Text bold>{translate('general.none')}</Text>
               </Box>
          );
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box flex={1} safeArea={5}>
                    <DisplayMessage type="info" message={translate('linked_accounts.info_message')} />
                    <Heading fontSize="lg" pb={2}>
                         {translate('linked_accounts.additional_accounts')}
                    </Heading>
                    <Text>{translate('linked_accounts.following_accounts_can_manage')}</Text>
                    <FlatList data={accounts} renderItem={({ item }) => <Account account={item} type="linked" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
                    <AddLinkedAccount />
                    <Divider my={4} />
                    <Heading fontSize="lg" pb={2}>
                         {translate('linked_accounts.other_accounts')}
                    </Heading>
                    <Text>{translate('linked_accounts.following_accounts_can_view')}</Text>
                    <FlatList data={viewers} renderItem={({ item }) => <Account account={item} type="viewer" />} ListEmptyComponent={Empty} keyExtractor={(item, index) => index.toString()} />
               </Box>
          </SafeAreaView>
     );
};

const Account = (data) => {
     const account = data.account;
     const type = data.account;
     const [isRemoving, setIsRemoving] = React.useState(false);
     const { user, accounts, viewers, updateUser, updateLinkedAccounts, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     const refreshLinkedAccounts = async () => {
          await getLinkedAccounts(library.baseUrl).then((result) => {
               if (accounts !== result) {
                    updateLinkedAccounts(result);
               }
          });
          await getViewers(library.baseUrl).then((result) => {
               if (viewers !== result) {
                    updateLinkedViewerAccounts(result);
               }
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     const removeAccount = async () => {
          if (type === 'viewer') {
               setIsRemoving(true);
               removeLinkedViewerAccount(account.id, library.baseUrl).then((res) => {
                    refreshLinkedAccounts();
                    setIsRemoving(false);
               });
          } else {
               setIsRemoving(true);
               removeLinkedAccount(account.id, library.baseUrl).then((res) => {
                    refreshLinkedAccounts();
                    setIsRemoving(false);
               });
          }
     };

     if (account) {
          return (
               <HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
                    <Text bold>
                         {account.displayName} - {account.homeLocation}
                    </Text>
                    <Button isLoading={isRemoving} isLoadingText="Removing..." colorScheme="warning" size="sm" onPress={() => removeAccount()}>
                         {translate('general.remove')}
                    </Button>
               </HStack>
          );
     }

     return null;
};