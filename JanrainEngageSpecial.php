<?php

class JanrainEngageSpecial extends SpecialPage {
        function __construct() {
                parent::__construct( 'JanrainEngage' );
                wfLoadExtensionMessages('JanrainEngage');
        }

        function execute( $par ) {
                require_once('JanrainEngageDB.php');
                require_once('JanrainEngageVars.php');

                global $wgRequest, $wgOut, $wgUser;
 
                $this->setHeaders();
                $wgOut->setPageTitle("Special:JanrainEngage");
 
                $token = $_POST['token'];

                if(strlen($token) == 40) {//test the length of the token; it should be 40 characters

                    global $wgJERpxApiKey;

                    //Use the token to make the auth_info API call
                    $post_data = array('token'  => $token,
                        'apiKey' => $wgJERpxApiKey,
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

                        global $wgArticlePath;
                        $login = '';
                        $special = '';
                        $placeHolder = '$1';
                        if ($wgArticlePath == '/index.php/$1') {
                            $login = '/index.php?title=$1';
                            $special = '/index.php/$1';
                        }
                        else {
                            $login = $wgArticlePath;
                            $special = $wgArticlePath;
                        }

                        //Use the identifier as the unique key to sign the user into the system.
                        //Extract the needed variables from the response
                        $profile = $auth_info['profile'];
                        $identifier = $profile['identifier'];
    
                        //okay, what are our cases here?
                        //first, is someone already logged in? then they are trying to add
                        if (!$wgUser->isAnon() && !array_key_exists($identifier, getIdsForUser($wgUser->getName()))) {
                            // best to make sure this is what they want to do
                            $wgOut->addWikiText("Is it okay to add this identifier to user ".$wgUser->getName()."?");
                            $wgOut->addWikiText("If not, you probably want to log out now and try again.");
                            $wgOut->addHTML("If so, <a href='".str_replace($placeHolder, "Special:JanrainEngage&confirm=true", $special)."'>confirm here</a>.");
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
                                $signupURL = str_replace($placeHolder, "Special:UserLogin&type=signup&returnto=Special:JanrainEngage", $login);
                                $loginURL = str_replace($placeHolder, "Special:UserLogin&nojanrain=true&returnto=Special:JanrainEngage", $login);
                               
                                $wgOut->addWikiText('You must attach this id to a user of this wiki:');
                                $wgOut->addHTML("<a href='$signupURL'>Create a new user</a><br />");
                                $wgOut->addHTML("<a href='$loginURL'>Log in an existing user</a>");
                                if ($username != 'SPECIALIP') {
                                    addTempIdentifierToDB($identifier);
                                }
                                return;
                            }
                            else {
                                //we know this user; let's set them up!
                                $wgUser->setID(User::idFromName($username));
                                $wgUser->loadFromDatabase();

                                global $JEIframeHTML;
                                $wgOut->addWikiText("Accounts currently linked to this user: ");
                                listIdsForUser($wgUser->getName());
                                $wgOut->addWikiText("Link another account:");
                                $wgOut->addHTML($JEIframeHTML);
                            }
                        }

                        $wgUser->setToken();
                        $wgUser->setCookies();
                        $wgUser->saveSettings();
                    }
                    else {
                        $wgOut->addWikiText("Nothing to see here, folks.");
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
                        global $JEIframeHTML;
                        upgradeTempIdentifierInDB();
                        $wgOut->addWikiText("Accounts currently linked to this user: ");
                        listIdsForUser($wgUser->getName());
                        $wgOut->addWikiText("Link another account:");
                        $wgOut->addHTML($JEIframeHTML);
                    }
                    else {
                        $wgOut->addWikiText("You must be logged in to use this page's functionality.");
                    }
                }
        }
}
