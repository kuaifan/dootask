class LocationPicker {
    constructor() {
        this.map = null;
        this.marker = null;
        this.geolocation = null;
        this.localSearch = null;
        this.currentPoint = null;
        this.loadNum = 0;
        this.config = {
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
            errclose: false,    // 定位失败是否关闭页面
            channel: null,      // 回传数据通道
            selectclose: false, // 选择地点是否关闭页面
        };
        this.init();
    }

    async init() {
        // 先初始化参数
        this.initParams();

        // 如果没有 key，直接返回
        if (!this.config.key) {
            console.error('未提供百度地图 API Key');
            return;
        }

        try {
            // 等待地图 JS 加载完成
            await this.loadBaiduMapScript();
            // 初始化地图
            this.initMap();
        } catch (error) {
            console.error('加载百度地图失败：', error);
        }
    }

    initParams() {
        // 获取当前URL的查询参数
        const urlParams = new URLSearchParams(window.location.search);

        // 遍历 config 对象的所有属性
        Object.keys(this.config).forEach(key => {
            // 从 URL 参数中获取值
            const value = urlParams.get(key);
            if (value !== null) {
                // 根据参数类型进行转换
                switch (key) {
                    case 'radius':
                        // 转换为数字
                        this.config[key] = parseInt(value) || 300;
                        break;
                    case 'point':
                        // 转换为坐标数组
                        const [lng, lat] = value.replace(/[|-]/, ',').split(',').map(parseFloat);
                        if (lng && lat) {
                            this.config[key] = {lng, lat};
                        }
                        break;
                    default:
                        // 字符串类型直接赋值
                        this.config[key] = value;
                }
            }
        });

        // 设置主题风格
        document.documentElement.classList.add(`theme-${this.config.theme}`);
        document.body.style.backgroundColor = "#ffffff";


        // 设置标题
        if (this.config.title) {
            document.title = this.config.title;
        }

        // 设置搜索框占位符
        if (this.config.placeholder) {
            document.getElementById('search-input').placeholder = this.config.placeholder;
        }

        // 设置label
        if (this.config.label) {
            document.getElementById('address-label').innerText = this.config.label;
        }
    }

    initMap() {
        // 初始化地图
        this.map = new BMap.Map('map-container');

        // 创建定位控件
        const locationControl = new BMap.GeolocationControl({
            anchor: BMAP_ANCHOR_BOTTOM_RIGHT,
            showAddressBar: false,
            enableAutoLocation: false,
            locationIcon: new BMap.Icon("empty.svg", new BMap.Size(0, 0))
        });

        // 监听定位事件
        locationControl.addEventListener("locationSuccess", (e) => {
            // 定位成功事件
            this.updateCurrentPoint(e.point);
        });

        locationControl.addEventListener("locationError", (e) => {
            // 定位失败事件
            console.error('定位失败：', e.message);
            this.locationError();
        });

        // 添加定位控件到地图
        this.map.addControl(locationControl);

        // 初始化本地搜索，移除地图渲染
        this.localSearch = new BMap.LocalSearch(this.map, {
            renderOptions: {
                autoViewport: false  // 关闭自动视野调整
            }
        });

        // 设置地图中心点
        if (this.config.point) {
            const {lng, lat} = this.config.point;
            this.config.point = new BMap.Point(lng, lat);
            // 设置地图中心点和缩放级别
            this.map.centerAndZoom(this.config.point, this.config.zoom);
            // 创建圆形区域
            const circle = new BMap.Circle(this.config.point, this.config.radius, {
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
        this.getCurrentLocation();
    }

    bindEvents() {
        const searchInput = document.getElementById('search-input');

        // 监听回车键
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                searchInput.blur();
            }
        });

        // 监听失去焦点
        searchInput.addEventListener('blur', () => {
            this.searchAddress();
        });
    }

    getCurrentLocation() {
        this.loaderShow();
        this.geolocation = new BMap.Geolocation();
        this.geolocation.getCurrentPosition((result) => {
            this.loaderHide();
            if (result && result.point) {
                this.updateCurrentPoint(result.point)
            } else {
                console.error('定位失败');
                this.locationError();
            }
        }, {enableHighAccuracy: true});
    }

    updateCurrentPoint(point) {
        this.currentPoint = point;
        this.map.centerAndZoom(this.currentPoint, this.config.zoom);
        this.updateMarker(this.currentPoint);
        this.searchNearby();
    }

    updateMarker(point) {
        if (this.marker) {
            this.marker.setPosition(point);
        } else {
            this.marker = new BMap.Marker(point);
            this.map.addOverlay(this.marker);
        }
    }

    searchAddress() {
        const keyword = document.getElementById('search-input').value;
        this.searchNearby(keyword ? [keyword] : []);
    }

    searchNearby(keywords = [], retryCount = 0) {
        // 当前位置未获取
        if (this.currentPoint === null) {
            return;
        }

        // 清除之前的搜索结果
        this.localSearch.clearResults();

        // 搜索附近的关键词
        if (keywords.length === 0) {
            keywords = ["写字楼", "公司", "银行", "餐馆", "商场", "超市", "学校", "医院", "公交站", "地铁站"]
        }

        // 定义一个随机数，用于判断定时器是否过期
        this.searchRandom = Math.random();

        // 设置搜索完成回调
        this.loaderShow();
        this.localSearch.setSearchCompleteCallback((results) => {
            this.loaderHide();
            if (this.localSearch.getStatus() !== BMAP_STATUS_SUCCESS) {
                // 搜索失败
                if (retryCount < 60) {
                    const tmpRand = this.searchRandom;
                    this.loaderShow();
                    setTimeout(() => {
                        this.loaderHide();
                        tmpRand === this.searchRandom && this.searchNearby(keywords, ++retryCount);
                    }, 1000)
                    return;
                }
            }
            // 搜索结果
            document.getElementById('address-list').style.display = 'block';
            const array = [];
            if (results instanceof Array) {
                results.some(result => {
                    if (!result) {
                        return false;
                    }
                    for (let i = 0; i < result.getCurrentNumPois(); i++) {
                        const poi = result.getPoi(i);
                        poi._distance = this.config.point ? this.map.getDistance(this.config.point, poi.point) : null;
                        array.push(poi);
                    }
                });
            }
            this.updatePoiList(array);
        });

        // 执行搜索
        this.localSearch.searchNearby(keywords, this.currentPoint, this.config.radius);
    }

    updatePoiList(results) {
        const poiList = document.getElementById('poi-list');
        poiList.innerHTML = '';

        // 如果没有搜索结果
        if (results.length === 0 && this.config.noresult) {
            poiList.innerHTML = '<li>' + this.config.noresult + '</li>';
            return;
        }

        // 按距离排序（如果有距离信息）
        results.sort((a, b) => {
            if (a._distance && b._distance) {
                return a._distance - b._distance;
            }
            return 0;
        });
        results = results.slice(0, 20);

        // 创建列表项
        results.forEach(poi => {
            const li = document.createElement('li');
            const distance = poi._distance ? `<div class="address-distance">${this.convertDistance(Math.round(poi._distance))}</div>` : '';
            li.innerHTML = `
                <div class="address-name">${poi.title}</div>
                <div class="address-detail">${poi.address || ""}${distance}</div>
            `;
            li.addEventListener('click', () => {
                const point = poi.point;
                this.updateMarker(point);
                this.map.setCenter(point);
                //
                if (typeof requireModuleJs === "function") {
                    const eeui = requireModuleJs("eeui");
                    eeui.setVariate("location::" + this.config.channel, JSON.stringify(poi))
                }
                if (this.config.selectclose) {
                    this.closePage();
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

    convertDistance(d) {
        if (d > 1000) {
            return (d / 1000).toFixed(1) + 'km';
        }
        return d.toFixed(0) + 'm';
    }

    locationError() {
        if (this.config.errtip) {
            alert(this.config.errtip);
        }
        if (this.config.errclose) {
            this.closePage();
        }
    }

    loaderShow() {
        this.loadNum++;
        this.loaderJudge();
    }

    loaderHide() {
        setTimeout(() => {
            this.loadNum--;
            this.loaderJudge();
        }, 100)
    }

    loaderJudge() {
        if (this.loadNum > 0) {
            document.querySelector('.loading').classList.add('show');
        } else if (this.loadNum <= 0) {
            document.querySelector('.loading').classList.remove('show');
        }
    }

    closePage() {
        try {
            // 方法1: 如果是在 eeui 环境中
            if (typeof requireModuleJs === "function") {
                const eeui = requireModuleJs("eeui");
                eeui.closePage();
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

    loadBaiduMapScript() {
        return new Promise((resolve, reject) => {
            // 如果已经加载过，直接返回
            if (window.BMap) {
                resolve();
                return;
            }

            // 创建script标签
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = `https://api.map.baidu.com/api?v=3.0&ak=${this.config.key}&callback=initBaiduMap`;

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
    new LocationPicker();
});
