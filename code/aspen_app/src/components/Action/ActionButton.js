import _ from 'lodash';
import { CheckedOutToYou } from './CheckedOutToYou';
import { CheckOut } from './CheckOut/CheckOut';
import { PlaceHold } from './Holds/PlaceHold';
import { StartVDXRequest } from './Holds/VDXRequest';
import { LoadOverDriveSample } from './LoadOverDriveSample';
import { OnHoldForYou } from './OnHoldForYou';
import { OpenSideLoad } from './OpenSideLoad';

export const ActionButton = (data) => {
     const action = data.actions;
     const {
          volumeInfo,
          groupedWorkId,
          fullRecordId,
          recordSource,
          title,
          author,
          publisher,
          isbn,
          oclcNumber,
          holdTypeForFormat,
          variationId,
          prevRoute,
          response,
          setResponse,
          responseIsOpen,
          setResponseIsOpen,
          onResponseClose,
          cancelResponseRef,
          holdConfirmationResponse,
          setHoldConfirmationResponse,
          holdConfirmationIsOpen,
          setHoldConfirmationIsOpen,
          onHoldConfirmationClose,
          cancelHoldConfirmationRef,
          language,
          holdSelectItemResponse,
          setHoldSelectItemResponse,
          holdItemSelectIsOpen,
          setHoldItemSelectIsOpen,
          onHoldItemSelectClose,
          cancelHoldItemSelectRef,
          userHasAlternateLibraryCard,
          shouldPromptAlternateLibraryCard,
     } = data;
     if (_.isObject(action)) {
          if (action.type === 'overdrive_sample') {
               return <LoadOverDriveSample title={action.title} prevRoute={prevRoute} id={fullRecordId} type={action.type} sampleNumber={action.sampleNumber} formatId={action.formatId} />;
          } else if (action.type === 'project_palace_sample') {
               return null;
          } else if (action.url === '/MyAccount/CheckedOut') {
               return <CheckedOutToYou title={action.title} prevRoute={prevRoute} />;
          } else if (action.url === '/MyAccount/Holds') {
               return <OnHoldForYou title={action.title} prevRoute={prevRoute} />;
          } else if (action.type === 'ils_hold') {
               return (
                    <PlaceHold
                         language={language}
                         title={action.title}
                         id={groupedWorkId}
                         type={action.type}
                         record={fullRecordId}
                         holdTypeForFormat={holdTypeForFormat}
                         variationId={variationId}
                         volumeInfo={volumeInfo}
                         prevRoute={prevRoute}
                         setResponseIsOpen={setResponseIsOpen}
                         responseIsOpen={responseIsOpen}
                         onResponseClose={onResponseClose}
                         cancelResponseRef={cancelResponseRef}
                         response={response}
                         setResponse={setResponse}
                         setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                         holdConfirmationIsOpen={holdConfirmationIsOpen}
                         onHoldConfirmationClose={onHoldConfirmationClose}
                         cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                         holdConfirmationResponse={holdConfirmationResponse}
                         setHoldConfirmationResponse={setHoldConfirmationResponse}
                         setHoldItemSelectIsOpen={setHoldItemSelectIsOpen}
                         holdItemSelectIsOpen={holdItemSelectIsOpen}
                         onHoldItemSelectClose={onHoldItemSelectClose}
                         cancelHoldItemSelectRef={cancelHoldItemSelectRef}
                         holdSelectItemResponse={holdSelectItemResponse}
                         setHoldSelectItemResponse={setHoldSelectItemResponse}
                         userHasAlternateLibraryCard={userHasAlternateLibraryCard}
                         shouldPromptAlternateLibraryCard={shouldPromptAlternateLibraryCard}
                         recordSource={recordSource}
                    />
               );
          } else if (action.type === 'vdx_request') {
               return (
                    <StartVDXRequest
                         title={action.title}
                         record={fullRecordId}
                         id={groupedWorkId}
                         workTitle={title}
                         author={author}
                         publisher={publisher}
                         isbn={isbn}
                         oclcNumber={oclcNumber}
                         holdTypeForFormat={holdTypeForFormat}
                         variationId={variationId}
                         prevRoute={prevRoute}
                         setResponseIsOpen={setResponseIsOpen}
                         responseIsOpen={responseIsOpen}
                         onResponseClose={onResponseClose}
                         cancelResponseRef={cancelResponseRef}
                         response={response}
                         setResponse={setResponse}
                         setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                         holdConfirmationIsOpen={holdConfirmationIsOpen}
                         onHoldConfirmationClose={onHoldConfirmationClose}
                         cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                         holdConfirmationResponse={holdConfirmationResponse}
                         setHoldConfirmationResponse={setHoldConfirmationResponse}
                    />
               );
          } else if (!_.isUndefined(action.redirectUrl)) {
               return <OpenSideLoad title={action.title} url={action.redirectUrl} prevRoute={prevRoute} />;
          } else {
               return (
                    <CheckOut
                         title={action.title}
                         type={action.type}
                         id={groupedWorkId}
                         record={fullRecordId}
                         holdTypeForFormat={holdTypeForFormat}
                         variationId={variationId}
                         volumeInfo={volumeInfo}
                         prevRoute={prevRoute}
                         setResponseIsOpen={setResponseIsOpen}
                         responseIsOpen={responseIsOpen}
                         onResponseClose={onResponseClose}
                         cancelResponseRef={cancelResponseRef}
                         response={response}
                         setResponse={setResponse}
                         setHoldConfirmationIsOpen={setHoldConfirmationIsOpen}
                         holdConfirmationIsOpen={holdConfirmationIsOpen}
                         onHoldConfirmationClose={onHoldConfirmationClose}
                         cancelHoldConfirmationRef={cancelHoldConfirmationRef}
                         holdConfirmationResponse={holdConfirmationResponse}
                         setHoldConfirmationResponse={setHoldConfirmationResponse}
                         userHasAlternateLibraryCard={userHasAlternateLibraryCard}
                         shouldPromptAlternateLibraryCard={shouldPromptAlternateLibraryCard}
                         recordSource={recordSource}
                    />
               );
          }
     }

     return null;
};