import React, {Component, useState} from "react";
import {Button, FormControl, Modal} from "native-base";
import {Ionicons, MaterialCommunityIcons, MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import _ from "lodash";

export default class SelectThawDate extends Component{
	constructor(props) {
		super(props);
		this.state = {
			selectedStartDate: null,
		};
		this.onDateChange = this.onDateChange.bind(this);
	}

	onDateChange(date) {
		this.setState({
			selectedStartDate: date,
		})
	}

	render() {
		const { selectedStartDate } = this.state;
		const startDate = selectedStartDate ? selectedStartDate.toString() : '';

		return (
			<ShowCalendarModal />
		)
	}
}

const ShowCalendarModal = (props) => {
	const {handleOnDateChange, libraryUrl, reactivationDate, data, count} = props;

	const [loading, setLoading] = useState(false);
	const [showModal, setShowModal] = useState(false);

	const minDate = new Date(); // Today
	//const maxDate = new Date(2017, 6, 3);

	let selectedReactivationDate = moment(reactivationDate).format('YYYY-MM-DD');
	if(selectedReactivationDate === "Invalid date") {
		selectedReactivationDate = null;
	}
}
