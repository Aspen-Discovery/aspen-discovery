/**
 * Created by mark on 1/24/14.
 */
var Globals = (function () {
	//Do setup work here
	return {
		path: '',
		url:  '',
		loggedIn:  false,
		opac:  false, // true prevents browser storage of user viewing settings
		automaticTimeoutLength: 0,
		automaticTimeoutLengthLoggedOut: 0,
		repositoryUrl: '',
		encodedRepositoryUrl: '',
		activeAction: '',
		activeModule: ''
	}
})(Globals || {});