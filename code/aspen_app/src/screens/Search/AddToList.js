import React, {useState} from "react";
import {Button, Center, Modal, Box, Text, Icon, FormControl, Input, Radio, TextArea, Heading, Select, HStack, Stack, IconButton } from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import {addTitlesToList, createListFromTitle, getLists} from "../../util/loadPatron";
import _ from "lodash";
import { translate } from '../../translations/translations';

export const AddToList = (props) => {
	const { item, libraryUrl, updateLastListUsed, lastListUsed} = props;
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
					//console.log(props.lastListUsed);
					await getLists(libraryUrl).then(response => {
						setLists(response);
						setShowUseExistingModal(true);
					});
				}
			} colorScheme="tertiary" leftIcon={<Icon as={MaterialIcons} name="bookmark" size="xs" mr="-1"/>} size="sm" variant="ghost">
				{translate('lists.add_to_list')}</Button>
			<Modal isOpen={showUseExistingModal} onClose={() => setShowUseExistingModal(false)} size="full" avoidKeyboard>
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="sm">{translate('lists.add_to_list')}</Heading>
					</Modal.Header>
					<Modal.Body>
						<FormControl pb={5}>
							{!_.isUndefined(lists)  ? (
								<Box>
									<FormControl.Label>{translate('lists.choose_a_list')}</FormControl.Label>
									<Select selectedValue={listId} onValueChange={(itemValue) => {setListId(itemValue)}}>
										{lists.map((item, index) => {
											return (<Select.List value={item.id} label={item.title} />);
										})}
									</Select>
									<HStack space={3} alignItems="center" pt={2}>
										<Text>{translate('general.or')}</Text>
										<Button size="sm" onPress={() => {
											setShowUseExistingModal(false)
											setShowCreateNewModal(true)
										}}>{translate('lists.create_new_list')}</Button>
									</HStack>
								</Box>
							) : (
								<Text>{translate('lists.no_lists_yet')}</Text>
							)}
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="outline" onPress={() => setShowUseExistingModal(false)}>{translate('general.cancel')}</Button>
							{!_.isEmpty(lists) ? (<Button
								isLoading={loading}
								onPress={async () => {
									setLoading(true);
									await addTitlesToList(listId, item, libraryUrl).then(res => {
										setLoading(false);
										setShowUseExistingModal(false)
									});
									updateLastListUsed(listId);
								}}>{translate('lists.save_to_list')}</Button>) : (<Button onPress={() => {
									setShowUseExistingModal(false)
									setShowCreateNewModal(true)
							}}>{translate('lists.create_new_list')}</Button>) }

						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
			<Modal isOpen={showCreateNewModal} onClose={() => setShowCreateNewModal(false)} size="full" avoidKeyboard>
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="sm">{translate('lists.create_new_list_item')}</Heading>
					</Modal.Header>
					<Modal.Body>
						<FormControl pb={5}>
							<FormControl.Label>{translate('general.title')}</FormControl.Label>
							<Input
								id="title"
								onChangeText={text => setTitle(text)}
								returnKeyType="next"
							/>
						</FormControl>
						<FormControl pb={5}>
							<FormControl.Label>{translate('general.description')}</FormControl.Label>
							<TextArea
								id="description"
								onChangeText={text => setDescription(text)}
								returnKeyType="next"
							/>
						</FormControl>
						<FormControl>
							<FormControl.Label>{translate('general.access')}</FormControl.Label>
							<Radio.Group defaultValue="1">
								<Stack direction="row" alignItems="center" space={4} w="75%" maxW="300px" onChange={nextValue => {setAccess(nextValue)}}>
									<Radio value="1" my={1}>{translate('general.private')}</Radio>
									<Radio value="0" my={1}>{translate('general.public')}</Radio>
								</Stack>
							</Radio.Group>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="outline" onPress={() => setShowCreateNewModal(false)}>{translate('general.cancel')}</Button>
							<Button
								isLoading={loading}
								onPress={async () => {
									setLoading(true);
									await createListFromTitle(title, description, access, item, libraryUrl).then(res => {
										updateLastListUsed(res.listId);
										setLoading(false);
										setShowCreateNewModal(false);
									});
								}}
							>{translate('lists.create_list')}</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</Center>
	);
}

export default AddToList;