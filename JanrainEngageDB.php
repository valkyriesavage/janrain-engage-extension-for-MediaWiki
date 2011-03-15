<?php
require("ProxyTools.php");

$tableName = $wgDBprefix."janrain";

function init() {
    global $tableName;
    global $wgDBserver;
    global $wgDBname;
    global $wgDBuser;
    global $wgDBpassword;
    global $wgDBprefix;
    global $wgDBTableOptions;

    $link = mysql_connect($wgDBserver, $wgDBuser, $wgDBpassword);
    mysql_select_db($wgDBname, $link);
    $createQuery = "CREATE TABLE IF NOT EXISTS $tableName (
                    `j_identifier` varbinary(255) NOT NULL,
                    `j_username` varbinary(255) NOT NULL,
                    `j_time` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `j_id` mediumint NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`j_identifier`),
                    KEY (`j_username`),
                    KEY (`j_id`)
                )$wgDBTableOptions;";
    mysql_query($createQuery);
}

function cleanup() {
    mysql_close();
}
    
function usernameFromIdentifier($identifier) {
    global $tableName;

    init();

    $findQuery = "SELECT `j_username` FROM `$tableName` WHERE `j_identifier` = '$identifier';";
    $result = mysql_query($findQuery);

    if (!$result) {
        return '';
    }

    $row = mysql_fetch_assoc($result);
    $username = $row['j_username'];
    mysql_free_result($result);

    cleanup();

    return $username;
}

function addIdAndUserToDB($identifier, $username) {
    global $tableName;

    init();

    $insertQuery = "INSERT INTO `$tableName` (`j_identifier`, `j_username`) VALUES ('$identifier', '$username');";
    mysql_query($insertQuery);

    cleanup();
}

function getIdentifierFromDB() {
    global $tableName;

    init();
    
    $ip = wfGetIP();

    $findQuery = "SELECT * FROM `$tableName` WHERE `j_username` = '$ip';";
    $result = mysql_query($findQuery);

    if (!$result) {
        return 'HOLY CRAP WE ARE ALL GONNA DIE';
    }

    $row = mysql_fetch_assoc($result);
    $j_time = $row['j_time'];
    if (time() - $j_time > 90) {
        $identifier = '';
    }
    else {
        $identifier = $row['j_identifier'];
    }
    mysql_free_result($result);

    $deleteQuery = "DELETE FROM `$tableName` WHERE `j_username` = '$ip';";
    mysql_query($deleteQuery);

    cleanup();

    return $identifier;
}

function addTempIdentifierToDB($identifier) {
    global $tableName;

    init();

    $ip = wfGetIP();
    
    $insertQuery = "INSERT INTO `$tableName` (`j_identifier`, `j_username`) VALUES ('$identifier', '$ip');";
    mysql_query($insertQuery);

    cleanup();
}

function removeTempIdentifierFromDB() {
    global $tableName;

    init();

    $ip = wfGetIP();
    
    $deleteQuery = "DELETE FROM `$tableName` WHERE `j_username` = '$ip';";
    mysql_query($deleteQuery);

    cleanup();
}

function upgradeTempIdentifierInDB() {
    global $tableName;
    global $wgUser;

    init();

    $ip = wfGetIP();
    $username = $wgUser->getName();
    $updateQuery = "UPDATE `$tableName` SET `j_username`='$username' WHERE `j_username`='$ip';";
    mysql_query($updateQuery);

    cleanup();
}


function listIdsForUser($username) {
    global $wgOut;
    global $tableName;

    init();

    $findQuery = "SELECT * FROM `$tableName` WHERE `j_username` = '$username';";
    $result = mysql_query($findQuery);

    if (!$result) {
        $wgOut->addWikiText('<br />None yet!<br />');
        cleanup();
        return;
    }
    
    $wgOut->addHTML('<ul>');
    while ($row = mysql_fetch_assoc($result)) {
        $identifier = $row['j_identifier'];
        $id = $row['j_id'];
        $wgOut->addHTML("<li>$identifier - <a href='/index.php?title=Special:JanrainEngageSpecial&unlink=$id'>Unlink</a></li>");
    }
    $wgOut->addHTML('</ul>');
                    
    mysql_free_result($result);

    cleanup();
}

function removeRecord($id) {
    global $tableName;

    init();

    $deleteQuery = "DELETE FROM `$tableName` WHERE `j_id` = $id;";
    mysql_query($deleteQuery);

    cleanup();
}
