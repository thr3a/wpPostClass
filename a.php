<pre>
<?php
require_once("wpPost.php");
$obj = new wpPost('localhost', '/wordpress/xmlrpc.php', 'thr3a', 'pass');
$postData = array(
	'title' => date('is'),
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