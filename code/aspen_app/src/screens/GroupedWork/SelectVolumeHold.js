import React, {useState} from "react";
import {Button, FormControl, Modal, Select, CheckIcon} from "native-base";
import {translate} from "../../translations/translations";
import {completeAction} from "./Record";

const SelectVolumeHold = (props) => {

	const {label, action, record, patron, showAlert, libraryUrl, linkedAccounts, linkedAccountsCount, user, volumes, updateProfile} = props;
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = useState(false);
	let [volume, setVolume] = React.useState("");
	let [activeAccount, setActiveAccount] = React.useState(user.id);

	const availableAccounts = Object.values(linkedAccounts);

	return (
		<>
			<Button onPress={() => setShowModal(true)} colorScheme="primary" size="md">{label}</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
				<Modal.Content>
					<Modal.CloseButton/>
					<Modal.Header>{label}</Modal.Header>
					<Modal.Body>
						{linkedAccountsCount > 0 ? (
							<FormControl>
								<FormControl.Label>Place hold for account</FormControl.Label>
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
									<Select.Item label={user.displayName} value={patron}/>
									{availableAccounts.map((item, index) => {
										return <Select.Item label={item.displayName} value={item.id}/>;
									})}
								</Select>
							</FormControl>
						) : null}
						<FormControl>
							<FormControl.Label>Select volume</FormControl.Label>
							<Select
								name="volumeForHold"
								selectedValue={volume}
								minWidth="200"
								accessibilityLabel="Select a Volume"
								_selectedItem={{
									bg: "tertiary.300",
									endIcon: <CheckIcon size="5"/>
								}}
								mt={1}
								mb={2}
								onValueChange={itemValue => setVolume(itemValue)}
							>
								{volumes.map((item, index) => {
									return <Select.Item label={item.displayLabel} value={item.volumeId} />;
								})}
							</Select>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group space={2} size="md">
							<Button colorScheme="muted" variant="outline"
							        onPress={() => {
								        setShowModal(false)
								        setLoading(false)
							        }
							}>{translate('general.close_window')}</Button>
							<Button
								isLoading={loading}
								isLoadingText="Placing hold..."
								onPress={async () => {
									setLoading(true);
									await completeAction(record, action, activeAccount, "", "", "", libraryUrl, volume).then(response => {
										updateProfile();
										setLoading(false);
										setShowModal(false);
										showAlert(response);
									});
									setShowModal(false);
								}}
							>
								{label}
							</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</>
	)
}

export default SelectVolumeHold;