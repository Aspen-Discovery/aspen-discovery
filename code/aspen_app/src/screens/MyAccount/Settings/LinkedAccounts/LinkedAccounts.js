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
import {ScrollView} from "react-native";
import {userContext} from "../../../../context/user";

export default class LinkedAccounts extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			linkedAccounts: [],
			viewers: [],
		};
		this._fetchLinkedAccounts();
		this._fetchViewers();
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

	_fetchLinkedAccounts = async () => {
		const { navigation, route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getLinkedAccounts(libraryUrl);
	}

	_fetchViewers = async () => {
		const { navigation, route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getViewers(libraryUrl);
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: true,
		});

		await this._fetchLinkedAccounts();
		await this._fetchViewers();
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

	renderLinkedAccounts = (item, libraryUrl) => {
		return (
			<HStack space={3} justifyContent="space-between" pt={2} pb={2} alignItems="center">
				<Text bold>{item.displayName} - {item.homeLocation}</Text>
				<Button colorScheme="warning" size="sm" onPress={async () => {await removeLinkedAccount(item.id, libraryUrl);}}>{translate('general.remove')}</Button>
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
				<Text bold>{translate('general.none')}</Text>
			</Box>
		);
	};

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<ScrollView>
			<Box flex={1} safeArea={5}>
				<DisplayMessage type="info" message={translate('linked_accounts.info_message')} />
				<Heading fontSize="lg" pb={2}>{translate('linked_accounts.additional_accounts')}</Heading>
				<Text>{translate('linked_accounts.following_accounts_can_manage')}</Text>
				<FlatList
					data={this.state.linkedAccounts}
					renderItem={({item}) => this.renderLinkedAccounts(item, library.baseUrl)}
					ListEmptyComponent={() => this.renderNoLinkedAccounts()}
					keyExtractor={(item) => item.id}
				/>
				<AddLinkedAccount libraryUrl={library.baseUrl} />
				<Divider my={4} />
				<Heading fontSize="lg" pb={2}>{translate('linked_accounts.other_accounts')}</Heading>
				<Text>{translate('linked_accounts.following_accounts_can_view')}</Text>
				<FlatList
					data={this.state.viewers}
					renderItem={({item}) => this.renderViewers(item)}
					ListEmptyComponent={() => this.renderNoLinkedAccounts()}
					keyExtractor={(item) => item.id}
				/>
			</Box>
			</ScrollView>
		)
	}
}