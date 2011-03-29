<?php

class JanrainEngageSpecial extends SpecialPage {
        function __construct() {
                parent::__construct( 'JanrainEngage' );
                wfLoadExtensionMessages('JanrainEngage');
        }

        function execute( $par ) {
                require_once('JanrainEngageDB.php');
                global $wgRequest, $wgOut, $wgUser;
 
                $this->setHeaders();
                $wgOut->setPageTitle("Special:JanrainEngage");
 
                $rpx_api_key = 'e21e876daa79d816cb8f10cabbad27c71faa0766';
                $token = $_POST['token'];

                if(strlen($token) == 40) {//test the length of the token; it should be 40 characters

                    //Use the token to make the auth_info API call
                    $post_data = array('token'  => $token,
                        'apiKey' => $rpx_api_key,
                        'format' => 'json',
                        'extended' => 'true'); //Extended is not available to Basic.

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_URL, 'https://rpxnow.com/api/v2/auth_info');
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_FAILONERROR, true);
                    $result = curl_exec($curl);

                    if ($result == false){
                        error_log("\n".'Curl error: ' . curl_error($curl));
                        error_log("\n".'HTTP code: ' . curl_errno($curl));
                        error_log("\n".var_dump($post_data));
                    }

                    curl_close($curl);

                    //Parse the JSON auth_info response
                    $auth_info = json_decode($result, true);

                    if ($auth_info['stat'] == 'ok') {

                        //Use the identifier as the unique key to sign the user into the system.
                        //Extract the needed variables from the response
                        $profile = $auth_info['profile'];
                        $identifier = $profile['identifier'];
    
                        //okay, what are our cases here?
                        //first, is someone already logged in? then they are trying to add
                        if (!$wgUser->isAnon()) {
                            // best to make sure this is what they want to do
                            $wgOut->addWikiText("Is it okay to add this identifier to user ".$wgUser->getName()."?");
                            $wgOut->addWikiText("If not, you probably want to log out now and try again.");
                            $wgOut->addHTML("If so, <a href='/index.php?title=Special:JanrainEngageSpecial&confirm=true'>confirm here</a>.");
                            addTempIdentifierToDB($identifier);
                        }
                        //otherwise, we need to know if we remember them
                        else {
                            $username = usernameFromIdentifier($identifier);

                            if (preg_match("/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/", $username)) {
                                // the user did something wrong... let's send them through again
                                // they still are logged in with an IP address
                                $username = 'SPECIALIP';
                            }

                            if ($username == '' or $username == 'SPECIALIP') {
                                // okay, they aren't in the DB.  what user should we be using here?
                                $wgOut->addWikiText('You must attach this id to a user of this wiki:');
                                $wgOut->addHTML("<a href='/index.php?title=Special:UserLogin&type=signup&returnto=Special:JanrainEngageSpecial'>Create a new user</a><br />");
                                $wgOut->addHTML("<a href='/index.php?title=Special:UserLogin&nojanrain=true&returnoto=Special:JanrainEngageSpecial'>Log in an existing user</a>");
                                if ($username != 'SPECIALIP') {
                                    addTempIdentifierToDB($identifier);
                                }
                                return;
                            }
                            else {
                                //we know this user; let's set them up!
                                $wgUser->setID(User::idFromName($username));
                                $wgUser->loadFromDatabase();

                                $wgOut->addWikiText("Accounts currently linked to this user: ");
                                listIdsForUser($wgUser->getName());
                                $wgOut->addWikiText("Link another account:");
                                $wgOut->addHTML('<iframe src="http://mediawiki.rpxnow.com/openid/embed?token_url=http%3A%2F%2Fdev.groupaya.net%2Findex.php%3Ftitle%3DSpecial%3AJanrainEngageSpecial" scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>');
                            }
                        }

                        $wgUser->setToken();
                        $wgUser->setCookies();
                        $wgUser->saveSettings();
                    }
                    else {
                        $wgOut->addWikiText("Nothing to see here, folks, just something broken...");
                    }
                }
                else {
                    if ($_GET['unlink']) {
                        // we are here to remove stuff from the DB
                        removeRecord($_GET['unlink']);
                    }
                    elseif ($_GET['confirm']) {
                        upgradeTempIdentifierInDB();
                    }

                    if (!$wgUser->isAnon()) {
                        $wgOut->addWikiText("Accounts currently linked to this user: ");
                        listIdsForUser($wgUser->getName());
                        $wgOut->addWikiText("Link another account:");
                        $wgOut->addHTML('<iframe src="http://mediawiki.rpxnow.com/openid/embed?token_url=http%3A%2F%2Fdev.groupaya.net%2Findex.php%3Ftitle%3DSpecial%3AJanrainEngageSpecial" scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>');
                    }
                    else {
                        $wgOut->addWikiText("You must be logged in to use this page's functionality.");
                    }
                }
        }
}
