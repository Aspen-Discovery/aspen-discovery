import React, {useState} from "react";
import {Button, Center, Modal, FormControl, Select, Heading, CheckIcon } from "native-base";
import {completeAction} from "./Record";

const SelectLinkedAccount = (props) => {
	const {user, linkedAccounts, id, action, libraryUrl, showAlert} = props;
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = useState(false);

	let [activeAccount, setActiveAccount] = React.useState(user.id);
	const availableAccounts = Object.values(linkedAccounts);

	return (
		<Center>
			<Button
				onPress={() => setShowModal(true)}
			>
				{props.title}</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full">
				<Modal.Content maxWidth="90%" bg="white" _dark={{bg: "coolGray.800"}}>
					<Modal.CloseButton />
					<Modal.Header>
						<Heading size="md">Checkout Options</Heading>
					</Modal.Header>
					<Modal.Body>
						<FormControl pb={5}>
							<FormControl.Label>Checkout to account</FormControl.Label>
							<Select
								name="linkedAccount"
								selectedValue={activeAccount}
								minWidth="200"
								accessibilityLabel="Select an account to place hold for"
								_selectedItem={{
									bg: "tertiary.300",
									endIcon: <CheckIcon size="5" />
								}}
								mt={1}
								mb={3}
								onValueChange={itemValue => setActiveAccount(itemValue)}
							>
								<Select.Item label={user.displayName} value={user.id}/>
								{availableAccounts.map((item, index) => {
									return <Select.Item label={item.displayName} value={item.id}/>;
								})}
							</Select>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="outline" onPress={() => setShowModal(false)}>Cancel</Button>
							<Button
								isLoading={loading}
								isLoadingText="Checking out..."
								onPress={async () => {
									setLoading(true);
									await completeAction(id, action, activeAccount, null, null, null, libraryUrl).then(response =>{
										showAlert(response);
										setLoading(false);
									});
									setShowModal(false)
								}}
							>Checkout Title</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</Center>
	)
}

export default SelectLinkedAccount;