var Globals = (function () {
	//Do setup work here
	return {
		path: '',
		url:  '',
		loggedIn:  false,
		masqueradeMode: false,
		opac:  false, // true prevents browser storage of user viewing settings
		automaticTimeoutLength: 0,
		automaticTimeoutLengthLoggedOut: 0,
		repositoryUrl: '',
		encodedRepositoryUrl: '',
		activeAction: '',
		activeModule: '',
		hasILSConnection: false,
		hasAxis360Connection: false,
		hasCloudLibraryConnection: false,
		hasHooplaConnection: false,
		hasOverDriveConnection: false,
		loadingTitle: 'Loading',
		loadingBody: 'Loading, please wait',
		requestFailedTitle: 'Request Failed',
		requestFailedBody: 'There was an error with this AJAX Request.',
		rtl:false
	}
})(Globals || {});