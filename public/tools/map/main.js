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
     * 生成百度地图缩略图
     * @param point
     * @returns {string}
     */
    generateThumb(point) {
        if (!point || !this.params.key) return null;
        
        const params = new URLSearchParams({
            ak: this.params.key,
            center: `${point.lng},${point.lat}`,
            markers: `${point.lng},${point.lat}`,
            width: 800,
            height: 480,
            zoom: 17,
            copyright: 1,
        });
        
        return `https://api.map.baidu.com/staticimage/v2?${params.toString()}`;
    }

    /**
     * 更新搜索结果列表
     * @param pois
     */
    updatePoiList(pois) {
        const addressList = document.getElementById('address-list');
        addressList.style.display = 'flex';

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
                return this.sortFloat(a.distance_current, b.distance_current);
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
                App.setVariate("location::" + this.params.channel, JSON.stringify(Object.assign(poi, {
                    thumb: this.generateThumb(poi.point)
                })));
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
     * 计算排序值 （数字格式）
     * @param v1
     * @param v2
     * @returns {number}
     */
    sortFloat(v1, v2) {
        if (v1 === v2) return 0;
        return (parseFloat(v1) || 0) - (parseFloat(v2) || 0);
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

// 高德地图选点类
class AmapPicker {
    constructor() {
        this.map = null;
        this.marker = null;
        this.placeSearch = null;
        this.currentPoint = null;
        this.params = {
            theme: 'light',
            key: null,
            title: null,
            label: null,
            placeholder: null,
            point: null,
            noresult: null,
            radius: 300,
            zoom: 16,
            errtip: null,
            channel: null,
            selectclose: false,
        };
        this.init();
    }

    async init() {
        this.initParams();
        if (!this.params.key) {
            console.error('未提供高德地图 API Key');
            return;
        }
        
        try {
            await this.loadMapScript();
            this.initMap();
        } catch (error) {
            console.error('加载高德地图失败：', error);
        }
    }

    initParams() {
        const urlParams = new URLSearchParams(window.location.search);
        Object.keys(this.params).forEach(key => {
            const value = urlParams.get(key);
            if (value !== null) {
                switch (key) {
                    case 'radius':
                        this.params[key] = parseInt(value) || 300;
                        break;
                    case 'point':
                        const [lng, lat] = value.replace(/[|-]/, ',').split(',').map(parseFloat);
                        if (lng && lat) {
                            this.params[key] = {lng, lat};
                        }
                        break;
                    default:
                        this.params[key] = value;
                }
            }
        });

        if (!['dark', 'light'].includes(this.params.theme)) {
            this.params.theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.classList.add(`theme-${this.params.theme}`);
        document.body.style.backgroundColor = "#ffffff";

        if (this.params.title) document.title = this.params.title;
        if (this.params.placeholder) document.getElementById('search-input').placeholder = this.params.placeholder;
        if (this.params.label) document.getElementById('address-label').innerText = this.params.label;
    }

    initMap() {
        this.map = new AMap.Map('map-container', {
            zoom: this.params.zoom,
            center: this.params.point ? [this.params.point.lng, this.params.point.lat] : [116.404, 39.915],
            keyboardEnable: false
        });

        // 设置签到范围圆形
        if (this.params.point) {
            const circle = new AMap.Circle({
                center: [this.params.point.lng, this.params.point.lat],
                radius: this.params.radius,
                strokeColor: '#333333',
                strokeWeight: 1,
                strokeOpacity: 0.3,
                fillColor: '#333333',
                fillOpacity: 0.1,
                map: this.map
            });
        }

        this.bindEvents();
        this.getCurrentLocation().catch(error => {
            this.locationError(error);
        }).finally(() => {
            document.getElementById('map-location').style.display = 'block';
        });
    }

    bindEvents() {
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') searchInput.blur();
        });
        searchInput.addEventListener('blur', () => this.searchAddress());

        document.getElementById('map-location').addEventListener('click', () => {
            this.getCurrentLocation().catch(error => this.locationError(error));
        });
    }

    getCurrentLocation() {
        return new Promise((resolve, reject) => {
            Loader.show();
            App.getLocation().then(res => {
                Loader.hide();
                if (!App.isJson(res) || res.status !== 'success') {
                    reject(res.error || "unknown error");
                    return;
                }
                // WGS84坐标转换为GCJ02坐标系（高德地图使用的坐标系）
                const gcj02_coord = CoordTransform.wgs84ToGcj02(res.longitude, res.latitude);
                const point = [gcj02_coord[0], gcj02_coord[1]];
                this.updateCurrentPoint(point);
                resolve(point);
            });
        });
    }

    updateCurrentPoint(point) {
        this.currentPoint = point;
        this.map.setCenter(point);
        this.map.setZoom(this.params.zoom);
        this.updateMarker(point);
        this.searchAddress();
    }

    updateMarker(point) {
        if (this.marker) {
            this.marker.setPosition(point);
        } else {
            this.marker = new AMap.Marker({
                position: point,
                map: this.map
            });
        }
    }

    searchAddress() {
        const keyword = document.getElementById('search-input').value.trim();
        if (keyword) {
            this.searchKeyword(keyword);
        } else {
            this.searchLocation();
        }
    }

    searchKeyword(keyword) {
        if (!keyword || !this.currentPoint) return;
        
        Loader.show();
        // 使用Web服务API进行搜索，避免密钥平台不匹配问题
        const searchUrl = `https://restapi.amap.com/v3/place/around?key=${this.params.key}&location=${this.currentPoint[0]},${this.currentPoint[1]}&keywords=${encodeURIComponent(keyword)}&radius=${this.params.radius}&offset=20&page=1&extensions=all`;
        
        fetch(searchUrl)
            .then(response => response.json())
            .then(data => {
                Loader.hide();
                console.log('高德地图Web服务搜索结果:', data);
                if (data.status === '1' && data.pois) {
                    // 转换数据格式以匹配原有的处理逻辑
                    const pois = data.pois.map(poi => ({
                        name: poi.name,
                        address: poi.address,
                        location: {
                            lng: parseFloat(poi.location.split(',')[0]),
                            lat: parseFloat(poi.location.split(',')[1])
                        }
                    }));
                    this.updatePoiList(pois);
                } else {
                    console.log('高德地图Web服务搜索失败:', data);
                    this.updatePoiList([]);
                }
            })
            .catch(error => {
                Loader.hide();
                console.error('高德地图Web服务搜索错误:', error);
                this.updatePoiList([]);
            });
    }

    searchLocation() {
        if (!this.currentPoint) return;
        
        Loader.show();
        // 使用Web服务API获取附近POI，避免密钥平台不匹配问题
        const geocodeUrl = `https://restapi.amap.com/v3/geocode/regeo?key=${this.params.key}&location=${this.currentPoint[0]},${this.currentPoint[1]}&radius=${this.params.radius}&extensions=all&batch=false&roadlevel=0`;
        
        fetch(geocodeUrl)
            .then(response => response.json())
            .then(data => {
                Loader.hide();
                console.log('高德地图Web服务逆地理编码结果:', data);
                if (data.status === '1' && data.regeocode && data.regeocode.pois) {
                    // 转换数据格式以匹配原有的处理逻辑
                    const pois = data.regeocode.pois.map(poi => ({
                        name: poi.name,
                        address: poi.address,
                        location: {
                            lng: parseFloat(poi.location.split(',')[0]),
                            lat: parseFloat(poi.location.split(',')[1])
                        }
                    }));
                    this.updatePoiList(pois);
                } else {
                    console.log('高德地图Web服务逆地理编码失败:', data);
                    // 如果没有POI数据，尝试使用周边搜索
                    this.searchNearbyPlaces();
                }
            })
            .catch(error => {
                Loader.hide();
                console.error('高德地图Web服务逆地理编码错误:', error);
                // 网络错误时尝试周边搜索
                this.searchNearbyPlaces();
            });
    }

    // 周边地点搜索的备用方法
    searchNearbyPlaces() {
        if (!this.currentPoint) return;
        
        Loader.show();
        // 搜索常见的POI类型
        const keywords = ['餐厅|商店|银行|医院|学校|酒店|超市|加油站'];
        const searchUrl = `https://restapi.amap.com/v3/place/around?key=${this.params.key}&location=${this.currentPoint[0]},${this.currentPoint[1]}&keywords=${keywords}&radius=${this.params.radius}&offset=20&page=1&extensions=all`;
        
        fetch(searchUrl)
            .then(response => response.json())
            .then(data => {
                Loader.hide();
                console.log('高德地图周边搜索结果:', data);
                if (data.status === '1' && data.pois) {
                    const pois = data.pois.map(poi => ({
                        name: poi.name,
                        address: poi.address,
                        location: {
                            lng: parseFloat(poi.location.split(',')[0]),
                            lat: parseFloat(poi.location.split(',')[1])
                        }
                    }));
                    this.updatePoiList(pois);
                } else {
                    console.log('高德地图周边搜索也失败:', data);
                    this.updatePoiList([]);
                }
            })
            .catch(error => {
                Loader.hide();
                console.error('高德地图周边搜索错误:', error);
                this.updatePoiList([]);
            });
    }

    /**
     * 生成高德地图缩略图
     * @param point
     * @returns {string}
     */
    generateThumb(point) {
        if (!point || !this.params.key) return null;
        
        const params = new URLSearchParams({
            key: this.params.key,
            location: `${point.lng},${point.lat}`,
            zoom: 17,
            size: '800*480',
            markers: `mid,,A:${point.lng},${point.lat}`,
        });
        
        return `https://restapi.amap.com/v3/staticmap?${params.toString()}`;
    }

    updatePoiList(pois) {
        const addressList = document.getElementById('address-list');
        addressList.style.display = 'flex';

        const poiList = document.getElementById('poi-list');
        poiList.innerHTML = '';

        console.log('高德地图POI列表:', pois);

        if (pois.length === 0) {
            if (this.params.noresult) {
                poiList.innerHTML = '<li><div class="address-noresult">' + this.params.noresult + '</div></li>';
            }
            return;
        }

        // 计算距离并过滤
        const filteredPois = pois.filter(poi => {
            // 确保POI有位置信息
            if (!poi.location) {
                console.log('POI缺少位置信息:', poi);
                return false;
            }
            
            // 计算到签到中心点的距离
            if (this.params.point) {
                poi.distance = this.calculateDistance(
                    this.params.point.lat, this.params.point.lng,
                    poi.location.lat, poi.location.lng
                );
            } else {
                poi.distance = null;
            }
            
            // 计算到当前位置的距离
            poi.distance_current = this.calculateDistance(
                this.currentPoint[1], this.currentPoint[0],
                poi.location.lat, poi.location.lng
            );
            
            return poi.distance_current < this.params.radius + 100;
        }).sort((a, b) => a.distance_current - b.distance_current).slice(0, 20);

        console.log('过滤后的POI列表:', filteredPois);

        filteredPois.forEach(poi => {
            const li = document.createElement('li');
            const distanceFormat = poi.distance ? `<div class="address-distance">${this.convertDistance(Math.round(poi.distance))}</div>` : '';
            li.innerHTML = `
                <div class="address-name">${poi.name || poi.title}</div>
                <div class="address-detail">${poi.address || ""}${distanceFormat}</div>
            `;
            li.addEventListener('click', () => {
                const point = [poi.location.lng, poi.location.lat];
                this.updateMarker(point);
                this.map.setCenter(point);
                App.setVariate("location::" + this.params.channel, JSON.stringify({
                    title: poi.name || poi.title,
                    address: poi.address || "",
                    point: {lng: poi.location.lng, lat: poi.location.lat},
                    distance: poi.distance,
                    thumb: this.generateThumb(poi.location)
                }));
                if (this.params.selectclose) {
                    App.closePage();
                }
            });
            poiList.appendChild(li);
        });
    }

    // 简单的距离计算方法（当AMap.GeometryUtil不可用时使用）
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // 地球半径（米）
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    convertDistance(d) {
        if (d > 1000) {
            return (d / 1000).toFixed(1) + 'km';
        }
        return d.toString() + 'm';
    }

    locationError(error) {
        if (this.params.errtip) {
            alert(this.params.errtip + '：' + error);
        } else {
            alert(error);
        }
    }

    loadMapScript() {
        return new Promise((resolve, reject) => {
            if (window.AMap) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.type = 'text/javascript';
            // 只加载基础地图API，POI搜索使用Web服务API
            script.src = `https://webapi.amap.com/maps?v=1.4.15&key=${this.params.key}&callback=initAmapMap`;

            window.initAmapMap = () => {
                console.log('高德地图加载完成');
                resolve();
                delete window.initAmapMap;
            };

            script.onerror = () => {
                reject(new Error('高德地图脚本加载失败'));
            };

            document.body.appendChild(script);
        });
    }
}

// 腾讯地图选点类
class TencentMapPicker {
    constructor() {
        this.map = null;
        this.marker = null;
        this.currentPoint = null;
        this.params = {
            theme: 'light',
            key: null,
            title: null,
            label: null,
            placeholder: null,
            point: null,
            noresult: null,
            radius: 300,
            zoom: 16,
            errtip: null,
            channel: null,
            selectclose: false,
        };
        this.init();
    }

    async init() {
        this.initParams();
        if (!this.params.key) {
            console.error('未提供腾讯地图 API Key');
            return;
        }
        
        try {
            await this.loadMapScript();
            this.initMap();
        } catch (error) {
            console.error('加载腾讯地图失败：', error);
        }
    }

    initParams() {
        const urlParams = new URLSearchParams(window.location.search);
        Object.keys(this.params).forEach(key => {
            const value = urlParams.get(key);
            if (value !== null) {
                switch (key) {
                    case 'radius':
                        this.params[key] = parseInt(value) || 300;
                        break;
                    case 'point':
                        const [lng, lat] = value.replace(/[|-]/, ',').split(',').map(parseFloat);
                        if (lng && lat) {
                            this.params[key] = {lng, lat};
                        }
                        break;
                    default:
                        this.params[key] = value;
                }
            }
        });

        if (!['dark', 'light'].includes(this.params.theme)) {
            this.params.theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.classList.add(`theme-${this.params.theme}`);
        document.body.style.backgroundColor = "#ffffff";

        if (this.params.title) document.title = this.params.title;
        if (this.params.placeholder) document.getElementById('search-input').placeholder = this.params.placeholder;
        if (this.params.label) document.getElementById('address-label').innerText = this.params.label;
    }

    initMap() {
        const center = this.params.point ? 
            new TMap.LatLng(this.params.point.lat, this.params.point.lng) : 
            new TMap.LatLng(39.915, 116.404);
            
        this.map = new TMap.Map('map-container', {
            center: center,
            zoom: this.params.zoom,
            disableKeyboard: true,
            showControl: false,
            showScale: false,
            showZoom: false
        });

        // 设置签到范围圆形
        if (this.params.point) {
            const circle = new TMap.MultiCircle({
                map: this.map,
                geometries: [{
                    id: 'circle1',
                    center: new TMap.LatLng(this.params.point.lat, this.params.point.lng),
                    radius: this.params.radius
                }]
            });
        }

        this.bindEvents();
        this.getCurrentLocation().catch(error => {
            this.locationError(error);
        }).finally(() => {
            document.getElementById('map-location').style.display = 'block';
        });
    }

    bindEvents() {
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') searchInput.blur();
        });
        searchInput.addEventListener('blur', () => this.searchAddress());

        document.getElementById('map-location').addEventListener('click', () => {
            this.getCurrentLocation().catch(error => this.locationError(error));
        });
    }

    getCurrentLocation() {
        return new Promise((resolve, reject) => {
            Loader.show();
            App.getLocation().then(res => {
                Loader.hide();
                if (!App.isJson(res) || res.status !== 'success') {
                    reject(res.error || "unknown error");
                    return;
                }
                // WGS84坐标转换为GCJ02坐标系（腾讯地图使用的坐标系）
                const gcj02_coord = CoordTransform.wgs84ToGcj02(res.longitude, res.latitude);
                const point = new TMap.LatLng(gcj02_coord[1], gcj02_coord[0]);
                this.updateCurrentPoint(point);
                resolve(point);
            });
        });
    }

    updateCurrentPoint(point) {
        this.currentPoint = point;
        this.map.setCenter(point);
        this.map.setZoom(this.params.zoom);
        this.updateMarker(point);
        this.searchAddress();
    }

    updateMarker(point) {
        if (this.marker) {
            this.marker.updateGeometries([{
                id: 'marker1',
                position: point
            }]);
        } else {
            this.marker = new TMap.MultiMarker({
                map: this.map,
                geometries: [{
                    id: 'marker1',
                    position: point
                }]
            });
        }
    }

    searchAddress() {
        const keyword = document.getElementById('search-input').value.trim();
        if (keyword) {
            this.searchKeyword(keyword);
        } else {
            this.searchLocation();
        }
    }

    searchKeyword(keyword) {
        if (!this.currentPoint || !keyword) {
            this.updatePoiList([]);
            return;
        }
        
        Loader.show();
        // 使用JSONP方式调用腾讯地图Web服务API，避免跨域
        this.searchWithJsonp(`https://apis.map.qq.com/ws/place/v1/search?boundary=nearby(${this.currentPoint.lat},${this.currentPoint.lng},${this.params.radius})&keyword=${encodeURIComponent(keyword)}&page_size=20&page_index=1&orderby=_distance&key=${this.params.key}&output=jsonp&callback=tencentSearchCallback`);
    }

    searchLocation() {
        if (!this.currentPoint) return;
        
        Loader.show();
        // 使用JSONP方式调用腾讯地图逆地理编码API
        this.searchWithJsonp(`https://apis.map.qq.com/ws/geocoder/v1/?location=${this.currentPoint.lat},${this.currentPoint.lng}&key=${this.params.key}&get_poi=1&poi_options=radius=${this.params.radius};page_size=20&output=jsonp&callback=tencentGeocodeCallback`);
    }

    searchWithJsonp(url) {
        // 生成唯一的回调函数名
        const callbackId = 'tencentCallback_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const isSearchApi = url.includes('/place/v1/search');
        const actualCallback = isSearchApi ? 'tencentSearchCallback' : 'tencentGeocodeCallback';
        
        // 替换URL中的回调函数名
        const actualUrl = url.replace(/callback=[^&]+/, `callback=${actualCallback}`);
        
        // 保存this引用
        const self = this;
        
        // 设置全局回调函数
        if (isSearchApi) {
            window.tencentSearchCallback = (data) => {
                Loader.hide();
                console.log('腾讯地图JSONP搜索结果:', data);
                if (data.status === 0 && data.data) {
                    const pois = data.data.map(item => ({
                        title: item.title,
                        address: item.address,
                        point: {lng: item.location.lng, lat: item.location.lat},
                        distance: self.params.point ? self.calculateDistance(
                            self.params.point.lat, self.params.point.lng,
                            item.location.lat, item.location.lng
                        ) : null
                    }));
                    self.updatePoiList(pois);
                } else {
                    console.log('腾讯地图JSONP搜索失败:', data);
                    self.updatePoiList([]);
                }
                // 清理全局回调函数
                setTimeout(() => {
                    delete window.tencentSearchCallback;
                }, 100);
            };
        } else {
            window.tencentGeocodeCallback = (data) => {
                Loader.hide();
                console.log('腾讯地图JSONP逆地理编码结果:', data);
                if (data.status === 0 && data.result) {
                    const pois = [];
                    
                    // 只添加附近POI，不添加当前位置
                    if (data.result.pois) {
                        data.result.pois.forEach(poi => {
                            pois.push({
                                title: poi.title,
                                address: poi.address,
                                point: {lng: poi.location.lng, lat: poi.location.lat},
                                distance: self.params.point ? self.calculateDistance(
                                    self.params.point.lat, self.params.point.lng,
                                    poi.location.lat, poi.location.lng
                                ) : null
                            });
                        });
                    }
                    
                    self.updatePoiList(pois);
                } else {
                    console.log('腾讯地图JSONP逆地理编码失败:', data);
                    self.updatePoiList([]);
                }
                // 清理全局回调函数
                setTimeout(() => {
                    delete window.tencentGeocodeCallback;
                }, 100);
            };
        }

        // 创建script标签进行JSONP调用
        const script = document.createElement('script');
        script.src = actualUrl;
        script.onerror = () => {
            Loader.hide();
            console.error('腾讯地图JSONP调用失败');
            self.updatePoiList([]);
            // 清理
            document.body.removeChild(script);
            if (isSearchApi) {
                delete window.tencentSearchCallback;
            } else {
                delete window.tencentGeocodeCallback;
            }
        };
        
        script.onload = () => {
            // 移除script标签
            setTimeout(() => {
                if (document.body.contains(script)) {
                    document.body.removeChild(script);
                }
            }, 100);
        };

        document.body.appendChild(script);
    }

    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // 地球半径（米）
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    /**
     * 生成腾讯地图缩略图
     * @param point
     * @returns {string}
     */
    generateThumb(point) {
        if (!point || !this.params.key) return null;
        
        const params = new URLSearchParams({
            key: this.params.key,
            center: `${point.lat},${point.lng}`,
            zoom: 18,
            size: '800*480',
            maptype: 'roadmap',
            markers: `size:large|color:0xFF0000|label:A|${point.lat},${point.lng}`,
        });
        
        return `https://apis.map.qq.com/ws/staticmap/v2/?${params.toString()}`;
    }

    updatePoiList(pois) {
        const addressList = document.getElementById('address-list');
        addressList.style.display = 'flex';

        const poiList = document.getElementById('poi-list');
        poiList.innerHTML = '';

        if (pois.length === 0) {
            if (this.params.noresult) {
                poiList.innerHTML = '<li><div class="address-noresult">' + this.params.noresult + '</div></li>';
            }
            return;
        }

        pois.forEach(poi => {
            const li = document.createElement('li');
            const distanceFormat = poi.distance ? `<div class="address-distance">${this.convertDistance(Math.round(poi.distance))}</div>` : '';
            li.innerHTML = `
                <div class="address-name">${poi.title}</div>
                <div class="address-detail">${poi.address || ""}${distanceFormat}</div>
            `;
            li.addEventListener('click', () => {
                const point = new TMap.LatLng(poi.point.lat, poi.point.lng);
                this.updateMarker(point);
                this.map.setCenter(point);
                App.setVariate("location::" + this.params.channel, JSON.stringify(Object.assign(poi, {
                    thumb: this.generateThumb(poi.point)
                })));
                if (this.params.selectclose) {
                    App.closePage();
                }
            });
            poiList.appendChild(li);
        });
    }

    convertDistance(d) {
        if (d > 1000) {
            return (d / 1000).toFixed(1) + 'km';
        }
        return d.toString() + 'm';
    }

    locationError(error) {
        if (this.params.errtip) {
            alert(this.params.errtip + '：' + error);
        } else {
            alert(error);
        }
    }

    loadMapScript() {
        return new Promise((resolve, reject) => {
            if (window.TMap) {
                resolve();
                return;
            }

            // 只加载GL版本用于地图显示，POI搜索使用JSONP调用Web服务API
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = `https://map.qq.com/api/gljs?v=1.exp&key=${this.params.key}&callback=initTencentMap`;

            window.initTencentMap = () => {
                resolve();
                delete window.initTencentMap;
            };

            script.onerror = () => {
                reject(new Error('腾讯地图脚本加载失败'));
            };

            document.body.appendChild(script);
        });
    }
}

// 地图工厂类
class MapFactory {
    static createMap() {
        const urlParams = new URLSearchParams(window.location.search);
        const mapType = urlParams.get('type') || 'baidu';
        
        switch (mapType) {
            case 'amap':
                return new AmapPicker();
            case 'tencent':
                return new TencentMapPicker();
            case 'baidu':
            default:
                return new BaiduMapPicker();
        }
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    new App();
    MapFactory.createMap();
});
