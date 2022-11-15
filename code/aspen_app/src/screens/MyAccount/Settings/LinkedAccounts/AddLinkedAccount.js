import React, {useState, useRef} from "react";
import {Button, Center, Modal, FormControl, Input, Icon} from "native-base";
import {MaterialCommunityIcons} from "@expo/vector-icons";
import {addLinkedAccount} from "../../../../util/accountActions";
import {translate} from "../../../../translations/translations";

// custom components and helper files

const AddLinkedAccount = (props) => {
	const {_updateLinkedAccounts} = props;
	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);
	const [showPassword, setShowPassword] = useState(false);
	const [user, setUser] = useState('');
	const [password, setPassword] = useState('');

	const passwordRef = useRef();

	return (
		<Center>
			<Button onPress={() => setShowModal(true)}>Add an Account</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full" avoidKeyboard>
				<Modal.Content maxWidth="95%">
					<Modal.CloseButton/>
					<Modal.Header>{translate('linked_accounts.account_to_manage')}</Modal.Header>
					<Modal.Body>
						<FormControl>
							<FormControl.Label>{translate('linked_accounts.username')}</FormControl.Label>
							<Input
								onChangeText={text => setUser(text)}
								autoCorrect={false}
								autoCapitalize="none"
								id="username"
								returnKeyType="next"
								textContentType="username"
								required
								size="lg"
								onSubmitEditing={() => {
									passwordRef.current.focus();
								}}
								blurOnSubmit={false}
							/>
						</FormControl>
						<FormControl mt={3}>
							<FormControl.Label>{translate('linked_accounts.password')}</FormControl.Label>
							<Input
								onChangeText={text => setPassword(text)}
								autoCorrect={false}
								autoCapitalize="none"
								id="password"
								returnKeyType="next"
								textContentType="password"
								required
								size="lg"
								type={showPassword ? "text" : "password"}
								ref={passwordRef}
								InputRightElement={<Icon as={<MaterialCommunityIcons name={showPassword ? "eye" : "eye-off"}/>} size="sm" w="1/6" h="full" mr={1} onPress={() => setShowPassword(!showPassword)}/>}
							/>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button variant="ghost" onPress={() => setShowModal(false)}>Close</Button>
							<Button
								isLoading={loading}
								isLoadingText="Adding..."
								onPress={
									async () => {
										setLoading(true);
										await addLinkedAccount(user, password, props.libraryUrl).then(r => {
											setShowModal(false);
											setLoading(false);
											_updateLinkedAccounts();
										})
									}
								}>{translate('linked_accounts.add')}</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</Center>
	)
}

export default AddLinkedAccount;