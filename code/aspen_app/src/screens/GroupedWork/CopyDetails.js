import React, {useState} from "react";
import {Button, Center, Modal, HStack, Text, Icon, FlatList, Heading } from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import {translate} from "../../translations/translations";
import {getItemDetails} from "../../util/recordActions";

const ShowItemDetails = (props) => {
	const { data, title, id, format, libraryUrl } = props;
	const [showModal, setShowModal] = useState(false);
	const [details, setDetails] = React.useState('');
	const [shouldFetch, setShouldFetch] = React.useState(true);
	const loading = React.useCallback(() => setShouldFetch(true), []);

	React.useEffect(() => {
		if(!loading) {
			return;
		}

		const loadItemDetails = async () => {
			//id === undefined
			//format === null
			if(typeof id !== "undefined" && format !== null) {
				await getItemDetails(libraryUrl, id, format).then(response => {
					setShouldFetch(false);
					setDetails(response);
				});
			}
		};
		loadItemDetails();
	}, [loading])
	return (
		<Center>
			<Button
				onPress={() => {
					getItemDetails(libraryUrl, id, format).then(response => {
						setDetails(response);
						setShowModal(true);
					})
				}}
				colorScheme="tertiary"
				variant="ghost"
				size="sm"
				leftIcon={<Icon as={MaterialIcons} name="location-pin" size="xs" mr="-1"/>}
			>
				Where is it?</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full">
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<HStack>
							<Icon as={MaterialIcons} name="location-pin" size="xs" mt=".5" pr={5}/>
							<Heading size="sm">Where is it?</Heading>
						</HStack>
					</Modal.Header>
					<Modal.Body>
						<FlatList
							data={details}
							keyExtractor={(item) => item.description}
							ListHeaderComponent={renderHeader()}
							renderItem={({item}) => renderCopyDetails(item)}
						/>
					</Modal.Body>

				</Modal.Content>
			</Modal>
		</Center>
	);
}

const renderHeader = () => {
	return (
		<HStack space={4} justifyContent="space-between" pb={2}>
			<Text bold w="30%" fontSize="xs">Available Copies</Text>
			<Text bold w="30%" fontSize="xs">Location</Text>
			<Text bold w="30%" fontSize="xs">Call #</Text>
		</HStack>
	)
}

const renderCopyDetails = (item) => {
	return (
		<HStack space={4} justifyContent="space-between">
			<Text w="30%" fontSize="xs">{item.availableCopies} of {item.totalCopies}</Text>
			<Text w="30%" fontSize="xs">{item.shelfLocation}</Text>
			<Text w="30%" fontSize="xs">{item.callNumber}</Text>
		</HStack>
	);
};

export default ShowItemDetails;