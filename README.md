# prjindigo-hp

This code is intended to be a functional website example of how to implement a Project Indigo honeypot.
These are the initial development commits; much more to come as this is fully implemented.

#A Word on the Code's Security

In the config.inc.php file you will find a few settings that should be changed immediately; each one is marked with a
comment on the same line indicating that they should be changed. While it is fine to have the "admin" user name
and password hard coded into this site, you should ABSOUTELY change them from "user" and "password", respectively,
*IMMEDIATELY*. The same goes for the LOGIN_COOKIE_NAME, which in this case is the output of a hash of random letters and
numbers. (Albeit, the "real" login will only provide access to statistical information and isn't really very useful
to anyone.
