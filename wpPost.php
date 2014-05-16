<?php
/*
WordPress投稿クラス
wpPost();
@arg
* string ホスト名
* string xmlrpc.phpまでの絶対パス
* string WordPressのユーザー名
* string WordPressのパスワード

newPost();
@arg
* array 投稿データ
	* string title 記事タイトル
	* string description 本文
	+ categories array カテゴリ(複数可、予め登録されていないものはスルー、無指定で未分類)
	+ wp_author_id int 投稿者ID

@return
投稿に成功している場合
string 記事個別URL
エラーの場合
array error
ex.
$obj = new wpPost('localhost', '/wordpress/xmlrpc.php', 'username', 'pass');
$postData = array(
	'title' => 'タイトル',
	'description' => '本文',
	'categories' =>  array(
	'カテゴリ１', 'カテゴリ２'
	),
	'wp_author_id' => 2
);
$result = $obj->newPost($postData);
if(isset($result['error'])){
	echo $result['error'];
}

*/
require_once("XML/RPC.php");
class wpPost {
	private $host, $xmlrpc_path, $username, $passwd, $appkey, $client , $blog_id, $blog_url;
	
	public function __construct( $host, $xmlrpc_path, $username, $passwd ){
		$this->client = new XML_RPC_client($xmlrpc_path, $host, 80);
		$this->appkey = new XML_RPC_Value('', 'string');
		$this->username = new XML_RPC_Value( $username, 'string' );
		$this->passwd = new XML_RPC_Value( $passwd, 'string' );
		
		//１回目叩いてBlogIDを取得する
		$message = new XML_RPC_Message(
			'blogger.getUsersBlogs',
			array($this->appkey, $this->username, $this->passwd)
		);
		$result = $this->sendXMLRPC( $message );
		$this->blog_url = $result[0]['url'];
		$this->blog_id = new XML_RPC_Value($result[0]['blogid'], "string");
	}
	public function newPost( $data ){
		$content = array();
		//記事タイトル
		if( $title = $data['title'] ){
			$content['title'] = new XML_RPC_Value($title, 'string');
		}else{
			return array(
				'error' => 'タイトルが設定されていません'
			);
		}
		//本文
		if( $description = $data['description'] ){
			$content['description'] = new XML_RPC_Value($description, 'string');
		}else{
			return array(
				'error' => '本文が設定されていません'
			);
		}
		//投稿日時
		//まだ現在時刻のみ対応
		$nowtime = date("Ymd\TH:i:s", time());
		$content['post_date'] = new XML_RPC_Value($nowtime, 'dateTime.iso8601');
		//カテゴリ
		if( $categories = $data['categories'] ){
			$array = array();
			foreach($categories as $category){
				array_push( $array, new XML_RPC_Value( $category , "string") );
			}
			$content['categories'] = new XML_RPC_Value( $array, 'array');
		}
		//投稿タイプ(post[default]|page|revision|attachment|その他)
		if( $post_type = $data['post_type'] ){
			$content['post_type'] = new XML_RPC_Value($post_type, 'string');
		}
		//公開ステータス(publish[default]|pending|draft|private|static|object|attachment|inherit|future)
		if( $post_status = $data['post_status'] ){
			$content['post_status'] = new XML_RPC_Value($post_status, 'string');
		}
		//著者(default=1)
		if ( $wp_author_id = $data['wp_author_id'] ) {
			$content['wp_author_id'] = new XML_RPC_Value($wp_author_id, 'int');
		}
		$content = new XML_RPC_Value(
			$content, 'struct'
		);
		$publish = new XML_RPC_Value(1, "boolean");
		//データ送信
		$message = new XML_RPC_Message(
			'metaWeblog.newPost',
			array($this->blog_id, $this->username, $this->passwd, $content, $publish)
		);
		$result = $this->sendXMLRPC( $message );
		if( empty($result['error']) ){
			$result = $this->blog_url . '?p=' . $result;
		}
		return $result;
	}
	//送信メソッド
	public function sendXMLRPC( $message ){
		$response = $this->client->send( $message );
		if( !$response ){
			return array(
				'error' => 'Could not connect to the server.'
			);
		}else if( $response->faultCode() ){
			return array(
				'error' => $response->faultString()
			);
		}
		return XML_RPC_decode($response->value());
	}
}
//必要そうなもの
