# Prescia
Full-stack high-level LAMP Framework

Prescia is an advanced LAMP (Linux, Apache, mySQL, PHP) framework based on over 10 years of work by Caio Vianna de Lima Netto,
serving over 500 sites worldwide during it's production and non-open stage, Prescia is actually the 4th iteration of the
framework, based on yet another work based on the same author around 2006.

On an internet filled with plenty of multiple PHP frameworks, Prescia is different for it's high level (automation) and
simplicity, as well being a framework oriented to creating multiple sites on the same plataform (which mean you can deliver
multiple domains/sites with the same install, with no duplicity). The idea was born when internet agencies had the need to
deliver and maintain multiple sites a month, with the least redundancy of code possible.

With time, several features on code optimization, safety, simplicity and ease of implementation were inserted into the framework
(plenty features were used and eventually deprecated), and a lot of code changed or optimized to provide the best for all the
customers on the same install, while preserving the code clean and robust enough for any other number of applications of
installs.

It's worth to note that, being a high level framrwork, Prescia makes use of several third party components to abstract parts
of the framework that are outside the main scope, like libraries for javascript (prototypejs, jQuery, ckeditor), style
"frameworks" like Bootstrap, or many other open source codes like adodb_time.

If you know other frameworks an wish to know more about how Prescia offers a new perspective, check our differential page,
and also how the MVC approach is tweaked. One thing is certain, Prescia is quite different from your usual framework, and
certainly is a love-it or leave-it work.

Copyright is New BSD License / BSD-new, free for use.

Basic install:
1. Unzip in desired folder (Prescia was developed to work on root, but can be tweaked to work on a sub-folder)
2. Rename the files below:
  config/domains.original to config/domains
  config/settings.php.original to config/settings.php
3. Edit the above files with your desired settings
  Mandatory changes: CONS_MASTERPASS and CONS_MASTERMAIL
  Suggested important changes: date_default_timezone_set(), CONS_HTTPD_ERRFILE, CONS_OVERRIDE_DB, 
    CONS_OVERRIDE_DBUSER, CONS_OVERRIDE_DBPAS
4. Fire it up
