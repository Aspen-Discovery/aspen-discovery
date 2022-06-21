import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, Divider, HStack, Button, Text, Heading, FlatList} from "native-base";

// custom components and helper files
import AddLinkedAccount from "./AddLinkedAccount";
import {DisplayMessage} from "../../../../components/Notifications";
import _ from "lodash";
import {removeLinkedAccount} from "../../../../util/accountActions";
import {loadingSpinner} from "../../../../components/loadingSpinner";
import {getLinkedAccounts, getViewers} from "../../../../util/loadPatron";
import {translate} from "../../../../translations/translations";

export default class LinkedAccounts extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			linkedAccounts: [],
			viewers: [],
		};
		getLinkedAccounts();
		getViewers();
		this.loadLinkedAccounts();
		this.loadViewers()
	}

	loadLinkedAccounts = async () => {
		const tmp = await AsyncStorage.getItem('@linkedAccounts');
		let accounts = JSON.parse(tmp);
		accounts = _.values(accounts);
		this.setState({
			linkedAccounts: accounts,
			isLoading: false,
		})
	}

	loadViewers = async () => {
		const tmp = await AsyncStorage.getItem('@viewerAccounts');
		let accounts = JSON.parse(tmp);
		accounts = _.values(accounts);
		this.setState({
			viewers: accounts,
			isLoading: false,
		})
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: true,
		});

		await this.loadLinkedAccounts();
		await this.loadViewers();

		this.interval = setInterval(() => {
			this.loadLinkedAccounts()
			this.loadViewers();
		}, 1000)

		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	renderLinkedAccounts = (item) => {
		return (
			<HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
				<Text bold>{item.displayName} - {item.homeLocation}</Text>
				<Button colorScheme="warning" size="sm" onPress={() => (removeLinkedAccount(item.id))}>{translate('linked_accounts.remove')}</Button>
			</HStack>
		);
	};

	renderViewers = (item) => {
		return (
			<HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
				<Text bold>{item.displayName} - {item.homeLocation}</Text>
			</HStack>
		);
	};

	renderNoLinkedAccounts = () => {
		return (
			<Box pt={3} pb={5}>
				<Text bold>{translate('linked_accounts.none')}</Text>
			</Box>
		);
	};

	render() {

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<Box flex={1} safeArea={5}>
				<DisplayMessage type="info" message="Linked accounts allow you to easily maintain multiple accounts for the library so you can see all of your information in one place. Information from linked accounts will appear when you view your checkouts, holds, etc. in the main account." />
				<Heading fontSize="lg" pb={2}>Additional accounts to manage</Heading>
				<Text>The following accounts can be managed from this account:</Text>
				<FlatList
					data={this.state.linkedAccounts}
					renderItem={({item}) => this.renderLinkedAccounts(item)}
					ListEmptyComponent={() => this.renderNoLinkedAccounts()}
					keyExtractor={(item) => item.id}
				/>
				<AddLinkedAccount />
				<Divider my={4} />
				<Heading fontSize="lg" pb={2}>Other accounts that can view this account</Heading>
				<Text>The following accounts can view checkout and hold information from this account. If someone is viewing your account that you do not want to have access, please contact library staff.</Text>
				<FlatList
					data={this.state.viewers}
					renderItem={({item}) => this.renderViewers(item)}
					ListEmptyComponent={() => this.renderNoLinkedAccounts()}
					keyExtractor={(item) => item.id}
				/>
			</Box>
		)
	}
}