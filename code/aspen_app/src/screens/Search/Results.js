import React, { Component } from "react";
import {
	Center,
	Button,
	Box,
	Badge,
	Text,
	FlatList,
	Heading,
	Stack,
	HStack,
	VStack,
	Avatar,
	Pressable
} from "native-base";
import { CommonActions } from '@react-navigation/native';

// custom components and helper files
import { translate } from '../../translations/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { searchResults } from "../../util/search";
import _ from "lodash";

export default class Results extends Component {
	constructor() {
		super();
		this.state = {
            isLoading: true,
            isLoadingMore: false,
            data: [],
            searchMessage: null,
            page: 1,
            hasError: false,
            error: null,
            refreshing: false,
            filtering: false,
			endOfResults: false,
			dataMessage: null
		};
	}

	componentDidMount = async () => {
		//const level      = this.props.navigation.state.params.level;
		//const format     = this.props.navigation.state.params.format;
		//const searchType = this.props.navigation.state.params.searchType;

		await this._fetchResults();

	};

	_fetchResults = async () => {
	    const { page } = this.state;
		const { navigation, route } = this.props;
		const givenSearch = route.params?.searchTerm ?? '%20';
        const searchTerm = givenSearch.replace(/" "/g, "%20");

        await searchResults(searchTerm, 100, page).then(response => {
            if(response.ok) {
                if(response.data.result.count > 0) {
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
	                if(page === 1 && response.data.result.count === 0) {
                    /* No search results were found */
                        this.setState({
                            hasError: true,
                            error: response.data.result.message,
                            isLoading: false,
                            isLoadingMore: false,
                            refreshing: false,
                            dataMessage: response.data.result.message,
                        });
                    } else {
                        /* Tried to fetch next page, but end of results */
                        this.setState({
                            isLoading: false,
                            isLoadingMore: false,
                            refreshing: false,
                            dataMessage: response.data.result.message,
	                        endOfResults: true,
                        });
                    }
                }
            }
        })

	}

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

	renderItem = (item) => {
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.onPressItem(item.key)}>
				<HStack space={3}>
					<Avatar source={{ uri: item.image }} alt={item.title} borderRadius="md" size={{base: "80px", lg: "120px"}} />
					<VStack maxW="80%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800">{translate('grouped_work.by')} {item.author}</Text> : null }
						<Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{item.itemList.map((item, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>{item.name}</Badge>;
							})}
						</Stack>
					</VStack>
				</HStack>
			</Pressable>
		)
	}

	// handles the on press action
	onPressItem = (item) => {
		const { navigation, route } = this.props;
		navigation.dispatch(CommonActions.navigate({
			name: 'GroupedWork',
			params: {
				item: item,
			},
		}));
	};

    // this one shouldn't probably ever load with the catches in the render, but just in case
	_listEmptyComponent = () => {
		const { navigation, route } = this.props;
		return (
            <Center flex={1}>
                <Heading pt={5}>{translate('search.no_results')}</Heading>
                <Text bold w="75%" textAlign="center">{route.params?.searchTerm}</Text>
                <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
            </Center>
		);
	};

	_renderFooter = () => {
	    if(!this.state.isLoadingMore) return null;
	    return ( loadingSpinner() );
	}

	render() {
		const { navigation, route } = this.props;

		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError && !this.state.dataMessage) {
            return ( loadError(this.state.error, this._fetchResults) );
		}

        if (this.state.hasError && this.state.dataMessage) {
            return (
                <Center flex={1}>
                    <Heading pt={5}>{translate('search.no_results')}</Heading>
                    <Text bold w="75%" textAlign="center">{route.params?.searchTerm}</Text>
                    <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
                </Center>
            );
        }

		return (
			<Box>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderItem(item)}
					keyExtractor={(item) => item.key}
					ListFooterComponent={this._renderFooter}
					onEndReached={!this.state.dataMessage ? this._handleLoadMore : null} // only try to load more if no message has been set
					onEndReachedThreshold={.5}
					initialNumToRender={25}
				/>
			</Box>
		);
	}
}

