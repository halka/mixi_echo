<?php
/********************************************************************************************

2009/5/8 1.1.0 自分への返信も取れるようにしました。
2009/5/15 1.1.1 my_replyとrecentの一部を訂正。
2009/5/25 1.1.2 postとreplyが同じだったので区別．取得したログを最初から文字コード変換するようにした．user_echoにおいて配列の添え字が抜けていたところを訂正．
2009/7/7 1.1.3 login()をコンストラクタに変更．

mixiエコーにポストしたり、タイムラインをパースして返します。
simplehtmldomを使ってパースしています。http://sourceforge.net/projects/simplehtmldom/
pearのHTTP/Clientも必要です。
a_halka
電子メイル : halka.rjch@gmail.com
Website : http://www.rw12.net/
Twitter : http://twitter.com/a_halka

よく分からないのでMITライセンスにしておきます。

Copyright (c) 2009 a_halka
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*********************************************************************************************/
require_once 'HTTP/Client.php';
include 'lib/simple_html_dom.php';

class mixi_echo{
	var $login_url='http://mixi.jp/login.pl';
	var $recent='http://mixi.jp/recent_echo.pl';
	var $my_reply='http://mixi.jp/res_echo.pl';
	var $user_echo='http://mixi.jp/list_echo.pl?id=';
	var $post_echo='http://mixi.jp/add_echo.pl';
	var $_email;
	var $_pass;
	var $_post_key;
	var $env_encode='utf-8';
	var $mixi_encode='euc-jp';
	
	function __construct($email,$pass,$post_key){
	$this->_email=$email;
	$this->_pass=$pass;
	$this->_post_key=$post_key;
	unset($email);unset($pass);unset($post_key);
	}
	
	function recent(){
		$login_pm=array('next_url'=>'/home.pl','email'=>$this->_email,'password'=>$this->_pass);
		$cl=& new HTTP_Client();
		$cl->post($this->login_url,$login_pm);
		$cl->get($this->recent);
		$res=$cl->currentResponse();
		$i=0;
		$content=str_get_html($res['body']);
		$echo=$content->find('div[class=archiveList]');
		$entry=$echo[0]->find('tr');
		foreach($entry as $tmp){
		$echo_list[$i]['member_id']=$tmp->find('div',0)->innertext;
		$echo_list[$i]['post_time']=$tmp->find('div',1)->innertext;
		$echo_list[$i]['nickname']=mb_convert_encoding($tmp->find('div',2)->innertext,$this->env_encode,$this->mixi_encode);
		$echo_list[$i]['body']=mb_convert_encoding($tmp->find('div',3)->innertext,$this->env_encode,$this->mixi_encode);
		$i++;
		}
		return $echo_list;
		unset($echo_list);
	}
	
		function my_reply(){
		$login_pm=array('next_url'=>'/home.pl','email'=>$this->_email,'password'=>$this->_pass);
		$cl=& new HTTP_Client();
		$cl->post($this->login_url,$login_pm);
		$cl->get($this->my_reply);
		$res=$cl->currentResponse();
		$i=0;
		$content=str_get_html($res['body']);
		$echo=$content->find('div[class=archiveList]');
		$entry=$echo[0]->find('tr');
		foreach($entry as $tmp){
		$echo_list[$i]['member_id']=$tmp->find('div',0)->innertext;
		$echo_list[$i]['post_time']=$tmp->find('div',1)->innertext;
		$echo_list[$i]['nickname']=mb_convert_encoding($tmp->find('div',2)->innertext,$this->env_encode,$this->mixi_encode);
		$echo_list[$i]['body']=mb_convert_encoding($tmp->find('div',3)->innertext,$this->env_encode,$this->mixi_encode);
		$i++;
		}
		return $echo_list;
		unset($echo_list);
	}
	
	function user_echo($id){
		$login_pm=array('next_url'=>'/home.pl','email'=>$this->_email,'password'=>$this->_pass);
		$user_list=$this->user_echo.$id;
		//echo $user_list;
		$cl=& new HTTP_Client();
		$cl->post($this->login_url,$login_pm);
		$cl->get($user_list);
		$res=$cl->currentResponse();
		$content=str_get_html($res['body']);
		$echo=$content->find('div[class=archiveList]');
		$entry=$echo[0]->find('tr');
		$i=0;
		foreach($entry as $tmp){
		$echo_list[$i]['member_id']=$tmp->find('div',0)->innertext;
		$echo_list[$i]['post_time']=$tmp->find('div',1)->innertext;
		$echo_list[$i]['nickname']=mb_convert_encoding($tmp->find('div',2)->innertext,$this->env_encode,$this->mixi_encode);
		$echo_list[$i]['body']=mb_convert_encoding($tmp->find('div',3)->innertext,$this->env_encode,$this->mixi_encode);
		$i++;
		}
		return $echo_list;
		unset($echo_list);
	}
	
	function post($body){
		$login_pm=array('next_url'=>'/home.pl','email'=>$this->_email,'password'=>$this->_pass);
		$cl=& new HTTP_Client();
		$cl->post($this->login_url,$login_pm);
		$add_pm=array(
			'body'=>mb_convert_encoding($body,$this->mixi_encode,$this->env_encode),
			'post_key'=>$this->_post_key
			);
		return $cl->post($this->post_echo,$add_pm);
	}
	
 	function reply($body,$member_id,$post_time){
		$cl=& new HTTP_Client();
		$cl->post($login_url,$login_pm);
		$add_pm=array(
			'body'=>mb_convert_encoding($body,$this->mixi_encode,$this->env_encode),
			'parent_member_id'=>$member_id,
			'parent_post_time'=>$post_time,
			'post_key'=>$this->_post_key
			);
		return $cl->post($this->post_echo,$add_pm);
	}
}
?>