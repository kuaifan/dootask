# apiDoc 参数标签说明（完整速查）

apiDoc 使用内联注释为 RESTful API 自动生成文档。
以下为所有官方支持的参数与其说明。

---

## @api
**定义 API 方法的基本信息**

```js
@api {method} path title
```

- **method**：请求方法，如 `GET`、`POST`、`PUT`、`DELETE` 等  
- **path**：请求路径，例如 `/user/:id`  
- **title**：简短标题（显示在文档中）

📘 示例：
```js
@api {get} /user/:id Get user info
```

---

## @apiBody
**定义请求体参数**

```js
@apiBody [{type}] [field=defaultValue] [description]
```

- `{type}` 参数类型（如 String, Number, Object, String[]）
- `[field]` 可选字段（方括号表示可选）
- `=defaultValue` 默认值
- `description` 参数说明

📘 示例：
```js
@apiBody {String} lastname Mandatory Lastname.
@apiBody {Object} [address] Optional address object.
@apiBody {String} [address[city]] Optional city.
```

---

## @apiDefine
**定义可复用的文档块**

```js
@apiDefine name [title] [description]
```

- `name`：唯一标识  
- `title`：简短标题  
- `description`：多行描述  

📘 示例：
```js
@apiDefine MyError
@apiError UserNotFound The <code>id</code> of the User was not found.
```

---

## @apiDeprecated
**标记接口为弃用状态**

```js
@apiDeprecated [text]
```

- `text`：提示文本，可带链接到新方法

📘 示例：
```js
@apiDeprecated use now (#User:GetDetails)
```

---

## @apiDescription
**描述接口详细说明**

```js
@apiDescription text
```

📘 示例：
```js
@apiDescription This is the Description.
It is multiline capable.
```

---

## @apiError
**定义错误返回参数**

```js
@apiError [(group)] [{type}] field [description]
```

📘 示例：
```js
@apiError UserNotFound The id of the User was not found.
```

---

## @apiErrorExample
**定义错误返回示例**

```js
@apiErrorExample [{type}] [title]
example
```

📘 示例：
```js
@apiErrorExample {json} Error-Response:
    HTTP/1.1 404 Not Found
    { "error": "UserNotFound" }
```

---

## @apiExample
**定义接口使用示例**

```js
@apiExample [{type}] title
example
```

📘 示例：
```js
@apiExample {curl} Example usage:
    curl -i http://localhost/user/4711
```

---

## @apiGroup
**定义所属分组**

```js
@apiGroup name
```

📘 示例：
```js
@apiGroup User
```

---

## @apiHeader
**定义请求头参数**

```js
@apiHeader [(group)] [{type}] [field=defaultValue] [description]
```

📘 示例：
```js
@apiHeader {String} access-key Users unique access-key.
```

---

## @apiHeaderExample
**定义请求头示例**

```js
@apiHeaderExample [{type}] [title]
example
```

📘 示例：
```js
@apiHeaderExample {json} Header-Example:
    {
      "Accept-Encoding": "gzip, deflate"
    }
```

---

## @apiIgnore
**忽略当前文档块**

```js
@apiIgnore [hint]
```

📘 示例：
```js
@apiIgnore Not finished method
```

---

## @apiName
**定义接口唯一名称**

```js
@apiName name
```

📘 示例：
```js
@apiName GetUser
```

---

## @apiParam
**定义请求参数**

```js
@apiParam [(group)] [{type}] [field=defaultValue] [description]
```

📘 示例：
```js
@apiParam {Number} id Users unique ID.
@apiParam {String} [firstname] Optional firstname.
@apiParam {String} country="DE" Mandatory with default.
```

---

## @apiParamExample
**定义参数请求示例**

```js
@apiParamExample [{type}] [title]
example
```

📘 示例：
```js
@apiParamExample {json} Request-Example:
    { "id": 4711 }
```

---

## @apiPermission
**定义权限要求**

```js
@apiPermission name
```

📘 示例：
```js
@apiPermission admin
```

---

## @apiPrivate
**标记接口为私有（可过滤）**

```js
@apiPrivate
```

---

## @apiQuery
**定义查询参数（?query）**

```js
@apiQuery [{type}] [field=defaultValue] [description]
```

📘 示例：
```js
@apiQuery {Number} id Users unique ID.
@apiQuery {String} [sort="asc"] Sort order.
```

---

## @apiSampleRequest
**定义接口测试请求 URL**

```js
@apiSampleRequest url
```

📘 示例：
```js
@apiSampleRequest http://test.github.com/some_path/
```

---

## @apiSuccess
**定义成功返回参数**

```js
@apiSuccess [(group)] [{type}] field [description]
```

📘 示例：
```js
@apiSuccess {String} firstname Firstname of the User.
@apiSuccess {String} lastname  Lastname of the User.
```

---

## @apiSuccessExample
**定义成功返回示例**

```js
@apiSuccessExample [{type}] [title]
example
```

📘 示例：
```js
@apiSuccessExample {json} Success-Response:
    HTTP/1.1 200 OK
    { "firstname": "John", "lastname": "Doe" }
```

---

## @apiUse
**引用定义块（@apiDefine）**

```js
@apiUse name
```

📘 示例：
```js
@apiDefine MySuccess
@apiSuccess {String} firstname User firstname.

@apiUse MySuccess
```

---

## @apiVersion
**定义接口版本**

```js
@apiVersion version
```

📘 示例：
```js
@apiVersion 1.6.2
```

---

# 附录：常用标签速查表

| 标签 | 作用 | 示例 |
|------|------|------|
| `@api` | 定义接口 | `@api {get} /user/:id` |
| `@apiName` | 唯一名称 | `@apiName GetUser` |
| `@apiGroup` | 所属分组 | `@apiGroup User` |
| `@apiParam` | 请求参数 | `@apiParam {Number} id Users unique ID.` |
| `@apiBody` | 请求体参数 | `@apiBody {String} name Username.` |
| `@apiQuery` | 查询参数 | `@apiQuery {String} keyword Search term.` |
| `@apiHeader` | Header 参数 | `@apiHeader {String} token Auth token.` |
| `@apiSuccess` | 成功返回字段 | `@apiSuccess {String} name Username.` |
| `@apiError` | 错误返回字段 | `@apiError NotFound User not found.` |
| `@apiVersion` | 版本号 | `@apiVersion 1.0.0` |
