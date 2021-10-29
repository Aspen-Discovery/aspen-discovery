import React, { Component, useState } from "react";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Pressable, IconButton, Badge, VStack } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import { create, CancelToken } from 'apisauce';

import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";

import { getProfile } from '../../util/loadPatron';

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
            numOverdue: global.numOverdue,
            isLoading: false,
            thisPatron: global.patron + "'s",
        });

        await this._fetchProfile();

	};

    _fetchProfile = async () => {
        this.setState({
            isLoading: true,
        });

        await getProfile().then(response => {
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

		return (
			<Box safeArea={5} style={{ backgroundColor: "white" }}>
			<Center>
					<Text fontSize="xl">{this.state.thisPatron} Account Summary</Text>
						{this.state.barcode ?
						<HStack space={1} alignItems="center"><Icon as={Ionicons} name="card" size="xs" />
						<Text bold fontSize="sm" mr={0.5}>
							{this.state.barcode}
						</Text>
						</HStack>
						: null}
						</Center>
					<Divider mt={2} mb={2} />
					<HStack space={1}>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Checked Out: </Text>{calcCheckouts(global.numCheckedOutIls, global.numCheckedOutOverDrive)}
						</Text>
						{this.state.numOverdue ? <Badge colorScheme="danger" rounded="4px"><Text fontSize="xs" bold>{this.state.numOverdue} Overdue</Text></Badge> : null}
						</Center>
                    </VStack>
					<VStack width="50%">
					<Center>
						<Text fontSize="md" mb={1}>
							<Text bold>Holds: </Text>{calcHolds(global.numHoldsIls, global.numHoldsOverDrive)}
						</Text>
						{this.state.numHoldsAvailableIls > 0 ? <Badge colorScheme="success" rounded="4px"><Text fontSize="xs" bold>{this.state.numHoldsAvailableIls} Ready for Pickup</Text></Badge> : null}
						</Center>
                    </VStack>
                    </HStack>
			</Box>
		);
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