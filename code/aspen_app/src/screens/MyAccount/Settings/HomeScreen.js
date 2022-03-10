import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, FlatList, HStack, Switch, Text, VStack, Pressable} from "native-base";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {loadError} from "../../../components/loadError";
import {getActiveBrowseCategories} from "../../../util/loadLibrary";
import {getHiddenBrowseCategories, getPatronBrowseCategories} from "../../../util/loadPatron";
import {dismissBrowseCategory, showBrowseCategory} from "../../../util/accountActions";

export default class Settings_HomeScreen extends Component {

	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			hasUpdated: false,
			isRefreshing: false,
			browseCategories: [],
			user: [],
			patronCategories: [],
		};
		getPatronBrowseCategories();
		this.loadUser();
		this.loadBrowseCategories();
	}

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this.loadUser();
		await this.loadBrowseCategories();

		this.interval = setInterval(() => {
			this.loadBrowseCategories();
		}, 1000)

		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}


	loadUser = async () => {
		const tmp = await AsyncStorage.getItem('@patronProfile');
		const profile = JSON.parse(tmp);
		this.setState({
			user: profile,
			isLoading: false,
		})
	}

	loadBrowseCategories = async () => {
		const tmp = await AsyncStorage.getItem('@patronBrowseCategories');
		const items = JSON.parse(tmp);
		this.setState({
			patronCategories: items,
			isLoading: false,
		})
	}

	// Update the status of the browse category when the toggle switch is flipped
	updateToggle = async (item) => {
		if (item.isHidden === true) {
			await showBrowseCategory(item.key);
		} else {
			await dismissBrowseCategory(item.key, this.state.user.id);
		}
	};


	renderNativeItem = (item) => {
		return (
			<Box borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
					<Text isTruncated bold maxW="80%" fontSize={{base: "lg", lg: "xl"}}>{item.title}</Text>
					{item.isHidden ? <Switch size={{base: "md", lg: "lg"}} onValueChange={() => this.updateToggle(item)}/> :
						<Switch size={{base: "md", lg: "lg"}} onValueChange={() => this.updateToggle(item)} defaultIsChecked/>}
				</HStack>
			</Box>
		);
	};

	render() {

		const {user, patronCategories} = this.state;

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (!patronCategories) {
			return (loadError("No categories"));
		}

		return (
			<Box>
				<FlatList
					data={patronCategories}
					renderItem={({item}) => this.renderNativeItem(item)}
				/>
			</Box>
		);
	}
}
