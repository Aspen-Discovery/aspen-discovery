import { Badge, BadgeText, Box, Center, HStack, Pressable, Text, VStack } from '@gluestack-ui/themed';
import { useRoute } from '@react-navigation/native';
import CachedImage from 'expo-cached-image';
import { Image } from 'expo-image';
import _ from 'lodash';
import React from 'react';

// custom components and helper files
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../context/initialContext';
import { getCleanTitle } from '../../helpers/item';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getFormats } from '../../util/search';
import AddToList from './AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const DisplayGroupedWorkResult = (props) => {
     const item = props.data;
     let params = useRoute();
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);

     let formats = item?.itemList ?? [];
     const id = item.key ?? item.id;

     let title;
     if (item.title) {
          title = item.title;
     } else if (item.title_display) {
          title = item.title_display;
     }

     let author;
     if (item.author) {
          author = item.author;
     } else if (item.author_display) {
          author = item.author_display;
     }

     if (_.isEmpty(formats)) {
          if (item.format) {
               formats = item.format;
          }
     }

     if (params.name === 'SearchBySavedSearch') {
          formats = getFormats(formats);
     }

     const handlePressItem = () => {
          navigate('GroupedWorkScreen', {
               id: id,
               title: getCleanTitle(title),
               url: library.baseUrl,
          });
     };

     function getFormat(n) {
          if (_.isArray(n) || _.isObject(n)) {
               return (
                    <Badge key={n.key} borderRadius="$sm" borderColor={theme['colors']['secondary']['400']} variant="outline" bg="transparent">
                         <BadgeText textTransform="none" color={theme['colors']['secondary']['400']} sx={{ '@base': { fontSize: 10, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                              {n.name}
                         </BadgeText>
                    </Badge>
               );
          }

          return (
               <Badge key={n} borderRadius="$sm" borderColor={theme['colors']['secondary']['400']} variant="outline" bg="transparent">
                    <BadgeText textTransform="none" color={theme['colors']['secondary']['400']} sx={{ '@base': { fontSize: 10, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                         {n}
                    </BadgeText>
               </Badge>
          );
     }

     const key = 'medium_' + id;

     let url = library.baseUrl + '/bookcover.php?id=' + id + '&size=medium';

     return (
          <Pressable borderBottomWidth={1} borderColor={colorMode === 'light' ? theme['colors']['warmGray']['400'] : theme['colors']['gray']['600']} pl="$4" pr="$5" py="$2" onPress={handlePressItem}>
               <HStack space="md">
                    <VStack sx={{ '@base': { width: 100 }, '@lg': { width: 180 } }}>
                         <Box sx={{ '@base': { height: 150 }, '@lg': { height: 250 } }}>
                              <Image
                                   alt={item.title}
                                   source={url}
                                   style={{
                                        width: '100%',
                                        height: '100%',
                                        borderRadius: 4,
                                   }}
                                   placeholder={blurhash}
                                   transition={1000}
                                   contentFit="cover"
                              />
                         </Box>
                         {item.language ? (
                              <Center
                                   mt="$1"
                                   sx={{
                                        bgColor: colorMode === 'light' ? theme['colors']['warmGray']['200'] : theme['colors']['coolGray']['900'],
                                   }}>
                                   <Badge
                                        size="$sm"
                                        sx={{
                                             bgColor: colorMode === 'light' ? theme['colors']['warmGray']['200'] : theme['colors']['coolGray']['900'],
                                        }}>
                                        <BadgeText textTransform="none" color={colorMode === 'light' ? theme['colors']['coolGray']['600'] : theme['colors']['warmGray']['400']} sx={{ '@base': { fontSize: 10 }, '@lg': { fontSize: 16, padding: 4, textAlign: 'center' } }}>
                                             {item.language}
                                        </BadgeText>
                                   </Badge>
                              </Center>
                         ) : null}
                         <AddToList itemId={id} btnStyle="sm" />
                    </VStack>
                    <VStack w="65%" pt="$1">
                         {title ? (
                              <Text color={textColor} bold sx={{ '@base': { fontSize: 14, lineHeight: 17, paddingBottom: 4 }, '@lg': { fontSize: 22, lineHeight: 25, paddingBottom: 4 } }}>
                                   {title}
                              </Text>
                         ) : null}
                         {author ? (
                              <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                   {getTermFromDictionary(language, 'by')} {author}
                              </Text>
                         ) : null}
                         <HStack mt="$4" direction="row" space="xs" flexWrap="wrap">
                              {_.map(formats, getFormat)}
                         </HStack>
                    </VStack>
               </HStack>
          </Pressable>
     );
};