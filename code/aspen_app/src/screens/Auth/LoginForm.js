import React, {useRef} from 'react';
import {Button, Center, FormControl, Icon, Input} from 'native-base';
import {Ionicons} from '@expo/vector-icons';

// custom components and helper files
import {translate} from '../../translations/translations';
import {AuthContext} from '../../components/navigation';

export const GetLoginForm = (props) => {
	const [loading, setLoading] = React.useState(false);

	// securely set and store key:value pairs
	const [valueUser, onChangeValueUser] = React.useState('');
	const [valueSecret, onChangeValueSecret] = React.useState('');

	// show:hide data from password field
	const [show, setShow] = React.useState(false);
	const handleClick = () => setShow(!show);

	// make ref to move the user to next input field
	const passwordRef = useRef();
	const {signIn} = React.useContext(AuthContext);
	const libraryUrl = props.libraryUrl;
	const patronsLibrary = props.patronsLibrary;

	return (
			<>
				<FormControl>
					<FormControl.Label
							_text={{
								fontSize: 'sm',
								fontWeight: 600,
							}}
					>
						{translate('login.username')}
					</FormControl.Label>
					<Input
							autoCapitalize="none"
							size="xl"
							autoCorrect={false}
							variant="filled"
							id="barcode"
							onChangeText={text => onChangeValueUser(text)}
							returnKeyType="next"
							textContentType="username"
							required
							onSubmitEditing={() => {
								passwordRef.current.focus();
							}}
							blurOnSubmit={false}
					/>
				</FormControl>
				<FormControl mt={3}>
					<FormControl.Label
							_text={{
								fontSize: 'sm',
								fontWeight: 600,
							}}
					>
						{translate('login.password')}
					</FormControl.Label>
					<Input
							variant="filled"
							size="xl"
							type={show ? 'text' : 'password'}
							returnKeyType="go"
							textContentType="password"
							ref={passwordRef}
							InputRightElement={
								<Icon
										as={<Ionicons name={show ? 'eye-outline' : 'eye-off-outline'}/>}
										size="md"
										ml={1}
										mr={3}
										onPress={handleClick}
										roundedLeft={0}
										roundedRight="md"
								/>
							}
							onChangeText={text => onChangeValueSecret(text)}
							onSubmitEditing={(event) => {
								setLoading(true);
								signIn({valueUser, valueSecret, libraryUrl, patronsLibrary});
								setTimeout(
										function() {
											setLoading(false);
										}.bind(this), 1500,
								);
							}}
							required
					/>
				</FormControl>

				<Center>
					<Button
							mt={3}
							size="md"
							color="#30373b"
							isLoading={loading}
							isLoadingText="Logging in..."
							onPress={() => {
								setLoading(true);
								signIn({valueUser, valueSecret, libraryUrl, patronsLibrary});
								setTimeout(
										function() {
											setLoading(false);
										}.bind(this), 1500,
								);
							}}
					>
						{translate('general.login')}
					</Button>
				</Center>
			</>
	);
};