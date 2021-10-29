import React, { Component } from "react";
import { Center, HStack, Spinner } from "native-base";

export function loadingSpinner() {
    return (
        <Center flex={1}>
            <HStack>
                <Spinner accessibilityLabel="Loading..." />
            </HStack>
        </Center>
    );
}