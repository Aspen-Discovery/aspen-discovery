import React, {Component} from "react";
import {Badge, Box, Center, Divider, HStack, Icon, Text, VStack} from "native-base";
import {Ionicons} from "@expo/vector-icons";

// custom components and helper files
import {translate} from "../../util/translations";
import {getProfile} from '../../util/loadPatron';

export default class Summary extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			numCheckedOut: 0,
			numHolds: 0,
			numHoldsAvailable: 0,
			numOverdue: 0,
		};
	}

	componentDidMount = async () => {
		this.setState({
			barcode: global.barcode,
			numCheckedOut: global.numCheckedOut,
			numHolds: global.numHolds,
			numHoldsAvailable: global.numHoldsAvailable,
			numOverdue: global.numOverdue,
			isLoading: false,
			thisPatron: global.patron + "'s",
		});

		await this._fetchProfile();

	};

	_fetchProfile = async () => {

		await getProfile().then(response => {
			if (response === "TIMEOUT_ERROR") {
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
			<Box safeArea={5} style={{backgroundColor: "white"}}>
				<Center>
					<Text fontSize="xl">{this.state.thisPatron} {translate('user_profile.title')}</Text>
					{this.state.barcode ?
						<HStack space={1} alignItems="center"><Icon as={Ionicons} name="card" size="xs"/>
							<Text bold fontSize="sm" mr={0.5}>
								{this.state.barcode}
							</Text>
						</HStack>
						: null}
				</Center>
				<Divider mt={2} mb={2}/>
				<HStack space={1}>
					<VStack width="50%">
						<Center>
							<Text fontSize="md" mb={1}>
								<Text bold>{translate('checkouts.title')}: </Text>{this.state.numCheckedOut}
							</Text>
							{this.state.numOverdue > 0 ? <Badge colorScheme="danger" rounded="4px"><Text fontSize="xs"
							                                                                             bold>{translate('checkouts.overdue_summary', {count: this.state.numOverdue})}</Text></Badge> : null}
						</Center>
					</VStack>
					<VStack width="50%">
						<Center>
							<Text fontSize="md" mb={1}>
								<Text bold>{translate('holds.holds')}: </Text>{this.state.numHolds}
							</Text>
							{this.state.numHoldsAvailable > 0 ?
								<Badge colorScheme="success" rounded="4px"><Text fontSize="xs"
								                                                 bold>{translate('holds.ready_for_pickup', {count: this.state.numHoldsAvailable})}</Text></Badge> : null}
						</Center>
					</VStack>
				</HStack>
			</Box>
		);
	}
}