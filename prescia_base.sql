# Host: localhost  (Version: 5.6.24)
# Date: 2015-05-07 12:11:09
# Generator: MySQL-Front 5.3  (Build 4.205)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "app_content"
#

DROP TABLE IF EXISTS `app_content`;
CREATE TABLE `app_content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) unsigned DEFAULT NULL,
  `code` tinyint(3) NOT NULL DEFAULT '1',
  `page` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lang` enum('pt-br','en') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `header` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `metakeys` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` mediumtext COLLATE utf8_unicode_ci,
  `publish` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  `ordem` int(10) DEFAULT NULL,
  `locked` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "app_content"
#

INSERT INTO `app_content` VALUES (1,NULL,1,'/contato','pt-br','contato',NULL,NULL,NULL,'Content Manager','y',NULL,'n'),(2,NULL,1,'/contato','en','contato',NULL,NULL,NULL,'Content Manager','y',NULL,'n'),(3,NULL,1,'/index','pt-br','index',NULL,NULL,NULL,'Content Manager','y',NULL,'n'),(4,NULL,1,'/index','en','index',NULL,NULL,NULL,'Content Manager','y',NULL,'n'),(5,NULL,2,'/index','pt-br','index 2',NULL,NULL,NULL,'Content Manager (index 2)','y',NULL,'n'),(6,NULL,2,'/index','en','index 2',NULL,NULL,NULL,'Content Manager (index 2)','y',NULL,'n'),(7,NULL,3,'/index','pt-br','index 3',NULL,NULL,NULL,'Content Manager (index 3)','y',NULL,'n'),(8,NULL,3,'/index','en','index 3',NULL,NULL,NULL,'Content Manager (index 3)','y',NULL,'n'),(9,NULL,4,'/index','pt-br','index 4',NULL,NULL,NULL,'Content Manager (index 4)','y',NULL,'n'),(10,NULL,4,'/index','en','index 4',NULL,NULL,NULL,'Content Manager (index 4)','y',NULL,'n'),(11,NULL,1,'/resources/reference','pt-br','resources/reference',NULL,NULL,NULL,'Content Manager','y',NULL,'n'),(12,NULL,1,'/resources/reference','en','resources/reference',NULL,NULL,NULL,'Content Manager','y',NULL,'n');

#
# Structure for table "appfunctions"
#

DROP TABLE IF EXISTS `appfunctions`;
CREATE TABLE `appfunctions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `holder` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `parameters` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `descriptionpt` text COLLATE utf8_unicode_ci,
  `autocallorder` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `internals` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  `quickref` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "appfunctions"
#


#
# Structure for table "auth_groups"
#

DROP TABLE IF EXISTS `auth_groups`;
CREATE TABLE `auth_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8_unicode_ci,
  `level` smallint(5) NOT NULL DEFAULT '0',
  `active` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "auth_groups"
#

INSERT INTO `auth_groups` VALUES (1,'Guest','a:30:{s:5:\"users\";s:34:\"1001001110000000000000000000000000\";s:6:\"groups\";s:34:\"1001001110000000000000000000000000\";s:15:\"session_manager\";s:34:\"1111111110000000000000000000000000\";s:3:\"seo\";s:34:\"1001111110000000000000000000000000\";s:7:\"bi_undo\";s:34:\"1111111110000000000000000000000000\";s:10:\"contentman\";s:34:\"1001111110000000000000000000000000\";s:5:\"forum\";s:34:\"1001111110000000000000000000000000\";s:11:\"forumthread\";s:34:\"1001001110000000000000000000000000\";s:9:\"forumpost\";s:34:\"1001001110000000000000000000000000\";s:6:\"bbmail\";s:34:\"1001001110000000000000000000000000\";s:5:\"stats\";s:34:\"1001001000000000000000000000000000\";s:10:\"statsdaily\";s:34:\"1001001000000000000000000000000000\";s:8:\"statsref\";s:34:\"1001001000000000000000000000000000\";s:13:\"statsrefdaily\";s:34:\"1001001000000000000000000000000000\";s:7:\"statsrt\";s:34:\"1001001000000000000000000000000000\";s:9:\"statspath\";s:34:\"1001001000000000000000000000000000\";s:12:\"statsbrowser\";s:34:\"1001001000000000000000000000000000\";s:8:\"statsres\";s:34:\"1001001000000000000000000000000000\";s:9:\"statsbots\";s:34:\"1001001000000000000000000000000000\";s:9:\"functions\";s:34:\"1001111110000000000000000000000000\";s:13:\"plugin_bi_dev\";s:9:\"000000000\";s:16:\"plugin_bi_groups\";s:9:\"000000000\";s:14:\"plugin_bi_auth\";s:9:\"000000000\";s:13:\"plugin_bi_seo\";s:9:\"000000000\";s:14:\"plugin_bi_undo\";s:9:\"000000000\";s:13:\"plugin_bi_adm\";s:15:\"000000000000000\";s:13:\"plugin_bi_cms\";s:15:\"000000000000000\";s:12:\"plugin_bi_bb\";s:15:\"000000000000000\";s:15:\"plugin_bi_stats\";s:15:\"000000000000000\";s:21:\"plugin_bi_permissions\";s:15:\"000000000000000\";}',0,'y'),(2,'Administrator','',90,'y'),(3,'Master Administrator','',100,'y'),(4,'Default User','',5,'y');

#
# Structure for table "auth_session"
#

DROP TABLE IF EXISTS `auth_session`;
CREATE TABLE `auth_session` (
  `id_user` int(11) unsigned NOT NULL,
  `revalidatecode` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `startdate` datetime NOT NULL,
  `lastaction` datetime DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "auth_session"
#


#
# Structure for table "auth_users"
#

DROP TABLE IF EXISTS `auth_users`;
CREATE TABLE `auth_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_group` int(11) unsigned NOT NULL,
  `name` varchar(124) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(124) COLLATE utf8_unicode_ci DEFAULT NULL,
  `login` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `active` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  `expiration_date` datetime DEFAULT NULL,
  `history` text COLLATE utf8_unicode_ci,
  `userprefs` text COLLATE utf8_unicode_ci,
  `image` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  `authcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `id_group` (`id_group`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "auth_users"
#

INSERT INTO `auth_users` VALUES (1,3,'Master',NULL,'master','adminprescia','y',NULL,'a:1:{i:0;a:6:{s:7:\"browser\";s:2:\"CH\";s:14:\"browserVersion\";s:4:\"40.0\";s:10:\"browserTag\";s:108:\"Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36\";s:4:\"time\";s:19:\"2015-02-03 17:41:03\";s:10:\"resolution\";s:8:\"1600x900\";s:2:\"ip\";s:21:\"0:0:0:0:0:ffff:7f00:1\";}}','a:6:{s:4:\"skin\";s:4:\"base\";s:4:\"init\";s:5:\"index\";s:4:\"pfim\";i:30;s:2:\"sf\";i:1;s:8:\"floating\";i:0;s:4:\"lang\";s:2:\"en\";}','n',NULL),(2,2,'Administrador',NULL,'admin','adminprescia','y',NULL,NULL,NULL,'n',NULL);

#
# Structure for table "bb_forum"
#

DROP TABLE IF EXISTS `bb_forum`;
CREATE TABLE `bb_forum` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lang` enum('pt-br','en') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `introduction` text COLLATE utf8_unicode_ci,
  `urla` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ordem` int(10) NOT NULL DEFAULT '0',
  `operationmode` enum('bb','blog','articles') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'bb',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "bb_forum"
#


#
# Structure for table "bb_mail"
#

DROP TABLE IF EXISTS `bb_mail`;
CREATE TABLE `bb_mail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_author` int(11) unsigned NOT NULL,
  `id_recipient` int(11) unsigned NOT NULL,
  `id_responsefrom` int(11) unsigned DEFAULT NULL,
  `outbox` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `date` datetime DEFAULT NULL,
  `dateseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "bb_mail"
#


#
# Structure for table "bb_post"
#

DROP TABLE IF EXISTS `bb_post`;
CREATE TABLE `bb_post` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_forum` int(11) unsigned NOT NULL,
  `id_forumthread` int(11) unsigned NOT NULL,
  `id_author` int(11) unsigned NOT NULL,
  `content` mediumtext COLLATE utf8_unicode_ci,
  `date` datetime NOT NULL,
  `id_whoflagged` int(11) unsigned DEFAULT NULL,
  `props` mediumtext COLLATE utf8_unicode_ci,
  `ip` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `includehtml` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "bb_post"
#


#
# Structure for table "bb_thread"
#

DROP TABLE IF EXISTS `bb_thread`;
CREATE TABLE `bb_thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_forum` int(11) unsigned NOT NULL,
  `is_featured` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `video` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  `date` datetime NOT NULL,
  `tags` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `urla` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastupdate` datetime DEFAULT NULL,
  `id_author` int(11) unsigned NOT NULL,
  `publish_after` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "bb_thread"
#


#
# Structure for table "stats_bots"
#

DROP TABLE IF EXISTS `stats_bots`;
CREATE TABLE `stats_bots` (
  `data` date NOT NULL,
  `hits` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_bots"
#


#
# Structure for table "stats_browser"
#

DROP TABLE IF EXISTS `stats_browser`;
CREATE TABLE `stats_browser` (
  `data` date NOT NULL,
  `browser` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `hits` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`data`,`browser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_browser"
#

INSERT INTO `stats_browser` VALUES ('2015-02-03','Chrome 40.0.2214.93',10),('2015-02-27','Chrome 40.0.2214.115',2),('2015-03-06','Chrome 41.0.2272.76',2),('2015-03-13','Chrome 40.0.2214.115',3),('2015-03-16','Chrome 41.0.2272.89',2);

#
# Structure for table "stats_hitsd"
#

DROP TABLE IF EXISTS `stats_hitsd`;
CREATE TABLE `stats_hitsd` (
  `data` date NOT NULL,
  `hour` tinyint(3) NOT NULL,
  `page` varchar(72) COLLATE utf8_unicode_ci NOT NULL,
  `hid` int(10) NOT NULL DEFAULT '0',
  `lang` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pt-br',
  `hits` smallint(5) NOT NULL DEFAULT '1',
  `uhits` smallint(5) NOT NULL DEFAULT '0',
  `bhits` smallint(5) NOT NULL DEFAULT '0',
  `rhits` smallint(5) NOT NULL DEFAULT '0',
  `ahits` smallint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`data`,`hour`,`page`,`hid`,`lang`),
  KEY `page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_hitsd"
#

INSERT INTO `stats_hitsd` VALUES ('2015-02-27',16,'index',0,'en',1,1,0,0,0),('2015-02-27',17,'index',0,'en',1,1,0,0,0),('2015-03-06',18,'index',0,'en',2,1,1,0,0),('2015-03-13',15,'bb/index',0,'en',1,0,1,0,0),('2015-03-13',15,'index',0,'en',2,1,1,0,0),('2015-03-16',14,'index',0,'en',1,0,0,0,0),('2015-03-16',23,'index',0,'en',1,1,0,0,0);

#
# Structure for table "stats_hitsh"
#

DROP TABLE IF EXISTS `stats_hitsh`;
CREATE TABLE `stats_hitsh` (
  `data` date NOT NULL,
  `page` varchar(72) COLLATE utf8_unicode_ci NOT NULL,
  `hid` int(10) NOT NULL DEFAULT '0',
  `lang` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pt-br',
  `hits` smallint(5) NOT NULL DEFAULT '1',
  `uhits` smallint(5) NOT NULL DEFAULT '0',
  `bhits` smallint(5) NOT NULL DEFAULT '0',
  `rhits` smallint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`data`,`page`,`hid`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_hitsh"
#

INSERT INTO `stats_hitsh` VALUES ('2015-02-03','index',0,'en',8,1,1,0),('2015-02-03','index',0,'pt-br',1,0,0,0),('2015-02-03','resources/reference',0,'pt-br',1,0,0,0);

#
# Structure for table "stats_path"
#

DROP TABLE IF EXISTS `stats_path`;
CREATE TABLE `stats_path` (
  `data` date NOT NULL,
  `page` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pagefoward` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hits` smallint(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`data`,`page`,`pagefoward`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_path"
#

INSERT INTO `stats_path` VALUES ('2015-03-06','index','index',1),('2015-03-13','bb/index','index',1),('2015-03-13','index','bb/index',1),('2015-03-16','index','index',1);

#
# Structure for table "stats_refererd"
#

DROP TABLE IF EXISTS `stats_refererd`;
CREATE TABLE `stats_refererd` (
  `data` date NOT NULL,
  `referer` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entrypage` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hits` smallint(5) NOT NULL DEFAULT '1',
  `pages` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`data`,`referer`,`entrypage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_refererd"
#

INSERT INTO `stats_refererd` VALUES ('2015-03-16','','index',1,',');

#
# Structure for table "stats_refererh"
#

DROP TABLE IF EXISTS `stats_refererh`;
CREATE TABLE `stats_refererh` (
  `data` date NOT NULL,
  `referer` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entrypage` varchar(72) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hits` smallint(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`data`,`referer`,`entrypage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_refererh"
#


#
# Structure for table "stats_resolution"
#

DROP TABLE IF EXISTS `stats_resolution`;
CREATE TABLE `stats_resolution` (
  `data` date NOT NULL,
  `resolution` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `hits` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`data`,`resolution`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_resolution"
#

INSERT INTO `stats_resolution` VALUES ('2015-02-03','1600x900',11),('2015-02-27','1600x900',2),('2015-03-06','1920x1080',2),('2015-03-13','1600x900',3),('2015-03-16','1600x900',2);

#
# Structure for table "stats_rt"
#

DROP TABLE IF EXISTS `stats_rt`;
CREATE TABLE `stats_rt` (
  `data` datetime NOT NULL,
  `data_ini` datetime DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `page` varchar(72) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pagelast` varchar(72) COLLATE utf8_unicode_ci DEFAULT NULL,
  `agent` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `agentcode` enum('IE','FF','SA','OP','CH','KO','MO','UN') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UN',
  `fullpath` text COLLATE utf8_unicode_ci,
  `referer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "stats_rt"
#

INSERT INTO `stats_rt` VALUES ('2015-03-16 23:11:34','2015-03-16 23:11:34','0:0:0:0:0:ffff:7f00:1','index','index','Chrome 41.0.2272.89','CH','index,','');

#
# Structure for table "sys_seo"
#

DROP TABLE IF EXISTS `sys_seo`;
CREATE TABLE `sys_seo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lang` enum('pt-br','en') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `meta` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `metakey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publicar` enum('y','n') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y',
  `redirectmode` enum('normal','sr_temporary','sr_permanent') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "sys_seo"
#


#
# Structure for table "sys_undo"
#

DROP TABLE IF EXISTS `sys_undo`;
CREATE TABLE `sys_undo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `modulo` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `event` enum('update','delete') COLLATE utf8_unicode_ci NOT NULL,
  `ids` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `history` longtext COLLATE utf8_unicode_ci NOT NULL,
  `files` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `data` datetime DEFAULT NULL,
  `id_author` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Data for table "sys_undo"
#

