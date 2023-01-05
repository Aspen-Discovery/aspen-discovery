import React from "react";
import {Center, Heading, HStack, VStack, Spinner} from "native-base";

/*
TODO: Translate the accessibility labels
*/

export function loadingSpinner(message = "") {
	if (message !== "") {
		return (
			<Center flex={1} px="3">
				<VStack space={2} alignItems="center">
					<Spinner size="lg" accessibilityLabel="Loading..."/>
					<Heading fontSize="md">
						{message}
					</Heading>
				</VStack>
			</Center>
		);
	}

	return (
		<Center flex={1}>
			<HStack>
				<Spinner size="lg" accessibilityLabel="Loading..."/>
			</HStack>
		</Center>
	);
}