import React, {useState} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Button, Center, Modal, Box, Text, Icon, FormControl, Input, Radio, TextArea, Heading, Select, HStack, Stack } from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import {addTitlesToList, createListFromTitle, getLists} from "../../util/loadPatron";
import _ from "lodash";
import {popAlert} from "../../components/loadError";

export const AddToListFromItem = (props) => {
	const { user, item, libraryUrl } = props;
	const lastListUsed = user.lastListUsed;
	const [showUseExistingModal, setShowUseExistingModal] = useState(false);
	const [showCreateNewModal, setShowCreateNewModal] = useState(false);
	const [loading, setLoading] = useState(false);
	const [lists, setLists] = useState([]);
	const [listId, setListId] = useState(lastListUsed);

	const [title, setTitle] = React.useState('');
	const [description, setDescription] = React.useState('');
	const [access, setAccess] = React.useState(false);

	return (
		<Center>
			<Button onPress={
				async () => {
					await getLists(libraryUrl).then(response => {
						setLists(response);
						setShowUseExistingModal(true);
					});
				}
			} colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="xs" mr="-1"/>}>
				Add to list</Button>
			<Modal isOpen={showUseExistingModal} onClose={() => setShowUseExistingModal(false)} size="full">
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="sm">Add to List</Heading>
					</Modal.Header>
					<Modal.Body>
						<FormControl pb={5}>
							{!_.isUndefined(lists)  ? (
								<Box>
									<FormControl.Label>Choose a List</FormControl.Label>
									<Select selectedValue={listId} onValueChange={(itemValue) => {setListId(itemValue)}}>
										{lists.map((item, index) => {
											return (<Select.List value={item.id} label={item.title} />);
										})}
									</Select>
									<HStack space={3} alignItems="center" pt={2}>
										<Text>or</Text>
										<Button size="sm" onPress={() => {
											setShowUseExistingModal(false)
											setShowCreateNewModal(true)
										}}>Create a new list</Button>
									</HStack>
								</Box>
							) : (
								<Text>You have no lists yet</Text>
							)}
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="outline" onPress={() => setShowUseExistingModal(false)}>Cancel</Button>
							{!_.isEmpty(lists) ? (<Button
								isLoading={loading}
								onPress={async () => {
									setLoading(true);
									await addTitlesToList(listId, item, libraryUrl).then(res => {
										setLoading(false);
										setShowUseExistingModal(false)
									});
								}}>Save to list</Button>) : (<Button onPress={() => {
								setShowUseExistingModal(false)
								setShowCreateNewModal(true)
							}}>Create a new list</Button>) }

						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
			<Modal isOpen={showCreateNewModal} onClose={() => setShowCreateNewModal(false)} size="full">
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="sm">Create a new list from item</Heading>
					</Modal.Header>
					<Modal.Body>
						<FormControl pb={5}>
							<FormControl.Label>Title</FormControl.Label>
							<Input
								id="title"
								onChangeText={text => setTitle(text)}
								returnKeyType="next"
							/>
						</FormControl>
						<FormControl pb={5}>
							<FormControl.Label>Description</FormControl.Label>
							<TextArea
								id="description"
								onChangeText={text => setDescription(text)}
								returnKeyType="next"
							/>
						</FormControl>
						<FormControl>
							<FormControl.Label>Access</FormControl.Label>
							<Radio.Group defaultValue="1">
								<Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px" onChange={nextValue => {setAccess(nextValue)}}>
									<Radio value="1" my={1}>Private</Radio>
									<Radio value="0" my={1}>Public</Radio>
								</Stack>
							</Radio.Group>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="outline" onPress={() => setShowCreateNewModal(false)}>Cancel</Button>
							<Button
								isLoading={loading}
								onPress={async () => {
									await createListFromTitle(title, description, access, item, libraryUrl).then(res =>{
										let status = "success"
										if(!res.success) {
											status = "danger"
										}
										setLoading(false)
										popAlert("List created from item", res.message, status);
									});
									setShowCreateNewModal(false)
								}}
							>Create List</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</Center>
	);
}

export default AddToListFromItem;