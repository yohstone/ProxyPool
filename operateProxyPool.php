<?php
require('./config.php');
require('./function.php');

// 爬取代理网站中的代理数据
$proxy_infos = get_proxy($urls);

// 将代理数据以json格式保存到文件中
file_put_contents('proxyPool.dat', json_encode(array_values($proxy_infos)));


?>