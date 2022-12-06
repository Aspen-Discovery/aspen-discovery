import {
  Badge,
  Box,
  Center,
  FlatList,
  Pressable,
  Text,
  HStack,
  VStack,
} from "native-base";
import React, { Component } from "react";
import { SafeAreaView } from "react-native";

// custom components and helper files
import { loadingSpinner } from "../../../components/loadingSpinner";
import { userContext } from "../../../context/user";
import { getSavedSearches } from "../../../util/loadPatron";

export default class MySavedSearches extends Component {
  constructor() {
    super();
    this.state = {
      isLoading: true,
      hasError: false,
      error: null,
      libraryUrl: "",
      searches: [],
    };
    this._isMounted = false;
  }

  _fetchSearches = async () => {
    const { route } = this.props;
    const libraryUrl = this.context.library.baseUrl;

    await getSavedSearches(libraryUrl).then((response) =>
      this.setState({
        searches: response,
      })
    );
  };

  componentDidMount = async () => {
    this._isMounted = true;

    this._isMounted &&
      (await this._fetchSearches().then((r) => {
        this.setState({
          isLoading: false,
        });
      }));
  };

  componentWillUnmount() {
    this._isMounted = false;
  }

  // renders the items on the screen
  renderSearch = (item, libraryUrl) => {
    let hasNewResults = 0;
    if (typeof item.hasNewResults !== "undefined") {
      hasNewResults = item.hasNewResults;
    }

    return (
      <Pressable
        onPress={() => {
          this.openList(item.id, item, libraryUrl);
        }}
        borderBottomWidth="1"
        _dark={{ borderColor: "gray.600" }}
        borderColor="coolGray.200"
        pl="1"
        pr="1"
        py="2"
      >
        <HStack space={3} justifyContent="flex-start">
          <VStack space={1}>
            {/*<Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain" />*/}
          </VStack>
          <VStack space={1} justifyContent="space-between" maxW="80%">
            <Box>
              <Text bold fontSize="md">
                {item.title}{" "}
                {hasNewResults == 1 ? (
                  <Badge mb="-0.5" colorScheme="warning">
                    Updated
                  </Badge>
                ) : null}
              </Text>
              <Text fontSize="9px" italic>
                Created on {item.created}
              </Text>
            </Box>
          </VStack>
        </HStack>
      </Pressable>
    );
  };

  openList = (id, item, libraryUrl) => {
    this.props.navigation.navigate("AccountScreenTab", {
      screen: "SavedSearch",
      params: {
        id,
        details: item,
        title: item.title,
        libraryUrl,
      },
    });
  };

  _listEmptyComponent = () => {
    return (
      <Center mt={5} mb={5}>
        <Text bold fontSize="lg">
          You have no saved searches.
        </Text>
      </Center>
    );
  };

  static contextType = userContext;

  render() {
    const { searches } = this.state;
    const user = this.context.user;
    const location = this.context.location;
    const library = this.context.library;

    if (this.state.isLoading) {
      return loadingSpinner();
    }

    return (
      <SafeAreaView style={{ flex: 1 }}>
        <Box safeArea={2} h="100%">
          <FlatList
            data={searches}
            ListEmptyComponent={this._listEmptyComponent()}
            renderItem={({ item }) => this.renderSearch(item, library.baseUrl)}
            keyExtractor={(item, index) => index.toString()}
          />
        </Box>
      </SafeAreaView>
    );
  }
}