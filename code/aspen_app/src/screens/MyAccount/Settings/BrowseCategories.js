import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Box, FlatList, HStack, Switch, Text } from 'native-base';
import React from 'react';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { BrowseCategoryContext, LanguageContext, LibrarySystemContext } from '../../../context/initialContext';

import { getBrowseCategoryListForUser, updateBrowseCategoryStatus } from '../../../util/loadPatron';

export const Settings_BrowseCategories = () => {
     const [loading, setLoading] = React.useState(true);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { list, updateBrowseCategoryList } = React.useContext(BrowseCategoryContext);

     useQuery(['browse_categories_list', library.baseUrl, language], () => getBrowseCategoryListForUser(library.baseUrl), {
          onSuccess: (data) => {
               updateBrowseCategoryList(data);
               setLoading(false);
          },
          placeholderData: [],
     });

     if (loading) {
          return loadingSpinner();
     }

     return <FlatList keyExtractor={(item) => item.key} data={list} renderItem={({ item }) => <DisplayCategory data={item} />} />;
};

const DisplayCategory = (data) => {
     const queryClient = useQueryClient();
     const category = data.data;
     const [toggled, setToggle] = React.useState(!category.isHidden);
     const toggleSwitch = () => setToggle((previousState) => !previousState);
     const { library } = React.useContext(LibrarySystemContext);

     const updateToggle = async (category) => {
          const key = category['key'] ?? category['sourceId'];

          await updateBrowseCategoryStatus(key, library.baseUrl).then(async (response) => {
               queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl] });
               queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl] });
          });

          console.log(key + ', ' + category['isHidden']);
     };
     return (
          <Box borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
                    <Text
                         isTruncated
                         bold
                         maxW="80%"
                         fontSize={{
                              base: 'lg',
                              lg: 'xl',
                         }}>
                         {category.title}
                    </Text>
                    <Switch
                         size="md"
                         name={category.key}
                         onToggle={() => {
                              toggleSwitch();
                              updateToggle(category);
                         }}
                         isChecked={toggled}
                    />
               </HStack>
          </Box>
     );
};