import { MaterialIcons } from '@expo/vector-icons';
import _ from 'lodash';
import { Button, HStack, Icon, Text, View, Pressable, FlatList } from 'native-base';
import React from 'react';

import { translate } from '../../translations/translations';
import { LibrarySystemContext } from '../../context/initialContext';
import { SafeAreaView } from 'react-native';

const DisplayBrowseCategory = (props) => {
     const { id, user, renderRecords, libraryUrl, records, categoryLabel, categoryKey, loadMore, categorySource, discoveryVersion, onPressCategory, categoryList, hideCategory, isHidden } = props;

     let key = categoryKey;
     if (id) {
          key = id;
     }

     //console.log(categoryLabel + ': ' + isHidden);
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
                                   <View pb={5} height="225">
                                        <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
                                             {library.version >= '22.10.00' ? (
                                                  <Pressable onPress={() => onPressCategory(categoryLabel, categoryKey, categorySource)} maxWidth="80%" mb={1}>
                                                       <Text
                                                            bold
                                                            fontSize={{
                                                                 base: 'lg',
                                                                 lg: '2xl',
                                                            }}>
                                                            {categoryLabel}
                                                       </Text>
                                                  </Pressable>
                                             ) : (
                                                  <Text
                                                       maxWidth="80%"
                                                       mb={1}
                                                       bold
                                                       fontSize={{
                                                            base: 'lg',
                                                            lg: '2xl',
                                                       }}>
                                                       {categoryLabel}
                                                  </Text>
                                             )}

                                             <Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => hideCategory(libraryUrl, key)} startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5} />}>
                                                  {translate('general.hide')}
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