<?php

# where does the page live?
global $wgServer, $wgArticlePath;
if ($wgArticlePath == '') {
  $article = '/index.php/Special:JanrainEngage';
}
else {
  $placeHolder = "$1";
  $article = str_replace($placeHolder, "Special:JanrainEngage", $wgArticlePath);
}
$JESpecialPageURL = $wgServer.$article;
$search = array('/', ':', '?');
$replace = array('%2F', '%3A', '%3F');
$escapedJESpecialPageURL = str_replace($search, $replace, $JESpecialPageURL);

# how do we show the login box?
$JEIframeHTML = '<iframe src="http://mediawiki.rpxnow.com/openid/embed?token_url='.$escapedJESpecialPageURL.
        '" scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';
