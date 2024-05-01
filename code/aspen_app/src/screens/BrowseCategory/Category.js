import { Button, ButtonGroup, ButtonIcon, ButtonText, FlatList, View, HStack, Pressable, Text, SafeAreaView } from '@gluestack-ui/themed';
import { XIcon } from 'lucide-react-native';
import _ from 'lodash';
import React from 'react';

import { LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

const DisplayBrowseCategory = (props) => {
     const { language, id, renderRecords, libraryUrl, records, categoryLabel, categoryKey, loadMore, categorySource, discoveryVersion, onPressCategory, categoryList, hideCategory, isHidden, textColor } = props;

     const hide = getTermFromDictionary(language, 'hide');
     let key = categoryKey;
     if (id) {
          key = id;
     }

     //console.log(categoryLabel + ': ' + isHidden);
     if (isHidden) {
          return null;
     }

     //console.log(records);

     if (typeof records !== 'undefined' || typeof records !== 'subCategories') {
          let newArr = [];
          if (typeof records !== 'undefined' && !_.isNull(records)) {
               newArr = Object.values(records);
          }
          const recordCount = newArr.length;
          if (newArr.length > 0) {
               return (
                    <LibrarySystemContext.Consumer>
                         {(library) => (
                              <SafeAreaView>
                                   <View
                                        pb="$5"
                                        sx={{
                                             '@base': {
                                                  height: categorySource === 'SavedSearch' ? 240 : 225,
                                             },
                                             '@lg': {
                                                  height: 325,
                                             },
                                        }}>
                                        <HStack space="$3" alignItems="center" justifyContent="space-between" pb="$2">
                                             {library.version >= '22.10.00' ? (
                                                  <Pressable onPress={() => onPressCategory(categoryLabel, categoryKey, categorySource)} maxWidth="80%" mb="$1">
                                                       <Text
                                                            color={textColor}
                                                            sx={{
                                                                 '@base': {
                                                                      fontSize: 16,
                                                                 },
                                                                 '@lg': {
                                                                      fontSize: 22,
                                                                 },
                                                            }}
                                                            bold>
                                                            {categoryLabel}
                                                       </Text>
                                                  </Pressable>
                                             ) : (
                                                  <Text
                                                       maxWidth="80%"
                                                       mb="$1"
                                                       bold
                                                       color={textColor}
                                                       size={{
                                                            base: 'lg',
                                                            lg: '2xl',
                                                       }}>
                                                       {categoryLabel}
                                                  </Text>
                                             )}

                                             <Button size="xs" variant="link" onPress={() => hideCategory(libraryUrl, key)}>
                                                  <ButtonIcon as={XIcon} color={textColor} mr="$1" />
                                                  <ButtonText fontWeight="$medium" sx={{ color: textColor }}>
                                                       {hide}
                                                  </ButtonText>
                                             </Button>
                                        </HStack>
                                        <FlatList horizontal data={newArr} keyExtractor={(item, index) => index.toString()} renderItem={(item, index) => renderRecords(item, library.baseUrl, library.version, index)} initialNumToRender={5} ListFooterComponent={loadMore(categoryLabel, categoryKey, libraryUrl, categorySource, recordCount, discoveryVersion)} extra={categoryList} />
                                   </View>
                              </SafeAreaView>
                         )}
                    </LibrarySystemContext.Consumer>
               );
          }
     }

     return null;
};

export default DisplayBrowseCategory;