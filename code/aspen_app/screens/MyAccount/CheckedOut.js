import React, { Component, useState, useEffect } from "react";
import { View, Dimensions, Animated, StatusBar, RefreshControl } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Heading, Divider, Flex, Box, Text, Icon, Image, Menu, Pressable, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import base64 from 'react-native-base64';
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

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

	_onRefresh() {
	    this.setState({ isRefreshing: true });
	    this._fetchCheckouts().then(() => {
	        this.setState({ isRefreshing: false });
	    });
	}


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

async function getPatronCheckedOutItems() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        ("Patron checkouts updated.")
        return fetchedData;

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

function CheckedOutItem(props) {


    const { openWebsite, renewItem, data } = props;
    const { isOpen, onOpen, onClose } = useDisclose();
    const dueDate = moment.unix(data.dueDate);
    var itemDueOn = moment(dueDate).format("MMM D, YYYY");

    console.log(itemDueOn);

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
                    <Text fontSize="xs"> {itemDueOn}</Text>
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

        {data.source == "overdrive" &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="book"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            viewOverDriveItem(data.userId, formatId, data.overDriveId);
            onClose(onClose);
            }} >
            {label}
        </Actionsheet.Item>
        }

        {data.accessOnlineUrl != null &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="book"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            viewOnlineItem(data.userId, data.recordId, data.source);
            onClose(onClose);
            }} >
            {label}
        </Actionsheet.Item>
        }

        {data.accessOnlineUrl != null &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnCheckout(data.userId, data.recordId, data.source, data.overDriveId);
            onClose(onClose);
            }}>
            Return Now
        </Actionsheet.Item>
        }

        {data.canReturnEarly &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnCheckout(data.userId, data.recordId, data.source, data.overDriveId);
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

async function returnCheckout(userId, id, source, overDriveId) {

    var itemId = id;
    if(overDriveId != null) {
        var itemId = overDriveId;
    }

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=returnCheckout', { username: global.userKey, password: global.secretKey, id: itemId, patronId: userId, itemSource: source });

    if(response.ok) {
        const results = response.data;

        if (results.result.success == true) {
            Toast.show({
                title: "Title returned",
                description: results.result.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: results.result.message,
                zIndex: 9999,
                placement: "top"
            });
        } else {
            Toast.show({
                title: "Unable to return title",
                description: results.result.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: results.result.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        const result = response.problem;
        return result;
    }

}

async function isLoggedIn() {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=isLoggedIn');


    if(response.ok) {
        const result = response.data;
        console.log(result);
        return result;

    } else {
        const result = response.problem;
        console.log(result);
        return result;
    }
}

async function viewOnlineItem(userId, id, source) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, patronId: userId, itemId: id, itemSource: source });

    if(response.ok) {
        const results = response.data;

        console.log(results.result.url);

        const result = results.result.url;

        await WebBrowser.openBrowserAsync(result)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      console.log("Unable to close previous browser session.");
                    });
                } catch(error) {
                    console.log ("Really borked.");
                }

            } else {
              console.log("Unable to open browser window.");
            }
          });
    } else {
        var result = response.problem;
        return result;
    }

}

async function viewOverDriveItem(userId, formatId, overDriveId) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, patronId: userId, overDriveId: overDriveId, formatId: formatId, itemSource: "overdrive" });

    if(response.ok) {
        const result = response.data;
        const accessUrl = result.result.url;

        await WebBrowser.openBrowserAsync(accessUrl)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      console.log("Unable to close previous browser session.");
                    });
                } catch(error) {
                    console.log ("Really borked.");
                }
            } else {
              console.log("Unable to open browser window.");
            }
          });


    } else {
        const result = response.problem;
        return result;
    }
}
