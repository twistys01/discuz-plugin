<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_mobile_setting.php 31700 2012-09-24 03:46:59Z zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_bbscoin extends discuz_table {

	public function __construct() {
		$this->_table = 'common_bbscoin';
		$this->_pk = 'orderid';

		parent::__construct();
	}

	public function fetch($orderid) {
		return DB::result_first('SELECT * FROM %t WHERE orderid=%s', array($this->_table, $orderid));
	}

	public function fetch_by_transaction_hash($transaction_hash) {
		return DB::result_first('SELECT * FROM %t WHERE transaction_hash=%s', array($this->_table, $transaction_hash));
	}

}