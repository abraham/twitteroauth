/*
 Navicat MySQL Data Transfer

 Source Server         : LOCAL
 Source Server Version : 50144
 Source Host           : localhost
 Source Database       : gegere_com

 Target Server Version : 50144
 File Encoding         : utf-8

 Date: 05/09/2013 20:17:59 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `rss_twitter_users`
-- ----------------------------
DROP TABLE IF EXISTS `rss_twitter_users`;
CREATE TABLE `rss_twitter_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oauth_provider` varchar(10) DEFAULT NULL,
  `oauth_uid` text,
  `oauth_token` text,
  `oauth_secret` text,
  `username` text,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `oauth_uid` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
