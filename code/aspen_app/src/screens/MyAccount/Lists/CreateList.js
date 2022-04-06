import React, {useState} from "react";
import {Button, Center, Modal, Stack, Text, Icon, FormControl, Input, TextArea, Heading, Radio } from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import {createList} from "../../../util/loadPatron";
import {popAlert} from "../../../components/loadError";

const CreateList = (props) => {
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = useState(false);

	const [title, setTitle] = React.useState('');
	const [description, setDescription] = React.useState('');
	const [access, setAccess] = React.useState(false);

	return (
		<Center>
			<Button
				onPress={() => setShowModal(true)}
				size="sm"
				leftIcon={<Icon as={MaterialIcons} name="add" size="xs" mr="-1"/>}
			>
				Create a New List</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full">
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="md">Create a New List</Heading>
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
							<Button variant="outline" onPress={() => setShowModal(false)}>Cancel</Button>
							<Button
								isLoading={loading}
								onPress={async () => {
									await createList(title, description, access, props.libraryUrl).then(res =>{
										let status = "success"
										if(!res.success) {
											status = "danger"
										}
										setLoading(false)
										popAlert("List created", res.message, status);
									});
									setShowModal(false)
								}}
							>Create List</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</Center>
	);
}

export default CreateList;