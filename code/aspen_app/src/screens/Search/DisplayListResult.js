import React from 'react';
import { Badge, BadgeText, Box, HStack, Pressable, Text, VStack, Button, ButtonText, ButtonIcon, Center } from '@gluestack-ui/themed';
import { LanguageContext, LibrarySystemContext, ThemeContext, UserContext } from '../../context/initialContext';
import { TrashIcon } from 'lucide-react-native';
import { useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { getCleanTitle } from '../../helpers/item';
import { navigateStack } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { removeTitlesFromList } from '../../util/api/list';
import AddToList from './AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const DisplayListResult = (props) => {
     const item = props.data;
     const isUserList = props.isUserList;
     const listId = props.listId;
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const queryClient = useQueryClient();

     const { theme, textColor, colorMode } = React.useContext(ThemeContext);

     const backgroundColor = colorMode === 'light' ? theme['colors']['warmGray']['200'] : theme['colors']['coolGray']['900'];

     let recordType = 'grouped_work';
     if (item.recordtype) {
          recordType = item.recordtype;
     }
     const imageUrl = library.baseUrl + '/bookcover.php?id=' + item.id + '&size=medium&type=' + recordType;
     const key = 'medium_' + item.id;
     const handlePressItem = () => {
          if (item) {
               if (recordType === 'list') {
                    navigateStack('BrowseTab', 'ListResults', {
                         id: item.id,
                         title: item.title_display,
                         url: library.baseUrl,
                         prevRoute: 'SearchByList',
                    });
               } else {
                    navigateStack('BrowseTab', 'ListResultItem', {
                         id: item.id,
                         title: getCleanTitle(item.title_display),
                         url: library.baseUrl,
                         libraryContext: library,
                         prevRoute: 'SearchByList',
                    });
               }
          }
     };

     return (
          <Pressable borderBottomWidth={1} borderColor={colorMode === 'light' ? theme['colors']['warmGray']['400'] : theme['colors']['gray']['600']} pl="$4" pr="$5" py="$2" onPress={handlePressItem}>
               <HStack space="md">
                    <VStack sx={{ '@base': { width: 100 }, '@lg': { width: 180 } }}>
                         <Box sx={{ '@base': { height: 150 }, '@lg': { height: 250 } }}>
                              <Image
                                   alt={item.title_display}
                                   source={imageUrl}
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
                              <Center>
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
                         {isUserList ? (
                              <Button
                                   onPress={() => {
                                        removeTitlesFromList(listId, item.id, library.baseUrl).then(async () => {
                                             queryClient.invalidateQueries({ queryKey: ['list', listId] });
                                             queryClient.invalidateQueries({ queryKey: ['searchResultsForList', library.baseUrl, 1, listId, language] });
                                        });
                                   }}
                                   colorScheme="danger"
                                   size="sm"
                                   variant="ghost">
                                   <ButtonIcon as={TrashIcon} />
                                   <ButtonText>{getTermFromDictionary(language, 'delete')}</ButtonText>
                              </Button>
                         ) : (
                              <AddToList itemId={item.id} btnStyle="sm" />
                         )}
                    </VStack>
                    <VStack w="65%" pt="$1">
                         <Text color={textColor} bold sx={{ '@base': { fontSize: 14, lineHeight: 17, paddingBottom: 4 }, '@lg': { fontSize: 22, lineHeight: 25, paddingBottom: 4 } }}>
                              {item.title_display}
                         </Text>
                         {item.author_display ? (
                              <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                   {getTermFromDictionary(language, 'by')} {item.author_display}
                              </Text>
                         ) : null}
                         {item.format ? (
                              <HStack mt="$4" direction="row" space="xs" flexWrap="wrap">
                                   {item.format.map((format, i) => {
                                        return (
                                             <Badge key={i} borderRadius="$sm" borderColor={theme['colors']['secondary']['400']} variant="outline" bg="transparent">
                                                  <BadgeText textTransform="none" color={theme['colors']['secondary']['400']} sx={{ '@base': { fontSize: 10, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                                                       {format}
                                                  </BadgeText>
                                             </Badge>
                                        );
                                   })}
                              </HStack>
                         ) : null}
                    </VStack>
               </HStack>
          </Pressable>
     );
};