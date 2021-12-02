import React, { Component } from "react";
import { Center, HStack, Spinner, Heading } from "native-base";

export function loadingSpinner(message = null) {
    if(message) {
        return (
        <Center flex={1} px="3">
            <HStack space={2} alignItems="center">
              <Spinner accessibilityLabel="Loading..." />
              <Heading fontSize="md">
                {message}
              </Heading>
            </HStack>
        </Center>
        );
    }

    return (
        <Center flex={1}>
            <HStack>
                <Spinner accessibilityLabel="Loading..." />
            </HStack>
        </Center>
    );
}