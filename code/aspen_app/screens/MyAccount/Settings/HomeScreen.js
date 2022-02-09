import React, {Component} from "react";
import {Box, FlatList, HStack, Switch, Text, VStack, Pressable} from "native-base";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../../util/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {loadError} from "../../../components/loadError";
import {getActiveBrowseCategories} from "../../../util/loadLibrary";
import {getHiddenBrowseCategories} from "../../../util/loadPatron";
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
		};
	}

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this._fetchBrowseCategoryList();
		await this._fetchHiddenBrowseCategories();

	};

	_fetchBrowseCategoryList = async () => {

		this.setState({
			isLoading: true,
		});

		await getActiveBrowseCategories().then(response => {
			if(response === "TIMEOUT_ERROR") {
				this.setState({
					hasError: true,
					error: translate('error.timeout'),
					isLoading: false,
				});
			} else {
				this.setState({
					browseCategories: response,
					hasError: false,
					error: null,
					isLoading: false,
				});
			}
		});
	}

	_fetchHiddenBrowseCategories = async () => {
		this.setState({
			isLoading: true,
		});

		await getHiddenBrowseCategories().then(response => {
			if(response === "TIMEOUT_ERROR") {
				this.setState({
					hasError: true,
					error: translate('error.timeout'),
					isLoading: false,
				});
			} else {
				const hiddenCategories = response;
				const browseCategories = this.state.browseCategories;
				if (typeof hiddenCategories === 'undefined') {
					var allCategories = browseCategories;
				} else {
					var allCategories = hiddenCategories.concat(browseCategories);
				}

				var uniqueCategories = _.uniqBy(allCategories, 'key');

				this.setState({
					hiddenCategories: response,
					allCategories: uniqueCategories,
					hasError: false,
					error: null,
					isLoading: false,
				});
			}
		});
	}

	// Update the status of the browse category when the toggle switch is flipped
	updateToggle = async (item) => {
		if (item.isHidden === true) {
			await showBrowseCategory(item.key);
		} else {
			await dismissBrowseCategory(item.key);
		}
		await this._fetchBrowseCategoryList();
		await this._fetchHiddenBrowseCategories();
	};


	renderNativeItem = (item) => {
		return (
			<Box borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
					<Text isTruncated bold maxW="80%" fontSize={{base: "lg", lg: "xl"}}>{item.title}</Text>
					{item.isHidden ? <Switch size={{base: "md", lg: "lg"}} onToggle={() => this.updateToggle(item)}/> :
						<Switch size={{base: "md", lg: "lg"}} onToggle={() => this.updateToggle(item)} isChecked/>}
				</HStack>
			</Box>
		);
	};

	render() {

		const allCategories = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof allCategories === 'undefined') {
			return (loadError("No categories"));
		}

		return (
			<Box>
				<FlatList
					data={this.state.allCategories}
					renderItem={({item}) => this.renderNativeItem(item)}
				/>
			</Box>
		);
	}
}
