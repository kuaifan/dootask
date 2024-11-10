class App {
    static #eeui = null;

    constructor() {
        this.constructor.init();
    }

    static async init() {
        while (typeof requireModuleJs !== "function") {
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        this.#eeui = requireModuleJs("eeui");
    }

    static async setVariate(key, value) {
        while (!this.#eeui) {
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        await this.#eeui.setVariate(key, value);
    }

    static async getLocation() {
        while (!this.#eeui) {
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        return new Promise(resolve => {
            this.#eeui.getGeolocation((res) => {
                resolve(res);
            });
        });
    }

    static closePage() {
        try {
            // 方法1: 如果是在 eeui 环境中
            if (this.#eeui) {
                this.#eeui.closePage();
            }

            // 方法2: 如果是从其他页面打开的，可以关闭当前窗口
            window.close();

            // 方法3: 如果是在 iOS WKWebView 中
            try {
                window.webkit.messageHandlers.closeWindow.postMessage(null);
            } catch (e) {}

            // 方法4: 如果是在 Android WebView 中
            try {
                window.android.closeWindow();
            } catch (e) {}

            // 方法5: 如果以上方法都失败，返回上一页
            window.history.back();
        } catch (e) {
            console.error('关闭页面失败', e);
        }
    }

    static isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    }

    static isJson(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
    }
}

class CoordTransform {
    // 私有静态常量
    static #x_PI = 3.14159265358979324 * 3000.0 / 180.0;
    static #PI = 3.1415926535897932384626;
    static #a = 6378245.0;
    static #ee = 0.00669342162296594323;

    /**
     * WGS84 转 BD09
     * @param {number} lng WGS84 经度
     * @param {number} lat WGS84 纬度
     * @returns {[number, number]} BD09 坐标 [经度, 纬度]
     */
    static wgs84toBd09(lng, lat) {
        const gcj = CoordTransform.wgs84ToGcj02(lng, lat);
        return CoordTransform.gcj02ToBd09(gcj[0], gcj[1]);
    }

    /**
     * WGS84 转 GCJ02
     * @private
     */
    static wgs84ToGcj02(lng, lat) {
        if (CoordTransform.outOfChina(lng, lat)) {
            return [lng, lat];
        }

        let dlat = CoordTransform.transformLat(lng - 105.0, lat - 35.0);
        let dlng = CoordTransform.transformLng(lng - 105.0, lat - 35.0);
        const radLat = lat / 180.0 * CoordTransform.#PI;
        let magic = Math.sin(radLat);
        magic = 1 - CoordTransform.#ee * magic * magic;
        const sqrtMagic = Math.sqrt(magic);
        dlat = (dlat * 180.0) / ((CoordTransform.#a * (1 - CoordTransform.#ee)) / (magic * sqrtMagic) * CoordTransform.#PI);
        dlng = (dlng * 180.0) / (CoordTransform.#a / sqrtMagic * Math.cos(radLat) * CoordTransform.#PI);
        const mgLat = lat + dlat;
        const mgLng = lng + dlng;
        return [mgLng, mgLat];
    }

    /**
     * GCJ02 转 BD09
     * @private
     */
    static gcj02ToBd09(lng, lat) {
        const z = Math.sqrt(lng * lng + lat * lat) + 0.00002 * Math.sin(lat * CoordTransform.#x_PI);
        const theta = Math.atan2(lat, lng) + 0.000003 * Math.cos(lng * CoordTransform.#x_PI);
        const bdLng = z * Math.cos(theta) + 0.0065;
        const bdLat = z * Math.sin(theta) + 0.006;
        return [bdLng, bdLat];
    }

    /**
     * 判断坐标是否在中国境内
     * @private
     */
    static outOfChina(lng, lat) {
        return (lng < 72.004 || lng > 137.8347) || (lat < 0.8293 || lat > 55.8271);
    }

    /**
     * 转换纬度
     * @private
     */
    static transformLat(lng, lat) {
        let ret = -100.0 + 2.0 * lng + 3.0 * lat + 0.2 * lat * lat +
            0.1 * lng * lat + 0.2 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * CoordTransform.#PI) + 20.0 *
            Math.sin(2.0 * lng * CoordTransform.#PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lat * CoordTransform.#PI) + 40.0 *
            Math.sin(lat / 3.0 * CoordTransform.#PI)) * 2.0 / 3.0;
        ret += (160.0 * Math.sin(lat / 12.0 * CoordTransform.#PI) + 320 *
            Math.sin(lat * CoordTransform.#PI / 30.0)) * 2.0 / 3.0;
        return ret;
    }

    /**
     * 转换经度
     * @private
     */
    static transformLng(lng, lat) {
        let ret = 300.0 + lng + 2.0 * lat + 0.1 * lng * lng +
            0.1 * lng * lat + 0.1 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * CoordTransform.#PI) + 20.0 *
            Math.sin(2.0 * lng * CoordTransform.#PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lng * CoordTransform.#PI) + 40.0 *
            Math.sin(lng / 3.0 * CoordTransform.#PI)) * 2.0 / 3.0;
        ret += (150.0 * Math.sin(lng / 12.0 * CoordTransform.#PI) + 300.0 *
            Math.sin(lng / 30.0 * CoordTransform.#PI)) * 2.0 / 3.0;
        return ret;
    }
}

class Loader {
    static #num = 0;

    static show() {
        this.#num++;
        this.judge();
    }

    static hide() {
        setTimeout(() => {
            this.#num--;
            this.judge();
        }, 100)
    }

    static judge() {
        if (this.#num > 0) {
            document.querySelector('.loading').classList.add('show');
        } else if (this.#num <= 0) {
            document.querySelector('.loading').classList.remove('show');
        }
    }
}

class BaiduMapPicker {
    constructor() {
        this.map = null;
        this.marker = null;
        this.localSearch = null;
        this.currentPoint = null;
        this.params = {
            theme: 'light',     // 主题风格，light|dark
            key: null,          // 百度地图 API Key
            title: null,        // 页面标题，如：选择打卡地点
            label: null,        // 搜索列表标签，如：附近的地点
            placeholder: null,  // 搜索框占位符，如：搜索附近的地点
            point: null,        // 初始坐标，如：116.404,39.915
            noresult: null,     // 无搜索结果提示，如：附近没有找到地点
            radius: 300,        // 搜索半径，单位：300
            zoom: 16,           // 地图缩放级别
            errtip: null,       // 定位失败提示
            channel: null,      // 回传数据通道
            selectclose: false, // 选择地点是否关闭页面
        };
        this.init();
    }

    async init() {
        // 先初始化参数
        this.initParams();

        // 如果没有 key，直接返回
        if (!this.params.key) {
            console.error('未提供百度地图 API Key');
            return;
        }

        try {
            // 等待地图 JS 加载完成
            await this.loadMapScript();
            // 初始化地图
            this.initMap();
        } catch (error) {
            console.error('加载百度地图失败：', error);
        }
    }

    /**
     * 初始化参数
     */
    initParams() {
        // 获取当前URL的查询参数
        const urlParams = new URLSearchParams(window.location.search);

        // 遍历 params 对象的所有属性
        Object.keys(this.params).forEach(key => {
            // 从 URL 参数中获取值
            const value = urlParams.get(key);
            if (value !== null) {
                // 根据参数类型进行转换
                switch (key) {
                    case 'radius':
                        // 转换为数字
                        this.params[key] = parseInt(value) || 300;
                        break;
                    case 'point':
                        // 转换为坐标数组
                        const [lng, lat] = value.replace(/[|-]/, ',').split(',').map(parseFloat);
                        if (lng && lat) {
                            this.params[key] = {lng, lat};
                        }
                        break;
                    default:
                        // 字符串类型直接赋值
                        this.params[key] = value;
                }
            }
        });

        // 设置主题风格
        if (!['dark', 'light'].includes(this.params.theme)) {
            this.params.theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.classList.add(`theme-${this.params.theme}`);
        document.body.style.backgroundColor = "#ffffff";


        // 设置标题
        if (this.params.title) {
            document.title = this.params.title;
        }

        // 设置搜索框占位符
        if (this.params.placeholder) {
            document.getElementById('search-input').placeholder = this.params.placeholder;
        }

        // 设置label
        if (this.params.label) {
            document.getElementById('address-label').innerText = this.params.label;
        }
    }

    /**
     * 初始化地图
     */
    initMap() {
        // 初始化地图
        this.map = new BMap.Map('map-container', {
            enableMapClick: false
        });

        // 初始化本地搜索，移除地图渲染
        this.localSearch = new BMap.LocalSearch(this.map, {
            renderOptions: {
                autoViewport: false  // 关闭自动视野调整
            }
        });

        // 设置地图中心点
        if (this.params.point) {
            const {lng, lat} = this.params.point;
            this.params.point = new BMap.Point(lng, lat);
            // 设置地图中心点和缩放级别
            this.map.centerAndZoom(this.params.point, this.params.zoom);
            // 创建圆形区域
            const circle = new BMap.Circle(this.params.point, this.params.radius, {
                fillColor: "#333333",
                fillOpacity: 0.1,
                strokeColor: "#333333",
                strokeWeight: 1,
                strokeOpacity: 0.3
            });
            this.map.addOverlay(circle);
        }

        // 绑定事件
        this.bindEvents();

        // 初始化时自动定位
        this.getCurrentLocation().catch(error => {
            this.locationError(error);
        }).finally(() => {
            document.getElementById('map-location').style.display = 'block';
        });
    }

    /**
     * 绑定事件
     */
    bindEvents() {
        // 输入框事件
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                searchInput.blur();
            }
        });
        searchInput.addEventListener('blur', () => {
            this.searchAddress();
        });

        // 地图定位点击事件
        const mapLocation = document.getElementById('map-location');
        mapLocation.addEventListener('click', () => {
            this.getCurrentLocation().catch(error => {
                this.locationError(error);
            });
        });
    }

    /**
     * 获取当前位置
     * @returns {Promise<unknown>}
     */
    getCurrentLocation() {
        return new Promise((resolve, reject) => {
            Loader.show()
            App.getLocation().then(res => {
                Loader.hide()
                if (!App.isJson(res)) {
                    console.error('定位失败', res);
                    reject("result error");
                    return;
                }
                if (res.status != 'success') {
                    console.error('定位失败', res);
                    reject(res.error || "unknown error");
                    return;
                }
                const bd09_coord = CoordTransform.wgs84toBd09(res.longitude, res.latitude);
                const point = new BMap.Point(bd09_coord[0], bd09_coord[1]);
                this.updateCurrentPoint(point)
                resolve(point);
            })
        })
    }

    /**
     * 更新当前位置
     * @param point
     */
    updateCurrentPoint(point) {
        this.currentPoint = point;
        if (Math.abs(this.map.getZoom() - this.params.zoom) > 1) {
            this.map.centerAndZoom(this.currentPoint, this.params.zoom);
        } else {
            this.map.setCenter(this.currentPoint);
        }
        this.updateMarker(this.currentPoint);
        this.searchAddress();
    }

    /**
     * 更新标记点
     * @param point
     */
    updateMarker(point) {
        if (this.marker) {
            this.marker.setPosition(point);
        } else {
            this.marker = new BMap.Marker(point);
            this.map.addOverlay(this.marker);
        }
    }

    /**
     * 搜索地址
     */
    searchAddress() {
        const keyword = `${document.getElementById('search-input').value}`.trim();
        if (keyword) {
            this.searchKeyword(this.currentPoint, keyword);
        } else {
            this.searchLocation(this.currentPoint);
        }
    }

    /**
     * 通过关键词搜索附近的地点
     * @param centerPoint
     * @param keyword
     * @param retryCount
     */
    searchKeyword(centerPoint, keyword, retryCount = 0) {
        // 清除之前的搜索结果
        this.localSearch.clearResults();

        // 定义一个随机数，用于判断定时器是否过期
        this.searchRandom = Math.random();

        // 设置搜索完成回调
        Loader.show();
        this.localSearch.setSearchCompleteCallback(result => {
            Loader.hide();
            if (this.localSearch.getStatus() !== BMAP_STATUS_SUCCESS) {
                // 搜索失败，10次重试
                if (retryCount < 10) {
                    const tmpRand = this.searchRandom;
                    Loader.show();
                    setTimeout(() => {
                        Loader.hide();
                        tmpRand === this.searchRandom && this.searchKeyword(centerPoint, keyword, ++retryCount);
                    }, 300)
                    return;
                }
            }
            // 搜索结果
            const pois = [];
            for (let i = 0; i < result.getCurrentNumPois(); i++) {
                const poi = result.getPoi(i);
                if (!poi.point) {
                    continue;
                }
                poi.distance = this.params.point ? this.map.getDistance(this.params.point, poi.point) : null;
                poi.distance_current = this.map.getDistance(centerPoint, poi.point);
                pois.push(poi);
            }
            this.updatePoiList(pois);
        });

        // 执行搜索
        this.localSearch.searchNearby(keyword, centerPoint, this.params.radius);
    }

    /**
     * 通过坐标搜索附近的地点
     * @param point
     */
    searchLocation(point) {
        const geoc = new BMap.Geocoder();
        Loader.show();
        geoc.getLocation(point, (result) => {
            Loader.hide();
            const pois = [];
            if (result) {
                // 搜索结果
                const surroundingPois = result.surroundingPois || [];
                if (surroundingPois.length === 0) {
                    surroundingPois.push({
                        title: result.addressComponents.city + result.addressComponents.district,
                        address: result.address,
                        point: result.point,
                    });
                }
                surroundingPois.some(poi => {
                    if (!poi.point) {
                        return false;
                    }
                    poi.distance = this.params.point ? this.map.getDistance(this.params.point, poi.point) : null;
                    poi.distance_current = this.map.getDistance(point, poi.point);
                    pois.push(poi);
                })
            }
            this.updatePoiList(pois)
        }, {
            poiRadius: this.params.radius,
            numPois: 20,
        });
    }

    /**
     * 更新搜索结果列表
     * @param pois
     */
    updatePoiList(pois) {
        const addressList = document.getElementById('address-list');
        addressList.style.display = 'block';

        const poiList = document.getElementById('poi-list');
        poiList.innerHTML = '';

        // 如果没有搜索结果
        if (pois.length === 0) {
            if (this.params.noresult) {
                poiList.innerHTML = '<li><div class="address-noresult">' + this.params.noresult + '</div></li>';
            }
            return;
        }

        // 筛选距离小于搜索半径的结果（+100）
        pois = pois.filter(poi => {
            return poi.title && poi.point && poi.distance_current < this.params.radius + 100;
        });

        // 按距离排序（如果有距离信息）
        pois.sort((a, b) => {
            if (a.distance_current && b.distance_current) {
                return a.distance_current - b.distance_current;
            }
            return 0;
        });

        // 只显示前20个结果
        pois = pois.slice(0, 20);

        // 创建列表项
        pois.forEach(poi => {
            const li = document.createElement('li');
            const distanceFormat = poi.distance ? `<div class="address-distance">${this.convertDistance(Math.round(poi.distance))}</div>` : '';
            li.innerHTML = `
                <div class="address-name">${poi.title}</div>
                <div class="address-detail">${poi.address || ""}${distanceFormat}</div>
            `;
            li.addEventListener('click', () => {
                const point = poi.point;
                this.updateMarker(point);
                this.map.setCenter(point);
                //
                App.setVariate("location::" + this.params.channel, JSON.stringify(poi));
                if (this.params.selectclose) {
                    App.closePage();
                }
            });
            poiList.appendChild(li);
        });

        // 列表更新后，重新将当前标记点居中显示
        setTimeout(() => {
            if (this.marker) {
                this.map.setCenter(this.marker.getPosition());
            }
        }, 100);  // 添加小延时确保DOM已更新
    }

    /**
     * 转换距离显示
     * @param d
     * @returns {string}
     */
    convertDistance(d) {
        if (d > 1000) {
            return (d / 1000).toFixed(1) + 'km';
        }
        return d.toFixed(0) + 'm';
    }

    /**
     * 定位失败提示
     */
    locationError(error) {
        if (this.params.errtip) {
            alert(this.params.errtip + '：' + error);
        } else {
            alert(error);
        }
    }

    /**
     * 加载百度地图脚本
     * @returns {Promise<unknown>}
     */
    loadMapScript() {
        return new Promise((resolve, reject) => {
            // 如果已经加载过，直接返回
            if (window.BMap) {
                resolve();
                return;
            }

            // 创建script标签
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = `https://api.map.baidu.com/api?v=3.0&ak=${this.params.key}&callback=initBaiduMap`;

            // 添加回调函数
            window.initBaiduMap = () => {
                resolve();
                delete window.initBaiduMap;
            };

            // 处理加载错误
            script.onerror = () => {
                reject(new Error('百度地图脚本加载失败'));
            };

            // 添加到页面
            document.body.appendChild(script);
        });
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    new App();
    new BaiduMapPicker();
});
