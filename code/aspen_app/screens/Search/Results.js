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

// custom components and helper files
import { translate } from '../../util/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { searchResults } from "../../util/search";

export default class Results extends Component {
	static navigationOptions = ({ navigation }) => ({
		title: typeof navigation.state.params === "undefined"  || typeof navigation.state.params.title === "undefined" ? translate('search.search_results') : navigation.state.params.title,
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

		await this._fetchResults();
		if(this.props.navigation.state.params.searchTerm != null) {
		    this.props.navigation.setParams({ title: translate('search.search_results_title') + " " + this.props.navigation.state.params.searchTerm });
		} else {
		    this.props.navigation.setParams({ title: translate('search.search_results') });
		}

	};

	_fetchResults = async () => {
	    const { page } = this.state;
        const searchTerm = encodeURI(this.props.navigation.state.params.searchTerm.replace(" ", "%20"));

        await searchResults(searchTerm, 25, page).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                if(response.data.result.success == true) {
                    this.setState((prevState, nextProps) => ({
                        data:
                            page === 1
                                ? Array.from(response.data.result.items)
                                : [...this.state.data, ...response.data.result.items],
                        isLoading: false,
                        isLoadingMore: false,
                        refreshing: false
                    }));
                } else {
                    this.setState({
                        hasError: true,
                        error: response.data.result.message,
                        isLoading: false,
                        isLoadingMore: false,
                        refreshing: false
                    });
                }
            }
        })

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
                        {translate('grouped_work.by')} {item.author}
                    </Text> : null }
                    <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
                        {item.itemList.map((item, index) => {
                            return <Badge colorScheme="tertiary" mt={1} variant="outline" rounded="4px">{item.name}</Badge>;
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
					<Heading>{translate('search.no_results')}</Heading>
					<Button onPress={() => this.props.navigation.goBack()}>{translate('search.new_search_button')}</Button>
				</VStack>
			</Center>
		);
	};

	_renderFooter = () => {
	    if(!this.state.isLoadingMore) return null;
	    return ( loadingSpinner() );
	}

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError) {
            return ( loadError(this.state.error, this._fetchResults) );
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

