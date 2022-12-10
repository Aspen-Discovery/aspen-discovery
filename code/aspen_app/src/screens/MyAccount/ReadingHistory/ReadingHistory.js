import React, { useState } from 'react';
import { Box, Divider, HStack, Button, Text, ScrollView, FlatList } from 'native-base';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { translate } from '../../../translations/translations';
import { DisplayMessage } from '../../../components/Notifications';
import { getReadingHistory, optIntoReadingHistory, optOutOfReadingHistory, refreshProfile } from '../../../util/api/user';
import { SafeAreaView } from "react-native";

export const MyReadingHistory = () => {
     const [isLoading, setLoading] = React.useState(true);
     const {library} = React.useContext(LibrarySystemContext);
     const {user, updateUser, readingHistory, updateReadingHistory} = React.useContext(UserContext);

     const fetchReadingHistory = async () => {
          setLoading(true);
          await getReadingHistory().then((result) => {
               updateReadingHistory(result);
          });
          setLoading(false);
     };

     const optIn = async () => {
          setLoading(true);
          await optIntoReadingHistory(library.baseUrl).then(() => {
               refreshProfile(library.baseUrl).then((result) => {
                    updateUser(result);
                    setLoading(false);
               });
          });
     };

     const optOut = async () => {
          setLoading(true);
          await optOutOfReadingHistory(library.baseUrl).then(() => {
               refreshProfile(library.baseUrl).then((result) => {
                    updateUser(result);
                    setLoading(false);
               });
          });
     };

     const getDisclaimer = () => {
          return <DisplayMessage type="info" message={translate('reading_history.disclaimer')}/>;
     };

     const getActionButtons = () => {
          return null;
     }

     const Empty = () => {
          return null;
     }

     if (isLoading) {
          return loadingSpinner();
     }

     if (user.trackReadingHistory !== '1') {
          return (
              <Box safeArea={5}>
                   <Button>{translate('reading_history.opt_in')}</Button>
              </Box>
          );
     }

     return (
         <SafeAreaView style={{flex: 1}}>
              <Box safeArea={2} bgColor="coolGray.100" borderBottomWidth="1" _dark={{borderColor: 'gray.600', bg: 'coolGray.700'}} borderColor="coolGray.200" flexWrap="nowrap">
                   <ScrollView horizontal>{actionButtons()}</ScrollView>
              </Box>
              <FlatList data={readingHistory} ListEmptyComponent={Empty} renderItem={({item}) => <Item data={item}/>} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{paddingBottom: 30}}/>
         </SafeAreaView>
     )
};

const Item = (data) => {
     const item = data.item;
     return null;
};