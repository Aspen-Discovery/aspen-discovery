import React, {useState} from "react";
import {Button, FormControl, Modal, Radio} from "native-base";
import {translate} from "../../translations/translations";
import {completeAction} from "./Record";

const SelectPickupLocation = (props) => {

	const {locations, label, action, record, patron, showAlert, libraryUrl} = props;
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = useState(false);
	let [value, setValue] = React.useState("");

	return (
		<>
			<Button onPress={() => setShowModal(true)} colorScheme="primary" size="md">{label}</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
				<Modal.Content>
					<Modal.CloseButton/>
					<Modal.Header>{label}</Modal.Header>
					<Modal.Body>
						<FormControl>
							<FormControl.Label>{translate('pickup_locations.text')}</FormControl.Label>
							<Radio.Group
								name="pickupLocations"
								value={value}
								onChange={(nextValue) => {
									setValue(nextValue);
								}}
								mt="1"
							>
								{locations.map((item, index) => {
									return <Radio value={item.code} my={1}>{item.name}</Radio>;
								})}
							</Radio.Group>
						</FormControl>
					</Modal.Body>
					<Modal.Footer>
						<Button.Group space={2} size="md">
							<Button colorScheme="muted" variant="outline"
							        onPress={() => setShowModal(false)}>{translate('general.close_window')}</Button>
							<Button
								isLoading={loading}
								isLoadingText="Placing hold..."
								onPress={async () => {
									setLoading(true);
									await completeAction(record, action, patron, "", "", value, libraryUrl).then(response => {
										setLoading(false);
										setShowModal(false);
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

export default SelectPickupLocation;