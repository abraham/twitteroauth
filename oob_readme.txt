
Step One:
   Edit config.php, to set:
      define('OAUTH_CALLBACK', 'oob');

  (If not already done, go to https://dev.twitter.com/apps, create and configure the app, and add the consumer key and secret to config.php.)

Step Two:
   php oob1.php

Step Three:
   Visit the URL it tells you to, and approve the application

Step Four:
   php oob2.php 1234567
  (where 1234567 is the PIN number you got at the end of step three)

Step Five:
   php oob3.php account/verify_credentials
 (will show your account; see the source for other supported commands, and some shortcuts.)
