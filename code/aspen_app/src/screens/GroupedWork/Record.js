import React, {Component} from "react";
import {Button, Center, HStack, Text, VStack, Badge } from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
	checkoutItem,
	openSideLoad,
	overDriveSample,
	placeHold
} from "../../util/recordActions";
import SelectPickupLocation from "./SelectPickupLocation";
import ShowItemDetails from "./CopyDetails";
import _ from "lodash";
import SelectLinkedAccount from "./SelectLinkedAccount";
import SelectVolumeHold from "./SelectVolumeHold";
import {userContext} from "../../context/user";

export class Record extends Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: true,
		}
	}

	componentDidMount = async () => {

	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const {available, availableOnline, actions, edition, format, publisher, publicationDate, status, copiesMessage, source, id, title, locationCount, locations, showAlert, itemDetails, groupedWorkId, linkedAccounts, openHolds, openCheckouts, discoveryVersion, updateProfile, majorityOfItemsHaveVolumes, volumes, hasItemsWithoutVolumes} = this.props;
		let actionCount = 1;
		console.log(actions);
		if(typeof actions !== 'undefined') {
			actionCount = _.size(actions);
		}

		let copyCount = 1;
		if(typeof itemDetails !== 'undefined') {
			copyCount = _.size(itemDetails);
		}

		let linkedAccountsCount = 0;
		if(discoveryVersion >= "22.05.00") {
			if(typeof linkedAccounts !== 'undefined') {
				linkedAccountsCount = _.size(linkedAccounts);
			}
		}

		let statusColor;
		if(available === true) {
			statusColor = "success";
		} else if(availableOnline === true) {
			statusColor = "success";
		} else {
			statusColor = "danger";
		}

		let libraryUrl = library.baseUrl;

		return (
			<Center mt={5} mb={0} bgColor="white" _dark={{ bgColor: "coolGray.900" }} p={3} rounded="8px" width={{base: "100%", lg: "75%"}}>
				{publisher ? (<Text fontSize={10} bold pb={3}>{edition} {publisher}, {publicationDate}</Text>) : null}
				<HStack justifyContent="space-around" alignItems="center" space={2} flex={1}>
					<VStack space={1} alignItems="center" maxW="40%" flex={1}>
						<Badge colorScheme={statusColor} rounded="4px" _text={{fontSize: 14}} mb={.5}>{status}</Badge>
						{copiesMessage ? (<Text fontSize={8} textAlign="center" italic={1} maxW="75%">{copiesMessage}</Text>) : null}
						{source === "ils" && itemDetails ? <ShowItemDetails id={groupedWorkId} format={format} title={title} libraryUrl={libraryUrl}/> : null}
					</VStack>
					<Button.Group maxW="50%" direction={actionCount > 1 ? "column" : "row"} alignItems="stretch">
						{actions.map((thisAction) => {
							if (thisAction.type === "overdrive_sample") {
								return (
									<OverDriveSample
										id = {id}
										actionType = {thisAction.type}
										actionLabel = {thisAction.title}
										patronId = {user.id}
										formatId = {thisAction.formatId}
										sampleNumber = {thisAction.sampleNumber}
										libraryUrl = {libraryUrl}
									/>
								)
							} else if (thisAction.type === "ils_hold") {
								return (
									<ILS
										id = {id}
										actionLabel = {thisAction.title}
										actionType = {thisAction.type}
										patronId = {user.id}
										formatId = {thisAction.formatId}
										sampleNumber = {thisAction.sampleNumber}
										pickupLocation = {user.pickupLocationId}
										rememberPickupLocation = {user.rememberHoldPickupLocation}
										locationCount = {locationCount}
										locations = {locations}
										showAlert = {showAlert}
										libraryUrl = {libraryUrl}
										user = {user}
										linkedAccounts = {linkedAccounts}
										linkedAccountsCount = {linkedAccountsCount}
										updateProfile = {updateProfile}
										hasItemsWithoutVolumes = {hasItemsWithoutVolumes}
										majorityOfItemsHaveVolumes = {majorityOfItemsHaveVolumes}
										volumes = {volumes}
									/>
								)
							} else if (thisAction.title === "Access Online") {
								return (
									<SideLoad
										actionUrl = {thisAction.url}
										actionLabel = {thisAction.title}
										libraryUrl = {libraryUrl}
									/>
								)
							} else if (thisAction.url === "/MyAccount/CheckedOut") {
								return (
									<CheckedOutToYou title={thisAction.title} openCheckouts={openCheckouts} />
								)
							} else if (thisAction.url === "/MyAccount/Holds") {
								return (
									<OnHoldForYou title={thisAction.title} openHolds={openHolds} />
								)
							} else {
								return (
									<CheckOutEContent
										action = {completeAction}
										title = {thisAction.title}
										actionType = {thisAction.type}
										id = {id}
										libraryUrl = {libraryUrl}
										user = {user}
										showAlert = {showAlert}
										linkedAccounts = {linkedAccounts}
										linkedAccountsCount = {linkedAccountsCount}
										updateProfile = {updateProfile}
									/>
								);
							}
						})}
					</Button.Group>
				</HStack>
			</Center>
		)
	}
}

const CheckOutEContent = (props) => {
	const [loading, setLoading] = React.useState(false);
	if(props.linkedAccountsCount > 0) {
		return (
			<SelectLinkedAccount action={props.actionType} id={props.id} user={props.user} linkedAccounts={props.linkedAccounts} title={props.title} libraryUrl={props.libraryUrl} showAlert={props.showAlert} updateProfile={props.updateProfile} />
		)
	} else {
		return (
			<Button size="md" colorScheme="primary" variant="solid"
			        _text={{padding: 0, textAlign: "center"}}
			        isLoading={loading}
			        isLoadingText="Checking out title..."
			        style={{flex: 1, flexWrap: 'wrap'}} onPress={async () => {
				setLoading(true);
				completeAction(props.id, props.actionType, props.user.id, null, null, null, props.libraryUrl, props.user).then(response => {
					props.updateProfile();
					props.showAlert(response);
					setLoading(false);
				})
			}}>{props.title}</Button>
		)
	}
}

const ILS = (props) => {
	const [loading, setLoading] = React.useState(false);
	if (props.locationCount && props.locationCount > 1) {
		return (
			<SelectPickupLocation
				locations={props.locations}
				label={props.actionLabel}
				action={props.actionType}
				record={props.id}
				patron={props.patronId}
				showAlert={props.showAlert}
				preferredLocation={props.pickupLocation}
				libraryUrl={props.libraryUrl}
				linkedAccounts = {props.linkedAccounts}
				linkedAccountsCount = {props.linkedAccountsCount}
				user = {props.user}
				majorityOfItemsHaveVolumes = {props.majorityOfItemsHaveVolumes}
				volumes = {props.volumes}
				updateProfile = {props.updateProfile}
				hasItemsWithoutVolumes = {props.hasItemsWithoutVolumes}
			/>
		)
	} else {
		if(props.majorityOfItemsHaveVolumes || props.hasItemsWithoutVolumes) {
			return (
				<SelectVolumeHold
					label={props.actionLabel}
					action={props.actionType}
					record={props.id}
					patron={props.patronId}
					showAlert={props.showAlert}
					libraryUrl={props.libraryUrl}
					linkedAccounts = {props.linkedAccounts}
					linkedAccountsCount = {props.linkedAccountsCount}
					user = {props.user}
					volumes = {props.volumes}
					updateProfile = {props.updateProfile}
					hasItemsWithoutVolumes = {props.hasItemsWithoutVolumes}
					majorityOfItemsHaveVolumes = {props.majorityOfItemsHaveVolumes}
				/>
			)
		} else {
			return (
				<Button
					size="md"
					colorScheme="primary"
					variant="solid"
					_text={{padding: 0, textAlign: "center"}}
					style={{flex: 1, flexWrap: 'wrap'}}
					isLoading={loading}
					isLoadingText="Placing hold..."
					onPress={async () => {
						setLoading(true);
						completeAction(props.id, props.actionType, props.patronId, null, null, props.locations[0].code, props.libraryUrl).then(response => {
							setLoading(false);
							props.showAlert(response)
							console.log(response);
						})
					}}>{props.actionLabel}</Button>
			);
		}
	}
}

const OverDriveSample = (props) => {
	const [loading, setLoading] = React.useState(false);

	//console.log(props);
	return (
		<Button size="xs"
	        colorScheme="primary"
	        variant="outline"
	        _text={{padding: 0, textAlign: "center", fontSize: 12}}
	        style={{flex: 1, flexWrap: 'wrap'}}
            isLoading={loading}
            isLoadingText="Opening..."
	        onPress={() => {
		        setLoading(true);
		        completeAction(props.id, props.actionType, props.patronId, props.formatId, props.sampleNumber, null, props.libraryUrl, props.user, null).then(r => {
			        setLoading(false);
		        })
	        }}
		>{props.actionLabel}</Button>
	)
}

const SideLoad = (props) => {
	const [loading, setLoading] = React.useState(false);

	return (
		<Button size="md"
	        colorScheme="primary"
	        variant="solid"
	        _text={{padding: 0, textAlign: "center"}}
	        style={{flex: 1, flexWrap: 'wrap'}}
            isLoading={loading}
            isLoadingText="Opening..."
	        onPress={async () => {
		        setLoading(true);
				await openSideLoad(props.actionUrl).then(r => setLoading(false))
			}}
		>{props.actionLabel}</Button>
	)
}

const CheckedOutToYou = (props) => {
	const [loading, setLoading] = React.useState(false);

	return (
		<Button size="md" colorScheme="primary" variant="solid"
		        _text={{padding: 0, textAlign: "center"}}
		        isLoading={loading}
		        isLoadingText="Loading..."
		        style={{flex: 1, flexWrap: 'wrap'}} onPress={() => {
			setLoading(true);
			props.openCheckouts()
		}}>{props.title}</Button>
	)
}

const OnHoldForYou = (props) => {
	const [loading, setLoading] = React.useState(false);

	return (
		<Button size="md" colorScheme="primary" variant="solid"
		        _text={{padding: 0, textAlign: "center"}}
		        isLoading={loading}
		        isLoadingText="Loading..."
		        style={{flex: 1, flexWrap: 'wrap'}} onPress={() => {
			setLoading(true);
			props.openHolds()
		}}>{props.title}</Button>
	)
}

// complete the action on the item, i.e. checkout, hold, or view sample
export async function completeAction(id, actionType, patronId, formatId = null, sampleNumber = null, pickupBranch = null, libraryUrl, volumeId = null) {
	const recordId = id.split(":");
	const source = recordId[0];
	let itemId = recordId[1];
	if(recordId[1] === "kindle") {
		itemId = recordId[2]
	}

	let patronProfile;
	try {
		let tmp = await AsyncStorage.getItem("@patronProfile");
		patronProfile = JSON.parse(tmp);
	} catch (e) {
		console.log("Unable to fetch patron profile in grouped work from async storage");
		console.log(e);
	}

	//console.log(patronProfile);

	if (actionType.includes("checkout")) {
		return await checkoutItem(libraryUrl, itemId, source, patronId);
	} else if (actionType.includes("hold")) {

		if(volumeId) {
			return await placeHold(libraryUrl, itemId, source, patronId, pickupBranch, volumeId);
		} else if (!patronProfile.overdriveEmail && patronProfile.promptForOverdriveEmail === 1 && source === "overdrive") {
			const getPromptForOverdriveEmail = [];
			getPromptForOverdriveEmail['getPrompt'] = true;
			getPromptForOverdriveEmail['itemId'] = itemId;
			getPromptForOverdriveEmail['source'] = source;
			getPromptForOverdriveEmail['patronId'] = patronId;
			getPromptForOverdriveEmail['overdriveEmail'] = patronProfile.overdriveEmail;
			getPromptForOverdriveEmail['promptForOverdriveEmail'] = patronProfile.promptForOverdriveEmail;
			return getPromptForOverdriveEmail;
		} else {
			return await placeHold(libraryUrl, itemId, source, patronId, pickupBranch);
		}

	} else if (actionType.includes("sample")) {
		return await overDriveSample(libraryUrl, formatId, itemId, sampleNumber);
	}
}

export default Record;