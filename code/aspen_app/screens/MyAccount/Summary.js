import React, { Component, useState } from "react";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Pressable, IconButton, Badge, VStack } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import { create, CancelToken } from 'apisauce';

export default class Summary extends Component {
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
            barcode: global.barcode,
            numHoldsAvailableIls: global.numHoldsAvailableIls,
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
            return null
        }

		return (
			<Box safeArea={5} style={{ backgroundColor: "white" }}>
			<Center>
					<Text fontSize="xl">{global.patron}'s Account Summary</Text>
						{this.state.barcode &&
						<HStack space={1} alignItems="center"><Icon as={Ionicons} name="card" size="xs" />
						<Text bold fontSize="sm" mr={0.5}>
							{this.state.barcode}
						</Text>
						</HStack>
						}
						</Center>
					<Divider mt={2} mb={2} />
					<HStack space={1}>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Checked Out:</Text> {calcCheckouts(global.numCheckedOutIls, global.numCheckedOutOverDrive)}
						</Text>
						<Badge colorScheme="danger" rounded="4px">1 Overdue</Badge>
						</Center>
                    </VStack>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Holds:</Text> {calcHolds(global.numHoldsIls, global.numHoldsOverDrive)}
						</Text>
						<Badge colorScheme="success" rounded="4px"><Text fontSize="xs" bold>{this.state.numHoldsAvailableIls} Ready for Pickup</Text></Badge>
						</Center>
                    </VStack>
                    </HStack>
			</Box>
		);
	}
}

async function getPatronProfile() {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronProfile', { username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const result = response.data;
        const patronProfile = result.result;
        const fetchedData = patronProfile.profile;
        console.log(result);
        return fetchedData;
    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }

    console.log(response)
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