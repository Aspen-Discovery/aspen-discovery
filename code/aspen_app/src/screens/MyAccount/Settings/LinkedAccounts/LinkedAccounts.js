import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';
import { Box, Divider, HStack, Button, Text, Heading, FlatList } from 'native-base';
import React, { Component } from 'react';
import { SafeAreaView } from 'react-native';

import { DisplayMessage } from '../../../../components/Notifications';
import { loadingSpinner } from '../../../../components/loadingSpinner';
import { userContext } from '../../../../context/user';
import { translate } from '../../../../translations/translations';
import { removeLinkedAccount } from '../../../../util/accountActions';
import { getLinkedAccounts, getViewers } from '../../../../util/loadPatron';
import AddLinkedAccount from './AddLinkedAccount';
import { LibrarySystemContext } from '../../../../context/initialContext';

export default class LinkedAccounts extends Component {
     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               linkedAccounts: [],
               viewers: [],
               removingLink: false,
          };
          this._isMounted = false;
          this._fetchLinkedAccounts();
          this._fetchViewers();
          this.loadLinkedAccounts();
          this.loadViewers();
     }

     loadLinkedAccounts = async () => {
          const tmp = await AsyncStorage.getItem('@linkedAccounts');
          let accounts = JSON.parse(tmp);
          accounts = _.values(accounts);
          this.setState({
               linkedAccounts: accounts,
               isLoading: false,
          });
     };

     loadViewers = async () => {
          const tmp = await AsyncStorage.getItem('@viewerAccounts');
          let accounts = JSON.parse(tmp);
          accounts = _.values(accounts);
          this.setState({
               viewers: accounts,
               isLoading: false,
          });
     };

     _fetchLinkedAccounts = async () => {
          await getLinkedAccounts(this.context.library.baseUrl);
     };

     _fetchViewers = async () => {
          await getViewers(this.context.library.baseUrl);
     };

     _updateLinkedAccounts = async () => {
          this._isMounted && (await this._fetchLinkedAccounts());
          this._isMounted && (await this._fetchViewers());
          this._isMounted && (await this.loadLinkedAccounts());
          this._isMounted && (await this.loadViewers());
     };

     componentDidMount = async () => {
          this._isMounted = true;

          this._isMounted &&
               this.setState({
                    isLoading: true,
               });

          this._isMounted && (await this._updateLinkedAccounts());
     };

     componentWillUnmount() {
          this._isMounted = false;
     }

     renderLinkedAccounts = (item, libraryUrl) => {
          return (
               <HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
                    <Text bold>
                         {item.displayName} - {item.homeLocation}
                    </Text>
                    <Button
                         isLoading={this.state.removingLink}
                         isLoadingText="Removing..."
                         colorScheme="warning"
                         size="sm"
                         onPress={async () => {
                              this.setState({ removingLink: true });
                              removeLinkedAccount(item.id, libraryUrl).then((res) => {
                                   this._updateLinkedAccounts();
                                   this.setState({ removingLink: false });
                              });
                         }}>
                         {translate('general.remove')}
                    </Button>
               </HStack>
          );
     };

     renderViewers = (item) => {
          return (
               <HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
                    <Text bold>
                         {item.displayName} - {item.homeLocation}
                    </Text>
               </HStack>
          );
     };

     renderNoLinkedAccounts = () => {
          return (
               <Box pt={3} pb={5}>
                    <Text bold>{translate('general.none')}</Text>
               </Box>
          );
     };

     static contextType = userContext;

     render() {
          const library = this.context.library;

          if (this.state.isLoading) {
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
                         <FlatList data={this.state.linkedAccounts} renderItem={({ item }) => this.renderLinkedAccounts(item, library.baseUrl)} ListEmptyComponent={() => this.renderNoLinkedAccounts()} keyExtractor={(item, index) => index.toString()} />
                         <AddLinkedAccount libraryUrl={library.baseUrl} _updateLinkedAccounts={this._updateLinkedAccounts} />
                         <Divider my={4} />
                         <Heading fontSize="lg" pb={2}>
                              {translate('linked_accounts.other_accounts')}
                         </Heading>
                         <Text>{translate('linked_accounts.following_accounts_can_view')}</Text>
                         <FlatList data={this.state.viewers} renderItem={({ item }) => this.renderViewers(item)} ListEmptyComponent={() => this.renderNoLinkedAccounts()} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               </SafeAreaView>
          );
     }
}

LinkedAccounts.contextType = LibrarySystemContext;