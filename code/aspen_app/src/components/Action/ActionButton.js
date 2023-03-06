import _ from 'lodash';
import {LoadOverDriveSample} from './LoadOverDriveSample';
import {CheckedOutToYou} from './CheckedOutToYou';
import {OnHoldForYou} from './OnHoldForYou';
import {PlaceHold} from './Holds/PlaceHold';
import {StartVDXRequest} from './Holds/VDXRequest';
import {OpenSideLoad} from './OpenSideLoad';
import {CheckOut} from './CheckOut/CheckOut';

export const ActionButton = (data) => {
	const action = data.actions;
	const { volumeInfo, groupedWorkId, fullRecordId, recordSource, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = data;
	if (_.isObject(action)) {
		if (action.type === 'overdrive_sample') {
			return <LoadOverDriveSample title={action.title} prevRoute={prevRoute} id={fullRecordId} type={action.type} sampleNumber={action.sampleNumber} formatId={action.formatId} />;
		} else if (action.url === '/MyAccount/CheckedOut') {
			return <CheckedOutToYou title={action.title} prevRoute={prevRoute} />;
		} else if (action.url === '/MyAccount/Holds') {
			return <OnHoldForYou title={action.title} prevRoute={prevRoute} />;
		} else if (action.type === 'ils_hold') {
			return <PlaceHold title={action.title} id={groupedWorkId} type={action.type} record={fullRecordId} volumeInfo={volumeInfo} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse}/>;
		} else if (action.type === 'vdx_request') {
			return <StartVDXRequest title={action.title} record={fullRecordId} id={groupedWorkId} prevRoute={prevRoute} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse} />;
		} else if (!_.isUndefined(action.redirectUrl)) {
			return <OpenSideLoad title={action.title} url={action.redirectUrl} prevRoute={prevRoute} />;
		} else {
			return <CheckOut title={action.title} type={action.type} id={groupedWorkId} record={fullRecordId} volumeInfo={volumeInfo} prevRoute={prevRoute}  setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse}/>;
		}
	}

	return null;
};