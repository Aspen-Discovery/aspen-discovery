import React from "react";
import { Actionsheet, useDisclose, Pressable, HStack, Avatar, VStack, Text, Badge, Box } from 'native-base';
import {translate} from "../../../translations/translations";
import moment from "moment";


export function CheckedOutTitle(props) {
	const {item} = props;
	const {isOpen, onOpen, onClose} = useDisclose();

	return (
		<>
			<RenderItem onOpen={onOpen} item={item} />
			<Actionsheet isOpen={isOpen} onClose={onClose} size="full">
				<Actionsheet.Content>

				</Actionsheet.Content>
			</Actionsheet>
		</>

	)
}

const RenderItem = (props) => {
	const {item, onOpen} = props;
	return (
		<>
			<Pressable onPress={onOpen} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2">
				<HStack space={3}>
					<Avatar source={{uri: item.coverUrl}} borderRadius="md" size={{base: "80px", lg: "120px"}} alt={item.title}/>
					<VStack maxW="75%">
						{item.title ? (<ItemTitle title={item.title} />) : null}
						{item.overdue ? (<ItemBadge label={translate('checkouts.overdue')} color="danger" />) : null}
						{item.author ? (<ItemAuthor item={item} />) : null}
						{item.format !== "Unknown" ? (<ItemFormat format={item.format} />) : null}
						<Text fontSize={{base: "xs", lg: "sm"}}>
							<Text bold>Checked Out To:</Text> {item.user}
						</Text>
						{item.autoRenew === 1 ? (<ItemAutoRenew renewalDate={item.renewalDate}/>) : null}
					</VStack>
				</HStack>
			</Pressable>
		</>
	)
}

function overdriveCheckoutType(item) {
	let formatId;
	let label;
	if (item.overdriveRead === 1) {
		formatId = "ebook-overdrive";
		label = translate('checkouts.read_online', {source: item.checkoutSource});
	} else if (item.overdriveListen === 1) {
		formatId = "audiobook-overdrive";
		label = translate('checkouts.listen_online', {source: item.checkoutSource});
	} else if (item.overdriveVideo === 1) {
		formatId = "video-streaming";
		label = translate('checkouts.watch_online', {source: item.checkoutSource});
	} else if (item.overdriveMagazine === 1) {
		formatId = "magazine-overdrive";
		label = translate('checkouts.read_online', {source: item.checkoutSource});
	} else {
		formatId = 'ebook-overdrive';
		label = translate('checkouts.access_online', {source: item.checkoutSource});
	}
	return {formatId, label}
}

function formatTitle(item) {
	let title = item.title;
	let countSlash = title.split('/').length - 1;
	if(countSlash > 0) {
		title = title.substring(0, title.lastIndexOf('/'));
	}
	return title;
}

function formatAuthor(item) {
	let author = item.author;
	let countComma = author.split(',').length - 1;
	if(countComma > 1) {
		author = author.substring(0, author.lastIndexOf(','));
	}
	return author;
}

function formatDueDate(item) {
	let dueDate = moment.unix(item.dueDate)
	dueDate = moment(dueDate).format("MMM D, YYYY");
	return dueDate;
}

const ItemTitle = (props) => {
	return (
		<Text bold mb={1} fontSize={{base: "sm", lg: "lg"}}>{formatTitle(props.title)}</Text>
	)
}

const ItemAuthor = (props) => {
	return (
		<Text>
			<Text bold>{translate('grouped_work.author')}:</Text> {formatAuthor(props.item)}
		</Text>
	)
}

const ItemBadge = (props) => {
	return (
		<Text>
			<Badge
				colorScheme={props.color}
				rounded="4px"
				mt={-.5}>
					{translate(props.label)}
			</Badge>
		</Text>
	)
}

const ItemAutoRenew = (props) => {
	return (
		<Box mt={1} p={.5} bgColor="muted.100">
			<Text fontSize={{base: "xs", lg: "sm"}}><Text bold>{translate('checkouts.auto_renew')}:</Text> {props.renewalDate}</Text>
		</Box>
	)
}

const ItemFormat = (props) => {
	return (
		<Text fontSize={{base: "xs", lg: "sm"}}>
			<Text bold>{translate('grouped_work.format')}:</Text> {props.format}
		</Text>
	)
}

export default CheckedOutTitle;