(function(c){var a,d=function(){a=(new UAParser).getResult();return this};d.prototype={getSoftwareVersion:function(){return"ClientJS 0.05"},getBrowserData:function(){return a},getFingerprint:function(){var e=a.ua,b=this.getScreenPrint(),c=this.getPlugins(),d=this.getFonts(),f=this.isLocalStorage(),g=this.isSessionStorage(),h=this.getTimeZone(),k=this.getLanguage(),l=this.getSystemLanguage(),m=this.isCookie(),n=this.getCanvasPrint();return murmurhash3_32_gc(e+"|"+b+"|"+c+"|"+d+"|"+f+"|"+g+"|"+h+"|"+
k+"|"+l+"|"+m+"|"+n,256)},getUserAgent:function(){return a.ua},getUserAgentLowerCase:function(){return a.ua.toLowerCase()},getBrowser:function(){return a.browser.name},getBrowserVersion:function(){return a.browser.version},getBrowserMajorVersion:function(){return a.browser.major},isIE:function(){return /IE/i.test(a.browser.name)},isChrome:function(){return /Chrome/i.test(a.browser.name)},isFirefox:function(){return /Firefox/i.test(a.browser.name)},isSafari:function(){return /Safari/i.test(a.browser.name)},isOpera:function(){return /Opera/i.test(a.browser.name)},getEngine:function(){return a.engine.name},getEngineVersion:function(){return a.engine.version},getOS:function(){return a.os.name},getOSVersion:function(){return a.os.version},isWindows:function(){return /Windows/i.test(a.os.name)},isMac:function(){return /Mac/i.test(a.os.name)},isLinux:function(){return /Linux/i.test(a.os.name)},isUbuntu:function(){return /Ubuntu/i.test(a.os.name)},isSolaris:function(){return /Solaris/i.test(a.os.name)},getDevice:function(){return a.device.model},getDeviceType:function(){return a.device.type},getDeviceVendor:function(){return a.device.vendor},getCPU:function(){return a.cpu.architecture},isMobile:function(){var e=a.ua||navigator.vendor||window.opera;return /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge|maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm(os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows(ce|phone)|xda|xiino/i.test(e)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(e.substr(0,4))},isMobileMajor:function(){return this.isMobileAndroid()||this.isMobileBlackBerry()||this.isMobileIOS()||this.isMobileOpera()||this.isMobileWindows()},isMobileAndroid:function(){return a.ua.match(/Android/i)?!0:!1},isMobileOpera:function(){return a.ua.match(/Opera Mini/i)?!0:!1},isMobileWindows:function(){return a.ua.match(/IEMobile/i)?!0:!1},isMobileBlackBerry:function(){return a.ua.match(/BlackBerry/i)?!0:!1},isMobileIOS:function(){return a.ua.match(/iPhone|iPad|iPod/i)?!0:!1},isIphone:function(){return a.ua.match(/iPhone/i)?!0:!1},isIpad:function(){return a.ua.match(/iPad/i)?!0:!1},isIpod:function(){return a.ua.match(/iPod/i)?!0:!1},getScreenPrint:function(){return"Current Resolution: "+this.getCurrentResolution()+", Avaiable Resolution: "+this.getAvailableResolution()+", Color Depth: "+this.getColorDepth()+", Device XDPI: "+this.getDeviceXDPI()+", Device YDPI: "+this.getDeviceYDPI()},getColorDepth:function(){return screen.colorDepth},getCurrentResolution:function(){return screen.width+"x"+screen.height},getAvailableResolution:function(){return screen.availWidth+"x"+screen.availHeight},getDeviceXDPI:function(){return screen.deviceXDPI},getDeviceYDPI:function(){return screen.deviceYDPI},getPlugins:function(){for(var a="",b=0;b<navigator.plugins.length;b++)a=b==navigator.plugins.length-1?a+navigator.plugins[b].name:a+(navigator.plugins[b].name+", ");return a},isJava:function(){return navigator.javaEnabled()},getJavaVersion:function(){return deployJava.getJREs().toString()},isFlash:function(){objPlayerVersion=swfobject.getFlashPlayerVersion();strTemp=objPlayerVersion.major+"."+objPlayerVersion.minor+"."+objPlayerVersion.release;return"0.0.0"===strTemp?!1:!0},getFlashVersion:function(){objPlayerVersion=swfobject.getFlashPlayerVersion();return objPlayerVersion.major+"."+objPlayerVersion.minor+"."+objPlayerVersion.release},isSilverlight:function(){return navigator.plugins["Silverlight Plug-In"]?!0:!1},getSilverlightVersion:function(){return navigator.plugins["Silverlight Plug-In"].description},isMimeTypes:function(){return navigator.mimeTypes.length?!0:!1},getMimeTypes:function(){for(var a="",b=0;b<navigator.mimeTypes.length;b++)a=b==navigator.mimeTypes.length-1?a+navigator.mimeTypes[b].description:a+(navigator.mimeTypes[b].description+", ");return a},isFont:function(a){return(new Detector).detect(a)},getFonts:function(){return''},isLocalStorage:function(){try{return!!c.localStorage}catch(a){return!0}},isSessionStorage:function(){try{return!!c.sessionStorage}catch(a){return!0}},isCookie:function(){return navigator.cookieEnabled},getTimeZone:function(){return String(String(new Date).split("(")[1]).split(")")[0]},getLanguage:function(){return navigator.language},getSystemLanguage:function(){return navigator.systemLanguage},isCanvas:function(){var a=document.createElement("canvas");return!(!a.getContext||!a.getContext("2d"))},getCanvasPrint:function(){var a=document.createElement("canvas"),b=a.getContext("2d");b.textBaseline="top";b.font="14px 'Arial'";b.textBaseline="alphabetic";b.fillStyle="#f60";b.fillRect(125,1,62,20);b.fillStyle="#069";b.fillText("http://valve.github.io",2,15);b.fillStyle="rgba(102, 204, 0, 0.7)";b.fillText("http://valve.github.io",4,17);return a.toDataURL()}};"object"===typeof module&&"object"===typeof exports&&(module.exports=d);c.ClientJS=d})(window);