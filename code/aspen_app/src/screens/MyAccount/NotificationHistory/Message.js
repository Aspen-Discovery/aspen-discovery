import React from 'react';
import { SafeAreaView } from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Heading, Box, Button, ButtonText, ButtonGroup, Center, FlatList, HStack, Icon, Pressable, ScrollView, Text, VStack } from '@gluestack-ui/themed';

export const MyMessage = () => {
     const navigation = useNavigation();
     const route = useRoute();
     const defaultMessage = {
          title: '',
          content: '',
          isRead: 0,
          dateSent: null,
     };
     const message = useRoute().params?.data ?? defaultMessage;
     console.log(route);
     //console.log(message);
     return (
          <ScrollView>
               <Box p="$5"></Box>
          </ScrollView>
     );
};