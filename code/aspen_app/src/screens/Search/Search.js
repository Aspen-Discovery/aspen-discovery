import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, Button, Center, FlatList, FormControl, Input, Text} from "native-base";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";

export default class Search extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			library: [],
			searchTerm: "",
		};
	}

	loadLibrary = async () => {
		const tmp = await AsyncStorage.getItem('@libraryInfo');
		const profile = JSON.parse(tmp);
		this.setState({
			library: profile,
			isLoading: false,
		})
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this.loadLibrary();
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
		const {library} = this.state;

		const quickSearchNum = _.size(library.quickSearches);

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

				{quickSearchNum > 0 ?
				<Center>
					<Text mt={8} mb={2} fontSize="xl" bold>
						{translate('search.quick_search_title')}
					</Text>
				</Center>
				: null }
					<FlatList
						data={_.sortBy(library.quickSearches, ['weight', 'label'])}
						renderItem={({item}) => this.renderItem(item)}
					/>
			</Box>
		);
	}
}