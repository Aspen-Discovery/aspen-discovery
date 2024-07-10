import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useRoute, useNavigation, CommonActions, StackActions } from '@react-navigation/native';
import { Box, FlatList, HStack, Switch, Text, Pressable, ChevronLeftIcon } from 'native-base';
import React from 'react';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { BrowseCategoryContext, LanguageContext, LibrarySystemContext, ThemeContext } from '../../../context/initialContext';

import { getBrowseCategoryListForUser, updateBrowseCategoryStatus } from '../../../util/loadPatron';

export const Settings_BrowseCategories = () => {
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(false);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { list, updateBrowseCategoryList } = React.useContext(BrowseCategoryContext);
     const { theme } = React.useContext(ThemeContext);
     const route = useRoute();
     console.log(route.params);

     const handleGoBack = () => {
          if (route?.params?.prevRoute === 'HomeScreen') {
               navigation.dispatch(CommonActions.setParams({ prevRoute: null }));
               navigation.dispatch(StackActions.replace('MoreMenu'));
          } else {
               navigation.goBack();
          }
     };

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => (
                    <Pressable onPress={handleGoBack} mr={3} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                         <ChevronLeftIcon size="md" ml={1} color={theme['colors']['primary']['baseContrast']} />
                    </Pressable>
               ),
          });
     }, [navigation]);

     const { status, data, error, isFetching } = useQuery(['browse_categories_list', library.baseUrl, language], () => getBrowseCategoryListForUser(library.baseUrl), {
          initialData: list,
          onSuccess: (data) => {
               updateBrowseCategoryList(data);
               setLoading(false);
          },
          onSettle: (data) => {
               setLoading(false);
          },
          placeholderData: [],
     });

     if (loading || isFetching) {
          return loadingSpinner();
     }

     return <FlatList keyExtractor={(item) => item.key} data={list} renderItem={({ item }) => <DisplayCategory data={item} setLoading={setLoading} />} />;
};

const DisplayCategory = (data) => {
     const queryClient = useQueryClient();
     const category = data.data;
     const setLoading = data.setLoading;
     const [toggled, setToggle] = React.useState(!category.isHidden);
     const toggleSwitch = () => setToggle((previousState) => !previousState);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { maxNum } = React.useContext(BrowseCategoryContext);

     const updateToggle = async (category) => {
          setLoading(true);
          const key = category['key'] ?? category['sourceId'];
          await updateBrowseCategoryStatus(key, library.baseUrl).then(async (response) => {
               queryClient.invalidateQueries({ queryKey: ['browse_categories', library.baseUrl, language, maxNum] });
               await queryClient.invalidateQueries({ queryKey: ['browse_categories_list', library.baseUrl, language] });
          });

          console.log(key + ', ' + category['isHidden']);
     };
     return (
          <Box borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
                    <Text
                         flexWrap="wrap"
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