var __tools = function() {
	return {
		noop: ''

		/**
		 * 解析URL中的某个请求参数
		 */
		,
		getQueryString: function(paramName) {
				var params = this.getQueryStringMap();
				return params[paramName];
			}
			/**
			 * 解析URL中的请求参数集合
			 */
			,
		getQueryStringMap: function() {
				return this.querystringparse(window.location.search)
			}
			/**
			 * 解析URL请求参数
			 */
			,
		querystringparse: function(string) {
			var parsed = {};
			string = (string !== undefined) ? string : window.location.search;

			if (typeof string === "string" && string.length > 0) {
				if (string[0] === '?') {
					string = string.substring(1);
				}

				string = string.split('&');

				for (var i = 0, length = string.length; i < length; i++) {
					var element = string[i],
						eqPos = element.indexOf('='),
						keyValue, elValue;

					if (eqPos >= 0) {
						keyValue = element.substr(0, eqPos);
						elValue = element.substr(eqPos + 1);
					} else {
						keyValue = element;
						elValue = '';
					}

					elValue = decodeURIComponent(elValue);

					if (parsed[keyValue] === undefined) {
						parsed[keyValue] = elValue;
					} else if (parsed[keyValue] instanceof Array) {
						parsed[keyValue].push(elValue);
					} else {
						parsed[keyValue] = [parsed[keyValue], elValue];
					}
				}
			}

			return parsed;
		},
		/**
		 * 拼装URL请求参数
		 */
		querystringify: function(obj) {
				var string = [];

				if (!!obj && obj.constructor === Object) {
					for (var prop in obj) {
						if (obj[prop] instanceof Array) {
							for (var i = 0, length = obj[prop].length; i < length; i++) {
								string.push([encodeURIComponent(prop), encodeURIComponent(obj[prop][i])].join('='));
							}
						} else {
							string.push([encodeURIComponent(prop), encodeURIComponent(obj[prop])].join('='));
						}
					}
				}

				return string.join('&');
			}
			/**
			 * 获取appversion
			 * @return 如：ios2.3.1
			 */
			,
		getAppversion: function() {
				var that = this;
				var appversion = window.appversion;
				if (!appversion) {
					appversion = that.getQueryString("appversion");
				}
				if (!appversion) {
					appversion = localStorage.getItem("_appversion");
				}
				if (!appversion) {
					return "";
				} else {
					appversion = appversion + "";
					localStorage.setItem("_appversion", appversion);
					return appversion;
				}
			}
			/**
			 * 通知APP
			 */
			,
		sendMsgToApp: function(url) {
			if (!url) {
				return;
			}
			var that = this;
			var appversion = that.getAppversion();
			var isCheck = that.checkAppversion(appversion, "2.3.1");
			if (isCheck) {
				url = ("nativeapi://" + (url + "").replace(/^nativeapi:\/{2,3}/gi, "")).replace(/\/{2,}/gi, "//");
			}
			var api = that.getApiFromUrl(url);
			var apiVersionMap = that.appMsgVersionMap();
			var thisStartVersion = apiVersionMap[api];

			if (thisStartVersion) { //只有配了才认为是要控制版本
				var checkResult = that.checkAppversion(appversion, thisStartVersion); //查看appversion是否高于等于api需要的起始版本
				if (!checkResult) {
					return;
				}
			}

			var iFrame;
			iFrame = document.createElement("iFrame");
			iFrame.setAttribute("src", url);
			iFrame.setAttribute("style", "display:none;");
			iFrame.setAttribute("height", "0px");
			iFrame.setAttribute("width", "0px");
			iFrame.setAttribute("frameborder", "0");
			document.body.appendChild(iFrame);
			setTimeout(function() {
				iFrame.parentNode.removeChild(iFrame);
				iFrame = null;
			}, 10);
		}

			/**
			 * 从url截取App交互信息的接口路径
			 */
			,
		getApiFromUrl: function(url) {
				return (url + '').replace(/^(.+?\/{2,3}|\/)/gi, '').replace(/\?.*$/gi, '');
			}
			/**
			 * App交互信息的接口和起始版本号的映射关系
			 */
			,
		appMsgVersionMap: function() {
			return {
				//"接口名称":"起始版本号"
				"titlebar": "2.3.1",
				"openview": "2.3.1"
			};
		}

		/**
		 * 查看version1是否高于等于version2
		 */
		,
		checkAppversion: function(version1, version2) {
			if (!version1 || !version2) {
				return false;
			}
			var that = this;
			version1 = (version1 + '').toLowerCase().replace(/^(ios|android|andriod)/g, '');
			version2 = (version2 + '').toLowerCase().replace(/^(ios|android|andriod)/g, '');
			if (version1 === version2) {
				return true;
			}

			var version1Parts = version1.split(".");
			var version2Parts = version2.split(".");
			for (var i = 0; i < version1Parts.length; i++) {
				var version1Part = that.toInteger(version1Parts[i], 0);
				var version2Part = 0;
				if (version2Parts.length > i) {
					version2Part = that.toInteger(version2Parts[i], 0);
				}
				if (version1Part > version2Part) {
					return true;
				} else if (version1Part < version2Part) {
					return false;
				}
			}
			return false;
		},
		toInteger: function(input, defaultVal) {
			if (defaultVal === undefined) {
				defaultVal = 0;
			}
			if (input === null || input === undefined) {
				return defaultVal;
			}
			try {
				var val = parseInt(input);
				if (isNaN(val)) {
					return defaultVal;
				}

				return val;
			} catch (e) {
				return defaultVal;
			}
		}
	}; //return
}(); //Utils