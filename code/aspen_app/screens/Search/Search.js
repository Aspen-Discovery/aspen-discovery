import React, {Component} from "react";
import {Box, Button, Center, FlatList, FormControl, Input, Text} from "native-base";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../util/translations';
import {loadingSpinner} from "../../components/loadingSpinner";

export default class Search extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			searchTerm: "",
			quickSearches: null,
		};
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		if(typeof global.quickSearches !== "undefined") {
			this.setState({
				quickSearches: global.quickSearches,
			});
		}
	};

	initiateSearch = async () => {
		const {searchTerm} = this.state;
		const { navigation } = this.props;
		navigation.navigate("SearchResults", {
			searchTerm: searchTerm
		});
	};

	renderItem = (item) => {
		const { navigation } = this.props;
		return (
			<Button
				mb={3}
				onPress={() =>
					navigation.navigate("SearchResults", {
						searchTerm: item.searchTerm,
					})
				}
			>
				{item.label}
			</Button>
		);
	};

	clearText = () => {
		this.setState({searchTerm: ""});
	};

	render() {
		const quickSearches = this.state.quickSearches;
		console.log(quickSearches);

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<Box safeArea={5}>
				<FormControl>
					<Input
						variant="filled"
						autoCapitalize="none"
						onChangeText={(searchTerm) => this.setState({searchTerm})}
						status="info"
						placeholder={translate('search.title')}
						clearButtonMode="always"
						onSubmitEditing={this.initiateSearch}
						value={this.state.searchTerm}
						size="xl"
					/>
				</FormControl>

				{quickSearches ?
				<Center>
					<Text mt={8} mb={2} fontSize="xl" bold>
						{translate('search.quick_search_title')}
					</Text>
				</Center>
				: null }
					<FlatList
						data={_.sortBy(quickSearches, ['weight', 'label'])}
						renderItem={({item}) => this.renderItem(item)}
					/>
			</Box>
		);
	}
}