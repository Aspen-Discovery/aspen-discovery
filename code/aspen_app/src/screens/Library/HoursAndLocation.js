import React from 'react'
import {Box, Center, Divider, HStack, Icon, Text, VStack} from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import moment from "moment";

// custom components and helper files
import {translate} from '../../translations/translations';

const HoursAndLocation = (props) => {

	const {hoursMessage, description} = props

	return (
		<>
			<Box mb={4}>
				<Center>
					<HStack space={3} alignItems="center">
						<Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1}/>
						<Text fontSize="lg" bold>{translate('library_contact.today_hours')}</Text>
						<Text>{description}</Text>
					</HStack>
					<Text alignText="center" mt={2} italic>{hoursMessage}</Text>
				</Center>
			</Box>
			<Divider mb={10}/>
		</>
	)
}

export default HoursAndLocation;