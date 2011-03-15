<?php
 
$wgExtensionCredits['other'][] = array(
  'name' => 'JanrainEngage',
  'version' => '0.1',
  'author' => '[http://www.mediawiki.org/wiki/User:parmesan002  parmesan002]',
  'url' => 'http://www.mediawiki.org/wiki/Extension:JanrainEngage',
  'description' => 'An extension for MediaWiki that allows single-signon through Google, Twitter,
                    Facebook, OpenID, Linkedin, and Yahoo!.  It is powered by JanrainEngage.'
);

$wgHooks['UserLoginForm'][] = 'addJanrainLoginLink';
$wgHooks['UserLoginComplete'][] = 'createDBMapping';
$wgHooks['UserLogout'][] = 'removeTempDBEntries';

require_once('JanrainEngageDB.php');

function addJanrainLoginLink(&$template) {
    if ($_GET['nojanrain']) {
        return true;
    }

    global $wgOut;
    $wgOut->addHTML('<iframe src="http://mediawiki.rpxnow.com/openid/embed?token_url=http%3A%2F%2Fdev.groupaya.net%2Findex.php%3Ftitle%3DSpecial%3AJanrainEngageSpecial" scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>');

    return true;
}

function createDBMapping(&$user, &$inject_html) {
    $identifier = getIdentifierFromDB();
    addIdAndUserToDB($identifier, $user->getName());

    return true;
}

function removeTempDBEntries(&$user) {
    removeTempIdentifierFromDB();

    return true;
}

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['JanrainEngageSpecial'] = $dir.'JanrainEngageSpecial.php';
$wgExtensionMessagesFiles['JanrainEngageSpecial'] = $dir . 'JanrainEngageSpecial.i18n.php';
$wgExtensionAliasesFiles['JanrainEngageSpecial'] = $dir . 'JanrainEngageSpecial.alias.php';
$wgSpecialPages['JanrainEngageSpecial'] = 'JanrainEngageSpecial';
