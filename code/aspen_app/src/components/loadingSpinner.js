import React from 'react';
import { Center, Heading, HStack, VStack, Spinner } from '@gluestack-ui/themed-native-base';
import { ThemeContext } from '../context/initialContext';

/*
TODO: Translate the accessibility labels
*/

export function loadingSpinner(message = '') {
     if (message !== '') {
          return (
               <Center flex={1} px="3">
                    <VStack space={2} alignItems="center">
                         <Spinner size="lg" accessibilityLabel="Loading..." />
                         <Heading fontSize="md">{message}</Heading>
                    </VStack>
               </Center>
          );
     }

     return (
          <Center flex={1}>
               <HStack>
                    <Spinner size="lg" accessibilityLabel="Loading..." />
               </HStack>
          </Center>
     );
}

export const LoadingSpinner = (message) => {
     const { colorMode, theme, textColor } = React.useContext(ThemeContext);
     if (message && message !== '') {
          return (
               <Center flex={1} px="$3">
                    <VStack space="md" alignItems="center">
                         <Spinner size="lg" accessibilityLabel="Loading..." />
                         <Heading fontSize="md" color={textColor}>
                              {message}
                         </Heading>
                    </VStack>
               </Center>
          );
     }

     return (
          <Center flex={1}>
               <HStack>
                    <Spinner size="lg" accessibilityLabel="Loading..." />
               </HStack>
          </Center>
     );
};