import React, {Component, useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
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
	Text,
	useDisclose,
	HStack,
	VStack,
	IconButton,
	Select,
	CheckIcon,
	Checkbox,
	Image,
	ScrollView
} from "native-base";
import {Ionicons, MaterialCommunityIcons, MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {getHolds, getProfile, reloadHolds} from '../../util/loadPatron';
import {
	cancelHold, cancelHolds, cancelVdxRequest,
	changeHoldPickUpLocation,
	freezeHold,
	freezeHolds,
	thawHold,
	thawHolds
} from '../../util/accountActions';
import {getPickupLocations} from '../../util/loadLibrary';
import {userContext} from "../../context/user";
import DateTimePicker from '@react-native-community/datetimepicker';
import {DisplayMessage} from "../../components/Notifications";
import {loadError} from "../../components/loadError";

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
			selectedAllStartDate: null,
			groupValue: [],
			groupValues: [],
			selectFreeze: [],
			selectThaw: [],
			selectCancel: [],
		};
		this.onDateChange = this.onDateChange.bind(this);
		this.onAllDateChange = this.onAllDateChange.bind(this);
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

	onAllDateChange(date) {
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
		})
	}

	_fetchHolds = async () => {
		this.setState({
			isLoading: true,
		})

		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await reloadHolds(libraryUrl).then(r => {
			this.setState({
				holds: r['holds'],
				holdsNotReady: r['holdsNotReady'],
				holdsReady: r['holdsReady'],
				isLoading: false,
			})
		});
	}


	loadPickupLocations = async () => {
		const tmp = await AsyncStorage.getItem('@pickupLocations');
		const locations = JSON.parse(tmp);
		this.setState({
			locations: locations,
		})
	}

	_pickupLocations = async () => {
		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getPickupLocations(libraryUrl).then(r => this.loadPickupLocations())
	}

	componentDidMount = async () => {
		if(this.context.library.discoveryVersion) {
			let version = this.context.library.discoveryVersion;
			version = version.split(" ");
			this.setState({
				discoveryVersion: version[0],
			});
		} else {
			this.setState({
				discoveryVersion: "22.06.00",
			});
		}

		await this._fetchHolds();
		await this._pickupLocations();
		await this.loadHolds();
		await this.loadPickupLocations();

		this.setState({
			isLoading: false
		})

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// Handles opening the GroupedWork screen with the item data
	openGroupedWork = (item, libraryUrl) => {
		this.props.navigation.navigate("GroupedWork", {item: item, libraryUrl: libraryUrl});
	};

	selectedItems = (items) => {
		this.setState({
			groupValue: items
		})
	}

	setGroupValue = (values) => {
		this.setState({
			groupValues: values,
		})
	}

	clearGroupValue = () => {
		this.setState({
			groupValues: [],
			groupValue: [],
		})
	}

	// Renders the hold items on the screen
	renderHoldItem = (item, libraryUrl, user, updateProfile, _fetchHolds) => {
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
				_fetchHolds = {_fetchHolds}
				discoveryVersion={this.state.discoveryVersion}
				onDateChange={this.onDateChange}
				selectedReactivationDate={this.state.selectedStartDate}
				groupValue={this.state.groupValues}
				selectedItems={this.selectedItems}
			/>
		);
	}

	// Trigger a context refresh
	updateProfile = async () => {
		console.log("Getting new profile data from holds...");
		await getProfile().then(response => {
			this.context.user = response;
			this.setState({
				groupValues: [],
			})
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

	_listHeaderComponent = (libraryUrl, updateProfile, _fetchHolds) => {

		const groupValues = this.state.groupValues;
		let showSelectOptions = false;
		if(groupValues.length >= 1) {
			showSelectOptions = true;
		}

		if(showSelectOptions) {
			return (
				<Center mt={5} mb={5}>
					<ManageSelectedHolds
						selectedValues={this.state.groupValues}
						libraryUrl={libraryUrl}
						updateProfile={updateProfile}
						_fetchHolds={_fetchHolds}
						onAllDateChange={this.onDateChange}
						selectedReactivationDate={this.state.selectedStartDate}
						clearGroupValue={this.clearGroupValue}
					/>
				</Center>
			);
		}

		return (
			<Center mt={5} mb={5}>
					<ManageAllHolds
						data={this.state.holds}
						libraryUrl={libraryUrl}
						updateProfile={updateProfile}
						_fetchHolds={_fetchHolds}
						onDateChange={this.onDateChange}
						selectedReactivationDate={this.state.selectedStartDate}
					/>
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
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error, this._fetchHolds));
		}

		return (
			<ScrollView>
			<Box pt={10}>
				<Center>
					<Checkbox.Group
						defaultValue={this.state.groupValues}
						accessibilityLabel="choose multiple items"
						onChange={values => {this.setGroupValue(values)}}>
						<FlatList
							data={holds}
							ListEmptyComponent={this._listEmptyComponent()}
							ListFooterComponent={this._listHeaderComponent(library.baseUrl, this.updateProfile, this._fetchHolds)}
							renderItem={({item}) => this.renderHoldItem(item, library.baseUrl, user, this.updateProfile, this._fetchHolds)}
							keyExtractor={(item) => item.id.concat("_", item.position)}
						/>
					</Checkbox.Group>
					<Center pt={5} pb={5}>
						<IconButton _icon={{ as: MaterialIcons, name: "refresh", color: "coolGray.500" }} onPress={() => {this._fetchHolds()}}
						/>
					</Center>
				</Center>
			</Box>
			</ScrollView>
		);
	}
}

function HoldItem(props) {
	let expirationDate;
	let availableDate;
	const {data, locations, openGroupedWork, translations, libraryUrl, updateProfile, discoveryVersion, userProfile, onDateChange, selectedReactivationDate, _fetchHolds} = props;
	const {isOpen, onOpen, onClose} = useDisclose();

	const [loading, setLoading] = useState(false);
	const [thaw, setThaw] = useState(false);
	const [loadingText, setLoadingText] = useState("Loading...");

	//console.log(groupValue.length);

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
	let label = "";
	let method = "";
	let icon = "";
	if (data.canFreeze === true) {
		if (data.frozen === true) {
			label = translations.thawHold;
			method = "thawHold";
			icon = "play";
		} else {
			label = translations.freezeHold;
			method = "freezeHold";
			icon = "pause";
			if (data.available) {
				label = translate('overdrive.delay_checkout');
				method = "freezeHold";
				icon = "pause";
			}
		}
	}

	if (data.status === "Pending") {
		let statusColor = "green";
	}

	let title = "";
	if (data.title) {
		title = data.title;
		title = title.substring(0, title.lastIndexOf('/'));
		if (title === '') {
			title = data.title;
		}
	}

	let author = "";
	let countComma = 0;
	if (data.author) {
		author = data.author;
		countComma = author.split(',').length - 1;
		if (countComma > 1) {
			author = author.substring(0, author.lastIndexOf(','));
		}
	}

	let allowLinkedAccountAction = true;
	if(discoveryVersion < "22.05.00") {
		if(data.userId !== userProfile.id) {
			allowLinkedAccountAction = false;
		}
	}

	//console.log(allowLinkedAccountAction);

	let source = data.source;
	let holdSource = data.holdSource;
	let readyMessage = "";
	if (source === 'ils') {
		readyMessage = data.status;
	} else {
		readyMessage = translate('overdrive.hold_ready');
	}

	let isAvailable = data.available;
	let updateLocation = data.locationUpdateable;
	if (data.available && data.locationUpdateable) {
		updateLocation = false;
	}

	let checkoutOnline = false;
	if (data.available && source !== 'ils') {
		checkoutOnline = true;
	}

	let cancelable = false;
	if (!data.available && source !== 'ils') {
		cancelable = data.cancelable;
	} else if (!data.available && source === 'ils') {
		cancelable = true;
	}

	let cancelLabel = translate('holds.cancel_hold');
	if(data.type === "interlibrary_loan") {
		cancelLabel = translate('holds.cancel_request');
	}

	return (
		<>
			<Pressable onPress={onOpen} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3}>

					{data.coverUrl ? (
						<VStack>
							<Image source={{uri: data.coverUrl}} borderRadius="md" size={{base: "80px", lg: "120px"}} alt={data.title}/>
							{data.allowFreezeHolds && cancelable && allowLinkedAccountAction ?
								<Center><Checkbox value={method + '|' + data.recordId + "|" + data.cancelId + "|" + data.source + "|" + data.userId} my={3} size="md"></Checkbox></Center>
								: null}
						</VStack>
					) : (
						<Center><Checkbox value={method + '|' + data.recordId + "|" + data.cancelId + "|" + data.source + "|" + data.userId} my={3} size="md"></Checkbox></Center>
					)}

					<VStack maxW="80%">
						<Text bold mb={1} fontSize={{base: "sm", lg: "lg"}}>{title}</Text>
							{data.frozen ?
								<Text><Badge colorScheme="yellow" rounded="4px" mt={-.5}>{data.status}</Badge></Text> : <Text><Badge colorScheme="info" rounded="4px" mt={-.5}>{data.status}</Badge></Text>}
							{data.available ?
								<Text><Badge colorScheme="green" rounded="4px" mt={-.5}>{readyMessage}</Badge></Text>
								: null}

						{author ?
							<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translations.author}:</Text> {author}
							</Text>
							: null}
						{data.format ?
							<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>{translations.format}:</Text> {data.format}
							</Text>
						: null}
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>{translations.onHoldFor}:</Text> {data.user}
						</Text>
						{data.source === "ils" ? (<Text fontSize={{base: "xs", lg: "sm"}}>
								<Text bold>{translations.pickUpLocation}:</Text> {data.currentPickupName}</Text>) : null}
						{data.available ? <Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translations.pickupBy}:</Text> {expirationDate}</Text> :
							null}
						{!data.available && data.position ? (<Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translations.position}:</Text> {data.position}</Text>) : null}
						{data.type === "interlibrary_loan" ? (
							<Text fontSize={{base: "xs", lg: "sm"}} bold>Interlibrary Loan Request</Text>
						) : null}
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
					{cancelable && allowLinkedAccountAction && data.source !== "vdx" ?
						<Actionsheet.Item
							isLoading={loading}
							isLoadingText="Cancelling..."
							startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setLoading(true);
								cancelHold(data.cancelId, data.recordId, data.source, libraryUrl, data.userId).then(r => {
									updateProfile();
									_fetchHolds();
									onClose(onClose);
									setLoading(false);
								});
							}}
						>
							{cancelLabel}
						</Actionsheet.Item>
						: ""}
					{cancelable && allowLinkedAccountAction && data.source === "vdx" ? (
						<Actionsheet.Item
							isLoading={loading}
							isLoadingText="Cancelling..."
							startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6"/>}
							onPress={() => {
								setLoading(true);
								cancelVdxRequest(libraryUrl, data.sourceId, data.cancelId).then(r => {
									updateProfile();
									_fetchHolds();
									onClose(onClose);
									setLoading(false);
								});
							}}
						>
							{cancelLabel}
						</Actionsheet.Item>
					) : ""}
					{data.allowFreezeHolds === "1" && allowLinkedAccountAction && data.frozen === false ?
						<SelectThawDate
							handleOnDateChange={onDateChange}
							onClose={onClose}
							freezeId={data.cancelId}
							recordId={data.recordId}
							source={data.source}
							libraryUrl={libraryUrl}
							userId={data.userId}
							reactivationDate={selectedReactivationDate}
							_fetchHolds={_fetchHolds}
							updateProfile={updateProfile}
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
									_fetchHolds();
									onClose(onClose);
									setThaw(false);
								});
							}}
						>
							{label}
						</Actionsheet.Item>
						: ""}

					{updateLocation && allowLinkedAccountAction ?
						<SelectPickupLocation locations={locations} onClose={onClose} _fetchHolds={_fetchHolds}
						                      userId = {data.userId}
						                      currentPickupId={data.pickupLocationId} holdId={data.cancelId} label={translations.changePickUpLocation} libraryUrl={libraryUrl}/>
						: ""}

				</Actionsheet.Content>
			</Actionsheet>
		</>
	)
}

const SelectThawDate = (props) => {
	const {handleOnDateChange, onClose, freezeId, recordId, source, libraryUrl, userId, _fetchHolds, data, count, updateProfile, clearGroupValue} = props;
	//const minDate = new Date(); // Today
	//const maxDate = new Date(2017, 6, 3);
	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);

	const todaysDate = new Date();
	const minDate = todaysDate.setDate(todaysDate.getDate() + 7);
	//console.log(defaultDate);
	const [date, setDate] = useState(new Date());
	const [mode, setMode] = useState('date');
	const [show, setShow] = useState(false);

	const onChange = (event, selectedDate) => {
		let currentDate = selectedDate;
		setShow(false);
		setDate(currentDate);
	};

	return (
	<>
		{data ? (
			<Actionsheet.Item
				onPress={() => {
					setShowModal(true)}}>
				<Text>Freeze holds ({count})</Text>
			</Actionsheet.Item>

		) : (
			<Actionsheet.Item startIcon={<Icon as={MaterialIcons} name="pause" color="trueGray.400" mr="1" size="6"/>}
			                  onPress={() => {
				                  setShowModal(true);
			                  }}>
				Freeze Hold
			</Actionsheet.Item>
		)}
		<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick="false" size="full">
			<Modal.Content>
				<Modal.CloseButton/>
				<Modal.Header>{data ? "Freeze Holds" : "Freeze Hold"}</Modal.Header>
				<Modal.Body>
					{data ? <Text>Select the date when you want the selected holds thawed.</Text> : <Text>Select the date when you want the hold thawed.</Text>}
					<Box mt={3} mb={3}>
						<DateTimePicker
							testID="dateTimePicker"
							value={date}
							mode="date"
							display="default"
							minimumDate={minDate}
							onChange={onChange}
						/>

					</Box>
				</Modal.Body>
				<Modal.Footer>
					<Button.Group space={2} size="md">
						<Button colorScheme="muted" variant="outline"
						        onPress={() => setShowModal(false)}>{translate('general.close_window')}</Button>
						{data ? (
							<Button
								isLoading={loading}
								isLoadingText="Freezing..."
								onPress={() => {
									setLoading(true);
									freezeHolds(data, libraryUrl, date).then(r => {
											clearGroupValue();
											setShowModal(false);
											updateProfile();
											_fetchHolds();
											onClose(onClose);
											setLoading(false);
										}
									);
								}}
							>
								Freeze Holds
							</Button>
						) : (
							<Button
								isLoading={loading}
								isLoadingText="Freezing..."
								onPress={() => {
									setLoading(true);
									freezeHold(freezeId, recordId, source, libraryUrl, userId, date).then(r => {
											clearGroupValue();
											setShowModal(false);
											updateProfile();
											_fetchHolds();
											onClose(onClose);
											setLoading(false);
										}
									);
								}}
							>
								Freeze Hold
							</Button>
						)}
					</Button.Group>
				</Modal.Footer>
			</Modal.Content>
		</Modal>
	</>
	)
}

const SelectPickupLocation = (props) => {

	const {locations, label, onClose, currentPickupId, holdId, libraryUrl, userId, _fetchHolds} = props;

	let pickupLocation = _.findIndex(locations, function(o) { return o.locationId === currentPickupId; });
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
											_fetchHolds();
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

const ManageSelectedHolds = (props) => {
	const {selectedValues, onAllDateChange, libraryUrl, selectedReactivationDate, _fetchHolds, updateProfile, clearGroupValue} = props;
	const {isOpen, onOpen, onClose} = useDisclose();
	const [loading, setLoading] = useState(false);
	const [cancelling, startCancelling] = useState(false);
	const [thawing, startThawing] = useState(false);

	let titlesToFreeze = [];
	let titlesToThaw = [];
	let titlesToCancel = [];

	const categorizedValues = selectedValues.map((item, index) => {
		if(item.includes("freeze")) {
			let freezeArr = item.split('|');
			titlesToFreeze.push({
				'action': freezeArr[0],
				'recordId': freezeArr[1],
				'cancelId': freezeArr[2],
				'source': freezeArr[3],
				'patronId': freezeArr[4],
			})
		}
		if(item.includes("thaw")) {
			let thawArr = item.split('|');
			titlesToThaw.push({
				'action': thawArr[0],
				'recordId': thawArr[1],
				'cancelId': thawArr[2],
				'source': thawArr[3],
				'patronId': thawArr[4],
			})
		}

		let cancelArr = item.split('|');
		titlesToCancel.push({
			'action': cancelArr[0],
			'recordId': cancelArr[1],
			'cancelId': cancelArr[2],
			'source': cancelArr[3],
			'patronId': cancelArr[4],
		})

	});

	let numSelected = "Managed Selected (" + selectedValues.length + ")";
	let numToCancel = titlesToCancel.length;
	let numToFreeze = titlesToFreeze.length;
	let numToThaw = titlesToThaw.length;

	return (
		<Center>
			<Button onPress={onOpen}>{numSelected}</Button>
			<Actionsheet isOpen={isOpen} onClose={onClose}>
				<Actionsheet.Content>
					{numToCancel > 0 ? <Actionsheet.Item isLoading={cancelling} isLoadingText="Cancelling..." onPress={() => {
						startCancelling(true);
						cancelHolds(titlesToCancel, libraryUrl).then(r => {
							clearGroupValue();
							numToThaw = [];
							numToCancel = [];
							numToFreeze = [];
							updateProfile();
							_fetchHolds();
							onClose(onClose);
							startCancelling(false);
						})
					}}><Text>Cancel holds ({numToCancel})</Text></Actionsheet.Item> : <Actionsheet.Item isDisabled><Text>Cancel holds ({numToCancel})</Text></Actionsheet.Item>}
					{numToFreeze > 0 ? <SelectThawDate count={numToFreeze} data={titlesToFreeze} handleOnDateChange={onAllDateChange} libraryUrl={libraryUrl} reactivationDate={selectedReactivationDate} _fetchHolds={_fetchHolds} onClose={onClose} updateProfile={updateProfile} clearGroupValue={clearGroupValue} /> : <Actionsheet.Item isDisabled><Text>Freeze holds ({numToFreeze})</Text></Actionsheet.Item>}
					{numToThaw > 0 ? <Actionsheet.Item isLoading={thawing} isLoadingText="Thawing..." onPress={() => {
						startThawing(true);
						thawHolds(titlesToThaw, libraryUrl).then(r => {
							clearGroupValue();
							numToThaw = [];
							numToCancel = [];
							numToFreeze = [];
							updateProfile();
							_fetchHolds();
							onClose(onClose);
							startThawing(false);
						})
					}}><Text>Thaw holds ({numToThaw})</Text></Actionsheet.Item> : <Actionsheet.Item isDisabled><Text>Thaw holds ({numToThaw})</Text></Actionsheet.Item>}
				</Actionsheet.Content>
			</Actionsheet>
		</Center>
	)
}

const ManageAllHolds = (props) => {
	const {data, libraryUrl, onDateChange, selectedReactivationDate, updateProfile, _fetchHolds} = props;
	const {isOpen, onOpen, onClose} = useDisclose();
	const [loading, setLoading] = useState(false);
	const [cancelling, startCancelling] = useState(false);
	const [thawing, startThawing] = useState(false);

	let titlesToFreeze = [];
	let titlesToThaw = [];
	let titlesToCancel = [];

	const categorizedValues = data.map((item, index) => {
		if(item.allowFreezeHolds && item.frozen) {
			titlesToThaw.push({
				'recordId': item.recordId,
				'cancelId': item.cancelId,
				'source': item.source,
				'patronId': item.userId,
			})
		}
		if(item.allowFreezeHolds && !item.frozen) {
			titlesToFreeze.push({
				'recordId': item.recordId,
				'cancelId': item.cancelId,
				'source': item.source,
				'patronId': item.userId,
			})
		}
		if(item.cancelable) {
			titlesToCancel.push({
				'recordId': item.recordId,
				'cancelId': item.cancelId,
				'source': item.source,
				'patronId': item.userId,
			})
		}
	});

	let numToCancel = titlesToCancel.length;
	let numToFreeze = titlesToFreeze.length;
	let numToThaw = titlesToThaw.length;

	return (
		<Center>
			<Button onPress={onOpen}>Manage All</Button>
			<Actionsheet isOpen={isOpen} onClose={onClose}>
				<Actionsheet.Content>
					<Actionsheet.Item
						isLoading={cancelling}
						isLoadingText="Cancelling..."
						onPress={() => {
							startCancelling(true);
							cancelHolds(titlesToCancel, libraryUrl).then(r => {
								updateProfile();
								_fetchHolds();
								onClose(onClose);
								startCancelling(false);
							})
						}}
					>
						<Text>Cancel all holds ({numToCancel})</Text></Actionsheet.Item>
					<Actionsheet.Item
					>
						<SelectThawDate count={numToFreeze} data={titlesToFreeze} handleOnDateChange={onDateChange} libraryUrl={libraryUrl} reactivationDate={selectedReactivationDate} updateProfile={updateProfile} _fetchHolds={_fetchHolds}/></Actionsheet.Item>
					<Actionsheet.Item
						isLoading={thawing}
						isLoadingText="Thawing..."
						onPress={() => {
							startThawing(true);
							thawHolds(titlesToThaw, libraryUrl).then(r => {
								updateProfile();
								_fetchHolds();
								onClose(onClose);
								startThawing(false);
							})
						}}
					>
						<Text>Thaw all holds ({numToThaw})</Text></Actionsheet.Item>
				</Actionsheet.Content>
			</Actionsheet>
		</Center>
	)
}