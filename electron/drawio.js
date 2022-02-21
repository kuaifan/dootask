window.utilsStorage={getStorageString(key,def=''){let value=this.storage(key);return typeof value==="string"||typeof value==="number"?value:def},storage(key,value){if(!key){return}let keyName='__state__';if(key.substring(0,5)==='cache'){keyName='__state:'+key+'__'}if(typeof value==='undefined'){return this.loadFromlLocal(key,'',keyName)}else{this.savaToLocal(key,value,keyName)}},savaToLocal(key,value,keyName){try{if(typeof keyName==='undefined')keyName='__seller__';let seller=window.localStorage[keyName];if(!seller){seller={}}else{seller=JSON.parse(seller)}seller[key]=value;window.localStorage[keyName]=JSON.stringify(seller)}catch(e){}},loadFromlLocal(key,def,keyName){try{if(typeof keyName==='undefined')keyName='__seller__';let seller=window.localStorage[keyName];if(!seller){return def}seller=JSON.parse(seller);if(!seller||typeof seller[key]==='undefined'){return def}return seller[key]}catch(e){return def}},}

window.cacheServerUrl = window.utilsStorage.getStorageString("cacheServerUrl")
if (window.cacheServerUrl) {
    window.systemInfo.apiUrl = window.cacheServerUrl
}

window.EXPORT_URL = window.systemInfo.apiUrl + "../drawio/export/";
window.DRAWIO_LIGHTBOX_URL = window.systemInfo.apiUrl + "../drawio/webapp";
while (window.EXPORT_URL.indexOf("/../") !== -1) {window.EXPORT_URL = window.EXPORT_URL.replace(/\/(((?!\/).)*)\/\.\.\//, "/")}
while (window.DRAWIO_LIGHTBOX_URL.indexOf("/../") !== -1) {window.DRAWIO_LIGHTBOX_URL = window.DRAWIO_LIGHTBOX_URL.replace(/\/(((?!\/).)*)\/\.\.\//, "/")}
