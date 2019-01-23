<?php
/**
 * Script to upgrade VuFind database between versions.
 *
 * command line arguments:
 *   * MySQL admin username
 *   * MySQL admin password
 *   * path to previous version installation
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Upgrade_Tools
 * @author   Till Kinstler <kinstler@gmail.com>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/migration_notes Wiki
 */

// Die if we don't have enough parameters:
if (!isset($argv[3]) || !isset($argv[2]) || !isset($argv[1])) {
    die("\n\nMissing command line parameter... aborting database upgrade.\n\n");
}

$mysql_admin_user = $argv[1];
$mysql_admin_pw = $argv[2];
$old_config = $argv[3];

// Try to read the ini file:
$iniFile = $old_config . '/web/conf/config.ini';
if (!file_exists($iniFile)) {
    die("\n\nProblem opening {$iniFile}... aborting database upgrade.\n\n");
}
$configArray = parse_ini_file($iniFile, true);

?>

#### Database upgrade ####

This script will upgrade your VuFind database from the previous version to the new
release.  It is recommended that you make a backup before proceeding with this
script, just to be on the safe side!

<?php

// get connection credentials from config.ini
$match = preg_match(
    "/mysql:\/\/([^:]+):([^@]+)@([^\/]+)\/(.+)/",
    $configArray['Database']['database'], $mysql_conn
);
if (!$match) {
    echo "Can't determine data needed to access your MySQL database. You have " .
        $configArray['Database']['database'] .
        "\nas connection string in your config.ini.\n";
    exit(0);
}

if ($mysql_admin_user=="") {
    $mysql_admin_user="root";
}
$mysql_host = $mysql_conn[3];
$mysql_db = $mysql_conn[4];
if ($mysql_db=="") {
    $mysql_db="vufind";
}

echo "\nUsing the following values to access your MySQL database:\n";
echo "MySQL admin username: " . $mysql_admin_user . "\n";
echo "MySQL VuFind username: " . $mysql_conn[1] . "\n";
echo "MySQL database: " . $mysql_db . "\n";
echo "MySQL host: " . $mysql_host . "\n";

$line = "n";

while ($line != "y") {
    $line = getInput("\nDo you want to proceed? [y/n] ");
    if ($line == "n") {
        exit(0);
    }
}

// create a PDO with connection to database
$dsn = 'mysql:host=' . $mysql_host . ';dbname=' . $mysql_db;
try {
    $db = new PDO($dsn, $mysql_admin_user, $mysql_admin_pw);
} catch(PDOException $e) {
    echo "Error connecting to database -- " . $e->getMessage() . "\n";
    exit(0);
}
if (!$db) {
    echo "Error connecting to Database\n";
    exit(0);
}

// add new tables:
addNewTables();

// adjust existing tables:
updateExistingTables();

// clean up anonymous tags (this should not be necessary after release 1.1, but
// it doesn't hurt to retain the check just in case the data gets corrupted or
// this cleanup step was skipped during a previous upgrade process):
cleanUpAnonymousTags();

/**
 * Support function to execute a SQL statement.
 *
 * @param string $sqlStatement SQL to execute.
 * @param array  $bindParams   Bind parameters to pass with SQL.
 *
 * @return object              PDO SQL object (on success; does not return on error).
 */
function executeSQL($sqlStatement, $bindParams = array())
{
    global $db;

    $sql = $db->prepare($sqlStatement);
    if (!$sql->execute($bindParams)) {
        die("Problem executing: {$sqlStatement}");
    }
    return $sql;
}

/**
 * Routine to handle cleanup of anonymous tags in the database.
 *
 * @return void
 */
function cleanUpAnonymousTags()
{
    // Are there any problem tags to deal with?
    $sql = executeSQL("SELECT COUNT(*) FROM resource_tags WHERE user_id IS NULL");
    $problems = $sql->fetchColumn();
    if ($problems < 1) {
        return;
    }

    // Problems found -- get username!
    echo "Due to a bug in earlier versions of VuFind, you have {$problems} tags\n";
    echo "in your database that are not associated with a user account.  It is\n";
    echo "recommended that you associate these tags with a user account for\n";
    echo "easier maintenance in the future.  Please enter a username (preferably\n";
    echo "an administrator) to associate with old anonymous tags.\n\n";
    echo "See http://vufind.org/jira/browse/VUFIND-217 for more details.\n\n";
    while (true) {
        $username = getInput("Enter username of new tag owner (blank to skip): ");
        if (empty($username)) {
            $sure = getInput("Are you sure you want to skip this step? [y/n] ");
            if ($sure == 'y') {
                return;
            }
        } else {
            $sql = executeSQL(
                "SELECT * FROM user WHERE username = :name",
                array(':name' => $username)
            );
            $user = $sql->fetch();
            if ($user === false) {
                echo "User {$username} does not exist!\n";
            } else {
                break;
            }
        }
    }

    // We have user information -- let's perform the update!
    $sql = executeSQL(
        "UPDATE resource_tags SET user_id=:id WHERE user_id IS NULL",
        array(':id' => $user['id'])
    );
    echo $sql->rowCount() . " rows successfully reassigned to {$username}.\n";
}

/**
 * Routine to add new tables to the database.
 *
 * @return void
 */
function addNewTables()
{
    /* No new tables in this release, but retaining code from previous
       upgrade script for future reference...
    // Get a list of all existing tables (so we can avoid duplicates):
    $sql = executeSQL("SHOW TABLES;");
    $tmp = $sql->fetchAll();
    $tables = array();
    foreach ($tmp as $current) {
        $tables[] = $current[0];
    }

    if (in_array('change_tracker', $tables)) {
        echo "Skipping table creation -- change_tracker already exists.\n";
    } else {
        $sqlStatement = "CREATE TABLE `change_tracker` (" .
            "`core` varchar(30) NOT NULL," .
            "`id` varchar(64) NOT NULL," .
            "`first_indexed` datetime," .
            "`last_indexed` datetime," .
            "`last_record_change` datetime," .
            "`deleted` datetime," .
            "PRIMARY KEY (`core`, `id`)," .
            "KEY `deleted_index` (`deleted`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        executeSQL($sqlStatement);
    }

    if (in_array('oai_resumption', $tables)) {
        echo "Skipping table creation -- oai_resumption already exists.\n";
    } else {
        $sqlStatement = "CREATE TABLE `oai_resumption` (" .
            "`id` int(11) NOT NULL auto_increment," .
            "`params` text," .
            "`expires` datetime NOT NULL default '0000-00-00 00:00:00'," .
            "PRIMARY KEY  (`id`)" .
            ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
        executeSQL($sqlStatement);
    }

    echo "Done creating tables.\n\n";
     */
}

/**
 * Routine to update existing tables in the database.
 *
 * @return void
 */
function updateExistingTables()
{
    // Get list of fields in user table:
    $check = executeSQL("DESCRIBE user");
    $tmp = $check->fetchAll();
    $fields = array();
    foreach ($tmp as $current) {
        $fields[] = $current['Field'];
    }

    // Add home_library field if not already present:
    if (!in_array('home_library', $fields)) {
        echo "Adding home library column to user table... ";
        executeSQL(
            "ALTER TABLE user ADD COLUMN home_library varchar(100) " .
            "NOT NULL DEFAULT '';"
        );
        echo "done!\n\n";
    } else {
        echo "No table updates necessary.\n\n";
    }
}

/**
 * readline() does not exist on Windows.  This is a simple wrapper for portability.
 *
 * @param string $prompt Prompt to display to the user.
 *
 * @return string        User-entered response.
 */
function getInput($prompt)
{
    // Standard function for most uses
    if (function_exists('readline')) {
        $in = readline($prompt);
        return $in;
    } else {
        // Or use our own if it doesn't exist (windows)
        print $prompt;
        $fp = fopen("php://stdin", "r");
        $in = fgets($fp, 4094); // Maximum windows buffer size
        fclose($fp);
        // Seems to keep the carriage return if you don't trim
        return trim($in);
    }
}

?>
