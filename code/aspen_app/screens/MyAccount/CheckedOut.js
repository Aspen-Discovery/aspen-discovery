import React, { Component, useState, useEffect } from "react";
import { View, Dimensions, Animated, StatusBar, RefreshControl } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Heading, Divider, Flex, Box, Text, Icon, Image, Menu, Pressable, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";

import { getCheckedOutItems } from '../../util/loadPatron';
import { isLoggedIn, renewCheckout, renewAllCheckouts, returnCheckout, viewOnlineItem, viewOverDriveItem } from '../../util/accountActions';

export default class CheckedOut extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
		};
	}

	componentDidMount = async () => {
        this.setState({
            data: global.checkedOutItems,
            isLoading: false,
        })

        if(!global.checkedOutItems){
            await this._fetchCheckouts();
        }
	};

	// grabs the items checked out to the account
	_fetchCheckouts = async () => {

	    this.setState({
	        isLoading: true,
	    });

	    const forceReload = this.state.isRefreshing;

        await getCheckedOutItems(forceReload).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                var thisMoment = moment().unix();
                this.setState({
                    data: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
	}

	// renders the items on the screen
	renderNativeItem = (item) => {
		return (
        <CheckedOutItem
          data={item}
          openWebsite={this.openWebsite}
        />
		);
	};

    openWebsite = async (url) => {

        this.setState({
            isLoading: true,
        });

        console.log("Trying to access " + url);

        console.log("Checking to see if user is logged in...");

        await isLoggedIn().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                this.setState({
                    isLoading: false,
                });
                console.log("User is logged in.");

                viewOverDriveItem(data.userId, formatId, data.overDriveId)
                    .then(res => {

                    })

                WebBrowser.openBrowserAsync(url)
                  .then(res => {
                    console.log(res);
                  })
                  .catch(async err => {
                    if (err.message === "Another WebBrowser is already being presented.") {

                      WebBrowser.dismissBrowser();
                      WebBrowser.openBrowserAsync(url)
                        .then(response => {
                          console.log(response);
                        })
                        .catch(async error => {
                          console.log("Unable to close previous browser session.");
                        });
                    } else {
                      console.log("Unable to open browser window.");
                    }
                  });
            }
        })

    }

	_listEmptyComponent = () => {
        return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					You have no items checked out.
				</Text>
			</Center>
		);
	};

	_onRefresh = () => {
	    this.setState({ isRefreshing: true }, () => {
            this._fetchCheckouts().then(() => {
                this.setState({ isRefreshing: false });
            });
	    });
	}


	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError) {
            return ( loadError(this.state.error, this._fetchCheckouts) );
		}

		return (
		<Box h="100%">
            <Center bg="white" pt={3} pb={3}>
                <Button
                    size="sm"
                    colorScheme="primary"
                    onPress={() => renewAllCheckouts()}
                    startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}
                >
                    Renew All
                </Button>
            </Center>
			<FlatList
				data={global.checkedOutItems}
				ListEmptyComponent={this._listEmptyComponent()}
				renderItem={({ item }) => this.renderNativeItem(item)}
				keyExtractor={(item) => item.recordId}
				refreshControl={
				    <RefreshControl
				        refreshing={this.state.isRefreshing}
				        onRefresh={this._onRefresh.bind(this)}
                    />
				}
			/>
        </Box>
		);

	}
}

function CheckedOutItem(props) {


    const { openWebsite, renewItem, data } = props;
    const { isOpen, onOpen, onClose } = useDisclose();
    const dueDate = moment.unix(data.dueDate);
    var itemDueOn = moment(dueDate).format("MMM D, YYYY");

    var label = "Access Online at " + data.checkoutSource;

     if(data.checkoutSource == "OverDrive") {

        if(data.overdriveRead == 1) {
            var formatId = "ebook-overdrive";
            var label = "Read Online at " + data.checkoutSource;

        } else if (data.overdriveListen == 1) {
            var formatId = "audiobook-overdrive";
            var label = "Listen Online at " + data.checkoutSource;

        } else if (data.overdriveVideo == 1) {
            var formatId = "video-streaming";
            var label = "Watch Online at " + data.checkoutSource;

        } else if (data.overdriveMagazine == 1) {
            var formatId = "magazine-overdrive";
            var label = "Read Online at " + data.checkoutSource;

        } else {
            var formatId = '';
            var label = "Access Online at " + data.checkoutSource;
        }

    }



    // check that title ends in / first
    var title = data.title;
    var countSlash = title.split('/').length-1;
    if (countSlash > 0) {
        var title = title.substring(0, title.lastIndexOf('/'));
    }

    var author = data.author;
    var countComma = author.split(',').length-1;
    if (countComma > 1) {
        var author = author.substring(0, author.lastIndexOf(','));
    }

    return (
    <>
    <Pressable onPress={onOpen}>
    <ListItem bottomDivider>
        <Avatar source={{ uri: data.coverUrl }} size="56px" alt={data.title}/>
        <ListItem.Content>
            <Text fontSize="sm" bold mb={1}>
                {data.overdue ? <Badge colorScheme="danger" rounded="4px" mt={-.5}>Overdue</Badge> : null} {title}
            </Text>

            {author != "" ?
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Author:
                    <Text fontSize="xs"> {author}</Text>
                </Text>
            </Text>
            : null}
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Format:
                    <Text fontSize="xs"> {data.format}</Text>
                </Text>
            </Text>
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Due:
                    <Text fontSize="xs"> {itemDueOn}</Text>
                </Text>
            </Text>
            {data.autoRenew == 1 ?
                <Box mt={1} p={.5} bgColor="muted.100">
                <Text fontSize="xs">If eligible, this item will renew on {data.renewalDate}</Text>
                </Box>
            : null}
        </ListItem.Content>

    </ListItem>
    </Pressable>
      <Actionsheet isOpen={isOpen} onClose={onClose} size="full">
        <Actionsheet.Content>
          <Box w="100%" h={60} px={4} justifyContent="center">
            <Text
              fontSize={18}
              color="gray.500"
              _dark={{
                color: "gray.300",
              }}
            >
              {title}
            </Text>
          </Box>
          <Divider />
        {data.canRenew ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="autorenew"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            renewCheckout(data.barcode, false);
            onClose(onClose);
            }} >
            Renew
        </Actionsheet.Item>
        : null}

        {data.source == "overdrive" ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="book"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            viewOverDriveItem(data.userId, formatId, data.overDriveId);
            onClose(onClose);
            }} >
            {label}
        </Actionsheet.Item>
        : null }

        {data.accessOnlineUrl != null ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="book"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            viewOnlineItem(data.userId, data.recordId, data.source, data.accessOnlineUrl);
            onClose(onClose);
            }} >
            {label}
        </Actionsheet.Item>
        : null }

        {data.accessOnlineUrl != null ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnCheckout(data.userId, data.recordId, data.source, data.overDriveId);
            onClose(onClose);
            }}>
            Return Now
        </Actionsheet.Item>
        : null }

        {data.canReturnEarly ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnCheckout(data.userId, data.recordId, data.source, data.overDriveId);
            onClose(onClose);
            }}>
            Return Now
        </Actionsheet.Item>
        : null }

        </Actionsheet.Content>
      </Actionsheet>
    </>
    )
}
