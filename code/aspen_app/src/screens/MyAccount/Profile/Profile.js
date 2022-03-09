import React, {Component} from "react";
import {Box, Divider} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
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
			user: [{
				firstname: "",
				lastname: "",
				address1: "",
				city: "",
				state: "",
				zip: "",
				email: "",
				phone: ""
			}],
		};

	}

	loadUser = async () => {
		const tmp = await AsyncStorage.getItem('@patronProfile');
		const profile = JSON.parse(tmp);
		this.setState({
			user: profile,
			isLoading: false,
		})
	}

	componentDidMount = async() => {
		this.setState({
			isLoading: false,
		});

		await this.loadUser();

		const interval = setInterval(() => {
			this.loadUser()
		}, 5000)

		return () => clearInterval(interval)
	}


	render() {
		return (
			<Box flex={1} safeArea={5}>
				<Profile_Identity
					firstName={this.state.user.firstname}
					lastName={this.state.user.lastname}
				/>
				<Divider />
				<Profile_MainAddress
					address={this.state.user.address1}
					city={this.state.user.city}
					state={this.state.user.state}
					zipCode={this.state.user.zip}
				/>
				<Divider />
				<Profile_ContactInformation
					email={this.state.user.email}
					phone={this.state.user.phone}
				/>
			</Box>
		)
	}
}