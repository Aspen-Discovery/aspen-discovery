import React, { Component, useState } from "react";
import { View, Dimensions, Animated, StatusBar } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Heading, Divider, Flex, Box, Text, Icon, Image, Menu, Pressable, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import base64 from 'react-native-base64';
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

export default class Checkouts extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = async () => {
        this.setState({
            checkoutInfoLastLoaded: JSON.parse(await SecureStore.getItemAsync("checkoutInfoLastLoaded")),
            data: global.checkedOutItems,
            isLoading: false,
        })

        let hours = moment().diff(moment(this.state.checkoutInfoLastLoaded), 'hours');
        if(hours >= 1) {
            console.log("Checkout data older than 1 hour.")
            try {
                this._fetchCheckouts();
            } catch (e) {
                console.log("Unable to update.")
            }
        } else {
            console.log("Checkout data still fresh.")
        }
	};

    componentWillUnmount() {
    }

	// grabs the items checked out to the account
	_fetchCheckouts = async () => {

	    this.setState({
	        isLoading: true,
	    });

        await getPatronCheckedOutItems().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
            console.log("Updated")
            var thisMoment = moment().unix();
                this.setState({
                    data: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                    checkoutInfoLastLoaded: thisMoment,
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
        WebBrowser.openBrowserAsync(url);
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

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		} else if (this.state.hasError) {
            return(
               <Center flex={1}>
                <HStack>
                     <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                     <Heading color="error.500" mb={2}>Error</Heading>
                </HStack>
                <Text bold w="75%" textAlign="center">There was an error loading results from the library. Please try again.</Text>
                 <Button
                     mt={5}
                     colorScheme="primary"
                     onPress={() => this._fetchCheckouts()}
                     startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                 >
                     Reload
                 </Button>
                 <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {this.state.error}</Text>
                </Center>
            )
		}

		return (
		<Box h="100%">
            <Center bg="white" pt={3} pb={3}>
                <Button.Group>
                    <Button
                        size="sm"
                        colorScheme="primary"
                        onPress={() => this.renewCheckout(barcode = null, renewAll = true)()}
                        startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}
                    >
                        Try to Renew All
                    </Button>
                    <Button
                    size="sm"
                        onPress={() => this._fetchCheckouts()}
                        startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                    >
                        Reload Checkouts
                    </Button>
                </Button.Group>
            </Center>
			<FlatList
				data={global.checkedOutItems}
				ListEmptyComponent={this._listEmptyComponent()}
				renderItem={({ item }) => this.renderNativeItem(item)}
				keyExtractor={(item) => item.barcode}
			/>
        </Box>
		);

	}
}

async function getPatronCheckedOutItems() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: base64.decode(global.userKey), password: base64.decode(global.secretKey) });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        global.checkedOutItems = fetchedData.checkedOutItems;

        ("Patron checkouts updated.")
        return fetchedData;
    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function viewOnlineItem(id, userId, source) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { userId: userId, id: id, source: source });

    if(response.ok) {
        const result = response.data;
        console.log(response.data);
        return result;
    } else {
        const result = response.problem;
        return result;
    }

}

async function getOverDriveDownloadLink(props) {

    const { userId, overDriveId, formatId } = props;

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=openOverDriveItem', { userId: userId, overDriveId: overDriveId, formatId: formatId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        return fetchedData;
    } else {
        const fetchedData = response.problem;
        return fetchedData;
    }
}

function CheckedOutItem(props) {

    const { openWebsite, renewItem, data } = props;
    const { isOpen, onOpen, onClose } = useDisclose();
    const dueDate = new Date(data.dueDate);
    const dateDue = moment(dueDate).format("MMM D, YYYY");

    console.log(data);

    var label = "Access Online";

     if(data.checkoutSource == "OverDrive") {

        if(data.overdriveRead == 1) {
            var formatId = "ebook-overdrive";
            var label = "Read Online";
        } else if (data.overdriveListen == 1) {
            var formatId = "audiobook-overdrive";
            var label = "Listen Online";
        } else if (data.overdriveVideo == 1) {
            var formatId = "video-streaming";
            var label = "Watch Online";
        } else if (data.overdriveMagazine == 1) {
            var formatId = "magazine-overdrive";
            var label = "Read Online";
        } else {
            var formatId = '';
            var label = "Access Online";
        }

        var accessOnlineUrl = getOverDriveDownloadLink(data.userId, formatId, data.overDriveId);

    } else if (data.checkoutSource == "Hoopla") {

        var accessOnlineUrl = viewOnlineItem(data.userId, data.id, data.source);

    } else if (data.checkoutSource == "CloudLibrary") {

        var accessOnlineUrl = viewOnlineItem(data.userId, data.id, data.source);

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
                {title} {data.overdue && <Badge colorScheme="danger" rounded="4px" mt={-.5}>Overdue</Badge>}
            </Text>

            {author != "" &&
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Author:
                    <Text fontSize="xs"> {author}</Text>
                </Text>
            </Text>
            }
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Format:
                    <Text fontSize="xs"> {data.format}</Text>
                </Text>
            </Text>
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    Due:
                    <Text fontSize="xs"> {dateDue}</Text>
                </Text>
            </Text>
            {data.autoRenew == 1 &&
                <Box mt={1} p={.5} bgColor="muted.100">
                <Text fontSize="xs">If eligible, this item will renew on {data.renewalDate}</Text>
                </Box>
            }
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
        {data.canRenew &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="autorenew"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            renewCheckout(data.barcode, false);
            onClose(onClose);
            }} >
            Renew
        </Actionsheet.Item>
        }

        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="book"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            openWebsite(accessOnlineUrl);
            onClose(onClose);
            }} >
            {label}
        </Actionsheet.Item>

        {data.canReturnEarly &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnOverDriveCheckout(data.overDriveId);
            onClose(onClose);
            }}>
            Return Now
        </Actionsheet.Item>
        }
        {data.groupedWorkId != null &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="search"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                console.log("Open item");
            }} >
        Item Details
        </Actionsheet.Item>
        }
        </Actionsheet.Content>
      </Actionsheet>
    </>
    )
}

async function renewCheckout(barcode, renewAll) {

    if(renewAll == true) {
        var renewMethod = "renewAll";
    } else {
        var renewMethod = "renewItem";
    }

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=' + renewMethod, { username: base64.decode(global.userKey), password: base64.decode(global.secretKey), itemBarcode: barcode });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if (fetchedData.success == true) {
            if (fetchedData.renewalMessage.success == true) {
                Toast.show({
                    title: "Title renewed",
                    description: fetchedData.renewalMessage.message,
                    status: "success",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: fetchedData.renewalMessage.message,
                    zIndex: 9999,
                    placement: "top"
                });
            } else {
                Toast.show({
                    title: "Unable to renew title",
                    description: fetchedData.renewalMessage.message,
                    status: "error",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: fetchedData.renewalMessage.message,
                    zIndex: 9999,
                    placement: "top"
                });
            }
        } else {
            console.log("Connection made, but title not renewed because: " + fetchedData.renewalMessage.message)
        }

    } else {
        const fetchedData = response.problem;
        console.log("Unable to connect to library.");
        return fetchedData;
    }

}

async function returnOverDriveCheckout(overDriveId) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=returnOverDriveCheckout', { username: base64.decode(global.userKey), password: base64.decode(global.secretKey), overDriveId: overDriveId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if (fetchedData.success == true) {
            Toast.show({
                title: "Title returned",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        } else {
            Toast.show({
                title: "Unable to return title",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        const fetchedData = response.problem;
        return fetchedData;
    }

}