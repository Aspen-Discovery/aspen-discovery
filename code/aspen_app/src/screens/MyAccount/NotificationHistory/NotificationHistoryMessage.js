import React from 'react';
import _ from 'lodash';

import { useRoute, useNavigation } from '@react-navigation/native';
import { Heading, Box, Button, ButtonText, ButtonGroup, ScrollView, Text, VStack } from '@gluestack-ui/themed';

export const MyNotificationHistoryMessage = () => {
     const navigation = useNavigation();
     const route = useRoute();
     let routeB = navigation.getParent().getState().routes;
     console.log(routeB);
     routeB = _.filter(routeB, { name: 'MyNotificationHistoryMessageModal' });
     console.log(routeB);
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