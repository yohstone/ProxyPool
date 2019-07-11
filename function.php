<?php

/*
 * 获取指定代理网站的代理ip
 * $urls : 需爬取的代理网站数组，其中包括了网站的链接、需爬取内容的正则匹配表达式。
 *         详情请看config.php文件 
 * return : 返回抓取到的代理数据数组 
 */
function get_proxy($urls){

    $size = count($urls);
    for($i=0; $i < $size; $i++){
        for($page=1; $page <= $urls[$i]['pages']; $page++){
            try {

                $url  = sprintf($urls[$i]['url'], $page);
                
                write_log(date('Y-m-d H:i:s '). " crawl $url start...\r\n");

                // 抓取方式1
                $html = get_html($url);
                // 判断抓取结果，若代码长度小于5千字节，即5KB，同时代码里包含明显的失败关键字，则抓取失败，换种方式抓取
                if( strlen($html) < 5120 or 
                    strstr($html, '301 Moved Permanently') or
                    strstr($html, '404 Not Found')  ){
                    // 抓取方式2
                    $html = file_get_contents($url);

                    if(  (strlen($html) < 5000 or 
                        strstr($html, '301 Moved Permanently') or
                        strstr($html, '404 Not Found') ) and 
                        !empty($proxy_infos[0]) ){
                        // 抓取方式3，用已获得的代理进行抓取
                        $html = get_html($url, $proxy_infos[0]['ip'], $proxy_infos[0]['port']);
                        // 三次都失败，则跳出当前循环，放弃该网站
                        if( (strlen($html) < 5000 or 
                            strstr($html, '301 Moved Permanently') or
                            strstr($html, '404 Not Found') ) and 
                            !empty($proxy_infos[0]) ){
                            write_log(date('Y-m-d H:i:s '). " crawl $url fail!\r\n" );
                            break;

                        }

                    }
                }

                // 判断网页编码，若不是utf-8则转码
                if( !is_utf8($html) ){
                    $html = iconv("GBK", "UTF-8", $html);
                }
                // 保存html源码，便于分析，若不需要可注释掉
                $html_filename = BASE_PATH. "/html_code/$i/";
                if( !file_exists($html_filename) ){
                    mkdir($html_filename);
                }
                file_put_contents( $html_filename.$page.'.html', $html);

                // 匹配整个代理表格
                preg_match($urls[$i]['main_reg'], $html, $main_res);
                // 匹配表格每一行
                preg_match_all($urls[$i]['line_reg'], $main_res[0], $line_res, PREG_SET_ORDER);
                if( empty($line_res) ){
                        write_log( " $i-$page line match failed...\r\n" ); 
                        continue;
                }
                // 遍历表格每一行
                $line_num = 1;  // 行号
                foreach( $line_res as $li ){
                    $li[1] = trim($li[1]);  // 去除空格
                    // 匹配代理ip地址
                    preg_match($urls[$i]['ip_reg'], $li[1], $ip_res);
                    if( empty($ip_res) ){
                        write_log( " $i-$page line-$line ip match failed...\r\n" );
                        continue;
                    }
                    // 匹配代理端口，ip之后下一列肯定为端口，把已经匹配过的ip截掉一部分，便于匹配下一列
                    $li[1] = substr($li[1], 15); 
                    preg_match($urls[$i]['port_reg'], $li[1], $port_res);
                    if( empty($port_res) ){
                        write_log( " $i-$page line-$line port match failed...\r\n" );
                        continue;
                    }

                    // 代理类型不一定在下一列，只能靠分析出的正则匹配类型
                    if( !empty($urls[$i]['type_reg']) ){
                        preg_match($urls[$i]['type_reg'], $li[1], $type_res);
                        if( empty($port_res) ){
                            write_log( " $i-$page line-$line type match failed...\r\n" );
                        }
                    }else{
                        $type_res = array( 0 => '', 1 => '');
                    }
                    
                    $proxy_infos[] = array(
                        'ip'   => strip_tags($ip_res[1]),
                        'port' => strip_tags($port_res[1]),
                        'type' => strip_tags($type_res[1]),
                    );
                }
                write_log( date('Y-m-d H:i:s '). " crawl $url end...\r\n" );
                sleep(10); // 间隔10秒爬取一次
                
            } catch (Exception $e) {
                print_r($e);
            }
        }
    }
    //print_r($proxy_infos);
    return $proxy_infos;
}



/* 获取网站的html源码
 * $url : 网站链接
 * $proxy : 代理ip，默认为空（若获取源码失败，可能是主机ip被封，此时可以使用已有的代理再次尝试获取）
 * $proxy_port ： 代理端口号，默认为空
 * return ： 返回$url对应的网站源码
 */
function get_html($url,  $proxy='', $proxy_port='') {
    $ch = curl_init();
    // 设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.3 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//允许页面跳转，获取重定向
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);      // 60秒超时

    if($proxy != '' and $proxy_port != '') {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    // 获取内容
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


/* 判断是否是utf-8编码
 * $word : 需要判断的文本数据或字符串数据
 * return ： 若是utf-8编码的数据，则返回true，否则返回false
 */

function is_utf8($word){
    return (
           preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/", $word) == true 
        || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/", $word) == true 
        || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/", $word) == true
    );
}


/* 将日志数据写入文件中，记录下来
 * $log_info : 需要写入日志文件的数据
 */
function write_log($log_info){
    file_put_contents('./getProxy.log', $log_info, FILE_APPEND);
}
?>