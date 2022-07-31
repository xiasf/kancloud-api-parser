<?php

/**
 * DocApiParser
 *
 * last update: 2022-07-20 18:30:33
 *
 * author: xiak
 */

class DocApiParser
{

    public $formatHandle;

    public $defaultApiPrefix = 'default';

    public function __construct()
    {
        // https://blog.51cto.com/u_6491481/3288304
        // 正则字符数量会受限
        // echo ini_get('pcre.backtrack_limit'); // 默认的只有 1000000
        ini_set('pcre.backtrack_limit', 1000000000);
    }

    // 格式处理，目前先处理成 OpenApi 的格式
    private function _formatHandle(array $arr)
    {
        if (isset($this->formatHandle)) {
            return call_user_func($this->formatHandle, $arr);
        }

        return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // 写入到文件
    public function writeApi(string $file, array $arr)
    {
        $_dir = dirname($file);
        !is_dir($_dir) && mkdir($_dir, 0755, true);

        file_put_contents($file, $this->_formatHandle($arr));
    }

    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    // https://github.com/OAI/OpenAPI-Specification/blob/main/versions/3.1.0.md
    public function OpenAPIFormatHandle(array $apis)
    {
        $data = [
            'openapi'    => '3.1.0',
            'info'       => [
                'title'       => '看云文档导出',
                'summary'     => '看云文档导出 OpenAPI 3.1',
                'description' => '',
                'version'     => '1.0.0',
                'create_date' => date('Y-m-d H:i:s'),
            ],
            'servers'    => [
                [
                    'url'         => 'api.test.domain.cn',
                    'description' => '测试环境',
                ],
                [
                    'url'         => 'api.kf.domain.cn',
                    'description' => '开发环境',
                ],
                [
                    'url'         => 'api.domain.cn',
                    'description' => '正式环境',
                ],
            ],
            'tags'       => [
                [
                    'name'        => 'admin',
                    'description' => 'admin module',
                ],
                [
                    'name'        => 'client',
                    'description' => 'client.car app module',
                ],
                [
                    'name'        => 'h5',
                    'description' => 'h5.car app module',
                ],
                [
                    'name'        => 'user',
                    'description' => 'sp app module',
                ],
            ],
            'components' => [
                'request' => [
                ],
            ],
        ];

        $_paths = [];

        foreach ($apis as $_api) {

            $_required   = [];
            $_properties = [];

            $_parameters = [];
            foreach ($_api['fields'] as $_field) {
                $_schema = [
                    'type' => 'integer',
                ];
                if ('int' === $_field['type']) {
                    $_schema['format'] = 'int32';
                } else {
                    $_schema['type'] = 'string';
                }
                $_parameters[] = [
                    'name'        => $_field['name'],
                    'in'          => 'query',
                    'description' => $_field['desc'],
                    'required'    => $_field['isq'],
                    'example'     => $_field['value'],
                    'deprecated'  => false,
                    // 'allowEmptyValue' => true,
                    'style'       => 'form',
                    'explode'     => true,
                    'schema'      => $_schema,
                ];

                if ('binary' === $_field['type']) {
                    $_propertie = [
                        'type'        => 'string',
                        'description' => $_field['desc'],
                        'example'     => 'file://{file}',
                        'format'      => 'binary',
                    ];
                } elseif ('int' === $_field['type']) {
                    $_propertie = [
                        'description' => $_field['desc'],
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'example'     => $_field['value'],
                    ];
                } else {
                    $_propertie = [
                        'description' => $_field['desc'],
                        'type'        => 'string',
                        'example'     => $_field['value'],
                    ];
                }

                $_properties[$_field['name']] = $_propertie;

                if ($_field['isq']) {
                    $_required[] = $_field['name'];
                }
            }

            $_requestBody = [
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type'       => 'object',
                            'properties' => $_properties,
                            'required'   => $_required,
                        ],
                    ],
                ],
            ];

            $_responses = [];
            $_content   = [
                'application/json' => [
                    'examples' => [],
                ],
            ];

            foreach ($_api['ress'] as $_res) {
                $_type = ($_res_content = json_decode($_res['res_content'])) ? 'application/json' : 'text/plain';

                // if ($_type === 'application/json') {
                //     $_content[$_type] = [
                //         'schema' => [
                //             'type'    => 'object',
                //             'example' => $_res_content,
                //         ],
                //     ];
                // } else {
                //     $_content[$_type] = [
                //         'schema' => [
                //             'type'    => 'string',
                //             'example' => $_res['res_content'],
                //         ],
                //     ];
                // }

                // 看云文档只定义响应示例，而不是响应格式
                // 'schema' => [
                //     'type'       => 'object',
                //     'properties' => [
                //         'ids' => [
                //             'type'        => 'string',
                //             'description' => 'description',
                //         ],
                //     ],
                //     'example' => ['s'],
                // ],

                // ress 即 examples 即 apifox的响应示例
                $_content['application/json']['examples'][] = [
                    'summary' => $_res['res_name'],
                    'value'   => $_res_content ?: $_res['res_content'],
                ];
            }

            // 只定义一个响应格式
            $_content['application/json']['schema'] = [
                'type'            => 'object',
                'properties'      => [
                    'data' => [
                        'type' => 'object',
                    ],
                    'msg'  => [
                        'type' => 'string',
                    ],
                    'code' => [
                        'type' => 'integer',
                    ],
                ],
                'required'        => ['data', 'msg', 'code'],
                'x-apifox-orders' => ['data', 'msg', 'code'],
            ];

            // 多个 http状态码相同的响应格式可以这样：x-200:成功3  x-200:{$_content}
            $_responses['200'] = [
                'description' => 'ok',
                'content'     => $_content,
            ];

            // $_requestBody['content']['application/json'] = [
            //     'schema'  => [
            //         'type'       => 'object',
            //         'properties' => [
            //             'id'   => [
            //                 'type'        => 'string',
            //                 'description' => 'description',
            //             ],
            //             'list' => [
            //                 'type'  => 'array',
            //                 'items' => [
            //                     'type'       => 'object',
            //                     'properties' => [
            //                         'i' => [
            //                             'type' => 'integer',
            //                         ],
            //                     ],
            //                 ],
            //             ],
            //         ],
            //     ],
            //     'example' => [
            //         'id'   => '',
            //         'list' => [['i' => 1]],
            //     ],
            // ];

            // 目前来看用 multipart/form-data 最合适，apifox UI 前端支持得最好

            // requestBody.content.multipart/form-data.schema

            $_paths[$_api['api_url']][$_api['api_method']] = [
                'tags'            => [
                    $_api['api_prefix'],
                ],
                'x-apifox-folder' => trim($_api['api_path'], '/'),
                'summary'         => $_api['api_title'],
                'description'     => $_api['api_desc'],
                // 'parameters'      => $_parameters,
                'parameters'      => [],
                'requestBody'     => $_requestBody,
                'responses'       => $_responses,
            ];
        }

        $data['paths'] = $_paths;

        return $data;
    }

    // 解析文档文件
    public function parseDocFile(string $file)
    {
        $apiList = [];

        $regex = '/^(?:#{3,}\s*(?<api_title>.+))\s*(?<api_desc>[\s\S]*?)?\s*~~~\[api(?::(?<api_prefix>[^\]]+))?\]\s*(?:[^\w]*\s*(?<api_method>post|get|\w+):)(?<api_url>.+)\s*(?<fields>(?:(?:\*)?(?:(?:\w+):)?(?:\w+)(?:=(?:[^#]*))?\s*(?:#(?:.*))?\s*)+)?(?<ress>(?:<<<\s*(?:.+)(?:[^<~]+))*)(?:~~~)?\s*$/uim';

        $str = file_get_contents($file);
        $str = trim($str);
        // https://www.cnblogs.com/fishparadise/p/4570654.html
        // https://tool.oschina.net/hexconvert
        $str = preg_replace('/\x0d/u', '', $str); // 13 回车键 (清除看云文档中的看不见的换行)

        // 解析整体
        $api_count = preg_match_all($regex, $str, $matches);

        // 匹配到的 api 结构数量
        // var_dump($api_count);

        if (empty($api_count)) {
            return $apiList;
        }

        // print_r($matches['api_title']);

        // print_r($matches['api_desc']);

        // print_r($matches['api_prefix']);

        // print_r($matches['api_method']);

        // print_r($matches['api_url']);
        // exit;

        $fields_arr = [];

        $fields = $matches['fields'];
        // print_r($fields);exit;

        foreach ($fields as $k => $field) {
            // 解析字段部分
            $field_count = preg_match_all('/(?<field>(?<isq>\*)?(?:(?<type>\w+):)?(?<name>\w+)(?:=(?<value>[^#]*))?(?:#(?<desc>.*))?\s*)/uim', $field, $field_matches);
            //print_r($field_matches);exit;

            if (0 == $field_count) {
                $fields_arr[$k][] = [];
            }

            for ($i = 0; $i < $field_count; $i++) {
                $fields_arr[$k][] = [
                    'isq'   => !empty($field_matches['isq'][$i]) ? true : false,
                    'type'  => trim($field_matches['type'][$i] ?? ''),
                    'name'  => trim($field_matches['name'][$i] ?? ''),
                    'value' => trim($field_matches['value'][$i] ?? ''),
                    'desc'  => trim($field_matches['desc'][$i] ?? ''),
                ];
            }
        }

        // print_r($fields_arr);
        // exit;

        $ress_arr = [];

        $ress = $matches['ress'];
        // print_r($ress);

        foreach ($ress as $k => $res) {
            // 解析 res 部分
            $res_count = preg_match_all('/(?<res><<<\s*(?<res_name>.+)(?<res_content>[^<~]+)\s*)/uim', $res, $res_matches);
            // print_r($r_matches);

            if (0 == $res_count) {
                $ress_arr[$k][] = [];
            }

            for ($i = 0; $i < $res_count; $i++) {
                $ress_arr[$k][] = [
                    'res_name'    => trim($res_matches['res_name'][$i] ?? ''),
                    'res_content' => trim($res_matches['res_content'][$i] ?? ''),
                ];
            }
        }

        // print_r($ress_arr);
        // exit;

        for ($i = 0; $i < $api_count; $i++) {
            $apiList[$i] = [
                'api_prefix' => $matches['api_prefix'][$i] ?: $this->defaultApiPrefix,
                'api_url'    => $matches['api_url'][$i],
                'api_title'  => $matches['api_title'][$i],
                'api_desc'   => $matches['api_desc'][$i],
                'api_method' => $matches['api_method'][$i],
                'fields'     => $fields_arr[$i],
                'ress'       => $ress_arr[$i],
            ];

            // 我们只有这两种路由格式的 api
            // client: /v1.pub/getClientToken
            // admin: /parkinglot/v1.car/index

            // client/v1/pub
            // admin/parkinglot/v1/car

            if (preg_match('/(?<module>\w+\/)?(?<version>\w+)\.(?<controller>\w+)/', $matches['api_url'][$i], $url_matches)) {
                $apiList[$i]['api_version'] = $url_matches['version'];
                $apiList[$i]['api_path']    = '/' . $apiList[$i]['api_prefix'] . '/'
                . $url_matches['module']
                // v1 这一层先去掉
                // . $url_matches['version'] . '/'
                // 控制器统一大驼峰形式
                 . self::parseName($url_matches['controller'], 1) . '/';
            } else {
                $apiList[$i]['api_path']    = '';
                $apiList[$i]['api_version'] = '';
            }

        }

        return $apiList;
    }

    // 解析文档全部文件
    public function parseDocSummary(string $file)
    {
        $list = [];

        $_dir = dirname(realpath($file)) . '/';

        if ($file_count = preg_match_all('/\((?<file_path>.+)\)/uim', file_get_contents($file), $matches)) {
            $list = $matches['file_path'];
        }

        array_walk_recursive($list, function (&$_file) use ($_dir) {
            $_file = $_dir . $_file;
        });

        return $list;
    }

}
