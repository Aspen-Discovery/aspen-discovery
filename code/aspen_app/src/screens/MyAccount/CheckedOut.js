import React, {Component, useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {RefreshControl, ScrollView} from "react-native";
import {
	Actionsheet,
	Avatar,
	Badge,
	Box,
	Button,
	Center,
	Divider,
	FlatList,
	Icon,
	Pressable,
	Text,
	useDisclose,
	HStack,
	VStack,
	IconButton
} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {getCheckedOutItems, getProfile} from '../../util/loadPatron';
import {
	isLoggedIn,
	renewAllCheckouts,
	renewCheckout,
	returnCheckout,
	viewOnlineItem,
	viewOverDriveItem
} from '../../util/accountActions';
import {userContext} from "../../context/user";

export default class CheckedOut extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
			renewingAll: false,
			user: [],
			checkouts: []
		};
		//this._fetchCheckouts();
		this.loadCheckouts();
	}

	loadCheckouts = async () => {
		const tmp = await AsyncStorage.getItem('@patronCheckouts');
		const items = JSON.parse(tmp);
		this.setState({
			checkouts: items,
			isLoading: false,
		})
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this._fetchCheckouts();
		await this.loadCheckouts();

		this.interval = setInterval(() => {
			this.loadCheckouts();
		}, 1000)

		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// grabs the items checked out to the account
	_fetchCheckouts = async () => {
		this.setState({
			isLoading: true,
		});

		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getCheckedOutItems(libraryUrl).then(r => this.loadCheckouts());
	}

	// renders the items on the screen
	renderNativeItem = (item, library) => {
		return (
			<CheckedOutItem
				data={item}
				navigation={this.props.navigation}
				openWebsite={this.openWebsite}
				openGroupedWork={this.openGroupedWork}
				libraryUrl={library.baseUrl}
			/>
		);
	};

	openGroupedWork = (item, libraryUrl) => {
		this.props.navigation.navigate("GroupedWork", {item: item, libraryUrl: libraryUrl});
	};

	openWebsite = async (url) => {

		this.setState({
			isLoading: true,
		});


		await isLoggedIn().then(response => {
			if (response === "TIMEOUT_ERROR") {
				this.setState({
					hasError: true,
					error: translate('error.timeout'),
					isLoading: false,
				});
			} else {
				this.setState({
					isLoading: false,
				});

				viewOverDriveItem(data.userId, formatId, data.overDriveId)
					.then(res => {

					})

				WebBrowser.openBrowserAsync(url)
					.then(res => {
						console.log(res);
					})
					.catch(async err => {
						if (err.message === "Another WebBrowser is already being presented.") {

							WebBrowser.dismissBrowser();
							WebBrowser.openBrowserAsync(url)
								.then(response => {
									console.log(response);
								})
								.catch(async error => {
									console.log("Unable to close previous browser session.");
								});
						} else {
							console.log("Unable to open browser window.");
						}
					});
			}
		})

	}

	_listEmptyComponent = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					{translate('checkouts.no_checkouts')}
				</Text>
			</Center>
		);
	};

	_onRefresh = () => {
		this.setState({isRefreshing: true}, () => {
			getCheckedOutItems().then(() => {
				this.setState({isRefreshing: false});
			});
		});
	}

	static contextType = userContext;

	render() {

		const {checkouts} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error, this._fetchCheckouts));
		}

		return (
			<ScrollView style={{ marginBottom: 80 }}>
			<Box>
				{user.numCheckedOut && user.numCheckedOut > 0 ?
					<Center pt={3} pb={3}>
						<Button
							isLoading={this.state.renewingAll}
							isLoadingText="Renewing all..."
							size="sm"
							colorScheme="primary"
							onPress={() => {
								this.setState({renewingAll: true})
								renewAllCheckouts(library.baseUrl).then(r => {
									this.setState({renewingAll: false})
								})
							}}
							startIcon={<Icon as={MaterialIcons} name="autorenew" size={5}/>}
						>
							{translate('checkouts.renew_all')}
						</Button>
					</Center>
					: null}
				<FlatList
					data={checkouts}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({item}) => this.renderNativeItem(item, library)}
					keyExtractor={(item) => item.recordId}
				/>
				<Center pt={5} pb={5}>
					<IconButton _icon={{ as: MaterialIcons, name: "refresh", color: "coolGray.500" }} onPress={() => {this._fetchCheckouts()}}
					/>
				</Center>
			</Box>
			</ScrollView>
		);

	}
}

function CheckedOutItem(props) {

	const [access, setAccess] = useState(false);
	const [returning, setReturn] = useState(false);
	const [renewing, setRenew] = useState(false);
	const {openWebsite, data, renewItem, openGroupedWork, libraryUrl} = props;
	const {isOpen, onOpen, onClose} = useDisclose();
	const dueDate = moment.unix(data.dueDate);
	var itemDueOn = moment(dueDate).format("MMM D, YYYY");

	var label = translate('checkouts.access_online', {source: data.checkoutSource});

	if (data.checkoutSource === "OverDrive") {

		if (data.overdriveRead === 1) {
			var formatId = "ebook-overdrive";
			var label = translate('checkouts.read_online', {source: data.checkoutSource});

		} else if (data.overdriveListen === 1) {
			var formatId = "audiobook-overdrive";
			var label = translate('checkouts.listen_online', {source: data.checkoutSource});

		} else if (data.overdriveVideo === 1) {
			var formatId = "video-streaming";
			var label = translate('checkouts.watch_online', {source: data.checkoutSource});

		} else if (data.overdriveMagazine === 1) {
			var formatId = "magazine-overdrive";
			var label = translate('checkouts.read_online', {source: data.checkoutSource});

		} else {
			var formatId = 'ebook-overdrive';
			var label = translate('checkouts.access_online', {source: data.checkoutSource});
		}

	}


	// check that title ends in / first
	if (data.title) {
		var title = data.title;
		var countSlash = title.split('/').length - 1;
		if (countSlash > 0) {
			var title = title.substring(0, title.lastIndexOf('/'));
		}
	}

	if (data.author) {
		var author = data.author;
		var countComma = author.split(',').length - 1;
		if (countComma > 1) {
			var author = author.substring(0, author.lastIndexOf(','));
		}
	}



	return (
		<>
			<Pressable onPress={onOpen} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3}>
					<Avatar source={{uri: data.coverUrl}} borderRadius="md" size={{base: "80px", lg: "120px"}} alt={data.title}/>
					<VStack maxW="75%">
						<Text bold mb={1} fontSize={{base: "sm", lg: "lg"}}>{title}</Text>
							{data.overdue ? <Text><Badge colorScheme="danger" rounded="4px"
							                       mt={-.5}>{translate('checkouts.overdue')}</Badge></Text> : null}

						{data.author ?
							<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translate('grouped_work.author')}:</Text> {author}
							</Text>
							: null}
						{data.format !== "Unknown" ?
							<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translate('grouped_work.format')}:</Text> {data.format}
							</Text>
							: null}
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>Checked Out To:</Text> {data.user}
						</Text>
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>{translate('checkouts.due')}:</Text> {itemDueOn}
						</Text>
						{data.autoRenew === 1 ?
							<Box mt={1} p={.5} bgColor="muted.100">
								<Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translate('checkouts.auto_renew')}:</Text> {data.renewalDate}</Text>
							</Box>
							: null}
					</VStack>
				</HStack>
			</Pressable>
			<Actionsheet isOpen={isOpen} onClose={onClose} size="full">
				<Actionsheet.Content>
					<Box w="100%" h={60} px={4} justifyContent="center">
						<Text
							fontSize={18}
							color="gray.500"
							_dark={{
								color: "gray.300",
							}}
						>
							{title}
						</Text>
					</Box>
					<Divider/>
					{data.groupedWorkId != null ?
						<Actionsheet.Item
							startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								openGroupedWork(data.groupedWorkId, libraryUrl);
								onClose(onClose);
							}}>
							{translate('grouped_work.view_item_details')}
						</Actionsheet.Item>
						: null
					}

					{data.canRenew ?
						<Actionsheet.Item
							isLoading={renewing}
							isLoadingText="Renewing..."
							startIcon={<Icon as={MaterialIcons} name="autorenew" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setRenew(true);
								renewCheckout(data.barcode, data.recordId, data.source, data.itemId, libraryUrl).then(r => {
									setRenew(false);
									onClose(onClose)
								});
							}}>
							{translate('checkouts.renew')}
						</Actionsheet.Item>
						: null
					}

					{data.source === "overdrive" ?
						<Actionsheet.Item
							isLoading={access}
							isLoadingText="Accessing..."
							startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setAccess(true);
								viewOverDriveItem(data.userId, formatId, data.overDriveId, libraryUrl).then(r => {
									setAccess(false);
									onClose(onClose)
								});
							}}>
							{label}
						</Actionsheet.Item>
						: null}

					{data.accessOnlineUrl != null ?
						<Actionsheet.Item
							isLoading={access}
							isLoadingText="Accessing..."
							startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setAccess(true);
								viewOnlineItem(data.userId, data.recordId, data.source, data.accessOnlineUrl, libraryUrl).then(r => {
									setAccess(false);
									onClose(onClose);
								});
							}}>
							{label}
						</Actionsheet.Item>
						: null}

					{data.accessOnlineUrl != null ?
						<Actionsheet.Item
							isLoading={returning}
							isLoadingText="Returning..."
							startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setReturn(true);
								returnCheckout(data.userId, data.recordId, data.source, data.overDriveId, libraryUrl).then(r => {
									setReturn(false);
									onClose(onClose);
									getProfile(libraryUrl);
								});
							}}>
							{translate('checkouts.return_now')}
						</Actionsheet.Item>
						: null}

					{data.canReturnEarly ?
						<Actionsheet.Item
							isLoading={returning}
							isLoadingText="Returning..."
							startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setReturn(true);
								returnCheckout(data.userId, data.recordId, data.source, data.overDriveId, libraryUrl).then(r => {
									setReturn(false);
									onClose(onClose);
									getProfile(libraryUrl);
								});
							}}>
							{translate('checkouts.return_now')}
						</Actionsheet.Item>
						: null}

				</Actionsheet.Content>
			</Actionsheet>
		</>
	)
}
