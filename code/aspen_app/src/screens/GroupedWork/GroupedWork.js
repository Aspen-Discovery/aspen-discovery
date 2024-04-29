import { SearchIcon } from 'lucide-react-native';

import { Button, ButtonGroup, ButtonIcon, ButtonText, Box, Center, HStack, Text, SafeAreaView, ScrollView } from '@gluestack-ui/themed';
import { useRoute } from '@react-navigation/native';
import { useQueries, useQuery, useQueryClient } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import { useToken } from 'native-base';
import React from 'react';

// custom components and helper files
import { loadError } from '../../components/loadError';
import { loadingSpinner, LoadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { GroupedWorkContext, LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../context/initialContext';
import { startSearch } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getFirstRecord, getVariations } from '../../util/api/item';
import { getLinkedAccounts } from '../../util/api/user';
import { getGroupedWork } from '../../util/api/work';
import { decodeHTML, urldecode } from '../../util/apiAuth';
import { getPickupLocations } from '../../util/loadLibrary';
import AddToList from '../Search/AddToList';
import Variations from './Variations';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const GroupedWorkScreen = () => {
     const route = useRoute();
     const queryClient = useQueryClient();
     const id = route.params.id;
     const { user, locations, accounts, cards, updatePickupLocations, updateLinkedAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { language, updateGroupedWork, updateFormat } = React.useContext(GroupedWorkContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language: userLanguage } = React.useContext(LanguageContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const { theme, colorMode } = React.useContext(ThemeContext);

     const { status, data, error, isFetching } = useQuery(['groupedWork', id, userLanguage, library.baseUrl], () => getGroupedWork(route.params.id, userLanguage, library.baseUrl));

     React.useEffect(() => {
          let isSubscribed = true;
          if (!_.isUndefined(data) && !_.isEmpty(data)) {
               const update = async () => {
                    if (isSubscribed) {
                         updateGroupedWork(data);
                         updateFormat(data.format);
                         await getLinkedAccounts(user, cards, library.barcodeStyle, library.baseUrl, language).then((result) => {
                              if (accounts !== result.accounts) {
                                   updateLinkedAccounts(result.accounts);
                              }
                              if (cards !== result.cards) {
                                   updateLibraryCards(result.cards);
                              }
                         });
                         await getPickupLocations(library.baseUrl).then((result) => {
                              if (locations !== result) {
                                   updatePickupLocations(result);
                              }
                         });
                    }
               };
               update().catch(console.error);

               return () => (isSubscribed = false);
          }
     }, [data]);

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               {status === 'loading' || isFetching ? (
                    LoadingSpinner('Fetching data...')
               ) : status === 'error' ? (
                    loadError(error, '')
               ) : (
                    <ScrollView>
                         <Box sx={{ '@base': { height: 150 }, '@lg': { height: 200 } }} w="100%" bgColor={colorMode === 'light' ? theme['colors']['warmGray']['200'] : theme['colors']['coolGray']['900']} zIndex={-1} position="absolute" left={0} top={0} />
                         {_.size(systemMessages) > 0 ? <Box p="$2">{showSystemMessage()}</Box> : null}
                         <DisplayGroupedWork data={data.results} initialFormat={data.format} updateFormat={data.format} />
                    </ScrollView>
               )}
          </SafeAreaView>
     );
};

const DisplayGroupedWork = (payload) => {
     const backgroundColor = useToken('colors', 'warmGray.200');
     const groupedWork = payload.data;
     const route = useRoute();
     const id = route.params.id;
     const { format } = React.useContext(GroupedWorkContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { textColor } = React.useContext(ThemeContext);

     const formats = Object.keys(groupedWork.formats);
     if (_.isObject(formats)) {
          useQueries({
               queries: formats.map((format) => {
                    return {
                         queryKey: ['recordId', groupedWork.id, format, language, library.baseUrl],
                         queryFn: () => getFirstRecord(id, format, language, library.baseUrl, groupedWork.formats[format]),
                    };
               }),
          });
     }

     useQueries({
          queries: formats.map((format) => {
               return {
                    queryKey: ['variation', groupedWork.id, format, language, library.baseUrl],
                    queryFn: () => getVariations(id, format, language, library.baseUrl, groupedWork.formats[format]),
               };
          }),
     });

     const key = 'large_' + groupedWork.id;

     return (
          <Box p="$5" w="100%">
               <Center mt="$5" width="100%">
                    <Image alt={groupedWork.title} source={groupedWork.cover} style={{ width: 180, height: 250, borderRadius: 4 }} placeholder={blurhash} transition={1000} contentFit="cover" />
                    {getTitle(groupedWork.title)}
                    {getAuthor(groupedWork.author)}
               </Center>
               {getLanguage(groupedWork.language)}
               {getFormats(groupedWork.formats)}
               <Variations format={format} data={groupedWork} />
               <AddToList itemId={groupedWork.id} btnStyle="lg" />
               {getDescription(groupedWork.description)}
          </Box>
     );
};

const getTitle = (title) => {
     const { textColor } = React.useContext(ThemeContext);
     if (title) {
          return (
               <>
                    <Text color={textColor} sx={{ '@base': { fontSize: 16, lineHeight: 19 }, '@lg': { fontSize: 24, lineHeight: 27 } }} bold pt="$5" alignText="center">
                         {title}
                    </Text>
               </>
          );
     } else {
          return null;
     }
};

const getAuthor = (author) => {
     const { library } = React.useContext(LibrarySystemContext);
     const { theme } = React.useContext(ThemeContext);
     if (author) {
          return (
               <Button size="sm" variant="link" onPress={() => startSearch(author, 'SearchResults', library.baseUrl)}>
                    <ButtonIcon as={SearchIcon} color={theme['colors']['tertiary']['400']} size="xs" mr="$1" />
                    <ButtonText fontWeight="normal" color={theme['colors']['tertiary']['400']}>
                         {author}
                    </ButtonText>
               </Button>
          );
     }
     return null;
};

const Format = (data) => {
     const format = data.data;
     const key = data.format;
     const isSelected = data.isSelected;
     const updateFormat = data.updateFormat;
     const btnStyle = isSelected === key ? 'solid' : 'outline';
     const { theme } = React.useContext(ThemeContext);

     if (isSelected === key) {
          updateFormat(key);
     }

     return (
          <Button size="sm" bg={btnStyle === 'outline' ? 'transparent' : theme['colors']['secondary']['400']} borderColor={theme['colors']['secondary']['400']} mb="$1" mr="$1" variant={btnStyle} onPress={() => updateFormat(key)}>
               <ButtonText color={btnStyle === 'outline' ? theme['colors']['secondary']['400'] : theme['colors']['secondary']['400-text']}>{format.label}</ButtonText>
          </Button>
     );
};

const getDescription = (description) => {
     const { theme, textColor } = React.useContext(ThemeContext);
     if (description) {
          return (
               <Text mt="$5" mb="$5" sx={{ '@base': { fontSize: 14, lineHeight: 21 }, '@lg': { fontSize: 20, lineHeight: 27 } }} color={textColor}>
                    {decodeHTML(description)}
               </Text>
          );
     } else {
          return null;
     }
};

const getLanguage = (language) => {
     const { language: user_language } = React.useContext(LanguageContext);
     const { theme, textColor } = React.useContext(ThemeContext);
     if (language) {
          return (
               <HStack mt="$3" mb="$1">
                    <Text sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }} bold color={textColor}>
                         {getTermFromDictionary(user_language, 'language')}:
                    </Text>
                    <Text sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }} ml="$1" color={textColor}>
                         {' '}
                         {language}
                    </Text>
               </HStack>
          );
     } else {
          return null;
     }
};

const getFormats = (formats) => {
     const { language } = React.useContext(LanguageContext);
     const { format, updateFormat } = React.useContext(GroupedWorkContext);
     const { theme, textColor } = React.useContext(ThemeContext);
     if (formats) {
          return (
               <>
                    <Text sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }} bold mt="$3" mb="$1" color={textColor}>
                         {getTermFromDictionary(language, 'format')}:
                    </Text>
                    <ButtonGroup flexDirection="row" flexWrap="wrap">
                         {_.map(_.keys(formats), function (item, index, array) {
                              return <Format key={index} format={item} data={formats[item]} isSelected={format} updateFormat={updateFormat} />;
                         })}
                    </ButtonGroup>
               </>
          );
     } else {
          return null;
     }
};