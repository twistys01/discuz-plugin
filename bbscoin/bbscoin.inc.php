<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, array(), array('login' => 1));
}
global $_G;

function _config() {
	global $_G;
    if (!isset($_G['cache']['plugin'])) {
        loadcache('plugin');
    }
    $config = $_G['cache']['plugin']['bbscoin'];
	return $config;		
}
$config = _config();

if (!$config['bbscoin_wallet_address']) {
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s11'));
}

//提交表单
if(submitcheck('addfundssubmit')){

    $amount = $_POST['addfundamount'];

    if($amount < 1) {
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s1').'1');
    }

    $orderid = dgmdate(TIMESTAMP, 'YmdHis').random(3);
    $transaction_hash = trim($_POST['transactionhash']);

    if(discuz_process::islocked('pay_bbscoin_'.$_G['uid'], 10)) {
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s4'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    $transaction_info = C::t('#bbscoin#common_bbscoin')->fetch_by_transaction_hash($transaction_hash);

    if($transaction_info['transaction_hash']) {
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s3'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    $need_bbscoin = ceil((($amount / $config['pay_ratio']) * 100)) / 100;

    if(!$orderinfo) {
        $orderinfo = array(
        	'orderid' => $orderid,
        	'status' => '1',
        	'uid' => $_G['uid'],
        	'amount' => $amount,
        	'price' => $need_bbscoin,
        	'submitdate' => $_G['timestamp'],
        );
        C::t('forum_order')->insert($orderinfo);
    }

    $req_data = array(
      "params" => array(
      	"transactionHash" => $transaction_hash
      ),
      "jsonrpc" => "2.0",
      "method" => "getTransaction"
    );

    $result = getUrlContent($config['bbscoin_walletd'], json_encode($req_data)); 
    $rsp_data = json_decode($result, true);

    $status_req_data = array(
      "jsonrpc" => "2.0",
      "method" => "getStatus"
    );

    $result = getUrlContent($config['bbscoin_walletd'], json_encode($status_req_data)); 
    $status_rsp_data = json_decode($result, true);

    $blockCount = $status_rsp_data['result']['blockCount'];
    $transactionBlockIndex = $rsp_data['result']['transaction']['blockIndex'];
    $confirmed = $blockCount - $transactionBlockIndex + 1;
    if ($blockCount <= 0 || $transactionBlockIndex <= 0 || $confirmed <= $config['confirmed_blocks']) {
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s13').$config['confirmed_blocks'], '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    $trans_amount = 0;
    if ($rsp_data['result']['transaction']['transfers']) {
        foreach ($rsp_data['result']['transaction']['transfers'] as $transfer_item) {
            if ($transfer_item['address'] == $config['bbscoin_wallet_address']) {
                $trans_amount += $transfer_item['amount'];
            }
        }
    }

    $trans_amount = $trans_amount / 100000000;

    if ($trans_amount == $need_bbscoin) {
    	C::t('forum_order')->update($orderid, array('status' => '2', 'confirmdate' => $_G['timestamp']));
        C::t('#bbscoin#common_bbscoin')->insert(
            array(
                'orderid' => $orderid,
                'transaction_hash' => $transaction_hash,
                'address' => '',
                'dateline' => $_G['timestamp'],
            )
        );
    	updatemembercount($_G['uid'], array($config['pay_credit'] => $orderinfo['amount']), 1, 'AFD', $_G['uid']);

    	notification_add($_G['uid'], 'system', 'system_notice', array('subject' => lang('plugin/bbscoin', 'pay_lang_s9'), 'message' => lang('plugin/bbscoin', 'pay_lang_s8').$orderid, 'from_id' => 0, 'from_idtype' => 'sendnotice'), 1);

        echo  "<script language=\"javascript\" type=\"text/javascript\">window.location.href='".$_G['siteurl']."forum.php?mod=misc&action=paysucceed'</script>";
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
    } else {
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
        showmessage(lang('plugin/bbscoin', 'pay_lang_s2'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    exit();
} elseif(submitcheck('addcoinssubmit') && $config['pay_to_bbscoin']){
    $amount = $_POST['addcoinamount'];
    $need_point = ceil((($amount / $config['pay_to_coin_ratio']) * 100)) / 100;

    if ($need_point < 1) {
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s1').'1');
    }

    $walletaddress = trim($_POST['walletaddress']);

    if ($config['bbscoin_wallet_address'] == $walletaddress) {
        showmessage(lang('plugin/bbscoin', 'pay_lang_s5'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    $real_price = $amount * 100000000 - 1000000;

    if ($real_price <= 0) {
        showmessage(lang('plugin/bbscoin', 'pay_lang_s12'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    if(discuz_process::islocked('pay_bbscoin_'.$_G['uid'], 10)) {
    	showmessage(lang('plugin/bbscoin', 'pay_lang_s4'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    if ($need_point > getuserprofile('extcredits'.$config['pay_credit'])) {
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
        showmessage(lang('plugin/bbscoin', 'pay_lang_s10'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    $orderid = dgmdate(TIMESTAMP, 'YmdHis').random(3);

    $orderinfo = array(
    	'orderid' => $orderid,
    	'status' => '1',
    	'uid' => $_G['uid'],
    	'amount' => $need_point,
    	'price' => $amount,
    	'submitdate' => $_G['timestamp'],
    );
    C::t('forum_order')->insert($orderinfo);

    $req_data = array(
      'params' => array(
          'anonymity' => 0,
          'fee' => 1000000,
          'unlockTime' => 0,
          'changeAddress' => $config['bbscoin_wallet_address'],
          "transfers" => array(
           0 => array(
                'amount' => $real_price,
                'address' => $walletaddress,
            )
          )
      ),
      "jsonrpc" => "2.0",
      "method" => "sendTransaction"
    );

    $result = getUrlContent($config['bbscoin_walletd'], json_encode($req_data)); 
    $rsp_data = json_decode($result, true);

    $trans_amount = 0;
    if ($rsp_data['result']['transactionHash']) {
    	C::t('forum_order')->update($orderid, array('status' => '2', 'confirmdate' => $_G['timestamp']));
        C::t('#bbscoin#common_bbscoin')->insert(
            array(
                'orderid' => $orderid,
                'transaction_hash' => $rsp_data['result']['transactionHash'],
                'address' => $walletaddress,
                'dateline' => $_G['timestamp'],
            )
        );
    	updatemembercount($_G['uid'], array($config['pay_credit'] => -$need_point), 1, 'AFD', $_G['uid']);

    	notification_add($_G['uid'], 'system', 'system_notice', array('subject' => lang('plugin/bbscoin', 'pay_lang_s7'), 'message' => lang('plugin/bbscoin', 'pay_lang_s6').$orderid.', '.$rsp_data['result']['transactionHash'], 'from_id' => 0, 'from_idtype' => 'sendnotice'), 1);

        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
        showmessage(lang('plugin/bbscoin', 'pay_lang_s6').$orderid.', '.$rsp_data['result']['transactionHash'], '', array(), array('alert' => 'right', 'showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    } else {
        discuz_process::unlock('pay_bbscoin_'.$_G['uid']);
        showmessage(lang('plugin/bbscoin', 'pay_lang_s5'), '', array(), array('showdialog' => 1, 'showmsg' => true, 'closetime' => true));
    }

    exit();

}


function getUrlContent($url, $data_string) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BBSCoin');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $data;
}