// $Id$

Login Security
--------------

This module was developed to improve the security options in the login operation
of a drupal site. By default, drupal has only basic access control deniying IP
access to the full content of the site. 

With Login security a site administrator may add two types of access control to 
the login forms (default and block). These are the features included:

 Soft Protections:

 - Request Time delay: On any failed login, a time delay in included to the submit
   request, hardenning the bruteforce attack to the login form.
 - Block login forms or requests, when the protection flag is enabled the form is
   never submited, and any request even with a valid form token ID will be dropped,
   but the host still can access the site.
   
 Hard Protections:   
 - Block account: on a number of failed attempts, the account can be blocked.
 - Block IP: on a number of failed attempts, a host may be added to the access 
   control list.

Notes: 
 - soft block for hosts will expire in the ammount of hours configured.
 - hard blocke hosts will not be removed from list. It should be done by manually.
 - blocked accounts will not be removed from block list. It should be donde manually.

The session ID (php session neither drupal's session) is not taking in count for 
the security operations, as automated bruteforce tool may request new sessions on 
any attempt, ignoring the session fixation from the server.



Installation
------------

To install copy the login_security folder in your modules directory. Go to 
Administer -> Site Building -> Modules and under the "Other" frame the 
"Login Security" module Item will appear in the list. Enable the Checkbox
and save the configuration.



Configuration
-------------

Go to Administer -> User Management -> User Settings and new box will appear
close to the registering information with the following options. Any value
set to 0 will disable that option.

 - Track time: The time window where to check for security violiations. It's,
   the time in hours the login information is kept to compute the login attempts
   count. A common example could be 24 hours. After that time, the attempt is
   deleted from the list, so it will not count.

 - Delay on login fail: Delay in seconds for the next login count. It's computed
   if the form:  host & user, so any attempt to login again with the same user
   from the same IP address will be delayed that number of seconds. The time
   the user has to wait for the next login operation is (attempts * delay), and
   the number of attempts is counted within the "Track time" time value. In the 
   previous example of 24 hours, after 24h the login attemps will be cleared, 
   and the delay decreased.
   
 - Soft block host on login fail: After that number of attempts to login from 
   that IP address, no matter the username used, the host will not be allowed to 
   submit the login form again, but the content of the site is still accesible
   for that IP address. The block will start to clear of counts after the 
   "Track Time" time window.

 - Hard block host on login fail: As the soft block, but this time the IP address
   will be banned from the site, and included in the access list as a deny rule.
   To remove the IP from the list you will have to go to:
   Administer -> User Management -> Access Rules.
   
 - Block user on login fail: It's that easy, after N attempts of login as a user
   no matter the IP address attempting to, the user will be blocked. To remove
   the blocking of the user, you will have to go to:
   Administer -> User Management -> Users  


 The flow will happend like described:

1st -  On any login, the pair host<->username is saved for security, and only 
on a successfull login, the pair host-username is deleted from the security log. 

2nd - For the delay operation, the host and the username are taken in count. 
This way, your login time will not be affected is someone is attempting to brute 
the account.

3rd - For the soft blocking operation, any failed attempt from that host is 
being count, and when the number of attempts exceeds, the host is not allowed 
to submit the form.

Note: (2nd and 3rd impose restrictions to the login form, and the time these 
restrictions are in rule is the time the information is being tracked: "Track Time"). 

4th For the user blocking operation, any failed attempt is count, so no matter 
what the source IP address is, when too many attempts appear the account is 
blocked. A successful login, even if the user is blocked will remove any tracking 
entry fron the database.

5th For the host blocking operation, only the host is taken in count. When too 
many attempts appear, no matter the username being tested, the host IP address 
is banned.

Note: (4th and 5th operations are not being cancelled automatically).
Note: The tracking entries in the database for any host <-> username pair are 
      being deleted on: login, update and delete. 



Notifications
-------------

Thanks to christefano, the module now accepts configurable notifications for the
actions. These are the available placeholders for the displayed messages:

 - In site "host is banned" message (soft and hard):
   %ip        : banned IP address
   
 - In site message when a user is blocked:
   %username  : the user name blocked

 - Email to admin: dynamic strings in subject and body
   %username  : the user name blocked
   %site      : the name of the site
   %uri       : site base url
   %uri_brief : clean site url
   %email     : blocked user email
   %date      : current date/time
   %edit_uri  : direct link to edit blocked user
   
 If you want your users to be informed when it's account has been blocked, you can use the
 module "Extended user status notifications": http://www.drupal.org/project/user_status
   

Other modules interaction
-------------------------

Thanks to christefano, now the login_security fits well in any drupal installation allowing
without breaking other module operations. The only interaction being performed for just 
blocks a user, so to switch the user status an update hook is launched. Other modules can
now react to that hook.

This module doesn't include the option to notify the user when it's account is blocked. You
can use the module: "Extended user status notifications" for that operation.


Future Roadmap
--------------

In a future a better bruteforce and control function is to be included, so 
database is now ready for this future update. A fuzzy dataminner will be able 
to detect bruteforce attacks no matter the time and scope.

Why is not password policy included in this module? Because of:

http://drupal.org/project/password_policy

There exists a module for that, may be you should encourage it's mantainer to
update the code for 5.x and 6.x branches of drupal.

Thanks to..
-----------

Christefano has done a great job helping with the code cleanup and the string
cleaning.. my english is not that good yet!. The module has more options 
now! Thanks dude!


ilo [at] reversing.org
christefano can be located at http://parahuman.org/contact/
