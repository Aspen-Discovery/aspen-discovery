import React from "react";
import {Button, Center, HStack, Text, VStack, Badge } from "native-base";
import {checkoutItem, openCheckouts, openSideLoad, overDriveSample, placeHold} from "../../util/recordActions";
import SelectPickupLocation from "./SelectPickupLocation";
import ShowItemDetails from "./CopyDetails";
import _ from "lodash";

const DisplayRecord = (props) => {

	const [loading, setLoading] = React.useState(false);
	const {available, availableOnline, actions, edition, format, publisher, publicationDate, status, copiesMessage, source, id, title, locationCount, locations, showAlert, itemDetails, user} = props;

	let actionCount = 1;
	if(typeof actions !== "undefined") {
		actionCount = _.size(actions);
	}

	let copyCount = 1;
	if(typeof itemDetails !== "undefined") {
		copyCount = _.size(itemDetails);
	}

	let statusColor;
	if(available === true) {
		statusColor = "success";
	} else if(availableOnline === true) {
		statusColor = "success";
	} else {
		statusColor = "danger";
	}

	console.log(loading);
	return (
		<Center mt={5} mb={0} bgColor="white" _dark={{ bgColor: "coolGray.900" }} p={3} rounded="8px" width={{base: "100%", lg: "75%"}}>
			{publisher ? (<Text fontSize={10} bold pb={3}>{edition} {publisher}, {publicationDate}</Text>) : null}
			<HStack justifyContent="space-around" alignItems="center" space={2} flex={1}>
				<VStack space={1} alignItems="center" maxW="40%" flex={1}>
					<Badge colorScheme={statusColor} rounded="4px" _text={{fontSize: 14}} mb={.5}>{status}</Badge>
					{copiesMessage ? (<Text fontSize={8} textAlign="center" italic={1} maxW="75%">{copiesMessage}</Text>) : null}
					{source === "ils" && itemDetails ? <ShowItemDetails data={itemDetails} title={title} /> : null}
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
								/>
							)
						} else if (thisAction.title === "Access Online") {
							return (
								<SideLoad
									actionUrl = {thisAction.url}
									actionLabel = {thisAction.title}
								/>
							)
						} else if (thisAction.title === "Checked Out to You") {
							return (
								<CheckedOutToYou />
							)
						} else {
							return (
								<Button size={{base: "md", lg: "lg"}} colorScheme="primary" variant="solid"
								        _text={{padding: 0, textAlign: "center"}}
								        isLoading={loading}
								        isLoadingText="Checking out title..."
								        style={{flex: 1, flexWrap: 'wrap'}} onPress={async () => {
									setLoading(true);
									await completeAction(id, thisAction.type, user.id).then(response => {
										showAlert(response);
										setLoading(false);
									})
								}}>{thisAction.title}</Button>
							);
						}
					})}
				</Button.Group>
			</HStack>
		</Center>
	)
}

const ILS = (props) => {
	const [loading, setLoading] = React.useState(false);

	if (props.locationCount > 1) {
		return (
			<SelectPickupLocation
				locations={props.locations}
				label={props.actionLabel}
				action={props.actionType}
				record={props.id}
				patron={props.patronId}
				showAlert={props.showAlert}
				preferredLocation={props.pickupLocation}
			/>
		)
	} else {
		return (
			<Button
				size={{base: "md", lg: "lg"}}
		        colorScheme="primary"
		        variant="solid"
		        _text={{padding: 0, textAlign: "center"}}
		        style={{flex: 1, flexWrap: 'wrap'}}
				isLoading={loading}
				isLoadingText="Placing hold..."
		        onPress={async () => {
			        setLoading(true);
					await completeAction(props.id, props.actionType, props.patronId, null, null, props.locations[0].code).then(response => {setLoading(false);props.showAlert(response)})
				}}>{props.actionLabel}</Button>
		);
	}
}

const OverDriveSample = (props) => {
	const [loading, setLoading] = React.useState(false);

	return (
		<Button size={{base: "xs", lg: "sm"}}
	        colorScheme="primary"
	        variant="outline"
	        _text={{padding: 0, textAlign: "center", fontSize: 12}}
	        style={{flex: 1, flexWrap: 'wrap'}}
            isLoading={loading}
            isLoadingText="Opening..."
	        onPress={() => {
		        setLoading(true);
		        completeAction(props.id, props.actionType, props.patronId, props.formatId, props.sampleNumber).then(r => setLoading(false))
	        }}
		>{props.actionLabel}</Button>
	)
}

const SideLoad = (props) => {
	const [loading, setLoading] = React.useState(false);

	return (
		<Button size={{base: "md", lg: "lg"}}
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
		<Button size={{base: "md", lg: "lg"}} colorScheme="primary" variant="solid"
		        _text={{padding: 0, textAlign: "center"}}
		        isLoading={loading}
		        isLoadingText="Loading..."
		        style={{flex: 1, flexWrap: 'wrap'}} onPress={() => {
			setLoading(true);
			openCheckouts()
		}}>{props.title}</Button>
	)
}

// complete the action on the item, i.e. checkout, hold, or view sample
export async function completeAction(id, actionType, patronId, formatId = null, sampleNumber = null, pickupBranch = null) {
	const recordId = id.split(":");
	const source = recordId[0];
	const itemId = recordId[1];

	if (actionType.includes("checkout")) {
		return await checkoutItem(itemId, source, patronId);
	} else if (actionType.includes("hold")) {

		if (!global.overdriveEmail && global.promptForOverdriveEmail === 1 && source === "overdrive") {
			const getPromptForOverdriveEmail = [];
			getPromptForOverdriveEmail['getPrompt'] = true;
			getPromptForOverdriveEmail['itemId'] = itemId;
			getPromptForOverdriveEmail['source'] = source;
			getPromptForOverdriveEmail['patronId'] = patronId;
			getPromptForOverdriveEmail['overdriveEmail'] = global.overdriveEmail;
			getPromptForOverdriveEmail['promptForOverdriveEmail'] = global.promptForOverdriveEmail;
			return getPromptForOverdriveEmail;
		} else {
			return await placeHold(itemId, source, patronId, pickupBranch);
		}

	} else if (actionType.includes("sample")) {
		return await overDriveSample(formatId, itemId, sampleNumber);
	}
}

export default DisplayRecord;