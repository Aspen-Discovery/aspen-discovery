import React, {Component, useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {RefreshControl} from "react-native";
import {
	Actionsheet,
	Avatar,
	Badge,
	Box,
	Button,
	Center,
	FlatList,
	FormControl,
	Icon,
	Modal,
	Pressable,
	Radio,
	Text,
	useDisclose,
	HStack,
	VStack
} from "native-base";
import {Ionicons, MaterialCommunityIcons, MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import _ from "lodash";
import {GLOBALS} from "../../util/globals";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {getHolds} from '../../util/loadPatron';
import {cancelHold, changeHoldPickUpLocation, freezeHold, thawHold} from '../../util/accountActions';
import {getPickupLocations} from '../../util/loadLibrary';
import {getTranslation, getTranslations} from "../../translations/TranslationService";

export default class Holds extends Component {

	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
			locations: null,
			forceReload: true,
			holds: [],
			user: {
				interfaceLanguage: "en",
			},
			holdsNotReady: [],
			holdsReady: [],
			translation: {
				author: "Author",
				format: "Format",
				onHoldFor: "On Hold For",
				pickUpLocation: "Pickup Location",
				pickupBy: "Pickup By",
				position: "Position",
				viewItemDetails: "View Item Details",
				cancelHold: "Cancel Hold",
				freezeHold: "Freeze Hold",
				thawHold: "Thaw Hold",
				changePickUpLocation: "Change Pickup Location"
			}
		};
		getPickupLocations();
		getHolds();
		this.loadUser();
		this.loadPickupLocations();
		this.loadLibrary();
		this.loadHolds();
		this.loadTranslations();
	}

	loadUser = async () => {
		const tmp = await AsyncStorage.getItem('@patronProfile');
		const profile = JSON.parse(tmp);
		this.setState({
			user: profile,
			isLoading: false,
		})
	}

	loadHolds = async () => {
		const tmpHolds = await AsyncStorage.getItem('@patronHolds');
		const tmpHoldsNotReady = await AsyncStorage.getItem('@patronHoldsNotReady');
		const tmpHoldsReady = await AsyncStorage.getItem('@patronHoldsReady');
		const holds = JSON.parse(tmpHolds);
		const holdsNotReady = JSON.parse(tmpHoldsNotReady);
		const holdsReady = JSON.parse(tmpHoldsReady);
		this.setState({
			holds: holds,
			holdsNotReady: holdsNotReady,
			holdsReady: holdsReady,
			isLoading: false,
		})
	}

	loadLibrary = async () => {
		const tmp = await AsyncStorage.getItem('@patronLibrary');
		const profile = JSON.parse(tmp);
		this.setState({
			library: profile,
			isLoading: false,
		})
	}

	loadPickupLocations = async () => {
		const tmp = await AsyncStorage.getItem('@pickupLocations');
		const locations = JSON.parse(tmp);
		this.setState({
			locations: locations,
			isLoading: false,
		})
	}

	loadTranslations = async () => {
		const tmp = await AsyncStorage.getItem('@libraryLanguages');
		let languages = JSON.parse(tmp);
		languages = _.values(languages);
		languages.map(async (language) => {
			const tmp = await AsyncStorage.getItem(language.code);
		})
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		})

		await this.loadUser();
		await this.loadHolds();
		await this.loadLibrary();
		await this.loadPickupLocations();
		await this.loadTranslations();

		this.interval = setInterval(() => {
			this.loadHolds();
			this.loadUser();
			this.loadTranslations();
		}, 1000)

		return () => clearInterval(this.interval)
	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// Handles opening the GroupedWork screen with the item data
	openGroupedWork = (item) => {
		this.props.navigation.navigate("GroupedWork", {item});
	};

	// Renders the hold items on the screen
	renderHoldItem = (item) => {
		return (
			<HoldItem
				data={item}
				onPressItem={this.onPressItem}
				navigation={this.props.navigation}
				locations={this.state.locations}
				openGroupedWork={this.openGroupedWork}
				translations={this.state.translation}
			/>
		);
	}

	// Triggers a screen refresh
	_onRefresh() {
		this.setState({isRefreshing: true}, () => {
			this.loadHolds().then(() => {
				this.setState({isRefreshing: false});
			});
		});
	}

	_listEmptyComponent = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					{translate('holds.no_holds')}
				</Text>
			</Center>
		);
	};

	render() {
		const {holds} = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner(this.state.loadingMessage));
		}

		return (
			<Box h="100%">
				<FlatList
					data={holds}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({item}) => this.renderHoldItem(item)}
					keyExtractor={(item) => item.id}
					refreshControl={
						<RefreshControl
							refreshing={this.state.isRefreshing}
							onRefresh={this._onRefresh.bind(this)}
						/>
					}
				/>
			</Box>
		);
	}
}

function HoldItem(props) {
	let expirationDate;
	let availableDate;
	const {onPressItem, data, navigation, locations, forceScreenReload, openGroupedWork, translations} = props;
	const {isOpen, onOpen, onClose} = useDisclose();

	const [loading, setLoading] = useState(false);
	const [cancel, setCancel] = useState(false);
	const [freeze, setFreeze] = useState(false);
	const [thaw, setThaw] = useState(false);
	const [loadingText, setLoadingText] = useState("Loading...");

	// format some dates
	if (data.availableDate != null) {
		const availableDateUnix = moment.unix(data.availableDate);
		availableDate = moment(availableDateUnix).format("MMM D, YYYY");
	} else {
		availableDate = "";
	}

	if (data.expirationDate) {
		const expirationDateUnix = moment.unix(data.expirationDate);
		expirationDate = moment(expirationDateUnix).format("MMM D, YYYY");
	} else {
		expirationDate = "";
	}

	// check freeze status to see which option to display
	if (data.canFreeze === true) {
		if (data.frozen === true) {
			var label = translations.thawHold;
			var method = "thawHold";
			var icon = "play";
		} else {
			var label = translations.freezeHold;
			var method = "freezeHold";
			var icon = "pause";
			if (data.available) {
				var label = translate('overdrive.delay_checkout');
				var method = "freezeHold";
				var icon = "pause";
			}
		}
	}

	if (data.status === "Pending") {
		var statusColor = "green";
	}

	if (data.title) {
		var title = data.title;
		var title = title.substring(0, title.lastIndexOf('/'));
		if (title == '') {
			var title = data.title;
		}
	}

	if (data.author) {
		var author = data.author;
		var countComma = author.split(',').length - 1;
		if (countComma > 1) {
			var author = author.substring(0, author.lastIndexOf(','));
		}
	}

	var source = data.source;
	var holdSource = data.holdSource;
	if (source === 'ils') {
		var readyMessage = data.status;
	} else {
		var readyMessage = translate('overdrive.hold_ready');
	}

	var isAvailable = data.available;
	var updateLocation = data.locationUpdateable;
	if (data.available && data.locationUpdateable) {
		var updateLocation = false;
	}

	var checkoutOnline = false;
	if (data.available && source !== 'ils') {
		var checkoutOnline = true;
	}

	var cancelable = false;
	if (!data.available && source !== 'ils') {
		var cancelable = true;
	} else if (!data.available && source === 'ils') {
		var cancelable = true;
	}

	return (
		<>
			<Pressable onPress={onOpen} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3}>

					<Avatar source={{uri: data.coverUrl}} borderRadius="md" size={{base: "80px", lg: "120px"}} alt={data.title}/>
					<VStack maxW="75%">
						<Text bold mb={1} fontSize={{base: "sm", lg: "lg"}}>{title}</Text>
							{data.frozen ?
								<Text><Badge colorScheme="yellow" rounded="4px" mt={-.5}>{data.status}</Badge></Text> : null}
							{data.available ?
								<Text><Badge colorScheme="green" rounded="4px" mt={-.5}>{readyMessage}</Badge></Text>
								: null}

						{author ?
							<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translations.author}:</Text> {author}
							</Text>
							: null}
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>{translations.format}:</Text> {data.format}
						</Text>
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>{translations.onHoldFor}:</Text> {data.user}
						</Text>
						{data.source === "ils" ? (<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translations.pickUpLocation}:</Text> {data.currentPickupName}</Text>) : null}
						{data.available ? <Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translations.pickupBy}:</Text> {expirationDate}</Text> :
							<Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translations.position}:</Text> {data.position}</Text>}
					</VStack>
				</HStack>
			</Pressable>
			<Actionsheet isOpen={isOpen} onClose={onClose} size="full">
				<Actionsheet.Content>
					<Box w="100%" h={60} px={4} justifyContent="center">
						<Text
							fontSize={16}
							color="gray.500"
							_dark={{
								color: "gray.300",
							}}
						>
							{title}
						</Text>
					</Box>
					{data.groupedWorkId != null ?
						<Actionsheet.Item
							startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								openGroupedWork(data.groupedWorkId);
								onClose(onClose);
							}}>
							{translations.viewItemDetails}
						</Actionsheet.Item>
						: ""
					}
					{cancelable ?
						<Actionsheet.Item
							isLoading={loading}
							isLoadingText="Cancelling..."
							startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setLoading(true);
								cancelHold(data.cancelId, data.recordId, data.source).then(r => {
									onClose(onClose);
									setLoading(false);
								});
							}}
						>
							{translations.cancelHold}
						</Actionsheet.Item>
						: ""}
					{data.allowFreezeHolds ?
						<Actionsheet.Item
							isLoading={data.frozen === true ? thaw : freeze}
							isLoadingText={loadingText}
							startIcon={<Icon as={MaterialCommunityIcons} name={icon} color="trueGray.400" mr="1"
							                 size="6"/>}
							onPress={() => {
								if (data.frozen === true) {
									setThaw(true);
									setLoadingText("Thawing...");
									thawHold(data.cancelId, data.recordId, data.source).then(r => {
										onClose(onClose);
										setThaw(false);
									});
								} else {
									setFreeze(true);
									setLoadingText("Freezing...");
									freezeHold(data.cancelId, data.recordId, data.source).then(r => {
										onClose(onClose);
										setFreeze(false);
									});
								}
							}}
						>
							{label}
						</Actionsheet.Item>
						: ""}

					{updateLocation ?
						<SelectPickupLocation locations={locations} onClose={onClose}
						                      currentPickupId={data.pickupLocationId} holdId={data.cancelId} label={translations.changePickUpLocation}/>
						: ""}

				</Actionsheet.Content>
			</Actionsheet>
		</>
	)
}

const SelectPickupLocation = (props) => {

	const {locations, label, onClose, currentPickupId, holdId} = props;

	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);
	let [value, setValue] = React.useState(currentPickupId);

	return (
		<>
			<Actionsheet.Item startIcon={<Icon as={Ionicons} name="location" color="trueGray.400" mr="1" size="6"/>}
			                  onPress={() => {
				                  setShowModal(true);
			                  }}>
				{label}
			</Actionsheet.Item>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
				<Modal.Content>
					<Modal.CloseButton/>
					<Modal.Header>{translate('pickup_locations.change_hold_location')}</Modal.Header>
					<Modal.Body>
						<FormControl>
							<FormControl.Label>{translate('pickup_locations.select_new_pickup')}</FormControl.Label>
							<Radio.Group
								name="pickupLocations"
								value={value}
								onChange={(nextValue) => {
									setValue(nextValue);
								}}
								mt="1"
							>
								{locations.map((item, index) => {
									const locationId = item.locationId;
									const code = item.code;
									const id = locationId.concat("_", code);
									return <Radio value={id} my={1}>{item.name}</Radio>;
								})}
							</Radio.Group>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group space={2} size="md">
							<Button colorScheme="muted" variant="outline"
							        onPress={() => setShowModal(false)}>{translate('general.close_window')}</Button>
							<Button
								isLoading={loading}
								isLoadingText="Updating..."
								onPress={() => {
									setLoading(true);
									changeHoldPickUpLocation(holdId, value).then(r => {
											setShowModal(false);
											onClose(onClose);
											setLoading(false);
										}
									);
								}}
							>
								{translate('pickup_locations.change_location')}
							</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</>
	)
}