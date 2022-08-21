# prjindigo-hp

This code is intended to be a functional website example of how to implement a Project Indigo honeypot.
These are the initial development commits; much more to come as this is fully implemented.

# A Word on the Code's Security

In the config.inc.php file you will find a few settings that should be changed immediately; each one is marked with a
comment on the same line indicating that they should be changed. While it is fine to have the "admin" user name
and password hard coded into this site, you should ABSOUTELY change them from "user" and "password", respectively,
*IMMEDIATELY*. The same goes for the LOGIN_COOKIE_NAME, which in this case is the output of a hash of random letters and
numbers. (Albeit, the "real" login will only provide access to statistical information and isn't really very useful
to anyone.

Similarly, the Project Indigo settings (account_id, secret hash, encryption key, and signing key) should be updated
prior to making the site available. As Project Indigo registration (as of August 21, 2022) *STILL* is not open to any one,
that makes this software technically useless. This has mainly due to my day job, but I have begun to work on all aspects
of the code for PI, prjindigo-hp, and pirag again recently.

# Requirements

This code is functional on Apache 2.4.6 and PHP 7.2.34 on CentOS 7 on a small virtual machine (1 vCPU, 1GB RAM, way more
storage than necessary) somewhere out in the Internet, so the system requirements are not very high. It does depend on
PHP's PDO database abstraction, Sodium encryption library, cURL, and a database. I currently have it running on SQLite3,
but I'm adapting the code to work with MariaDB/MySQL and PostgreSQL, though I wanted the requirements to be as minimal as
possible.
