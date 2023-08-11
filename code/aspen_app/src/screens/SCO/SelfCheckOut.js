import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';
import { Box, Button, Text, Heading, Center, HStack, Icon, FlatList } from 'native-base';
import { useNavigation } from '@react-navigation/native';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { navigateStack } from '../../helpers/RootNavigator';
import { Ionicons } from '@expo/vector-icons';
import _ from 'lodash';

export const SelfCheckOut = () => {
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);
     const [items, setItems] = React.useState([]);

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     const openScanner = async () => {
          navigateStack('SelfCheckTab', 'SelfCheckOutScanner');
     };

     const finishSession = () => {
          navigateStack('SelfCheckTab', 'FinishCheckOutSession');
     };

     const currentCheckoutHeader = () => {
          if (_.size(items) >= 1) {
               return (
                    <HStack space={4} justifyContent="space-between" pb={2}>
                         <Text bold fontSize="xs" w="70%">
                              {getTermFromDictionary(language, 'title')}
                         </Text>
                         <Text bold fontSize="xs" w="25%">
                              {getTermFromDictionary(language, 'checkout_due')}
                         </Text>
                    </HStack>
               );
          }
          return null;
     };

     const currentCheckOutItem = (item) => {
          return (
               <HStack space={4} justifyContent="space-between">
                    <Text fontSize="xs" w="70%">
                         {item.title}
                    </Text>
                    <Text fontSize="xs" w="25%">
                         {item.due}
                    </Text>
               </HStack>
          );
     };

     const currentCheckOutEmpty = () => {
          return <Text>{getTermFromDictionary(language, 'no_items_checked_out')}</Text>;
     };

     const currentCheckOutFooter = () => {};

     return (
          <Box safeArea={5} w="100%">
               <Center pb={5}>
                    <Text pb={3}>You are checking out as Samwise</Text>
                    <Button leftIcon={<Icon as={<Ionicons name="barcode-outline" />} size={6} mr="1" />} colorScheme="secondary" onPress={() => openScanner()}>
                         {getTermFromDictionary(language, 'add_new_item')}
                    </Button>
               </Center>
               <Heading fontSize="md" pb={2}>
                    {getTermFromDictionary(language, 'checked_out_during_session')}
               </Heading>
               <FlatList data={items} keyExtractor={(item, index) => index.toString()} ListEmptyComponent={currentCheckOutEmpty()} ListHeaderComponent={currentCheckoutHeader()} renderItem={({ item }) => currentCheckOutItem(item)} />
               <Center pt={5}>
                    <Button onPress={() => finishSession()} colorScheme="primary" size="sm">
                         {getTermFromDictionary(language, 'button_finish')}
                    </Button>
               </Center>
          </Box>
     );
};