<template>
    <DialogMarkdown :text="content"/>
</template>

<script>
import DialogMarkdown from "../../DialogMarkdown.vue";
import {languageName} from "../../../../../language";

export default {
    components: {DialogMarkdown},
    props: {
        msg: Object,
    },
    data() {
        return {
            isChinese: /^zh/.test(languageName),
            chineseTemplate: `
## API 使用说明

---

## 一、机器人主动发送消息

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">发送文本消息接口</span>
</summary>
<br>

**功能说明**：开发者可以通过调用此API接口，让机器人主动向指定的对话（群组或私聊）发送文本消息。这是一个主动推送接口，适用于机器人需要定时通知、告警提醒等主动发送消息的场景。

#### 接口信息

| 属性 | 结果 |
|------|------|
| **请求方式** | POST |
| **接口地址** | \`{{sendApiUrl}}\` |
| **认证方式** | 通过请求头中的 version 和 token 进行认证 |
| **超时时间** | 30秒 |

#### 请求头

| 参数名 | 值 | 必填 | 说明 |
|--------|-----|------|------|
| \`version\` | \`{{version}}\` | √ | 系统版本号 |
| \`token\` | 机器人Token | √ | 机器人的访问令牌，可在机器人设置中获取 |

#### 请求参数

| 参数名 | 说明 | 类型 | 必填 | 示例值 |
|--------|------|------|------|--------|
| \`dialog_id\` | 对话ID | string | √ |  |
| \`text\` | 消息内容 | string | √ |  |
| \`text_type\` | 文本类型 | string | | html 或 md |
| \`key\` | 消息唯一标识 | string | | 留空则自动生成 |
| \`silence\` | 静默模式 | string | | yes 或 no |
| \`reply_id\` | 回复消息ID | string | |  |

</details>

---

## 二、Webhook 事件推送

**功能说明**：当特定事件发生时，系统会自动向机器人配置的 Webhook 地址发送 POST 请求，推送事件数据。这是一个被动接收机制，适用于机器人需要响应用户消息、监听群组事件等场景。

**重要提示**：
- 请确保 Webhook 地址可正常访问，且能在超时时间内响应
- 建议对推送的数据进行签名验证，确保数据来源可信
- Webhook 接口应尽快返回响应（200 OK），复杂业务逻辑建议异步处理

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">接收消息事件（<code>message</code>）</span>
</summary>
<br>

**触发时机**：当机器人在对话中收到新消息时（包括被@提及或私聊消息），系统会自动推送到配置的 Webhook 地址。

**超时时间**：30秒

**使用场景**：智能问答、关键词回复、消息记录、自动客服等

#### 推送参数

| 参数名 | 说明 | 类型 |
|--------|------|------|
| \`event\` | 事件类型，固定值 \`message\` | string |
| \`text\` | 消息的文本内容 | string |
| \`reply_text\` | 如果是回复消息，则包含被回复消息的文本内容 | string |
| \`token\` | 机器人Token | string |
| \`session_id\` | 会话ID {{sessionDesc}} | string |
| \`dialog_id\` | 对话ID | string |
| \`dialog_type\` | 对话类型 | string |
| \`msg_id\` | 消息ID | string |
| \`msg_uid\` | 消息发送人的用户ID | string |
| \`msg_user\` | 消息发送人的详细信息（昵称、头像等） | object |
| \`mention\` | 机器人是否被@提及 | boolean |
| \`bot_uid\` | 机器人ID | string |
| \`version\` | 系统版本 | string |
</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">打开会话事件（<code>dialog_open</code>）</span>
</summary>
<br>

**触发时机**：当用户打开与机器人的会话窗口时（首次打开或重新进入），系统会推送此事件。

**超时时间**：10秒

**使用场景**：欢迎语、菜单展示、会话初始化、用户行为统计等

#### 推送参数

| 参数名 | 说明 | 类型 |
|--------|------|------|
| \`event\` | 事件类型，固定值 \`dialog_open\` | string |
| \`dialog_id\` | 对话ID | number |
| \`dialog_type\` | 对话类型 | string |
| \`group_type\` | 群组类型（仅群组对话时有值） | string |
| \`dialog_name\` | 对话名称（群组名称或用户昵称） | string |
| \`member\` | 打开会话的成员信息 | object |
| \`operator\` | 操作人信息（通常与 member 相同） | object |

</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">成员加入事件（<code>member_join</code>）</span>
</summary>
<br>

**触发时机**：当新成员加入机器人所在的群组时，系统会推送此事件。

**超时时间**：10秒

**使用场景**：新成员欢迎语、群规则提醒、自动分配权限、成员统计等

#### 推送参数

| 参数名 | 说明 | 类型 |
|--------|------|------|
| \`event\` | 事件类型，固定值 \`member_join\` | string |
| \`dialog_id\` | 群组对话ID | number |
| \`dialog_type\` | 对话类型，此处固定为 \`group\` | string |
| \`group_type\` | 群组类型 | string |
| \`dialog_name\` | 群组名称 | string |
| \`member\` | 加入的成员信息（昵称、ID等） | object |
| \`operator\` | 操作人信息（邀请人，如果是自己加入则与 member 相同） | object |

</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">成员退出事件（<code>member_leave</code>）</span>
</summary>
<br>

**触发时机**：当成员退出或被移出机器人所在的群组时，系统会推送此事件。

**超时时间**：10秒

**使用场景**：成员变动记录、权限清理、离职提醒、成员统计等

#### 推送参数

| 参数名 | 说明 | 类型 |
|--------|------|------|
| \`event\` | 事件类型，固定值 \`member_leave\` | string |
| \`dialog_id\` | 群组对话ID | number |
| \`dialog_type\` | 对话类型，此处固定为 \`group\` | string |
| \`group_type\` | 群组类型 | string |
| \`dialog_name\` | 群组名称 | string |
| \`member\` | 退出的成员信息（昵称、ID等） | object |
| \`operator\` | 操作人信息（踢出者，如果是自己退出则与 member 相同） | object |

</details>

---

**提示**：请妥善保管机器人 Token，确保 Webhook 接口稳定可用并及时响应。更多帮助请发送 <span class="mark-color mark-set">/help</span> 命令查看。
`,
            englishTemplate: `
## API Documentation

---

## 1. Bot Proactive Message Sending

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">Send Text Message API</span>
</summary>
<br>

**Description**: Developers can call this API to allow the bot to proactively send text messages to specified conversations (groups or private chats). This is a proactive push interface, suitable for scenarios where the bot needs to send scheduled notifications, alerts, and other proactive messages.

#### API Information

| Property | Value |
|------|------|
| **Request Method** | POST |
| **API Endpoint** | \`{{sendApiUrl}}\` |
| **Authentication** | Authenticate via version and token in request headers |
| **Timeout** | 30 seconds |

#### Request Headers

| Parameter | Value | Required | Description |
|--------|-----|------|------|
| \`version\` | \`{{version}}\` | √ | System version number |
| \`token\` | Bot Token | √ | Bot access token, available in bot settings |

#### Request Parameters

| Parameter | Description | Type | Required | Example |
|--------|------|------|------|--------|
| \`dialog_id\` | Dialog ID | string | √ |  |
| \`text\` | Message content | string | √ |  |
| \`text_type\` | Text type | string | | html or md |
| \`key\` | Unique message identifier | string | | Auto-generated if left empty |
| \`silence\` | Silent mode | string | | yes or no |
| \`reply_id\` | Reply message ID | string | |  |

</details>

---

## 2. Webhook Event Push

**Description**: When specific events occur, the system will automatically send POST requests to the Webhook URL configured for the bot, pushing event data. This is a passive receiving mechanism, suitable for scenarios where the bot needs to respond to user messages, monitor group events, etc.

**Important Notes**:
- Ensure the Webhook URL is accessible and can respond within the timeout period
- Recommend verifying the signature of pushed data to ensure the source is trustworthy
- Webhook interface should return a response (200 OK) as quickly as possible; complex business logic should be handled asynchronously

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">Message Received Event (<code>message</code>)</span>
</summary>
<br>

**Trigger Timing**: When the bot receives a new message in a conversation (including being @mentioned or private messages), the system will automatically push to the configured Webhook URL.

**Timeout**: 30 seconds

**Use Cases**: Intelligent Q&A, keyword replies, message logging, automatic customer service, etc.

#### Push Parameters

| Parameter | Description | Type |
|--------|------|------|
| \`event\` | Event type, fixed value \`message\` | string |
| \`text\` | Text content of the message | string |
| \`reply_text\` | If it's a reply message, contains the text content of the replied message | string |
| \`token\` | Bot Token | string |
| \`session_id\` | Session ID {{sessionDesc}} | string |
| \`dialog_id\` | Dialog ID | string |
| \`dialog_type\` | Dialog type | string |
| \`msg_id\` | Message ID | string |
| \`msg_uid\` | User ID of message sender | string |
| \`msg_user\` | Detailed information of message sender (nickname, avatar, etc.) | object |
| \`mention\` | Whether the bot was @mentioned | boolean |
| \`bot_uid\` | Bot ID | string |
| \`version\` | System version | string |
</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">Dialog Open Event (<code>dialog_open</code>)</span>
</summary>
<br>

**Trigger Timing**: When a user opens a conversation window with the bot (first time or re-entry), the system will push this event.

**Timeout**: 10 seconds

**Use Cases**: Welcome messages, menu display, session initialization, user behavior statistics, etc.

#### Push Parameters

| Parameter | Description | Type |
|--------|------|------|
| \`event\` | Event type, fixed value \`dialog_open\` | string |
| \`dialog_id\` | Dialog ID | number |
| \`dialog_type\` | Dialog type | string |
| \`group_type\` | Group type (only for group conversations) | string |
| \`dialog_name\` | Dialog name (group name or user nickname) | string |
| \`member\` | Information of member opening the session | object |
| \`operator\` | Operator information (usually same as member) | object |

</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">Member Join Event (<code>member_join</code>)</span>
</summary>
<br>

**Trigger Timing**: When a new member joins a group where the bot is present, the system will push this event.

**Timeout**: 10 seconds

**Use Cases**: New member welcome messages, group rules reminders, automatic permission assignment, member statistics, etc.

#### Push Parameters

| Parameter | Description | Type |
|--------|------|------|
| \`event\` | Event type, fixed value \`member_join\` | string |
| \`dialog_id\` | Group dialog ID | number |
| \`dialog_type\` | Dialog type, fixed as \`group\` | string |
| \`group_type\` | Group type | string |
| \`dialog_name\` | Group name | string |
| \`member\` | Information of joining member (nickname, ID, etc.) | object |
| \`operator\` | Operator information (inviter, same as member if self-joined) | object |

</details>

<details>
<summary>
    <span style="font-size:1.25em;font-weight:bold;padding-left:8px">Member Leave Event (<code>member_leave</code>)</span>
</summary>
<br>

**Trigger Timing**: When a member leaves or is removed from a group where the bot is present, the system will push this event.

**Timeout**: 10 seconds

**Use Cases**: Member change records, permission cleanup, departure reminders, member statistics, etc.

#### Push Parameters

| Parameter | Description | Type |
|--------|------|------|
| \`event\` | Event type, fixed value \`member_leave\` | string |
| \`dialog_id\` | Group dialog ID | number |
| \`dialog_type\` | Dialog type, fixed as \`group\` | string |
| \`group_type\` | Group type | string |
| \`dialog_name\` | Group name | string |
| \`member\` | Information of leaving member (nickname, ID, etc.) | object |
| \`operator\` | Operator information (remover, same as member if self-left) | object |

</details>

---

**Tip**: Please keep the bot Token secure, ensure the Webhook interface is stable and responds promptly. For more help, send the <span class="mark-color mark-set">/help</span> command.
`,
        };
    },
    computed: {
        content() {
            const variables = {
                sendApiUrl: $A.apiUrl('dialog/msg/sendtext'),
                version: this.msg.version,
                sessionDesc: !/^(ai-|user-session-)/.test(this.msg.email) ? ` <span style='color:#999;padding-left:4px;'>(${this.$L('该机器人不支持')})</span>` : ``,
            };
            const template = this.isChinese ? this.chineseTemplate : this.englishTemplate;
            return template.replace(/\{\{([^}]+)\}\}/g, (_, v1) => variables[v1] || v1).trim();
        },
    },
    methods: {},
}
</script>
