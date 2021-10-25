import React, { Component } from 'react';
import { Center, HStack, Icon, Heading, Text, Button } from 'react-native';
import { MaterialIcons } from '@expo/vector-icons';

const Error = (props) => {

    const { message, reload } = props;

     return(
     <>
        <Center flex={1}>
         <HStack>
              <Icon as={MaterialIcons} name="error" size={{ base: "md", lg: "lg"}} mt={.5} mr={1} color="error.500" />
              <Heading color="error.500" mb={2}>Error</Heading>
         </HStack>
         <Text bold w={{ base: "75%", lg: "50%"}} textAlign="center" fontSize={{ base: "md", lg: "xl"}}>There was an error loading results from the library. Please try again.</Text>
          <Button
              mt={5}
              size={{ base: "md", lg: "lg"}}
              colorScheme="primary"

              startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
          >
              Reload
          </Button>
          <Text fontSize={{ base: "xs", lg: "md"}} w={{ base: "75%", lg: "50%"}} mt={5} color="muted.500" textAlign="center">ERROR: {message}</Text>
         </Center>
         </>
     )
}

export default Error;