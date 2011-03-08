<?php
 
$wgExtensionCredits['other'][] = array(
  'name' => 'JanrainEngage',
  'version' => '0.0001',
  'author' => '[http://www.mediawiki.org/wiki/User:vasavage vasavage]',
  'url' => 'http://www.mediawiki.org/wiki/Extension:JanrainEngage',
  'description' => 'bla blah blah'
);

$wgHooks['BeforePageDisplay'][] = 'onBeforePageDisplay' ;
$wgHooks['PersonalUrls'][] = 'onPersonalUrls';

function onBeforePageDisplay( &$out, &$sk ) {
    $out->addScript( "<script type=\"text/javascript\">
  var rpxJsHost = ((\"https:\" == document.location.protocol) ? \"https://\" : \"http://static.\");
  document.write(unescape(\"%3Cscript src='\" + rpxJsHost +
\"rpxnow.com/js/lib/rpx.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
  RPXNOW.overlay = true;
  RPXNOW.language_preference = 'en';
</script>" );

    return true;
}

function onPersonalUrls( &$personal_urls, &$title ) {
    $personal_urls['janrain_login'] = array(
      'text' => 'JANRAIN LOGIN',
      'href' => 'https://mediawiki.rpxnow.com/openid/v2/signin?token_url=http%3A%2F%2F192.168.0.11',
      'onclick' => 'return false;'
    );
    return true;
}
