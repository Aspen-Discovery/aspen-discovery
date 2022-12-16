import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Box, Divider, VStack, Button, Text, ScrollView, FlatList } from 'native-base';
import { useNavigation, useFocusEffect } from '@react-navigation/native';

import { loadingSpinner } from '../../../components/loadingSpinner';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { translate } from '../../../translations/translations';
import { DisplayMessage } from '../../../components/Notifications';
import { fetchReadingHistory, optIntoReadingHistory, optOutOfReadingHistory, refreshProfile } from '../../../util/api/user';
import { SafeAreaView } from 'react-native';

export const MyReadingHistory = () => {
     const [page, setPage] = React.useState(1);
     const [isLoading, setLoading] = React.useState(false);
     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser, readingHistory, updateReadingHistory } = React.useContext(UserContext);
     const url = library.baseUrl;
     const sort = 'checkedOut';
     const pageSize = 25;

     const { status, data, error, isFetching, isPreviousData } = useQuery(['readingHistory', url, page, pageSize, sort], () => fetchReadingHistory(page, pageSize, sort, url), {
          keepPreviousData: true,
          staleTime: 1000,
     });

     console.log(data);

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
          return <DisplayMessage type="info" message={translate('reading_history.disclaimer')} />;
     };

     const getActionButtons = () => {
          return (
               <Box
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderBottomWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200"
                    flexWrap="nowrap">
                    <VStack space={2}>
                         <ScrollView horizontal>
                              <Button.Group size="sm" variant="solid" colorScheme="danger">
                                   <Button mr={1}>{translate('reading_history.opt_out')}</Button>
                                   <Button mr={1}>{translate('general.delete_all')}</Button>
                              </Button.Group>
                         </ScrollView>
                         <ScrollView horizontal>
                              <Button mr={1} size="sm">
                                   Sort By Last Used
                              </Button>
                         </ScrollView>
                    </VStack>
               </Box>
          );
     };

     const Empty = () => {
          return null;
     };

     const Paging = () => {
          return (
               <Box
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderTopWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200"
                    flexWrap="nowrap"
                    alignItems="center">
                    <ScrollView horizontal>
                         <Button.Group size="sm">
                              <Button onPress={() => setPage(page - 1)} isDisabled={page === 1}>
                                   {translate('general.previous')}
                              </Button>
                              <Button
                                   onPress={() => {
                                        if (!isPreviousData && data?.hasMore) {
                                             console.log('Adding to page');
                                             setPage(page + 1);
                                        }
                                   }}
                                   isDisabled={isPreviousData || !data?.hasMore}>
                                   {translate('general.next')}
                              </Button>
                         </Button.Group>
                    </ScrollView>
                    <Text mt={2} fontSize="sm">
                         Page {page} of {data?.totalPages}
                    </Text>
               </Box>
          );
     };

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2}>{getDisclaimer()}</Box>
               {user.trackReadingHistory !== '1' ? (
                    <Box safeArea={5}>
                         <Button>{translate('reading_history.opt_in')}</Button>
                    </Box>
               ) : (
                    <>
                         <Box
                              safeArea={2}
                              bgColor="coolGray.100"
                              borderBottomWidth="1"
                              _dark={{
                                   borderColor: 'gray.600',
                                   bg: 'coolGray.700',
                              }}
                              borderColor="coolGray.200"
                              flexWrap="nowrap">
                              <ScrollView horizontal>{getActionButtons()}</ScrollView>
                         </Box>
                         <FlatList data={readingHistory} ListEmptyComponent={Empty} ListFooterComponent={Paging} renderItem={({ item }) => <Item data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                    </>
               )}
          </SafeAreaView>
     );
};

const Item = (data) => {
     const { library } = React.useContext(LibrarySystemContext);
     const navigation = useNavigation();
     const item = data.item;

     const openGroupedWork = (item, title) => {
          const displayTitle = getTitle(title);
          navigation.navigate('GroupedWork', {
               id: item,
               title: displayTitle,
               url: library.baseUrl,
               userContext: user,
               libraryContext: library,
          });
     };

     return null;
};