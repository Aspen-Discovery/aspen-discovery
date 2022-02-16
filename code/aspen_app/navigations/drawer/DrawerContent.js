import React from "react";
import { DrawerContentScrollView } from "@react-navigation/drawer";
import { Menu, Container, HamburgerIcon, Badge, VStack, Box, Text, HStack, Icon, Pressable, Divider, Image, Alert, Button, IconButton } from 'native-base';
import {MaterialIcons, Ionicons} from "@expo/vector-icons";
import {translate} from "../../util/translations";
import {UseColorMode} from "../../themes/theme";
import {AuthContext} from "../../components/navigation";
import Constants from 'expo-constants';


function CustomDrawerContent({navigation}) {
	return (
		<DrawerContentScrollView>
			<VStack space="4" my="2" mx="1" divider={<Divider />}>
				<Box px="4">
					<HStack space={3} alignItems="center">
						<Image
							source={{
								uri: Constants.manifest.extra.libraryCardLogo
							}}
							fallbackSource={require("../../themes/default/aspenLogo.png")}
							w={42}
							h={42}
							alt={translate('user_profile.library_card')}
							rounded="8"
						/>
						<Box>
							<Text bold fontSize="14">{global.patron}</Text>
							<Text fontSize="12" fontWeight="500">{global.libraryName}</Text>
							<HStack space={1} alignItems="center" >
								<Icon as={MaterialIcons} name="credit-card" size="xs"/>
								<Text fontSize="12" fontWeight="500">{global.barcode}</Text>
							</HStack>
						</Box>
					</HStack>
				</Box>

				<VStack divider={<Divider />} space="4">
					<VStack>
						<Pressable px="5" py="2" rounded="md" onPress={() => { navigation.navigate('AccountTab', { screen: 'CheckedOut' })}}>
							<HStack space="3" alignItems="center">
								<Icon as={MaterialIcons} name="book" size="7" />
								<VStack w="100%">
									<Text fontWeight="500">{translate('checkouts.title')} <Text bold>({global.numCheckedOut})</Text></Text>
									<Container><Badge colorScheme="error" rounded="4px">{translate('checkouts.overdue_summary', {count: global.numOverdue})}</Badge></Container>
								</VStack>
							</HStack>
						</Pressable>
						<Pressable px="5" py="3" rounded="md" onPress={() => { navigation.navigate('AccountTab', { screen: 'Holds' })}}>
							<HStack space="3" alignItems="center">
								<Icon as={MaterialIcons} name="hourglass-top" size="7" />
								<VStack w="100%">
									<Text fontWeight="500">{translate('holds.title')} <Text bold>({global.numHolds})</Text></Text>
									<Container><Badge colorScheme="success" rounded="4px">{translate('holds.ready_for_pickup', {count: global.numHoldsAvailable})}</Badge></Container>
								</VStack>
							</HStack>
						</Pressable>
						<Pressable px="5" py="3" rounded="md" bg="transparent">
							<HStack space="3" alignItems="center">
								<Icon as={Ionicons} name="bookmark" size="7" />
								<VStack w="100%">
									<Text fontWeight="500">My Lists</Text>
								</VStack>
							</HStack>
						</Pressable>
					</VStack>
					<VStack space="3">
						<Text fontWeight="500" fontSize="14" px="5" color="gray.500">
							Account Settings
						</Text>
						<VStack>
							<Pressable px="5" py="3">
								<HStack space="3" alignItems="center">
									<Icon as={MaterialIcons} name="account-circle" size="5" />
									<Text fontWeight="500">
										Profile
									</Text>
								</HStack>
							</Pressable>
							<Pressable px="5" py="2">
								<HStack space="3" alignItems="center">
									<Icon as={MaterialIcons} name="supervisor-account" size="5" />
									<Text fontWeight="500">
										Linked Accounts
									</Text>
								</HStack>
							</Pressable>
							<Pressable px="5" py="3">
								<HStack space="3" alignItems="center">
									<Icon as={MaterialIcons} name="settings" size="5" />
									<Text fontWeight="500">
										Preferences
									</Text>
								</HStack>
							</Pressable>
						</VStack>
					</VStack>
				</VStack>
				<VStack space={3} alignItems="center">
					<HStack space={2}>
						<LogOutButton/>
						<LanguageSwitcher/>
					</HStack>
					<UseColorMode/>
				</VStack>
			</VStack>
		</DrawerContentScrollView>
	);
}

function LogOutButton() {
	const { signOut } = React.useContext(AuthContext);

	return(
		<Button size="sm" colorScheme="secondary" onPress={signOut} leftIcon={<Icon as={MaterialIcons} name="logout" size="xs" />}>{translate('general.logout')}</Button>
	)
}

function LanguageSwitcher() {
	return (
		<Button size="sm" colorScheme="secondary" leftIcon={<Icon as={MaterialIcons} name="language" size="xs" />}>English</Button>
	)
}

export default CustomDrawerContent