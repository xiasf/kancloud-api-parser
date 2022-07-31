<?php

/**
 * 使用 DocApiParser 解析看云文档api 结构，用于导入 到 apifox 中
 *
 * kancloud api doc => OpenAPI 3.0
 * https://blog.csdn.net/m0_56069948/article/details/125468864
 *
 * last update: 2022-07-21 11:46:33
 *
 * author: xiak
 */

date_default_timezone_set('PRC');

require 'DocApiParser.php';

$docApiParser = new DocApiParser();

// $a = $docApiParser->parseDocFile('parkinglot/管理后台/停车场管理/套餐管理.md');
// print_r($a);exit;

// $apiAll = $docApiParser->OpenAPIFormatHandle($a);

// $docApiParser->writeApi('test-kancloud-api.json', $apiAll);

// print_r($apiAll);exit;

// echo file_get_contents('parkinglot/管理后台/登录.md');exit;

// print_r($docApiParser->parseDocFile('yangfei/后台API文档/物业管理.md'));exit;
// file_get_contents('yangfei/后台API文档/物业管理.md');exit;

// $apis = $docApiParser->parseDocFile('yangfei/支付中心/园区支付测试.md');

// $apis = $docApiParser->OpenAPIFormatHandle($apis);
// print_r($apis);exit;

// $docApiParser->writeApi('kancloud-api.json', $apis);
// exit;

$apiAll   = [];

// $docName = 'parkinglot'; // 实际 265， 导入时少 22个，原来有重复，去重后是对的
$docName = 'yangfei';

$fileList = $docApiParser->parseDocSummary($docName . '/SUMMARY.md');

// $docApiParser->formatHandle = function ($arr) {
//     return print_r($arr, true);
// };

//print_r($fileList);exit;

$api_total = 0;

$common_file_total = 0;

$useTimes = [];

foreach ($fileList as $file) {
    if (file_exists($file)) {
        $_t = microtime(true);
        $apis = $docApiParser->parseDocFile($file);
        $_useTime = microtime(true) - $_t;
        $useTimes[$file] = $_useTime;
        if (!empty($apis)) {
            $api_total += count($apis);
            // echo $file . PHP_EOL;
            // print_r($apis);
            $apiAll[$file] = $apis;
        } else {
            $common_file_total++;
            echo $common_file_total . ': ' . $file . '  ' . $useTimes[$file] . 's' . PHP_EOL;
        }
    }
}

// print_r($apiAll['/workspace/php7.4/parkinglot/default.md']);exit;

$apis = [];
foreach ($apiAll as $file => $_apis) {
    $apis = array_merge($apis, $_apis);
}

$apis = $docApiParser->OpenAPIFormatHandle($apis);
// print_r($apis);exit;

echo $docName . '-kancloud-api.json' . PHP_EOL;

$docApiParser->writeApi($docName . '-kancloud-api.json', $apis);

$api_real_total = 0;
foreach ($apis['paths'] as $_url => $_methods) {
    $api_real_total += count($_methods);
}

echo 'common file: ' . $common_file_total . PHP_EOL;
echo 'api file: ' . count($apiAll) . PHP_EOL;
echo 'api total: ' . $api_total . PHP_EOL;
echo 'api real total: ' . $api_real_total . PHP_EOL;
echo 'useTime total: ' . array_sum($useTimes) . 's' . PHP_EOL;
