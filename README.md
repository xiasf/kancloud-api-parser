## kancloud-api-parser 说明

将 [看云文档api](https://help.kancloud.cn/67539) 格式解析导出为 [OpenAPI 3.1](https://github.com/OAI/OpenAPI-Specification/blob/main/versions/3.1.0.md) 格式的json文件，方便导入到 [Apifox](https://www.apifox.cn/) 中。

对于之前使用了不支持标准化API文档结构导出的文档平台来说，自己根据规则解析是唯一的办法了，否则首次迁移成本太高了，可能就放弃了。

本脚本解析效果取决于文档书写的规范程度，因为是通过正则匹配解析的，所以也可以根据需要灵活的去做调整。目前我们使用没什么问题，如果问题欢迎提 Issues 交流。

----

### 使用方法

```php
require 'DocApiParser.php';

$docApiParser = new DocApiParser();

// ...

```

#### 自定义默认API分组（前缀）

分组即 api_prefix ，决定导入到 Apifox 中的最上层目录名称(x-apifox-folder 字段)

```php

$docApiParser->defaultApiPrefix = 'admin'; // 默认为 default

```

#### 解析并导出为 OpenAPI json格式文件

```php
// 0. 解析看云文档目录文件
$fileList = $docApiParser->parseDocSummary('xxx-doc/SUMMARY.md');

// 1. 解析 看云 特有的 API 格式，转换为 标准的API元信息 数组格式
$apis = $docApiParser->parseDocFile('parkinglot/管理后台/停车场管理/套餐管理.md');
// print_r($apis);exit;

// 基于 标准的API元信息 ，你可以定义其他的任何转换处理器

// 2. 再转换为 OpenAPI 格式的数组
$apis = $docApiParser->OpenAPIFormatHandle($apis);
// print_r($apis);exit;

// 3. 写入到文件 ， 默认为 json 格式，你也可以定义 其他 格式转换处理 formatHandle 属性，如 XML等格式
$docApiParser->writeApi('kancloud-api.json', $apis);

```

----

### 看云文档 API 标准格式

![image](https://user-images.githubusercontent.com/17535757/182022957-b06bf327-105c-4a8b-93c5-13fd9b1b2918.png)

```text
### 1. 创建、编辑 新闻

使用场景：后台管理员 创建、编辑 新闻

~~~[api:admin]
post:/news/edit
*int:id=1#参数id
string:name=默认值#说明文字
<<<
success
{
    "data": {
        "id": 1,
    },
    "msg": "ok",
    "code": 1
}
<<<
error
{
    "data": {},
    "msg": "error msg",
    "code": 0
}
<<<
响应示例
这里填写错误的返回码
以此类推，每个状态使用 <<< 分割,
第一行添加状态名称
~~~
```

https://help.kancloud.cn/67539
