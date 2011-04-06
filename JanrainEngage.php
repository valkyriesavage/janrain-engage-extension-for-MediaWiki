<?php

if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install JanrainEngage, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/JanrainEngage/JanrainEngage.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['other'][] = array(
  'name' => 'JanrainEngage',
  'version' => '0.1',
  'author' => '[http://www.mediawiki.org/wiki/User:parmesan002  parmesan002]',
  'url' => 'http://www.mediawiki.org/wiki/Extension:JanrainEngage',
  'description' => 'An extension for MediaWiki that allows single-signon through Google, Twitter,
                    Facebook, OpenID, Linkedin, and Yahoo!.  It is powered by JanrainEngage.'
);

$wgHooks['UserLoginForm'][] = 'addJanrainLoginLink';
$wgHooks['UserLogout'][] = 'removeTempDBEntries';

require_once('JanrainEngageDB.php');
require_once('JanrainEngageVars.php');

function addJanrainLoginLink(&$template) {
    if ($_GET['nojanrain']) {
        return true;
    }
    global $wgOut, $JEIframeHTML;
    $wgOut->addHTML($JEIframeHTML);
    return true;
}

function removeTempDBEntries(&$user) {
    removeTempIdentifierFromDB();
    return true;
}

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['JanrainEngageSpecial'] = $dir.'JanrainEngageSpecial.php';
$wgExtensionMessagesFiles['JanrainEngage'] = $dir . 'JanrainEngage.i18n.php';
$wgExtensionAliasesFiles['JanrainEngage'] = $dir . 'JanrainEngage.alias.php';
$wgSpecialPages['JanrainEngage'] = 'JanrainEngageSpecial';
$wgSpecialPageGroups['JanrainEngage'] = 'other';
