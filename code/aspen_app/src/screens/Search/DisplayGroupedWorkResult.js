import { Badge, BadgeText, Box, Center, HStack, Pressable, Text, VStack } from '@gluestack-ui/themed';
import CachedImage from 'expo-cached-image';
import _ from 'lodash';
import React from 'react';

// custom components and helper files
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../context/initialContext';
import { getCleanTitle } from '../../helpers/item';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import AddToList from './AddToList';

export const DisplayGroupedWorkResult = (props) => {
     const item = props.data;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);

     const formats = item?.itemList ?? [];
     const id = item.key ?? item.id;

     const handlePressItem = () => {
          navigate('GroupedWorkScreen', {
               id: id,
               title: getCleanTitle(item.title),
               url: library.baseUrl,
          });
     };

     function getFormat(n) {
          return (
               <Badge key={n.key} borderRadius="$sm" borderColor={theme['colors']['secondary']['400']} variant="outline" bg="transparent">
                    <BadgeText textTransform="none" color={theme['colors']['secondary']['400']} sx={{ '@base': { fontSize: 10, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                         {n.name}
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
                              <CachedImage
                                   cacheKey={key}
                                   alt={item.title}
                                   source={{
                                        uri: `${url}`,
                                        expiresIn: 86400,
                                   }}
                                   style={{
                                        width: '100%',
                                        height: '100%',
                                        borderRadius: 4,
                                   }}
                                   resizeMode="cover"
                                   placeholderContent={
                                        <Box
                                             bg={colorMode === 'light' ? theme['colors']['warmGray']['50'] : theme['colors']['coolGray']['800']}
                                             width={{
                                                  base: 100,
                                                  lg: 200,
                                             }}
                                             height={{
                                                  base: 150,
                                                  lg: 250,
                                             }}
                                        />
                                   }
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
                         {item.title ? (
                              <Text color={textColor} bold sx={{ '@base': { fontSize: 14, lineHeight: 17, paddingBottom: 4 }, '@lg': { fontSize: 22, lineHeight: 25, paddingBottom: 4 } }}>
                                   {item.title}
                              </Text>
                         ) : null}
                         {item.author ? (
                              <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                   {getTermFromDictionary(language, 'by')} {item.author}
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