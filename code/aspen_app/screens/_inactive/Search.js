import React, { Component } from "react";
import { HStack, Center, Spinner, Button, Flex, Box, Heading, Text, Icon, Input, FormControl, FlatList } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import { create, CancelToken } from 'apisauce';

export default class Search extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			searchTerm: "",
            defaultSearches: [
                {
                    label: "New York Times",
                    term: "new york times",
                },
                {
                    label: "Autobiography",
                    term: "autobiography",
                },
                {
                    label: "Super Heroes",
                    term: "super hero",
                },
                {
                    label: "US History",
                    term: "us history",
                },
            ]
		};
	}

	componentDidMount = async () => {
        this.setState({ isLoading: false });
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

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		}

		return (
				<Box safeArea={5}>
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
                    <FlatList
                        data={this.state.defaultSearches}
                        renderItem={({ item }) => this.renderItem(item)}
                        keyExtractor={(item, index) => index.toString()}
                    />
				</Box>
		);
	}
}