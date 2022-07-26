/**
 * Date: 2022/7/25
 *
 * @author zhaotao
 */
import cookie from "cookie";

/**
 * 尝试添加CSRF验证头
 *
 * @param method    请求方式
 * @param headers   请求头集合
 */
export function tryAddCSRFHeader(method, headers) {
    switch (method) {
        case 'DELETE':
        case "POST": case 'PUT': break;
        default:                 return;
    }

    let token = cookie.parse(document.cookie)['XSRF-TOKEN'];

    if ( !token ) {
        return;
    }

    headers['X-XSRF-TOKEN'] = token;
}
