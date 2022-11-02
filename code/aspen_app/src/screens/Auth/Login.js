import React, {Component} from 'react';
import {Platform} from 'react-native';
import {Badge, Box, Button, Center, HStack, Icon, Image, Input, KeyboardAvoidingView, Pressable, Text, VStack} from 'native-base';
import {create} from 'apisauce';
import {Ionicons} from '@expo/vector-icons';
import _ from 'lodash';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';

// custom components and helper files
import {translate} from '../../translations/translations';
import {getHeaders} from '../../util/apiAuth';
import {GLOBALS, LOGIN_DATA} from '../../util/globals';
import {GetLoginForm} from './LoginForm';
import {SelectYourLibrary} from './SelectYourLibrary';
import {makeGreenhouseRequestAll, makeGreenhouseRequestNearby} from '../../util/login';

export default class Login extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			libraryData: [],
			query: '',
			fetchError: null,
			isFetching: true,
			fetchAll: true,
			listen: null,
			error: false,
			isBeta: false,
			fullData: [],
			locationNum: 0,
			showModal: false,
			hasPendingChanges: false,
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		if (_.isEmpty(LOGIN_DATA.nearbyLocations)) {
			this._isMounted && await makeGreenhouseRequestNearby();
			LOGIN_DATA.hasPendingChanges = false;
			if (!LOGIN_DATA.showSelectLibrary) {
				await this.setLibraryBranch(LOGIN_DATA.nearbyLocations[0]);
			}
		}

		this._isMounted && this.setState({
			isLoading: false,
			isFetching: false,
		});

		if (this._isMounted && Constants.manifest.slug === 'aspen-lida') {
			if (GLOBALS.runGreenhouse) {
				await makeGreenhouseRequestAll();
				LOGIN_DATA.runGreenhouse = false;
				LOGIN_DATA.hasPendingChanges = false;
			}
		}
	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	componentDidUpdate(prevProps, prevState) {
		if (prevState.messages !== LOGIN_DATA.hasPendingChanges) {
			this.setState({
				messages: LOGIN_DATA.hasPendingChanges,
			});
			LOGIN_DATA.hasPendingChanges = !LOGIN_DATA.hasPendingChanges;
		}
	}

	// handles the opening or closing of the showLibraries() modal
	handleModal = (newState) => {
		this.setState({
			showModal: newState,
		});
	};

	makeFullGreenhouseRequest = async () => {
		if (LOGIN_DATA.runGreenhouse) {
			this.setState({
				isFetching: true,
			});
			const api = create({
				baseURL: Constants.manifest.extra.greenhouse + '/API',
				timeout: GLOBALS.timeoutSlow,
				headers: getHeaders(),
			});
			const response = await api.get('/GreenhouseAPI?method=getLibraries', {
				release_channel: Updates.releaseChannel,
			});
			if (response.ok) {
				const data = response.data;
				let libraries = _.uniqBy(data.libraries, v => [v.locationId, v.libraryId].join());
				LOGIN_DATA.allLocations = _.uniqBy(libraries, v => [v.librarySystem, v.name].join());
				LOGIN_DATA.runGreenhouse = false;
				this.setState({
					isFetching: false,
				});
				return true;
			} else {
				this.setState({
					error: true,
				});
				console.log(response);
			}
			console.log('Full greenhouse request completed.');
			return false;
		}
	};

	renderListItem = (item, setShowModal, showModal) => {
		let isCommunity = true;
		if (Constants.manifest.slug !== 'aspen-lida') {
			isCommunity = false;
		}
		return (
				<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" onPress={() => this.setNewLibraryBranch(item, showModal)} pl="4" pr="5" py="2">
					<HStack space={3} alignItems="center">
						<Image borderRadius={100} source={{uri: item.favicon}} fallbackSource={require('../../themes/default/aspenLogo.png')} size={6} alt={item.name}/>
						<VStack>
							<Text bold fontSize={{base: 'sm', lg: 'md'}}>{item.name}</Text>
							{isCommunity ? <Text fontSize={{base: 'xs', lg: 'sm'}}>{item.librarySystem}</Text> : null}
						</VStack>
					</HStack>
				</Pressable>
		);
	};

	// FlatList: Renders the search box for filtering
	renderListHeader = () => {
		return (
				<Box bg="white" _dark={{bg: 'coolGray.800'}}>
					<Input
							variant="filled"
							size="lg"
							autoCorrect={false}
							onChangeText={(text) => this.setState({query: text})}
							status="info"
							placeholder={translate('search.title')}
							clearButtonMode="always"
							value={this.state.query}
					/>
				</Box>
		);
	};

	filterLibraries(payload) {
		const query = this.state.query;
		if (!_.isEmpty(query) && !_.isEmpty(LOGIN_DATA.allLocations)) {
			payload = LOGIN_DATA.allLocations;
		}
		if (Constants.manifest.slug !== 'aspen-lida') {
			return _.filter(payload, function(branch) {
				return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
			});
		}
		return _.filter(payload, function(branch) {
			return (branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1 || branch.librarySystem.toLowerCase().indexOf(query.toLowerCase()) > -1);
		});
	}

	// showLibraries: handles storing the states based on selected library to use later on in validation
	setLibraryBranch = async (item) => {
		if (_.isObject(item) && !this.state.libraryName) {
			this.setState({
				libraryName: item.name,
				libraryUrl: item.baseUrl,
				solrScope: item.solrScope,
				libraryId: item.libraryId,
				locationId: item.locationId,
				favicon: item.favicon,
				logo: item.logo,
				patronsLibrary: item,
			});
		}
	};

	setNewLibraryBranch = async (item) => {
		if (_.isObject(item)) {
			this.setState({
				libraryName: item.name,
				libraryUrl: item.baseUrl,
				solrScope: item.solrScope,
				libraryId: item.libraryId,
				locationId: item.locationId,
				favicon: item.favicon,
				logo: item.logo,
				patronsLibrary: item,
			});
		}

		this.handleModal(false);
	};

	/**
    // end of showLibraries() setup
	 **/
	// render the Login screen
	render() {
		const isBeta = this.state.isBeta;
		const logo = Constants.manifest.extra.loginLogo;

		let isCommunity = true;
		if (Constants.manifest.slug !== 'aspen-lida') {
			isCommunity = false;
		}

		return (
				<Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
					<Image source={{uri: logo}} rounded={25} size="xl"
								 alt={translate('app.name')} fallbackSource={require('../../themes/default/aspenLogo.png')}/>
					{LOGIN_DATA.showSelectLibrary || isCommunity ? (
							<SelectYourLibrary
									libraryName={this.state.libraryName}
									uniqueLibraries={this.filterLibraries(LOGIN_DATA.nearbyLocations)}
									renderListItem={this.renderListItem}
									renderListHeader={this.renderListHeader}
									extraData={this.state.query}
									isRefreshing={this.state.isFetching}
									showModal={this.state.showModal}
									handleModal={this.handleModal}
							/>
					) : null}
					<KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'padding'} width="100%">
						{this.state.libraryName ?
								<GetLoginForm
										libraryName={this.state.libraryName}
										locationId={this.state.locationId}
										libraryId={this.state.libraryId}
										libraryUrl={this.state.libraryUrl}
										solrScope={this.state.solrScope}
										favicon={this.state.favicon}
										logo={this.state.logo}
										sessionId={this.state.sessionId}
										navigation={this.props.navigation}
										patronsLibrary={this.state.patronsLibrary}
								/>
								: null}

						{isCommunity ?
								<Button
										onPress={() => makeGreenhouseRequestNearby()}
										mt={8}
										size="xs"
										variant="ghost"
										colorScheme="secondary"
										startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5}/>}
								>
									{translate('login.reset_geolocation')}
								</Button>
								: null}
						<Center>{isBeta ? <Badge rounded={5}
																		 mt={5}>{translate('app.beta')}</Badge> : null}</Center>
						<Center><Text mt={5} fontSize="xs" color="coolGray.600">{GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}]</Text></Center>
					</KeyboardAvoidingView>
				</Box>
		);
	}
}