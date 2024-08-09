import React from 'react';
import { ChevronRight, Dot } from 'lucide-react-native';
import { SafeAreaView } from 'react-native';
import _ from 'lodash';
import { useRoute, useNavigation } from '@react-navigation/native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { loadError } from '../../../components/loadError';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../../components/Notifications';
import { LanguageContext, LibrarySystemContext, SystemMessagesContext, ThemeContext, UserContext } from '../../../context/initialContext';
import { Heading, Box, Button, ButtonText, ButtonGroup, Center, FlatList, HStack, Icon, Pressable, ScrollView, Text, VStack } from '@gluestack-ui/themed';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { fetchSavedEvents } from '../../../util/api/event';
import { stripHTML } from '../../../util/apiAuth';

export const MyMessage = () => {
     const defaultMessage = {
          title: '',
          content: '',
          isRead: 0,
          dateSent: null,
     };
     const message = useRoute().params?.data ?? defaultMessage;
     console.log(useRoute().params);
     console.log(message);
     return (
          <ScrollView>
               <Box p="$5"></Box>
          </ScrollView>
     );
};