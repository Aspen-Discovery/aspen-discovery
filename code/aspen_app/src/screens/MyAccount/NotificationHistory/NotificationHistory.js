import React from 'react';
import { ChevronRight, Dot } from 'lucide-react-native';
import { SafeAreaView } from 'react-native';
import _ from 'lodash';
import { useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { loadError } from '../../../components/loadError';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { Heading, Box, Button, ButtonText, ButtonGroup, Center, FlatList, HStack, Icon, Pressable, ScrollView, Text, VStack } from '@gluestack-ui/themed';
import { navigate } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { fetchSavedEvents } from '../../../util/api/event';
import { stripHTML } from '../../../util/apiAuth';
import { CommonActions } from '@react-navigation/native';

export const MyNotificationHistory = () => {
     const navigation = useNavigation();
     const queryClient = useQueryClient();
     const [isLoading, setLoading] = React.useState(false);
     const [page, setPage] = React.useState(1);
     const [paginationLabel, setPaginationLabel] = React.useState('Page 1 of 1');
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     const { user, notificationHistory, updateNotificationHistory, inbox, updateInbox } = React.useContext(UserContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const url = library.baseUrl;
     const pageSize = 25;
     const systemMessagesForScreen = [];

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     React.useEffect(() => {
          if (_.isArray(systemMessages)) {
               systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1') {
                         systemMessagesForScreen.push(obj);
                    }
               });
          }
     }, [systemMessages]);

     const { status, data, error, isFetching, isPreviousData } = useQuery(['notification_history', user.id, library.baseUrl, page], () => fetchSavedEvents(page, pageSize, library.baseUrl), {
          initialData: notificationHistory,
          keepPreviousData: true,
          staleTime: 1000,
          onSuccess: (data) => {
               updateNotificationHistory(data);
               updateInbox(data?.inbox ?? []);
               if (data.totalPages) {
                    let tmp = getTermFromDictionary(language, 'page_of_page');
                    tmp = tmp.replace('%1%', page);
                    tmp = tmp.replace('%2%', data.totalPages);
                    console.log(tmp);
                    setPaginationLabel(tmp);
               }
          },
          onSettle: (data) => setLoading(false),
     });

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

     const Empty = () => {
          return (
               <>
                    {_.size(systemMessagesForScreen) > 0 ? <Box p="$2">{showSystemMessage()}</Box> : null}
                    <Center flex={1}>
                         <Heading pt="$5">{getTermFromDictionary(language, 'notification_history_empty')}</Heading>
                    </Center>
               </>
          );
     };

     const Paging = () => {
          if (data?.totalResults > 0) {
               return (
                    <Box p="$2" bgColor={colorMode === 'light' ? theme['colors']['coolGray']['100'] : theme['colors']['coolGray']['700']} borderTopWidth={1} borderColor={colorMode === 'light' ? theme['colors']['coolGray']['200'] : theme['colors']['gray']['600']} flexWrap="nowrap" alignItems="center">
                         <ScrollView horizontal>
                              <ButtonGroup>
                                   <Button onPress={() => setPage(page - 1)} isDisabled={page === 1} size="sm" bgColor={theme['colors']['primary']['500']}>
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'previous')}</ButtonText>
                                   </Button>
                                   <Button
                                        bgColor={theme['colors']['primary']['500']}
                                        onPress={() => {
                                             if (!isPreviousData && data.hasMore) {
                                                  console.log('Adding to page');
                                                  setPage(page + 1);
                                             }
                                        }}
                                        isDisabled={isPreviousData || !data?.hasMore}
                                        size="sm">
                                        <ButtonText color={theme['colors']['primary']['500-text']}>{getTermFromDictionary(language, 'next')}</ButtonText>
                                   </Button>
                              </ButtonGroup>
                         </ScrollView>
                         <Text mt="$2" fontSize={10} color={textColor}>
                              {paginationLabel}
                         </Text>
                    </Box>
               );
          }
          return null;
     };

     const handleOpenMyMessage = (item) => {
          navigate('MyNotificationHistoryMessageModal', {
               message: item,
          });
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {_.size(systemMessagesForScreen) > 0 ? <Box safeArea="$2">{showSystemMessage()}</Box> : null}
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
                    <>
                         <FlatList data={inbox} ListEmptyComponent={Empty} ListFooterComponent={Paging} renderItem={({ item }) => <Item data={item} handleOpenMyMessage={handleOpenMyMessage} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
                    </>
               )}
          </SafeAreaView>
     );
};

const Item = (data) => {
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     const message = data.data;
     const handleOpenMyMessage = data.handleOpenMyMessage;
     let content = stripHTML(message.content);
     content = _.truncate(content, { length: 35 });

     return (
          <Pressable onPress={() => handleOpenMyMessage(message)} borderBottomWidth="$1" borderColor={colorMode === 'light' ? theme['colors']['warmGray']['300'] : theme['colors']['coolGray']['500']} pl="$4" pr="$5" py="$2">
               <HStack alignItems="start">
                    {message.isRead === '0' ? (
                         <Box width="7%">
                              <Icon as={Dot} color={textColor} />
                         </Box>
                    ) : (
                         <Box width="7%" />
                    )}
                    <VStack width="86%">
                         {message.isRead === '0' ? (
                              <Text bold color={textColor} sx={{ '@base': { fontSize: 14, lineHeight: 16 }, '@lg': { fontSize: 18, lineHeight: 22 } }}>
                                   {message.title}
                              </Text>
                         ) : (
                              <Text color={textColor} sx={{ '@base': { fontSize: 14, lineHeight: 16 }, '@lg': { fontSize: 18, lineHeight: 22 } }}>
                                   {message.title}
                              </Text>
                         )}
                         <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                              {content}
                         </Text>
                    </VStack>
                    <Box width="7%">
                         <Icon as={ChevronRight} color={textColor} />
                    </Box>
               </HStack>
          </Pressable>
     );
};