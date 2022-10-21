import React from "react";
import {Center, Text, VStack} from "native-base";

// custom components and helper files
import {translate} from '../../translations/translations';
import DisplayRecord, {Record} from "./Record";

const Manifestation = (props) => {
	let arrayToSearch = [];
	const {navigation, data, format, language, locations, showAlert, groupedWorkTitle, groupedWorkAuthor, groupedWorkISBN, itemDetails, user, groupedWorkId, library, linkedAccounts, openHolds, openCheckouts, discoveryVersion, updateProfile} = props;

	if(typeof data[format] !== "undefined") {
		arrayToSearch = data[format];
	}

	let locationCount = 1;
	if(typeof locations !== "undefined") {
		locationCount = locations.length;
	}

	let match = arrayToSearch.filter(function (item) {
		return (item.format === format);
	});

	match = match.filter(function (item) {
		return (item.language === language);
	});

	let copyDetails = [];
	match.map((item) => {
		let copyDetail = [];
		if(discoveryVersion >= "22.09.00") {
			copyDetail = {
				'id': item.id,
				'format': item.format,
				'totalCopies': item.totalCopies,
				'availableCopies': item.availableCopies,
				'shelfLocation': item.shelfLocation,
				'callNumber': item.callNumber,
			};
		};

		copyDetails.push(copyDetail)
	})

	if (match.length === 0) {
		return (
			<Center mt={5} mb={0} bgColor="white" _dark={{ bgColor: "coolGray.900" }} p={3} rounded="8px">
				<VStack alignItems="center" width={{base: "100%", lg: "75%"}}>
					<Text bold textAlign="center">
						{translate('grouped_work.no_matches', {
						language: language,
						format: format
						})}
					</Text>
				</VStack>
			</Center>
		);
	}

	return match.map((item, index) => {

		let volumes = [];
		let majorityOfItemsHaveVolumes = false;
		let hasItemsWithoutVolumes = false;
		if(discoveryVersion >= "22.06.00") {
			volumes = item.volumes;
			majorityOfItemsHaveVolumes = item.majorityOfItemsHaveVolumes;
			hasItemsWithoutVolumes = item.hasItemsWithoutVolumes;
		}

		return (
			<Record
				available = {item.available}
				availableOnline = {item.availableOnline}
				actions = {item.action}
				edition = {item.edition}
				format = {item.format}
				publisher = {item.publisher}
				publicationDate = {item.publicationDate}
				status = {item.status}
				copiesMessage = {item.copiesMessage}
				source = {item.source}
				id = {item.id}
				title = {groupedWorkTitle}
				locationCount = {locationCount}
				locations = {locations}
				showAlert = {showAlert}
				itemDetails = {itemDetails}
				user = {user}
				groupedWorkId = {groupedWorkId}
				groupedWorkAuthor = {groupedWorkAuthor}
				groupedWorkISBN = {groupedWorkISBN}
				library = {library}
				linkedAccounts = {linkedAccounts}
				openCheckouts = {openCheckouts}
				openHolds = {openHolds}
				hasItemsWithoutVolumes = {hasItemsWithoutVolumes}
				majorityOfItemsHaveVolumes = {majorityOfItemsHaveVolumes}
				volumes = {volumes}
				discoveryVersion = {discoveryVersion}
				updateProfile = {updateProfile}
				navigation = {navigation}
				recordData = {item}
				copyDetails = {copyDetails}
			/>
		)
	})
}

export default Manifestation;