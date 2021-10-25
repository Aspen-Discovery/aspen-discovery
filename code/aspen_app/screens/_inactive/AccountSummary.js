import React, { Component, useState } from "react";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Pressable, IconButton, Badge, VStack } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import moment from "moment";
import base64 from 'react-native-base64';
import { create, CancelToken } from 'apisauce';

export default class AccountSummary extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = async () => {
        this.setState({
            barcode: JSON.parse(await SecureStore.getItemAsync("barcode")),
            fines: JSON.parse(await SecureStore.getItemAsync("fines")),
            numOverdue: JSON.parse(await SecureStore.getItemAsync("numOverdue")),
            numCheckedOutIls: JSON.parse(await SecureStore.getItemAsync("numCheckedOutIls")),
            numCheckedOutOverDrive: JSON.parse(await SecureStore.getItemAsync("numCheckedOutOverDrive")),
            numHoldsIls: JSON.parse(await SecureStore.getItemAsync("numHoldsIls")),
            numHoldsOverDrive: JSON.parse(await SecureStore.getItemAsync("numHoldsOverDrive")),
            numHoldsAvailableIls: JSON.parse(await SecureStore.getItemAsync("numHoldsAvailableIls")),
            isLoading: false,
        });

        await this._fetchProfile();

	};

    _fetchProfile = async () => {

        this.setState({
            isLoading: true,
        });

        await getPatronProfile().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                this.setState({
                    data: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
    }

	render() {

        if (this.state.isLoading) {
            return (
                <Center flex={1}>
                    <HStack>
                        <Spinner accessibilityLabel="Loading..." />
                    </HStack>
                </Center>
            );
        } else if (this.state.hasError) {
            return(
               <Center flex={1}>
                <HStack>
                     <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                     <Heading color="error.500" mb={2}>Error</Heading>
                </HStack>
                <Text bold w="75%" textAlign="center">There was an error loading results from the library. Please try again.</Text>
                 <Button
                     mt={5}
                     colorScheme="primary"
                     onPress={() => this._fetchProfile()}
                     startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                 >
                     Reload
                 </Button>
                 <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {this.state.error}</Text>
                </Center>
            )
        }

		return (
			<Box safeArea={5} style={{ backgroundColor: "white" }}>
			<Center>
					<Text fontSize="xl">{global.patron}'s Account Summary</Text>
						<Text bold fontSize="sm" mr={0.5}>
							{this.state.barcode}
						</Text>
						</Center>
					<Divider mt={2} mb={2} />
					<HStack space={1}>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Checked Out:</Text> {calcCheckouts(this.state.numCheckedOutIls, this.state.numCheckedOutOverDrive)}
						</Text>
						<Badge colorScheme="danger" rounded="4px">1 Overdue</Badge>
						</Center>
                    </VStack>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Holds:</Text> {calcHolds(this.state.numHoldsIls, this.state.numHoldsOverDrive)}
						</Text>
						<Badge colorScheme="success" rounded="4px">1 Ready for Pickup</Badge>
						</Center>
                    </VStack>
                    </HStack>
			</Box>
		);
	}
}

async function getPatronProfile() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronProfile', { username: base64.decode(global.userKey), password: base64.decode(global.secretKey) });

    if(response.ok) {
        const result = response.data;
        const patronProfile = result.result;
        const fetchedData = patronProfile.profile;

        return fetchedData;
    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

function calcCheckouts(ilsCheckouts, overdriveCheckouts) {

    var totalCheckouts = 0;

    totalCheckouts += parseInt(ilsCheckouts);
    totalCheckouts += parseInt(overdriveCheckouts);

    return totalCheckouts;
}

function calcHolds(ilsHolds, overdriveHolds) {

    var totalHolds = 0;

    totalHolds += parseInt(ilsHolds);
    totalHolds += parseInt(overdriveHolds);

    return totalHolds;
}