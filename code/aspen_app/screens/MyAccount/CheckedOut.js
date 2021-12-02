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
import _ from "lodash";

// custom components and helper files
import { translate } from '../../util/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { getCheckedOutItems } from '../../util/loadPatron';
import { isLoggedIn, renewCheckout, renewAllCheckouts, returnCheckout, viewOnlineItem, viewOverDriveItem } from '../../util/accountActions';

export default class CheckedOut extends Component {
	static navigationOptions = { title: translate('checkouts.title') };

	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
			numCheckedOut: global.numCheckedOut,
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
                    error: translate('error.timeout'),
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
          forceScreenReload={this._forceScreenReload}
          openGroupedWork={this.openGroupedWork}
        />
		);
	};

	openGroupedWork = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
	};

    openWebsite = async (url) => {

        this.setState({
            isLoading: true,
        });


        await isLoggedIn().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                this.setState({
                    isLoading: false,
                });

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
					{translate('checkouts.no_checkouts')}
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

    _forceScreenReload = async () => {
        var forceReload = true;

        this.setState({
            isLoading: true,
            loadingMessage: "Updating your checkouts",
        });

        await getCheckedOutItems(forceReload).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                    loadingMessage: null,
                    forceReload: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                    loadingMessage: null,
                    forceReload: false,
                });
            }
        })
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
		{this.state.numCheckedOut > 0 ?
            <Center bg="white" pt={3} pb={3}>
                <Button
                    size="sm"
                    colorScheme="primary"
                    onPress={() => renewAllCheckouts()}
                    startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}
                >
                    {translate('checkouts.renew_all')}
                </Button>
            </Center>
        : null }
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
    const { openWebsite, data, renewItem, forceScreenReload, openGroupedWork } = props;
    const { isOpen, onOpen, onClose } = useDisclose();
    const dueDate = moment.unix(data.dueDate);
    var itemDueOn = moment(dueDate).format("MMM D, YYYY");

    var label = translate('checkouts.access_online', { source : data.checkoutSource });

     if(data.checkoutSource == "OverDrive") {

        if(data.overdriveRead == 1) {
            var formatId = "ebook-overdrive";
            var label = translate('checkouts.read_online', { source : data.checkoutSource });

        } else if (data.overdriveListen == 1) {
            var formatId = "audiobook-overdrive";
            var label = translate('checkouts.listen_online', { source : data.checkoutSource });

        } else if (data.overdriveVideo == 1) {
            var formatId = "video-streaming";
            var label = translate('checkouts.watch_online', { source : data.checkoutSource });

        } else if (data.overdriveMagazine == 1) {
            var formatId = "magazine-overdrive";
            var label = translate('checkouts.read_online', { source : data.checkoutSource });

        } else {
            var formatId = 'ebook-overdrive';
            var label = translate('checkouts.access_online', { source : data.checkoutSource });
        }

    }



    // check that title ends in / first
    if(data.title) {
        var title = data.title;
        var countSlash = title.split('/').length-1;
        if (countSlash > 0) {
            var title = title.substring(0, title.lastIndexOf('/'));
        }
    }

    if(data.author) {
        var author = data.author;
        var countComma = author.split(',').length-1;
        if (countComma > 1) {
            var author = author.substring(0, author.lastIndexOf(','));
        }
    }

    return (
    <>
    <Pressable onPress={onOpen}>
    <ListItem bottomDivider>
        <Avatar source={{ uri: data.coverUrl }} size="56px" alt={data.title}/>
        <ListItem.Content>
            <Text fontSize="sm" bold mb={1}>
                {data.overdue ? <Badge colorScheme="danger" rounded="4px" mt={-.5}>{translate('checkouts.overdue')}</Badge> : null} {title}
            </Text>

            {data.author ?
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    {translate('grouped_work.author')}
                    <Text fontSize="xs"> {author}</Text>
                </Text>
            </Text>
            : null}
            {data.format != "Unknown" ?
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    {translate('grouped_work.format')}
                    <Text fontSize="xs"> {data.format}</Text>
                </Text>
            </Text>
            : null }
            <Text fontSize="xs">
                <Text bold fontSize="xs">
                    {translate('checkouts.due')}
                    <Text fontSize="xs"> {itemDueOn}</Text>
                </Text>
            </Text>
            {data.autoRenew == 1 ?
                <Box mt={1} p={.5} bgColor="muted.100">
                <Text fontSize="xs">{translate('checkouts.auto_renew')} {data.renewalDate}</Text>
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
        {data.groupedWorkId != null ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="search"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            openGroupedWork(data.groupedWorkId);
            onClose(onClose);
            }} >
            {translate('grouped_work.view_item_details')}
        </Actionsheet.Item>
        : null
        }

        {data.canRenew ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="autorenew"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            renewCheckout(data.barcode, data.recordId, data.source, data.itemId);
            setTimeout(function(){forceScreenReload();}.bind(this),500);
            onClose(onClose);
            }} >
            {translate('checkouts.renew')}
        </Actionsheet.Item>
        : null
        }

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
            setTimeout(function(){forceScreenReload();}.bind(this),500);
            onClose(onClose);
            }}>
            {translate('checkouts.return_now')}
        </Actionsheet.Item>
        : null }

        {data.canReturnEarly ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="logout"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            returnCheckout(data.userId, data.recordId, data.source, data.overDriveId);
            setTimeout(function(){forceScreenReload();}.bind(this),500);
            onClose(onClose);
            }}>
            {translate('checkouts.return_now')}
        </Actionsheet.Item>
        : null }

        </Actionsheet.Content>
      </Actionsheet>
    </>
    )
}
