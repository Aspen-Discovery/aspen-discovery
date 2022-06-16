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
	FlatList,
	FormControl,
	Icon,
	Modal,
	Pressable,
	Radio,
	Text,
	useDisclose,
	HStack,
	VStack,
	IconButton,
	Select,
	CheckIcon
} from "native-base";
import {Ionicons, MaterialCommunityIcons, MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import _ from "lodash";
import {GLOBALS} from "../../util/globals";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {getCheckedOutItems, getHolds, getProfile, reloadProfile} from '../../util/loadPatron';
import {cancelHold, changeHoldPickUpLocation, freezeHold, thawHold} from '../../util/accountActions';
import {getPickupLocations} from '../../util/loadLibrary';
import {getTranslation, getTranslations} from "../../translations/TranslationService";
import {userContext} from "../../context/user";
import CalendarPicker from 'react-native-calendar-picker';
import {DisplayMessage} from "../../components/Notifications";

export default class Holds extends Component {

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
			locations: null,
			forceReload: true,
			holds: [],
			isUpdating: false,
			user: {
				interfaceLanguage: "en",
			},
			library: this.context.library,
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
			},
			selectedStartDate: null,
		};
		this.onDateChange = this.onDateChange.bind(this);
		//this._fetchHolds();
		this._pickupLocations();
		this.loadPickupLocations();
		this.loadHolds();
	}

	onDateChange(date) {
		this.setState({
			selectedStartDate: date,
		});
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

	_fetchHolds = async () => {
		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getHolds(libraryUrl).then(r => {
			console.log(r);
			this.loadHolds()
		});
	}

	loadPickupLocations = async () => {
		const tmp = await AsyncStorage.getItem('@pickupLocations');
		const locations = JSON.parse(tmp);
		this.setState({
			locations: locations,
			isLoading: false,
		})
	}

	_pickupLocations = async () => {
		this.setState({
			isLoading: true,
		});

		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getPickupLocations(libraryUrl).then(r => this.loadPickupLocations())
	}

	componentDidMount = async () => {
		let discoveryVersion = "22.04.00";
		if(this.context.library.discoveryVersion) {
			let version = this.context.library.discoveryVersion;
			version = version.split(" ");
			discoveryVersion = version[0];
		}

		this.setState({
			isLoading: true,
			discoveryVersion: discoveryVersion,
		})

		await this._fetchHolds();
		await this._pickupLocations();
		await this.loadHolds();
		await this.loadPickupLocations();

		this.interval = setInterval(() => {
			this.loadHolds();
		}, 1000)

		return () => clearInterval(this.interval)
	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// Handles opening the GroupedWork screen with the item data
	openGroupedWork = (item, libraryUrl) => {
		this.props.navigation.navigate("GroupedWork", {item: item, libraryUrl: libraryUrl});
	};

	// Renders the hold items on the screen
	renderHoldItem = (item, libraryUrl, user, updateProfile) => {
		return (
			<HoldItem
				data={item}
				onPressItem={this.onPressItem}
				navigation={this.props.navigation}
				locations={this.state.locations}
				openGroupedWork={this.openGroupedWork}
				translations={this.state.translation}
				libraryUrl={libraryUrl}
				userProfile={user}
				updateProfile = {updateProfile}
				discoveryVersion={this.state.discoveryVersion}
				onDateChange={this.onDateChange}
				selectedReactivationDate={this.state.selectedStartDate}
			/>
		);
	}

	// Trigger a context refresh
	updateProfile = async () => {
		console.log("Getting new profile data from holds...");
		await getProfile().then(response => {
			this.context.user = response;
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

	static contextType = userContext;

	render() {
		const {holds} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner(this.state.loadingMessage));
		}

		return (
			<ScrollView>
			<Box>
				<FlatList
					data={holds}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({item}) => this.renderHoldItem(item, library.baseUrl, user, this.updateProfile)}
					keyExtractor={(item) => item.id.concat("_", item.position)}
				/>
				<Center pt={5} pb={5}>
					<IconButton _icon={{ as: MaterialIcons, name: "refresh", color: "coolGray.500" }} onPress={() => {this._fetchHolds()}}
					/>
				</Center>
			</Box>
			</ScrollView>
		);
	}
}

function HoldItem(props) {
	let expirationDate;
	let availableDate;
	const {onPressItem, data, navigation, locations, forceScreenReload, openGroupedWork, translations, libraryUrl, updateProfile, discoveryVersion, userProfile, onDateChange, selectedReactivationDate} = props;
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

	let allowLinkedAccountAction = true;
	if(discoveryVersion < "22.05.00") {
		if(data.userId !== userProfile.id) {
			allowLinkedAccountAction = false;
		}
	}

	//console.log(allowLinkedAccountAction);

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
								openGroupedWork(data.groupedWorkId, libraryUrl);
								onClose(onClose);
							}}>
							{translations.viewItemDetails}
						</Actionsheet.Item>
						: ""
					}
					{cancelable && allowLinkedAccountAction ?
						<Actionsheet.Item
							isLoading={loading}
							isLoadingText="Cancelling..."
							startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setLoading(true);
								cancelHold(data.cancelId, data.recordId, data.source, libraryUrl, data.userId).then(r => {
									updateProfile();
									onClose(onClose);
									setLoading(false);
								});
							}}
						>
							{translations.cancelHold}
						</Actionsheet.Item>
						: ""}
					{data.allowFreezeHolds === "1" && allowLinkedAccountAction && data.frozen === false ?
						<SelectThawDate
							onDateChange={onDateChange}
							onClose={onClose}
							freezeId={data.cancelId}
							recordId={data.recordId}
							source={data.source}
							libraryUrl={libraryUrl}
							userId={data.userId}
							reactivationDate={selectedReactivationDate}
						/> : ""
 					}
					{data.allowFreezeHolds === "1" && allowLinkedAccountAction && data.frozen === true ?
						<Actionsheet.Item
							isLoading={thaw}
							isLoadingText={loadingText}
							startIcon={<Icon as={MaterialCommunityIcons} name={icon} color="trueGray.400" mr="1"
							                 size="6"/>}
							onPress={() => {
								setThaw(true);
								setLoadingText("Thawing...");
								thawHold(data.cancelId, data.recordId, data.source, libraryUrl, data.userId).then(r => {
									updateProfile();
									onClose(onClose);
									setThaw(false);
								});
							}}
						>
							{label}
						</Actionsheet.Item>
						: ""}

					{updateLocation && allowLinkedAccountAction ?
						<SelectPickupLocation locations={locations} onClose={onClose}
						                      userId = {data.userId}
						                      currentPickupId={data.pickupLocationId} holdId={data.cancelId} label={translations.changePickUpLocation} libraryUrl={libraryUrl}/>
						: ""}

				</Actionsheet.Content>
			</Actionsheet>
		</>
	)
}

const SelectThawDate = (props) => {
	const {onDateChange, onClose, freezeId, recordId, source, libraryUrl, userId, reactivationDate} = props;
	const minDate = new Date(); // Today
	const maxDate = new Date(2017, 6, 3);
	const [freeze, setFreeze] = useState(false);
	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);
	//YYYY-MM-DD

	let selectedReactivationDate = moment(reactivationDate).format('YYYY-MM-DD');
	if(selectedReactivationDate === "Invalid date") {
		selectedReactivationDate = null;
	}

	return (
	<>
		<Actionsheet.Item startIcon={<Icon as={MaterialIcons} name="pause" color="trueGray.400" mr="1" size="6"/>}
		                  onPress={() => {
			                  setShowModal(true);
		                  }}>
			Freeze Hold
		</Actionsheet.Item>
		<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="full">
			<Modal.Content>
				<Modal.CloseButton/>
				<Modal.Header>Freeze Hold</Modal.Header>
				<Modal.Body>
					<Text>Select the date when you want the hold thawed.</Text>
					<Box mt={3} mb={3}>
						<CalendarPicker
							onDateChange={onDateChange}
							minDate={minDate}
							previousTitle={<Icon as={MaterialIcons} name="chevron-left" color="trueGray.400" size="6"/>}
							nextTitle={<Icon as={MaterialIcons} name="chevron-right" color="trueGray.400" size="6"/>}
						/>
					</Box>
					<DisplayMessage type="info" message="If a date is not selected, the hold will be frozen for 30 days." />
				</Modal.Body>
				<Modal.Footer>
					<Button.Group space={2} size="md">
						<Button colorScheme="muted" variant="outline"
						        onPress={() => setShowModal(false)}>{translate('general.close_window')}</Button>
						<Button
							isLoading={loading}
							isLoadingText="Freezing..."
							onPress={() => {
								setLoading(true);
								freezeHold(freezeId, recordId, source, libraryUrl, userId, selectedReactivationDate).then(r => {
										setShowModal(false);
										onClose(onClose);
										setLoading(false);
									}
								);
							}}
						>
							Freeze Hold
						</Button>
					</Button.Group>
				</Modal.Footer>
			</Modal.Content>
		</Modal>
	</>
	)
}

const SelectPickupLocation = (props) => {

	const {locations, label, onClose, currentPickupId, holdId, libraryUrl, userId} = props;

	let pickupLocation = _.findIndex(locations, function(o) { return o.locationId == currentPickupId; });
	pickupLocation = _.nth(locations, pickupLocation);
	let pickupLocationCode = _.get(pickupLocation, 'code', '');
	pickupLocation = currentPickupId.concat("_", pickupLocationCode);
	// 									const locationId = item.locationId;
	// 									const code = item.code;
	// 									const id = locationId.concat("_", code);
	//console.log("Current pickup: " + currentPickupId);
	//console.log(locations);

	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);
	let [location, setLocation] = React.useState(pickupLocation);

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
							<Select
								name="pickupLocations"
								selectedValue={location}
								minWidth="200"
								accessibilityLabel="Select a new pickup location"
								_selectedItem={{
									bg: "tertiary.300",
									endIcon: <CheckIcon size="5" />
								}}
								mt={1}
								mb={3}
								onValueChange={itemValue => setLocation(itemValue)}
							>
								{locations.map((item, index) => {
									const locationId = item.locationId;
									const code = item.code;
									const id = locationId.concat("_", code);
									return <Select.Item value={id} label={item.name}/>;
								})}
							</Select>
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
									changeHoldPickUpLocation(holdId, location, libraryUrl, userId).then(r => {
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