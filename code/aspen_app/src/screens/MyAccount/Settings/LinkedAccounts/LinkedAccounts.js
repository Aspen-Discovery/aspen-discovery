import { useNavigation, useFocusEffect } from '@react-navigation/native';

import { Box, Divider, HStack, Button, Text, Heading, FlatList } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';

import { DisplayMessage } from '../../../../components/Notifications';
import { loadingSpinner } from '../../../../components/loadingSpinner';
import { translate } from '../../../../translations/translations';
import AddLinkedAccount from './AddLinkedAccount';
import { LibrarySystemContext, UserContext } from '../../../../context/initialContext';
import { refreshProfile, getLinkedAccounts, getViewerAccounts, removeLinkedAccount, removeViewerAccount, reloadProfile } from '../../../../util/api/user';

export const MyLinkedAccounts = () => {
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(true);
     const { accounts, viewers, updateLinkedAccounts, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     React.useEffect(() => {
          const update = navigation.addListener('focus', async () => {
               await getLinkedAccounts(library.baseUrl).then((result) => {
                    if (accounts !== result) {
                         updateLinkedAccounts(result);
                    }
               });
               await getViewerAccounts(library.baseUrl).then((result) => {
                    if (viewers !== result) {
                         updateLinkedViewerAccounts(result);
                    }
               });
               setLoading(false);
          })
          return update;
     }, [navigation])

     const Empty = () => {
          return (
               <Box pt={3} pb={5}>
                    <Text bold>{translate('general.none')}</Text>
               </Box>
          );
     };

     if (loading) {
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
     const type = data.type;
     const [isRemoving, setIsRemoving] = React.useState(false);
     const { user, accounts, viewers, updateUser, updateLinkedAccounts, updateLinkedViewerAccounts } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     const refreshLinkedAccounts = async () => {
          await getLinkedAccounts(library.baseUrl).then((result) => {
               if (accounts !== result) {
                    updateLinkedAccounts(result);
               }
          });
          await getViewerAccounts(library.baseUrl).then((result) => {
               if (viewers !== result) {
                    updateLinkedViewerAccounts(result);
               }
          });
          reloadProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     const removeAccount = async () => {
          console.log(type);
          if (type === 'viewer') {
               setIsRemoving(true);
               removeViewerAccount(account.id, library.baseUrl).then(async (res) => {
                    await refreshLinkedAccounts();
                    setIsRemoving(false);
               });
          } else {
               setIsRemoving(true);
               removeLinkedAccount(account.id, library.baseUrl).then(async (res) => {
                    await refreshLinkedAccounts();
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