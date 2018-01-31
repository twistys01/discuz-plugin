<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

CREATE TABLE IF NOT EXISTS pre_common_bbscoin (
  `orderid` char(50) NOT NULL,
  `transaction_hash` char(64) NOT NULL,
  `address` char(100) NOT NULL,
  `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`orderid`),
  UNIQUE `transaction_hash` (`transaction_hash`),
  KEY `address` (`address`, `dateline`)
) ENGINE=MyISAM;


EOF;

runquery($sql);

$finish = TRUE;
