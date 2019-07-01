<?php

// 设置时区
date_default_timezone_set('PRC');

// 当前路径
define("BASE_PATH", str_replace("\\", "/", realpath(dirname(__FILE__))));

// 需要抓取的代理网站，正则需要分析网站源码，自己配置。
// 其中1~5为必填，6可以选填
$urls = array(
	'0' => array(
		'url'   => 'http://www.ip3366.net/free/?page=%d', 
		'pages' => 2, 										    // 1.抓取页数
		'main_reg'   => '/<tbody>(.*?)<\/tbody>/s', 			// 2.匹配整个代理表格的正则表达式
		'line_reg'   => '/<tr>(.*?)<\/tr>/s',        		    // 3.匹配代理表格每一行的正则表达式
		'ip_reg'     => '/<td *>(.*?)<\/td>/s',                 // 4.匹配代理ip的正则表达式
		'port_reg'   => '/<td *>(.*?)<\/td>/s',				    // 5.匹配代理端口号
		'type_reg'   => '/IP<\/td>[ |\t|\r|\n]*<td *>(.*?)<\/td>/s',  // 6.匹配代理类型，如是http还是https

	),
	'1' => array(
		'url'   => 'http://www.kuaidaili.com/free/inha/%d/', 
		'pages' => 2, 										    // 1.抓取页数
		'main_reg'   => '/<tbody>(.*?)<\/tbody>/s', 			// 2.匹配整个代理表格的正则表达式
		'line_reg'   => '/<tr>(.*?)<\/tr>/s',        		    // 3.匹配代理表格每一行的正则表达式
		'ip_reg'     => '/<td data-title="IP">(.*?)<\/td>/s',   // 4.匹配代理ip的正则表达式
		'port_reg'   => '/<td data-title="PORT">(.*?)<\/td>/s',	// 5.匹配代理端口号
		'type_reg'   => '/<td data-title="类型">(.*?)<\/td>/s',  // 6.匹配代理类型，如是http还是https

	),

);	

?>