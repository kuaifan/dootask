/**
 * AI 助手前端操作 WebSocket 客户端
 *
 * 负责与 MCP Server 建立 WebSocket 连接，
 * 接收来自 MCP 工具的请求并返回响应。
 */

const WS_PATH = '/apps/mcp_server/mcp/operation';
const RECONNECT_DELAY = 3000;
const MAX_RECONNECT_ATTEMPTS = 5;

/**
 * 前端操作客户端
 */
export class OperationClient {
    /**
     * @param {Object} options
     * @param {Function} options.getToken - 获取用户 token 的函数
     * @param {Function} options.onRequest - 处理请求的回调函数
     * @param {Function} options.onConnected - 连接成功回调
     * @param {Function} options.onDisconnected - 断开连接回调
     * @param {Function} options.onError - 错误回调
     */
    constructor(options = {}) {
        this.getToken = options.getToken;
        this.onRequest = options.onRequest;
        this.onConnected = options.onConnected;
        this.onDisconnected = options.onDisconnected;
        this.onError = options.onError;

        this.ws = null;
        this.sessionId = null;
        this.expiresAt = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.isConnecting = false;
        this.isManualClose = false;
    }

    /**
     * 建立 WebSocket 连接
     */
    connect() {
        if (this.ws && (this.ws.readyState === WebSocket.CONNECTING || this.ws.readyState === WebSocket.OPEN)) {
            return;
        }

        if (this.isConnecting) {
            return;
        }

        this.isConnecting = true;
        this.isManualClose = false;

        const token = this.getToken?.();
        if (!token) {
            this.isConnecting = false;
            this.onError?.('未登录或 token 不可用');
            return;
        }

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        const url = `${protocol}//${host}${WS_PATH}?token=${encodeURIComponent(token)}`;

        try {
            this.ws = new WebSocket(url);
            this.setupEventHandlers();
        } catch (error) {
            this.isConnecting = false;
            this.onError?.(error.message);
        }
    }

    /**
     * 设置 WebSocket 事件处理器
     */
    setupEventHandlers() {
        this.ws.onopen = () => {
            this.isConnecting = false;
            this.reconnectAttempts = 0;
        };

        this.ws.onmessage = (event) => {
            this.handleMessage(event.data);
        };

        this.ws.onclose = (event) => {
            this.isConnecting = false;
            this.sessionId = null;
            this.onDisconnected?.();

            if (!this.isManualClose && this.reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                this.scheduleReconnect();
            }
        };

        this.ws.onerror = () => {
            this.isConnecting = false;
            this.onError?.('WebSocket 连接错误');
        };
    }

    /**
     * 处理收到的消息
     */
    handleMessage(data) {
        let msg;
        try {
            msg = JSON.parse(data);
        } catch {
            return;
        }

        switch (msg.type) {
            case 'connected':
                this.sessionId = msg.session_id;
                this.expiresAt = msg.expires_at;
                this.onConnected?.(this.sessionId);
                break;

            case 'request':
                this.handleRequest(msg);
                break;

            case 'pong':
                // 心跳响应
                break;
        }
    }

    /**
     * 处理来自 MCP 的请求
     */
    async handleRequest(msg) {
        const { id, action, payload } = msg;

        if (!this.onRequest) {
            this.sendResponse(id, false, null, '请求处理器未配置');
            return;
        }

        try {
            const result = await this.onRequest(action, payload);
            this.sendResponse(id, true, result, null);
        } catch (error) {
            const errorMsg = error.message || '操作执行失败';
            this.sendResponse(id, false, null, errorMsg);
        }
    }

    /**
     * 发送响应
     */
    sendResponse(id, success, data, error) {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return;
        }

        const response = {
            type: 'response',
            id,
            success,
            data,
            error,
        };

        this.ws.send(JSON.stringify(response));
    }

    /**
     * 发送心跳
     */
    ping() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({ type: 'ping' }));
        }
    }

    /**
     * 安排重连
     */
    scheduleReconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }

        this.reconnectAttempts++;
        const delay = RECONNECT_DELAY * this.reconnectAttempts;

        this.reconnectTimer = setTimeout(() => {
            this.connect();
        }, delay);
    }

    /**
     * 断开连接
     */
    disconnect() {
        this.isManualClose = true;

        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }

        this.sessionId = null;
        this.reconnectAttempts = 0;
    }

    /**
     * 获取当前 session ID
     */
    getSessionId() {
        return this.sessionId;
    }

    /**
     * 检查是否已连接
     */
    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN && this.sessionId;
    }
}

export default OperationClient;
