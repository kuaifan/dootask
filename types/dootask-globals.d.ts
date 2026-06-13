/**
 * DooTask 前端全局工具 $A / 翻译函数 $L 的 TypeScript 类型声明（纯声明，无运行时代码）。
 *
 * 来源文件（$A 是 jQuery 实例，由以下三个 IIFE 文件通过 $.extend() 扩展而成，
 * 挂载于 window.$A 与 Vue.prototype.$A）：
 *   - resources/assets/js/functions/common.js  （基础函数 / localForage / Storage / ihttp / ajaxc / time / sort）
 *   - resources/assets/js/functions/web.js     （页面专用 / iviewui 弹窗提示 / dark 暗黑模式）
 *   - resources/assets/js/functions/eeui.js    （EEUI App 专用）
 * $L 来源：resources/assets/js/language/index.js 的 switchLanguage，
 * 挂载于 window.$L、Vue.prototype.$L 以及 $A.L（见 resources/assets/js/app.js）。
 *
 * 维护提示：在上述源文件中新增/修改 $.extend 挂载的 $A 方法时，须同步更新本声明文件。
 */

/** modal 系列配置（字符串等价于 { content: 字符串 }） */
interface DooTaskModalConfig {
    /** 标题，默认「温馨提示」 */
    title?: string;
    /** 内容 */
    content?: string | false;
    /** 确定按钮文字，默认「确定」 */
    okText?: string;
    /** 取消按钮文字，默认「取消」 */
    cancelText?: string;
    /** 传 false 时由调用方自行处理翻译，否则内部自动 $L 翻译 */
    language?: boolean;
    /** onOk 返回 Promise 时启用 loading 等待 */
    loading?: boolean;
    onOk?: (...args: any[]) => any;
    onCancel?: (...args: any[]) => any;
    render?: (...args: any[]) => any;
    [key: string]: any;
}

/** notice 系列配置（字符串等价于 { desc: 字符串 }） */
interface DooTaskNoticeConfig {
    /** 标题，默认「温馨提示」 */
    title?: string;
    /** 描述内容 */
    desc?: string;
    /** 显示时长（秒） */
    duration?: number;
    render?: (...args: any[]) => any;
    [key: string]: any;
}

/** ajaxc 请求参数 */
interface DooTaskAjaxcParams {
    url: string;
    data?: any;
    cache?: boolean;
    method?: string;
    timeout?: number;
    dataType?: string;
    header?: Record<string, string>;
    requestId?: string | number | null;
    before?: () => void;
    complete?: () => void;
    after?: (success: boolean) => void;
    success?: (data: any, status: number | string, xhr: XMLHttpRequest) => void;
    error?: (xhr: XMLHttpRequest, status: number | string) => void;
    [key: string]: any;
}

/** extractImageParameter 返回的图片参数 */
interface DooTaskImageParameter {
    src: string | null;
    width: number;
    height: number;
    original: string;
}

/** imageRatioHandle 参数/返回值 */
interface DooTaskImageRatioParams {
    /** 原图地址 */
    src: string;
    /** 原图宽度 */
    width: number;
    /** 原图高度 */
    height: number;
    /** 裁剪参数，如：{ratio:3, percentage:"80x0"} */
    crops?: { ratio?: number; size?: string; percentage?: string; cover?: string; contain?: string };
    /** 返回尺寸缩放最高尺寸 */
    scaleSize?: number;
    [key: string]: any;
}

/** dark 暗黑模式工具 */
interface DooTaskDark {
    utils: {
        /** 判断浏览器支持的暗黑实现方式 chrome|webkit|null */
        supportMode(): "chrome" | "webkit" | null;
        /** 默认反色滤镜样式 */
        defaultFilter(): string;
        /** 反向反色滤镜样式 */
        reverseFilter(): string;
        /** 取消滤镜样式 */
        noneFilter(): string;
        /** 附加额外样式 */
        addExtraStyle(): string;
        /** 添加样式标签 */
        addStyle(id: string, tag: string, css: string): void;
        /** 获取元素 classList */
        getClassList(node: Element): DOMTokenList | any[];
        /** 给元素添加 class */
        addClass(node: Element, name: string): DooTaskDark["utils"];
        /** 移除元素 class */
        removeClass(node: Element, name: string): DooTaskDark["utils"];
        /** 判断元素是否含有 class */
        hasClass(node: Element, name: string): boolean;
        /** 根据 id 获取元素 */
        hasElementById(eleId: string): HTMLElement | null;
        /** 根据 id 删除元素 */
        removeElementById(eleId: string): void;
    };
    /** 创建暗黑模式样式 */
    createDarkStyle(): void;
    /** 开启暗黑模式 */
    enableDarkMode(): void;
    /** 关闭暗黑模式 */
    disableDarkMode(): void;
    /** 跟随系统自动切换暗黑模式 */
    autoDarkMode(): void;
    /** 是否已开启暗黑模式 */
    isDarkEnabled(): boolean;
}

/**
 * DooTask 全局工具对象。
 * 说明：$A 本体是 jQuery 实例（window.$ / window.jQuery），为避免引入 @types/jquery 依赖，
 * 这里不 extends JQueryStatic，而是用「可调用签名 + 字符串索引签名」兜底 jQuery 本体的
 * 选择器调用（如 $A(el)）与 each/extend 等静态方法。
 */
interface DooTaskGlobal {
    /** jQuery 选择器调用兜底（$A(selector)，返回 jQuery 对象） */
    (selector?: any, context?: any): any;
    /** jQuery 本体其余静态属性/方法兜底（each/extend/fn 等） */
    [key: string]: any;

    /* =========================================================================
     * app.js 挂载的全局属性
     * ========================================================================= */

    /** 翻译函数（同 window.$L，见 language/index.js switchLanguage） */
    L(text: string, ...args: Array<string | number>): string;
    /** 应用是否初始化完成 */
    Ready: boolean;
    /** Electron 桥接对象（非 Electron 环境为 null） */
    Electron: any;
    /** 运行平台：web|mac|win|ios|android */
    Platform: string;
    /** 是否 Electron 主窗口 */
    isMainElectron: boolean;
    /** 是否 Electron 子窗口 */
    isSubElectron: boolean;
    /** 是否 EEUI App 环境 */
    isEEUIApp: boolean;
    /** 是否 Electron 环境 */
    isElectron: boolean;
    /** 是否客户端软件环境（Electron 或 EEUI） */
    isSoftware: boolean;
    /** 是否开启调试日志（VConsole） */
    openLog: boolean;
    /** iView Modal 实例（app 初始化后可用） */
    Modal: any;
    /** iView Message 实例（app 初始化后可用） */
    Message: any;
    /** iView Notice 实例（app 初始化后可用） */
    Notice: any;

    /* =========================================================================
     * common.js —— 基础函数类
     * ========================================================================= */

    /** 是否数组 */
    isArray(obj: any): boolean;
    /** 规范化为整型数组（去重、过滤非正整数） */
    normalizeIntArray(data: any): number[];
    /** 是否数组对象（普通 JSON 对象） */
    isJson(obj: any): boolean;
    /** 是否在数组里（regular 为 true 时支持 * 通配） */
    inArray(key: any, array: any[], regular?: boolean): boolean;
    /** 随机获取范围内的整数 */
    randNum(Min: number, Max: number): number;
    /** 获取数组最后一个值（无则返回 false） */
    last(array: any[]): any;
    /** 字符串是否包含（lower 为 true 时区分大小写） */
    strExists(string: any, find: any, lower?: boolean): boolean;
    /** 字符串是否左边包含 */
    leftExists(string: any, find: any, lower?: boolean): boolean;
    /** 删除左边字符串 */
    leftDelete(string: any, find: any, lower?: boolean): string;
    /** 字符串是否右边包含 */
    rightExists(string: any, find: any, lower?: boolean): boolean;
    /** 删除右边字符串 */
    rightDelete(string: any, find: any, lower?: boolean): string;
    /** 取字符串中间 */
    getMiddle(string: any, start?: string | null, end?: string | null): string;
    /** 截取字符串 */
    subString(string: any, start: number, end?: number): string;
    /** 随机字符（默认 32 位） */
    randomString(len?: number): string;
    /** 判断是否有值（enhanced 为 true 时空数组/空对象视为无） */
    isHave(val: any, enhanced?: boolean): boolean;
    /** 判断是否为真（true/1/"true"/"1"） */
    isTrue(value: any): boolean;
    /** 相当于 intval（fixed 指定小数位时返回字符串） */
    runNum(str: any, fixed?: number | string | null): number | string;
    /** 补零 */
    zeroFill(str: any, length: number, after?: boolean): string;
    /** 检测手机号码格式 */
    isMobile(str: any): boolean;
    /** 检测邮箱地址格式 */
    isEmail(email: any): boolean;
    /** 根据两点间的经纬度计算距离（米，字符串） */
    getDistance(lng1: number, lat1: number, lng2: number, lat2: number): string;
    /** 设置网页标题 */
    setTile(title: string): void;
    /** 克隆对象 */
    cloneJSON<T>(value: T, useParse?: boolean): T;
    /** 将一个 JSON 字符串转换为对象（已try） */
    jsonParse(str: any, defaultVal?: any): any;
    /** 将 JavaScript 值转换为 JSON 字符串（已try） */
    jsonStringify(json: any, defaultVal?: string): string;
    /** 监听对象尺寸发生改变 */
    resize(obj: any, callback?: () => void): void;
    /** 获取屏幕方向 */
    screenOrientation(): "landscape" | "portrait";
    /** 是否IOS */
    isIos(): boolean;
    /** 是否iPad */
    isIpad(): boolean;
    /** 是否安卓 */
    isAndroid(): boolean;
    /** 是否微信 */
    isWeixin(): boolean;
    /** 是否Chrome */
    isChrome(): boolean;
    /** 是否桌面端 */
    isDesktop(): boolean;
    /** 获取对象（支持 a.b.c 路径取值） */
    getObject(obj: any, keys: string | Array<string | number>, defaultValue?: any): any;
    /** 统计数组或对象长度 */
    count(obj: any): number;
    /** 获取文本长度 */
    stringLength(string: any): number;
    /** 获取数组长度（处理数组不存在） */
    arrayLength(array: any): number;
    /** 将数组或对象内容部分拼成字符串 */
    objImplode(obj: any): string;
    /** 指定键获取url参数（不传 key 返回全部） */
    urlParameter(key?: string): any;
    /** 获取所有url参数 */
    urlParameterAll(): Record<string, string>;
    /** 删除地址中的参数 */
    removeURLParameter(url: string, keys: string | string[]): string;
    /** 连接加上参数 */
    urlAddParams(url: string, params: Record<string, any>): string;
    /** 替换url中的hash（只传一个参数时视为 path，url 默认当前页面） */
    urlReplaceHash(url: string, path?: string): string;
    /** 刷新当前地址 */
    reloadUrl(): void;
    /** 链接字符串（第一个参数为连接符） */
    stringConnect(...value: any[]): string;
    /** 判断两个对象是否相等 */
    objEquals(x: any, y: any): boolean;
    /** 输入框内插入文本 */
    insert2Input(object: any, content: string): void;
    /** 输入框数字限制 */
    inputNumberLimit(object: any, min?: number | null, max?: number | null): void;
    /** iOS上虚拟键盘引起的触控错位修正 */
    iOSKeyboardFixer(): void;
    /** 动态加载js文件 */
    loadScript(url: string): Promise<boolean>;
    /** 按顺序动态加载多个js文件 */
    loadScriptS(urls: string[]): Promise<void>;
    /** 动态加载css文件 */
    loadCss(url: string): Promise<boolean>;
    /** 按顺序动态加载多个css文件 */
    loadCssS(urls: string[]): Promise<void>;
    /** 动态加载iframe */
    loadIframe(url: string, loadedRemove?: number): Promise<boolean>;
    /** 按顺序动态加载多个iframe */
    loadIframes(urls: string[]): Promise<void>;
    /** 字节转换（如 1024 -> "1 KB"） */
    bytesToSize(bytes: number): string;
    /** html代码转义 */
    html2Escape(sHtml: string): string;
    /** 正则提取域名 */
    getDomain(weburl: any): string;
    /** 提取 URL 协议 */
    getProtocol(weburl: any): string;
    /** 滚动到View */
    scrollToView(element: Element | null, options?: boolean | Record<string, any>): void;
    /** 按需滚动到View */
    scrollIntoViewIfNeeded(element?: Element | null, smooth?: boolean): void;
    /** 给元素添加一个class，过指定时间之后再去除这个class */
    addClassWithTimeout(element: Element | null, className: string, duration: number): void;
    /** 滚动到元素并抖动 */
    scrollIntoAndShake(element: Element | Element[] | null, viewIfNeeded?: boolean): void;
    /** 等比缩放尺寸 */
    scaleToScale(width: number, height: number, maxW: number, maxH?: number): { width: number; height: number };
    /** 阻止滑动穿透 */
    scrollPreventThrough(el: HTMLElement | null): void;
    /** 获取元素属性 */
    getAttr(el: Element | null, attrName: string, def?: string): string | null;
    /** 排序JSON对象 */
    sortObject(obj: Record<string, any>, ignore?: string[]): Record<string, any>;
    /** 从HTML中提取图片参数 */
    extractImageParameter(imgTag: string): DooTaskImageParameter;
    /** 从HTML中提取所有图片参数 */
    extractImageParameterAll(html: string): DooTaskImageParameter[];
    /** 增强版的字符串截取（超长自动加后缀） */
    cutString(str: string, length: number, start?: number, suffix?: string): string;
    /** 获取两个数组后面的交集 */
    getLastSameElements(arr1: any[], arr2: any[]): any[];
    /** 查找元素并在失败时重试 */
    findElementWithRetry(findElementFn: () => any, maxAttempts?: number, delayMs?: number): Promise<any>;
    /** 轮询等待条件满足 */
    waitForCondition(conditionFn: () => boolean, intervalMs?: number, timeoutMs?: number): Promise<boolean>;
    /** 执行指定次数的定时任务，返回取消函数 */
    repeatWithCount(fn: (count: number) => boolean | void, delay: number, interval?: number, times?: number): () => void;
    /** 通过URL生成base64图片 */
    generateBase64Image(url: string, quality?: number, maxWidth?: number, maxHeight?: number): Promise<string>;
    /** 是否全屏（根据尺寸对比） */
    isFullScreen(): boolean;

    /* =========================================================================
     * common.js —— localForage（IndexedDB）
     * ========================================================================= */

    /** 测试 IndexedDB 是否可用 */
    IDBTest(): Promise<boolean>;
    /** 延迟保存（防抖，默认 100ms） */
    IDBSave(key: string, value: any, delay?: number): void;
    /** 删除缓存 */
    IDBDel(key: string): Promise<void>;
    /** 设置缓存 */
    IDBSet(key: string, value: any): Promise<any>;
    /** 删除缓存（同 IDBDel） */
    IDBRemove(key: string): Promise<void>;
    /** 清除缓存（可指定保留的 key） */
    IDBClear(keysToKeep?: string[]): Promise<void>;
    /** 获取缓存值 */
    IDBValue(key: string): Promise<any>;
    /** 获取缓存值（字符串） */
    IDBString(key: string, def?: string): Promise<string | number>;
    /** 获取缓存值（整数） */
    IDBInt(key: string, def?: number): Promise<number>;
    /** 获取缓存值（布尔） */
    IDBBoolean(key: string, def?: boolean): Promise<boolean>;
    /** 获取缓存值（数组） */
    IDBArray(key: string, def?: any[]): Promise<any[]>;
    /** 获取缓存值（对象） */
    IDBJson(key: string, def?: Record<string, any>): Promise<Record<string, any>>;

    /* =========================================================================
     * common.js —— localStorage
     * ========================================================================= */

    /** 设置本地存储 */
    setStorage(key: string, value: any): void;
    /** 获取本地存储值 */
    getStorageValue(key: string): any;
    /** 获取本地存储值（字符串） */
    getStorageString(key: string, def?: string): string | number;
    /** 获取本地存储值（整数） */
    getStorageInt(key: string, def?: number): number;
    /** 获取本地存储值（布尔） */
    getStorageBoolean(key: string, def?: boolean): boolean;
    /** 获取本地存储值（数组） */
    getStorageArray(key: string, def?: any[]): any[];
    /** 获取本地存储值（对象） */
    getStorageJson(key: string, def?: Record<string, any>): Record<string, any>;
    /** 本地存储是否存在 */
    existsStorage(key: string): boolean;

    /* =========================================================================
     * common.js —— sessionStorage
     * ========================================================================= */

    /** 设置会话存储 */
    setSessionStorage(key: string, value: any): void;
    /** 获取会话存储值 */
    getSessionStorageValue(key: string): any;
    /** 获取会话存储值（字符串） */
    getSessionStorageString(key: string, def?: string): string | number;
    /** 获取会话存储值（整数） */
    getSessionStorageInt(key: string, def?: number): number;

    /* =========================================================================
     * common.js —— ihttp / ajaxc
     * ========================================================================= */

    /** 序列化对象为查询字符串 */
    serializeObject(obj: any, parents?: string[]): string;
    /** 全局 Ajax 配置 */
    globalAjaxOptions: Record<string, any>;
    /** 设置全局 Ajax 配置 */
    ajaxSetup(options: Record<string, any>): void;
    /** XHR 请求（jQuery.ajax 风格，JSONP 时无返回值） */
    ihttp(options: Record<string, any>): XMLHttpRequest | void;
    /** Ajax 请求封装（带请求列表管理，可用 ajaxcCancel 取消） */
    ajaxc(params: DooTaskAjaxcParams): void | false;
    /** 取消 ajaxc 请求，返回取消的数量 */
    ajaxcCancel(requestId: string | number): number;

    /* =========================================================================
     * common.js —— time / sort
     * ========================================================================= */

    /** 时间对象（dayjs 实例，自动识别 10/13 位时间戳；返回 any 以避免引入 dayjs 类型依赖） */
    dayjs(v?: any): any;
    /** 时间对象（减去时区差，dayjs 实例） */
    daytz(v?: any): any;
    /** 更新时区，返回时区差（小时） */
    updateTimezone(tz?: string): number;
    /** 当前时区名称 */
    timezoneName: string | null;
    /** 时区差（小时） */
    timezoneDifference: number;
    /** 对象中有Date格式的转成指定格式（支持 dayjs、Date、string，递归处理对象/数组） */
    newDateString(value: any, format?: string, key?: string | null): any;
    /** 对象中有Date格式的转成时间戳（递归处理对象/数组） */
    newTimestamp(value: any): any;
    /** 判断是否是日期格式（YYYY-MM-DD[ HH[:mm[:ss]]]） */
    isDateString(value: any): boolean;
    /** 秒数倒计时，格式：00:00:00, 00:00, 0s */
    secondsToTime(s: number): string;
    /** 格式化时间（本地时间自动减去时区差） */
    timeFormat(date: any): string;
    /** 倒计时（开始时间自动减去时区差） */
    countDownFormat(s: any, e: any): string;
    /** 计算排序值（日期格式） */
    sortDay(v1: any, v2: any): number;
    /** 计算排序值（数字格式） */
    sortFloat(v1: any, v2: any): number;

    /* =========================================================================
     * web.js —— 页面专用
     * ========================================================================= */

    /** 接口地址（补全为完整 API URL） */
    apiUrl(str: string): string;
    /** 主页地址 */
    mainUrl(str?: string | null): string;
    /** 获取 mainUrl 的域名 */
    mainDomain(): string;
    /** 移除 mainUrl 前缀（忽略 http/https 协议差异，只匹配域名） */
    removeMainUrlPrefix(url: any): string;
    /** 服务地址 */
    originUrl(str: string): string;
    /** 预览文件地址 */
    onlinePreviewUrl(name: string, key: string): string;
    /** 项目配置模板 */
    projectParameterTemplate(project_id: number | string): {
        project_id: number | string;
        menuInit: boolean;
        menuType: string;
        chat: boolean;
        showMy: boolean;
        showHelp: boolean;
        showUndone: boolean;
        showCompleted: boolean;
        completedTask: boolean;
    };
    /** 获取日期选择器的 shortcuts 模板参数 */
    timeOptionShortcuts(): Array<{ text: string; value(): [Date, Date] }>;
    /** 对话标签（已完成/已删除/已归档） */
    dialogTags(dialog: any): Array<{ color: string; text: string }>;
    /** 对话是否完成（返回 success 标签） */
    dialogCompleted(dialog: any): { color: string; text: string } | undefined;
    /** 返回对话未读数量（不含免打扰，但如果免打扰中有@则返回@数量） */
    getDialogNum(dialog: any): number;
    /** 返回对话未读数量（containSilence 是否包含免打扰消息） */
    getDialogUnread(dialog: any, containSilence?: boolean): number;
    /** 返回对话@提及未读数量 */
    getDialogMention(dialog: any): number;
    /** 返回文本信息预览格式 */
    getMsgTextPreview(msgData: { type?: string; text?: string }, imgClassName?: string | null): string;
    /** 消息格式化处理（将消息内的RemoteURL换成真实地址） */
    formatMsgBasic<T>(data: T): T;
    /** 消息格式化处理（@提及、链接、图片尺寸） */
    formatTextMsg(text: string, userid: number | string): string;
    /** 获取文本消息图片 */
    getTextImagesInfo(text: string): Array<{ src: string; width: number | string; height: number | string }>;
    /** 合并转发消息标题 */
    getMergeForwardTitle(msg: any): string;
    /** 消息简单描述 */
    getMsgSimpleDesc(data: any, imgClassName?: string | null): string;
    /** 文件消息简单描述 */
    fileMsgSimpleDesc(msg: any, imgClassName?: string | null): string;
    /** 模板消息简单描述 */
    templateMsgSimpleDesc(msg: any): string;
    /** 获取文件标题（name.ext） */
    getFileName(file: { name?: string; ext?: string }): string;
    /** 是否是doo服务器 */
    isDooServer(): boolean;
    /** 缩略图还原 */
    thumbRestore(url: any): string;
    /** 拖拽或粘贴的数据是否包含文件夹 */
    dataHasFolder(data: { items?: any }): boolean;
    /** 图片尺寸比例超出处理（裁剪 + 等比缩放） */
    imageRatioHandle(params: DooTaskImageRatioParams): DooTaskImageRatioParams;
    /** 判断图片地址是否满足比例缩放 */
    imageRatioJudge(url: string): boolean;
    /** 图片尺寸比例超出（返回超出时的 ratio，否则 0） */
    imageRatioExceed(width: number, height: number, ratio: number, float?: number): number;
    /** 去除html内容中无效的部分 */
    filterInvalidLine(content: any): string;
    /** 加载 VConsole 日志组件（key: 'log.o' 开 / 'log.c' 关） */
    loadVConsole(key?: string): boolean | void;
    /** 提取工作报告中的时间 */
    reportExtractTime(text: string): string;
    /** 根据十六进制颜色生成通用 CSS 变量样式 */
    generateColorVarStyle(hexColor: string, levels?: number[], prefix?: string, styles?: Record<string, string> | null): Record<string, string> | null;
    /** 转换工作流状态 */
    convertWorkflow(item: string | { flow_item_name?: string; complete_at?: any }): { status: string | null; name: string; color: string | null };

    /* =========================================================================
     * web.js —— iviewui assist（弹窗/提示/通知）
     * 注意：modal/message/notice 系列内部自动 $L 翻译，调用方勿再包 $L
     * （仅当 config.language === false 时由调用方自行处理翻译）。
     * ========================================================================= */

    /** 弹窗配置规范化（内部自动 $L 翻译 title/content/okText/cancelText，调用方勿再包 $L） */
    modalConfig(config?: string | DooTaskModalConfig): DooTaskModalConfig;
    /** 弹窗文案翻译辅助（language === false 时翻译，否则原样返回交给 modalConfig 统一翻译） */
    modalTranslation(title: string, language?: boolean): string;
    /** 输入弹窗（内部自动 $L 翻译，调用方勿再包 $L；millisecond 为延迟弹出毫秒数） */
    modalInput(config: string | DooTaskModalConfig, millisecond?: number): void;
    /** 确认弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalConfirm(config: string | DooTaskModalConfig | false, millisecond?: number): void;
    /** 成功弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalSuccess(config: string | DooTaskModalConfig | false, millisecond?: number): void;
    /** 信息弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalInfo(config: string | DooTaskModalConfig | false, millisecond?: number): void;
    /** 警告弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalWarning(config: string | DooTaskModalConfig | false, millisecond?: number): void;
    /** 错误弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalError(config: string | DooTaskModalConfig | false, millisecond?: number): void;
    /** alert 弹窗（内部自动 $L 翻译，调用方勿再包 $L） */
    modalAlert(msg: string | false): void;
    /** 成功提示（内部自动 $L 翻译，调用方勿再包 $L） */
    messageSuccess(msg: string): void;
    /** 信息提示（内部自动 $L 翻译，调用方勿再包 $L） */
    messageInfo(msg: string): void;
    /** 警告提示（内部自动 $L 翻译，调用方勿再包 $L） */
    messageWarning(msg: string | false): void;
    /** 错误提示（内部自动 $L 翻译，调用方勿再包 $L） */
    messageError(msg: string | false): void;
    /** 通知配置规范化（内部自动 $L 翻译 title/desc，调用方勿再包 $L） */
    noticeConfig(config?: string | DooTaskNoticeConfig): DooTaskNoticeConfig;
    /** 成功通知（内部自动 $L 翻译，调用方勿再包 $L） */
    noticeSuccess(config: string | DooTaskNoticeConfig | false): void;
    /** 警告通知（内部自动 $L 翻译，调用方勿再包 $L） */
    noticeWarning(config: string | DooTaskNoticeConfig | false): void;
    /** 错误通知（内部自动 $L 翻译，调用方勿再包 $L；字符串默认 duration 6 秒） */
    noticeError(config: string | DooTaskNoticeConfig | false): void;

    /* =========================================================================
     * web.js —— dark 暗黑模式
     * ========================================================================= */

    /** 暗黑模式工具对象 */
    dark: DooTaskDark;

    /* =========================================================================
     * eeui.js —— EEUI App 专用
     * ========================================================================= */

    /** 获取eeui模块 */
    eeuiModule(name?: string): any;
    /** 获取eeui模块（Promise） */
    eeuiModulePromise(name?: string): Promise<any>;
    /** 获取eeui版本号 */
    eeuiAppVersion(): string | undefined;
    /** 获取本地软件版本号 */
    eeuiAppLocalVersion(): string | undefined;
    /** Alert 弹窗 */
    eeuiAppAlert(object: any, callback?: (result: any) => void): void;
    /** Toast 提示 */
    eeuiAppToast(object: any): void;
    /** 相对地址基于当前地址补全 */
    eeuiAppRewriteUrl(val: string): string | undefined;
    /** 获取页面信息 */
    eeuiAppGetPageInfo(pageName?: string): any;
    /** 打开app新页面 */
    eeuiAppOpenPage(object: Record<string, any>, callback?: (result: any) => void): void;
    /** 使用系统浏览器打开网页 */
    eeuiAppOpenWeb(url: string): void;
    /** 拦截返回按键事件（仅支持android、iOS无效） */
    eeuiAppSetPageBackPressed(object: any, callback?: (result: any) => void): void;
    /** 返回手机桌面 */
    eeuiAppGoDesktop(): void;
    /** 打开屏幕常亮 */
    eeuiAppKeepScreenOn(): void;
    /** 关闭屏幕常亮 */
    eeuiAppKeepScreenOff(): void;
    /** 隐藏软键盘 */
    eeuiAppKeyboardHide(): void;
    /** 给app发送消息 */
    eeuiAppSendMessage(object: any): void;
    /** 设置浏览器地址 */
    eeuiAppSetUrl(url: string): void;
    /** 生成webview快照 */
    eeuiAppGetWebviewSnapshot(callback: (result: any) => void): void;
    /** 显示webview快照 */
    eeuiAppShowWebviewSnapshot(): void;
    /** 隐藏webview快照 */
    eeuiAppHideWebviewSnapshot(): void;
    /** 扫码（成功时回调扫码文本） */
    eeuiAppScan(callback: (text: string) => void): void;
    /** 检查更新 */
    eeuiAppCheckUpdate(): void;
    /** 获取主题名称 light|dark */
    eeuiAppGetThemeName(): string | undefined;
    /** 判断软键盘是否可见 */
    eeuiAppKeyboardStatus(): boolean | undefined;
    /** 设置全局变量 */
    eeuiAppSetVariate(key: string, value: any): void;
    /** 获取全局变量 */
    eeuiAppGetVariate(key: string, defaultVal?: any): any;
    /** 设置缓存数据 */
    eeuiAppSetCachesString(key: string, value: string, expired?: number): void;
    /** 获取缓存数据 */
    eeuiAppGetCachesString(key: string, defaultVal?: string): string | undefined;
    /** 是否长按内容震动（仅支持android、iOS无效） */
    eeuiAppSetHapticBackEnabled(val: boolean): void;
    /** 禁止长按选择（仅支持android、iOS无效；传毫秒数则临时禁止） */
    eeuiAppSetDisabledUserLongClickSelect(val: boolean | number | string): void;
    /** 复制文本 */
    eeuiAppCopyText(text: string): void;
    /** 设置是否禁止滚动 */
    eeuiAppSetScrollDisabled(disabled: boolean): void;
    /** 设置应用程序级别的摇动撤销（仅支持iOS、android无效） */
    eeuiAppShakeToEditEnabled(enabled: boolean): void;
    /** 获取最新一张照片 */
    eeuiAppGetLatestPhoto(expiration?: number, timeout?: number): Promise<any>;
    /** 上传照片（params 参数：{url,data,headers,path,fieldName,onReady?}） */
    eeuiAppUploadPhoto(params: Record<string, any>, timeout?: number): Promise<any>;
    /** 取消上传照片 */
    eeuiAppCancelUploadPhoto(id: any): Promise<any>;
    /** 获取导航栏和状态栏高度 */
    eeuiAppGetSafeAreaInsets(): Promise<any>;
    /** 获取当前语言（zh -> zh-Hans 等映射） */
    eeuiAppConvertLanguage(): string;
    /** 获取设备信息 */
    eeuiAppGetDeviceInfo(): Promise<any>;
    /** 判断是否窗口化 */
    eeuiAppIsWindowed(): Promise<boolean>;
}

/** DooTask 全局工具对象（jQuery 实例 + $.extend 扩展方法） */
declare const $A: DooTaskGlobal;

/**
 * 翻译函数（language/index.js switchLanguage）。
 * 动态值用 (*) 占位：$L('共(*)条', n)；禁止拼接翻译。
 */
declare function $L(text: string, ...args: Array<string | number>): string;

interface Window {
    $A: DooTaskGlobal;
    $L: typeof $L;
}
