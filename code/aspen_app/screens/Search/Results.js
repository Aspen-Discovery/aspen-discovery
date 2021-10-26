import React, { Component } from "react";
import { ListItem } from "react-native-elements";
import {
	Alert,
	Container,
	HStack,
	VStack,
	Center,
	Spinner,
	Button,
	Divider,
	Flex,
	Box,
	Badge,
	Text,
	Icon,
	ChevronRightIcon,
	Input,
	FormControl,
	FlatList,
	Heading,
	Avatar,
	Stack,
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import { MaterialCommunityIcons, Ionicons, MaterialIcons } from "@expo/vector-icons";

export default class Results extends Component {
	static navigationOptions = ({ navigation }) => ({
		title: typeof navigation.state.params === "undefined"  || typeof navigation.state.params.title === "undefined" ? "Results" : navigation.state.params.title,
	});

	constructor() {
		super();
		this.state = {
            isLoading: true,
            isLoadingMore: false,
            data: [],
            page: 1,
            hasError: false,
            error: null,
            refreshing: false,
            filtering: false,
		};
	}

	componentDidMount = async () => {
		//const level      = this.props.navigation.state.params.level;
		//const format     = this.props.navigation.state.params.format;
		//const searchType = this.props.navigation.state.params.searchType;

		this._fetchResults();
        console.log("Search term: " + this.props.navigation.state.params.searchTerm);
		if(this.props.navigation.state.params.searchTerm != "%20") {
		    this.props.navigation.setParams({ title: "Results for " + this.props.navigation.state.params.searchTerm });
		} else {
		    this.props.navigation.setParams({ title: "Search Results" });
		}

	};

	_fetchResults = () => {
	    const { page } = this.state;
        const searchTerm = encodeURI(this.props.navigation.state.params.searchTerm.replace(" ", "%20"));

        fetch(global.libraryUrl + "/app/aspenSearchResults.php?library=" + global.solrScope + "&lida=true" + "&searchTerm=" + searchTerm + "&page=" + page)
            .then((res) => res.json())
            .then((res) => {
                this.setState((prevState, nextProps) => ({
                    data:
                        page === 1
                            ? Array.from(res.Items)
                            : [...this.state.data, ...res.Items],
                    isLoading: false,
                    isLoadingMore: false,
                    refreshing: false
                }));
            })
            .catch((error) => {
				console.log("Unable to fetch data from: <" + global.aspenSearchResults + "&searchTerm=" + searchTerm + "&page=" + page + "> in _fetchResults");
				this.setState({
					error: "Unable to fetch from _fetchResults()",
					isLoading: false,
					isLoadingMore: false,
					hasError: true,
				});
            });

	}

	_handleRefresh = () => {
	    this.setState(
	    {
	        page: 1,
	        refreshing: true
	    },
	    () => {
	        this._fetchResults();
	    }
	    );
	};

	_handleLoadMore = () => {
	    this.setState(
	        (prevState, nextProps) => ({
	            page: prevState.page + 1,
	            isLoadingMore: true
	        }),
	        () => {
	            this._fetchResults();
	        }
	    )
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
        return (
            <ListItem bottomDivider onPress={() => this.onPressItem(item.key)}>
                <Avatar source={{ uri: item.image }} size={{ base: '80px', lg: '120px'}} alt={item.title} />
                <ListItem.Content>
                    <Text fontSize={{ base: "md", lg: "xl"}} bold mb={0.5} color="coolGray.800">
                        {item.title}
                    </Text>
                    {item.author ? <Text fontSize={{ base: "xs", lg: "lg"}} color="coolGray.600">
                        By: {item.author}
                    </Text> : null }
                    <Stack mt={1.5} direction="row" flexWrap="wrap" space={1}>
                {item.itemList.map((item, index) => {
                        return <Badge colorScheme="tertiary" variant="outline" rounded="4px" mb={1}>{item.name}</Badge>;
                })}
                </Stack>
                </ListItem.Content>
                <ListItem.Chevron />
            </ListItem>
        );
	};

	// handles the on press action
	onPressItem = (item) => {
		this.props.navigation.navigate("GroupedWork", { item });
	};

	_listEmptyComponent = () => {
		return (
			<Center flex={1} justifyContent="center" alignItems="center" pt="70%">
				<VStack space={5}>
					<Heading>No results found.</Heading>
					<Button onPress={() => this.props.navigation.goBack()}>Try a new search</Button>
				</VStack>
			</Center>
		);
	};

	_renderFooter = () => {
	    if(!this.state.isLoadingMore) return null;

	    return(
	        <Center pt={5} pb={5}>
	            <Spinner accessibilityLabel="Searching..." />
	        </Center>
	    )

	}

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Searching..." />
					</HStack>
				</Center>
			);
		} else if (this.state.hasError) {
            return (
                <Center flex={1}>
                <HStack>
                     <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                     <Heading color="error.500" mb={2}>Error</Heading>
                </HStack>
                <Text bold w="75%" textAlign="center">There was an error loading results from the library. Please try again.</Text>
                 <Button
                     mt={5}
                     colorScheme="primary"
                     onPress={() => this._fetchResults()}
                     startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                 >
                     Reload
                 </Button>
                 <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {this.state.error}</Text>
                </Center>
            );
        }

		return (
			<Box>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderNativeItem(item)}
					keyExtractor={(item, index) => index.toString()}
					ListFooterComponent={this._renderFooter}
					onEndReached={this._handleLoadMore}
					onEndReachedThreshold={0.25}
					initialNumToRender={25}
					onRefresh={this._handleRefresh}
                    refreshing={this.state.refreshing}
				/>
			</Box>
		);
	}
}

