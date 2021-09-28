import React, { Component } from "react";
import { HStack, Center, Spinner, Button, Flex, Box, Text, Icon, Input, FormControl, FlatList } from "native-base";
import * as SecureStore from 'expo-secure-store';

export default class Search extends Component {
	constructor() {
		super();

		this.state = {
			isLoading: true,
			searchTerm: "",
		};
	}

	componentDidMount = async () => {

		const url = global.libraryUrl + "/app/aspenSearchLists.php?library=" + global.solrScope;
		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.list,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from <" + url + "> in Search");
				console.log("Error: " + error)
			});
	};

	initiateSearch = async () => {
		const { searchTerm } = this.state;
		this.props.navigation.navigate("SearchResults", {
			searchTerm: searchTerm,
			searchType: this.state.searchType,
		});
	};

	renderItem = (item) => {
		const { navigate } = this.props.navigation;
		return (
			<Button
				mb={3}
				onPress={() =>
					navigate("SearchResults", {
						searchTerm: item.SearchTerm,
						searchType: this.state.searchType,
					})
				}
			>
				{item.SearchName}
			</Button>
		);
	};

	clearText = () => {
		this.setState({ searchTerm: "" });
	};

	getHeader = () => {
		return (
			<FormControl>
				<Input
					variant="filled"
					autoCapitalize="none"
					onChangeText={(searchTerm) => this.setState({ searchTerm })}
					status="info"
					placeholder="Search"
					clearButtonMode="always"
					onSubmitEditing={this.initiateSearch}
					value={this.state.searchTerm}
					size="xl"
				/>
				<Center>
					<Text mt={8} mb={2} fontSize="xl" bold>
						Quick Searches:
					</Text>
				</Center>
			</FormControl>
		);
	};

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Fetching..." />
					</HStack>
				</Center>
			);
		}

		return (
			<>
				<Box safeArea={5}>
					<FlatList
						data={this.state.data}
						renderItem={({ item }) => this.renderItem(item)}
						keyExtractor={(item, index) => index.toString()}
						ListHeaderComponent={this.getHeader}
					/>
				</Box>
			</>
		);
	}
}
