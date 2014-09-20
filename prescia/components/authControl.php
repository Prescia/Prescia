<?	# -------------------------------- Prescia Auth control interface (this file actually is just a basic interface to be overriden)


/* -- THIS CLASS IS A BASIC AUTHENTICATION PLACEHOLDER. For a real authentication system, override this class with yours in a plugin (example: bi_auth)

	This is a summary of what each function does and when they are called:

-- GENERAL FUNCTIONS --
	lockpermissions - everytime an action changes a permission setting, this will check if the permission scope has changed and load them
		called by: auth::checkPermissions (loads permissions if none loaded), auth::logsGuest (reload permissions of the guest), core::lockPermissions (core shortcut)
	checkPermission - will check if someone ($owner) has permission to perform a certain $action (see constants) in a given $module
		called by: auth::forcePermissions (should check for permissions while enforcing permissions) core::gracefullDownload (check for permission on viewing a file), module::runAction (check if the current user can perform an action)
	checkOwner - will check if the current logged user is the owner (by user, group or general permissions) of a certain item on a $module given the $keys
		called by: module::runAction (will limit actions to the current owner)
	forcePermissions - will change (add) SQL statements on $sql queries to prevent data the current user should not be able to see or change ona given $module
		called by: module::runContent (will limit selects for what the current user can see)
-- LOGIN/LOGOUT FUNCTIONS --
	logsGuest - will log the default guest user (meaning, nobody is logged)
		called by: auth::logOut (logs the guest after a logout)
	logOut - will log the current user out and proceed to log the default guest (logsGuest above)
		called by: auth::auth (authentication resulted on logout request)
	auth - will autenticate a user by checking incomming cookies, POST or session data. Should return the authentication level resulted (constants CONS_AUTH_...)
		called by: core::checkActions (starts authentication process)
	logUser - will log a given user ($loginId).
		called by:auth::auth (if login/password match an id)

*/

class CauthControl { # This class can be overridden by any auth module

	var $parent = null;

	function __construct(&$parent) {
		$this->parent = &$parent;
	}

	function lockpermissions() { # called by core
		# this function should replace the current permission array ($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS]) which whatever permissions it detects, based on requested module and data or even globals
		if (!is_array($this->parent->permissionTemplate)) $this->parent->loadPermissions();
		$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] =$this->parent->permissionTemplate;
	} # lockpermissions

	function checkPermission($module, $action=CONS_ACTION_SELECT, $owner =false) {
		# This function should check if the selected action can be performed on the selected module
		# owner if a 3-position array with: ( is-owner (boolean), is-from-same-group (boolean), [group-id (int)] )
		return true; // basic system has no auth control, thus always true
	} # checkPermission

	function checkOwner(&$module,$keys) { # called by modules
		# Default system has no owner control, so everything is owned by everyone
		# returns if the current user is the owner (and in what level) of the specified module given the keys.
		# The return will be used on checkPermission's third parameter, and goes in the format: ( is-owner (boolean), is-from-same-group (boolean), [group-id (int)] )
		return array(true,true,0);
	} # checkOwner

	function forcePermissions(&$module,$sql,$isRead=true) { # called by modules
		# Given a SQL array, changes it (adds restrictions on WHERE, LEFT JOINS etc) to guarantee it only shows allowed data as per action/module it required
		if ($module->dbname != "" && strpos($sql['FROM'][0],$module->dbname)===false)
			$this->parent->errorControl->raise(108,$sql['FROM'][0],$module->name); # the $module is not the same as the database specified
		return $sql; # Default system has no permission control, thus don't change the sql
	} # forcePermissions

	function logsGuest($nooverride = false) {
		$_SESSION[CONS_SESSION_ACCESS_USER] = array();
		$_SESSION[CONS_SESSION_ACCESS_LEVEL] = CONS_SESSION_ACCESS_LEVEL_GUEST;
		$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = array();
		$this->parent->lockPermissions();
		$this->parent->currentAuth = CONS_AUTH_SESSION_GUEST;
	} # logsGuest
#-
	function logOut() {
		# placeholder function
		$this->logsGuest();
	}
#-
	function auth() {
		# Authenticate user or guest, returns a CONS_AUTH_SESSION_... constant
		if ($this->parent->offlineMode || isset($_REQUEST['logout'])) { // requested to log-off
			$this->logOut();
			return CONS_AUTH_SESSION_GUEST;
		}
		if (isset($_SESSION[CONS_SESSION_ACCESS_USER]) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id'])) { // someone is logged, keep it
			return CONS_AUTH_SESSION_KEEP; # someone is logged, ignore login procedures until logs-off/time out
		}
		$this->logsGuest(); # guest
		return CONS_AUTH_SESSION_GUEST;
	} # auth
#-
	function logUser($loginId,$sucessCode) {
		# placeholder function
		// if this where a real auth module, on sucessfully login would return $sucessCode
		return CONS_AUTH_SESSION_GUEST;
	} # logUser


}

