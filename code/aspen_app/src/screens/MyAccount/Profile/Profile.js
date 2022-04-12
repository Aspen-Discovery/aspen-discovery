import React, {Component} from "react";
import {Box, Divider} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import {userContext} from "../../../context/user";
import Profile_Identity from "./Identity";
import Profile_MainAddress from "./MainAddress";
import Profile_ContactInformation from "./ContactInformation";

export default class Profile extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			hasUpdated: false,
			isRefreshing: false,
		};

	}

	componentDidMount = async() => {
		this.setState({
			isLoading: false,
		});
	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		return (
			<Box flex={1} safeArea={5}>
				<Profile_Identity
					firstName={user.firstname}
					lastName={user.lastname}
				/>
				<Divider />
				<Profile_MainAddress
					address={user.address1}
					city={user.city}
					state={user.state}
					zipCode={user.zip}
				/>
				<Divider />
				<Profile_ContactInformation
					email={user.email}
					phone={user.phone}
				/>
			</Box>
		)
	}
}