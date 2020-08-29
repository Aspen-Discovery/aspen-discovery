/*! jQuery v1.11.0 | (c) 2005, 2014 jQuery Foundation, Inc. | jquery.org/license */
!function(a,b){"object"==typeof module&&"object"==typeof module.exports?module.exports=a.document?b(a,!0):function(a){if(!a.document)throw new Error("jQuery requires a window with a document");return b(a)}:b(a)}("undefined"!=typeof window?window:this,function(a,b){var c=[],d=c.slice,e=c.concat,f=c.push,g=c.indexOf,h={},i=h.toString,j=h.hasOwnProperty,k="".trim,l={},m="1.11.0",n=function(a,b){return new n.fn.init(a,b)},o=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,p=/^-ms-/,q=/-([\da-z])/gi,r=function(a,b){return b.toUpperCase()};n.fn=n.prototype={jquery:m,constructor:n,selector:"",length:0,toArray:function(){return d.call(this)},get:function(a){return null!=a?0>a?this[a+this.length]:this[a]:d.call(this)},pushStack:function(a){var b=n.merge(this.constructor(),a);return b.prevObject=this,b.context=this.context,b},each:function(a,b){return n.each(this,a,b)},map:function(a){return this.pushStack(n.map(this,function(b,c){return a.call(b,c,b)}))},slice:function(){return this.pushStack(d.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(a){var b=this.length,c=+a+(0>a?b:0);return this.pushStack(c>=0&&b>c?[this[c]]:[])},end:function(){return this.prevObject||this.constructor(null)},push:f,sort:c.sort,splice:c.splice},n.extend=n.fn.extend=function(){var a,b,c,d,e,f,g=arguments[0]||{},h=1,i=arguments.length,j=!1;for("boolean"==typeof g&&(j=g,g=arguments[h]||{},h++),"object"==typeof g||n.isFunction(g)||(g={}),h===i&&(g=this,h--);i>h;h++)if(null!=(e=arguments[h]))for(d in e)a=g[d],c=e[d],g!==c&&(j&&c&&(n.isPlainObject(c)||(b=n.isArray(c)))?(b?(b=!1,f=a&&n.isArray(a)?a:[]):f=a&&n.isPlainObject(a)?a:{},g[d]=n.extend(j,f,c)):void 0!==c&&(g[d]=c));return g},n.extend({expando:"jQuery"+(m+Math.random()).replace(/\D/g,""),isReady:!0,error:function(a){throw new Error(a)},noop:function(){},isFunction:function(a){return"function"===n.type(a)},isArray:Array.isArray||function(a){return"array"===n.type(a)},isWindow:function(a){return null!=a&&a==a.window},isNumeric:function(a){return a-parseFloat(a)>=0},isEmptyObject:function(a){var b;for(b in a)return!1;return!0},isPlainObject:function(a){var b;if(!a||"object"!==n.type(a)||a.nodeType||n.isWindow(a))return!1;try{if(a.constructor&&!j.call(a,"constructor")&&!j.call(a.constructor.prototype,"isPrototypeOf"))return!1}catch(c){return!1}if(l.ownLast)for(b in a)return j.call(a,b);for(b in a);return void 0===b||j.call(a,b)},type:function(a){return null==a?a+"":"object"==typeof a||"function"==typeof a?h[i.call(a)]||"object":typeof a},globalEval:function(b){b&&n.trim(b)&&(a.execScript||function(b){a.eval.call(a,b)})(b)},camelCase:function(a){return a.replace(p,"ms-").replace(q,r)},nodeName:function(a,b){return a.nodeName&&a.nodeName.toLowerCase()===b.toLowerCase()},each:function(a,b,c){var d,e=0,f=a.length,g=s(a);if(c){if(g){for(;f>e;e++)if(d=b.apply(a[e],c),d===!1)break}else for(e in a)if(d=b.apply(a[e],c),d===!1)break}else if(g){for(;f>e;e++)if(d=b.call(a[e],e,a[e]),d===!1)break}else for(e in a)if(d=b.call(a[e],e,a[e]),d===!1)break;return a},trim:k&&!k.call("\ufeff\xa0")?function(a){return null==a?"":k.call(a)}:function(a){return null==a?"":(a+"").replace(o,"")},makeArray:function(a,b){var c=b||[];return null!=a&&(s(Object(a))?n.merge(c,"string"==typeof a?[a]:a):f.call(c,a)),c},inArray:function(a,b,c){var d;if(b){if(g)return g.call(b,a,c);for(d=b.length,c=c?0>c?Math.max(0,d+c):c:0;d>c;c++)if(c in b&&b[c]===a)return c}return-1},merge:function(a,b){var c=+b.length,d=0,e=a.length;while(c>d)a[e++]=b[d++];if(c!==c)while(void 0!==b[d])a[e++]=b[d++];return a.length=e,a},grep:function(a,b,c){for(var d,e=[],f=0,g=a.length,h=!c;g>f;f++)d=!b(a[f],f),d!==h&&e.push(a[f]);return e},map:function(a,b,c){var d,f=0,g=a.length,h=s(a),i=[];if(h)for(;g>f;f++)d=b(a[f],f,c),null!=d&&i.push(d);else for(f in a)d=b(a[f],f,c),null!=d&&i.push(d);return e.apply([],i)},guid:1,proxy:function(a,b){var c,e,f;return"string"==typeof b&&(f=a[b],b=a,a=f),n.isFunction(a)?(c=d.call(arguments,2),e=function(){return a.apply(b||this,c.concat(d.call(arguments)))},e.guid=a.guid=a.guid||n.guid++,e):void 0},now:function(){return+new Date},support:l}),n.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(a,b){h["[object "+b+"]"]=b.toLowerCase()});function s(a){var b=a.length,c=n.type(a);return"function"===c||n.isWindow(a)?!1:1===a.nodeType&&b?!0:"array"===c||0===b||"number"==typeof b&&b>0&&b-1 in a}var t=function(a){var b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s="sizzle"+-new Date,t=a.document,u=0,v=0,w=eb(),x=eb(),y=eb(),z=function(a,b){return a===b&&(j=!0),0},A="undefined",B=1<<31,C={}.hasOwnProperty,D=[],E=D.pop,F=D.push,G=D.push,H=D.slice,I=D.indexOf||function(a){for(var b=0,c=this.length;c>b;b++)if(this[b]===a)return b;return-1},J="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",K="[\\x20\\t\\r\\n\\f]",L="(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",M=L.replace("w","w#"),N="\\["+K+"*("+L+")"+K+"*(?:([*^$|!~]?=)"+K+"*(?:(['\"])((?:\\\\.|[^\\\\])*?)\\3|("+M+")|)|)"+K+"*\\]",O=":("+L+")(?:\\(((['\"])((?:\\\\.|[^\\\\])*?)\\3|((?:\\\\.|[^\\\\()[\\]]|"+N.replace(3,8)+")*)|.*)\\)|)",P=new RegExp("^"+K+"+|((?:^|[^\\\\])(?:\\\\.)*)"+K+"+$","g"),Q=new RegExp("^"+K+"*,"+K+"*"),R=new RegExp("^"+K+"*([>+~]|"+K+")"+K+"*"),S=new RegExp("="+K+"*([^\\]'\"]*?)"+K+"*\\]","g"),T=new RegExp(O),U=new RegExp("^"+M+"$"),V={ID:new RegExp("^#("+L+")"),CLASS:new RegExp("^\\.("+L+")"),TAG:new RegExp("^("+L.replace("w","w*")+")"),ATTR:new RegExp("^"+N),PSEUDO:new RegExp("^"+O),CHILD:new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+K+"*(even|odd|(([+-]|)(\\d*)n|)"+K+"*(?:([+-]|)"+K+"*(\\d+)|))"+K+"*\\)|)","i"),bool:new RegExp("^(?:"+J+")$","i"),needsContext:new RegExp("^"+K+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+K+"*((?:-\\d)?\\d*)"+K+"*\\)|)(?=[^-]|$)","i")},W=/^(?:input|select|textarea|button)$/i,X=/^h\d$/i,Y=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,$=/[+~]/,_=/'|\\/g,ab=new RegExp("\\\\([\\da-f]{1,6}"+K+"?|("+K+")|.)","ig"),bb=function(a,b,c){var d="0x"+b-65536;return d!==d||c?b:0>d?String.fromCharCode(d+65536):String.fromCharCode(d>>10|55296,1023&d|56320)};try{G.apply(D=H.call(t.childNodes),t.childNodes),D[t.childNodes.length].nodeType}catch(cb){G={apply:D.length?function(a,b){F.apply(a,H.call(b))}:function(a,b){var c=a.length,d=0;while(a[c++]=b[d++]);a.length=c-1}}}function db(a,b,d,e){var f,g,h,i,j,m,p,q,u,v;if((b?b.ownerDocument||b:t)!==l&&k(b),b=b||l,d=d||[],!a||"string"!=typeof a)return d;if(1!==(i=b.nodeType)&&9!==i)return[];if(n&&!e){if(f=Z.exec(a))if(h=f[1]){if(9===i){if(g=b.getElementById(h),!g||!g.parentNode)return d;if(g.id===h)return d.push(g),d}else if(b.ownerDocument&&(g=b.ownerDocument.getElementById(h))&&r(b,g)&&g.id===h)return d.push(g),d}else{if(f[2])return G.apply(d,b.getElementsByTagName(a)),d;if((h=f[3])&&c.getElementsByClassName&&b.getElementsByClassName)return G.apply(d,b.getElementsByClassName(h)),d}if(c.qsa&&(!o||!o.test(a))){if(q=p=s,u=b,v=9===i&&a,1===i&&"object"!==b.nodeName.toLowerCase()){m=ob(a),(p=b.getAttribute("id"))?q=p.replace(_,"\\$&"):b.setAttribute("id",q),q="[id='"+q+"'] ",j=m.length;while(j--)m[j]=q+pb(m[j]);u=$.test(a)&&mb(b.parentNode)||b,v=m.join(",")}if(v)try{return G.apply(d,u.querySelectorAll(v)),d}catch(w){}finally{p||b.removeAttribute("id")}}}return xb(a.replace(P,"$1"),b,d,e)}function eb(){var a=[];function b(c,e){return a.push(c+" ")>d.cacheLength&&delete b[a.shift()],b[c+" "]=e}return b}function fb(a){return a[s]=!0,a}function gb(a){var b=l.createElement("div");try{return!!a(b)}catch(c){return!1}finally{b.parentNode&&b.parentNode.removeChild(b),b=null}}function hb(a,b){var c=a.split("|"),e=a.length;while(e--)d.attrHandle[c[e]]=b}function ib(a,b){var c=b&&a,d=c&&1===a.nodeType&&1===b.nodeType&&(~b.sourceIndex||B)-(~a.sourceIndex||B);if(d)return d;if(c)while(c=c.nextSibling)if(c===b)return-1;return a?1:-1}function jb(a){return function(b){var c=b.nodeName.toLowerCase();return"input"===c&&b.type===a}}function kb(a){return function(b){var c=b.nodeName.toLowerCase();return("input"===c||"button"===c)&&b.type===a}}function lb(a){return fb(function(b){return b=+b,fb(function(c,d){var e,f=a([],c.length,b),g=f.length;while(g--)c[e=f[g]]&&(c[e]=!(d[e]=c[e]))})})}function mb(a){return a&&typeof a.getElementsByTagName!==A&&a}c=db.support={},f=db.isXML=function(a){var b=a&&(a.ownerDocument||a).documentElement;return b?"HTML"!==b.nodeName:!1},k=db.setDocument=function(a){var b,e=a?a.ownerDocument||a:t,g=e.defaultView;return e!==l&&9===e.nodeType&&e.documentElement?(l=e,m=e.documentElement,n=!f(e),g&&g!==g.top&&(g.addEventListener?g.addEventListener("unload",function(){k()},!1):g.attachEvent&&g.attachEvent("onunload",function(){k()})),c.attributes=gb(function(a){return a.className="i",!a.getAttribute("className")}),c.getElementsByTagName=gb(function(a){return a.appendChild(e.createComment("")),!a.getElementsByTagName("*").length}),c.getElementsByClassName=Y.test(e.getElementsByClassName)&&gb(function(a){return a.innerHTML="<div class='a'></div><div class='a i'></div>",a.firstChild.className="i",2===a.getElementsByClassName("i").length}),c.getById=gb(function(a){return m.appendChild(a).id=s,!e.getElementsByName||!e.getElementsByName(s).length}),c.getById?(d.find.ID=function(a,b){if(typeof b.getElementById!==A&&n){var c=b.getElementById(a);return c&&c.parentNode?[c]:[]}},d.filter.ID=function(a){var b=a.replace(ab,bb);return function(a){return a.getAttribute("id")===b}}):(delete d.find.ID,d.filter.ID=function(a){var b=a.replace(ab,bb);return function(a){var c=typeof a.getAttributeNode!==A&&a.getAttributeNode("id");return c&&c.value===b}}),d.find.TAG=c.getElementsByTagName?function(a,b){return typeof b.getElementsByTagName!==A?b.getElementsByTagName(a):void 0}:function(a,b){var c,d=[],e=0,f=b.getElementsByTagName(a);if("*"===a){while(c=f[e++])1===c.nodeType&&d.push(c);return d}return f},d.find.CLASS=c.getElementsByClassName&&function(a,b){return typeof b.getElementsByClassName!==A&&n?b.getElementsByClassName(a):void 0},p=[],o=[],(c.qsa=Y.test(e.querySelectorAll))&&(gb(function(a){a.innerHTML="<select t=''><option selected=''></option></select>",a.querySelectorAll("[t^='']").length&&o.push("[*^$]="+K+"*(?:''|\"\")"),a.querySelectorAll("[selected]").length||o.push("\\["+K+"*(?:value|"+J+")"),a.querySelectorAll(":checked").length||o.push(":checked")}),gb(function(a){var b=e.createElement("input");b.setAttribute("type","hidden"),a.appendChild(b).setAttribute("name","D"),a.querySelectorAll("[name=d]").length&&o.push("name"+K+"*[*^$|!~]?="),a.querySelectorAll(":enabled").length||o.push(":enabled",":disabled"),a.querySelectorAll("*,:x"),o.push(",.*:")})),(c.matchesSelector=Y.test(q=m.webkitMatchesSelector||m.mozMatchesSelector||m.oMatchesSelector||m.msMatchesSelector))&&gb(function(a){c.disconnectedMatch=q.call(a,"div"),q.call(a,"[s!='']:x"),p.push("!=",O)}),o=o.length&&new RegExp(o.join("|")),p=p.length&&new RegExp(p.join("|")),b=Y.test(m.compareDocumentPosition),r=b||Y.test(m.contains)?function(a,b){var c=9===a.nodeType?a.documentElement:a,d=b&&b.parentNode;return a===d||!(!d||1!==d.nodeType||!(c.contains?c.contains(d):a.compareDocumentPosition&&16&a.compareDocumentPosition(d)))}:function(a,b){if(b)while(b=b.parentNode)if(b===a)return!0;return!1},z=b?function(a,b){if(a===b)return j=!0,0;var d=!a.compareDocumentPosition-!b.compareDocumentPosition;return d?d:(d=(a.ownerDocument||a)===(b.ownerDocument||b)?a.compareDocumentPosition(b):1,1&d||!c.sortDetached&&b.compareDocumentPosition(a)===d?a===e||a.ownerDocument===t&&r(t,a)?-1:b===e||b.ownerDocument===t&&r(t,b)?1:i?I.call(i,a)-I.call(i,b):0:4&d?-1:1)}:function(a,b){if(a===b)return j=!0,0;var c,d=0,f=a.parentNode,g=b.parentNode,h=[a],k=[b];if(!f||!g)return a===e?-1:b===e?1:f?-1:g?1:i?I.call(i,a)-I.call(i,b):0;if(f===g)return ib(a,b);c=a;while(c=c.parentNode)h.unshift(c);c=b;while(c=c.parentNode)k.unshift(c);while(h[d]===k[d])d++;return d?ib(h[d],k[d]):h[d]===t?-1:k[d]===t?1:0},e):l},db.matches=function(a,b){return db(a,null,null,b)},db.matchesSelector=function(a,b){if((a.ownerDocument||a)!==l&&k(a),b=b.replace(S,"='$1']"),!(!c.matchesSelector||!n||p&&p.test(b)||o&&o.test(b)))try{var d=q.call(a,b);if(d||c.disconnectedMatch||a.document&&11!==a.document.nodeType)return d}catch(e){}return db(b,l,null,[a]).length>0},db.contains=function(a,b){return(a.ownerDocument||a)!==l&&k(a),r(a,b)},db.attr=function(a,b){(a.ownerDocument||a)!==l&&k(a);var e=d.attrHandle[b.toLowerCase()],f=e&&C.call(d.attrHandle,b.toLowerCase())?e(a,b,!n):void 0;return void 0!==f?f:c.attributes||!n?a.getAttribute(b):(f=a.getAttributeNode(b))&&f.specified?f.value:null},db.error=function(a){throw new Error("Syntax error, unrecognized expression: "+a)},db.uniqueSort=function(a){var b,d=[],e=0,f=0;if(j=!c.detectDuplicates,i=!c.sortStable&&a.slice(0),a.sort(z),j){while(b=a[f++])b===a[f]&&(e=d.push(f));while(e--)a.splice(d[e],1)}return i=null,a},e=db.getText=function(a){var b,c="",d=0,f=a.nodeType;if(f){if(1===f||9===f||11===f){if("string"==typeof a.textContent)return a.textContent;for(a=a.firstChild;a;a=a.nextSibling)c+=e(a)}else if(3===f||4===f)return a.nodeValue}else while(b=a[d++])c+=e(b);return c},d=db.selectors={cacheLength:50,createPseudo:fb,match:V,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(a){return a[1]=a[1].replace(ab,bb),a[3]=(a[4]||a[5]||"").replace(ab,bb),"~="===a[2]&&(a[3]=" "+a[3]+" "),a.slice(0,4)},CHILD:function(a){return a[1]=a[1].toLowerCase(),"nth"===a[1].slice(0,3)?(a[3]||db.error(a[0]),a[4]=+(a[4]?a[5]+(a[6]||1):2*("even"===a[3]||"odd"===a[3])),a[5]=+(a[7]+a[8]||"odd"===a[3])):a[3]&&db.error(a[0]),a},PSEUDO:function(a){var b,c=!a[5]&&a[2];return V.CHILD.test(a[0])?null:(a[3]&&void 0!==a[4]?a[2]=a[4]:c&&T.test(c)&&(b=ob(c,!0))&&(b=c.indexOf(")",c.length-b)-c.length)&&(a[0]=a[0].slice(0,b),a[2]=c.slice(0,b)),a.slice(0,3))}},filter:{TAG:function(a){var b=a.replace(ab,bb).toLowerCase();return"*"===a?function(){return!0}:function(a){return a.nodeName&&a.nodeName.toLowerCase()===b}},CLASS:function(a){var b=w[a+" "];return b||(b=new RegExp("(^|"+K+")"+a+"("+K+"|$)"))&&w(a,function(a){return b.test("string"==typeof a.className&&a.className||typeof a.getAttribute!==A&&a.getAttribute("class")||"")})},ATTR:function(a,b,c){return function(d){var e=db.attr(d,a);return null==e?"!="===b:b?(e+="","="===b?e===c:"!="===b?e!==c:"^="===b?c&&0===e.indexOf(c):"*="===b?c&&e.indexOf(c)>-1:"$="===b?c&&e.slice(-c.length)===c:"~="===b?(" "+e+" ").indexOf(c)>-1:"|="===b?e===c||e.slice(0,c.length+1)===c+"-":!1):!0}},CHILD:function(a,b,c,d,e){var f="nth"!==a.slice(0,3),g="last"!==a.slice(-4),h="of-type"===b;return 1===d&&0===e?function(a){return!!a.parentNode}:function(b,c,i){var j,k,l,m,n,o,p=f!==g?"nextSibling":"previousSibling",q=b.parentNode,r=h&&b.nodeName.toLowerCase(),t=!i&&!h;if(q){if(f){while(p){l=b;while(l=l[p])if(h?l.nodeName.toLowerCase()===r:1===l.nodeType)return!1;o=p="only"===a&&!o&&"nextSibling"}return!0}if(o=[g?q.firstChild:q.lastChild],g&&t){k=q[s]||(q[s]={}),j=k[a]||[],n=j[0]===u&&j[1],m=j[0]===u&&j[2],l=n&&q.childNodes[n];while(l=++n&&l&&l[p]||(m=n=0)||o.pop())if(1===l.nodeType&&++m&&l===b){k[a]=[u,n,m];break}}else if(t&&(j=(b[s]||(b[s]={}))[a])&&j[0]===u)m=j[1];else while(l=++n&&l&&l[p]||(m=n=0)||o.pop())if((h?l.nodeName.toLowerCase()===r:1===l.nodeType)&&++m&&(t&&((l[s]||(l[s]={}))[a]=[u,m]),l===b))break;return m-=e,m===d||m%d===0&&m/d>=0}}},PSEUDO:function(a,b){var c,e=d.pseudos[a]||d.setFilters[a.toLowerCase()]||db.error("unsupported pseudo: "+a);return e[s]?e(b):e.length>1?(c=[a,a,"",b],d.setFilters.hasOwnProperty(a.toLowerCase())?fb(function(a,c){var d,f=e(a,b),g=f.length;while(g--)d=I.call(a,f[g]),a[d]=!(c[d]=f[g])}):function(a){return e(a,0,c)}):e}},pseudos:{not:fb(function(a){var b=[],c=[],d=g(a.replace(P,"$1"));return d[s]?fb(function(a,b,c,e){var f,g=d(a,null,e,[]),h=a.length;while(h--)(f=g[h])&&(a[h]=!(b[h]=f))}):function(a,e,f){return b[0]=a,d(b,null,f,c),!c.pop()}}),has:fb(function(a){return function(b){return db(a,b).length>0}}),contains:fb(function(a){return function(b){return(b.textContent||b.innerText||e(b)).indexOf(a)>-1}}),lang:fb(function(a){return U.test(a||"")||db.error("unsupported lang: "+a),a=a.replace(ab,bb).toLowerCase(),function(b){var c;do if(c=n?b.lang:b.getAttribute("xml:lang")||b.getAttribute("lang"))return c=c.toLowerCase(),c===a||0===c.indexOf(a+"-");while((b=b.parentNode)&&1===b.nodeType);return!1}}),target:function(b){var c=a.location&&a.location.hash;return c&&c.slice(1)===b.id},root:function(a){return a===m},focus:function(a){return a===l.activeElement&&(!l.hasFocus||l.hasFocus())&&!!(a.type||a.href||~a.tabIndex)},enabled:function(a){return a.disabled===!1},disabled:function(a){return a.disabled===!0},checked:function(a){var b=a.nodeName.toLowerCase();return"input"===b&&!!a.checked||"option"===b&&!!a.selected},selected:function(a){return a.parentNode&&a.parentNode.selectedIndex,a.selected===!0},empty:function(a){for(a=a.firstChild;a;a=a.nextSibling)if(a.nodeType<6)return!1;return!0},parent:function(a){return!d.pseudos.empty(a)},header:function(a){return X.test(a.nodeName)},input:function(a){return W.test(a.nodeName)},button:function(a){var b=a.nodeName.toLowerCase();return"input"===b&&"button"===a.type||"button"===b},text:function(a){var b;return"input"===a.nodeName.toLowerCase()&&"text"===a.type&&(null==(b=a.getAttribute("type"))||"text"===b.toLowerCase())},first:lb(function(){return[0]}),last:lb(function(a,b){return[b-1]}),eq:lb(function(a,b,c){return[0>c?c+b:c]}),even:lb(function(a,b){for(var c=0;b>c;c+=2)a.push(c);return a}),odd:lb(function(a,b){for(var c=1;b>c;c+=2)a.push(c);return a}),lt:lb(function(a,b,c){for(var d=0>c?c+b:c;--d>=0;)a.push(d);return a}),gt:lb(function(a,b,c){for(var d=0>c?c+b:c;++d<b;)a.push(d);return a})}},d.pseudos.nth=d.pseudos.eq;for(b in{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})d.pseudos[b]=jb(b);for(b in{submit:!0,reset:!0})d.pseudos[b]=kb(b);function nb(){}nb.prototype=d.filters=d.pseudos,d.setFilters=new nb;function ob(a,b){var c,e,f,g,h,i,j,k=x[a+" "];if(k)return b?0:k.slice(0);h=a,i=[],j=d.preFilter;while(h){(!c||(e=Q.exec(h)))&&(e&&(h=h.slice(e[0].length)||h),i.push(f=[])),c=!1,(e=R.exec(h))&&(c=e.shift(),f.push({value:c,type:e[0].replace(P," ")}),h=h.slice(c.length));for(g in d.filter)!(e=V[g].exec(h))||j[g]&&!(e=j[g](e))||(c=e.shift(),f.push({value:c,type:g,matches:e}),h=h.slice(c.length));if(!c)break}return b?h.length:h?db.error(a):x(a,i).slice(0)}function pb(a){for(var b=0,c=a.length,d="";c>b;b++)d+=a[b].value;return d}function qb(a,b,c){var d=b.dir,e=c&&"parentNode"===d,f=v++;return b.first?function(b,c,f){while(b=b[d])if(1===b.nodeType||e)return a(b,c,f)}:function(b,c,g){var h,i,j=[u,f];if(g){while(b=b[d])if((1===b.nodeType||e)&&a(b,c,g))return!0}else while(b=b[d])if(1===b.nodeType||e){if(i=b[s]||(b[s]={}),(h=i[d])&&h[0]===u&&h[1]===f)return j[2]=h[2];if(i[d]=j,j[2]=a(b,c,g))return!0}}}function rb(a){return a.length>1?function(b,c,d){var e=a.length;while(e--)if(!a[e](b,c,d))return!1;return!0}:a[0]}function sb(a,b,c,d,e){for(var f,g=[],h=0,i=a.length,j=null!=b;i>h;h++)(f=a[h])&&(!c||c(f,d,e))&&(g.push(f),j&&b.push(h));return g}function tb(a,b,c,d,e,f){return d&&!d[s]&&(d=tb(d)),e&&!e[s]&&(e=tb(e,f)),fb(function(f,g,h,i){var j,k,l,m=[],n=[],o=g.length,p=f||wb(b||"*",h.nodeType?[h]:h,[]),q=!a||!f&&b?p:sb(p,m,a,h,i),r=c?e||(f?a:o||d)?[]:g:q;if(c&&c(q,r,h,i),d){j=sb(r,n),d(j,[],h,i),k=j.length;while(k--)(l=j[k])&&(r[n[k]]=!(q[n[k]]=l))}if(f){if(e||a){if(e){j=[],k=r.length;while(k--)(l=r[k])&&j.push(q[k]=l);e(null,r=[],j,i)}k=r.length;while(k--)(l=r[k])&&(j=e?I.call(f,l):m[k])>-1&&(f[j]=!(g[j]=l))}}else r=sb(r===g?r.splice(o,r.length):r),e?e(null,g,r,i):G.apply(g,r)})}function ub(a){for(var b,c,e,f=a.length,g=d.relative[a[0].type],i=g||d.relative[" "],j=g?1:0,k=qb(function(a){return a===b},i,!0),l=qb(function(a){return I.call(b,a)>-1},i,!0),m=[function(a,c,d){return!g&&(d||c!==h)||((b=c).nodeType?k(a,c,d):l(a,c,d))}];f>j;j++)if(c=d.relative[a[j].type])m=[qb(rb(m),c)];else{if(c=d.filter[a[j].type].apply(null,a[j].matches),c[s]){for(e=++j;f>e;e++)if(d.relative[a[e].type])break;return tb(j>1&&rb(m),j>1&&pb(a.slice(0,j-1).concat({value:" "===a[j-2].type?"*":""})).replace(P,"$1"),c,e>j&&ub(a.slice(j,e)),f>e&&ub(a=a.slice(e)),f>e&&pb(a))}m.push(c)}return rb(m)}function vb(a,b){var c=b.length>0,e=a.length>0,f=function(f,g,i,j,k){var m,n,o,p=0,q="0",r=f&&[],s=[],t=h,v=f||e&&d.find.TAG("*",k),w=u+=null==t?1:Math.random()||.1,x=v.length;for(k&&(h=g!==l&&g);q!==x&&null!=(m=v[q]);q++){if(e&&m){n=0;while(o=a[n++])if(o(m,g,i)){j.push(m);break}k&&(u=w)}c&&((m=!o&&m)&&p--,f&&r.push(m))}if(p+=q,c&&q!==p){n=0;while(o=b[n++])o(r,s,g,i);if(f){if(p>0)while(q--)r[q]||s[q]||(s[q]=E.call(j));s=sb(s)}G.apply(j,s),k&&!f&&s.length>0&&p+b.length>1&&db.uniqueSort(j)}return k&&(u=w,h=t),r};return c?fb(f):f}g=db.compile=function(a,b){var c,d=[],e=[],f=y[a+" "];if(!f){b||(b=ob(a)),c=b.length;while(c--)f=ub(b[c]),f[s]?d.push(f):e.push(f);f=y(a,vb(e,d))}return f};function wb(a,b,c){for(var d=0,e=b.length;e>d;d++)db(a,b[d],c);return c}function xb(a,b,e,f){var h,i,j,k,l,m=ob(a);if(!f&&1===m.length){if(i=m[0]=m[0].slice(0),i.length>2&&"ID"===(j=i[0]).type&&c.getById&&9===b.nodeType&&n&&d.relative[i[1].type]){if(b=(d.find.ID(j.matches[0].replace(ab,bb),b)||[])[0],!b)return e;a=a.slice(i.shift().value.length)}h=V.needsContext.test(a)?0:i.length;while(h--){if(j=i[h],d.relative[k=j.type])break;if((l=d.find[k])&&(f=l(j.matches[0].replace(ab,bb),$.test(i[0].type)&&mb(b.parentNode)||b))){if(i.splice(h,1),a=f.length&&pb(i),!a)return G.apply(e,f),e;break}}}return g(a,m)(f,b,!n,e,$.test(a)&&mb(b.parentNode)||b),e}return c.sortStable=s.split("").sort(z).join("")===s,c.detectDuplicates=!!j,k(),c.sortDetached=gb(function(a){return 1&a.compareDocumentPosition(l.createElement("div"))}),gb(function(a){return a.innerHTML="<a href='#'></a>","#"===a.firstChild.getAttribute("href")})||hb("type|href|height|width",function(a,b,c){return c?void 0:a.getAttribute(b,"type"===b.toLowerCase()?1:2)}),c.attributes&&gb(function(a){return a.innerHTML="<input/>",a.firstChild.setAttribute("value",""),""===a.firstChild.getAttribute("value")})||hb("value",function(a,b,c){return c||"input"!==a.nodeName.toLowerCase()?void 0:a.defaultValue}),gb(function(a){return null==a.getAttribute("disabled")})||hb(J,function(a,b,c){var d;return c?void 0:a[b]===!0?b.toLowerCase():(d=a.getAttributeNode(b))&&d.specified?d.value:null}),db}(a);n.find=t,n.expr=t.selectors,n.expr[":"]=n.expr.pseudos,n.unique=t.uniqueSort,n.text=t.getText,n.isXMLDoc=t.isXML,n.contains=t.contains;var u=n.expr.match.needsContext,v=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,w=/^.[^:#\[\.,]*$/;function x(a,b,c){if(n.isFunction(b))return n.grep(a,function(a,d){return!!b.call(a,d,a)!==c});if(b.nodeType)return n.grep(a,function(a){return a===b!==c});if("string"==typeof b){if(w.test(b))return n.filter(b,a,c);b=n.filter(b,a)}return n.grep(a,function(a){return n.inArray(a,b)>=0!==c})}n.filter=function(a,b,c){var d=b[0];return c&&(a=":not("+a+")"),1===b.length&&1===d.nodeType?n.find.matchesSelector(d,a)?[d]:[]:n.find.matches(a,n.grep(b,function(a){return 1===a.nodeType}))},n.fn.extend({find:function(a){var b,c=[],d=this,e=d.length;if("string"!=typeof a)return this.pushStack(n(a).filter(function(){for(b=0;e>b;b++)if(n.contains(d[b],this))return!0}));for(b=0;e>b;b++)n.find(a,d[b],c);return c=this.pushStack(e>1?n.unique(c):c),c.selector=this.selector?this.selector+" "+a:a,c},filter:function(a){return this.pushStack(x(this,a||[],!1))},not:function(a){return this.pushStack(x(this,a||[],!0))},is:function(a){return!!x(this,"string"==typeof a&&u.test(a)?n(a):a||[],!1).length}});var y,z=a.document,A=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,B=n.fn.init=function(a,b){var c,d;if(!a)return this;if("string"==typeof a){if(c="<"===a.charAt(0)&&">"===a.charAt(a.length-1)&&a.length>=3?[null,a,null]:A.exec(a),!c||!c[1]&&b)return!b||b.jquery?(b||y).find(a):this.constructor(b).find(a);if(c[1]){if(b=b instanceof n?b[0]:b,n.merge(this,n.parseHTML(c[1],b&&b.nodeType?b.ownerDocument||b:z,!0)),v.test(c[1])&&n.isPlainObject(b))for(c in b)n.isFunction(this[c])?this[c](b[c]):this.attr(c,b[c]);return this}if(d=z.getElementById(c[2]),d&&d.parentNode){if(d.id!==c[2])return y.find(a);this.length=1,this[0]=d}return this.context=z,this.selector=a,this}return a.nodeType?(this.context=this[0]=a,this.length=1,this):n.isFunction(a)?"undefined"!=typeof y.ready?y.ready(a):a(n):(void 0!==a.selector&&(this.selector=a.selector,this.context=a.context),n.makeArray(a,this))};B.prototype=n.fn,y=n(z);var C=/^(?:parents|prev(?:Until|All))/,D={children:!0,contents:!0,next:!0,prev:!0};n.extend({dir:function(a,b,c){var d=[],e=a[b];while(e&&9!==e.nodeType&&(void 0===c||1!==e.nodeType||!n(e).is(c)))1===e.nodeType&&d.push(e),e=e[b];return d},sibling:function(a,b){for(var c=[];a;a=a.nextSibling)1===a.nodeType&&a!==b&&c.push(a);return c}}),n.fn.extend({has:function(a){var b,c=n(a,this),d=c.length;return this.filter(function(){for(b=0;d>b;b++)if(n.contains(this,c[b]))return!0})},closest:function(a,b){for(var c,d=0,e=this.length,f=[],g=u.test(a)||"string"!=typeof a?n(a,b||this.context):0;e>d;d++)for(c=this[d];c&&c!==b;c=c.parentNode)if(c.nodeType<11&&(g?g.index(c)>-1:1===c.nodeType&&n.find.matchesSelector(c,a))){f.push(c);break}return this.pushStack(f.length>1?n.unique(f):f)},index:function(a){return a?"string"==typeof a?n.inArray(this[0],n(a)):n.inArray(a.jquery?a[0]:a,this):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(a,b){return this.pushStack(n.unique(n.merge(this.get(),n(a,b))))},addBack:function(a){return this.add(null==a?this.prevObject:this.prevObject.filter(a))}});function E(a,b){do a=a[b];while(a&&1!==a.nodeType);return a}n.each({parent:function(a){var b=a.parentNode;return b&&11!==b.nodeType?b:null},parents:function(a){return n.dir(a,"parentNode")},parentsUntil:function(a,b,c){return n.dir(a,"parentNode",c)},next:function(a){return E(a,"nextSibling")},prev:function(a){return E(a,"previousSibling")},nextAll:function(a){return n.dir(a,"nextSibling")},prevAll:function(a){return n.dir(a,"previousSibling")},nextUntil:function(a,b,c){return n.dir(a,"nextSibling",c)},prevUntil:function(a,b,c){return n.dir(a,"previousSibling",c)},siblings:function(a){return n.sibling((a.parentNode||{}).firstChild,a)},children:function(a){return n.sibling(a.firstChild)},contents:function(a){return n.nodeName(a,"iframe")?a.contentDocument||a.contentWindow.document:n.merge([],a.childNodes)}},function(a,b){n.fn[a]=function(c,d){var e=n.map(this,b,c);return"Until"!==a.slice(-5)&&(d=c),d&&"string"==typeof d&&(e=n.filter(d,e)),this.length>1&&(D[a]||(e=n.unique(e)),C.test(a)&&(e=e.reverse())),this.pushStack(e)}});var F=/\S+/g,G={};function H(a){var b=G[a]={};return n.each(a.match(F)||[],function(a,c){b[c]=!0}),b}n.Callbacks=function(a){a="string"==typeof a?G[a]||H(a):n.extend({},a);var b,c,d,e,f,g,h=[],i=!a.once&&[],j=function(l){for(c=a.memory&&l,d=!0,f=g||0,g=0,e=h.length,b=!0;h&&e>f;f++)if(h[f].apply(l[0],l[1])===!1&&a.stopOnFalse){c=!1;break}b=!1,h&&(i?i.length&&j(i.shift()):c?h=[]:k.disable())},k={add:function(){if(h){var d=h.length;!function f(b){n.each(b,function(b,c){var d=n.type(c);"function"===d?a.unique&&k.has(c)||h.push(c):c&&c.length&&"string"!==d&&f(c)})}(arguments),b?e=h.length:c&&(g=d,j(c))}return this},remove:function(){return h&&n.each(arguments,function(a,c){var d;while((d=n.inArray(c,h,d))>-1)h.splice(d,1),b&&(e>=d&&e--,f>=d&&f--)}),this},has:function(a){return a?n.inArray(a,h)>-1:!(!h||!h.length)},empty:function(){return h=[],e=0,this},disable:function(){return h=i=c=void 0,this},disabled:function(){return!h},lock:function(){return i=void 0,c||k.disable(),this},locked:function(){return!i},fireWith:function(a,c){return!h||d&&!i||(c=c||[],c=[a,c.slice?c.slice():c],b?i.push(c):j(c)),this},fire:function(){return k.fireWith(this,arguments),this},fired:function(){return!!d}};return k},n.extend({Deferred:function(a){var b=[["resolve","done",n.Callbacks("once memory"),"resolved"],["reject","fail",n.Callbacks("once memory"),"rejected"],["notify","progress",n.Callbacks("memory")]],c="pending",d={state:function(){return c},always:function(){return e.done(arguments).fail(arguments),this},then:function(){var a=arguments;return n.Deferred(function(c){n.each(b,function(b,f){var g=n.isFunction(a[b])&&a[b];e[f[1]](function(){var a=g&&g.apply(this,arguments);a&&n.isFunction(a.promise)?a.promise().done(c.resolve).fail(c.reject).progress(c.notify):c[f[0]+"With"](this===d?c.promise():this,g?[a]:arguments)})}),a=null}).promise()},promise:function(a){return null!=a?n.extend(a,d):d}},e={};return d.pipe=d.then,n.each(b,function(a,f){var g=f[2],h=f[3];d[f[1]]=g.add,h&&g.add(function(){c=h},b[1^a][2].disable,b[2][2].lock),e[f[0]]=function(){return e[f[0]+"With"](this===e?d:this,arguments),this},e[f[0]+"With"]=g.fireWith}),d.promise(e),a&&a.call(e,e),e},when:function(a){var b=0,c=d.call(arguments),e=c.length,f=1!==e||a&&n.isFunction(a.promise)?e:0,g=1===f?a:n.Deferred(),h=function(a,b,c){return function(e){b[a]=this,c[a]=arguments.length>1?d.call(arguments):e,c===i?g.notifyWith(b,c):--f||g.resolveWith(b,c)}},i,j,k;if(e>1)for(i=new Array(e),j=new Array(e),k=new Array(e);e>b;b++)c[b]&&n.isFunction(c[b].promise)?c[b].promise().done(h(b,k,c)).fail(g.reject).progress(h(b,j,i)):--f;return f||g.resolveWith(k,c),g.promise()}});var I;n.fn.ready=function(a){return n.ready.promise().done(a),this},n.extend({isReady:!1,readyWait:1,holdReady:function(a){a?n.readyWait++:n.ready(!0)},ready:function(a){if(a===!0?!--n.readyWait:!n.isReady){if(!z.body)return setTimeout(n.ready);n.isReady=!0,a!==!0&&--n.readyWait>0||(I.resolveWith(z,[n]),n.fn.trigger&&n(z).trigger("ready").off("ready"))}}});function J(){z.addEventListener?(z.removeEventListener("DOMContentLoaded",K,!1),a.removeEventListener("load",K,!1)):(z.detachEvent("onreadystatechange",K),a.detachEvent("onload",K))}function K(){(z.addEventListener||"load"===event.type||"complete"===z.readyState)&&(J(),n.ready())}n.ready.promise=function(b){if(!I)if(I=n.Deferred(),"complete"===z.readyState)setTimeout(n.ready);else if(z.addEventListener)z.addEventListener("DOMContentLoaded",K,!1),a.addEventListener("load",K,!1);else{z.attachEvent("onreadystatechange",K),a.attachEvent("onload",K);var c=!1;try{c=null==a.frameElement&&z.documentElement}catch(d){}c&&c.doScroll&&!function e(){if(!n.isReady){try{c.doScroll("left")}catch(a){return setTimeout(e,50)}J(),n.ready()}}()}return I.promise(b)};var L="undefined",M;for(M in n(l))break;l.ownLast="0"!==M,l.inlineBlockNeedsLayout=!1,n(function(){var a,b,c=z.getElementsByTagName("body")[0];c&&(a=z.createElement("div"),a.style.cssText="border:0;width:0;height:0;position:absolute;top:0;left:-9999px;margin-top:1px",b=z.createElement("div"),c.appendChild(a).appendChild(b),typeof b.style.zoom!==L&&(b.style.cssText="border:0;margin:0;width:1px;padding:1px;display:inline;zoom:1",(l.inlineBlockNeedsLayout=3===b.offsetWidth)&&(c.style.zoom=1)),c.removeChild(a),a=b=null)}),function(){var a=z.createElement("div");if(null==l.deleteExpando){l.deleteExpando=!0;try{delete a.test}catch(b){l.deleteExpando=!1}}a=null}(),n.acceptData=function(a){var b=n.noData[(a.nodeName+" ").toLowerCase()],c=+a.nodeType||1;return 1!==c&&9!==c?!1:!b||b!==!0&&a.getAttribute("classid")===b};var N=/^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,O=/([A-Z])/g;function P(a,b,c){if(void 0===c&&1===a.nodeType){var d="data-"+b.replace(O,"-$1").toLowerCase();if(c=a.getAttribute(d),"string"==typeof c){try{c="true"===c?!0:"false"===c?!1:"null"===c?null:+c+""===c?+c:N.test(c)?n.parseJSON(c):c}catch(e){}n.data(a,b,c)}else c=void 0}return c}function Q(a){var b;for(b in a)if(("data"!==b||!n.isEmptyObject(a[b]))&&"toJSON"!==b)return!1;return!0}function R(a,b,d,e){if(n.acceptData(a)){var f,g,h=n.expando,i=a.nodeType,j=i?n.cache:a,k=i?a[h]:a[h]&&h;if(k&&j[k]&&(e||j[k].data)||void 0!==d||"string"!=typeof b)return k||(k=i?a[h]=c.pop()||n.guid++:h),j[k]||(j[k]=i?{}:{toJSON:n.noop}),("object"==typeof b||"function"==typeof b)&&(e?j[k]=n.extend(j[k],b):j[k].data=n.extend(j[k].data,b)),g=j[k],e||(g.data||(g.data={}),g=g.data),void 0!==d&&(g[n.camelCase(b)]=d),"string"==typeof b?(f=g[b],null==f&&(f=g[n.camelCase(b)])):f=g,f
}}function S(a,b,c){if(n.acceptData(a)){var d,e,f=a.nodeType,g=f?n.cache:a,h=f?a[n.expando]:n.expando;if(g[h]){if(b&&(d=c?g[h]:g[h].data)){n.isArray(b)?b=b.concat(n.map(b,n.camelCase)):b in d?b=[b]:(b=n.camelCase(b),b=b in d?[b]:b.split(" ")),e=b.length;while(e--)delete d[b[e]];if(c?!Q(d):!n.isEmptyObject(d))return}(c||(delete g[h].data,Q(g[h])))&&(f?n.cleanData([a],!0):l.deleteExpando||g!=g.window?delete g[h]:g[h]=null)}}}n.extend({cache:{},noData:{"applet ":!0,"embed ":!0,"object ":"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"},hasData:function(a){return a=a.nodeType?n.cache[a[n.expando]]:a[n.expando],!!a&&!Q(a)},data:function(a,b,c){return R(a,b,c)},removeData:function(a,b){return S(a,b)},_data:function(a,b,c){return R(a,b,c,!0)},_removeData:function(a,b){return S(a,b,!0)}}),n.fn.extend({data:function(a,b){var c,d,e,f=this[0],g=f&&f.attributes;if(void 0===a){if(this.length&&(e=n.data(f),1===f.nodeType&&!n._data(f,"parsedAttrs"))){c=g.length;while(c--)d=g[c].name,0===d.indexOf("data-")&&(d=n.camelCase(d.slice(5)),P(f,d,e[d]));n._data(f,"parsedAttrs",!0)}return e}return"object"==typeof a?this.each(function(){n.data(this,a)}):arguments.length>1?this.each(function(){n.data(this,a,b)}):f?P(f,a,n.data(f,a)):void 0},removeData:function(a){return this.each(function(){n.removeData(this,a)})}}),n.extend({queue:function(a,b,c){var d;return a?(b=(b||"fx")+"queue",d=n._data(a,b),c&&(!d||n.isArray(c)?d=n._data(a,b,n.makeArray(c)):d.push(c)),d||[]):void 0},dequeue:function(a,b){b=b||"fx";var c=n.queue(a,b),d=c.length,e=c.shift(),f=n._queueHooks(a,b),g=function(){n.dequeue(a,b)};"inprogress"===e&&(e=c.shift(),d--),e&&("fx"===b&&c.unshift("inprogress"),delete f.stop,e.call(a,g,f)),!d&&f&&f.empty.fire()},_queueHooks:function(a,b){var c=b+"queueHooks";return n._data(a,c)||n._data(a,c,{empty:n.Callbacks("once memory").add(function(){n._removeData(a,b+"queue"),n._removeData(a,c)})})}}),n.fn.extend({queue:function(a,b){var c=2;return"string"!=typeof a&&(b=a,a="fx",c--),arguments.length<c?n.queue(this[0],a):void 0===b?this:this.each(function(){var c=n.queue(this,a,b);n._queueHooks(this,a),"fx"===a&&"inprogress"!==c[0]&&n.dequeue(this,a)})},dequeue:function(a){return this.each(function(){n.dequeue(this,a)})},clearQueue:function(a){return this.queue(a||"fx",[])},promise:function(a,b){var c,d=1,e=n.Deferred(),f=this,g=this.length,h=function(){--d||e.resolveWith(f,[f])};"string"!=typeof a&&(b=a,a=void 0),a=a||"fx";while(g--)c=n._data(f[g],a+"queueHooks"),c&&c.empty&&(d++,c.empty.add(h));return h(),e.promise(b)}});var T=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,U=["Top","Right","Bottom","Left"],V=function(a,b){return a=b||a,"none"===n.css(a,"display")||!n.contains(a.ownerDocument,a)},W=n.access=function(a,b,c,d,e,f,g){var h=0,i=a.length,j=null==c;if("object"===n.type(c)){e=!0;for(h in c)n.access(a,b,h,c[h],!0,f,g)}else if(void 0!==d&&(e=!0,n.isFunction(d)||(g=!0),j&&(g?(b.call(a,d),b=null):(j=b,b=function(a,b,c){return j.call(n(a),c)})),b))for(;i>h;h++)b(a[h],c,g?d:d.call(a[h],h,b(a[h],c)));return e?a:j?b.call(a):i?b(a[0],c):f},X=/^(?:checkbox|radio)$/i;!function(){var a=z.createDocumentFragment(),b=z.createElement("div"),c=z.createElement("input");if(b.setAttribute("className","t"),b.innerHTML="  <link/><table></table><a href='/a'>a</a>",l.leadingWhitespace=3===b.firstChild.nodeType,l.tbody=!b.getElementsByTagName("tbody").length,l.htmlSerialize=!!b.getElementsByTagName("link").length,l.html5Clone="<:nav></:nav>"!==z.createElement("nav").cloneNode(!0).outerHTML,c.type="checkbox",c.checked=!0,a.appendChild(c),l.appendChecked=c.checked,b.innerHTML="<textarea>x</textarea>",l.noCloneChecked=!!b.cloneNode(!0).lastChild.defaultValue,a.appendChild(b),b.innerHTML="<input type='radio' checked='checked' name='t'/>",l.checkClone=b.cloneNode(!0).cloneNode(!0).lastChild.checked,l.noCloneEvent=!0,b.attachEvent&&(b.attachEvent("onclick",function(){l.noCloneEvent=!1}),b.cloneNode(!0).click()),null==l.deleteExpando){l.deleteExpando=!0;try{delete b.test}catch(d){l.deleteExpando=!1}}a=b=c=null}(),function(){var b,c,d=z.createElement("div");for(b in{submit:!0,change:!0,focusin:!0})c="on"+b,(l[b+"Bubbles"]=c in a)||(d.setAttribute(c,"t"),l[b+"Bubbles"]=d.attributes[c].expando===!1);d=null}();var Y=/^(?:input|select|textarea)$/i,Z=/^key/,$=/^(?:mouse|contextmenu)|click/,_=/^(?:focusinfocus|focusoutblur)$/,ab=/^([^.]*)(?:\.(.+)|)$/;function bb(){return!0}function cb(){return!1}function db(){try{return z.activeElement}catch(a){}}n.event={global:{},add:function(a,b,c,d,e){var f,g,h,i,j,k,l,m,o,p,q,r=n._data(a);if(r){c.handler&&(i=c,c=i.handler,e=i.selector),c.guid||(c.guid=n.guid++),(g=r.events)||(g=r.events={}),(k=r.handle)||(k=r.handle=function(a){return typeof n===L||a&&n.event.triggered===a.type?void 0:n.event.dispatch.apply(k.elem,arguments)},k.elem=a),b=(b||"").match(F)||[""],h=b.length;while(h--)f=ab.exec(b[h])||[],o=q=f[1],p=(f[2]||"").split(".").sort(),o&&(j=n.event.special[o]||{},o=(e?j.delegateType:j.bindType)||o,j=n.event.special[o]||{},l=n.extend({type:o,origType:q,data:d,handler:c,guid:c.guid,selector:e,needsContext:e&&n.expr.match.needsContext.test(e),namespace:p.join(".")},i),(m=g[o])||(m=g[o]=[],m.delegateCount=0,j.setup&&j.setup.call(a,d,p,k)!==!1||(a.addEventListener?a.addEventListener(o,k,!1):a.attachEvent&&a.attachEvent("on"+o,k))),j.add&&(j.add.call(a,l),l.handler.guid||(l.handler.guid=c.guid)),e?m.splice(m.delegateCount++,0,l):m.push(l),n.event.global[o]=!0);a=null}},remove:function(a,b,c,d,e){var f,g,h,i,j,k,l,m,o,p,q,r=n.hasData(a)&&n._data(a);if(r&&(k=r.events)){b=(b||"").match(F)||[""],j=b.length;while(j--)if(h=ab.exec(b[j])||[],o=q=h[1],p=(h[2]||"").split(".").sort(),o){l=n.event.special[o]||{},o=(d?l.delegateType:l.bindType)||o,m=k[o]||[],h=h[2]&&new RegExp("(^|\\.)"+p.join("\\.(?:.*\\.|)")+"(\\.|$)"),i=f=m.length;while(f--)g=m[f],!e&&q!==g.origType||c&&c.guid!==g.guid||h&&!h.test(g.namespace)||d&&d!==g.selector&&("**"!==d||!g.selector)||(m.splice(f,1),g.selector&&m.delegateCount--,l.remove&&l.remove.call(a,g));i&&!m.length&&(l.teardown&&l.teardown.call(a,p,r.handle)!==!1||n.removeEvent(a,o,r.handle),delete k[o])}else for(o in k)n.event.remove(a,o+b[j],c,d,!0);n.isEmptyObject(k)&&(delete r.handle,n._removeData(a,"events"))}},trigger:function(b,c,d,e){var f,g,h,i,k,l,m,o=[d||z],p=j.call(b,"type")?b.type:b,q=j.call(b,"namespace")?b.namespace.split("."):[];if(h=l=d=d||z,3!==d.nodeType&&8!==d.nodeType&&!_.test(p+n.event.triggered)&&(p.indexOf(".")>=0&&(q=p.split("."),p=q.shift(),q.sort()),g=p.indexOf(":")<0&&"on"+p,b=b[n.expando]?b:new n.Event(p,"object"==typeof b&&b),b.isTrigger=e?2:3,b.namespace=q.join("."),b.namespace_re=b.namespace?new RegExp("(^|\\.)"+q.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,b.result=void 0,b.target||(b.target=d),c=null==c?[b]:n.makeArray(c,[b]),k=n.event.special[p]||{},e||!k.trigger||k.trigger.apply(d,c)!==!1)){if(!e&&!k.noBubble&&!n.isWindow(d)){for(i=k.delegateType||p,_.test(i+p)||(h=h.parentNode);h;h=h.parentNode)o.push(h),l=h;l===(d.ownerDocument||z)&&o.push(l.defaultView||l.parentWindow||a)}m=0;while((h=o[m++])&&!b.isPropagationStopped())b.type=m>1?i:k.bindType||p,f=(n._data(h,"events")||{})[b.type]&&n._data(h,"handle"),f&&f.apply(h,c),f=g&&h[g],f&&f.apply&&n.acceptData(h)&&(b.result=f.apply(h,c),b.result===!1&&b.preventDefault());if(b.type=p,!e&&!b.isDefaultPrevented()&&(!k._default||k._default.apply(o.pop(),c)===!1)&&n.acceptData(d)&&g&&d[p]&&!n.isWindow(d)){l=d[g],l&&(d[g]=null),n.event.triggered=p;try{d[p]()}catch(r){}n.event.triggered=void 0,l&&(d[g]=l)}return b.result}},dispatch:function(a){a=n.event.fix(a);var b,c,e,f,g,h=[],i=d.call(arguments),j=(n._data(this,"events")||{})[a.type]||[],k=n.event.special[a.type]||{};if(i[0]=a,a.delegateTarget=this,!k.preDispatch||k.preDispatch.call(this,a)!==!1){h=n.event.handlers.call(this,a,j),b=0;while((f=h[b++])&&!a.isPropagationStopped()){a.currentTarget=f.elem,g=0;while((e=f.handlers[g++])&&!a.isImmediatePropagationStopped())(!a.namespace_re||a.namespace_re.test(e.namespace))&&(a.handleObj=e,a.data=e.data,c=((n.event.special[e.origType]||{}).handle||e.handler).apply(f.elem,i),void 0!==c&&(a.result=c)===!1&&(a.preventDefault(),a.stopPropagation()))}return k.postDispatch&&k.postDispatch.call(this,a),a.result}},handlers:function(a,b){var c,d,e,f,g=[],h=b.delegateCount,i=a.target;if(h&&i.nodeType&&(!a.button||"click"!==a.type))for(;i!=this;i=i.parentNode||this)if(1===i.nodeType&&(i.disabled!==!0||"click"!==a.type)){for(e=[],f=0;h>f;f++)d=b[f],c=d.selector+" ",void 0===e[c]&&(e[c]=d.needsContext?n(c,this).index(i)>=0:n.find(c,this,null,[i]).length),e[c]&&e.push(d);e.length&&g.push({elem:i,handlers:e})}return h<b.length&&g.push({elem:this,handlers:b.slice(h)}),g},fix:function(a){if(a[n.expando])return a;var b,c,d,e=a.type,f=a,g=this.fixHooks[e];g||(this.fixHooks[e]=g=$.test(e)?this.mouseHooks:Z.test(e)?this.keyHooks:{}),d=g.props?this.props.concat(g.props):this.props,a=new n.Event(f),b=d.length;while(b--)c=d[b],a[c]=f[c];return a.target||(a.target=f.srcElement||z),3===a.target.nodeType&&(a.target=a.target.parentNode),a.metaKey=!!a.metaKey,g.filter?g.filter(a,f):a},props:"altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(a,b){return null==a.which&&(a.which=null!=b.charCode?b.charCode:b.keyCode),a}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(a,b){var c,d,e,f=b.button,g=b.fromElement;return null==a.pageX&&null!=b.clientX&&(d=a.target.ownerDocument||z,e=d.documentElement,c=d.body,a.pageX=b.clientX+(e&&e.scrollLeft||c&&c.scrollLeft||0)-(e&&e.clientLeft||c&&c.clientLeft||0),a.pageY=b.clientY+(e&&e.scrollTop||c&&c.scrollTop||0)-(e&&e.clientTop||c&&c.clientTop||0)),!a.relatedTarget&&g&&(a.relatedTarget=g===a.target?b.toElement:g),a.which||void 0===f||(a.which=1&f?1:2&f?3:4&f?2:0),a}},special:{load:{noBubble:!0},focus:{trigger:function(){if(this!==db()&&this.focus)try{return this.focus(),!1}catch(a){}},delegateType:"focusin"},blur:{trigger:function(){return this===db()&&this.blur?(this.blur(),!1):void 0},delegateType:"focusout"},click:{trigger:function(){return n.nodeName(this,"input")&&"checkbox"===this.type&&this.click?(this.click(),!1):void 0},_default:function(a){return n.nodeName(a.target,"a")}},beforeunload:{postDispatch:function(a){void 0!==a.result&&(a.originalEvent.returnValue=a.result)}}},simulate:function(a,b,c,d){var e=n.extend(new n.Event,c,{type:a,isSimulated:!0,originalEvent:{}});d?n.event.trigger(e,null,b):n.event.dispatch.call(b,e),e.isDefaultPrevented()&&c.preventDefault()}},n.removeEvent=z.removeEventListener?function(a,b,c){a.removeEventListener&&a.removeEventListener(b,c,!1)}:function(a,b,c){var d="on"+b;a.detachEvent&&(typeof a[d]===L&&(a[d]=null),a.detachEvent(d,c))},n.Event=function(a,b){return this instanceof n.Event?(a&&a.type?(this.originalEvent=a,this.type=a.type,this.isDefaultPrevented=a.defaultPrevented||void 0===a.defaultPrevented&&(a.returnValue===!1||a.getPreventDefault&&a.getPreventDefault())?bb:cb):this.type=a,b&&n.extend(this,b),this.timeStamp=a&&a.timeStamp||n.now(),void(this[n.expando]=!0)):new n.Event(a,b)},n.Event.prototype={isDefaultPrevented:cb,isPropagationStopped:cb,isImmediatePropagationStopped:cb,preventDefault:function(){var a=this.originalEvent;this.isDefaultPrevented=bb,a&&(a.preventDefault?a.preventDefault():a.returnValue=!1)},stopPropagation:function(){var a=this.originalEvent;this.isPropagationStopped=bb,a&&(a.stopPropagation&&a.stopPropagation(),a.cancelBubble=!0)},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=bb,this.stopPropagation()}},n.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(a,b){n.event.special[a]={delegateType:b,bindType:b,handle:function(a){var c,d=this,e=a.relatedTarget,f=a.handleObj;return(!e||e!==d&&!n.contains(d,e))&&(a.type=f.origType,c=f.handler.apply(this,arguments),a.type=b),c}}}),l.submitBubbles||(n.event.special.submit={setup:function(){return n.nodeName(this,"form")?!1:void n.event.add(this,"click._submit keypress._submit",function(a){var b=a.target,c=n.nodeName(b,"input")||n.nodeName(b,"button")?b.form:void 0;c&&!n._data(c,"submitBubbles")&&(n.event.add(c,"submit._submit",function(a){a._submit_bubble=!0}),n._data(c,"submitBubbles",!0))})},postDispatch:function(a){a._submit_bubble&&(delete a._submit_bubble,this.parentNode&&!a.isTrigger&&n.event.simulate("submit",this.parentNode,a,!0))},teardown:function(){return n.nodeName(this,"form")?!1:void n.event.remove(this,"._submit")}}),l.changeBubbles||(n.event.special.change={setup:function(){return Y.test(this.nodeName)?(("checkbox"===this.type||"radio"===this.type)&&(n.event.add(this,"propertychange._change",function(a){"checked"===a.originalEvent.propertyName&&(this._just_changed=!0)}),n.event.add(this,"click._change",function(a){this._just_changed&&!a.isTrigger&&(this._just_changed=!1),n.event.simulate("change",this,a,!0)})),!1):void n.event.add(this,"beforeactivate._change",function(a){var b=a.target;Y.test(b.nodeName)&&!n._data(b,"changeBubbles")&&(n.event.add(b,"change._change",function(a){!this.parentNode||a.isSimulated||a.isTrigger||n.event.simulate("change",this.parentNode,a,!0)}),n._data(b,"changeBubbles",!0))})},handle:function(a){var b=a.target;return this!==b||a.isSimulated||a.isTrigger||"radio"!==b.type&&"checkbox"!==b.type?a.handleObj.handler.apply(this,arguments):void 0},teardown:function(){return n.event.remove(this,"._change"),!Y.test(this.nodeName)}}),l.focusinBubbles||n.each({focus:"focusin",blur:"focusout"},function(a,b){var c=function(a){n.event.simulate(b,a.target,n.event.fix(a),!0)};n.event.special[b]={setup:function(){var d=this.ownerDocument||this,e=n._data(d,b);e||d.addEventListener(a,c,!0),n._data(d,b,(e||0)+1)},teardown:function(){var d=this.ownerDocument||this,e=n._data(d,b)-1;e?n._data(d,b,e):(d.removeEventListener(a,c,!0),n._removeData(d,b))}}}),n.fn.extend({on:function(a,b,c,d,e){var f,g;if("object"==typeof a){"string"!=typeof b&&(c=c||b,b=void 0);for(f in a)this.on(f,b,c,a[f],e);return this}if(null==c&&null==d?(d=b,c=b=void 0):null==d&&("string"==typeof b?(d=c,c=void 0):(d=c,c=b,b=void 0)),d===!1)d=cb;else if(!d)return this;return 1===e&&(g=d,d=function(a){return n().off(a),g.apply(this,arguments)},d.guid=g.guid||(g.guid=n.guid++)),this.each(function(){n.event.add(this,a,d,c,b)})},one:function(a,b,c,d){return this.on(a,b,c,d,1)},off:function(a,b,c){var d,e;if(a&&a.preventDefault&&a.handleObj)return d=a.handleObj,n(a.delegateTarget).off(d.namespace?d.origType+"."+d.namespace:d.origType,d.selector,d.handler),this;if("object"==typeof a){for(e in a)this.off(e,b,a[e]);return this}return(b===!1||"function"==typeof b)&&(c=b,b=void 0),c===!1&&(c=cb),this.each(function(){n.event.remove(this,a,c,b)})},trigger:function(a,b){return this.each(function(){n.event.trigger(a,b,this)})},triggerHandler:function(a,b){var c=this[0];return c?n.event.trigger(a,b,c,!0):void 0}});function eb(a){var b=fb.split("|"),c=a.createDocumentFragment();if(c.createElement)while(b.length)c.createElement(b.pop());return c}var fb="abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",gb=/ jQuery\d+="(?:null|\d+)"/g,hb=new RegExp("<(?:"+fb+")[\\s/>]","i"),ib=/^\s+/,jb=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,kb=/<([\w:]+)/,lb=/<tbody/i,mb=/<|&#?\w+;/,nb=/<(?:script|style|link)/i,ob=/checked\s*(?:[^=]|=\s*.checked.)/i,pb=/^$|\/(?:java|ecma)script/i,qb=/^true\/(.*)/,rb=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,sb={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],area:[1,"<map>","</map>"],param:[1,"<object>","</object>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:l.htmlSerialize?[0,"",""]:[1,"X<div>","</div>"]},tb=eb(z),ub=tb.appendChild(z.createElement("div"));sb.optgroup=sb.option,sb.tbody=sb.tfoot=sb.colgroup=sb.caption=sb.thead,sb.th=sb.td;function vb(a,b){var c,d,e=0,f=typeof a.getElementsByTagName!==L?a.getElementsByTagName(b||"*"):typeof a.querySelectorAll!==L?a.querySelectorAll(b||"*"):void 0;if(!f)for(f=[],c=a.childNodes||a;null!=(d=c[e]);e++)!b||n.nodeName(d,b)?f.push(d):n.merge(f,vb(d,b));return void 0===b||b&&n.nodeName(a,b)?n.merge([a],f):f}function wb(a){X.test(a.type)&&(a.defaultChecked=a.checked)}function xb(a,b){return n.nodeName(a,"table")&&n.nodeName(11!==b.nodeType?b:b.firstChild,"tr")?a.getElementsByTagName("tbody")[0]||a.appendChild(a.ownerDocument.createElement("tbody")):a}function yb(a){return a.type=(null!==n.find.attr(a,"type"))+"/"+a.type,a}function zb(a){var b=qb.exec(a.type);return b?a.type=b[1]:a.removeAttribute("type"),a}function Ab(a,b){for(var c,d=0;null!=(c=a[d]);d++)n._data(c,"globalEval",!b||n._data(b[d],"globalEval"))}function Bb(a,b){if(1===b.nodeType&&n.hasData(a)){var c,d,e,f=n._data(a),g=n._data(b,f),h=f.events;if(h){delete g.handle,g.events={};for(c in h)for(d=0,e=h[c].length;e>d;d++)n.event.add(b,c,h[c][d])}g.data&&(g.data=n.extend({},g.data))}}function Cb(a,b){var c,d,e;if(1===b.nodeType){if(c=b.nodeName.toLowerCase(),!l.noCloneEvent&&b[n.expando]){e=n._data(b);for(d in e.events)n.removeEvent(b,d,e.handle);b.removeAttribute(n.expando)}"script"===c&&b.text!==a.text?(yb(b).text=a.text,zb(b)):"object"===c?(b.parentNode&&(b.outerHTML=a.outerHTML),l.html5Clone&&a.innerHTML&&!n.trim(b.innerHTML)&&(b.innerHTML=a.innerHTML)):"input"===c&&X.test(a.type)?(b.defaultChecked=b.checked=a.checked,b.value!==a.value&&(b.value=a.value)):"option"===c?b.defaultSelected=b.selected=a.defaultSelected:("input"===c||"textarea"===c)&&(b.defaultValue=a.defaultValue)}}n.extend({clone:function(a,b,c){var d,e,f,g,h,i=n.contains(a.ownerDocument,a);if(l.html5Clone||n.isXMLDoc(a)||!hb.test("<"+a.nodeName+">")?f=a.cloneNode(!0):(ub.innerHTML=a.outerHTML,ub.removeChild(f=ub.firstChild)),!(l.noCloneEvent&&l.noCloneChecked||1!==a.nodeType&&11!==a.nodeType||n.isXMLDoc(a)))for(d=vb(f),h=vb(a),g=0;null!=(e=h[g]);++g)d[g]&&Cb(e,d[g]);if(b)if(c)for(h=h||vb(a),d=d||vb(f),g=0;null!=(e=h[g]);g++)Bb(e,d[g]);else Bb(a,f);return d=vb(f,"script"),d.length>0&&Ab(d,!i&&vb(a,"script")),d=h=e=null,f},buildFragment:function(a,b,c,d){for(var e,f,g,h,i,j,k,m=a.length,o=eb(b),p=[],q=0;m>q;q++)if(f=a[q],f||0===f)if("object"===n.type(f))n.merge(p,f.nodeType?[f]:f);else if(mb.test(f)){h=h||o.appendChild(b.createElement("div")),i=(kb.exec(f)||["",""])[1].toLowerCase(),k=sb[i]||sb._default,h.innerHTML=k[1]+f.replace(jb,"<$1></$2>")+k[2],e=k[0];while(e--)h=h.lastChild;if(!l.leadingWhitespace&&ib.test(f)&&p.push(b.createTextNode(ib.exec(f)[0])),!l.tbody){f="table"!==i||lb.test(f)?"<table>"!==k[1]||lb.test(f)?0:h:h.firstChild,e=f&&f.childNodes.length;while(e--)n.nodeName(j=f.childNodes[e],"tbody")&&!j.childNodes.length&&f.removeChild(j)}n.merge(p,h.childNodes),h.textContent="";while(h.firstChild)h.removeChild(h.firstChild);h=o.lastChild}else p.push(b.createTextNode(f));h&&o.removeChild(h),l.appendChecked||n.grep(vb(p,"input"),wb),q=0;while(f=p[q++])if((!d||-1===n.inArray(f,d))&&(g=n.contains(f.ownerDocument,f),h=vb(o.appendChild(f),"script"),g&&Ab(h),c)){e=0;while(f=h[e++])pb.test(f.type||"")&&c.push(f)}return h=null,o},cleanData:function(a,b){for(var d,e,f,g,h=0,i=n.expando,j=n.cache,k=l.deleteExpando,m=n.event.special;null!=(d=a[h]);h++)if((b||n.acceptData(d))&&(f=d[i],g=f&&j[f])){if(g.events)for(e in g.events)m[e]?n.event.remove(d,e):n.removeEvent(d,e,g.handle);j[f]&&(delete j[f],k?delete d[i]:typeof d.removeAttribute!==L?d.removeAttribute(i):d[i]=null,c.push(f))}}}),n.fn.extend({text:function(a){return W(this,function(a){return void 0===a?n.text(this):this.empty().append((this[0]&&this[0].ownerDocument||z).createTextNode(a))},null,a,arguments.length)},append:function(){return this.domManip(arguments,function(a){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var b=xb(this,a);b.appendChild(a)}})},prepend:function(){return this.domManip(arguments,function(a){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var b=xb(this,a);b.insertBefore(a,b.firstChild)}})},before:function(){return this.domManip(arguments,function(a){this.parentNode&&this.parentNode.insertBefore(a,this)})},after:function(){return this.domManip(arguments,function(a){this.parentNode&&this.parentNode.insertBefore(a,this.nextSibling)})},remove:function(a,b){for(var c,d=a?n.filter(a,this):this,e=0;null!=(c=d[e]);e++)b||1!==c.nodeType||n.cleanData(vb(c)),c.parentNode&&(b&&n.contains(c.ownerDocument,c)&&Ab(vb(c,"script")),c.parentNode.removeChild(c));return this},empty:function(){for(var a,b=0;null!=(a=this[b]);b++){1===a.nodeType&&n.cleanData(vb(a,!1));while(a.firstChild)a.removeChild(a.firstChild);a.options&&n.nodeName(a,"select")&&(a.options.length=0)}return this},clone:function(a,b){return a=null==a?!1:a,b=null==b?a:b,this.map(function(){return n.clone(this,a,b)})},html:function(a){return W(this,function(a){var b=this[0]||{},c=0,d=this.length;if(void 0===a)return 1===b.nodeType?b.innerHTML.replace(gb,""):void 0;if(!("string"!=typeof a||nb.test(a)||!l.htmlSerialize&&hb.test(a)||!l.leadingWhitespace&&ib.test(a)||sb[(kb.exec(a)||["",""])[1].toLowerCase()])){a=a.replace(jb,"<$1></$2>");try{for(;d>c;c++)b=this[c]||{},1===b.nodeType&&(n.cleanData(vb(b,!1)),b.innerHTML=a);b=0}catch(e){}}b&&this.empty().append(a)},null,a,arguments.length)},replaceWith:function(){var a=arguments[0];return this.domManip(arguments,function(b){a=this.parentNode,n.cleanData(vb(this)),a&&a.replaceChild(b,this)}),a&&(a.length||a.nodeType)?this:this.remove()},detach:function(a){return this.remove(a,!0)},domManip:function(a,b){a=e.apply([],a);var c,d,f,g,h,i,j=0,k=this.length,m=this,o=k-1,p=a[0],q=n.isFunction(p);if(q||k>1&&"string"==typeof p&&!l.checkClone&&ob.test(p))return this.each(function(c){var d=m.eq(c);q&&(a[0]=p.call(this,c,d.html())),d.domManip(a,b)});if(k&&(i=n.buildFragment(a,this[0].ownerDocument,!1,this),c=i.firstChild,1===i.childNodes.length&&(i=c),c)){for(g=n.map(vb(i,"script"),yb),f=g.length;k>j;j++)d=i,j!==o&&(d=n.clone(d,!0,!0),f&&n.merge(g,vb(d,"script"))),b.call(this[j],d,j);if(f)for(h=g[g.length-1].ownerDocument,n.map(g,zb),j=0;f>j;j++)d=g[j],pb.test(d.type||"")&&!n._data(d,"globalEval")&&n.contains(h,d)&&(d.src?n._evalUrl&&n._evalUrl(d.src):n.globalEval((d.text||d.textContent||d.innerHTML||"").replace(rb,"")));i=c=null}return this}}),n.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(a,b){n.fn[a]=function(a){for(var c,d=0,e=[],g=n(a),h=g.length-1;h>=d;d++)c=d===h?this:this.clone(!0),n(g[d])[b](c),f.apply(e,c.get());return this.pushStack(e)}});var Db,Eb={};function Fb(b,c){var d=n(c.createElement(b)).appendTo(c.body),e=a.getDefaultComputedStyle?a.getDefaultComputedStyle(d[0]).display:n.css(d[0],"display");return d.detach(),e}function Gb(a){var b=z,c=Eb[a];return c||(c=Fb(a,b),"none"!==c&&c||(Db=(Db||n("<iframe frameborder='0' width='0' height='0'/>")).appendTo(b.documentElement),b=(Db[0].contentWindow||Db[0].contentDocument).document,b.write(),b.close(),c=Fb(a,b),Db.detach()),Eb[a]=c),c}!function(){var a,b,c=z.createElement("div"),d="-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;display:block;padding:0;margin:0;border:0";c.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",a=c.getElementsByTagName("a")[0],a.style.cssText="float:left;opacity:.5",l.opacity=/^0.5/.test(a.style.opacity),l.cssFloat=!!a.style.cssFloat,c.style.backgroundClip="content-box",c.cloneNode(!0).style.backgroundClip="",l.clearCloneStyle="content-box"===c.style.backgroundClip,a=c=null,l.shrinkWrapBlocks=function(){var a,c,e,f;if(null==b){if(a=z.getElementsByTagName("body")[0],!a)return;f="border:0;width:0;height:0;position:absolute;top:0;left:-9999px",c=z.createElement("div"),e=z.createElement("div"),a.appendChild(c).appendChild(e),b=!1,typeof e.style.zoom!==L&&(e.style.cssText=d+";width:1px;padding:1px;zoom:1",e.innerHTML="<div></div>",e.firstChild.style.width="5px",b=3!==e.offsetWidth),a.removeChild(c),a=c=e=null}return b}}();var Hb=/^margin/,Ib=new RegExp("^("+T+")(?!px)[a-z%]+$","i"),Jb,Kb,Lb=/^(top|right|bottom|left)$/;a.getComputedStyle?(Jb=function(a){return a.ownerDocument.defaultView.getComputedStyle(a,null)},Kb=function(a,b,c){var d,e,f,g,h=a.style;return c=c||Jb(a),g=c?c.getPropertyValue(b)||c[b]:void 0,c&&(""!==g||n.contains(a.ownerDocument,a)||(g=n.style(a,b)),Ib.test(g)&&Hb.test(b)&&(d=h.width,e=h.minWidth,f=h.maxWidth,h.minWidth=h.maxWidth=h.width=g,g=c.width,h.width=d,h.minWidth=e,h.maxWidth=f)),void 0===g?g:g+""}):z.documentElement.currentStyle&&(Jb=function(a){return a.currentStyle},Kb=function(a,b,c){var d,e,f,g,h=a.style;return c=c||Jb(a),g=c?c[b]:void 0,null==g&&h&&h[b]&&(g=h[b]),Ib.test(g)&&!Lb.test(b)&&(d=h.left,e=a.runtimeStyle,f=e&&e.left,f&&(e.left=a.currentStyle.left),h.left="fontSize"===b?"1em":g,g=h.pixelLeft+"px",h.left=d,f&&(e.left=f)),void 0===g?g:g+""||"auto"});function Mb(a,b){return{get:function(){var c=a();if(null!=c)return c?void delete this.get:(this.get=b).apply(this,arguments)}}}!function(){var b,c,d,e,f,g,h=z.createElement("div"),i="border:0;width:0;height:0;position:absolute;top:0;left:-9999px",j="-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;display:block;padding:0;margin:0;border:0";h.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",b=h.getElementsByTagName("a")[0],b.style.cssText="float:left;opacity:.5",l.opacity=/^0.5/.test(b.style.opacity),l.cssFloat=!!b.style.cssFloat,h.style.backgroundClip="content-box",h.cloneNode(!0).style.backgroundClip="",l.clearCloneStyle="content-box"===h.style.backgroundClip,b=h=null,n.extend(l,{reliableHiddenOffsets:function(){if(null!=c)return c;var a,b,d,e=z.createElement("div"),f=z.getElementsByTagName("body")[0];if(f)return e.setAttribute("className","t"),e.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",a=z.createElement("div"),a.style.cssText=i,f.appendChild(a).appendChild(e),e.innerHTML="<table><tr><td></td><td>t</td></tr></table>",b=e.getElementsByTagName("td"),b[0].style.cssText="padding:0;margin:0;border:0;display:none",d=0===b[0].offsetHeight,b[0].style.display="",b[1].style.display="none",c=d&&0===b[0].offsetHeight,f.removeChild(a),e=f=null,c},boxSizing:function(){return null==d&&k(),d},boxSizingReliable:function(){return null==e&&k(),e},pixelPosition:function(){return null==f&&k(),f},reliableMarginRight:function(){var b,c,d,e;if(null==g&&a.getComputedStyle){if(b=z.getElementsByTagName("body")[0],!b)return;c=z.createElement("div"),d=z.createElement("div"),c.style.cssText=i,b.appendChild(c).appendChild(d),e=d.appendChild(z.createElement("div")),e.style.cssText=d.style.cssText=j,e.style.marginRight=e.style.width="0",d.style.width="1px",g=!parseFloat((a.getComputedStyle(e,null)||{}).marginRight),b.removeChild(c)}return g}});function k(){var b,c,h=z.getElementsByTagName("body")[0];h&&(b=z.createElement("div"),c=z.createElement("div"),b.style.cssText=i,h.appendChild(b).appendChild(c),c.style.cssText="-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;position:absolute;display:block;padding:1px;border:1px;width:4px;margin-top:1%;top:1%",n.swap(h,null!=h.style.zoom?{zoom:1}:{},function(){d=4===c.offsetWidth}),e=!0,f=!1,g=!0,a.getComputedStyle&&(f="1%"!==(a.getComputedStyle(c,null)||{}).top,e="4px"===(a.getComputedStyle(c,null)||{width:"4px"}).width),h.removeChild(b),c=h=null)}}(),n.swap=function(a,b,c,d){var e,f,g={};for(f in b)g[f]=a.style[f],a.style[f]=b[f];e=c.apply(a,d||[]);for(f in b)a.style[f]=g[f];return e};var Nb=/alpha\([^)]*\)/i,Ob=/opacity\s*=\s*([^)]*)/,Pb=/^(none|table(?!-c[ea]).+)/,Qb=new RegExp("^("+T+")(.*)$","i"),Rb=new RegExp("^([+-])=("+T+")","i"),Sb={position:"absolute",visibility:"hidden",display:"block"},Tb={letterSpacing:0,fontWeight:400},Ub=["Webkit","O","Moz","ms"];function Vb(a,b){if(b in a)return b;var c=b.charAt(0).toUpperCase()+b.slice(1),d=b,e=Ub.length;while(e--)if(b=Ub[e]+c,b in a)return b;return d}function Wb(a,b){for(var c,d,e,f=[],g=0,h=a.length;h>g;g++)d=a[g],d.style&&(f[g]=n._data(d,"olddisplay"),c=d.style.display,b?(f[g]||"none"!==c||(d.style.display=""),""===d.style.display&&V(d)&&(f[g]=n._data(d,"olddisplay",Gb(d.nodeName)))):f[g]||(e=V(d),(c&&"none"!==c||!e)&&n._data(d,"olddisplay",e?c:n.css(d,"display"))));for(g=0;h>g;g++)d=a[g],d.style&&(b&&"none"!==d.style.display&&""!==d.style.display||(d.style.display=b?f[g]||"":"none"));return a}function Xb(a,b,c){var d=Qb.exec(b);return d?Math.max(0,d[1]-(c||0))+(d[2]||"px"):b}function Yb(a,b,c,d,e){for(var f=c===(d?"border":"content")?4:"width"===b?1:0,g=0;4>f;f+=2)"margin"===c&&(g+=n.css(a,c+U[f],!0,e)),d?("content"===c&&(g-=n.css(a,"padding"+U[f],!0,e)),"margin"!==c&&(g-=n.css(a,"border"+U[f]+"Width",!0,e))):(g+=n.css(a,"padding"+U[f],!0,e),"padding"!==c&&(g+=n.css(a,"border"+U[f]+"Width",!0,e)));return g}function Zb(a,b,c){var d=!0,e="width"===b?a.offsetWidth:a.offsetHeight,f=Jb(a),g=l.boxSizing()&&"border-box"===n.css(a,"boxSizing",!1,f);if(0>=e||null==e){if(e=Kb(a,b,f),(0>e||null==e)&&(e=a.style[b]),Ib.test(e))return e;d=g&&(l.boxSizingReliable()||e===a.style[b]),e=parseFloat(e)||0}return e+Yb(a,b,c||(g?"border":"content"),d,f)+"px"}n.extend({cssHooks:{opacity:{get:function(a,b){if(b){var c=Kb(a,"opacity");return""===c?"1":c}}}},cssNumber:{columnCount:!0,fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{"float":l.cssFloat?"cssFloat":"styleFloat"},style:function(a,b,c,d){if(a&&3!==a.nodeType&&8!==a.nodeType&&a.style){var e,f,g,h=n.camelCase(b),i=a.style;if(b=n.cssProps[h]||(n.cssProps[h]=Vb(i,h)),g=n.cssHooks[b]||n.cssHooks[h],void 0===c)return g&&"get"in g&&void 0!==(e=g.get(a,!1,d))?e:i[b];if(f=typeof c,"string"===f&&(e=Rb.exec(c))&&(c=(e[1]+1)*e[2]+parseFloat(n.css(a,b)),f="number"),null!=c&&c===c&&("number"!==f||n.cssNumber[h]||(c+="px"),l.clearCloneStyle||""!==c||0!==b.indexOf("background")||(i[b]="inherit"),!(g&&"set"in g&&void 0===(c=g.set(a,c,d)))))try{i[b]="",i[b]=c}catch(j){}}},css:function(a,b,c,d){var e,f,g,h=n.camelCase(b);return b=n.cssProps[h]||(n.cssProps[h]=Vb(a.style,h)),g=n.cssHooks[b]||n.cssHooks[h],g&&"get"in g&&(f=g.get(a,!0,c)),void 0===f&&(f=Kb(a,b,d)),"normal"===f&&b in Tb&&(f=Tb[b]),""===c||c?(e=parseFloat(f),c===!0||n.isNumeric(e)?e||0:f):f}}),n.each(["height","width"],function(a,b){n.cssHooks[b]={get:function(a,c,d){return c?0===a.offsetWidth&&Pb.test(n.css(a,"display"))?n.swap(a,Sb,function(){return Zb(a,b,d)}):Zb(a,b,d):void 0},set:function(a,c,d){var e=d&&Jb(a);return Xb(a,c,d?Yb(a,b,d,l.boxSizing()&&"border-box"===n.css(a,"boxSizing",!1,e),e):0)}}}),l.opacity||(n.cssHooks.opacity={get:function(a,b){return Ob.test((b&&a.currentStyle?a.currentStyle.filter:a.style.filter)||"")?.01*parseFloat(RegExp.$1)+"":b?"1":""},set:function(a,b){var c=a.style,d=a.currentStyle,e=n.isNumeric(b)?"alpha(opacity="+100*b+")":"",f=d&&d.filter||c.filter||"";c.zoom=1,(b>=1||""===b)&&""===n.trim(f.replace(Nb,""))&&c.removeAttribute&&(c.removeAttribute("filter"),""===b||d&&!d.filter)||(c.filter=Nb.test(f)?f.replace(Nb,e):f+" "+e)}}),n.cssHooks.marginRight=Mb(l.reliableMarginRight,function(a,b){return b?n.swap(a,{display:"inline-block"},Kb,[a,"marginRight"]):void 0}),n.each({margin:"",padding:"",border:"Width"},function(a,b){n.cssHooks[a+b]={expand:function(c){for(var d=0,e={},f="string"==typeof c?c.split(" "):[c];4>d;d++)e[a+U[d]+b]=f[d]||f[d-2]||f[0];return e}},Hb.test(a)||(n.cssHooks[a+b].set=Xb)}),n.fn.extend({css:function(a,b){return W(this,function(a,b,c){var d,e,f={},g=0;if(n.isArray(b)){for(d=Jb(a),e=b.length;e>g;g++)f[b[g]]=n.css(a,b[g],!1,d);return f}return void 0!==c?n.style(a,b,c):n.css(a,b)
    },a,b,arguments.length>1)},show:function(){return Wb(this,!0)},hide:function(){return Wb(this)},toggle:function(a){return"boolean"==typeof a?a?this.show():this.hide():this.each(function(){V(this)?n(this).show():n(this).hide()})}});function $b(a,b,c,d,e){return new $b.prototype.init(a,b,c,d,e)}n.Tween=$b,$b.prototype={constructor:$b,init:function(a,b,c,d,e,f){this.elem=a,this.prop=c,this.easing=e||"swing",this.options=b,this.start=this.now=this.cur(),this.end=d,this.unit=f||(n.cssNumber[c]?"":"px")},cur:function(){var a=$b.propHooks[this.prop];return a&&a.get?a.get(this):$b.propHooks._default.get(this)},run:function(a){var b,c=$b.propHooks[this.prop];return this.pos=b=this.options.duration?n.easing[this.easing](a,this.options.duration*a,0,1,this.options.duration):a,this.now=(this.end-this.start)*b+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),c&&c.set?c.set(this):$b.propHooks._default.set(this),this}},$b.prototype.init.prototype=$b.prototype,$b.propHooks={_default:{get:function(a){var b;return null==a.elem[a.prop]||a.elem.style&&null!=a.elem.style[a.prop]?(b=n.css(a.elem,a.prop,""),b&&"auto"!==b?b:0):a.elem[a.prop]},set:function(a){n.fx.step[a.prop]?n.fx.step[a.prop](a):a.elem.style&&(null!=a.elem.style[n.cssProps[a.prop]]||n.cssHooks[a.prop])?n.style(a.elem,a.prop,a.now+a.unit):a.elem[a.prop]=a.now}}},$b.propHooks.scrollTop=$b.propHooks.scrollLeft={set:function(a){a.elem.nodeType&&a.elem.parentNode&&(a.elem[a.prop]=a.now)}},n.easing={linear:function(a){return a},swing:function(a){return.5-Math.cos(a*Math.PI)/2}},n.fx=$b.prototype.init,n.fx.step={};var _b,ac,bc=/^(?:toggle|show|hide)$/,cc=new RegExp("^(?:([+-])=|)("+T+")([a-z%]*)$","i"),dc=/queueHooks$/,ec=[jc],fc={"*":[function(a,b){var c=this.createTween(a,b),d=c.cur(),e=cc.exec(b),f=e&&e[3]||(n.cssNumber[a]?"":"px"),g=(n.cssNumber[a]||"px"!==f&&+d)&&cc.exec(n.css(c.elem,a)),h=1,i=20;if(g&&g[3]!==f){f=f||g[3],e=e||[],g=+d||1;do h=h||".5",g/=h,n.style(c.elem,a,g+f);while(h!==(h=c.cur()/d)&&1!==h&&--i)}return e&&(g=c.start=+g||+d||0,c.unit=f,c.end=e[1]?g+(e[1]+1)*e[2]:+e[2]),c}]};function gc(){return setTimeout(function(){_b=void 0}),_b=n.now()}function hc(a,b){var c,d={height:a},e=0;for(b=b?1:0;4>e;e+=2-b)c=U[e],d["margin"+c]=d["padding"+c]=a;return b&&(d.opacity=d.width=a),d}function ic(a,b,c){for(var d,e=(fc[b]||[]).concat(fc["*"]),f=0,g=e.length;g>f;f++)if(d=e[f].call(c,b,a))return d}function jc(a,b,c){var d,e,f,g,h,i,j,k,m=this,o={},p=a.style,q=a.nodeType&&V(a),r=n._data(a,"fxshow");c.queue||(h=n._queueHooks(a,"fx"),null==h.unqueued&&(h.unqueued=0,i=h.empty.fire,h.empty.fire=function(){h.unqueued||i()}),h.unqueued++,m.always(function(){m.always(function(){h.unqueued--,n.queue(a,"fx").length||h.empty.fire()})})),1===a.nodeType&&("height"in b||"width"in b)&&(c.overflow=[p.overflow,p.overflowX,p.overflowY],j=n.css(a,"display"),k=Gb(a.nodeName),"none"===j&&(j=k),"inline"===j&&"none"===n.css(a,"float")&&(l.inlineBlockNeedsLayout&&"inline"!==k?p.zoom=1:p.display="inline-block")),c.overflow&&(p.overflow="hidden",l.shrinkWrapBlocks()||m.always(function(){p.overflow=c.overflow[0],p.overflowX=c.overflow[1],p.overflowY=c.overflow[2]}));for(d in b)if(e=b[d],bc.exec(e)){if(delete b[d],f=f||"toggle"===e,e===(q?"hide":"show")){if("show"!==e||!r||void 0===r[d])continue;q=!0}o[d]=r&&r[d]||n.style(a,d)}if(!n.isEmptyObject(o)){r?"hidden"in r&&(q=r.hidden):r=n._data(a,"fxshow",{}),f&&(r.hidden=!q),q?n(a).show():m.done(function(){n(a).hide()}),m.done(function(){var b;n._removeData(a,"fxshow");for(b in o)n.style(a,b,o[b])});for(d in o)g=ic(q?r[d]:0,d,m),d in r||(r[d]=g.start,q&&(g.end=g.start,g.start="width"===d||"height"===d?1:0))}}function kc(a,b){var c,d,e,f,g;for(c in a)if(d=n.camelCase(c),e=b[d],f=a[c],n.isArray(f)&&(e=f[1],f=a[c]=f[0]),c!==d&&(a[d]=f,delete a[c]),g=n.cssHooks[d],g&&"expand"in g){f=g.expand(f),delete a[d];for(c in f)c in a||(a[c]=f[c],b[c]=e)}else b[d]=e}function lc(a,b,c){var d,e,f=0,g=ec.length,h=n.Deferred().always(function(){delete i.elem}),i=function(){if(e)return!1;for(var b=_b||gc(),c=Math.max(0,j.startTime+j.duration-b),d=c/j.duration||0,f=1-d,g=0,i=j.tweens.length;i>g;g++)j.tweens[g].run(f);return h.notifyWith(a,[j,f,c]),1>f&&i?c:(h.resolveWith(a,[j]),!1)},j=h.promise({elem:a,props:n.extend({},b),opts:n.extend(!0,{specialEasing:{}},c),originalProperties:b,originalOptions:c,startTime:_b||gc(),duration:c.duration,tweens:[],createTween:function(b,c){var d=n.Tween(a,j.opts,b,c,j.opts.specialEasing[b]||j.opts.easing);return j.tweens.push(d),d},stop:function(b){var c=0,d=b?j.tweens.length:0;if(e)return this;for(e=!0;d>c;c++)j.tweens[c].run(1);return b?h.resolveWith(a,[j,b]):h.rejectWith(a,[j,b]),this}}),k=j.props;for(kc(k,j.opts.specialEasing);g>f;f++)if(d=ec[f].call(j,a,k,j.opts))return d;return n.map(k,ic,j),n.isFunction(j.opts.start)&&j.opts.start.call(a,j),n.fx.timer(n.extend(i,{elem:a,anim:j,queue:j.opts.queue})),j.progress(j.opts.progress).done(j.opts.done,j.opts.complete).fail(j.opts.fail).always(j.opts.always)}n.Animation=n.extend(lc,{tweener:function(a,b){n.isFunction(a)?(b=a,a=["*"]):a=a.split(" ");for(var c,d=0,e=a.length;e>d;d++)c=a[d],fc[c]=fc[c]||[],fc[c].unshift(b)},prefilter:function(a,b){b?ec.unshift(a):ec.push(a)}}),n.speed=function(a,b,c){var d=a&&"object"==typeof a?n.extend({},a):{complete:c||!c&&b||n.isFunction(a)&&a,duration:a,easing:c&&b||b&&!n.isFunction(b)&&b};return d.duration=n.fx.off?0:"number"==typeof d.duration?d.duration:d.duration in n.fx.speeds?n.fx.speeds[d.duration]:n.fx.speeds._default,(null==d.queue||d.queue===!0)&&(d.queue="fx"),d.old=d.complete,d.complete=function(){n.isFunction(d.old)&&d.old.call(this),d.queue&&n.dequeue(this,d.queue)},d},n.fn.extend({fadeTo:function(a,b,c,d){return this.filter(V).css("opacity",0).show().end().animate({opacity:b},a,c,d)},animate:function(a,b,c,d){var e=n.isEmptyObject(a),f=n.speed(b,c,d),g=function(){var b=lc(this,n.extend({},a),f);(e||n._data(this,"finish"))&&b.stop(!0)};return g.finish=g,e||f.queue===!1?this.each(g):this.queue(f.queue,g)},stop:function(a,b,c){var d=function(a){var b=a.stop;delete a.stop,b(c)};return"string"!=typeof a&&(c=b,b=a,a=void 0),b&&a!==!1&&this.queue(a||"fx",[]),this.each(function(){var b=!0,e=null!=a&&a+"queueHooks",f=n.timers,g=n._data(this);if(e)g[e]&&g[e].stop&&d(g[e]);else for(e in g)g[e]&&g[e].stop&&dc.test(e)&&d(g[e]);for(e=f.length;e--;)f[e].elem!==this||null!=a&&f[e].queue!==a||(f[e].anim.stop(c),b=!1,f.splice(e,1));(b||!c)&&n.dequeue(this,a)})},finish:function(a){return a!==!1&&(a=a||"fx"),this.each(function(){var b,c=n._data(this),d=c[a+"queue"],e=c[a+"queueHooks"],f=n.timers,g=d?d.length:0;for(c.finish=!0,n.queue(this,a,[]),e&&e.stop&&e.stop.call(this,!0),b=f.length;b--;)f[b].elem===this&&f[b].queue===a&&(f[b].anim.stop(!0),f.splice(b,1));for(b=0;g>b;b++)d[b]&&d[b].finish&&d[b].finish.call(this);delete c.finish})}}),n.each(["toggle","show","hide"],function(a,b){var c=n.fn[b];n.fn[b]=function(a,d,e){return null==a||"boolean"==typeof a?c.apply(this,arguments):this.animate(hc(b,!0),a,d,e)}}),n.each({slideDown:hc("show"),slideUp:hc("hide"),slideToggle:hc("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(a,b){n.fn[a]=function(a,c,d){return this.animate(b,a,c,d)}}),n.timers=[],n.fx.tick=function(){var a,b=n.timers,c=0;for(_b=n.now();c<b.length;c++)a=b[c],a()||b[c]!==a||b.splice(c--,1);b.length||n.fx.stop(),_b=void 0},n.fx.timer=function(a){n.timers.push(a),a()?n.fx.start():n.timers.pop()},n.fx.interval=13,n.fx.start=function(){ac||(ac=setInterval(n.fx.tick,n.fx.interval))},n.fx.stop=function(){clearInterval(ac),ac=null},n.fx.speeds={slow:600,fast:200,_default:400},n.fn.delay=function(a,b){return a=n.fx?n.fx.speeds[a]||a:a,b=b||"fx",this.queue(b,function(b,c){var d=setTimeout(b,a);c.stop=function(){clearTimeout(d)}})},function(){var a,b,c,d,e=z.createElement("div");e.setAttribute("className","t"),e.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",a=e.getElementsByTagName("a")[0],c=z.createElement("select"),d=c.appendChild(z.createElement("option")),b=e.getElementsByTagName("input")[0],a.style.cssText="top:1px",l.getSetAttribute="t"!==e.className,l.style=/top/.test(a.getAttribute("style")),l.hrefNormalized="/a"===a.getAttribute("href"),l.checkOn=!!b.value,l.optSelected=d.selected,l.enctype=!!z.createElement("form").enctype,c.disabled=!0,l.optDisabled=!d.disabled,b=z.createElement("input"),b.setAttribute("value",""),l.input=""===b.getAttribute("value"),b.value="t",b.setAttribute("type","radio"),l.radioValue="t"===b.value,a=b=c=d=e=null}();var mc=/\r/g;n.fn.extend({val:function(a){var b,c,d,e=this[0];{if(arguments.length)return d=n.isFunction(a),this.each(function(c){var e;1===this.nodeType&&(e=d?a.call(this,c,n(this).val()):a,null==e?e="":"number"==typeof e?e+="":n.isArray(e)&&(e=n.map(e,function(a){return null==a?"":a+""})),b=n.valHooks[this.type]||n.valHooks[this.nodeName.toLowerCase()],b&&"set"in b&&void 0!==b.set(this,e,"value")||(this.value=e))});if(e)return b=n.valHooks[e.type]||n.valHooks[e.nodeName.toLowerCase()],b&&"get"in b&&void 0!==(c=b.get(e,"value"))?c:(c=e.value,"string"==typeof c?c.replace(mc,""):null==c?"":c)}}}),n.extend({valHooks:{option:{get:function(a){var b=n.find.attr(a,"value");return null!=b?b:n.text(a)}},select:{get:function(a){for(var b,c,d=a.options,e=a.selectedIndex,f="select-one"===a.type||0>e,g=f?null:[],h=f?e+1:d.length,i=0>e?h:f?e:0;h>i;i++)if(c=d[i],!(!c.selected&&i!==e||(l.optDisabled?c.disabled:null!==c.getAttribute("disabled"))||c.parentNode.disabled&&n.nodeName(c.parentNode,"optgroup"))){if(b=n(c).val(),f)return b;g.push(b)}return g},set:function(a,b){var c,d,e=a.options,f=n.makeArray(b),g=e.length;while(g--)if(d=e[g],n.inArray(n.valHooks.option.get(d),f)>=0)try{d.selected=c=!0}catch(h){d.scrollHeight}else d.selected=!1;return c||(a.selectedIndex=-1),e}}}}),n.each(["radio","checkbox"],function(){n.valHooks[this]={set:function(a,b){return n.isArray(b)?a.checked=n.inArray(n(a).val(),b)>=0:void 0}},l.checkOn||(n.valHooks[this].get=function(a){return null===a.getAttribute("value")?"on":a.value})});var nc,oc,pc=n.expr.attrHandle,qc=/^(?:checked|selected)$/i,rc=l.getSetAttribute,sc=l.input;n.fn.extend({attr:function(a,b){return W(this,n.attr,a,b,arguments.length>1)},removeAttr:function(a){return this.each(function(){n.removeAttr(this,a)})}}),n.extend({attr:function(a,b,c){var d,e,f=a.nodeType;if(a&&3!==f&&8!==f&&2!==f)return typeof a.getAttribute===L?n.prop(a,b,c):(1===f&&n.isXMLDoc(a)||(b=b.toLowerCase(),d=n.attrHooks[b]||(n.expr.match.bool.test(b)?oc:nc)),void 0===c?d&&"get"in d&&null!==(e=d.get(a,b))?e:(e=n.find.attr(a,b),null==e?void 0:e):null!==c?d&&"set"in d&&void 0!==(e=d.set(a,c,b))?e:(a.setAttribute(b,c+""),c):void n.removeAttr(a,b))},removeAttr:function(a,b){var c,d,e=0,f=b&&b.match(F);if(f&&1===a.nodeType)while(c=f[e++])d=n.propFix[c]||c,n.expr.match.bool.test(c)?sc&&rc||!qc.test(c)?a[d]=!1:a[n.camelCase("default-"+c)]=a[d]=!1:n.attr(a,c,""),a.removeAttribute(rc?c:d)},attrHooks:{type:{set:function(a,b){if(!l.radioValue&&"radio"===b&&n.nodeName(a,"input")){var c=a.value;return a.setAttribute("type",b),c&&(a.value=c),b}}}}}),oc={set:function(a,b,c){return b===!1?n.removeAttr(a,c):sc&&rc||!qc.test(c)?a.setAttribute(!rc&&n.propFix[c]||c,c):a[n.camelCase("default-"+c)]=a[c]=!0,c}},n.each(n.expr.match.bool.source.match(/\w+/g),function(a,b){var c=pc[b]||n.find.attr;pc[b]=sc&&rc||!qc.test(b)?function(a,b,d){var e,f;return d||(f=pc[b],pc[b]=e,e=null!=c(a,b,d)?b.toLowerCase():null,pc[b]=f),e}:function(a,b,c){return c?void 0:a[n.camelCase("default-"+b)]?b.toLowerCase():null}}),sc&&rc||(n.attrHooks.value={set:function(a,b,c){return n.nodeName(a,"input")?void(a.defaultValue=b):nc&&nc.set(a,b,c)}}),rc||(nc={set:function(a,b,c){var d=a.getAttributeNode(c);return d||a.setAttributeNode(d=a.ownerDocument.createAttribute(c)),d.value=b+="","value"===c||b===a.getAttribute(c)?b:void 0}},pc.id=pc.name=pc.coords=function(a,b,c){var d;return c?void 0:(d=a.getAttributeNode(b))&&""!==d.value?d.value:null},n.valHooks.button={get:function(a,b){var c=a.getAttributeNode(b);return c&&c.specified?c.value:void 0},set:nc.set},n.attrHooks.contenteditable={set:function(a,b,c){nc.set(a,""===b?!1:b,c)}},n.each(["width","height"],function(a,b){n.attrHooks[b]={set:function(a,c){return""===c?(a.setAttribute(b,"auto"),c):void 0}}})),l.style||(n.attrHooks.style={get:function(a){return a.style.cssText||void 0},set:function(a,b){return a.style.cssText=b+""}});var tc=/^(?:input|select|textarea|button|object)$/i,uc=/^(?:a|area)$/i;n.fn.extend({prop:function(a,b){return W(this,n.prop,a,b,arguments.length>1)},removeProp:function(a){return a=n.propFix[a]||a,this.each(function(){try{this[a]=void 0,delete this[a]}catch(b){}})}}),n.extend({propFix:{"for":"htmlFor","class":"className"},prop:function(a,b,c){var d,e,f,g=a.nodeType;if(a&&3!==g&&8!==g&&2!==g)return f=1!==g||!n.isXMLDoc(a),f&&(b=n.propFix[b]||b,e=n.propHooks[b]),void 0!==c?e&&"set"in e&&void 0!==(d=e.set(a,c,b))?d:a[b]=c:e&&"get"in e&&null!==(d=e.get(a,b))?d:a[b]},propHooks:{tabIndex:{get:function(a){var b=n.find.attr(a,"tabindex");return b?parseInt(b,10):tc.test(a.nodeName)||uc.test(a.nodeName)&&a.href?0:-1}}}}),l.hrefNormalized||n.each(["href","src"],function(a,b){n.propHooks[b]={get:function(a){return a.getAttribute(b,4)}}}),l.optSelected||(n.propHooks.selected={get:function(a){var b=a.parentNode;return b&&(b.selectedIndex,b.parentNode&&b.parentNode.selectedIndex),null}}),n.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){n.propFix[this.toLowerCase()]=this}),l.enctype||(n.propFix.enctype="encoding");var vc=/[\t\r\n\f]/g;n.fn.extend({addClass:function(a){var b,c,d,e,f,g,h=0,i=this.length,j="string"==typeof a&&a;if(n.isFunction(a))return this.each(function(b){n(this).addClass(a.call(this,b,this.className))});if(j)for(b=(a||"").match(F)||[];i>h;h++)if(c=this[h],d=1===c.nodeType&&(c.className?(" "+c.className+" ").replace(vc," "):" ")){f=0;while(e=b[f++])d.indexOf(" "+e+" ")<0&&(d+=e+" ");g=n.trim(d),c.className!==g&&(c.className=g)}return this},removeClass:function(a){var b,c,d,e,f,g,h=0,i=this.length,j=0===arguments.length||"string"==typeof a&&a;if(n.isFunction(a))return this.each(function(b){n(this).removeClass(a.call(this,b,this.className))});if(j)for(b=(a||"").match(F)||[];i>h;h++)if(c=this[h],d=1===c.nodeType&&(c.className?(" "+c.className+" ").replace(vc," "):"")){f=0;while(e=b[f++])while(d.indexOf(" "+e+" ")>=0)d=d.replace(" "+e+" "," ");g=a?n.trim(d):"",c.className!==g&&(c.className=g)}return this},toggleClass:function(a,b){var c=typeof a;return"boolean"==typeof b&&"string"===c?b?this.addClass(a):this.removeClass(a):this.each(n.isFunction(a)?function(c){n(this).toggleClass(a.call(this,c,this.className,b),b)}:function(){if("string"===c){var b,d=0,e=n(this),f=a.match(F)||[];while(b=f[d++])e.hasClass(b)?e.removeClass(b):e.addClass(b)}else(c===L||"boolean"===c)&&(this.className&&n._data(this,"__className__",this.className),this.className=this.className||a===!1?"":n._data(this,"__className__")||"")})},hasClass:function(a){for(var b=" "+a+" ",c=0,d=this.length;d>c;c++)if(1===this[c].nodeType&&(" "+this[c].className+" ").replace(vc," ").indexOf(b)>=0)return!0;return!1}}),n.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(a,b){n.fn[b]=function(a,c){return arguments.length>0?this.on(b,null,a,c):this.trigger(b)}}),n.fn.extend({hover:function(a,b){return this.mouseenter(a).mouseleave(b||a)},bind:function(a,b,c){return this.on(a,null,b,c)},unbind:function(a,b){return this.off(a,null,b)},delegate:function(a,b,c,d){return this.on(b,a,c,d)},undelegate:function(a,b,c){return 1===arguments.length?this.off(a,"**"):this.off(b,a||"**",c)}});var wc=n.now(),xc=/\?/,yc=/(,)|(\[|{)|(}|])|"(?:[^"\\\r\n]|\\["\\\/bfnrt]|\\u[\da-fA-F]{4})*"\s*:?|true|false|null|-?(?!0\d)\d+(?:\.\d+|)(?:[eE][+-]?\d+|)/g;n.parseJSON=function(b){if(a.JSON&&a.JSON.parse)return a.JSON.parse(b+"");var c,d=null,e=n.trim(b+"");return e&&!n.trim(e.replace(yc,function(a,b,e,f){return c&&b&&(d=0),0===d?a:(c=e||b,d+=!f-!e,"")}))?Function("return "+e)():n.error("Invalid JSON: "+b)},n.parseXML=function(b){var c,d;if(!b||"string"!=typeof b)return null;try{a.DOMParser?(d=new DOMParser,c=d.parseFromString(b,"text/xml")):(c=new ActiveXObject("Microsoft.XMLDOM"),c.async="false",c.loadXML(b))}catch(e){c=void 0}return c&&c.documentElement&&!c.getElementsByTagName("parsererror").length||n.error("Invalid XML: "+b),c};var zc,Ac,Bc=/#.*$/,Cc=/([?&])_=[^&]*/,Dc=/^(.*?):[ \t]*([^\r\n]*)\r?$/gm,Ec=/^(?:about|app|app-storage|.+-extension|file|res|widget):$/,Fc=/^(?:GET|HEAD)$/,Gc=/^\/\//,Hc=/^([\w.+-]+:)(?:\/\/(?:[^\/?#]*@|)([^\/?#:]*)(?::(\d+)|)|)/,Ic={},Jc={},Kc="*/".concat("*");try{Ac=location.href}catch(Lc){Ac=z.createElement("a"),Ac.href="",Ac=Ac.href}zc=Hc.exec(Ac.toLowerCase())||[];function Mc(a){return function(b,c){"string"!=typeof b&&(c=b,b="*");var d,e=0,f=b.toLowerCase().match(F)||[];if(n.isFunction(c))while(d=f[e++])"+"===d.charAt(0)?(d=d.slice(1)||"*",(a[d]=a[d]||[]).unshift(c)):(a[d]=a[d]||[]).push(c)}}function Nc(a,b,c,d){var e={},f=a===Jc;function g(h){var i;return e[h]=!0,n.each(a[h]||[],function(a,h){var j=h(b,c,d);return"string"!=typeof j||f||e[j]?f?!(i=j):void 0:(b.dataTypes.unshift(j),g(j),!1)}),i}return g(b.dataTypes[0])||!e["*"]&&g("*")}function Oc(a,b){var c,d,e=n.ajaxSettings.flatOptions||{};for(d in b)void 0!==b[d]&&((e[d]?a:c||(c={}))[d]=b[d]);return c&&n.extend(!0,a,c),a}function Pc(a,b,c){var d,e,f,g,h=a.contents,i=a.dataTypes;while("*"===i[0])i.shift(),void 0===e&&(e=a.mimeType||b.getResponseHeader("Content-Type"));if(e)for(g in h)if(h[g]&&h[g].test(e)){i.unshift(g);break}if(i[0]in c)f=i[0];else{for(g in c){if(!i[0]||a.converters[g+" "+i[0]]){f=g;break}d||(d=g)}f=f||d}return f?(f!==i[0]&&i.unshift(f),c[f]):void 0}function Qc(a,b,c,d){var e,f,g,h,i,j={},k=a.dataTypes.slice();if(k[1])for(g in a.converters)j[g.toLowerCase()]=a.converters[g];f=k.shift();while(f)if(a.responseFields[f]&&(c[a.responseFields[f]]=b),!i&&d&&a.dataFilter&&(b=a.dataFilter(b,a.dataType)),i=f,f=k.shift())if("*"===f)f=i;else if("*"!==i&&i!==f){if(g=j[i+" "+f]||j["* "+f],!g)for(e in j)if(h=e.split(" "),h[1]===f&&(g=j[i+" "+h[0]]||j["* "+h[0]])){g===!0?g=j[e]:j[e]!==!0&&(f=h[0],k.unshift(h[1]));break}if(g!==!0)if(g&&a["throws"])b=g(b);else try{b=g(b)}catch(l){return{state:"parsererror",error:g?l:"No conversion from "+i+" to "+f}}}return{state:"success",data:b}}n.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:Ac,type:"GET",isLocal:Ec.test(zc[1]),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":Kc,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":n.parseJSON,"text xml":n.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(a,b){return b?Oc(Oc(a,n.ajaxSettings),b):Oc(n.ajaxSettings,a)},ajaxPrefilter:Mc(Ic),ajaxTransport:Mc(Jc),ajax:function(a,b){"object"==typeof a&&(b=a,a=void 0),b=b||{};var c,d,e,f,g,h,i,j,k=n.ajaxSetup({},b),l=k.context||k,m=k.context&&(l.nodeType||l.jquery)?n(l):n.event,o=n.Deferred(),p=n.Callbacks("once memory"),q=k.statusCode||{},r={},s={},t=0,u="canceled",v={readyState:0,getResponseHeader:function(a){var b;if(2===t){if(!j){j={};while(b=Dc.exec(f))j[b[1].toLowerCase()]=b[2]}b=j[a.toLowerCase()]}return null==b?null:b},getAllResponseHeaders:function(){return 2===t?f:null},setRequestHeader:function(a,b){var c=a.toLowerCase();return t||(a=s[c]=s[c]||a,r[a]=b),this},overrideMimeType:function(a){return t||(k.mimeType=a),this},statusCode:function(a){var b;if(a)if(2>t)for(b in a)q[b]=[q[b],a[b]];else v.always(a[v.status]);return this},abort:function(a){var b=a||u;return i&&i.abort(b),x(0,b),this}};if(o.promise(v).complete=p.add,v.success=v.done,v.error=v.fail,k.url=((a||k.url||Ac)+"").replace(Bc,"").replace(Gc,zc[1]+"//"),k.type=b.method||b.type||k.method||k.type,k.dataTypes=n.trim(k.dataType||"*").toLowerCase().match(F)||[""],null==k.crossDomain&&(c=Hc.exec(k.url.toLowerCase()),k.crossDomain=!(!c||c[1]===zc[1]&&c[2]===zc[2]&&(c[3]||("http:"===c[1]?"80":"443"))===(zc[3]||("http:"===zc[1]?"80":"443")))),k.data&&k.processData&&"string"!=typeof k.data&&(k.data=n.param(k.data,k.traditional)),Nc(Ic,k,b,v),2===t)return v;h=k.global,h&&0===n.active++&&n.event.trigger("ajaxStart"),k.type=k.type.toUpperCase(),k.hasContent=!Fc.test(k.type),e=k.url,k.hasContent||(k.data&&(e=k.url+=(xc.test(e)?"&":"?")+k.data,delete k.data),k.cache===!1&&(k.url=Cc.test(e)?e.replace(Cc,"$1_="+wc++):e+(xc.test(e)?"&":"?")+"_="+wc++)),k.ifModified&&(n.lastModified[e]&&v.setRequestHeader("If-Modified-Since",n.lastModified[e]),n.etag[e]&&v.setRequestHeader("If-None-Match",n.etag[e])),(k.data&&k.hasContent&&k.contentType!==!1||b.contentType)&&v.setRequestHeader("Content-Type",k.contentType),v.setRequestHeader("Accept",k.dataTypes[0]&&k.accepts[k.dataTypes[0]]?k.accepts[k.dataTypes[0]]+("*"!==k.dataTypes[0]?", "+Kc+"; q=0.01":""):k.accepts["*"]);for(d in k.headers)v.setRequestHeader(d,k.headers[d]);if(k.beforeSend&&(k.beforeSend.call(l,v,k)===!1||2===t))return v.abort();u="abort";for(d in{success:1,error:1,complete:1})v[d](k[d]);if(i=Nc(Jc,k,b,v)){v.readyState=1,h&&m.trigger("ajaxSend",[v,k]),k.async&&k.timeout>0&&(g=setTimeout(function(){v.abort("timeout")},k.timeout));try{t=1,i.send(r,x)}catch(w){if(!(2>t))throw w;x(-1,w)}}else x(-1,"No Transport");function x(a,b,c,d){var j,r,s,u,w,x=b;2!==t&&(t=2,g&&clearTimeout(g),i=void 0,f=d||"",v.readyState=a>0?4:0,j=a>=200&&300>a||304===a,c&&(u=Pc(k,v,c)),u=Qc(k,u,v,j),j?(k.ifModified&&(w=v.getResponseHeader("Last-Modified"),w&&(n.lastModified[e]=w),w=v.getResponseHeader("etag"),w&&(n.etag[e]=w)),204===a||"HEAD"===k.type?x="nocontent":304===a?x="notmodified":(x=u.state,r=u.data,s=u.error,j=!s)):(s=x,(a||!x)&&(x="error",0>a&&(a=0))),v.status=a,v.statusText=(b||x)+"",j?o.resolveWith(l,[r,x,v]):o.rejectWith(l,[v,x,s]),v.statusCode(q),q=void 0,h&&m.trigger(j?"ajaxSuccess":"ajaxError",[v,k,j?r:s]),p.fireWith(l,[v,x]),h&&(m.trigger("ajaxComplete",[v,k]),--n.active||n.event.trigger("ajaxStop")))}return v},getJSON:function(a,b,c){return n.get(a,b,c,"json")},getScript:function(a,b){return n.get(a,void 0,b,"script")}}),n.each(["get","post"],function(a,b){n[b]=function(a,c,d,e){return n.isFunction(c)&&(e=e||d,d=c,c=void 0),n.ajax({url:a,type:b,dataType:e,data:c,success:d})}}),n.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(a,b){n.fn[b]=function(a){return this.on(b,a)}}),n._evalUrl=function(a){return n.ajax({url:a,type:"GET",dataType:"script",async:!1,global:!1,"throws":!0})},n.fn.extend({wrapAll:function(a){if(n.isFunction(a))return this.each(function(b){n(this).wrapAll(a.call(this,b))});if(this[0]){var b=n(a,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&b.insertBefore(this[0]),b.map(function(){var a=this;while(a.firstChild&&1===a.firstChild.nodeType)a=a.firstChild;return a}).append(this)}return this},wrapInner:function(a){return this.each(n.isFunction(a)?function(b){n(this).wrapInner(a.call(this,b))}:function(){var b=n(this),c=b.contents();c.length?c.wrapAll(a):b.append(a)})},wrap:function(a){var b=n.isFunction(a);return this.each(function(c){n(this).wrapAll(b?a.call(this,c):a)})},unwrap:function(){return this.parent().each(function(){n.nodeName(this,"body")||n(this).replaceWith(this.childNodes)}).end()}}),n.expr.filters.hidden=function(a){return a.offsetWidth<=0&&a.offsetHeight<=0||!l.reliableHiddenOffsets()&&"none"===(a.style&&a.style.display||n.css(a,"display"))},n.expr.filters.visible=function(a){return!n.expr.filters.hidden(a)};var Rc=/%20/g,Sc=/\[\]$/,Tc=/\r?\n/g,Uc=/^(?:submit|button|image|reset|file)$/i,Vc=/^(?:input|select|textarea|keygen)/i;function Wc(a,b,c,d){var e;if(n.isArray(b))n.each(b,function(b,e){c||Sc.test(a)?d(a,e):Wc(a+"["+("object"==typeof e?b:"")+"]",e,c,d)});else if(c||"object"!==n.type(b))d(a,b);else for(e in b)Wc(a+"["+e+"]",b[e],c,d)}n.param=function(a,b){var c,d=[],e=function(a,b){b=n.isFunction(b)?b():null==b?"":b,d[d.length]=encodeURIComponent(a)+"="+encodeURIComponent(b)};if(void 0===b&&(b=n.ajaxSettings&&n.ajaxSettings.traditional),n.isArray(a)||a.jquery&&!n.isPlainObject(a))n.each(a,function(){e(this.name,this.value)});else for(c in a)Wc(c,a[c],b,e);return d.join("&").replace(Rc,"+")},n.fn.extend({serialize:function(){return n.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var a=n.prop(this,"elements");return a?n.makeArray(a):this}).filter(function(){var a=this.type;return this.name&&!n(this).is(":disabled")&&Vc.test(this.nodeName)&&!Uc.test(a)&&(this.checked||!X.test(a))}).map(function(a,b){var c=n(this).val();return null==c?null:n.isArray(c)?n.map(c,function(a){return{name:b.name,value:a.replace(Tc,"\r\n")}}):{name:b.name,value:c.replace(Tc,"\r\n")}}).get()}}),n.ajaxSettings.xhr=void 0!==a.ActiveXObject?function(){return!this.isLocal&&/^(get|post|head|put|delete|options)$/i.test(this.type)&&$c()||_c()}:$c;var Xc=0,Yc={},Zc=n.ajaxSettings.xhr();a.ActiveXObject&&n(a).on("unload",function(){for(var a in Yc)Yc[a](void 0,!0)}),l.cors=!!Zc&&"withCredentials"in Zc,Zc=l.ajax=!!Zc,Zc&&n.ajaxTransport(function(a){if(!a.crossDomain||l.cors){var b;return{send:function(c,d){var e,f=a.xhr(),g=++Xc;if(f.open(a.type,a.url,a.async,a.username,a.password),a.xhrFields)for(e in a.xhrFields)f[e]=a.xhrFields[e];a.mimeType&&f.overrideMimeType&&f.overrideMimeType(a.mimeType),a.crossDomain||c["X-Requested-With"]||(c["X-Requested-With"]="XMLHttpRequest");for(e in c)void 0!==c[e]&&f.setRequestHeader(e,c[e]+"");f.send(a.hasContent&&a.data||null),b=function(c,e){var h,i,j;if(b&&(e||4===f.readyState))if(delete Yc[g],b=void 0,f.onreadystatechange=n.noop,e)4!==f.readyState&&f.abort();else{j={},h=f.status,"string"==typeof f.responseText&&(j.text=f.responseText);try{i=f.statusText}catch(k){i=""}h||!a.isLocal||a.crossDomain?1223===h&&(h=204):h=j.text?200:404}j&&d(h,i,j,f.getAllResponseHeaders())},a.async?4===f.readyState?setTimeout(b):f.onreadystatechange=Yc[g]=b:b()},abort:function(){b&&b(void 0,!0)}}}});function $c(){try{return new a.XMLHttpRequest}catch(b){}}function _c(){try{return new a.ActiveXObject("Microsoft.XMLHTTP")}catch(b){}}n.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/(?:java|ecma)script/},converters:{"text script":function(a){return n.globalEval(a),a}}}),n.ajaxPrefilter("script",function(a){void 0===a.cache&&(a.cache=!1),a.crossDomain&&(a.type="GET",a.global=!1)}),n.ajaxTransport("script",function(a){if(a.crossDomain){var b,c=z.head||n("head")[0]||z.documentElement;return{send:function(d,e){b=z.createElement("script"),b.async=!0,a.scriptCharset&&(b.charset=a.scriptCharset),b.src=a.url,b.onload=b.onreadystatechange=function(a,c){(c||!b.readyState||/loaded|complete/.test(b.readyState))&&(b.onload=b.onreadystatechange=null,b.parentNode&&b.parentNode.removeChild(b),b=null,c||e(200,"success"))},c.insertBefore(b,c.firstChild)},abort:function(){b&&b.onload(void 0,!0)}}}});var ad=[],bd=/(=)\?(?=&|$)|\?\?/;n.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var a=ad.pop()||n.expando+"_"+wc++;return this[a]=!0,a}}),n.ajaxPrefilter("json jsonp",function(b,c,d){var e,f,g,h=b.jsonp!==!1&&(bd.test(b.url)?"url":"string"==typeof b.data&&!(b.contentType||"").indexOf("application/x-www-form-urlencoded")&&bd.test(b.data)&&"data");return h||"jsonp"===b.dataTypes[0]?(e=b.jsonpCallback=n.isFunction(b.jsonpCallback)?b.jsonpCallback():b.jsonpCallback,h?b[h]=b[h].replace(bd,"$1"+e):b.jsonp!==!1&&(b.url+=(xc.test(b.url)?"&":"?")+b.jsonp+"="+e),b.converters["script json"]=function(){return g||n.error(e+" was not called"),g[0]},b.dataTypes[0]="json",f=a[e],a[e]=function(){g=arguments},d.always(function(){a[e]=f,b[e]&&(b.jsonpCallback=c.jsonpCallback,ad.push(e)),g&&n.isFunction(f)&&f(g[0]),g=f=void 0}),"script"):void 0}),n.parseHTML=function(a,b,c){if(!a||"string"!=typeof a)return null;"boolean"==typeof b&&(c=b,b=!1),b=b||z;var d=v.exec(a),e=!c&&[];return d?[b.createElement(d[1])]:(d=n.buildFragment([a],b,e),e&&e.length&&n(e).remove(),n.merge([],d.childNodes))};var cd=n.fn.load;n.fn.load=function(a,b,c){if("string"!=typeof a&&cd)return cd.apply(this,arguments);var d,e,f,g=this,h=a.indexOf(" ");return h>=0&&(d=a.slice(h,a.length),a=a.slice(0,h)),n.isFunction(b)?(c=b,b=void 0):b&&"object"==typeof b&&(f="POST"),g.length>0&&n.ajax({url:a,type:f,dataType:"html",data:b}).done(function(a){e=arguments,g.html(d?n("<div>").append(n.parseHTML(a)).find(d):a)}).complete(c&&function(a,b){g.each(c,e||[a.responseText,b,a])}),this},n.expr.filters.animated=function(a){return n.grep(n.timers,function(b){return a===b.elem}).length};var dd=a.document.documentElement;function ed(a){return n.isWindow(a)?a:9===a.nodeType?a.defaultView||a.parentWindow:!1}n.offset={setOffset:function(a,b,c){var d,e,f,g,h,i,j,k=n.css(a,"position"),l=n(a),m={};"static"===k&&(a.style.position="relative"),h=l.offset(),f=n.css(a,"top"),i=n.css(a,"left"),j=("absolute"===k||"fixed"===k)&&n.inArray("auto",[f,i])>-1,j?(d=l.position(),g=d.top,e=d.left):(g=parseFloat(f)||0,e=parseFloat(i)||0),n.isFunction(b)&&(b=b.call(a,c,h)),null!=b.top&&(m.top=b.top-h.top+g),null!=b.left&&(m.left=b.left-h.left+e),"using"in b?b.using.call(a,m):l.css(m)}},n.fn.extend({offset:function(a){if(arguments.length)return void 0===a?this:this.each(function(b){n.offset.setOffset(this,a,b)});var b,c,d={top:0,left:0},e=this[0],f=e&&e.ownerDocument;if(f)return b=f.documentElement,n.contains(b,e)?(typeof e.getBoundingClientRect!==L&&(d=e.getBoundingClientRect()),c=ed(f),{top:d.top+(c.pageYOffset||b.scrollTop)-(b.clientTop||0),left:d.left+(c.pageXOffset||b.scrollLeft)-(b.clientLeft||0)}):d},position:function(){if(this[0]){var a,b,c={top:0,left:0},d=this[0];return"fixed"===n.css(d,"position")?b=d.getBoundingClientRect():(a=this.offsetParent(),b=this.offset(),n.nodeName(a[0],"html")||(c=a.offset()),c.top+=n.css(a[0],"borderTopWidth",!0),c.left+=n.css(a[0],"borderLeftWidth",!0)),{top:b.top-c.top-n.css(d,"marginTop",!0),left:b.left-c.left-n.css(d,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var a=this.offsetParent||dd;while(a&&!n.nodeName(a,"html")&&"static"===n.css(a,"position"))a=a.offsetParent;return a||dd})}}),n.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(a,b){var c=/Y/.test(b);n.fn[a]=function(d){return W(this,function(a,d,e){var f=ed(a);return void 0===e?f?b in f?f[b]:f.document.documentElement[d]:a[d]:void(f?f.scrollTo(c?n(f).scrollLeft():e,c?e:n(f).scrollTop()):a[d]=e)},a,d,arguments.length,null)}}),n.each(["top","left"],function(a,b){n.cssHooks[b]=Mb(l.pixelPosition,function(a,c){return c?(c=Kb(a,b),Ib.test(c)?n(a).position()[b]+"px":c):void 0})}),n.each({Height:"height",Width:"width"},function(a,b){n.each({padding:"inner"+a,content:b,"":"outer"+a},function(c,d){n.fn[d]=function(d,e){var f=arguments.length&&(c||"boolean"!=typeof d),g=c||(d===!0||e===!0?"margin":"border");return W(this,function(b,c,d){var e;return n.isWindow(b)?b.document.documentElement["client"+a]:9===b.nodeType?(e=b.documentElement,Math.max(b.body["scroll"+a],e["scroll"+a],b.body["offset"+a],e["offset"+a],e["client"+a])):void 0===d?n.css(b,c,g):n.style(b,c,d,g)},b,f?d:void 0,f,null)}})}),n.fn.size=function(){return this.length},n.fn.andSelf=n.fn.addBack,"function"==typeof define&&define.amd&&define("jquery",[],function(){return n});var fd=a.jQuery,gd=a.$;return n.noConflict=function(b){return a.$===n&&(a.$=gd),b&&a.jQuery===n&&(a.jQuery=fd),n},typeof b===L&&(a.jQuery=a.$=n),n});

(function($,undefined){var uuid=0,runiqueId=/^ui-id-\d+$/;$.ui=$.ui||{};$.extend($.ui,{version:"1.10.4",keyCode:{BACKSPACE:8,COMMA:188,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,LEFT:37,NUMPAD_ADD:107,NUMPAD_DECIMAL:110,NUMPAD_DIVIDE:111,NUMPAD_ENTER:108,NUMPAD_MULTIPLY:106,NUMPAD_SUBTRACT:109,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SPACE:32,TAB:9,UP:38}});$.fn.extend({focus:function(orig){return function(delay,fn){return typeof delay==="number"?this.each(function(){var elem=this;setTimeout(function(){$(elem).focus();if(fn){fn.call(elem)}},delay)}):orig.apply(this,arguments)}}($.fn.focus),scrollParent:function(){var scrollParent;if($.ui.ie&&/(static|relative)/.test(this.css("position"))||/absolute/.test(this.css("position"))){scrollParent=this.parents().filter(function(){return/(relative|absolute|fixed)/.test($.css(this,"position"))&&/(auto|scroll)/.test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"))}).eq(0)}else{scrollParent=this.parents().filter(function(){return/(auto|scroll)/.test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"))}).eq(0)}return/fixed/.test(this.css("position"))||!scrollParent.length?$(document):scrollParent},zIndex:function(zIndex){if(zIndex!==undefined){return this.css("zIndex",zIndex)}if(this.length){var elem=$(this[0]),position,value;while(elem.length&&elem[0]!==document){position=elem.css("position");if(position==="absolute"||position==="relative"||position==="fixed"){value=parseInt(elem.css("zIndex"),10);if(!isNaN(value)&&value!==0){return value}}elem=elem.parent()}}return 0},uniqueId:function(){return this.each(function(){if(!this.id){this.id="ui-id-"+ ++uuid}})},removeUniqueId:function(){return this.each(function(){if(runiqueId.test(this.id)){$(this).removeAttr("id")}})}});function focusable(element,isTabIndexNotNaN){var map,mapName,img,nodeName=element.nodeName.toLowerCase();if("area"===nodeName){map=element.parentNode;mapName=map.name;if(!element.href||!mapName||map.nodeName.toLowerCase()!=="map"){return false}img=$("img[usemap=#"+mapName+"]")[0];return!!img&&visible(img)}return(/input|select|textarea|button|object/.test(nodeName)?!element.disabled:"a"===nodeName?element.href||isTabIndexNotNaN:isTabIndexNotNaN)&&visible(element)}function visible(element){return $.expr.filters.visible(element)&&!$(element).parents().addBack().filter(function(){return $.css(this,"visibility")==="hidden"}).length}$.extend($.expr[":"],{data:$.expr.createPseudo?$.expr.createPseudo(function(dataName){return function(elem){return!!$.data(elem,dataName)}}):function(elem,i,match){return!!$.data(elem,match[3])},focusable:function(element){return focusable(element,!isNaN($.attr(element,"tabindex")))},tabbable:function(element){var tabIndex=$.attr(element,"tabindex"),isTabIndexNaN=isNaN(tabIndex);return(isTabIndexNaN||tabIndex>=0)&&focusable(element,!isTabIndexNaN)}});if(!$("<a>").outerWidth(1).jquery){$.each(["Width","Height"],function(i,name){var side=name==="Width"?["Left","Right"]:["Top","Bottom"],type=name.toLowerCase(),orig={innerWidth:$.fn.innerWidth,innerHeight:$.fn.innerHeight,outerWidth:$.fn.outerWidth,outerHeight:$.fn.outerHeight};function reduce(elem,size,border,margin){$.each(side,function(){size-=parseFloat($.css(elem,"padding"+this))||0;if(border){size-=parseFloat($.css(elem,"border"+this+"Width"))||0}if(margin){size-=parseFloat($.css(elem,"margin"+this))||0}});return size}$.fn["inner"+name]=function(size){if(size===undefined){return orig["inner"+name].call(this)}return this.each(function(){$(this).css(type,reduce(this,size)+"px")})};$.fn["outer"+name]=function(size,margin){if(typeof size!=="number"){return orig["outer"+name].call(this,size)}return this.each(function(){$(this).css(type,reduce(this,size,true,margin)+"px")})}})}if(!$.fn.addBack){$.fn.addBack=function(selector){return this.add(selector==null?this.prevObject:this.prevObject.filter(selector))}}if($("<a>").data("a-b","a").removeData("a-b").data("a-b")){$.fn.removeData=function(removeData){return function(key){if(arguments.length){return removeData.call(this,$.camelCase(key))}else{return removeData.call(this)}}}($.fn.removeData)}$.ui.ie=!!/msie [\w.]+/.exec(navigator.userAgent.toLowerCase());$.support.selectstart="onselectstart"in document.createElement("div");$.fn.extend({disableSelection:function(){return this.bind(($.support.selectstart?"selectstart":"mousedown")+".ui-disableSelection",function(event){event.preventDefault()})},enableSelection:function(){return this.unbind(".ui-disableSelection")}});$.extend($.ui,{plugin:{add:function(module,option,set){var i,proto=$.ui[module].prototype;for(i in set){proto.plugins[i]=proto.plugins[i]||[];proto.plugins[i].push([option,set[i]])}},call:function(instance,name,args){var i,set=instance.plugins[name];if(!set||!instance.element[0].parentNode||instance.element[0].parentNode.nodeType===11){return}for(i=0;i<set.length;i++){if(instance.options[set[i][0]]){set[i][1].apply(instance.element,args)}}}},hasScroll:function(el,a){if($(el).css("overflow")==="hidden"){return false}var scroll=a&&a==="left"?"scrollLeft":"scrollTop",has=false;if(el[scroll]>0){return true}el[scroll]=1;has=el[scroll]>0;el[scroll]=0;return has}})})(jQuery);(function($,undefined){var uuid=0,slice=Array.prototype.slice,_cleanData=$.cleanData;$.cleanData=function(elems){for(var i=0,elem;(elem=elems[i])!=null;i++){try{$(elem).triggerHandler("remove")}catch(e){}}_cleanData(elems)};$.widget=function(name,base,prototype){var fullName,existingConstructor,constructor,basePrototype,proxiedPrototype={},namespace=name.split(".")[0];name=name.split(".")[1];fullName=namespace+"-"+name;if(!prototype){prototype=base;base=$.Widget}$.expr[":"][fullName.toLowerCase()]=function(elem){return!!$.data(elem,fullName)};$[namespace]=$[namespace]||{};existingConstructor=$[namespace][name];constructor=$[namespace][name]=function(options,element){if(!this._createWidget){return new constructor(options,element)}if(arguments.length){this._createWidget(options,element)}};$.extend(constructor,existingConstructor,{version:prototype.version,_proto:$.extend({},prototype),_childConstructors:[]});basePrototype=new base;basePrototype.options=$.widget.extend({},basePrototype.options);$.each(prototype,function(prop,value){if(!$.isFunction(value)){proxiedPrototype[prop]=value;return}proxiedPrototype[prop]=function(){var _super=function(){return base.prototype[prop].apply(this,arguments)},_superApply=function(args){return base.prototype[prop].apply(this,args)};return function(){var __super=this._super,__superApply=this._superApply,returnValue;this._super=_super;this._superApply=_superApply;returnValue=value.apply(this,arguments);this._super=__super;this._superApply=__superApply;return returnValue}}()});constructor.prototype=$.widget.extend(basePrototype,{widgetEventPrefix:existingConstructor?basePrototype.widgetEventPrefix||name:name},proxiedPrototype,{constructor:constructor,namespace:namespace,widgetName:name,widgetFullName:fullName});if(existingConstructor){$.each(existingConstructor._childConstructors,function(i,child){var childPrototype=child.prototype;$.widget(childPrototype.namespace+"."+childPrototype.widgetName,constructor,child._proto)});delete existingConstructor._childConstructors}else{base._childConstructors.push(constructor)}$.widget.bridge(name,constructor)};$.widget.extend=function(target){var input=slice.call(arguments,1),inputIndex=0,inputLength=input.length,key,value;for(;inputIndex<inputLength;inputIndex++){for(key in input[inputIndex]){value=input[inputIndex][key];if(input[inputIndex].hasOwnProperty(key)&&value!==undefined){if($.isPlainObject(value)){target[key]=$.isPlainObject(target[key])?$.widget.extend({},target[key],value):$.widget.extend({},value)}else{target[key]=value}}}}return target};$.widget.bridge=function(name,object){var fullName=object.prototype.widgetFullName||name;$.fn[name]=function(options){var isMethodCall=typeof options==="string",args=slice.call(arguments,1),returnValue=this;options=!isMethodCall&&args.length?$.widget.extend.apply(null,[options].concat(args)):options;if(isMethodCall){this.each(function(){var methodValue,instance=$.data(this,fullName);if(!instance){return $.error("cannot call methods on "+name+" prior to initialization; "+"attempted to call method '"+options+"'")}if(!$.isFunction(instance[options])||options.charAt(0)==="_"){return $.error("no such method '"+options+"' for "+name+" widget instance")}methodValue=instance[options].apply(instance,args);if(methodValue!==instance&&methodValue!==undefined){returnValue=methodValue&&methodValue.jquery?returnValue.pushStack(methodValue.get()):methodValue;return false}})}else{this.each(function(){var instance=$.data(this,fullName);if(instance){instance.option(options||{})._init()}else{$.data(this,fullName,new object(options,this))}})}return returnValue}};$.Widget=function(){};$.Widget._childConstructors=[];$.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",defaultElement:"<div>",options:{disabled:false,create:null},_createWidget:function(options,element){element=$(element||this.defaultElement||this)[0];this.element=$(element);this.uuid=uuid++;this.eventNamespace="."+this.widgetName+this.uuid;this.options=$.widget.extend({},this.options,this._getCreateOptions(),options);this.bindings=$();this.hoverable=$();this.focusable=$();if(element!==this){$.data(element,this.widgetFullName,this);this._on(true,this.element,{remove:function(event){if(event.target===element){this.destroy()}}});this.document=$(element.style?element.ownerDocument:element.document||element);this.window=$(this.document[0].defaultView||this.document[0].parentWindow)}this._create();this._trigger("create",null,this._getCreateEventData());this._init()},_getCreateOptions:$.noop,_getCreateEventData:$.noop,_create:$.noop,_init:$.noop,destroy:function(){this._destroy();this.element.unbind(this.eventNamespace).removeData(this.widgetName).removeData(this.widgetFullName).removeData($.camelCase(this.widgetFullName));this.widget().unbind(this.eventNamespace).removeAttr("aria-disabled").removeClass(this.widgetFullName+"-disabled "+"ui-state-disabled");this.bindings.unbind(this.eventNamespace);this.hoverable.removeClass("ui-state-hover");this.focusable.removeClass("ui-state-focus")},_destroy:$.noop,widget:function(){return this.element},option:function(key,value){var options=key,parts,curOption,i;if(arguments.length===0){return $.widget.extend({},this.options)}if(typeof key==="string"){options={};parts=key.split(".");key=parts.shift();if(parts.length){curOption=options[key]=$.widget.extend({},this.options[key]);for(i=0;i<parts.length-1;i++){curOption[parts[i]]=curOption[parts[i]]||{};curOption=curOption[parts[i]]}key=parts.pop();if(arguments.length===1){return curOption[key]===undefined?null:curOption[key]}curOption[key]=value}else{if(arguments.length===1){return this.options[key]===undefined?null:this.options[key]}options[key]=value}}this._setOptions(options);return this},_setOptions:function(options){var key;for(key in options){this._setOption(key,options[key])}return this},_setOption:function(key,value){this.options[key]=value;if(key==="disabled"){this.widget().toggleClass(this.widgetFullName+"-disabled ui-state-disabled",!!value).attr("aria-disabled",value);this.hoverable.removeClass("ui-state-hover");this.focusable.removeClass("ui-state-focus")}return this},enable:function(){return this._setOption("disabled",false)},disable:function(){return this._setOption("disabled",true)},_on:function(suppressDisabledCheck,element,handlers){var delegateElement,instance=this;if(typeof suppressDisabledCheck!=="boolean"){handlers=element;element=suppressDisabledCheck;suppressDisabledCheck=false}if(!handlers){handlers=element;element=this.element;delegateElement=this.widget()}else{element=delegateElement=$(element);this.bindings=this.bindings.add(element)}$.each(handlers,function(event,handler){function handlerProxy(){if(!suppressDisabledCheck&&(instance.options.disabled===true||$(this).hasClass("ui-state-disabled"))){return}return(typeof handler==="string"?instance[handler]:handler).apply(instance,arguments)}if(typeof handler!=="string"){handlerProxy.guid=handler.guid=handler.guid||handlerProxy.guid||$.guid++}var match=event.match(/^(\w+)\s*(.*)$/),eventName=match[1]+instance.eventNamespace,selector=match[2];if(selector){delegateElement.delegate(selector,eventName,handlerProxy)}else{element.bind(eventName,handlerProxy)}})},_off:function(element,eventName){eventName=(eventName||"").split(" ").join(this.eventNamespace+" ")+this.eventNamespace;element.unbind(eventName).undelegate(eventName)},_delay:function(handler,delay){function handlerProxy(){return(typeof handler==="string"?instance[handler]:handler).apply(instance,arguments)}var instance=this;return setTimeout(handlerProxy,delay||0)},_hoverable:function(element){this.hoverable=this.hoverable.add(element);this._on(element,{mouseenter:function(event){$(event.currentTarget).addClass("ui-state-hover")},mouseleave:function(event){$(event.currentTarget).removeClass("ui-state-hover")}})},_focusable:function(element){this.focusable=this.focusable.add(element);this._on(element,{focusin:function(event){$(event.currentTarget).addClass("ui-state-focus")},focusout:function(event){$(event.currentTarget).removeClass("ui-state-focus")}})},_trigger:function(type,event,data){var prop,orig,callback=this.options[type];data=data||{};event=$.Event(event);event.type=(type===this.widgetEventPrefix?type:this.widgetEventPrefix+type).toLowerCase();event.target=this.element[0];orig=event.originalEvent;if(orig){for(prop in orig){if(!(prop in event)){event[prop]=orig[prop]}}}this.element.trigger(event,data);return!($.isFunction(callback)&&callback.apply(this.element[0],[event].concat(data))===false||event.isDefaultPrevented())}};$.each({show:"fadeIn",hide:"fadeOut"},function(method,defaultEffect){$.Widget.prototype["_"+method]=function(element,options,callback){if(typeof options==="string"){options={effect:options}}var hasOptions,effectName=!options?method:options===true||typeof options==="number"?defaultEffect:options.effect||defaultEffect;options=options||{};if(typeof options==="number"){options={duration:options}}hasOptions=!$.isEmptyObject(options);options.complete=callback;if(options.delay){element.delay(options.delay)}if(hasOptions&&$.effects&&$.effects.effect[effectName]){element[method](options)}else if(effectName!==method&&element[effectName]){element[effectName](options.duration,options.easing,callback)}else{element.queue(function(next){$(this)[method]();if(callback){callback.call(element[0])}next()})}}})})(jQuery);(function($,undefined){var mouseHandled=false;$(document).mouseup(function(){mouseHandled=false});$.widget("ui.mouse",{version:"1.10.4",options:{cancel:"input,textarea,button,select,option",distance:1,delay:0},_mouseInit:function(){var that=this;this.element.bind("mousedown."+this.widgetName,function(event){return that._mouseDown(event)}).bind("click."+this.widgetName,function(event){if(true===$.data(event.target,that.widgetName+".preventClickEvent")){$.removeData(event.target,that.widgetName+".preventClickEvent");event.stopImmediatePropagation();return false}});this.started=false},_mouseDestroy:function(){this.element.unbind("."+this.widgetName);if(this._mouseMoveDelegate){$(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate)}},_mouseDown:function(event){if(mouseHandled){return}this._mouseStarted&&this._mouseUp(event);this._mouseDownEvent=event;var that=this,btnIsLeft=event.which===1,elIsCancel=typeof this.options.cancel==="string"&&event.target.nodeName?$(event.target).closest(this.options.cancel).length:false;if(!btnIsLeft||elIsCancel||!this._mouseCapture(event)){return true}this.mouseDelayMet=!this.options.delay;if(!this.mouseDelayMet){this._mouseDelayTimer=setTimeout(function(){that.mouseDelayMet=true},this.options.delay)}if(this._mouseDistanceMet(event)&&this._mouseDelayMet(event)){this._mouseStarted=this._mouseStart(event)!==false;if(!this._mouseStarted){event.preventDefault();return true}}if(true===$.data(event.target,this.widgetName+".preventClickEvent")){$.removeData(event.target,this.widgetName+".preventClickEvent")}this._mouseMoveDelegate=function(event){return that._mouseMove(event)};this._mouseUpDelegate=function(event){return that._mouseUp(event)};$(document).bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,this._mouseUpDelegate);event.preventDefault();mouseHandled=true;return true},_mouseMove:function(event){if($.ui.ie&&(!document.documentMode||document.documentMode<9)&&!event.button){return this._mouseUp(event)}if(this._mouseStarted){this._mouseDrag(event);return event.preventDefault()}if(this._mouseDistanceMet(event)&&this._mouseDelayMet(event)){this._mouseStarted=this._mouseStart(this._mouseDownEvent,event)!==false;this._mouseStarted?this._mouseDrag(event):this._mouseUp(event)}return!this._mouseStarted},_mouseUp:function(event){$(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate);if(this._mouseStarted){this._mouseStarted=false;if(event.target===this._mouseDownEvent.target){$.data(event.target,this.widgetName+".preventClickEvent",true)}this._mouseStop(event)}return false},_mouseDistanceMet:function(event){return Math.max(Math.abs(this._mouseDownEvent.pageX-event.pageX),Math.abs(this._mouseDownEvent.pageY-event.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},_mouseCapture:function(){return true}})})(jQuery);(function($,undefined){$.ui=$.ui||{};var cachedScrollbarWidth,max=Math.max,abs=Math.abs,round=Math.round,rhorizontal=/left|center|right/,rvertical=/top|center|bottom/,roffset=/[\+\-]\d+(\.[\d]+)?%?/,rposition=/^\w+/,rpercent=/%$/,_position=$.fn.position;function getOffsets(offsets,width,height){return[parseFloat(offsets[0])*(rpercent.test(offsets[0])?width/100:1),parseFloat(offsets[1])*(rpercent.test(offsets[1])?height/100:1)]}function parseCss(element,property){return parseInt($.css(element,property),10)||0}function getDimensions(elem){var raw=elem[0];if(raw.nodeType===9){return{width:elem.width(),height:elem.height(),offset:{top:0,left:0}}}if($.isWindow(raw)){return{width:elem.width(),height:elem.height(),offset:{top:elem.scrollTop(),left:elem.scrollLeft()}}}if(raw.preventDefault){return{width:0,height:0,offset:{top:raw.pageY,left:raw.pageX}}}return{width:elem.outerWidth(),height:elem.outerHeight(),offset:elem.offset()}}$.position={scrollbarWidth:function(){if(cachedScrollbarWidth!==undefined){return cachedScrollbarWidth}var w1,w2,div=$("<div style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>"),innerDiv=div.children()[0];$("body").append(div);w1=innerDiv.offsetWidth;div.css("overflow","scroll");w2=innerDiv.offsetWidth;if(w1===w2){w2=div[0].clientWidth}div.remove();return cachedScrollbarWidth=w1-w2},getScrollInfo:function(within){var overflowX=within.isWindow||within.isDocument?"":within.element.css("overflow-x"),overflowY=within.isWindow||within.isDocument?"":within.element.css("overflow-y"),hasOverflowX=overflowX==="scroll"||overflowX==="auto"&&within.width<within.element[0].scrollWidth,hasOverflowY=overflowY==="scroll"||overflowY==="auto"&&within.height<within.element[0].scrollHeight;return{width:hasOverflowY?$.position.scrollbarWidth():0,height:hasOverflowX?$.position.scrollbarWidth():0}},getWithinInfo:function(element){var withinElement=$(element||window),isWindow=$.isWindow(withinElement[0]),isDocument=!!withinElement[0]&&withinElement[0].nodeType===9;return{element:withinElement,isWindow:isWindow,isDocument:isDocument,offset:withinElement.offset()||{left:0,top:0},scrollLeft:withinElement.scrollLeft(),scrollTop:withinElement.scrollTop(),width:isWindow?withinElement.width():withinElement.outerWidth(),height:isWindow?withinElement.height():withinElement.outerHeight()}}};$.fn.position=function(options){if(!options||!options.of){return _position.apply(this,arguments)}options=$.extend({},options);var atOffset,targetWidth,targetHeight,targetOffset,basePosition,dimensions,target=$(options.of),within=$.position.getWithinInfo(options.within),scrollInfo=$.position.getScrollInfo(within),collision=(options.collision||"flip").split(" "),offsets={};dimensions=getDimensions(target);if(target[0].preventDefault){options.at="left top"}targetWidth=dimensions.width;targetHeight=dimensions.height;targetOffset=dimensions.offset;basePosition=$.extend({},targetOffset);$.each(["my","at"],function(){var pos=(options[this]||"").split(" "),horizontalOffset,verticalOffset;if(pos.length===1){pos=rhorizontal.test(pos[0])?pos.concat(["center"]):rvertical.test(pos[0])?["center"].concat(pos):["center","center"]}pos[0]=rhorizontal.test(pos[0])?pos[0]:"center";pos[1]=rvertical.test(pos[1])?pos[1]:"center";horizontalOffset=roffset.exec(pos[0]);verticalOffset=roffset.exec(pos[1]);offsets[this]=[horizontalOffset?horizontalOffset[0]:0,verticalOffset?verticalOffset[0]:0];options[this]=[rposition.exec(pos[0])[0],rposition.exec(pos[1])[0]]});if(collision.length===1){collision[1]=collision[0]}if(options.at[0]==="right"){basePosition.left+=targetWidth}else if(options.at[0]==="center"){basePosition.left+=targetWidth/2}if(options.at[1]==="bottom"){basePosition.top+=targetHeight}else if(options.at[1]==="center"){basePosition.top+=targetHeight/2}atOffset=getOffsets(offsets.at,targetWidth,targetHeight);basePosition.left+=atOffset[0];basePosition.top+=atOffset[1];return this.each(function(){var collisionPosition,using,elem=$(this),elemWidth=elem.outerWidth(),elemHeight=elem.outerHeight(),marginLeft=parseCss(this,"marginLeft"),marginTop=parseCss(this,"marginTop"),collisionWidth=elemWidth+marginLeft+parseCss(this,"marginRight")+scrollInfo.width,collisionHeight=elemHeight+marginTop+parseCss(this,"marginBottom")+scrollInfo.height,position=$.extend({},basePosition),myOffset=getOffsets(offsets.my,elem.outerWidth(),elem.outerHeight());if(options.my[0]==="right"){position.left-=elemWidth}else if(options.my[0]==="center"){position.left-=elemWidth/2}if(options.my[1]==="bottom"){position.top-=elemHeight}else if(options.my[1]==="center"){position.top-=elemHeight/2}position.left+=myOffset[0];position.top+=myOffset[1];if(!$.support.offsetFractions){position.left=round(position.left);position.top=round(position.top)}collisionPosition={marginLeft:marginLeft,marginTop:marginTop};$.each(["left","top"],function(i,dir){if($.ui.position[collision[i]]){$.ui.position[collision[i]][dir](position,{targetWidth:targetWidth,targetHeight:targetHeight,elemWidth:elemWidth,elemHeight:elemHeight,collisionPosition:collisionPosition,collisionWidth:collisionWidth,collisionHeight:collisionHeight,offset:[atOffset[0]+myOffset[0],atOffset[1]+myOffset[1]],my:options.my,at:options.at,within:within,elem:elem})}});if(options.using){using=function(props){var left=targetOffset.left-position.left,right=left+targetWidth-elemWidth,top=targetOffset.top-position.top,bottom=top+targetHeight-elemHeight,feedback={target:{element:target,left:targetOffset.left,top:targetOffset.top,width:targetWidth,height:targetHeight},element:{element:elem,left:position.left,top:position.top,width:elemWidth,height:elemHeight},horizontal:right<0?"left":left>0?"right":"center",vertical:bottom<0?"top":top>0?"bottom":"middle"};if(targetWidth<elemWidth&&abs(left+right)<targetWidth){feedback.horizontal="center"}if(targetHeight<elemHeight&&abs(top+bottom)<targetHeight){feedback.vertical="middle"}if(max(abs(left),abs(right))>max(abs(top),abs(bottom))){feedback.important="horizontal"}else{feedback.important="vertical"}options.using.call(this,props,feedback)}}elem.offset($.extend(position,{using:using}))})};$.ui.position={fit:{left:function(position,data){var within=data.within,withinOffset=within.isWindow?within.scrollLeft:within.offset.left,outerWidth=within.width,collisionPosLeft=position.left-data.collisionPosition.marginLeft,overLeft=withinOffset-collisionPosLeft,overRight=collisionPosLeft+data.collisionWidth-outerWidth-withinOffset,newOverRight;if(data.collisionWidth>outerWidth){if(overLeft>0&&overRight<=0){newOverRight=position.left+overLeft+data.collisionWidth-outerWidth-withinOffset;position.left+=overLeft-newOverRight}else if(overRight>0&&overLeft<=0){position.left=withinOffset}else{if(overLeft>overRight){position.left=withinOffset+outerWidth-data.collisionWidth}else{position.left=withinOffset}}}else if(overLeft>0){position.left+=overLeft}else if(overRight>0){position.left-=overRight}else{position.left=max(position.left-collisionPosLeft,position.left)}},top:function(position,data){var within=data.within,withinOffset=within.isWindow?within.scrollTop:within.offset.top,outerHeight=data.within.height,collisionPosTop=position.top-data.collisionPosition.marginTop,overTop=withinOffset-collisionPosTop,overBottom=collisionPosTop+data.collisionHeight-outerHeight-withinOffset,newOverBottom;if(data.collisionHeight>outerHeight){if(overTop>0&&overBottom<=0){newOverBottom=position.top+overTop+data.collisionHeight-outerHeight-withinOffset;position.top+=overTop-newOverBottom}else if(overBottom>0&&overTop<=0){position.top=withinOffset}else{if(overTop>overBottom){position.top=withinOffset+outerHeight-data.collisionHeight}else{position.top=withinOffset}}}else if(overTop>0){position.top+=overTop}else if(overBottom>0){position.top-=overBottom}else{position.top=max(position.top-collisionPosTop,position.top)}}},flip:{left:function(position,data){var within=data.within,withinOffset=within.offset.left+within.scrollLeft,outerWidth=within.width,offsetLeft=within.isWindow?within.scrollLeft:within.offset.left,collisionPosLeft=position.left-data.collisionPosition.marginLeft,overLeft=collisionPosLeft-offsetLeft,overRight=collisionPosLeft+data.collisionWidth-outerWidth-offsetLeft,myOffset=data.my[0]==="left"?-data.elemWidth:data.my[0]==="right"?data.elemWidth:0,atOffset=data.at[0]==="left"?data.targetWidth:data.at[0]==="right"?-data.targetWidth:0,offset=-2*data.offset[0],newOverRight,newOverLeft;if(overLeft<0){newOverRight=position.left+myOffset+atOffset+offset+data.collisionWidth-outerWidth-withinOffset;if(newOverRight<0||newOverRight<abs(overLeft)){position.left+=myOffset+atOffset+offset}}else if(overRight>0){newOverLeft=position.left-data.collisionPosition.marginLeft+myOffset+atOffset+offset-offsetLeft;if(newOverLeft>0||abs(newOverLeft)<overRight){position.left+=myOffset+atOffset+offset}}},top:function(position,data){var within=data.within,withinOffset=within.offset.top+within.scrollTop,outerHeight=within.height,offsetTop=within.isWindow?within.scrollTop:within.offset.top,collisionPosTop=position.top-data.collisionPosition.marginTop,overTop=collisionPosTop-offsetTop,overBottom=collisionPosTop+data.collisionHeight-outerHeight-offsetTop,top=data.my[1]==="top",myOffset=top?-data.elemHeight:data.my[1]==="bottom"?data.elemHeight:0,atOffset=data.at[1]==="top"?data.targetHeight:data.at[1]==="bottom"?-data.targetHeight:0,offset=-2*data.offset[1],newOverTop,newOverBottom;if(overTop<0){newOverBottom=position.top+myOffset+atOffset+offset+data.collisionHeight-outerHeight-withinOffset;if(position.top+myOffset+atOffset+offset>overTop&&(newOverBottom<0||newOverBottom<abs(overTop))){position.top+=myOffset+atOffset+offset}}else if(overBottom>0){newOverTop=position.top-data.collisionPosition.marginTop+myOffset+atOffset+offset-offsetTop;if(position.top+myOffset+atOffset+offset>overBottom&&(newOverTop>0||abs(newOverTop)<overBottom)){position.top+=myOffset+atOffset+offset}}}},flipfit:{left:function(){$.ui.position.flip.left.apply(this,arguments);$.ui.position.fit.left.apply(this,arguments)},top:function(){$.ui.position.flip.top.apply(this,arguments);$.ui.position.fit.top.apply(this,arguments)}}};(function(){var testElement,testElementParent,testElementStyle,offsetLeft,i,body=document.getElementsByTagName("body")[0],div=document.createElement("div");testElement=document.createElement(body?"div":"body");testElementStyle={visibility:"hidden",width:0,height:0,border:0,margin:0,background:"none"};if(body){$.extend(testElementStyle,{position:"absolute",left:"-1000px",top:"-1000px"})}for(i in testElementStyle){testElement.style[i]=testElementStyle[i]}testElement.appendChild(div);testElementParent=body||document.documentElement;testElementParent.insertBefore(testElement,testElementParent.firstChild);div.style.cssText="position: absolute; left: 10.7432222px;";offsetLeft=$(div).offset().left;$.support.offsetFractions=offsetLeft>10&&offsetLeft<11;testElement.innerHTML="";testElementParent.removeChild(testElement)})()})(jQuery);(function($,undefined){$.widget("ui.draggable",$.ui.mouse,{version:"1.10.4",widgetEventPrefix:"drag",options:{addClasses:true,appendTo:"parent",axis:false,connectToSortable:false,containment:false,cursor:"auto",cursorAt:false,grid:false,handle:false,helper:"original",iframeFix:false,opacity:false,refreshPositions:false,revert:false,revertDuration:500,scope:"default",scroll:true,scrollSensitivity:20,scrollSpeed:20,snap:false,snapMode:"both",snapTolerance:20,stack:false,zIndex:false,drag:null,start:null,stop:null},_create:function(){if(this.options.helper==="original"&&!/^(?:r|a|f)/.test(this.element.css("position"))){this.element[0].style.position="relative"}if(this.options.addClasses){this.element.addClass("ui-draggable")}if(this.options.disabled){this.element.addClass("ui-draggable-disabled")}this._mouseInit()},_destroy:function(){this.element.removeClass("ui-draggable ui-draggable-dragging ui-draggable-disabled");this._mouseDestroy()},_mouseCapture:function(event){var o=this.options;if(this.helper||o.disabled||$(event.target).closest(".ui-resizable-handle").length>0){return false}this.handle=this._getHandle(event);if(!this.handle){return false}$(o.iframeFix===true?"iframe":o.iframeFix).each(function(){$("<div class='ui-draggable-iframeFix' style='background: #fff;'></div>").css({width:this.offsetWidth+"px",height:this.offsetHeight+"px",position:"absolute",opacity:"0.001",zIndex:1e3}).css($(this).offset()).appendTo("body")});return true},_mouseStart:function(event){var o=this.options;this.helper=this._createHelper(event);this.helper.addClass("ui-draggable-dragging");this._cacheHelperProportions();if($.ui.ddmanager){$.ui.ddmanager.current=this}this._cacheMargins();this.cssPosition=this.helper.css("position");this.scrollParent=this.helper.scrollParent();this.offsetParent=this.helper.offsetParent();this.offsetParentCssPosition=this.offsetParent.css("position");this.offset=this.positionAbs=this.element.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};this.offset.scroll=false;$.extend(this.offset,{click:{left:event.pageX-this.offset.left,top:event.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()});this.originalPosition=this.position=this._generatePosition(event);this.originalPageX=event.pageX;this.originalPageY=event.pageY;o.cursorAt&&this._adjustOffsetFromHelper(o.cursorAt);this._setContainment();if(this._trigger("start",event)===false){this._clear();return false}this._cacheHelperProportions();if($.ui.ddmanager&&!o.dropBehaviour){$.ui.ddmanager.prepareOffsets(this,event)}this._mouseDrag(event,true);if($.ui.ddmanager){$.ui.ddmanager.dragStart(this,event)}return true},_mouseDrag:function(event,noPropagation){if(this.offsetParentCssPosition==="fixed"){this.offset.parent=this._getParentOffset()}this.position=this._generatePosition(event);this.positionAbs=this._convertPositionTo("absolute");if(!noPropagation){var ui=this._uiHash();if(this._trigger("drag",event,ui)===false){this._mouseUp({});return false}this.position=ui.position}if(!this.options.axis||this.options.axis!=="y"){this.helper[0].style.left=this.position.left+"px"}if(!this.options.axis||this.options.axis!=="x"){this.helper[0].style.top=this.position.top+"px"}if($.ui.ddmanager){$.ui.ddmanager.drag(this,event)
}return false},_mouseStop:function(event){var that=this,dropped=false;if($.ui.ddmanager&&!this.options.dropBehaviour){dropped=$.ui.ddmanager.drop(this,event)}if(this.dropped){dropped=this.dropped;this.dropped=false}if(this.options.helper==="original"&&!$.contains(this.element[0].ownerDocument,this.element[0])){return false}if(this.options.revert==="invalid"&&!dropped||this.options.revert==="valid"&&dropped||this.options.revert===true||$.isFunction(this.options.revert)&&this.options.revert.call(this.element,dropped)){$(this.helper).animate(this.originalPosition,parseInt(this.options.revertDuration,10),function(){if(that._trigger("stop",event)!==false){that._clear()}})}else{if(this._trigger("stop",event)!==false){this._clear()}}return false},_mouseUp:function(event){$("div.ui-draggable-iframeFix").each(function(){this.parentNode.removeChild(this)});if($.ui.ddmanager){$.ui.ddmanager.dragStop(this,event)}return $.ui.mouse.prototype._mouseUp.call(this,event)},cancel:function(){if(this.helper.is(".ui-draggable-dragging")){this._mouseUp({})}else{this._clear()}return this},_getHandle:function(event){return this.options.handle?!!$(event.target).closest(this.element.find(this.options.handle)).length:true},_createHelper:function(event){var o=this.options,helper=$.isFunction(o.helper)?$(o.helper.apply(this.element[0],[event])):o.helper==="clone"?this.element.clone().removeAttr("id"):this.element;if(!helper.parents("body").length){helper.appendTo(o.appendTo==="parent"?this.element[0].parentNode:o.appendTo)}if(helper[0]!==this.element[0]&&!/(fixed|absolute)/.test(helper.css("position"))){helper.css("position","absolute")}return helper},_adjustOffsetFromHelper:function(obj){if(typeof obj==="string"){obj=obj.split(" ")}if($.isArray(obj)){obj={left:+obj[0],top:+obj[1]||0}}if("left"in obj){this.offset.click.left=obj.left+this.margins.left}if("right"in obj){this.offset.click.left=this.helperProportions.width-obj.right+this.margins.left}if("top"in obj){this.offset.click.top=obj.top+this.margins.top}if("bottom"in obj){this.offset.click.top=this.helperProportions.height-obj.bottom+this.margins.top}},_getParentOffset:function(){var po=this.offsetParent.offset();if(this.cssPosition==="absolute"&&this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0])){po.left+=this.scrollParent.scrollLeft();po.top+=this.scrollParent.scrollTop()}if(this.offsetParent[0]===document.body||this.offsetParent[0].tagName&&this.offsetParent[0].tagName.toLowerCase()==="html"&&$.ui.ie){po={top:0,left:0}}return{top:po.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:po.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if(this.cssPosition==="relative"){var p=this.element.position();return{top:p.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:p.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}else{return{top:0,left:0}}},_cacheMargins:function(){this.margins={left:parseInt(this.element.css("marginLeft"),10)||0,top:parseInt(this.element.css("marginTop"),10)||0,right:parseInt(this.element.css("marginRight"),10)||0,bottom:parseInt(this.element.css("marginBottom"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var over,c,ce,o=this.options;if(!o.containment){this.containment=null;return}if(o.containment==="window"){this.containment=[$(window).scrollLeft()-this.offset.relative.left-this.offset.parent.left,$(window).scrollTop()-this.offset.relative.top-this.offset.parent.top,$(window).scrollLeft()+$(window).width()-this.helperProportions.width-this.margins.left,$(window).scrollTop()+($(window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top];return}if(o.containment==="document"){this.containment=[0,0,$(document).width()-this.helperProportions.width-this.margins.left,($(document).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top];return}if(o.containment.constructor===Array){this.containment=o.containment;return}if(o.containment==="parent"){o.containment=this.helper[0].parentNode}c=$(o.containment);ce=c[0];if(!ce){return}over=c.css("overflow")!=="hidden";this.containment=[(parseInt(c.css("borderLeftWidth"),10)||0)+(parseInt(c.css("paddingLeft"),10)||0),(parseInt(c.css("borderTopWidth"),10)||0)+(parseInt(c.css("paddingTop"),10)||0),(over?Math.max(ce.scrollWidth,ce.offsetWidth):ce.offsetWidth)-(parseInt(c.css("borderRightWidth"),10)||0)-(parseInt(c.css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left-this.margins.right,(over?Math.max(ce.scrollHeight,ce.offsetHeight):ce.offsetHeight)-(parseInt(c.css("borderBottomWidth"),10)||0)-(parseInt(c.css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top-this.margins.bottom];this.relative_container=c},_convertPositionTo:function(d,pos){if(!pos){pos=this.position}var mod=d==="absolute"?1:-1,scroll=this.cssPosition==="absolute"&&!(this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent;if(!this.offset.scroll){this.offset.scroll={top:scroll.scrollTop(),left:scroll.scrollLeft()}}return{top:pos.top+this.offset.relative.top*mod+this.offset.parent.top*mod-(this.cssPosition==="fixed"?-this.scrollParent.scrollTop():this.offset.scroll.top)*mod,left:pos.left+this.offset.relative.left*mod+this.offset.parent.left*mod-(this.cssPosition==="fixed"?-this.scrollParent.scrollLeft():this.offset.scroll.left)*mod}},_generatePosition:function(event){var containment,co,top,left,o=this.options,scroll=this.cssPosition==="absolute"&&!(this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,pageX=event.pageX,pageY=event.pageY;if(!this.offset.scroll){this.offset.scroll={top:scroll.scrollTop(),left:scroll.scrollLeft()}}if(this.originalPosition){if(this.containment){if(this.relative_container){co=this.relative_container.offset();containment=[this.containment[0]+co.left,this.containment[1]+co.top,this.containment[2]+co.left,this.containment[3]+co.top]}else{containment=this.containment}if(event.pageX-this.offset.click.left<containment[0]){pageX=containment[0]+this.offset.click.left}if(event.pageY-this.offset.click.top<containment[1]){pageY=containment[1]+this.offset.click.top}if(event.pageX-this.offset.click.left>containment[2]){pageX=containment[2]+this.offset.click.left}if(event.pageY-this.offset.click.top>containment[3]){pageY=containment[3]+this.offset.click.top}}if(o.grid){top=o.grid[1]?this.originalPageY+Math.round((pageY-this.originalPageY)/o.grid[1])*o.grid[1]:this.originalPageY;pageY=containment?top-this.offset.click.top>=containment[1]||top-this.offset.click.top>containment[3]?top:top-this.offset.click.top>=containment[1]?top-o.grid[1]:top+o.grid[1]:top;left=o.grid[0]?this.originalPageX+Math.round((pageX-this.originalPageX)/o.grid[0])*o.grid[0]:this.originalPageX;pageX=containment?left-this.offset.click.left>=containment[0]||left-this.offset.click.left>containment[2]?left:left-this.offset.click.left>=containment[0]?left-o.grid[0]:left+o.grid[0]:left}}return{top:pageY-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+(this.cssPosition==="fixed"?-this.scrollParent.scrollTop():this.offset.scroll.top),left:pageX-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+(this.cssPosition==="fixed"?-this.scrollParent.scrollLeft():this.offset.scroll.left)}},_clear:function(){this.helper.removeClass("ui-draggable-dragging");if(this.helper[0]!==this.element[0]&&!this.cancelHelperRemoval){this.helper.remove()}this.helper=null;this.cancelHelperRemoval=false},_trigger:function(type,event,ui){ui=ui||this._uiHash();$.ui.plugin.call(this,type,[event,ui]);if(type==="drag"){this.positionAbs=this._convertPositionTo("absolute")}return $.Widget.prototype._trigger.call(this,type,event,ui)},plugins:{},_uiHash:function(){return{helper:this.helper,position:this.position,originalPosition:this.originalPosition,offset:this.positionAbs}}});$.ui.plugin.add("draggable","connectToSortable",{start:function(event,ui){var inst=$(this).data("ui-draggable"),o=inst.options,uiSortable=$.extend({},ui,{item:inst.element});inst.sortables=[];$(o.connectToSortable).each(function(){var sortable=$.data(this,"ui-sortable");if(sortable&&!sortable.options.disabled){inst.sortables.push({instance:sortable,shouldRevert:sortable.options.revert});sortable.refreshPositions();sortable._trigger("activate",event,uiSortable)}})},stop:function(event,ui){var inst=$(this).data("ui-draggable"),uiSortable=$.extend({},ui,{item:inst.element});$.each(inst.sortables,function(){if(this.instance.isOver){this.instance.isOver=0;inst.cancelHelperRemoval=true;this.instance.cancelHelperRemoval=false;if(this.shouldRevert){this.instance.options.revert=this.shouldRevert}this.instance._mouseStop(event);this.instance.options.helper=this.instance.options._helper;if(inst.options.helper==="original"){this.instance.currentItem.css({top:"auto",left:"auto"})}}else{this.instance.cancelHelperRemoval=false;this.instance._trigger("deactivate",event,uiSortable)}})},drag:function(event,ui){var inst=$(this).data("ui-draggable"),that=this;$.each(inst.sortables,function(){var innermostIntersecting=false,thisSortable=this;this.instance.positionAbs=inst.positionAbs;this.instance.helperProportions=inst.helperProportions;this.instance.offset.click=inst.offset.click;if(this.instance._intersectsWith(this.instance.containerCache)){innermostIntersecting=true;$.each(inst.sortables,function(){this.instance.positionAbs=inst.positionAbs;this.instance.helperProportions=inst.helperProportions;this.instance.offset.click=inst.offset.click;if(this!==thisSortable&&this.instance._intersectsWith(this.instance.containerCache)&&$.contains(thisSortable.instance.element[0],this.instance.element[0])){innermostIntersecting=false}return innermostIntersecting})}if(innermostIntersecting){if(!this.instance.isOver){this.instance.isOver=1;this.instance.currentItem=$(that).clone().removeAttr("id").appendTo(this.instance.element).data("ui-sortable-item",true);this.instance.options._helper=this.instance.options.helper;this.instance.options.helper=function(){return ui.helper[0]};event.target=this.instance.currentItem[0];this.instance._mouseCapture(event,true);this.instance._mouseStart(event,true,true);this.instance.offset.click.top=inst.offset.click.top;this.instance.offset.click.left=inst.offset.click.left;this.instance.offset.parent.left-=inst.offset.parent.left-this.instance.offset.parent.left;this.instance.offset.parent.top-=inst.offset.parent.top-this.instance.offset.parent.top;inst._trigger("toSortable",event);inst.dropped=this.instance.element;inst.currentItem=inst.element;this.instance.fromOutside=inst}if(this.instance.currentItem){this.instance._mouseDrag(event)}}else{if(this.instance.isOver){this.instance.isOver=0;this.instance.cancelHelperRemoval=true;this.instance.options.revert=false;this.instance._trigger("out",event,this.instance._uiHash(this.instance));this.instance._mouseStop(event,true);this.instance.options.helper=this.instance.options._helper;this.instance.currentItem.remove();if(this.instance.placeholder){this.instance.placeholder.remove()}inst._trigger("fromSortable",event);inst.dropped=false}}})}});$.ui.plugin.add("draggable","cursor",{start:function(){var t=$("body"),o=$(this).data("ui-draggable").options;if(t.css("cursor")){o._cursor=t.css("cursor")}t.css("cursor",o.cursor)},stop:function(){var o=$(this).data("ui-draggable").options;if(o._cursor){$("body").css("cursor",o._cursor)}}});$.ui.plugin.add("draggable","opacity",{start:function(event,ui){var t=$(ui.helper),o=$(this).data("ui-draggable").options;if(t.css("opacity")){o._opacity=t.css("opacity")}t.css("opacity",o.opacity)},stop:function(event,ui){var o=$(this).data("ui-draggable").options;if(o._opacity){$(ui.helper).css("opacity",o._opacity)}}});$.ui.plugin.add("draggable","scroll",{start:function(){var i=$(this).data("ui-draggable");if(i.scrollParent[0]!==document&&i.scrollParent[0].tagName!=="HTML"){i.overflowOffset=i.scrollParent.offset()}},drag:function(event){var i=$(this).data("ui-draggable"),o=i.options,scrolled=false;if(i.scrollParent[0]!==document&&i.scrollParent[0].tagName!=="HTML"){if(!o.axis||o.axis!=="x"){if(i.overflowOffset.top+i.scrollParent[0].offsetHeight-event.pageY<o.scrollSensitivity){i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop+o.scrollSpeed}else if(event.pageY-i.overflowOffset.top<o.scrollSensitivity){i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop-o.scrollSpeed}}if(!o.axis||o.axis!=="y"){if(i.overflowOffset.left+i.scrollParent[0].offsetWidth-event.pageX<o.scrollSensitivity){i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft+o.scrollSpeed}else if(event.pageX-i.overflowOffset.left<o.scrollSensitivity){i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft-o.scrollSpeed}}}else{if(!o.axis||o.axis!=="x"){if(event.pageY-$(document).scrollTop()<o.scrollSensitivity){scrolled=$(document).scrollTop($(document).scrollTop()-o.scrollSpeed)}else if($(window).height()-(event.pageY-$(document).scrollTop())<o.scrollSensitivity){scrolled=$(document).scrollTop($(document).scrollTop()+o.scrollSpeed)}}if(!o.axis||o.axis!=="y"){if(event.pageX-$(document).scrollLeft()<o.scrollSensitivity){scrolled=$(document).scrollLeft($(document).scrollLeft()-o.scrollSpeed)}else if($(window).width()-(event.pageX-$(document).scrollLeft())<o.scrollSensitivity){scrolled=$(document).scrollLeft($(document).scrollLeft()+o.scrollSpeed)}}}if(scrolled!==false&&$.ui.ddmanager&&!o.dropBehaviour){$.ui.ddmanager.prepareOffsets(i,event)}}});$.ui.plugin.add("draggable","snap",{start:function(){var i=$(this).data("ui-draggable"),o=i.options;i.snapElements=[];$(o.snap.constructor!==String?o.snap.items||":data(ui-draggable)":o.snap).each(function(){var $t=$(this),$o=$t.offset();if(this!==i.element[0]){i.snapElements.push({item:this,width:$t.outerWidth(),height:$t.outerHeight(),top:$o.top,left:$o.left})}})},drag:function(event,ui){var ts,bs,ls,rs,l,r,t,b,i,first,inst=$(this).data("ui-draggable"),o=inst.options,d=o.snapTolerance,x1=ui.offset.left,x2=x1+inst.helperProportions.width,y1=ui.offset.top,y2=y1+inst.helperProportions.height;for(i=inst.snapElements.length-1;i>=0;i--){l=inst.snapElements[i].left;r=l+inst.snapElements[i].width;t=inst.snapElements[i].top;b=t+inst.snapElements[i].height;if(x2<l-d||x1>r+d||y2<t-d||y1>b+d||!$.contains(inst.snapElements[i].item.ownerDocument,inst.snapElements[i].item)){if(inst.snapElements[i].snapping){inst.options.snap.release&&inst.options.snap.release.call(inst.element,event,$.extend(inst._uiHash(),{snapItem:inst.snapElements[i].item}))}inst.snapElements[i].snapping=false;continue}if(o.snapMode!=="inner"){ts=Math.abs(t-y2)<=d;bs=Math.abs(b-y1)<=d;ls=Math.abs(l-x2)<=d;rs=Math.abs(r-x1)<=d;if(ts){ui.position.top=inst._convertPositionTo("relative",{top:t-inst.helperProportions.height,left:0}).top-inst.margins.top}if(bs){ui.position.top=inst._convertPositionTo("relative",{top:b,left:0}).top-inst.margins.top}if(ls){ui.position.left=inst._convertPositionTo("relative",{top:0,left:l-inst.helperProportions.width}).left-inst.margins.left}if(rs){ui.position.left=inst._convertPositionTo("relative",{top:0,left:r}).left-inst.margins.left}}first=ts||bs||ls||rs;if(o.snapMode!=="outer"){ts=Math.abs(t-y1)<=d;bs=Math.abs(b-y2)<=d;ls=Math.abs(l-x1)<=d;rs=Math.abs(r-x2)<=d;if(ts){ui.position.top=inst._convertPositionTo("relative",{top:t,left:0}).top-inst.margins.top}if(bs){ui.position.top=inst._convertPositionTo("relative",{top:b-inst.helperProportions.height,left:0}).top-inst.margins.top}if(ls){ui.position.left=inst._convertPositionTo("relative",{top:0,left:l}).left-inst.margins.left}if(rs){ui.position.left=inst._convertPositionTo("relative",{top:0,left:r-inst.helperProportions.width}).left-inst.margins.left}}if(!inst.snapElements[i].snapping&&(ts||bs||ls||rs||first)){inst.options.snap.snap&&inst.options.snap.snap.call(inst.element,event,$.extend(inst._uiHash(),{snapItem:inst.snapElements[i].item}))}inst.snapElements[i].snapping=ts||bs||ls||rs||first}}});$.ui.plugin.add("draggable","stack",{start:function(){var min,o=this.data("ui-draggable").options,group=$.makeArray($(o.stack)).sort(function(a,b){return(parseInt($(a).css("zIndex"),10)||0)-(parseInt($(b).css("zIndex"),10)||0)});if(!group.length){return}min=parseInt($(group[0]).css("zIndex"),10)||0;$(group).each(function(i){$(this).css("zIndex",min+i)});this.css("zIndex",min+group.length)}});$.ui.plugin.add("draggable","zIndex",{start:function(event,ui){var t=$(ui.helper),o=$(this).data("ui-draggable").options;if(t.css("zIndex")){o._zIndex=t.css("zIndex")}t.css("zIndex",o.zIndex)},stop:function(event,ui){var o=$(this).data("ui-draggable").options;if(o._zIndex){$(ui.helper).css("zIndex",o._zIndex)}}})})(jQuery);(function($,undefined){function isOverAxis(x,reference,size){return x>reference&&x<reference+size}$.widget("ui.droppable",{version:"1.10.4",widgetEventPrefix:"drop",options:{accept:"*",activeClass:false,addClasses:true,greedy:false,hoverClass:false,scope:"default",tolerance:"intersect",activate:null,deactivate:null,drop:null,out:null,over:null},_create:function(){var proportions,o=this.options,accept=o.accept;this.isover=false;this.isout=true;this.accept=$.isFunction(accept)?accept:function(d){return d.is(accept)};this.proportions=function(){if(arguments.length){proportions=arguments[0]}else{return proportions?proportions:proportions={width:this.element[0].offsetWidth,height:this.element[0].offsetHeight}}};$.ui.ddmanager.droppables[o.scope]=$.ui.ddmanager.droppables[o.scope]||[];$.ui.ddmanager.droppables[o.scope].push(this);o.addClasses&&this.element.addClass("ui-droppable")},_destroy:function(){var i=0,drop=$.ui.ddmanager.droppables[this.options.scope];for(;i<drop.length;i++){if(drop[i]===this){drop.splice(i,1)}}this.element.removeClass("ui-droppable ui-droppable-disabled")},_setOption:function(key,value){if(key==="accept"){this.accept=$.isFunction(value)?value:function(d){return d.is(value)}}$.Widget.prototype._setOption.apply(this,arguments)},_activate:function(event){var draggable=$.ui.ddmanager.current;if(this.options.activeClass){this.element.addClass(this.options.activeClass)}if(draggable){this._trigger("activate",event,this.ui(draggable))}},_deactivate:function(event){var draggable=$.ui.ddmanager.current;if(this.options.activeClass){this.element.removeClass(this.options.activeClass)}if(draggable){this._trigger("deactivate",event,this.ui(draggable))}},_over:function(event){var draggable=$.ui.ddmanager.current;if(!draggable||(draggable.currentItem||draggable.element)[0]===this.element[0]){return}if(this.accept.call(this.element[0],draggable.currentItem||draggable.element)){if(this.options.hoverClass){this.element.addClass(this.options.hoverClass)}this._trigger("over",event,this.ui(draggable))}},_out:function(event){var draggable=$.ui.ddmanager.current;if(!draggable||(draggable.currentItem||draggable.element)[0]===this.element[0]){return}if(this.accept.call(this.element[0],draggable.currentItem||draggable.element)){if(this.options.hoverClass){this.element.removeClass(this.options.hoverClass)}this._trigger("out",event,this.ui(draggable))}},_drop:function(event,custom){var draggable=custom||$.ui.ddmanager.current,childrenIntersection=false;if(!draggable||(draggable.currentItem||draggable.element)[0]===this.element[0]){return false}this.element.find(":data(ui-droppable)").not(".ui-draggable-dragging").each(function(){var inst=$.data(this,"ui-droppable");if(inst.options.greedy&&!inst.options.disabled&&inst.options.scope===draggable.options.scope&&inst.accept.call(inst.element[0],draggable.currentItem||draggable.element)&&$.ui.intersect(draggable,$.extend(inst,{offset:inst.element.offset()}),inst.options.tolerance)){childrenIntersection=true;return false}});if(childrenIntersection){return false}if(this.accept.call(this.element[0],draggable.currentItem||draggable.element)){if(this.options.activeClass){this.element.removeClass(this.options.activeClass)}if(this.options.hoverClass){this.element.removeClass(this.options.hoverClass)}this._trigger("drop",event,this.ui(draggable));return this.element}return false},ui:function(c){return{draggable:c.currentItem||c.element,helper:c.helper,position:c.position,offset:c.positionAbs}}});$.ui.intersect=function(draggable,droppable,toleranceMode){if(!droppable.offset){return false}var draggableLeft,draggableTop,x1=(draggable.positionAbs||draggable.position.absolute).left,y1=(draggable.positionAbs||draggable.position.absolute).top,x2=x1+draggable.helperProportions.width,y2=y1+draggable.helperProportions.height,l=droppable.offset.left,t=droppable.offset.top,r=l+droppable.proportions().width,b=t+droppable.proportions().height;switch(toleranceMode){case"fit":return l<=x1&&x2<=r&&t<=y1&&y2<=b;case"intersect":return l<x1+draggable.helperProportions.width/2&&x2-draggable.helperProportions.width/2<r&&t<y1+draggable.helperProportions.height/2&&y2-draggable.helperProportions.height/2<b;case"pointer":draggableLeft=(draggable.positionAbs||draggable.position.absolute).left+(draggable.clickOffset||draggable.offset.click).left;draggableTop=(draggable.positionAbs||draggable.position.absolute).top+(draggable.clickOffset||draggable.offset.click).top;return isOverAxis(draggableTop,t,droppable.proportions().height)&&isOverAxis(draggableLeft,l,droppable.proportions().width);case"touch":return(y1>=t&&y1<=b||y2>=t&&y2<=b||y1<t&&y2>b)&&(x1>=l&&x1<=r||x2>=l&&x2<=r||x1<l&&x2>r);default:return false}};$.ui.ddmanager={current:null,droppables:{"default":[]},prepareOffsets:function(t,event){var i,j,m=$.ui.ddmanager.droppables[t.options.scope]||[],type=event?event.type:null,list=(t.currentItem||t.element).find(":data(ui-droppable)").addBack();droppablesLoop:for(i=0;i<m.length;i++){if(m[i].options.disabled||t&&!m[i].accept.call(m[i].element[0],t.currentItem||t.element)){continue}for(j=0;j<list.length;j++){if(list[j]===m[i].element[0]){m[i].proportions().height=0;continue droppablesLoop}}m[i].visible=m[i].element.css("display")!=="none";if(!m[i].visible){continue}if(type==="mousedown"){m[i]._activate.call(m[i],event)}m[i].offset=m[i].element.offset();m[i].proportions({width:m[i].element[0].offsetWidth,height:m[i].element[0].offsetHeight})}},drop:function(draggable,event){var dropped=false;$.each(($.ui.ddmanager.droppables[draggable.options.scope]||[]).slice(),function(){if(!this.options){return}if(!this.options.disabled&&this.visible&&$.ui.intersect(draggable,this,this.options.tolerance)){dropped=this._drop.call(this,event)||dropped}if(!this.options.disabled&&this.visible&&this.accept.call(this.element[0],draggable.currentItem||draggable.element)){this.isout=true;this.isover=false;this._deactivate.call(this,event)}});return dropped},dragStart:function(draggable,event){draggable.element.parentsUntil("body").bind("scroll.droppable",function(){if(!draggable.options.refreshPositions){$.ui.ddmanager.prepareOffsets(draggable,event)}})},drag:function(draggable,event){if(draggable.options.refreshPositions){$.ui.ddmanager.prepareOffsets(draggable,event)}$.each($.ui.ddmanager.droppables[draggable.options.scope]||[],function(){if(this.options.disabled||this.greedyChild||!this.visible){return}var parentInstance,scope,parent,intersects=$.ui.intersect(draggable,this,this.options.tolerance),c=!intersects&&this.isover?"isout":intersects&&!this.isover?"isover":null;if(!c){return}if(this.options.greedy){scope=this.options.scope;parent=this.element.parents(":data(ui-droppable)").filter(function(){return $.data(this,"ui-droppable").options.scope===scope});if(parent.length){parentInstance=$.data(parent[0],"ui-droppable");parentInstance.greedyChild=c==="isover"}}if(parentInstance&&c==="isover"){parentInstance.isover=false;parentInstance.isout=true;parentInstance._out.call(parentInstance,event)}this[c]=true;this[c==="isout"?"isover":"isout"]=false;this[c==="isover"?"_over":"_out"].call(this,event);if(parentInstance&&c==="isout"){parentInstance.isout=false;parentInstance.isover=true;parentInstance._over.call(parentInstance,event)}})},dragStop:function(draggable,event){draggable.element.parentsUntil("body").unbind("scroll.droppable");if(!draggable.options.refreshPositions){$.ui.ddmanager.prepareOffsets(draggable,event)}}}})(jQuery);(function($,undefined){function num(v){return parseInt(v,10)||0}function isNumber(value){return!isNaN(parseInt(value,10))}$.widget("ui.resizable",$.ui.mouse,{version:"1.10.4",widgetEventPrefix:"resize",options:{alsoResize:false,animate:false,animateDuration:"slow",animateEasing:"swing",aspectRatio:false,autoHide:false,containment:false,ghost:false,grid:false,handles:"e,s,se",helper:false,maxHeight:null,maxWidth:null,minHeight:10,minWidth:10,zIndex:90,resize:null,start:null,stop:null},_create:function(){var n,i,handle,axis,hname,that=this,o=this.options;this.element.addClass("ui-resizable");$.extend(this,{_aspectRatio:!!o.aspectRatio,aspectRatio:o.aspectRatio,originalElement:this.element,_proportionallyResizeElements:[],_helper:o.helper||o.ghost||o.animate?o.helper||"ui-resizable-helper":null});if(this.element[0].nodeName.match(/canvas|textarea|input|select|button|img/i)){this.element.wrap($("<div class='ui-wrapper' style='overflow: hidden;'></div>").css({position:this.element.css("position"),width:this.element.outerWidth(),height:this.element.outerHeight(),top:this.element.css("top"),left:this.element.css("left")}));this.element=this.element.parent().data("ui-resizable",this.element.data("ui-resizable"));this.elementIsWrapper=true;this.element.css({marginLeft:this.originalElement.css("marginLeft"),marginTop:this.originalElement.css("marginTop"),marginRight:this.originalElement.css("marginRight"),marginBottom:this.originalElement.css("marginBottom")});this.originalElement.css({marginLeft:0,marginTop:0,marginRight:0,marginBottom:0});this.originalResizeStyle=this.originalElement.css("resize");this.originalElement.css("resize","none");this._proportionallyResizeElements.push(this.originalElement.css({position:"static",zoom:1,display:"block"}));this.originalElement.css({margin:this.originalElement.css("margin")});this._proportionallyResize()}this.handles=o.handles||(!$(".ui-resizable-handle",this.element).length?"e,s,se":{n:".ui-resizable-n",e:".ui-resizable-e",s:".ui-resizable-s",w:".ui-resizable-w",se:".ui-resizable-se",sw:".ui-resizable-sw",ne:".ui-resizable-ne",nw:".ui-resizable-nw"});if(this.handles.constructor===String){if(this.handles==="all"){this.handles="n,e,s,w,se,sw,ne,nw"}n=this.handles.split(",");this.handles={};for(i=0;i<n.length;i++){handle=$.trim(n[i]);hname="ui-resizable-"+handle;axis=$("<div class='ui-resizable-handle "+hname+"'></div>");axis.css({zIndex:o.zIndex});if("se"===handle){axis.addClass("ui-icon ui-icon-gripsmall-diagonal-se")}this.handles[handle]=".ui-resizable-"+handle;this.element.append(axis)}}this._renderAxis=function(target){var i,axis,padPos,padWrapper;target=target||this.element;for(i in this.handles){if(this.handles[i].constructor===String){this.handles[i]=$(this.handles[i],this.element).show()}if(this.elementIsWrapper&&this.originalElement[0].nodeName.match(/textarea|input|select|button/i)){axis=$(this.handles[i],this.element);padWrapper=/sw|ne|nw|se|n|s/.test(i)?axis.outerHeight():axis.outerWidth();padPos=["padding",/ne|nw|n/.test(i)?"Top":/se|sw|s/.test(i)?"Bottom":/^e$/.test(i)?"Right":"Left"].join("");target.css(padPos,padWrapper);this._proportionallyResize()}if(!$(this.handles[i]).length){continue}}};this._renderAxis(this.element);this._handles=$(".ui-resizable-handle",this.element).disableSelection();this._handles.mouseover(function(){if(!that.resizing){if(this.className){axis=this.className.match(/ui-resizable-(se|sw|ne|nw|n|e|s|w)/i)}that.axis=axis&&axis[1]?axis[1]:"se"}});if(o.autoHide){this._handles.hide();$(this.element).addClass("ui-resizable-autohide").mouseenter(function(){if(o.disabled){return}$(this).removeClass("ui-resizable-autohide");that._handles.show()}).mouseleave(function(){if(o.disabled){return}if(!that.resizing){$(this).addClass("ui-resizable-autohide");that._handles.hide()}})}this._mouseInit()},_destroy:function(){this._mouseDestroy();var wrapper,_destroy=function(exp){$(exp).removeClass("ui-resizable ui-resizable-disabled ui-resizable-resizing").removeData("resizable").removeData("ui-resizable").unbind(".resizable").find(".ui-resizable-handle").remove()};if(this.elementIsWrapper){_destroy(this.element);wrapper=this.element;this.originalElement.css({position:wrapper.css("position"),width:wrapper.outerWidth(),height:wrapper.outerHeight(),top:wrapper.css("top"),left:wrapper.css("left")}).insertAfter(wrapper);wrapper.remove()}this.originalElement.css("resize",this.originalResizeStyle);_destroy(this.originalElement);return this},_mouseCapture:function(event){var i,handle,capture=false;for(i in this.handles){handle=$(this.handles[i])[0];if(handle===event.target||$.contains(handle,event.target)){capture=true}}return!this.options.disabled&&capture},_mouseStart:function(event){var curleft,curtop,cursor,o=this.options,iniPos=this.element.position(),el=this.element;this.resizing=true;if(/absolute/.test(el.css("position"))){el.css({position:"absolute",top:el.css("top"),left:el.css("left")})}else if(el.is(".ui-draggable")){el.css({position:"absolute",top:iniPos.top,left:iniPos.left})}this._renderProxy();curleft=num(this.helper.css("left"));curtop=num(this.helper.css("top"));if(o.containment){curleft+=$(o.containment).scrollLeft()||0;curtop+=$(o.containment).scrollTop()||0}this.offset=this.helper.offset();this.position={left:curleft,top:curtop};this.size=this._helper?{width:this.helper.width(),height:this.helper.height()}:{width:el.width(),height:el.height()};this.originalSize=this._helper?{width:el.outerWidth(),height:el.outerHeight()}:{width:el.width(),height:el.height()};this.originalPosition={left:curleft,top:curtop};this.sizeDiff={width:el.outerWidth()-el.width(),height:el.outerHeight()-el.height()};this.originalMousePosition={left:event.pageX,top:event.pageY};this.aspectRatio=typeof o.aspectRatio==="number"?o.aspectRatio:this.originalSize.width/this.originalSize.height||1;cursor=$(".ui-resizable-"+this.axis).css("cursor");$("body").css("cursor",cursor==="auto"?this.axis+"-resize":cursor);el.addClass("ui-resizable-resizing");this._propagate("start",event);return true},_mouseDrag:function(event){var data,el=this.helper,props={},smp=this.originalMousePosition,a=this.axis,prevTop=this.position.top,prevLeft=this.position.left,prevWidth=this.size.width,prevHeight=this.size.height,dx=event.pageX-smp.left||0,dy=event.pageY-smp.top||0,trigger=this._change[a];if(!trigger){return false}data=trigger.apply(this,[event,dx,dy]);this._updateVirtualBoundaries(event.shiftKey);if(this._aspectRatio||event.shiftKey){data=this._updateRatio(data,event)}data=this._respectSize(data,event);this._updateCache(data);this._propagate("resize",event);if(this.position.top!==prevTop){props.top=this.position.top+"px"}if(this.position.left!==prevLeft){props.left=this.position.left+"px"}if(this.size.width!==prevWidth){props.width=this.size.width+"px"}if(this.size.height!==prevHeight){props.height=this.size.height+"px"}el.css(props);if(!this._helper&&this._proportionallyResizeElements.length){this._proportionallyResize()}if(!$.isEmptyObject(props)){this._trigger("resize",event,this.ui())}return false},_mouseStop:function(event){this.resizing=false;var pr,ista,soffseth,soffsetw,s,left,top,o=this.options,that=this;if(this._helper){pr=this._proportionallyResizeElements;ista=pr.length&&/textarea/i.test(pr[0].nodeName);soffseth=ista&&$.ui.hasScroll(pr[0],"left")?0:that.sizeDiff.height;soffsetw=ista?0:that.sizeDiff.width;s={width:that.helper.width()-soffsetw,height:that.helper.height()-soffseth};left=parseInt(that.element.css("left"),10)+(that.position.left-that.originalPosition.left)||null;top=parseInt(that.element.css("top"),10)+(that.position.top-that.originalPosition.top)||null;
if(!o.animate){this.element.css($.extend(s,{top:top,left:left}))}that.helper.height(that.size.height);that.helper.width(that.size.width);if(this._helper&&!o.animate){this._proportionallyResize()}}$("body").css("cursor","auto");this.element.removeClass("ui-resizable-resizing");this._propagate("stop",event);if(this._helper){this.helper.remove()}return false},_updateVirtualBoundaries:function(forceAspectRatio){var pMinWidth,pMaxWidth,pMinHeight,pMaxHeight,b,o=this.options;b={minWidth:isNumber(o.minWidth)?o.minWidth:0,maxWidth:isNumber(o.maxWidth)?o.maxWidth:Infinity,minHeight:isNumber(o.minHeight)?o.minHeight:0,maxHeight:isNumber(o.maxHeight)?o.maxHeight:Infinity};if(this._aspectRatio||forceAspectRatio){pMinWidth=b.minHeight*this.aspectRatio;pMinHeight=b.minWidth/this.aspectRatio;pMaxWidth=b.maxHeight*this.aspectRatio;pMaxHeight=b.maxWidth/this.aspectRatio;if(pMinWidth>b.minWidth){b.minWidth=pMinWidth}if(pMinHeight>b.minHeight){b.minHeight=pMinHeight}if(pMaxWidth<b.maxWidth){b.maxWidth=pMaxWidth}if(pMaxHeight<b.maxHeight){b.maxHeight=pMaxHeight}}this._vBoundaries=b},_updateCache:function(data){this.offset=this.helper.offset();if(isNumber(data.left)){this.position.left=data.left}if(isNumber(data.top)){this.position.top=data.top}if(isNumber(data.height)){this.size.height=data.height}if(isNumber(data.width)){this.size.width=data.width}},_updateRatio:function(data){var cpos=this.position,csize=this.size,a=this.axis;if(isNumber(data.height)){data.width=data.height*this.aspectRatio}else if(isNumber(data.width)){data.height=data.width/this.aspectRatio}if(a==="sw"){data.left=cpos.left+(csize.width-data.width);data.top=null}if(a==="nw"){data.top=cpos.top+(csize.height-data.height);data.left=cpos.left+(csize.width-data.width)}return data},_respectSize:function(data){var o=this._vBoundaries,a=this.axis,ismaxw=isNumber(data.width)&&o.maxWidth&&o.maxWidth<data.width,ismaxh=isNumber(data.height)&&o.maxHeight&&o.maxHeight<data.height,isminw=isNumber(data.width)&&o.minWidth&&o.minWidth>data.width,isminh=isNumber(data.height)&&o.minHeight&&o.minHeight>data.height,dw=this.originalPosition.left+this.originalSize.width,dh=this.position.top+this.size.height,cw=/sw|nw|w/.test(a),ch=/nw|ne|n/.test(a);if(isminw){data.width=o.minWidth}if(isminh){data.height=o.minHeight}if(ismaxw){data.width=o.maxWidth}if(ismaxh){data.height=o.maxHeight}if(isminw&&cw){data.left=dw-o.minWidth}if(ismaxw&&cw){data.left=dw-o.maxWidth}if(isminh&&ch){data.top=dh-o.minHeight}if(ismaxh&&ch){data.top=dh-o.maxHeight}if(!data.width&&!data.height&&!data.left&&data.top){data.top=null}else if(!data.width&&!data.height&&!data.top&&data.left){data.left=null}return data},_proportionallyResize:function(){if(!this._proportionallyResizeElements.length){return}var i,j,borders,paddings,prel,element=this.helper||this.element;for(i=0;i<this._proportionallyResizeElements.length;i++){prel=this._proportionallyResizeElements[i];if(!this.borderDif){this.borderDif=[];borders=[prel.css("borderTopWidth"),prel.css("borderRightWidth"),prel.css("borderBottomWidth"),prel.css("borderLeftWidth")];paddings=[prel.css("paddingTop"),prel.css("paddingRight"),prel.css("paddingBottom"),prel.css("paddingLeft")];for(j=0;j<borders.length;j++){this.borderDif[j]=(parseInt(borders[j],10)||0)+(parseInt(paddings[j],10)||0)}}prel.css({height:element.height()-this.borderDif[0]-this.borderDif[2]||0,width:element.width()-this.borderDif[1]-this.borderDif[3]||0})}},_renderProxy:function(){var el=this.element,o=this.options;this.elementOffset=el.offset();if(this._helper){this.helper=this.helper||$("<div style='overflow:hidden;'></div>");this.helper.addClass(this._helper).css({width:this.element.outerWidth()-1,height:this.element.outerHeight()-1,position:"absolute",left:this.elementOffset.left+"px",top:this.elementOffset.top+"px",zIndex:++o.zIndex});this.helper.appendTo("body").disableSelection()}else{this.helper=this.element}},_change:{e:function(event,dx){return{width:this.originalSize.width+dx}},w:function(event,dx){var cs=this.originalSize,sp=this.originalPosition;return{left:sp.left+dx,width:cs.width-dx}},n:function(event,dx,dy){var cs=this.originalSize,sp=this.originalPosition;return{top:sp.top+dy,height:cs.height-dy}},s:function(event,dx,dy){return{height:this.originalSize.height+dy}},se:function(event,dx,dy){return $.extend(this._change.s.apply(this,arguments),this._change.e.apply(this,[event,dx,dy]))},sw:function(event,dx,dy){return $.extend(this._change.s.apply(this,arguments),this._change.w.apply(this,[event,dx,dy]))},ne:function(event,dx,dy){return $.extend(this._change.n.apply(this,arguments),this._change.e.apply(this,[event,dx,dy]))},nw:function(event,dx,dy){return $.extend(this._change.n.apply(this,arguments),this._change.w.apply(this,[event,dx,dy]))}},_propagate:function(n,event){$.ui.plugin.call(this,n,[event,this.ui()]);n!=="resize"&&this._trigger(n,event,this.ui())},plugins:{},ui:function(){return{originalElement:this.originalElement,element:this.element,helper:this.helper,position:this.position,size:this.size,originalSize:this.originalSize,originalPosition:this.originalPosition}}});$.ui.plugin.add("resizable","animate",{stop:function(event){var that=$(this).data("ui-resizable"),o=that.options,pr=that._proportionallyResizeElements,ista=pr.length&&/textarea/i.test(pr[0].nodeName),soffseth=ista&&$.ui.hasScroll(pr[0],"left")?0:that.sizeDiff.height,soffsetw=ista?0:that.sizeDiff.width,style={width:that.size.width-soffsetw,height:that.size.height-soffseth},left=parseInt(that.element.css("left"),10)+(that.position.left-that.originalPosition.left)||null,top=parseInt(that.element.css("top"),10)+(that.position.top-that.originalPosition.top)||null;that.element.animate($.extend(style,top&&left?{top:top,left:left}:{}),{duration:o.animateDuration,easing:o.animateEasing,step:function(){var data={width:parseInt(that.element.css("width"),10),height:parseInt(that.element.css("height"),10),top:parseInt(that.element.css("top"),10),left:parseInt(that.element.css("left"),10)};if(pr&&pr.length){$(pr[0]).css({width:data.width,height:data.height})}that._updateCache(data);that._propagate("resize",event)}})}});$.ui.plugin.add("resizable","containment",{start:function(){var element,p,co,ch,cw,width,height,that=$(this).data("ui-resizable"),o=that.options,el=that.element,oc=o.containment,ce=oc instanceof $?oc.get(0):/parent/.test(oc)?el.parent().get(0):oc;if(!ce){return}that.containerElement=$(ce);if(/document/.test(oc)||oc===document){that.containerOffset={left:0,top:0};that.containerPosition={left:0,top:0};that.parentData={element:$(document),left:0,top:0,width:$(document).width(),height:$(document).height()||document.body.parentNode.scrollHeight}}else{element=$(ce);p=[];$(["Top","Right","Left","Bottom"]).each(function(i,name){p[i]=num(element.css("padding"+name))});that.containerOffset=element.offset();that.containerPosition=element.position();that.containerSize={height:element.innerHeight()-p[3],width:element.innerWidth()-p[1]};co=that.containerOffset;ch=that.containerSize.height;cw=that.containerSize.width;width=$.ui.hasScroll(ce,"left")?ce.scrollWidth:cw;height=$.ui.hasScroll(ce)?ce.scrollHeight:ch;that.parentData={element:ce,left:co.left,top:co.top,width:width,height:height}}},resize:function(event){var woset,hoset,isParent,isOffsetRelative,that=$(this).data("ui-resizable"),o=that.options,co=that.containerOffset,cp=that.position,pRatio=that._aspectRatio||event.shiftKey,cop={top:0,left:0},ce=that.containerElement;if(ce[0]!==document&&/static/.test(ce.css("position"))){cop=co}if(cp.left<(that._helper?co.left:0)){that.size.width=that.size.width+(that._helper?that.position.left-co.left:that.position.left-cop.left);if(pRatio){that.size.height=that.size.width/that.aspectRatio}that.position.left=o.helper?co.left:0}if(cp.top<(that._helper?co.top:0)){that.size.height=that.size.height+(that._helper?that.position.top-co.top:that.position.top);if(pRatio){that.size.width=that.size.height*that.aspectRatio}that.position.top=that._helper?co.top:0}that.offset.left=that.parentData.left+that.position.left;that.offset.top=that.parentData.top+that.position.top;woset=Math.abs((that._helper?that.offset.left-cop.left:that.offset.left-cop.left)+that.sizeDiff.width);hoset=Math.abs((that._helper?that.offset.top-cop.top:that.offset.top-co.top)+that.sizeDiff.height);isParent=that.containerElement.get(0)===that.element.parent().get(0);isOffsetRelative=/relative|absolute/.test(that.containerElement.css("position"));if(isParent&&isOffsetRelative){woset-=Math.abs(that.parentData.left)}if(woset+that.size.width>=that.parentData.width){that.size.width=that.parentData.width-woset;if(pRatio){that.size.height=that.size.width/that.aspectRatio}}if(hoset+that.size.height>=that.parentData.height){that.size.height=that.parentData.height-hoset;if(pRatio){that.size.width=that.size.height*that.aspectRatio}}},stop:function(){var that=$(this).data("ui-resizable"),o=that.options,co=that.containerOffset,cop=that.containerPosition,ce=that.containerElement,helper=$(that.helper),ho=helper.offset(),w=helper.outerWidth()-that.sizeDiff.width,h=helper.outerHeight()-that.sizeDiff.height;if(that._helper&&!o.animate&&/relative/.test(ce.css("position"))){$(this).css({left:ho.left-cop.left-co.left,width:w,height:h})}if(that._helper&&!o.animate&&/static/.test(ce.css("position"))){$(this).css({left:ho.left-cop.left-co.left,width:w,height:h})}}});$.ui.plugin.add("resizable","alsoResize",{start:function(){var that=$(this).data("ui-resizable"),o=that.options,_store=function(exp){$(exp).each(function(){var el=$(this);el.data("ui-resizable-alsoresize",{width:parseInt(el.width(),10),height:parseInt(el.height(),10),left:parseInt(el.css("left"),10),top:parseInt(el.css("top"),10)})})};if(typeof o.alsoResize==="object"&&!o.alsoResize.parentNode){if(o.alsoResize.length){o.alsoResize=o.alsoResize[0];_store(o.alsoResize)}else{$.each(o.alsoResize,function(exp){_store(exp)})}}else{_store(o.alsoResize)}},resize:function(event,ui){var that=$(this).data("ui-resizable"),o=that.options,os=that.originalSize,op=that.originalPosition,delta={height:that.size.height-os.height||0,width:that.size.width-os.width||0,top:that.position.top-op.top||0,left:that.position.left-op.left||0},_alsoResize=function(exp,c){$(exp).each(function(){var el=$(this),start=$(this).data("ui-resizable-alsoresize"),style={},css=c&&c.length?c:el.parents(ui.originalElement[0]).length?["width","height"]:["width","height","top","left"];$.each(css,function(i,prop){var sum=(start[prop]||0)+(delta[prop]||0);if(sum&&sum>=0){style[prop]=sum||null}});el.css(style)})};if(typeof o.alsoResize==="object"&&!o.alsoResize.nodeType){$.each(o.alsoResize,function(exp,c){_alsoResize(exp,c)})}else{_alsoResize(o.alsoResize)}},stop:function(){$(this).removeData("resizable-alsoresize")}});$.ui.plugin.add("resizable","ghost",{start:function(){var that=$(this).data("ui-resizable"),o=that.options,cs=that.size;that.ghost=that.originalElement.clone();that.ghost.css({opacity:.25,display:"block",position:"relative",height:cs.height,width:cs.width,margin:0,left:0,top:0}).addClass("ui-resizable-ghost").addClass(typeof o.ghost==="string"?o.ghost:"");that.ghost.appendTo(that.helper)},resize:function(){var that=$(this).data("ui-resizable");if(that.ghost){that.ghost.css({position:"relative",height:that.size.height,width:that.size.width})}},stop:function(){var that=$(this).data("ui-resizable");if(that.ghost&&that.helper){that.helper.get(0).removeChild(that.ghost.get(0))}}});$.ui.plugin.add("resizable","grid",{resize:function(){var that=$(this).data("ui-resizable"),o=that.options,cs=that.size,os=that.originalSize,op=that.originalPosition,a=that.axis,grid=typeof o.grid==="number"?[o.grid,o.grid]:o.grid,gridX=grid[0]||1,gridY=grid[1]||1,ox=Math.round((cs.width-os.width)/gridX)*gridX,oy=Math.round((cs.height-os.height)/gridY)*gridY,newWidth=os.width+ox,newHeight=os.height+oy,isMaxWidth=o.maxWidth&&o.maxWidth<newWidth,isMaxHeight=o.maxHeight&&o.maxHeight<newHeight,isMinWidth=o.minWidth&&o.minWidth>newWidth,isMinHeight=o.minHeight&&o.minHeight>newHeight;o.grid=grid;if(isMinWidth){newWidth=newWidth+gridX}if(isMinHeight){newHeight=newHeight+gridY}if(isMaxWidth){newWidth=newWidth-gridX}if(isMaxHeight){newHeight=newHeight-gridY}if(/^(se|s|e)$/.test(a)){that.size.width=newWidth;that.size.height=newHeight}else if(/^(ne)$/.test(a)){that.size.width=newWidth;that.size.height=newHeight;that.position.top=op.top-oy}else if(/^(sw)$/.test(a)){that.size.width=newWidth;that.size.height=newHeight;that.position.left=op.left-ox}else{if(newHeight-gridY>0){that.size.height=newHeight;that.position.top=op.top-oy}else{that.size.height=gridY;that.position.top=op.top+os.height-gridY}if(newWidth-gridX>0){that.size.width=newWidth;that.position.left=op.left-ox}else{that.size.width=gridX;that.position.left=op.left+os.width-gridX}}}})})(jQuery);(function($,undefined){$.widget("ui.selectable",$.ui.mouse,{version:"1.10.4",options:{appendTo:"body",autoRefresh:true,distance:0,filter:"*",tolerance:"touch",selected:null,selecting:null,start:null,stop:null,unselected:null,unselecting:null},_create:function(){var selectees,that=this;this.element.addClass("ui-selectable");this.dragged=false;this.refresh=function(){selectees=$(that.options.filter,that.element[0]);selectees.addClass("ui-selectee");selectees.each(function(){var $this=$(this),pos=$this.offset();$.data(this,"selectable-item",{element:this,$element:$this,left:pos.left,top:pos.top,right:pos.left+$this.outerWidth(),bottom:pos.top+$this.outerHeight(),startselected:false,selected:$this.hasClass("ui-selected"),selecting:$this.hasClass("ui-selecting"),unselecting:$this.hasClass("ui-unselecting")})})};this.refresh();this.selectees=selectees.addClass("ui-selectee");this._mouseInit();this.helper=$("<div class='ui-selectable-helper'></div>")},_destroy:function(){this.selectees.removeClass("ui-selectee").removeData("selectable-item");this.element.removeClass("ui-selectable ui-selectable-disabled");this._mouseDestroy()},_mouseStart:function(event){var that=this,options=this.options;this.opos=[event.pageX,event.pageY];if(this.options.disabled){return}this.selectees=$(options.filter,this.element[0]);this._trigger("start",event);$(options.appendTo).append(this.helper);this.helper.css({left:event.pageX,top:event.pageY,width:0,height:0});if(options.autoRefresh){this.refresh()}this.selectees.filter(".ui-selected").each(function(){var selectee=$.data(this,"selectable-item");selectee.startselected=true;if(!event.metaKey&&!event.ctrlKey){selectee.$element.removeClass("ui-selected");selectee.selected=false;selectee.$element.addClass("ui-unselecting");selectee.unselecting=true;that._trigger("unselecting",event,{unselecting:selectee.element})}});$(event.target).parents().addBack().each(function(){var doSelect,selectee=$.data(this,"selectable-item");if(selectee){doSelect=!event.metaKey&&!event.ctrlKey||!selectee.$element.hasClass("ui-selected");selectee.$element.removeClass(doSelect?"ui-unselecting":"ui-selected").addClass(doSelect?"ui-selecting":"ui-unselecting");selectee.unselecting=!doSelect;selectee.selecting=doSelect;selectee.selected=doSelect;if(doSelect){that._trigger("selecting",event,{selecting:selectee.element})}else{that._trigger("unselecting",event,{unselecting:selectee.element})}return false}})},_mouseDrag:function(event){this.dragged=true;if(this.options.disabled){return}var tmp,that=this,options=this.options,x1=this.opos[0],y1=this.opos[1],x2=event.pageX,y2=event.pageY;if(x1>x2){tmp=x2;x2=x1;x1=tmp}if(y1>y2){tmp=y2;y2=y1;y1=tmp}this.helper.css({left:x1,top:y1,width:x2-x1,height:y2-y1});this.selectees.each(function(){var selectee=$.data(this,"selectable-item"),hit=false;if(!selectee||selectee.element===that.element[0]){return}if(options.tolerance==="touch"){hit=!(selectee.left>x2||selectee.right<x1||selectee.top>y2||selectee.bottom<y1)}else if(options.tolerance==="fit"){hit=selectee.left>x1&&selectee.right<x2&&selectee.top>y1&&selectee.bottom<y2}if(hit){if(selectee.selected){selectee.$element.removeClass("ui-selected");selectee.selected=false}if(selectee.unselecting){selectee.$element.removeClass("ui-unselecting");selectee.unselecting=false}if(!selectee.selecting){selectee.$element.addClass("ui-selecting");selectee.selecting=true;that._trigger("selecting",event,{selecting:selectee.element})}}else{if(selectee.selecting){if((event.metaKey||event.ctrlKey)&&selectee.startselected){selectee.$element.removeClass("ui-selecting");selectee.selecting=false;selectee.$element.addClass("ui-selected");selectee.selected=true}else{selectee.$element.removeClass("ui-selecting");selectee.selecting=false;if(selectee.startselected){selectee.$element.addClass("ui-unselecting");selectee.unselecting=true}that._trigger("unselecting",event,{unselecting:selectee.element})}}if(selectee.selected){if(!event.metaKey&&!event.ctrlKey&&!selectee.startselected){selectee.$element.removeClass("ui-selected");selectee.selected=false;selectee.$element.addClass("ui-unselecting");selectee.unselecting=true;that._trigger("unselecting",event,{unselecting:selectee.element})}}}});return false},_mouseStop:function(event){var that=this;this.dragged=false;$(".ui-unselecting",this.element[0]).each(function(){var selectee=$.data(this,"selectable-item");selectee.$element.removeClass("ui-unselecting");selectee.unselecting=false;selectee.startselected=false;that._trigger("unselected",event,{unselected:selectee.element})});$(".ui-selecting",this.element[0]).each(function(){var selectee=$.data(this,"selectable-item");selectee.$element.removeClass("ui-selecting").addClass("ui-selected");selectee.selecting=false;selectee.selected=true;selectee.startselected=true;that._trigger("selected",event,{selected:selectee.element})});this._trigger("stop",event);this.helper.remove();return false}})})(jQuery);(function($,undefined){function isOverAxis(x,reference,size){return x>reference&&x<reference+size}function isFloating(item){return/left|right/.test(item.css("float"))||/inline|table-cell/.test(item.css("display"))}$.widget("ui.sortable",$.ui.mouse,{version:"1.10.4",widgetEventPrefix:"sort",ready:false,options:{appendTo:"parent",axis:false,connectWith:false,containment:false,cursor:"auto",cursorAt:false,dropOnEmpty:true,forcePlaceholderSize:false,forceHelperSize:false,grid:false,handle:false,helper:"original",items:"> *",opacity:false,placeholder:false,revert:false,scroll:true,scrollSensitivity:20,scrollSpeed:20,scope:"default",tolerance:"intersect",zIndex:1e3,activate:null,beforeStop:null,change:null,deactivate:null,out:null,over:null,receive:null,remove:null,sort:null,start:null,stop:null,update:null},_create:function(){var o=this.options;this.containerCache={};this.element.addClass("ui-sortable");this.refresh();this.floating=this.items.length?o.axis==="x"||isFloating(this.items[0].item):false;this.offset=this.element.offset();this._mouseInit();this.ready=true},_destroy:function(){this.element.removeClass("ui-sortable ui-sortable-disabled");this._mouseDestroy();for(var i=this.items.length-1;i>=0;i--){this.items[i].item.removeData(this.widgetName+"-item")}return this},_setOption:function(key,value){if(key==="disabled"){this.options[key]=value;this.widget().toggleClass("ui-sortable-disabled",!!value)}else{$.Widget.prototype._setOption.apply(this,arguments)}},_mouseCapture:function(event,overrideHandle){var currentItem=null,validHandle=false,that=this;if(this.reverting){return false}if(this.options.disabled||this.options.type==="static"){return false}this._refreshItems(event);$(event.target).parents().each(function(){if($.data(this,that.widgetName+"-item")===that){currentItem=$(this);return false}});if($.data(event.target,that.widgetName+"-item")===that){currentItem=$(event.target)}if(!currentItem){return false}if(this.options.handle&&!overrideHandle){$(this.options.handle,currentItem).find("*").addBack().each(function(){if(this===event.target){validHandle=true}});if(!validHandle){return false}}this.currentItem=currentItem;this._removeCurrentsFromItems();return true},_mouseStart:function(event,overrideHandle,noActivation){var i,body,o=this.options;this.currentContainer=this;this.refreshPositions();this.helper=this._createHelper(event);this._cacheHelperProportions();this._cacheMargins();this.scrollParent=this.helper.scrollParent();this.offset=this.currentItem.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};$.extend(this.offset,{click:{left:event.pageX-this.offset.left,top:event.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()});this.helper.css("position","absolute");this.cssPosition=this.helper.css("position");this.originalPosition=this._generatePosition(event);this.originalPageX=event.pageX;this.originalPageY=event.pageY;o.cursorAt&&this._adjustOffsetFromHelper(o.cursorAt);this.domPosition={prev:this.currentItem.prev()[0],parent:this.currentItem.parent()[0]};if(this.helper[0]!==this.currentItem[0]){this.currentItem.hide()}this._createPlaceholder();if(o.containment){this._setContainment()}if(o.cursor&&o.cursor!=="auto"){body=this.document.find("body");this.storedCursor=body.css("cursor");body.css("cursor",o.cursor);this.storedStylesheet=$("<style>*{ cursor: "+o.cursor+" !important; }</style>").appendTo(body)}if(o.opacity){if(this.helper.css("opacity")){this._storedOpacity=this.helper.css("opacity")}this.helper.css("opacity",o.opacity)}if(o.zIndex){if(this.helper.css("zIndex")){this._storedZIndex=this.helper.css("zIndex")}this.helper.css("zIndex",o.zIndex)}if(this.scrollParent[0]!==document&&this.scrollParent[0].tagName!=="HTML"){this.overflowOffset=this.scrollParent.offset()}this._trigger("start",event,this._uiHash());if(!this._preserveHelperProportions){this._cacheHelperProportions()}if(!noActivation){for(i=this.containers.length-1;i>=0;i--){this.containers[i]._trigger("activate",event,this._uiHash(this))}}if($.ui.ddmanager){$.ui.ddmanager.current=this}if($.ui.ddmanager&&!o.dropBehaviour){$.ui.ddmanager.prepareOffsets(this,event)}this.dragging=true;this.helper.addClass("ui-sortable-helper");this._mouseDrag(event);return true},_mouseDrag:function(event){var i,item,itemElement,intersection,o=this.options,scrolled=false;this.position=this._generatePosition(event);this.positionAbs=this._convertPositionTo("absolute");if(!this.lastPositionAbs){this.lastPositionAbs=this.positionAbs}if(this.options.scroll){if(this.scrollParent[0]!==document&&this.scrollParent[0].tagName!=="HTML"){if(this.overflowOffset.top+this.scrollParent[0].offsetHeight-event.pageY<o.scrollSensitivity){this.scrollParent[0].scrollTop=scrolled=this.scrollParent[0].scrollTop+o.scrollSpeed}else if(event.pageY-this.overflowOffset.top<o.scrollSensitivity){this.scrollParent[0].scrollTop=scrolled=this.scrollParent[0].scrollTop-o.scrollSpeed}if(this.overflowOffset.left+this.scrollParent[0].offsetWidth-event.pageX<o.scrollSensitivity){this.scrollParent[0].scrollLeft=scrolled=this.scrollParent[0].scrollLeft+o.scrollSpeed}else if(event.pageX-this.overflowOffset.left<o.scrollSensitivity){this.scrollParent[0].scrollLeft=scrolled=this.scrollParent[0].scrollLeft-o.scrollSpeed}}else{if(event.pageY-$(document).scrollTop()<o.scrollSensitivity){scrolled=$(document).scrollTop($(document).scrollTop()-o.scrollSpeed)}else if($(window).height()-(event.pageY-$(document).scrollTop())<o.scrollSensitivity){scrolled=$(document).scrollTop($(document).scrollTop()+o.scrollSpeed)}if(event.pageX-$(document).scrollLeft()<o.scrollSensitivity){scrolled=$(document).scrollLeft($(document).scrollLeft()-o.scrollSpeed)}else if($(window).width()-(event.pageX-$(document).scrollLeft())<o.scrollSensitivity){scrolled=$(document).scrollLeft($(document).scrollLeft()+o.scrollSpeed)}}if(scrolled!==false&&$.ui.ddmanager&&!o.dropBehaviour){$.ui.ddmanager.prepareOffsets(this,event)}}this.positionAbs=this._convertPositionTo("absolute");if(!this.options.axis||this.options.axis!=="y"){this.helper[0].style.left=this.position.left+"px"}if(!this.options.axis||this.options.axis!=="x"){this.helper[0].style.top=this.position.top+"px"}for(i=this.items.length-1;i>=0;i--){item=this.items[i];itemElement=item.item[0];intersection=this._intersectsWithPointer(item);if(!intersection){continue}if(item.instance!==this.currentContainer){continue}if(itemElement!==this.currentItem[0]&&this.placeholder[intersection===1?"next":"prev"]()[0]!==itemElement&&!$.contains(this.placeholder[0],itemElement)&&(this.options.type==="semi-dynamic"?!$.contains(this.element[0],itemElement):true)){this.direction=intersection===1?"down":"up";if(this.options.tolerance==="pointer"||this._intersectsWithSides(item)){this._rearrange(event,item)}else{break}this._trigger("change",event,this._uiHash());break}}this._contactContainers(event);if($.ui.ddmanager){$.ui.ddmanager.drag(this,event)}this._trigger("sort",event,this._uiHash());this.lastPositionAbs=this.positionAbs;return false},_mouseStop:function(event,noPropagation){if(!event){return}if($.ui.ddmanager&&!this.options.dropBehaviour){$.ui.ddmanager.drop(this,event)}if(this.options.revert){var that=this,cur=this.placeholder.offset(),axis=this.options.axis,animation={};if(!axis||axis==="x"){animation.left=cur.left-this.offset.parent.left-this.margins.left+(this.offsetParent[0]===document.body?0:this.offsetParent[0].scrollLeft)}if(!axis||axis==="y"){animation.top=cur.top-this.offset.parent.top-this.margins.top+(this.offsetParent[0]===document.body?0:this.offsetParent[0].scrollTop)}this.reverting=true;$(this.helper).animate(animation,parseInt(this.options.revert,10)||500,function(){that._clear(event)})}else{this._clear(event,noPropagation)}return false},cancel:function(){if(this.dragging){this._mouseUp({target:null});if(this.options.helper==="original"){this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper")}else{this.currentItem.show()}for(var i=this.containers.length-1;i>=0;i--){this.containers[i]._trigger("deactivate",null,this._uiHash(this));if(this.containers[i].containerCache.over){this.containers[i]._trigger("out",null,this._uiHash(this));this.containers[i].containerCache.over=0}}}if(this.placeholder){if(this.placeholder[0].parentNode){this.placeholder[0].parentNode.removeChild(this.placeholder[0])}if(this.options.helper!=="original"&&this.helper&&this.helper[0].parentNode){this.helper.remove()}$.extend(this,{helper:null,dragging:false,reverting:false,_noFinalSort:null});if(this.domPosition.prev){$(this.domPosition.prev).after(this.currentItem)}else{$(this.domPosition.parent).prepend(this.currentItem)}}return this},serialize:function(o){var items=this._getItemsAsjQuery(o&&o.connected),str=[];o=o||{};$(items).each(function(){var res=($(o.item||this).attr(o.attribute||"id")||"").match(o.expression||/(.+)[\-=_](.+)/);if(res){str.push((o.key||res[1]+"[]")+"="+(o.key&&o.expression?res[1]:res[2]))}});if(!str.length&&o.key){str.push(o.key+"=")}return str.join("&")},toArray:function(o){var items=this._getItemsAsjQuery(o&&o.connected),ret=[];o=o||{};items.each(function(){ret.push($(o.item||this).attr(o.attribute||"id")||"")});return ret},_intersectsWith:function(item){var x1=this.positionAbs.left,x2=x1+this.helperProportions.width,y1=this.positionAbs.top,y2=y1+this.helperProportions.height,l=item.left,r=l+item.width,t=item.top,b=t+item.height,dyClick=this.offset.click.top,dxClick=this.offset.click.left,isOverElementHeight=this.options.axis==="x"||y1+dyClick>t&&y1+dyClick<b,isOverElementWidth=this.options.axis==="y"||x1+dxClick>l&&x1+dxClick<r,isOverElement=isOverElementHeight&&isOverElementWidth;if(this.options.tolerance==="pointer"||this.options.forcePointerForContainers||this.options.tolerance!=="pointer"&&this.helperProportions[this.floating?"width":"height"]>item[this.floating?"width":"height"]){return isOverElement}else{return l<x1+this.helperProportions.width/2&&x2-this.helperProportions.width/2<r&&t<y1+this.helperProportions.height/2&&y2-this.helperProportions.height/2<b}},_intersectsWithPointer:function(item){var isOverElementHeight=this.options.axis==="x"||isOverAxis(this.positionAbs.top+this.offset.click.top,item.top,item.height),isOverElementWidth=this.options.axis==="y"||isOverAxis(this.positionAbs.left+this.offset.click.left,item.left,item.width),isOverElement=isOverElementHeight&&isOverElementWidth,verticalDirection=this._getDragVerticalDirection(),horizontalDirection=this._getDragHorizontalDirection();if(!isOverElement){return false}return this.floating?horizontalDirection&&horizontalDirection==="right"||verticalDirection==="down"?2:1:verticalDirection&&(verticalDirection==="down"?2:1)},_intersectsWithSides:function(item){var isOverBottomHalf=isOverAxis(this.positionAbs.top+this.offset.click.top,item.top+item.height/2,item.height),isOverRightHalf=isOverAxis(this.positionAbs.left+this.offset.click.left,item.left+item.width/2,item.width),verticalDirection=this._getDragVerticalDirection(),horizontalDirection=this._getDragHorizontalDirection();if(this.floating&&horizontalDirection){return horizontalDirection==="right"&&isOverRightHalf||horizontalDirection==="left"&&!isOverRightHalf}else{return verticalDirection&&(verticalDirection==="down"&&isOverBottomHalf||verticalDirection==="up"&&!isOverBottomHalf)}},_getDragVerticalDirection:function(){var delta=this.positionAbs.top-this.lastPositionAbs.top;return delta!==0&&(delta>0?"down":"up")},_getDragHorizontalDirection:function(){var delta=this.positionAbs.left-this.lastPositionAbs.left;return delta!==0&&(delta>0?"right":"left")},refresh:function(event){this._refreshItems(event);this.refreshPositions();return this},_connectWith:function(){var options=this.options;return options.connectWith.constructor===String?[options.connectWith]:options.connectWith},_getItemsAsjQuery:function(connected){var i,j,cur,inst,items=[],queries=[],connectWith=this._connectWith();if(connectWith&&connected){for(i=connectWith.length-1;i>=0;i--){cur=$(connectWith[i]);for(j=cur.length-1;j>=0;j--){inst=$.data(cur[j],this.widgetFullName);if(inst&&inst!==this&&!inst.options.disabled){queries.push([$.isFunction(inst.options.items)?inst.options.items.call(inst.element):$(inst.options.items,inst.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),inst])}}}}queries.push([$.isFunction(this.options.items)?this.options.items.call(this.element,null,{options:this.options,item:this.currentItem}):$(this.options.items,this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),this]);function addItems(){items.push(this)}for(i=queries.length-1;i>=0;i--){queries[i][0].each(addItems)}return $(items)},_removeCurrentsFromItems:function(){var list=this.currentItem.find(":data("+this.widgetName+"-item)");this.items=$.grep(this.items,function(item){for(var j=0;j<list.length;j++){if(list[j]===item.item[0]){return false}}return true})},_refreshItems:function(event){this.items=[];this.containers=[this];var i,j,cur,inst,targetData,_queries,item,queriesLength,items=this.items,queries=[[$.isFunction(this.options.items)?this.options.items.call(this.element[0],event,{item:this.currentItem}):$(this.options.items,this.element),this]],connectWith=this._connectWith();if(connectWith&&this.ready){for(i=connectWith.length-1;i>=0;i--){cur=$(connectWith[i]);for(j=cur.length-1;j>=0;j--){inst=$.data(cur[j],this.widgetFullName);if(inst&&inst!==this&&!inst.options.disabled){queries.push([$.isFunction(inst.options.items)?inst.options.items.call(inst.element[0],event,{item:this.currentItem}):$(inst.options.items,inst.element),inst]);this.containers.push(inst)}}}}for(i=queries.length-1;i>=0;i--){targetData=queries[i][1];_queries=queries[i][0];for(j=0,queriesLength=_queries.length;j<queriesLength;j++){item=$(_queries[j]);item.data(this.widgetName+"-item",targetData);items.push({item:item,instance:targetData,width:0,height:0,left:0,top:0})}}},refreshPositions:function(fast){if(this.offsetParent&&this.helper){this.offset.parent=this._getParentOffset()}var i,item,t,p;for(i=this.items.length-1;i>=0;i--){item=this.items[i];if(item.instance!==this.currentContainer&&this.currentContainer&&item.item[0]!==this.currentItem[0]){continue}t=this.options.toleranceElement?$(this.options.toleranceElement,item.item):item.item;
if(!fast){item.width=t.outerWidth();item.height=t.outerHeight()}p=t.offset();item.left=p.left;item.top=p.top}if(this.options.custom&&this.options.custom.refreshContainers){this.options.custom.refreshContainers.call(this)}else{for(i=this.containers.length-1;i>=0;i--){p=this.containers[i].element.offset();this.containers[i].containerCache.left=p.left;this.containers[i].containerCache.top=p.top;this.containers[i].containerCache.width=this.containers[i].element.outerWidth();this.containers[i].containerCache.height=this.containers[i].element.outerHeight()}}return this},_createPlaceholder:function(that){that=that||this;var className,o=that.options;if(!o.placeholder||o.placeholder.constructor===String){className=o.placeholder;o.placeholder={element:function(){var nodeName=that.currentItem[0].nodeName.toLowerCase(),element=$("<"+nodeName+">",that.document[0]).addClass(className||that.currentItem[0].className+" ui-sortable-placeholder").removeClass("ui-sortable-helper");if(nodeName==="tr"){that.currentItem.children().each(function(){$("<td>&#160;</td>",that.document[0]).attr("colspan",$(this).attr("colspan")||1).appendTo(element)})}else if(nodeName==="img"){element.attr("src",that.currentItem.attr("src"))}if(!className){element.css("visibility","hidden")}return element},update:function(container,p){if(className&&!o.forcePlaceholderSize){return}if(!p.height()){p.height(that.currentItem.innerHeight()-parseInt(that.currentItem.css("paddingTop")||0,10)-parseInt(that.currentItem.css("paddingBottom")||0,10))}if(!p.width()){p.width(that.currentItem.innerWidth()-parseInt(that.currentItem.css("paddingLeft")||0,10)-parseInt(that.currentItem.css("paddingRight")||0,10))}}}}that.placeholder=$(o.placeholder.element.call(that.element,that.currentItem));that.currentItem.after(that.placeholder);o.placeholder.update(that,that.placeholder)},_contactContainers:function(event){var i,j,dist,itemWithLeastDistance,posProperty,sizeProperty,base,cur,nearBottom,floating,innermostContainer=null,innermostIndex=null;for(i=this.containers.length-1;i>=0;i--){if($.contains(this.currentItem[0],this.containers[i].element[0])){continue}if(this._intersectsWith(this.containers[i].containerCache)){if(innermostContainer&&$.contains(this.containers[i].element[0],innermostContainer.element[0])){continue}innermostContainer=this.containers[i];innermostIndex=i}else{if(this.containers[i].containerCache.over){this.containers[i]._trigger("out",event,this._uiHash(this));this.containers[i].containerCache.over=0}}}if(!innermostContainer){return}if(this.containers.length===1){if(!this.containers[innermostIndex].containerCache.over){this.containers[innermostIndex]._trigger("over",event,this._uiHash(this));this.containers[innermostIndex].containerCache.over=1}}else{dist=1e4;itemWithLeastDistance=null;floating=innermostContainer.floating||isFloating(this.currentItem);posProperty=floating?"left":"top";sizeProperty=floating?"width":"height";base=this.positionAbs[posProperty]+this.offset.click[posProperty];for(j=this.items.length-1;j>=0;j--){if(!$.contains(this.containers[innermostIndex].element[0],this.items[j].item[0])){continue}if(this.items[j].item[0]===this.currentItem[0]){continue}if(floating&&!isOverAxis(this.positionAbs.top+this.offset.click.top,this.items[j].top,this.items[j].height)){continue}cur=this.items[j].item.offset()[posProperty];nearBottom=false;if(Math.abs(cur-base)>Math.abs(cur+this.items[j][sizeProperty]-base)){nearBottom=true;cur+=this.items[j][sizeProperty]}if(Math.abs(cur-base)<dist){dist=Math.abs(cur-base);itemWithLeastDistance=this.items[j];this.direction=nearBottom?"up":"down"}}if(!itemWithLeastDistance&&!this.options.dropOnEmpty){return}if(this.currentContainer===this.containers[innermostIndex]){return}itemWithLeastDistance?this._rearrange(event,itemWithLeastDistance,null,true):this._rearrange(event,null,this.containers[innermostIndex].element,true);this._trigger("change",event,this._uiHash());this.containers[innermostIndex]._trigger("change",event,this._uiHash(this));this.currentContainer=this.containers[innermostIndex];this.options.placeholder.update(this.currentContainer,this.placeholder);this.containers[innermostIndex]._trigger("over",event,this._uiHash(this));this.containers[innermostIndex].containerCache.over=1}},_createHelper:function(event){var o=this.options,helper=$.isFunction(o.helper)?$(o.helper.apply(this.element[0],[event,this.currentItem])):o.helper==="clone"?this.currentItem.clone():this.currentItem;if(!helper.parents("body").length){$(o.appendTo!=="parent"?o.appendTo:this.currentItem[0].parentNode)[0].appendChild(helper[0])}if(helper[0]===this.currentItem[0]){this._storedCSS={width:this.currentItem[0].style.width,height:this.currentItem[0].style.height,position:this.currentItem.css("position"),top:this.currentItem.css("top"),left:this.currentItem.css("left")}}if(!helper[0].style.width||o.forceHelperSize){helper.width(this.currentItem.width())}if(!helper[0].style.height||o.forceHelperSize){helper.height(this.currentItem.height())}return helper},_adjustOffsetFromHelper:function(obj){if(typeof obj==="string"){obj=obj.split(" ")}if($.isArray(obj)){obj={left:+obj[0],top:+obj[1]||0}}if("left"in obj){this.offset.click.left=obj.left+this.margins.left}if("right"in obj){this.offset.click.left=this.helperProportions.width-obj.right+this.margins.left}if("top"in obj){this.offset.click.top=obj.top+this.margins.top}if("bottom"in obj){this.offset.click.top=this.helperProportions.height-obj.bottom+this.margins.top}},_getParentOffset:function(){this.offsetParent=this.helper.offsetParent();var po=this.offsetParent.offset();if(this.cssPosition==="absolute"&&this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0])){po.left+=this.scrollParent.scrollLeft();po.top+=this.scrollParent.scrollTop()}if(this.offsetParent[0]===document.body||this.offsetParent[0].tagName&&this.offsetParent[0].tagName.toLowerCase()==="html"&&$.ui.ie){po={top:0,left:0}}return{top:po.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:po.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if(this.cssPosition==="relative"){var p=this.currentItem.position();return{top:p.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:p.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}else{return{top:0,left:0}}},_cacheMargins:function(){this.margins={left:parseInt(this.currentItem.css("marginLeft"),10)||0,top:parseInt(this.currentItem.css("marginTop"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var ce,co,over,o=this.options;if(o.containment==="parent"){o.containment=this.helper[0].parentNode}if(o.containment==="document"||o.containment==="window"){this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,$(o.containment==="document"?document:window).width()-this.helperProportions.width-this.margins.left,($(o.containment==="document"?document:window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top]}if(!/^(document|window|parent)$/.test(o.containment)){ce=$(o.containment)[0];co=$(o.containment).offset();over=$(ce).css("overflow")!=="hidden";this.containment=[co.left+(parseInt($(ce).css("borderLeftWidth"),10)||0)+(parseInt($(ce).css("paddingLeft"),10)||0)-this.margins.left,co.top+(parseInt($(ce).css("borderTopWidth"),10)||0)+(parseInt($(ce).css("paddingTop"),10)||0)-this.margins.top,co.left+(over?Math.max(ce.scrollWidth,ce.offsetWidth):ce.offsetWidth)-(parseInt($(ce).css("borderLeftWidth"),10)||0)-(parseInt($(ce).css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left,co.top+(over?Math.max(ce.scrollHeight,ce.offsetHeight):ce.offsetHeight)-(parseInt($(ce).css("borderTopWidth"),10)||0)-(parseInt($(ce).css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top]}},_convertPositionTo:function(d,pos){if(!pos){pos=this.position}var mod=d==="absolute"?1:-1,scroll=this.cssPosition==="absolute"&&!(this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=/(html|body)/i.test(scroll[0].tagName);return{top:pos.top+this.offset.relative.top*mod+this.offset.parent.top*mod-(this.cssPosition==="fixed"?-this.scrollParent.scrollTop():scrollIsRootNode?0:scroll.scrollTop())*mod,left:pos.left+this.offset.relative.left*mod+this.offset.parent.left*mod-(this.cssPosition==="fixed"?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft())*mod}},_generatePosition:function(event){var top,left,o=this.options,pageX=event.pageX,pageY=event.pageY,scroll=this.cssPosition==="absolute"&&!(this.scrollParent[0]!==document&&$.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=/(html|body)/i.test(scroll[0].tagName);if(this.cssPosition==="relative"&&!(this.scrollParent[0]!==document&&this.scrollParent[0]!==this.offsetParent[0])){this.offset.relative=this._getRelativeOffset()}if(this.originalPosition){if(this.containment){if(event.pageX-this.offset.click.left<this.containment[0]){pageX=this.containment[0]+this.offset.click.left}if(event.pageY-this.offset.click.top<this.containment[1]){pageY=this.containment[1]+this.offset.click.top}if(event.pageX-this.offset.click.left>this.containment[2]){pageX=this.containment[2]+this.offset.click.left}if(event.pageY-this.offset.click.top>this.containment[3]){pageY=this.containment[3]+this.offset.click.top}}if(o.grid){top=this.originalPageY+Math.round((pageY-this.originalPageY)/o.grid[1])*o.grid[1];pageY=this.containment?top-this.offset.click.top>=this.containment[1]&&top-this.offset.click.top<=this.containment[3]?top:top-this.offset.click.top>=this.containment[1]?top-o.grid[1]:top+o.grid[1]:top;left=this.originalPageX+Math.round((pageX-this.originalPageX)/o.grid[0])*o.grid[0];pageX=this.containment?left-this.offset.click.left>=this.containment[0]&&left-this.offset.click.left<=this.containment[2]?left:left-this.offset.click.left>=this.containment[0]?left-o.grid[0]:left+o.grid[0]:left}}return{top:pageY-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+(this.cssPosition==="fixed"?-this.scrollParent.scrollTop():scrollIsRootNode?0:scroll.scrollTop()),left:pageX-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+(this.cssPosition==="fixed"?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft())}},_rearrange:function(event,i,a,hardRefresh){a?a[0].appendChild(this.placeholder[0]):i.item[0].parentNode.insertBefore(this.placeholder[0],this.direction==="down"?i.item[0]:i.item[0].nextSibling);this.counter=this.counter?++this.counter:1;var counter=this.counter;this._delay(function(){if(counter===this.counter){this.refreshPositions(!hardRefresh)}})},_clear:function(event,noPropagation){this.reverting=false;var i,delayedTriggers=[];if(!this._noFinalSort&&this.currentItem.parent().length){this.placeholder.before(this.currentItem)}this._noFinalSort=null;if(this.helper[0]===this.currentItem[0]){for(i in this._storedCSS){if(this._storedCSS[i]==="auto"||this._storedCSS[i]==="static"){this._storedCSS[i]=""}}this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper")}else{this.currentItem.show()}if(this.fromOutside&&!noPropagation){delayedTriggers.push(function(event){this._trigger("receive",event,this._uiHash(this.fromOutside))})}if((this.fromOutside||this.domPosition.prev!==this.currentItem.prev().not(".ui-sortable-helper")[0]||this.domPosition.parent!==this.currentItem.parent()[0])&&!noPropagation){delayedTriggers.push(function(event){this._trigger("update",event,this._uiHash())})}if(this!==this.currentContainer){if(!noPropagation){delayedTriggers.push(function(event){this._trigger("remove",event,this._uiHash())});delayedTriggers.push(function(c){return function(event){c._trigger("receive",event,this._uiHash(this))}}.call(this,this.currentContainer));delayedTriggers.push(function(c){return function(event){c._trigger("update",event,this._uiHash(this))}}.call(this,this.currentContainer))}}function delayEvent(type,instance,container){return function(event){container._trigger(type,event,instance._uiHash(instance))}}for(i=this.containers.length-1;i>=0;i--){if(!noPropagation){delayedTriggers.push(delayEvent("deactivate",this,this.containers[i]))}if(this.containers[i].containerCache.over){delayedTriggers.push(delayEvent("out",this,this.containers[i]));this.containers[i].containerCache.over=0}}if(this.storedCursor){this.document.find("body").css("cursor",this.storedCursor);this.storedStylesheet.remove()}if(this._storedOpacity){this.helper.css("opacity",this._storedOpacity)}if(this._storedZIndex){this.helper.css("zIndex",this._storedZIndex==="auto"?"":this._storedZIndex)}this.dragging=false;if(this.cancelHelperRemoval){if(!noPropagation){this._trigger("beforeStop",event,this._uiHash());for(i=0;i<delayedTriggers.length;i++){delayedTriggers[i].call(this,event)}this._trigger("stop",event,this._uiHash())}this.fromOutside=false;return false}if(!noPropagation){this._trigger("beforeStop",event,this._uiHash())}this.placeholder[0].parentNode.removeChild(this.placeholder[0]);if(this.helper[0]!==this.currentItem[0]){this.helper.remove()}this.helper=null;if(!noPropagation){for(i=0;i<delayedTriggers.length;i++){delayedTriggers[i].call(this,event)}this._trigger("stop",event,this._uiHash())}this.fromOutside=false;return true},_trigger:function(){if($.Widget.prototype._trigger.apply(this,arguments)===false){this.cancel()}},_uiHash:function(_inst){var inst=_inst||this;return{helper:inst.helper,placeholder:inst.placeholder||$([]),position:inst.position,originalPosition:inst.originalPosition,offset:inst.positionAbs,item:inst.currentItem,sender:_inst?_inst.element:null}}})})(jQuery);(function($,undefined){$.widget("ui.autocomplete",{version:"1.10.4",defaultElement:"<input>",options:{appendTo:null,autoFocus:false,delay:300,minLength:1,position:{my:"left top",at:"left bottom",collision:"none"},source:null,change:null,close:null,focus:null,open:null,response:null,search:null,select:null},requestIndex:0,pending:0,_create:function(){var suppressKeyPress,suppressKeyPressRepeat,suppressInput,nodeName=this.element[0].nodeName.toLowerCase(),isTextarea=nodeName==="textarea",isInput=nodeName==="input";this.isMultiLine=isTextarea?true:isInput?false:this.element.prop("isContentEditable");this.valueMethod=this.element[isTextarea||isInput?"val":"text"];this.isNewMenu=true;this.element.addClass("ui-autocomplete-input").attr("autocomplete","off");this._on(this.element,{keydown:function(event){if(this.element.prop("readOnly")){suppressKeyPress=true;suppressInput=true;suppressKeyPressRepeat=true;return}suppressKeyPress=false;suppressInput=false;suppressKeyPressRepeat=false;var keyCode=$.ui.keyCode;switch(event.keyCode){case keyCode.PAGE_UP:suppressKeyPress=true;this._move("previousPage",event);break;case keyCode.PAGE_DOWN:suppressKeyPress=true;this._move("nextPage",event);break;case keyCode.UP:suppressKeyPress=true;this._keyEvent("previous",event);break;case keyCode.DOWN:suppressKeyPress=true;this._keyEvent("next",event);break;case keyCode.ENTER:case keyCode.NUMPAD_ENTER:if(this.menu.active){suppressKeyPress=true;event.preventDefault();this.menu.select(event)}break;case keyCode.TAB:if(this.menu.active){this.menu.select(event)}break;case keyCode.ESCAPE:if(this.menu.element.is(":visible")){this._value(this.term);this.close(event);event.preventDefault()}break;default:suppressKeyPressRepeat=true;this._searchTimeout(event);break}},keypress:function(event){if(suppressKeyPress){suppressKeyPress=false;if(!this.isMultiLine||this.menu.element.is(":visible")){event.preventDefault()}return}if(suppressKeyPressRepeat){return}var keyCode=$.ui.keyCode;switch(event.keyCode){case keyCode.PAGE_UP:this._move("previousPage",event);break;case keyCode.PAGE_DOWN:this._move("nextPage",event);break;case keyCode.UP:this._keyEvent("previous",event);break;case keyCode.DOWN:this._keyEvent("next",event);break}},input:function(event){if(suppressInput){suppressInput=false;event.preventDefault();return}this._searchTimeout(event)},focus:function(){this.selectedItem=null;this.previous=this._value()},blur:function(event){if(this.cancelBlur){delete this.cancelBlur;return}clearTimeout(this.searching);this.close(event);this._change(event)}});this._initSource();this.menu=$("<ul>").addClass("ui-autocomplete ui-front").appendTo(this._appendTo()).menu({role:null}).hide().data("ui-menu");this._on(this.menu.element,{mousedown:function(event){event.preventDefault();this.cancelBlur=true;this._delay(function(){delete this.cancelBlur});var menuElement=this.menu.element[0];if(!$(event.target).closest(".ui-menu-item").length){this._delay(function(){var that=this;this.document.one("mousedown",function(event){if(event.target!==that.element[0]&&event.target!==menuElement&&!$.contains(menuElement,event.target)){that.close()}})})}},menufocus:function(event,ui){if(this.isNewMenu){this.isNewMenu=false;if(event.originalEvent&&/^mouse/.test(event.originalEvent.type)){this.menu.blur();this.document.one("mousemove",function(){$(event.target).trigger(event.originalEvent)});return}}var item=ui.item.data("ui-autocomplete-item");if(false!==this._trigger("focus",event,{item:item})){if(event.originalEvent&&/^key/.test(event.originalEvent.type)){this._value(item.value)}}else{this.liveRegion.text(item.value)}},menuselect:function(event,ui){var item=ui.item.data("ui-autocomplete-item"),previous=this.previous;if(this.element[0]!==this.document[0].activeElement){this.element.focus();this.previous=previous;this._delay(function(){this.previous=previous;this.selectedItem=item})}if(false!==this._trigger("select",event,{item:item})){this._value(item.value)}this.term=this._value();this.close(event);this.selectedItem=item}});this.liveRegion=$("<span>",{role:"status","aria-live":"polite"}).addClass("ui-helper-hidden-accessible").insertBefore(this.element);this._on(this.window,{beforeunload:function(){this.element.removeAttr("autocomplete")}})},_destroy:function(){clearTimeout(this.searching);this.element.removeClass("ui-autocomplete-input").removeAttr("autocomplete");this.menu.element.remove();this.liveRegion.remove()},_setOption:function(key,value){this._super(key,value);if(key==="source"){this._initSource()}if(key==="appendTo"){this.menu.element.appendTo(this._appendTo())}if(key==="disabled"&&value&&this.xhr){this.xhr.abort()}},_appendTo:function(){var element=this.options.appendTo;if(element){element=element.jquery||element.nodeType?$(element):this.document.find(element).eq(0)}if(!element){element=this.element.closest(".ui-front")}if(!element.length){element=this.document[0].body}return element},_initSource:function(){var array,url,that=this;if($.isArray(this.options.source)){array=this.options.source;this.source=function(request,response){response($.ui.autocomplete.filter(array,request.term))}}else if(typeof this.options.source==="string"){url=this.options.source;this.source=function(request,response){if(that.xhr){that.xhr.abort()}that.xhr=$.ajax({url:url,data:request,dataType:"json",success:function(data){response(data)},error:function(){response([])}})}}else{this.source=this.options.source}},_searchTimeout:function(event){clearTimeout(this.searching);this.searching=this._delay(function(){if(this.term!==this._value()){this.selectedItem=null;this.search(null,event)}},this.options.delay)},search:function(value,event){value=value!=null?value:this._value();this.term=this._value();if(value.length<this.options.minLength){return this.close(event)}if(this._trigger("search",event)===false){return}return this._search(value)},_search:function(value){this.pending++;this.element.addClass("ui-autocomplete-loading");this.cancelSearch=false;this.source({term:value},this._response())},_response:function(){var index=++this.requestIndex;return $.proxy(function(content){if(index===this.requestIndex){this.__response(content)}this.pending--;if(!this.pending){this.element.removeClass("ui-autocomplete-loading")}},this)},__response:function(content){if(content){content=this._normalize(content)}this._trigger("response",null,{content:content});if(!this.options.disabled&&content&&content.length&&!this.cancelSearch){this._suggest(content);this._trigger("open")}else{this._close()}},close:function(event){this.cancelSearch=true;this._close(event)},_close:function(event){if(this.menu.element.is(":visible")){this.menu.element.hide();this.menu.blur();this.isNewMenu=true;this._trigger("close",event)}},_change:function(event){if(this.previous!==this._value()){this._trigger("change",event,{item:this.selectedItem})}},_normalize:function(items){if(items.length&&items[0].label&&items[0].value){return items}return $.map(items,function(item){if(typeof item==="string"){return{label:item,value:item}}return $.extend({label:item.label||item.value,value:item.value||item.label},item)})},_suggest:function(items){var ul=this.menu.element.empty();this._renderMenu(ul,items);this.isNewMenu=true;this.menu.refresh();ul.show();this._resizeMenu();ul.position($.extend({of:this.element},this.options.position));if(this.options.autoFocus){this.menu.next()}},_resizeMenu:function(){var ul=this.menu.element;ul.outerWidth(Math.max(ul.width("").outerWidth()+1,this.element.outerWidth()))},_renderMenu:function(ul,items){var that=this;$.each(items,function(index,item){that._renderItemData(ul,item)})},_renderItemData:function(ul,item){return this._renderItem(ul,item).data("ui-autocomplete-item",item)},_renderItem:function(ul,item){return $("<li>").append($("<a>").text(item.label)).appendTo(ul)},_move:function(direction,event){if(!this.menu.element.is(":visible")){this.search(null,event);return}if(this.menu.isFirstItem()&&/^previous/.test(direction)||this.menu.isLastItem()&&/^next/.test(direction)){this._value(this.term);this.menu.blur();return}this.menu[direction](event)},widget:function(){return this.menu.element},_value:function(){return this.valueMethod.apply(this.element,arguments)},_keyEvent:function(keyEvent,event){if(!this.isMultiLine||this.menu.element.is(":visible")){this._move(keyEvent,event);event.preventDefault()}}});$.extend($.ui.autocomplete,{escapeRegex:function(value){return value.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&")},filter:function(array,term){var matcher=new RegExp($.ui.autocomplete.escapeRegex(term),"i");return $.grep(array,function(value){return matcher.test(value.label||value.value||value)})}});$.widget("ui.autocomplete",$.ui.autocomplete,{options:{messages:{noResults:"No search results.",results:function(amount){return amount+(amount>1?" results are":" result is")+" available, use up and down arrow keys to navigate."}}},__response:function(content){var message;this._superApply(arguments);if(this.options.disabled||this.cancelSearch){return}if(content&&content.length){message=this.options.messages.results(content.length)}else{message=this.options.messages.noResults}this.liveRegion.text(message)}})})(jQuery);(function($,undefined){$.widget("ui.menu",{version:"1.10.4",defaultElement:"<ul>",delay:300,options:{icons:{submenu:"ui-icon-carat-1-e"},menus:"ul",position:{my:"left top",at:"right top"},role:"menu",blur:null,focus:null,select:null},_create:function(){this.activeMenu=this.element;this.mouseHandled=false;this.element.uniqueId().addClass("ui-menu ui-widget ui-widget-content ui-corner-all").toggleClass("ui-menu-icons",!!this.element.find(".ui-icon").length).attr({role:this.options.role,tabIndex:0}).bind("click"+this.eventNamespace,$.proxy(function(event){if(this.options.disabled){event.preventDefault()}},this));if(this.options.disabled){this.element.addClass("ui-state-disabled").attr("aria-disabled","true")}this._on({"mousedown .ui-menu-item > a":function(event){event.preventDefault()},"click .ui-state-disabled > a":function(event){event.preventDefault()},"click .ui-menu-item:has(a)":function(event){var target=$(event.target).closest(".ui-menu-item");if(!this.mouseHandled&&target.not(".ui-state-disabled").length){this.select(event);if(!event.isPropagationStopped()){this.mouseHandled=true}if(target.has(".ui-menu").length){this.expand(event)}else if(!this.element.is(":focus")&&$(this.document[0].activeElement).closest(".ui-menu").length){this.element.trigger("focus",[true]);if(this.active&&this.active.parents(".ui-menu").length===1){clearTimeout(this.timer)}}}},"mouseenter .ui-menu-item":function(event){var target=$(event.currentTarget);target.siblings().children(".ui-state-active").removeClass("ui-state-active");this.focus(event,target)},mouseleave:"collapseAll","mouseleave .ui-menu":"collapseAll",focus:function(event,keepActiveItem){var item=this.active||this.element.children(".ui-menu-item").eq(0);if(!keepActiveItem){this.focus(event,item)}},blur:function(event){this._delay(function(){if(!$.contains(this.element[0],this.document[0].activeElement)){this.collapseAll(event)}})},keydown:"_keydown"});this.refresh();this._on(this.document,{click:function(event){if(!$(event.target).closest(".ui-menu").length){this.collapseAll(event)}this.mouseHandled=false}})},_destroy:function(){this.element.removeAttr("aria-activedescendant").find(".ui-menu").addBack().removeClass("ui-menu ui-widget ui-widget-content ui-corner-all ui-menu-icons").removeAttr("role").removeAttr("tabIndex").removeAttr("aria-labelledby").removeAttr("aria-expanded").removeAttr("aria-hidden").removeAttr("aria-disabled").removeUniqueId().show();this.element.find(".ui-menu-item").removeClass("ui-menu-item").removeAttr("role").removeAttr("aria-disabled").children("a").removeUniqueId().removeClass("ui-corner-all ui-state-hover").removeAttr("tabIndex").removeAttr("role").removeAttr("aria-haspopup").children().each(function(){var elem=$(this);if(elem.data("ui-menu-submenu-carat")){elem.remove()}});this.element.find(".ui-menu-divider").removeClass("ui-menu-divider ui-widget-content")},_keydown:function(event){var match,prev,character,skip,regex,preventDefault=true;function escape(value){return value.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&")}switch(event.keyCode){case $.ui.keyCode.PAGE_UP:this.previousPage(event);break;case $.ui.keyCode.PAGE_DOWN:this.nextPage(event);break;case $.ui.keyCode.HOME:this._move("first","first",event);break;case $.ui.keyCode.END:this._move("last","last",event);break;case $.ui.keyCode.UP:this.previous(event);break;case $.ui.keyCode.DOWN:this.next(event);break;case $.ui.keyCode.LEFT:this.collapse(event);break;case $.ui.keyCode.RIGHT:if(this.active&&!this.active.is(".ui-state-disabled")){this.expand(event)}break;case $.ui.keyCode.ENTER:case $.ui.keyCode.SPACE:this._activate(event);break;case $.ui.keyCode.ESCAPE:this.collapse(event);break;default:preventDefault=false;prev=this.previousFilter||"";character=String.fromCharCode(event.keyCode);skip=false;clearTimeout(this.filterTimer);if(character===prev){skip=true}else{character=prev+character}regex=new RegExp("^"+escape(character),"i");match=this.activeMenu.children(".ui-menu-item").filter(function(){return regex.test($(this).children("a").text())});match=skip&&match.index(this.active.next())!==-1?this.active.nextAll(".ui-menu-item"):match;if(!match.length){character=String.fromCharCode(event.keyCode);regex=new RegExp("^"+escape(character),"i");match=this.activeMenu.children(".ui-menu-item").filter(function(){return regex.test($(this).children("a").text())})}if(match.length){this.focus(event,match);if(match.length>1){this.previousFilter=character;this.filterTimer=this._delay(function(){delete this.previousFilter},1e3)}else{delete this.previousFilter}}else{delete this.previousFilter}}if(preventDefault){event.preventDefault()}},_activate:function(event){if(!this.active.is(".ui-state-disabled")){if(this.active.children("a[aria-haspopup='true']").length){this.expand(event)}else{this.select(event)}}},refresh:function(){var menus,icon=this.options.icons.submenu,submenus=this.element.find(this.options.menus);this.element.toggleClass("ui-menu-icons",!!this.element.find(".ui-icon").length);submenus.filter(":not(.ui-menu)").addClass("ui-menu ui-widget ui-widget-content ui-corner-all").hide().attr({role:this.options.role,"aria-hidden":"true","aria-expanded":"false"}).each(function(){var menu=$(this),item=menu.prev("a"),submenuCarat=$("<span>").addClass("ui-menu-icon ui-icon "+icon).data("ui-menu-submenu-carat",true);item.attr("aria-haspopup","true").prepend(submenuCarat);menu.attr("aria-labelledby",item.attr("id"))});menus=submenus.add(this.element);menus.children(":not(.ui-menu-item):has(a)").addClass("ui-menu-item").attr("role","presentation").children("a").uniqueId().addClass("ui-corner-all").attr({tabIndex:-1,role:this._itemRole()});menus.children(":not(.ui-menu-item)").each(function(){var item=$(this);if(!/[^\-\u2014\u2013\s]/.test(item.text())){item.addClass("ui-widget-content ui-menu-divider")}});menus.children(".ui-state-disabled").attr("aria-disabled","true");if(this.active&&!$.contains(this.element[0],this.active[0])){this.blur()}},_itemRole:function(){return{menu:"menuitem",listbox:"option"}[this.options.role]},_setOption:function(key,value){if(key==="icons"){this.element.find(".ui-menu-icon").removeClass(this.options.icons.submenu).addClass(value.submenu)}this._super(key,value)},focus:function(event,item){var nested,focused;this.blur(event,event&&event.type==="focus");this._scrollIntoView(item);this.active=item.first();focused=this.active.children("a").addClass("ui-state-focus");if(this.options.role){this.element.attr("aria-activedescendant",focused.attr("id"))}this.active.parent().closest(".ui-menu-item").children("a:first").addClass("ui-state-active");if(event&&event.type==="keydown"){this._close()}else{this.timer=this._delay(function(){this._close()},this.delay)}nested=item.children(".ui-menu");if(nested.length&&event&&/^mouse/.test(event.type)){this._startOpening(nested)}this.activeMenu=item.parent();this._trigger("focus",event,{item:item})},_scrollIntoView:function(item){var borderTop,paddingTop,offset,scroll,elementHeight,itemHeight;if(this._hasScroll()){borderTop=parseFloat($.css(this.activeMenu[0],"borderTopWidth"))||0;paddingTop=parseFloat($.css(this.activeMenu[0],"paddingTop"))||0;offset=item.offset().top-this.activeMenu.offset().top-borderTop-paddingTop;scroll=this.activeMenu.scrollTop();elementHeight=this.activeMenu.height();itemHeight=item.height();if(offset<0){this.activeMenu.scrollTop(scroll+offset)}else if(offset+itemHeight>elementHeight){this.activeMenu.scrollTop(scroll+offset-elementHeight+itemHeight)}}},blur:function(event,fromFocus){if(!fromFocus){clearTimeout(this.timer)}if(!this.active){return}this.active.children("a").removeClass("ui-state-focus");this.active=null;this._trigger("blur",event,{item:this.active})},_startOpening:function(submenu){clearTimeout(this.timer);if(submenu.attr("aria-hidden")!=="true"){return}this.timer=this._delay(function(){this._close();this._open(submenu)},this.delay)},_open:function(submenu){var position=$.extend({of:this.active},this.options.position);clearTimeout(this.timer);this.element.find(".ui-menu").not(submenu.parents(".ui-menu")).hide().attr("aria-hidden","true");submenu.show().removeAttr("aria-hidden").attr("aria-expanded","true").position(position)},collapseAll:function(event,all){clearTimeout(this.timer);this.timer=this._delay(function(){var currentMenu=all?this.element:$(event&&event.target).closest(this.element.find(".ui-menu"));if(!currentMenu.length){currentMenu=this.element}this._close(currentMenu);this.blur(event);this.activeMenu=currentMenu},this.delay)},_close:function(startMenu){if(!startMenu){startMenu=this.active?this.active.parent():this.element}startMenu.find(".ui-menu").hide().attr("aria-hidden","true").attr("aria-expanded","false").end().find("a.ui-state-active").removeClass("ui-state-active")},collapse:function(event){var newItem=this.active&&this.active.parent().closest(".ui-menu-item",this.element);if(newItem&&newItem.length){this._close();
this.focus(event,newItem)}},expand:function(event){var newItem=this.active&&this.active.children(".ui-menu ").children(".ui-menu-item").first();if(newItem&&newItem.length){this._open(newItem.parent());this._delay(function(){this.focus(event,newItem)})}},next:function(event){this._move("next","first",event)},previous:function(event){this._move("prev","last",event)},isFirstItem:function(){return this.active&&!this.active.prevAll(".ui-menu-item").length},isLastItem:function(){return this.active&&!this.active.nextAll(".ui-menu-item").length},_move:function(direction,filter,event){var next;if(this.active){if(direction==="first"||direction==="last"){next=this.active[direction==="first"?"prevAll":"nextAll"](".ui-menu-item").eq(-1)}else{next=this.active[direction+"All"](".ui-menu-item").eq(0)}}if(!next||!next.length||!this.active){next=this.activeMenu.children(".ui-menu-item")[filter]()}this.focus(event,next)},nextPage:function(event){var item,base,height;if(!this.active){this.next(event);return}if(this.isLastItem()){return}if(this._hasScroll()){base=this.active.offset().top;height=this.element.height();this.active.nextAll(".ui-menu-item").each(function(){item=$(this);return item.offset().top-base-height<0});this.focus(event,item)}else{this.focus(event,this.activeMenu.children(".ui-menu-item")[!this.active?"first":"last"]())}},previousPage:function(event){var item,base,height;if(!this.active){this.next(event);return}if(this.isFirstItem()){return}if(this._hasScroll()){base=this.active.offset().top;height=this.element.height();this.active.prevAll(".ui-menu-item").each(function(){item=$(this);return item.offset().top-base+height>0});this.focus(event,item)}else{this.focus(event,this.activeMenu.children(".ui-menu-item").first())}},_hasScroll:function(){return this.element.outerHeight()<this.element.prop("scrollHeight")},select:function(event){this.active=this.active||$(event.target).closest(".ui-menu-item");var ui={item:this.active};if(!this.active.has(".ui-menu").length){this.collapseAll(event,true)}this._trigger("select",event,ui)}})})(jQuery);
if(typeof jQuery==="undefined"){throw new Error("Bootstrap requires jQuery")}+function($){"use strict";function transitionEnd(){var el=document.createElement("bootstrap");var transEndEventNames={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var name in transEndEventNames){if(el.style[name]!==undefined){return{end:transEndEventNames[name]}}}}$.fn.emulateTransitionEnd=function(duration){var called=false,$el=this;$(this).one($.support.transition.end,function(){called=true});var callback=function(){if(!called)$($el).trigger($.support.transition.end)};setTimeout(callback,duration);return this};$(function(){$.support.transition=transitionEnd()})}(jQuery);+function($){"use strict";var dismiss='[data-dismiss="alert"]';var Alert=function(el){$(el).on("click",dismiss,this.close)};Alert.prototype.close=function(e){var $this=$(this);var selector=$this.attr("data-target");if(!selector){selector=$this.attr("href");selector=selector&&selector.replace(/.*(?=#[^\s]*$)/,"")}var $parent=$(selector);if(e)e.preventDefault();if(!$parent.length){$parent=$this.hasClass("alert")?$this:$this.parent()}$parent.trigger(e=$.Event("close.bs.alert"));if(e.isDefaultPrevented())return;$parent.removeClass("in");function removeElement(){$parent.trigger("closed.bs.alert").remove()}$.support.transition&&$parent.hasClass("fade")?$parent.one($.support.transition.end,removeElement).emulateTransitionEnd(150):removeElement()};var old=$.fn.alert;$.fn.alert=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.alert");if(!data)$this.data("bs.alert",data=new Alert(this));if(typeof option=="string")data[option].call($this)})};$.fn.alert.Constructor=Alert;$.fn.alert.noConflict=function(){$.fn.alert=old;return this};$(document).on("click.bs.alert.data-api",dismiss,Alert.prototype.close)}(jQuery);+function($){"use strict";var Button=function(element,options){this.$element=$(element);this.options=$.extend({},Button.DEFAULTS,options)};Button.DEFAULTS={loadingText:"loading..."};Button.prototype.setState=function(state){var d="disabled";var $el=this.$element;var val=$el.is("input")?"val":"html";var data=$el.data();state=state+"Text";if(!data.resetText)$el.data("resetText",$el[val]());$el[val](data[state]||this.options[state]);setTimeout(function(){state=="loadingText"?$el.addClass(d).attr(d,d):$el.removeClass(d).removeAttr(d)},0)};Button.prototype.toggle=function(){var $parent=this.$element.closest('[data-toggle="buttons"]');var changed=true;if($parent.length){var $input=this.$element.find("input");if($input.prop("type")==="radio"){if($input.prop("checked")&&this.$element.hasClass("active"))changed=false;else $parent.find(".active").removeClass("active")}if(changed)$input.prop("checked",!this.$element.hasClass("active")).trigger("change")}if(changed)this.$element.toggleClass("active")};var old=$.fn.button;$.fn.button=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.button");var options=typeof option=="object"&&option;if(!data)$this.data("bs.button",data=new Button(this,options));if(option=="toggle")data.toggle();else if(option)data.setState(option)})};$.fn.button.Constructor=Button;$.fn.button.noConflict=function(){$.fn.button=old;return this};$(document).on("click.bs.button.data-api","[data-toggle^=button]",function(e){var $btn=$(e.target);if(!$btn.hasClass("btn"))$btn=$btn.closest(".btn");$btn.button("toggle");e.preventDefault()})}(jQuery);+function($){"use strict";var Carousel=function(element,options){this.$element=$(element);this.$indicators=this.$element.find(".carousel-indicators");this.options=options;this.paused=this.sliding=this.interval=this.$active=this.$items=null;this.options.pause=="hover"&&this.$element.on("mouseenter",$.proxy(this.pause,this)).on("mouseleave",$.proxy(this.cycle,this))};Carousel.DEFAULTS={interval:5e3,pause:"hover",wrap:true};Carousel.prototype.cycle=function(e){e||(this.paused=false);this.interval&&clearInterval(this.interval);this.options.interval&&!this.paused&&(this.interval=setInterval($.proxy(this.next,this),this.options.interval));return this};Carousel.prototype.getActiveIndex=function(){this.$active=this.$element.find(".item.active");this.$items=this.$active.parent().children();return this.$items.index(this.$active)};Carousel.prototype.to=function(pos){var that=this;var activeIndex=this.getActiveIndex();if(pos>this.$items.length-1||pos<0)return;if(this.sliding)return this.$element.one("slid.bs.carousel",function(){that.to(pos)});if(activeIndex==pos)return this.pause().cycle();return this.slide(pos>activeIndex?"next":"prev",$(this.$items[pos]))};Carousel.prototype.pause=function(e){e||(this.paused=true);if(this.$element.find(".next, .prev").length&&$.support.transition.end){this.$element.trigger($.support.transition.end);this.cycle(true)}this.interval=clearInterval(this.interval);return this};Carousel.prototype.next=function(){if(this.sliding)return;return this.slide("next")};Carousel.prototype.prev=function(){if(this.sliding)return;return this.slide("prev")};Carousel.prototype.slide=function(type,next){var $active=this.$element.find(".item.active");var $next=next||$active[type]();var isCycling=this.interval;var direction=type=="next"?"left":"right";var fallback=type=="next"?"first":"last";var that=this;if(!$next.length){if(!this.options.wrap)return;$next=this.$element.find(".item")[fallback]()}this.sliding=true;isCycling&&this.pause();var e=$.Event("slide.bs.carousel",{relatedTarget:$next[0],direction:direction});if($next.hasClass("active"))return;if(this.$indicators.length){this.$indicators.find(".active").removeClass("active");this.$element.one("slid.bs.carousel",function(){var $nextIndicator=$(that.$indicators.children()[that.getActiveIndex()]);$nextIndicator&&$nextIndicator.addClass("active")})}if($.support.transition&&this.$element.hasClass("slide")){this.$element.trigger(e);if(e.isDefaultPrevented())return;$next.addClass(type);$next[0].offsetWidth;$active.addClass(direction);$next.addClass(direction);$active.one($.support.transition.end,function(){$next.removeClass([type,direction].join(" ")).addClass("active");$active.removeClass(["active",direction].join(" "));that.sliding=false;setTimeout(function(){that.$element.trigger("slid.bs.carousel")},0)}).emulateTransitionEnd(600)}else{this.$element.trigger(e);if(e.isDefaultPrevented())return;$active.removeClass("active");$next.addClass("active");this.sliding=false;this.$element.trigger("slid.bs.carousel")}isCycling&&this.cycle();return this};var old=$.fn.carousel;$.fn.carousel=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.carousel");var options=$.extend({},Carousel.DEFAULTS,$this.data(),typeof option=="object"&&option);var action=typeof option=="string"?option:options.slide;if(!data)$this.data("bs.carousel",data=new Carousel(this,options));if(typeof option=="number")data.to(option);else if(action)data[action]();else if(options.interval)data.pause().cycle()})};$.fn.carousel.Constructor=Carousel;$.fn.carousel.noConflict=function(){$.fn.carousel=old;return this};$(document).on("click.bs.carousel.data-api","[data-slide], [data-slide-to]",function(e){var $this=$(this),href;var $target=$($this.attr("data-target")||(href=$this.attr("href"))&&href.replace(/.*(?=#[^\s]+$)/,""));var options=$.extend({},$target.data(),$this.data());var slideIndex=$this.attr("data-slide-to");if(slideIndex)options.interval=false;$target.carousel(options);if(slideIndex=$this.attr("data-slide-to")){$target.data("bs.carousel").to(slideIndex)}e.preventDefault()});$(window).on("load",function(){$('[data-ride="carousel"]').each(function(){var $carousel=$(this);$carousel.carousel($carousel.data())})})}(jQuery);+function($){"use strict";var Collapse=function(element,options){this.$element=$(element);this.options=$.extend({},Collapse.DEFAULTS,options);this.transitioning=null;if(this.options.parent)this.$parent=$(this.options.parent);if(this.options.toggle)this.toggle()};Collapse.DEFAULTS={toggle:true};Collapse.prototype.dimension=function(){var hasWidth=this.$element.hasClass("width");return hasWidth?"width":"height"};Collapse.prototype.show=function(){if(this.transitioning||this.$element.hasClass("in"))return;var startEvent=$.Event("show.bs.collapse");this.$element.trigger(startEvent);if(startEvent.isDefaultPrevented())return;var actives=this.$parent&&this.$parent.find("> .panel > .in");if(actives&&actives.length){var hasData=actives.data("bs.collapse");if(hasData&&hasData.transitioning)return;actives.collapse("hide");hasData||actives.data("bs.collapse",null)}var dimension=this.dimension();this.$element.removeClass("collapse").addClass("collapsing")[dimension](0);this.transitioning=1;var complete=function(){this.$element.removeClass("collapsing").addClass("in")[dimension]("auto");this.transitioning=0;this.$element.trigger("shown.bs.collapse")};if(!$.support.transition)return complete.call(this);var scrollSize=$.camelCase(["scroll",dimension].join("-"));this.$element.one($.support.transition.end,$.proxy(complete,this)).emulateTransitionEnd(350)[dimension](this.$element[0][scrollSize])};Collapse.prototype.hide=function(){if(this.transitioning||!this.$element.hasClass("in"))return;var startEvent=$.Event("hide.bs.collapse");this.$element.trigger(startEvent);if(startEvent.isDefaultPrevented())return;var dimension=this.dimension();this.$element[dimension](this.$element[dimension]())[0].offsetHeight;this.$element.addClass("collapsing").removeClass("collapse").removeClass("in");this.transitioning=1;var complete=function(){this.transitioning=0;this.$element.trigger("hidden.bs.collapse").removeClass("collapsing").addClass("collapse")};if(!$.support.transition)return complete.call(this);this.$element[dimension](0).one($.support.transition.end,$.proxy(complete,this)).emulateTransitionEnd(350)};Collapse.prototype.toggle=function(){this[this.$element.hasClass("in")?"hide":"show"]()};var old=$.fn.collapse;$.fn.collapse=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.collapse");var options=$.extend({},Collapse.DEFAULTS,$this.data(),typeof option=="object"&&option);if(!data)$this.data("bs.collapse",data=new Collapse(this,options));if(typeof option=="string")data[option]()})};$.fn.collapse.Constructor=Collapse;$.fn.collapse.noConflict=function(){$.fn.collapse=old;return this};$(document).on("click.bs.collapse.data-api","[data-toggle=collapse]",function(e){var $this=$(this),href;var target=$this.attr("data-target")||e.preventDefault()||(href=$this.attr("href"))&&href.replace(/.*(?=#[^\s]+$)/,"");var $target=$(target);var data=$target.data("bs.collapse");var option=data?"toggle":$this.data();var parent=$this.attr("data-parent");var $parent=parent&&$(parent);if(!data||!data.transitioning){if($parent)$parent.find('[data-toggle=collapse][data-parent="'+parent+'"]').not($this).addClass("collapsed");$this[$target.hasClass("in")?"addClass":"removeClass"]("collapsed")}$target.collapse(option)})}(jQuery);+function($){"use strict";var backdrop=".dropdown-backdrop";var toggle="[data-toggle=dropdown]";var Dropdown=function(element){$(element).on("click.bs.dropdown",this.toggle)};Dropdown.prototype.toggle=function(e){var $this=$(this);if($this.is(".disabled, :disabled"))return;var $parent=getParent($this);var isActive=$parent.hasClass("open");clearMenus();if(!isActive){if("ontouchstart"in document.documentElement&&!$parent.closest(".navbar-nav").length){$('<div class="dropdown-backdrop"/>').insertAfter($(this)).on("click",clearMenus)}$parent.trigger(e=$.Event("show.bs.dropdown"));if(e.isDefaultPrevented())return;$parent.toggleClass("open").trigger("shown.bs.dropdown");$this.focus()}return false};Dropdown.prototype.keydown=function(e){if(!/(38|40|27)/.test(e.keyCode))return;var $this=$(this);e.preventDefault();e.stopPropagation();if($this.is(".disabled, :disabled"))return;var $parent=getParent($this);var isActive=$parent.hasClass("open");if(!isActive||isActive&&e.keyCode==27){if(e.which==27)$parent.find(toggle).focus();return $this.click()}var $items=$("[role=menu] li:not(.divider):visible a",$parent);if(!$items.length)return;var index=$items.index($items.filter(":focus"));if(e.keyCode==38&&index>0)index--;if(e.keyCode==40&&index<$items.length-1)index++;if(!~index)index=0;$items.eq(index).focus()};function clearMenus(){$(backdrop).remove();$(toggle).each(function(e){var $parent=getParent($(this));if(!$parent.hasClass("open"))return;$parent.trigger(e=$.Event("hide.bs.dropdown"));if(e.isDefaultPrevented())return;$parent.removeClass("open").trigger("hidden.bs.dropdown")})}function getParent($this){var selector=$this.attr("data-target");if(!selector){selector=$this.attr("href");selector=selector&&/#/.test(selector)&&selector.replace(/.*(?=#[^\s]*$)/,"")}var $parent=selector&&$(selector);return $parent&&$parent.length?$parent:$this.parent()}var old=$.fn.dropdown;$.fn.dropdown=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.dropdown");if(!data)$this.data("bs.dropdown",data=new Dropdown(this));if(typeof option=="string")data[option].call($this)})};$.fn.dropdown.Constructor=Dropdown;$.fn.dropdown.noConflict=function(){$.fn.dropdown=old;return this};$(document).on("click.bs.dropdown.data-api",clearMenus).on("click.bs.dropdown.data-api",".dropdown form",function(e){e.stopPropagation()}).on("click.bs.dropdown.data-api",toggle,Dropdown.prototype.toggle).on("keydown.bs.dropdown.data-api",toggle+", [role=menu]",Dropdown.prototype.keydown)}(jQuery);+function($){"use strict";var Modal=function(element,options){this.options=options;this.$element=$(element);this.$backdrop=this.isShown=null;if(this.options.remote)this.$element.load(this.options.remote)};Modal.DEFAULTS={backdrop:true,keyboard:true,show:true};Modal.prototype.toggle=function(_relatedTarget){return this[!this.isShown?"show":"hide"](_relatedTarget)};Modal.prototype.show=function(_relatedTarget){var that=this;var e=$.Event("show.bs.modal",{relatedTarget:_relatedTarget});this.$element.trigger(e);if(this.isShown||e.isDefaultPrevented())return;this.isShown=true;this.escape();this.$element.on("click.dismiss.modal",'[data-dismiss="modal"]',$.proxy(this.hide,this));this.backdrop(function(){var transition=$.support.transition&&that.$element.hasClass("fade");if(!that.$element.parent().length){that.$element.appendTo(document.body)}that.$element.show();if(transition){that.$element[0].offsetWidth}that.$element.addClass("in").attr("aria-hidden",false);that.enforceFocus();var e=$.Event("shown.bs.modal",{relatedTarget:_relatedTarget});transition?that.$element.find(".modal-dialog").one($.support.transition.end,function(){that.$element.focus().trigger(e)}).emulateTransitionEnd(300):that.$element.focus().trigger(e)})};Modal.prototype.hide=function(e){if(e)e.preventDefault();e=$.Event("hide.bs.modal");this.$element.trigger(e);if(!this.isShown||e.isDefaultPrevented())return;this.isShown=false;this.escape();$(document).off("focusin.bs.modal");this.$element.removeClass("in").attr("aria-hidden",true).off("click.dismiss.modal");$.support.transition&&this.$element.hasClass("fade")?this.$element.one($.support.transition.end,$.proxy(this.hideModal,this)).emulateTransitionEnd(300):this.hideModal()};Modal.prototype.enforceFocus=function(){$(document).off("focusin.bs.modal").on("focusin.bs.modal",$.proxy(function(e){if(this.$element[0]!==e.target&&!this.$element.has(e.target).length){this.$element.focus()}},this))};Modal.prototype.escape=function(){if(this.isShown&&this.options.keyboard){this.$element.on("keyup.dismiss.bs.modal",$.proxy(function(e){e.which==27&&this.hide()},this))}else if(!this.isShown){this.$element.off("keyup.dismiss.bs.modal")}};Modal.prototype.hideModal=function(){var that=this;this.$element.hide();this.backdrop(function(){that.removeBackdrop();that.$element.trigger("hidden.bs.modal")})};Modal.prototype.removeBackdrop=function(){this.$backdrop&&this.$backdrop.remove();this.$backdrop=null};Modal.prototype.backdrop=function(callback){var that=this;var animate=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.options.backdrop){var doAnimate=$.support.transition&&animate;this.$backdrop=$('<div class="modal-backdrop '+animate+'" />').appendTo(document.body);this.$element.on("click.dismiss.modal",$.proxy(function(e){if(e.target!==e.currentTarget)return;this.options.backdrop=="static"?this.$element[0].focus.call(this.$element[0]):this.hide.call(this)},this));if(doAnimate)this.$backdrop[0].offsetWidth;this.$backdrop.addClass("in");if(!callback)return;doAnimate?this.$backdrop.one($.support.transition.end,callback).emulateTransitionEnd(150):callback()}else if(!this.isShown&&this.$backdrop){this.$backdrop.removeClass("in");$.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one($.support.transition.end,callback).emulateTransitionEnd(150):callback()}else if(callback){callback()}};var old=$.fn.modal;$.fn.modal=function(option,_relatedTarget){return this.each(function(){var $this=$(this);var data=$this.data("bs.modal");var options=$.extend({},Modal.DEFAULTS,$this.data(),typeof option=="object"&&option);if(!data)$this.data("bs.modal",data=new Modal(this,options));if(typeof option=="string")data[option](_relatedTarget);else if(options.show)data.show(_relatedTarget)})};$.fn.modal.Constructor=Modal;$.fn.modal.noConflict=function(){$.fn.modal=old;return this};$(document).on("click.bs.modal.data-api",'[data-toggle="modal"]',function(e){var $this=$(this);var href=$this.attr("href");var $target=$($this.attr("data-target")||href&&href.replace(/.*(?=#[^\s]+$)/,""));var option=$target.data("modal")?"toggle":$.extend({remote:!/#/.test(href)&&href},$target.data(),$this.data());e.preventDefault();$target.modal(option,this).one("hide",function(){$this.is(":visible")&&$this.focus()})});$(document).on("show.bs.modal",".modal",function(){$(document.body).addClass("modal-open")}).on("hidden.bs.modal",".modal",function(){$(document.body).removeClass("modal-open")})}(jQuery);+function($){"use strict";var Tooltip=function(element,options){this.type=this.options=this.enabled=this.timeout=this.hoverState=this.$element=null;this.init("tooltip",element,options)};Tooltip.DEFAULTS={animation:true,placement:"top",selector:false,template:'<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:false,container:false};Tooltip.prototype.init=function(type,element,options){this.enabled=true;this.type=type;this.$element=$(element);this.options=this.getOptions(options);var triggers=this.options.trigger.split(" ");for(var i=triggers.length;i--;){var trigger=triggers[i];if(trigger=="click"){this.$element.on("click."+this.type,this.options.selector,$.proxy(this.toggle,this))}else if(trigger!="manual"){var eventIn=trigger=="hover"?"mouseenter":"focus";var eventOut=trigger=="hover"?"mouseleave":"blur";this.$element.on(eventIn+"."+this.type,this.options.selector,$.proxy(this.enter,this));this.$element.on(eventOut+"."+this.type,this.options.selector,$.proxy(this.leave,this))}}this.options.selector?this._options=$.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()};Tooltip.prototype.getDefaults=function(){return Tooltip.DEFAULTS};Tooltip.prototype.getOptions=function(options){options=$.extend({},this.getDefaults(),this.$element.data(),options);if(options.delay&&typeof options.delay=="number"){options.delay={show:options.delay,hide:options.delay}}return options};Tooltip.prototype.getDelegateOptions=function(){var options={};var defaults=this.getDefaults();this._options&&$.each(this._options,function(key,value){if(defaults[key]!=value)options[key]=value});return options};Tooltip.prototype.enter=function(obj){var self=obj instanceof this.constructor?obj:$(obj.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type);clearTimeout(self.timeout);self.hoverState="in";if(!self.options.delay||!self.options.delay.show)return self.show();self.timeout=setTimeout(function(){if(self.hoverState=="in")self.show()},self.options.delay.show)};Tooltip.prototype.leave=function(obj){var self=obj instanceof this.constructor?obj:$(obj.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type);clearTimeout(self.timeout);self.hoverState="out";if(!self.options.delay||!self.options.delay.hide)return self.hide();self.timeout=setTimeout(function(){if(self.hoverState=="out")self.hide()},self.options.delay.hide)};Tooltip.prototype.show=function(){var e=$.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);if(e.isDefaultPrevented())return;var $tip=this.tip();this.setContent();if(this.options.animation)$tip.addClass("fade");var placement=typeof this.options.placement=="function"?this.options.placement.call(this,$tip[0],this.$element[0]):this.options.placement;var autoToken=/\s?auto?\s?/i;var autoPlace=autoToken.test(placement);if(autoPlace)placement=placement.replace(autoToken,"")||"top";$tip.detach().css({top:0,left:0,display:"block"}).addClass(placement);this.options.container?$tip.appendTo(this.options.container):$tip.insertAfter(this.$element);var pos=this.getPosition();var actualWidth=$tip[0].offsetWidth;var actualHeight=$tip[0].offsetHeight;if(autoPlace){var $parent=this.$element.parent();var orgPlacement=placement;var docScroll=document.documentElement.scrollTop||document.body.scrollTop;var parentWidth=this.options.container=="body"?window.innerWidth:$parent.outerWidth();var parentHeight=this.options.container=="body"?window.innerHeight:$parent.outerHeight();var parentLeft=this.options.container=="body"?0:$parent.offset().left;placement=placement=="bottom"&&pos.top+pos.height+actualHeight-docScroll>parentHeight?"top":placement=="top"&&pos.top-docScroll-actualHeight<0?"bottom":placement=="right"&&pos.right+actualWidth>parentWidth?"left":placement=="left"&&pos.left-actualWidth<parentLeft?"right":placement;$tip.removeClass(orgPlacement).addClass(placement)}var calculatedOffset=this.getCalculatedOffset(placement,pos,actualWidth,actualHeight);this.applyPlacement(calculatedOffset,placement);this.$element.trigger("shown.bs."+this.type)}};Tooltip.prototype.applyPlacement=function(offset,placement){var replace;var $tip=this.tip();var width=$tip[0].offsetWidth;var height=$tip[0].offsetHeight;var marginTop=parseInt($tip.css("margin-top"),10);var marginLeft=parseInt($tip.css("margin-left"),10);if(isNaN(marginTop))marginTop=0;if(isNaN(marginLeft))marginLeft=0;offset.top=offset.top+marginTop;offset.left=offset.left+marginLeft;$tip.offset(offset).addClass("in");var actualWidth=$tip[0].offsetWidth;var actualHeight=$tip[0].offsetHeight;if(placement=="top"&&actualHeight!=height){replace=true;offset.top=offset.top+height-actualHeight}if(/bottom|top/.test(placement)){var delta=0;if(offset.left<0){delta=offset.left*-2;offset.left=0;$tip.offset(offset);actualWidth=$tip[0].offsetWidth;actualHeight=$tip[0].offsetHeight}this.replaceArrow(delta-width+actualWidth,actualWidth,"left")}else{this.replaceArrow(actualHeight-height,actualHeight,"top")}if(replace)$tip.offset(offset)};Tooltip.prototype.replaceArrow=function(delta,dimension,position){this.arrow().css(position,delta?50*(1-delta/dimension)+"%":"")};Tooltip.prototype.setContent=function(){var $tip=this.tip();var title=this.getTitle();$tip.find(".tooltip-inner")[this.options.html?"html":"text"](title);$tip.removeClass("fade in top bottom left right")};Tooltip.prototype.hide=function(){var that=this;var $tip=this.tip();var e=$.Event("hide.bs."+this.type);function complete(){if(that.hoverState!="in")$tip.detach()}this.$element.trigger(e);if(e.isDefaultPrevented())return;$tip.removeClass("in");$.support.transition&&this.$tip.hasClass("fade")?$tip.one($.support.transition.end,complete).emulateTransitionEnd(150):complete();this.$element.trigger("hidden.bs."+this.type);return this};Tooltip.prototype.fixTitle=function(){var $e=this.$element;if($e.attr("title")||typeof $e.attr("data-original-title")!="string"){$e.attr("data-original-title",$e.attr("title")||"").attr("title","")}};Tooltip.prototype.hasContent=function(){return this.getTitle()};Tooltip.prototype.getPosition=function(){var el=this.$element[0];return $.extend({},typeof el.getBoundingClientRect=="function"?el.getBoundingClientRect():{width:el.offsetWidth,height:el.offsetHeight},this.$element.offset())};Tooltip.prototype.getCalculatedOffset=function(placement,pos,actualWidth,actualHeight){return placement=="bottom"?{top:pos.top+pos.height,left:pos.left+pos.width/2-actualWidth/2}:placement=="top"?{top:pos.top-actualHeight,left:pos.left+pos.width/2-actualWidth/2}:placement=="left"?{top:pos.top+pos.height/2-actualHeight/2,left:pos.left-actualWidth}:{top:pos.top+pos.height/2-actualHeight/2,left:pos.left+pos.width}};Tooltip.prototype.getTitle=function(){var title;var $e=this.$element;var o=this.options;title=$e.attr("data-original-title")||(typeof o.title=="function"?o.title.call($e[0]):o.title);return title};Tooltip.prototype.tip=function(){return this.$tip=this.$tip||$(this.options.template)};Tooltip.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")};Tooltip.prototype.validate=function(){if(!this.$element[0].parentNode){this.hide();this.$element=null;this.options=null}};Tooltip.prototype.enable=function(){this.enabled=true};Tooltip.prototype.disable=function(){this.enabled=false};Tooltip.prototype.toggleEnabled=function(){this.enabled=!this.enabled};Tooltip.prototype.toggle=function(e){var self=e?$(e.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type):this;self.tip().hasClass("in")?self.leave(self):self.enter(self)};Tooltip.prototype.destroy=function(){this.hide().$element.off("."+this.type).removeData("bs."+this.type)};var old=$.fn.tooltip;$.fn.tooltip=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.tooltip");var options=typeof option=="object"&&option;if(!data)$this.data("bs.tooltip",data=new Tooltip(this,options));if(typeof option=="string")data[option]()})};$.fn.tooltip.Constructor=Tooltip;$.fn.tooltip.noConflict=function(){$.fn.tooltip=old;return this}}(jQuery);+function($){"use strict";var Popover=function(element,options){this.init("popover",element,options)};if(!$.fn.tooltip)throw new Error("Popover requires tooltip.js");Popover.DEFAULTS=$.extend({},$.fn.tooltip.Constructor.DEFAULTS,{placement:"right",trigger:"click",content:"",template:'<div class="popover"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'});Popover.prototype=$.extend({},$.fn.tooltip.Constructor.prototype);Popover.prototype.constructor=Popover;Popover.prototype.getDefaults=function(){return Popover.DEFAULTS};Popover.prototype.setContent=function(){var $tip=this.tip();var title=this.getTitle();var content=this.getContent();$tip.find(".popover-title")[this.options.html?"html":"text"](title);$tip.find(".popover-content")[this.options.html?"html":"text"](content);$tip.removeClass("fade top bottom left right in");if(!$tip.find(".popover-title").html())$tip.find(".popover-title").hide()};Popover.prototype.hasContent=function(){return this.getTitle()||this.getContent()};Popover.prototype.getContent=function(){var $e=this.$element;var o=this.options;return $e.attr("data-content")||(typeof o.content=="function"?o.content.call($e[0]):o.content)};Popover.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".arrow")};Popover.prototype.tip=function(){if(!this.$tip)this.$tip=$(this.options.template);return this.$tip};var old=$.fn.popover;$.fn.popover=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.popover");var options=typeof option=="object"&&option;if(!data)$this.data("bs.popover",data=new Popover(this,options));if(typeof option=="string")data[option]()})};$.fn.popover.Constructor=Popover;$.fn.popover.noConflict=function(){$.fn.popover=old;return this}}(jQuery);+function($){"use strict";function ScrollSpy(element,options){var href;var process=$.proxy(this.process,this);this.$element=$(element).is("body")?$(window):$(element);this.$body=$("body");this.$scrollElement=this.$element.on("scroll.bs.scroll-spy.data-api",process);this.options=$.extend({},ScrollSpy.DEFAULTS,options);this.selector=(this.options.target||(href=$(element).attr("href"))&&href.replace(/.*(?=#[^\s]+$)/,"")||"")+" .nav li > a";this.offsets=$([]);this.targets=$([]);this.activeTarget=null;this.refresh();this.process()}ScrollSpy.DEFAULTS={offset:10};ScrollSpy.prototype.refresh=function(){var offsetMethod=this.$element[0]==window?"offset":"position";this.offsets=$([]);this.targets=$([]);var self=this;var $targets=this.$body.find(this.selector).map(function(){var $el=$(this);var href=$el.data("target")||$el.attr("href");var $href=/^#\w/.test(href)&&$(href);return $href&&$href.length&&[[$href[offsetMethod]().top+(!$.isWindow(self.$scrollElement.get(0))&&self.$scrollElement.scrollTop()),href]]||null}).sort(function(a,b){return a[0]-b[0]}).each(function(){self.offsets.push(this[0]);self.targets.push(this[1])})};ScrollSpy.prototype.process=function(){var scrollTop=this.$scrollElement.scrollTop()+this.options.offset;var scrollHeight=this.$scrollElement[0].scrollHeight||this.$body[0].scrollHeight;var maxScroll=scrollHeight-this.$scrollElement.height();var offsets=this.offsets;var targets=this.targets;var activeTarget=this.activeTarget;var i;if(scrollTop>=maxScroll){return activeTarget!=(i=targets.last()[0])&&this.activate(i)}for(i=offsets.length;i--;){activeTarget!=targets[i]&&scrollTop>=offsets[i]&&(!offsets[i+1]||scrollTop<=offsets[i+1])&&this.activate(targets[i])}};ScrollSpy.prototype.activate=function(target){this.activeTarget=target;$(this.selector).parents(".active").removeClass("active");var selector=this.selector+'[data-target="'+target+'"],'+this.selector+'[href="'+target+'"]';var active=$(selector).parents("li").addClass("active");if(active.parent(".dropdown-menu").length){active=active.closest("li.dropdown").addClass("active")}active.trigger("activate.bs.scrollspy")};var old=$.fn.scrollspy;$.fn.scrollspy=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.scrollspy");var options=typeof option=="object"&&option;if(!data)$this.data("bs.scrollspy",data=new ScrollSpy(this,options));if(typeof option=="string")data[option]()})};$.fn.scrollspy.Constructor=ScrollSpy;$.fn.scrollspy.noConflict=function(){$.fn.scrollspy=old;return this};$(window).on("load",function(){$('[data-spy="scroll"]').each(function(){var $spy=$(this);$spy.scrollspy($spy.data())})})}(jQuery);+function($){"use strict";var Tab=function(element){this.element=$(element)};Tab.prototype.show=function(){var $this=this.element;var $ul=$this.closest("ul:not(.dropdown-menu)");var selector=$this.data("target");if(!selector){selector=$this.attr("href");selector=selector&&selector.replace(/.*(?=#[^\s]*$)/,"")}if($this.parent("li").hasClass("active"))return;var previous=$ul.find(".active:last a")[0];var e=$.Event("show.bs.tab",{relatedTarget:previous});$this.trigger(e);if(e.isDefaultPrevented())return;var $target=$(selector);this.activate($this.parent("li"),$ul);this.activate($target,$target.parent(),function(){$this.trigger({type:"shown.bs.tab",relatedTarget:previous})})};Tab.prototype.activate=function(element,container,callback){var $active=container.find("> .active");var transition=callback&&$.support.transition&&$active.hasClass("fade");function next(){$active.removeClass("active").find("> .dropdown-menu > .active").removeClass("active");element.addClass("active");if(transition){element[0].offsetWidth;element.addClass("in")}else{element.removeClass("fade")}if(element.parent(".dropdown-menu")){element.closest("li.dropdown").addClass("active")}callback&&callback()}transition?$active.one($.support.transition.end,next).emulateTransitionEnd(150):next();$active.removeClass("in")};var old=$.fn.tab;$.fn.tab=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.tab");
if(!data)$this.data("bs.tab",data=new Tab(this));if(typeof option=="string")data[option]()})};$.fn.tab.Constructor=Tab;$.fn.tab.noConflict=function(){$.fn.tab=old;return this};$(document).on("click.bs.tab.data-api",'[data-toggle="tab"], [data-toggle="pill"]',function(e){e.preventDefault();$(this).tab("show")})}(jQuery);+function($){"use strict";var Affix=function(element,options){this.options=$.extend({},Affix.DEFAULTS,options);this.$window=$(window).on("scroll.bs.affix.data-api",$.proxy(this.checkPosition,this)).on("click.bs.affix.data-api",$.proxy(this.checkPositionWithEventLoop,this));this.$element=$(element);this.affixed=this.unpin=null;this.checkPosition()};Affix.RESET="affix affix-top affix-bottom";Affix.DEFAULTS={offset:0};Affix.prototype.checkPositionWithEventLoop=function(){setTimeout($.proxy(this.checkPosition,this),1)};Affix.prototype.checkPosition=function(){if(!this.$element.is(":visible"))return;var scrollHeight=$(document).height();var scrollTop=this.$window.scrollTop();var position=this.$element.offset();var offset=this.options.offset;var offsetTop=offset.top;var offsetBottom=offset.bottom;if(typeof offset!="object")offsetBottom=offsetTop=offset;if(typeof offsetTop=="function")offsetTop=offset.top();if(typeof offsetBottom=="function")offsetBottom=offset.bottom();var affix=this.unpin!=null&&scrollTop+this.unpin<=position.top?false:offsetBottom!=null&&position.top+this.$element.height()>=scrollHeight-offsetBottom?"bottom":offsetTop!=null&&scrollTop<=offsetTop?"top":false;if(this.affixed===affix)return;if(this.unpin)this.$element.css("top","");this.affixed=affix;this.unpin=affix=="bottom"?position.top-scrollTop:null;this.$element.removeClass(Affix.RESET).addClass("affix"+(affix?"-"+affix:""));if(affix=="bottom"){this.$element.offset({top:document.body.offsetHeight-offsetBottom-this.$element.height()})}};var old=$.fn.affix;$.fn.affix=function(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.affix");var options=typeof option=="object"&&option;if(!data)$this.data("bs.affix",data=new Affix(this,options));if(typeof option=="string")data[option]()})};$.fn.affix.Constructor=Affix;$.fn.affix.noConflict=function(){$.fn.affix=old;return this};$(window).on("load",function(){$('[data-spy="affix"]').each(function(){var $spy=$(this);var data=$spy.data();data.offset=data.offset||{};if(data.offsetBottom)data.offset.bottom=data.offsetBottom;if(data.offsetTop)data.offset.top=data.offsetTop;$spy.affix(data)})})}(jQuery);
(function($,undefined){function UTCDate(){return new Date(Date.UTC.apply(Date,arguments))}function UTCToday(){var today=new Date;return UTCDate(today.getFullYear(),today.getMonth(),today.getDate())}function isUTCEquals(date1,date2){return date1.getUTCFullYear()===date2.getUTCFullYear()&&date1.getUTCMonth()===date2.getUTCMonth()&&date1.getUTCDate()===date2.getUTCDate()}function alias(method){return function(){return this[method].apply(this,arguments)}}var DateArray=function(){var extras={get:function(i){return this.slice(i)[0]},contains:function(d){var val=d&&d.valueOf();for(var i=0,l=this.length;i<l;i++)if(this[i].valueOf()===val)return i;return-1},remove:function(i){this.splice(i,1)},replace:function(new_array){if(!new_array)return;if(!$.isArray(new_array))new_array=[new_array];this.clear();this.push.apply(this,new_array)},clear:function(){this.length=0},copy:function(){var a=new DateArray;a.replace(this);return a}};return function(){var a=[];a.push.apply(a,arguments);$.extend(a,extras);return a}}();var Datepicker=function(element,options){this._process_options(options);this.dates=new DateArray;this.viewDate=this.o.defaultViewDate;this.focusDate=null;this.element=$(element);this.isInline=false;this.isInput=this.element.is("input");this.component=this.element.hasClass("date")?this.element.find(".add-on, .input-group-addon, .btn"):false;this.hasInput=this.component&&this.element.find("input").length;if(this.component&&this.component.length===0)this.component=false;this.picker=$(DPGlobal.template);this._buildEvents();this._attachEvents();if(this.isInline){this.picker.addClass("datepicker-inline").appendTo(this.element)}else{this.picker.addClass("datepicker-dropdown dropdown-menu")}if(this.o.rtl){this.picker.addClass("datepicker-rtl")}this.viewMode=this.o.startView;if(this.o.calendarWeeks)this.picker.find("tfoot .today, tfoot .clear").attr("colspan",function(i,val){return parseInt(val)+1});this._allow_update=false;this.setStartDate(this._o.startDate);this.setEndDate(this._o.endDate);this.setDaysOfWeekDisabled(this.o.daysOfWeekDisabled);this.setDatesDisabled(this.o.datesDisabled);this.fillDow();this.fillMonths();this._allow_update=true;this.update();this.showMode();if(this.isInline){this.show()}};Datepicker.prototype={constructor:Datepicker,_process_options:function(opts){this._o=$.extend({},this._o,opts);var o=this.o=$.extend({},this._o);var lang=o.language;if(!dates[lang]){lang=lang.split("-")[0];if(!dates[lang])lang=defaults.language}o.language=lang;switch(o.startView){case 2:case"decade":o.startView=2;break;case 1:case"year":o.startView=1;break;default:o.startView=0}switch(o.minViewMode){case 1:case"months":o.minViewMode=1;break;case 2:case"years":o.minViewMode=2;break;default:o.minViewMode=0}o.startView=Math.max(o.startView,o.minViewMode);if(o.multidate!==true){o.multidate=Number(o.multidate)||false;if(o.multidate!==false)o.multidate=Math.max(0,o.multidate)}o.multidateSeparator=String(o.multidateSeparator);o.weekStart%=7;o.weekEnd=(o.weekStart+6)%7;var format=DPGlobal.parseFormat(o.format);if(o.startDate!==-Infinity){if(!!o.startDate){if(o.startDate instanceof Date)o.startDate=this._local_to_utc(this._zero_time(o.startDate));else o.startDate=DPGlobal.parseDate(o.startDate,format,o.language)}else{o.startDate=-Infinity}}if(o.endDate!==Infinity){if(!!o.endDate){if(o.endDate instanceof Date)o.endDate=this._local_to_utc(this._zero_time(o.endDate));else o.endDate=DPGlobal.parseDate(o.endDate,format,o.language)}else{o.endDate=Infinity}}o.daysOfWeekDisabled=o.daysOfWeekDisabled||[];if(!$.isArray(o.daysOfWeekDisabled))o.daysOfWeekDisabled=o.daysOfWeekDisabled.split(/[,\s]*/);o.daysOfWeekDisabled=$.map(o.daysOfWeekDisabled,function(d){return parseInt(d,10)});o.datesDisabled=o.datesDisabled||[];if(!$.isArray(o.datesDisabled)){var datesDisabled=[];datesDisabled.push(DPGlobal.parseDate(o.datesDisabled,format,o.language));o.datesDisabled=datesDisabled}o.datesDisabled=$.map(o.datesDisabled,function(d){return DPGlobal.parseDate(d,format,o.language)});var plc=String(o.orientation).toLowerCase().split(/\s+/g),_plc=o.orientation.toLowerCase();plc=$.grep(plc,function(word){return/^auto|left|right|top|bottom$/.test(word)});o.orientation={x:"auto",y:"auto"};if(!_plc||_plc==="auto");else if(plc.length===1){switch(plc[0]){case"top":case"bottom":o.orientation.y=plc[0];break;case"left":case"right":o.orientation.x=plc[0];break}}else{_plc=$.grep(plc,function(word){return/^left|right$/.test(word)});o.orientation.x=_plc[0]||"auto";_plc=$.grep(plc,function(word){return/^top|bottom$/.test(word)});o.orientation.y=_plc[0]||"auto"}if(o.defaultViewDate){var year=o.defaultViewDate.year||(new Date).getFullYear();var month=o.defaultViewDate.month||0;var day=o.defaultViewDate.day||1;o.defaultViewDate=UTCDate(year,month,day)}else{o.defaultViewDate=UTCToday()}o.showOnFocus=o.showOnFocus!==undefined?o.showOnFocus:true},_events:[],_secondaryEvents:[],_applyEvents:function(evs){for(var i=0,el,ch,ev;i<evs.length;i++){el=evs[i][0];if(evs[i].length===2){ch=undefined;ev=evs[i][1]}else if(evs[i].length===3){ch=evs[i][1];ev=evs[i][2]}el.on(ev,ch)}},_unapplyEvents:function(evs){for(var i=0,el,ev,ch;i<evs.length;i++){el=evs[i][0];if(evs[i].length===2){ch=undefined;ev=evs[i][1]}else if(evs[i].length===3){ch=evs[i][1];ev=evs[i][2]}el.off(ev,ch)}},_buildEvents:function(){var events={keyup:$.proxy(function(e){if($.inArray(e.keyCode,[27,37,39,38,40,32,13,9])===-1)this.update()},this),keydown:$.proxy(this.keydown,this)};if(this.o.showOnFocus===true){events.focus=$.proxy(this.show,this)}if(this.isInput){this._events=[[this.element,events]]}else if(this.component&&this.hasInput){this._events=[[this.element.find("input"),events],[this.component,{click:$.proxy(this.show,this)}]]}else if(this.element.is("div")){this.isInline=true}else{this._events=[[this.element,{click:$.proxy(this.show,this)}]]}this._events.push([this.element,"*",{blur:$.proxy(function(e){this._focused_from=e.target},this)}],[this.element,{blur:$.proxy(function(e){this._focused_from=e.target},this)}]);this._secondaryEvents=[[this.picker,{click:$.proxy(this.click,this)}],[$(window),{resize:$.proxy(this.place,this)}],[$(document),{"mousedown touchstart":$.proxy(function(e){if(!(this.element.is(e.target)||this.element.find(e.target).length||this.picker.is(e.target)||this.picker.find(e.target).length)){this.hide()}},this)}]]},_attachEvents:function(){this._detachEvents();this._applyEvents(this._events)},_detachEvents:function(){this._unapplyEvents(this._events)},_attachSecondaryEvents:function(){this._detachSecondaryEvents();this._applyEvents(this._secondaryEvents)},_detachSecondaryEvents:function(){this._unapplyEvents(this._secondaryEvents)},_trigger:function(event,altdate){var date=altdate||this.dates.get(-1),local_date=this._utc_to_local(date);this.element.trigger({type:event,date:local_date,dates:$.map(this.dates,this._utc_to_local),format:$.proxy(function(ix,format){if(arguments.length===0){ix=this.dates.length-1;format=this.o.format}else if(typeof ix==="string"){format=ix;ix=this.dates.length-1}format=format||this.o.format;var date=this.dates.get(ix);return DPGlobal.formatDate(date,format,this.o.language)},this)})},show:function(){if(this.element.attr("readonly")&&this.o.enableOnReadonly===false)return;if(!this.isInline)this.picker.appendTo(this.o.container);this.place();this.picker.show();this._attachSecondaryEvents();this._trigger("show");if((window.navigator.msMaxTouchPoints||"ontouchstart"in document)&&this.o.disableTouchKeyboard){$(this.element).blur()}return this},hide:function(){if(this.isInline)return this;if(!this.picker.is(":visible"))return this;this.focusDate=null;this.picker.hide().detach();this._detachSecondaryEvents();this.viewMode=this.o.startView;this.showMode();if(this.o.forceParse&&(this.isInput&&this.element.val()||this.hasInput&&this.element.find("input").val()))this.setValue();this._trigger("hide");return this},remove:function(){this.hide();this._detachEvents();this._detachSecondaryEvents();this.picker.remove();delete this.element.data().datepicker;if(!this.isInput){delete this.element.data().date}return this},_utc_to_local:function(utc){return utc&&new Date(utc.getTime()+utc.getTimezoneOffset()*6e4)},_local_to_utc:function(local){return local&&new Date(local.getTime()-local.getTimezoneOffset()*6e4)},_zero_time:function(local){return local&&new Date(local.getFullYear(),local.getMonth(),local.getDate())},_zero_utc_time:function(utc){return utc&&new Date(Date.UTC(utc.getUTCFullYear(),utc.getUTCMonth(),utc.getUTCDate()))},getDates:function(){return $.map(this.dates,this._utc_to_local)},getUTCDates:function(){return $.map(this.dates,function(d){return new Date(d)})},getDate:function(){return this._utc_to_local(this.getUTCDate())},getUTCDate:function(){var selected_date=this.dates.get(-1);if(typeof selected_date!=="undefined"){return new Date(selected_date)}else{return null}},clearDates:function(){var element;if(this.isInput){element=this.element}else if(this.component){element=this.element.find("input")}if(element){element.val("").change()}this.update();this._trigger("changeDate");if(this.o.autoclose){this.hide()}},setDates:function(){var args=$.isArray(arguments[0])?arguments[0]:arguments;this.update.apply(this,args);this._trigger("changeDate");this.setValue();return this},setUTCDates:function(){var args=$.isArray(arguments[0])?arguments[0]:arguments;this.update.apply(this,$.map(args,this._utc_to_local));this._trigger("changeDate");this.setValue();return this},setDate:alias("setDates"),setUTCDate:alias("setUTCDates"),setValue:function(){var formatted=this.getFormattedDate();if(!this.isInput){if(this.component){this.element.find("input").val(formatted).change()}}else{this.element.val(formatted).change()}return this},getFormattedDate:function(format){if(format===undefined)format=this.o.format;var lang=this.o.language;return $.map(this.dates,function(d){return DPGlobal.formatDate(d,format,lang)}).join(this.o.multidateSeparator)},setStartDate:function(startDate){this._process_options({startDate:startDate});this.update();this.updateNavArrows();return this},setEndDate:function(endDate){this._process_options({endDate:endDate});this.update();this.updateNavArrows();return this},setDaysOfWeekDisabled:function(daysOfWeekDisabled){this._process_options({daysOfWeekDisabled:daysOfWeekDisabled});this.update();this.updateNavArrows();return this},setDatesDisabled:function(datesDisabled){this._process_options({datesDisabled:datesDisabled});this.update();this.updateNavArrows()},place:function(){if(this.isInline)return this;var calendarWidth=this.picker.outerWidth(),calendarHeight=this.picker.outerHeight(),visualPadding=10,windowWidth=$(this.o.container).width(),windowHeight=$(this.o.container).height(),scrollTop=$(this.o.container).scrollTop(),appendOffset=$(this.o.container).offset();var parentsZindex=[];this.element.parents().each(function(){var itemZIndex=$(this).css("z-index");if(itemZIndex!=="auto"&&itemZIndex!==0)parentsZindex.push(parseInt(itemZIndex))});var zIndex=Math.max.apply(Math,parentsZindex)+10;var offset=this.component?this.component.parent().offset():this.element.offset();var height=this.component?this.component.outerHeight(true):this.element.outerHeight(false);var width=this.component?this.component.outerWidth(true):this.element.outerWidth(false);var left=offset.left-appendOffset.left,top=offset.top-appendOffset.top;this.picker.removeClass("datepicker-orient-top datepicker-orient-bottom "+"datepicker-orient-right datepicker-orient-left");if(this.o.orientation.x!=="auto"){this.picker.addClass("datepicker-orient-"+this.o.orientation.x);if(this.o.orientation.x==="right")left-=calendarWidth-width}else{if(offset.left<0){this.picker.addClass("datepicker-orient-left");left-=offset.left-visualPadding}else if(left+calendarWidth>windowWidth){this.picker.addClass("datepicker-orient-right");left=offset.left+width-calendarWidth}else{this.picker.addClass("datepicker-orient-left")}}var yorient=this.o.orientation.y,top_overflow,bottom_overflow;if(yorient==="auto"){top_overflow=-scrollTop+top-calendarHeight;bottom_overflow=scrollTop+windowHeight-(top+height+calendarHeight);if(Math.max(top_overflow,bottom_overflow)===bottom_overflow)yorient="top";else yorient="bottom"}this.picker.addClass("datepicker-orient-"+yorient);if(yorient==="top")top+=height;else top-=calendarHeight+parseInt(this.picker.css("padding-top"));if(this.o.rtl){var right=windowWidth-(left+width);this.picker.css({top:top,right:right,zIndex:zIndex})}else{this.picker.css({top:top,left:left,zIndex:zIndex})}return this},_allow_update:true,update:function(){if(!this._allow_update)return this;var oldDates=this.dates.copy(),dates=[],fromArgs=false;if(arguments.length){$.each(arguments,$.proxy(function(i,date){if(date instanceof Date)date=this._local_to_utc(date);dates.push(date)},this));fromArgs=true}else{dates=this.isInput?this.element.val():this.element.data("date")||this.element.find("input").val();if(dates&&this.o.multidate)dates=dates.split(this.o.multidateSeparator);else dates=[dates];delete this.element.data().date}dates=$.map(dates,$.proxy(function(date){return DPGlobal.parseDate(date,this.o.format,this.o.language)},this));dates=$.grep(dates,$.proxy(function(date){return date<this.o.startDate||date>this.o.endDate||!date},this),true);this.dates.replace(dates);if(this.dates.length)this.viewDate=new Date(this.dates.get(-1));else if(this.viewDate<this.o.startDate)this.viewDate=new Date(this.o.startDate);else if(this.viewDate>this.o.endDate)this.viewDate=new Date(this.o.endDate);if(fromArgs){this.setValue()}else if(dates.length){if(String(oldDates)!==String(this.dates))this._trigger("changeDate")}if(!this.dates.length&&oldDates.length)this._trigger("clearDate");this.fill();return this},fillDow:function(){var dowCnt=this.o.weekStart,html="<tr>";if(this.o.calendarWeeks){this.picker.find(".datepicker-days thead tr:first-child .datepicker-switch").attr("colspan",function(i,val){return parseInt(val)+1});var cell='<th class="cw">&#160;</th>';html+=cell}while(dowCnt<this.o.weekStart+7){html+='<th class="dow">'+dates[this.o.language].daysMin[dowCnt++%7]+"</th>"}html+="</tr>";this.picker.find(".datepicker-days thead").append(html)},fillMonths:function(){var html="",i=0;while(i<12){html+='<span class="month">'+dates[this.o.language].monthsShort[i++]+"</span>"}this.picker.find(".datepicker-months td").html(html)},setRange:function(range){if(!range||!range.length)delete this.range;else this.range=$.map(range,function(d){return d.valueOf()});this.fill()},getClassNames:function(date){var cls=[],year=this.viewDate.getUTCFullYear(),month=this.viewDate.getUTCMonth(),today=new Date;if(date.getUTCFullYear()<year||date.getUTCFullYear()===year&&date.getUTCMonth()<month){cls.push("old")}else if(date.getUTCFullYear()>year||date.getUTCFullYear()===year&&date.getUTCMonth()>month){cls.push("new")}if(this.focusDate&&date.valueOf()===this.focusDate.valueOf())cls.push("focused");if(this.o.todayHighlight&&date.getUTCFullYear()===today.getFullYear()&&date.getUTCMonth()===today.getMonth()&&date.getUTCDate()===today.getDate()){cls.push("today")}if(this.dates.contains(date)!==-1)cls.push("active");if(date.valueOf()<this.o.startDate||date.valueOf()>this.o.endDate||$.inArray(date.getUTCDay(),this.o.daysOfWeekDisabled)!==-1){cls.push("disabled")}if(this.o.datesDisabled.length>0&&$.grep(this.o.datesDisabled,function(d){return isUTCEquals(date,d)}).length>0){cls.push("disabled","disabled-date")}if(this.range){if(date>this.range[0]&&date<this.range[this.range.length-1]){cls.push("range")}if($.inArray(date.valueOf(),this.range)!==-1){cls.push("selected")}}return cls},fill:function(){var d=new Date(this.viewDate),year=d.getUTCFullYear(),month=d.getUTCMonth(),startYear=this.o.startDate!==-Infinity?this.o.startDate.getUTCFullYear():-Infinity,startMonth=this.o.startDate!==-Infinity?this.o.startDate.getUTCMonth():-Infinity,endYear=this.o.endDate!==Infinity?this.o.endDate.getUTCFullYear():Infinity,endMonth=this.o.endDate!==Infinity?this.o.endDate.getUTCMonth():Infinity,todaytxt=dates[this.o.language].today||dates["en"].today||"",cleartxt=dates[this.o.language].clear||dates["en"].clear||"",tooltip;if(isNaN(year)||isNaN(month))return;this.picker.find(".datepicker-days thead .datepicker-switch").text(dates[this.o.language].months[month]+" "+year);this.picker.find("tfoot .today").text(todaytxt).toggle(this.o.todayBtn!==false);this.picker.find("tfoot .clear").text(cleartxt).toggle(this.o.clearBtn!==false);this.updateNavArrows();this.fillMonths();var prevMonth=UTCDate(year,month-1,28),day=DPGlobal.getDaysInMonth(prevMonth.getUTCFullYear(),prevMonth.getUTCMonth());prevMonth.setUTCDate(day);prevMonth.setUTCDate(day-(prevMonth.getUTCDay()-this.o.weekStart+7)%7);var nextMonth=new Date(prevMonth);nextMonth.setUTCDate(nextMonth.getUTCDate()+42);nextMonth=nextMonth.valueOf();var html=[];var clsName;while(prevMonth.valueOf()<nextMonth){if(prevMonth.getUTCDay()===this.o.weekStart){html.push("<tr>");if(this.o.calendarWeeks){var ws=new Date(+prevMonth+(this.o.weekStart-prevMonth.getUTCDay()-7)%7*864e5),th=new Date(Number(ws)+(7+4-ws.getUTCDay())%7*864e5),yth=new Date(Number(yth=UTCDate(th.getUTCFullYear(),0,1))+(7+4-yth.getUTCDay())%7*864e5),calWeek=(th-yth)/864e5/7+1;html.push('<td class="cw">'+calWeek+"</td>")}}clsName=this.getClassNames(prevMonth);clsName.push("day");if(this.o.beforeShowDay!==$.noop){var before=this.o.beforeShowDay(this._utc_to_local(prevMonth));if(before===undefined)before={};else if(typeof before==="boolean")before={enabled:before};else if(typeof before==="string")before={classes:before};if(before.enabled===false)clsName.push("disabled");if(before.classes)clsName=clsName.concat(before.classes.split(/\s+/));if(before.tooltip)tooltip=before.tooltip}clsName=$.unique(clsName);html.push('<td class="'+clsName.join(" ")+'"'+(tooltip?' title="'+tooltip+'"':"")+">"+prevMonth.getUTCDate()+"</td>");tooltip=null;if(prevMonth.getUTCDay()===this.o.weekEnd){html.push("</tr>")}prevMonth.setUTCDate(prevMonth.getUTCDate()+1)}this.picker.find(".datepicker-days tbody").empty().append(html.join(""));var months=this.picker.find(".datepicker-months").find("th:eq(1)").text(year).end().find("span").removeClass("active");$.each(this.dates,function(i,d){if(d.getUTCFullYear()===year)months.eq(d.getUTCMonth()).addClass("active")});if(year<startYear||year>endYear){months.addClass("disabled")}if(year===startYear){months.slice(0,startMonth).addClass("disabled")}if(year===endYear){months.slice(endMonth+1).addClass("disabled")}if(this.o.beforeShowMonth!==$.noop){var that=this;$.each(months,function(i,month){if(!$(month).hasClass("disabled")){var moDate=new Date(year,i,1);var before=that.o.beforeShowMonth(moDate);if(before===false)$(month).addClass("disabled")}})}html="";year=parseInt(year/10,10)*10;var yearCont=this.picker.find(".datepicker-years").find("th:eq(1)").text(year+"-"+(year+9)).end().find("td");year-=1;var years=$.map(this.dates,function(d){return d.getUTCFullYear()}),classes;for(var i=-1;i<11;i++){classes=["year"];if(i===-1)classes.push("old");else if(i===10)classes.push("new");if($.inArray(year,years)!==-1)classes.push("active");if(year<startYear||year>endYear)classes.push("disabled");html+='<span class="'+classes.join(" ")+'">'+year+"</span>";year+=1}yearCont.html(html)},updateNavArrows:function(){if(!this._allow_update)return;var d=new Date(this.viewDate),year=d.getUTCFullYear(),month=d.getUTCMonth();switch(this.viewMode){case 0:if(this.o.startDate!==-Infinity&&year<=this.o.startDate.getUTCFullYear()&&month<=this.o.startDate.getUTCMonth()){this.picker.find(".prev").css({visibility:"hidden"})}else{this.picker.find(".prev").css({visibility:"visible"})}if(this.o.endDate!==Infinity&&year>=this.o.endDate.getUTCFullYear()&&month>=this.o.endDate.getUTCMonth()){this.picker.find(".next").css({visibility:"hidden"})}else{this.picker.find(".next").css({visibility:"visible"})}break;case 1:case 2:if(this.o.startDate!==-Infinity&&year<=this.o.startDate.getUTCFullYear()){this.picker.find(".prev").css({visibility:"hidden"})}else{this.picker.find(".prev").css({visibility:"visible"})}if(this.o.endDate!==Infinity&&year>=this.o.endDate.getUTCFullYear()){this.picker.find(".next").css({visibility:"hidden"})}else{this.picker.find(".next").css({visibility:"visible"})}break}},click:function(e){e.preventDefault();var target=$(e.target).closest("span, td, th"),year,month,day;if(target.length===1){switch(target[0].nodeName.toLowerCase()){case"th":switch(target[0].className){case"datepicker-switch":this.showMode(1);break;case"prev":case"next":var dir=DPGlobal.modes[this.viewMode].navStep*(target[0].className==="prev"?-1:1);switch(this.viewMode){case 0:this.viewDate=this.moveMonth(this.viewDate,dir);this._trigger("changeMonth",this.viewDate);break;case 1:case 2:this.viewDate=this.moveYear(this.viewDate,dir);if(this.viewMode===1)this._trigger("changeYear",this.viewDate);break}this.fill();break;case"today":var date=new Date;date=UTCDate(date.getFullYear(),date.getMonth(),date.getDate(),0,0,0);this.showMode(-2);var which=this.o.todayBtn==="linked"?null:"view";this._setDate(date,which);break;case"clear":this.clearDates();break}break;case"span":if(!target.hasClass("disabled")){this.viewDate.setUTCDate(1);if(target.hasClass("month")){day=1;month=target.parent().find("span").index(target);year=this.viewDate.getUTCFullYear();this.viewDate.setUTCMonth(month);this._trigger("changeMonth",this.viewDate);if(this.o.minViewMode===1){this._setDate(UTCDate(year,month,day))}}else{day=1;month=0;year=parseInt(target.text(),10)||0;this.viewDate.setUTCFullYear(year);this._trigger("changeYear",this.viewDate);if(this.o.minViewMode===2){this._setDate(UTCDate(year,month,day))}}this.showMode(-1);this.fill()}break;case"td":if(target.hasClass("day")&&!target.hasClass("disabled")){day=parseInt(target.text(),10)||1;year=this.viewDate.getUTCFullYear();month=this.viewDate.getUTCMonth();if(target.hasClass("old")){if(month===0){month=11;year-=1}else{month-=1}}else if(target.hasClass("new")){if(month===11){month=0;year+=1}else{month+=1}}this._setDate(UTCDate(year,month,day))}break}}if(this.picker.is(":visible")&&this._focused_from){$(this._focused_from).focus()}delete this._focused_from},_toggle_multidate:function(date){var ix=this.dates.contains(date);if(!date){this.dates.clear()}if(ix!==-1){if(this.o.multidate===true||this.o.multidate>1||this.o.toggleActive){this.dates.remove(ix)}}else if(this.o.multidate===false){this.dates.clear();this.dates.push(date)}else{this.dates.push(date)}if(typeof this.o.multidate==="number")while(this.dates.length>this.o.multidate)this.dates.remove(0)},_setDate:function(date,which){if(!which||which==="date")this._toggle_multidate(date&&new Date(date));if(!which||which==="view")this.viewDate=date&&new Date(date);this.fill();this.setValue();if(!which||which!=="view"){this._trigger("changeDate")}var element;if(this.isInput){element=this.element}else if(this.component){element=this.element.find("input")}if(element){element.change()}if(this.o.autoclose&&(!which||which==="date")){this.hide()}},moveMonth:function(date,dir){if(!date)return undefined;if(!dir)return date;var new_date=new Date(date.valueOf()),day=new_date.getUTCDate(),month=new_date.getUTCMonth(),mag=Math.abs(dir),new_month,test;dir=dir>0?1:-1;if(mag===1){test=dir===-1?function(){return new_date.getUTCMonth()===month}:function(){return new_date.getUTCMonth()!==new_month};new_month=month+dir;new_date.setUTCMonth(new_month);if(new_month<0||new_month>11)new_month=(new_month+12)%12}else{for(var i=0;i<mag;i++)new_date=this.moveMonth(new_date,dir);new_month=new_date.getUTCMonth();new_date.setUTCDate(day);test=function(){return new_month!==new_date.getUTCMonth()}}while(test()){new_date.setUTCDate(--day);new_date.setUTCMonth(new_month)}return new_date},moveYear:function(date,dir){return this.moveMonth(date,dir*12)},dateWithinRange:function(date){return date>=this.o.startDate&&date<=this.o.endDate},keydown:function(e){if(!this.picker.is(":visible")){if(e.keyCode===27)this.show();return}var dateChanged=false,dir,newDate,newViewDate,focusDate=this.focusDate||this.viewDate;switch(e.keyCode){case 27:if(this.focusDate){this.focusDate=null;this.viewDate=this.dates.get(-1)||this.viewDate;this.fill()}else this.hide();e.preventDefault();break;case 37:case 39:if(!this.o.keyboardNavigation)break;dir=e.keyCode===37?-1:1;if(e.ctrlKey){newDate=this.moveYear(this.dates.get(-1)||UTCToday(),dir);newViewDate=this.moveYear(focusDate,dir);this._trigger("changeYear",this.viewDate)}else if(e.shiftKey){newDate=this.moveMonth(this.dates.get(-1)||UTCToday(),dir);newViewDate=this.moveMonth(focusDate,dir);this._trigger("changeMonth",this.viewDate)}else{newDate=new Date(this.dates.get(-1)||UTCToday());newDate.setUTCDate(newDate.getUTCDate()+dir);newViewDate=new Date(focusDate);newViewDate.setUTCDate(focusDate.getUTCDate()+dir)}if(this.dateWithinRange(newViewDate)){this.focusDate=this.viewDate=newViewDate;this.setValue();this.fill();e.preventDefault()}break;case 38:case 40:if(!this.o.keyboardNavigation)break;dir=e.keyCode===38?-1:1;if(e.ctrlKey){newDate=this.moveYear(this.dates.get(-1)||UTCToday(),dir);newViewDate=this.moveYear(focusDate,dir);this._trigger("changeYear",this.viewDate)}else if(e.shiftKey){newDate=this.moveMonth(this.dates.get(-1)||UTCToday(),dir);newViewDate=this.moveMonth(focusDate,dir);this._trigger("changeMonth",this.viewDate)}else{newDate=new Date(this.dates.get(-1)||UTCToday());newDate.setUTCDate(newDate.getUTCDate()+dir*7);newViewDate=new Date(focusDate);newViewDate.setUTCDate(focusDate.getUTCDate()+dir*7)}if(this.dateWithinRange(newViewDate)){this.focusDate=this.viewDate=newViewDate;this.setValue();this.fill();e.preventDefault()}break;case 32:break;case 13:focusDate=this.focusDate||this.dates.get(-1)||this.viewDate;if(this.o.keyboardNavigation){this._toggle_multidate(focusDate);dateChanged=true}this.focusDate=null;this.viewDate=this.dates.get(-1)||this.viewDate;this.setValue();this.fill();if(this.picker.is(":visible")){e.preventDefault();if(typeof e.stopPropagation==="function"){e.stopPropagation()}else{e.cancelBubble=true}if(this.o.autoclose)this.hide()}break;case 9:this.focusDate=null;this.viewDate=this.dates.get(-1)||this.viewDate;this.fill();this.hide();break}if(dateChanged){if(this.dates.length)this._trigger("changeDate");else this._trigger("clearDate");var element;if(this.isInput){element=this.element}else if(this.component){element=this.element.find("input")}if(element){element.change()}}},showMode:function(dir){if(dir){this.viewMode=Math.max(this.o.minViewMode,Math.min(2,this.viewMode+dir))}this.picker.children("div").hide().filter(".datepicker-"+DPGlobal.modes[this.viewMode].clsName).css("display","block");this.updateNavArrows()}};var DateRangePicker=function(element,options){this.element=$(element);this.inputs=$.map(options.inputs,function(i){return i.jquery?i[0]:i});delete options.inputs;datepickerPlugin.call($(this.inputs),options).bind("changeDate",$.proxy(this.dateUpdated,this));this.pickers=$.map(this.inputs,function(i){return $(i).data("datepicker")});this.updateDates()};DateRangePicker.prototype={updateDates:function(){this.dates=$.map(this.pickers,function(i){return i.getUTCDate()});this.updateRanges()},updateRanges:function(){var range=$.map(this.dates,function(d){return d.valueOf()});$.each(this.pickers,function(i,p){p.setRange(range)})},dateUpdated:function(e){if(this.updating)return;this.updating=true;var dp=$(e.target).data("datepicker"),new_date=dp.getUTCDate(),i=$.inArray(e.target,this.inputs),j=i-1,k=i+1,l=this.inputs.length;if(i===-1)return;$.each(this.pickers,function(i,p){if(!p.getUTCDate())p.setUTCDate(new_date)});if(new_date<this.dates[j]){while(j>=0&&new_date<this.dates[j]){this.pickers[j--].setUTCDate(new_date)}}else if(new_date>this.dates[k]){while(k<l&&new_date>this.dates[k]){this.pickers[k++].setUTCDate(new_date)}}this.updateDates();delete this.updating},remove:function(){$.map(this.pickers,function(p){p.remove()});delete this.element.data().datepicker}};function opts_from_el(el,prefix){var data=$(el).data(),out={},inkey,replace=new RegExp("^"+prefix.toLowerCase()+"([A-Z])");prefix=new RegExp("^"+prefix.toLowerCase());function re_lower(_,a){return a.toLowerCase()}for(var key in data)if(prefix.test(key)){inkey=key.replace(replace,re_lower);out[inkey]=data[key]}return out}function opts_from_locale(lang){var out={};if(!dates[lang]){lang=lang.split("-")[0];if(!dates[lang])return}var d=dates[lang];$.each(locale_opts,function(i,k){if(k in d)out[k]=d[k]});return out}var old=$.fn.datepicker;var datepickerPlugin=function(option){var args=Array.apply(null,arguments);args.shift();var internal_return;this.each(function(){var $this=$(this),data=$this.data("datepicker"),options=typeof option==="object"&&option;if(!data){var elopts=opts_from_el(this,"date"),xopts=$.extend({},defaults,elopts,options),locopts=opts_from_locale(xopts.language),opts=$.extend({},defaults,locopts,elopts,options);if($this.hasClass("input-daterange")||opts.inputs){var ropts={inputs:opts.inputs||$this.find("input").toArray()};$this.data("datepicker",data=new DateRangePicker(this,$.extend(opts,ropts)))}else{$this.data("datepicker",data=new Datepicker(this,opts))}}if(typeof option==="string"&&typeof data[option]==="function"){internal_return=data[option].apply(data,args);if(internal_return!==undefined)return false}});if(internal_return!==undefined)return internal_return;else return this};$.fn.datepicker=datepickerPlugin;var defaults=$.fn.datepicker.defaults={autoclose:false,beforeShowDay:$.noop,beforeShowMonth:$.noop,calendarWeeks:false,clearBtn:false,toggleActive:false,daysOfWeekDisabled:[],datesDisabled:[],endDate:Infinity,forceParse:true,format:"mm/dd/yyyy",keyboardNavigation:true,language:"en",minViewMode:0,multidate:false,multidateSeparator:",",orientation:"auto",rtl:false,startDate:-Infinity,startView:0,todayBtn:false,todayHighlight:false,weekStart:0,disableTouchKeyboard:false,enableOnReadonly:true,container:"body"};var locale_opts=$.fn.datepicker.locale_opts=["format","rtl","weekStart"];$.fn.datepicker.Constructor=Datepicker;var dates=$.fn.datepicker.dates={en:{days:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],daysShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sun"],daysMin:["Su","Mo","Tu","We","Th","Fr","Sa","Su"],months:["January","February","March","April","May","June","July","August","September","October","November","December"],monthsShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],today:"Today",clear:"Clear"}};var DPGlobal={modes:[{clsName:"days",navFnc:"Month",navStep:1},{clsName:"months",navFnc:"FullYear",navStep:1},{clsName:"years",navFnc:"FullYear",navStep:10}],isLeapYear:function(year){return year%4===0&&year%100!==0||year%400===0},getDaysInMonth:function(year,month){return[31,DPGlobal.isLeapYear(year)?29:28,31,30,31,30,31,31,30,31,30,31][month]},validParts:/dd?|DD?|mm?|MM?|yy(?:yy)?/g,nonpunctuation:/[^ -\/:-@\[\u3400-\u9fff-`{-~\t\n\r]+/g,parseFormat:function(format){var separators=format.replace(this.validParts,"\x00").split("\x00"),parts=format.match(this.validParts);if(!separators||!separators.length||!parts||parts.length===0){throw new Error("Invalid date format.")}return{separators:separators,parts:parts}},parseDate:function(date,format,language){if(!date)return undefined;if(date instanceof Date)return date;if(typeof format==="string")format=DPGlobal.parseFormat(format);var part_re=/([\-+]\d+)([dmwy])/,parts=date.match(/([\-+]\d+)([dmwy])/g),part,dir,i;if(/^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/.test(date)){date=new Date;for(i=0;i<parts.length;i++){part=part_re.exec(parts[i]);dir=parseInt(part[1]);switch(part[2]){case"d":date.setUTCDate(date.getUTCDate()+dir);break;case"m":date=Datepicker.prototype.moveMonth.call(Datepicker.prototype,date,dir);break;case"w":date.setUTCDate(date.getUTCDate()+dir*7);break;case"y":date=Datepicker.prototype.moveYear.call(Datepicker.prototype,date,dir);break}}return UTCDate(date.getUTCFullYear(),date.getUTCMonth(),date.getUTCDate(),0,0,0)}parts=date&&date.match(this.nonpunctuation)||[];
date=new Date;var parsed={},setters_order=["yyyy","yy","M","MM","m","mm","d","dd"],setters_map={yyyy:function(d,v){return d.setUTCFullYear(v)},yy:function(d,v){return d.setUTCFullYear(2e3+v)},m:function(d,v){if(isNaN(d))return d;v-=1;while(v<0)v+=12;v%=12;d.setUTCMonth(v);while(d.getUTCMonth()!==v)d.setUTCDate(d.getUTCDate()-1);return d},d:function(d,v){return d.setUTCDate(v)}},val,filtered;setters_map["M"]=setters_map["MM"]=setters_map["mm"]=setters_map["m"];setters_map["dd"]=setters_map["d"];date=UTCDate(date.getFullYear(),date.getMonth(),date.getDate(),0,0,0);var fparts=format.parts.slice();if(parts.length!==fparts.length){fparts=$(fparts).filter(function(i,p){return $.inArray(p,setters_order)!==-1}).toArray()}function match_part(){var m=this.slice(0,parts[i].length),p=parts[i].slice(0,m.length);return m.toLowerCase()===p.toLowerCase()}if(parts.length===fparts.length){var cnt;for(i=0,cnt=fparts.length;i<cnt;i++){val=parseInt(parts[i],10);part=fparts[i];if(isNaN(val)){switch(part){case"MM":filtered=$(dates[language].months).filter(match_part);val=$.inArray(filtered[0],dates[language].months)+1;break;case"M":filtered=$(dates[language].monthsShort).filter(match_part);val=$.inArray(filtered[0],dates[language].monthsShort)+1;break}}parsed[part]=val}var _date,s;for(i=0;i<setters_order.length;i++){s=setters_order[i];if(s in parsed&&!isNaN(parsed[s])){_date=new Date(date);setters_map[s](_date,parsed[s]);if(!isNaN(_date))date=_date}}}return date},formatDate:function(date,format,language){if(!date)return"";if(typeof format==="string")format=DPGlobal.parseFormat(format);var val={d:date.getUTCDate(),D:dates[language].daysShort[date.getUTCDay()],DD:dates[language].days[date.getUTCDay()],m:date.getUTCMonth()+1,M:dates[language].monthsShort[date.getUTCMonth()],MM:dates[language].months[date.getUTCMonth()],yy:date.getUTCFullYear().toString().substring(2),yyyy:date.getUTCFullYear()};val.dd=(val.d<10?"0":"")+val.d;val.mm=(val.m<10?"0":"")+val.m;date=[];var seps=$.extend([],format.separators);for(var i=0,cnt=format.parts.length;i<=cnt;i++){if(seps.length)date.push(seps.shift());date.push(val[format.parts[i]])}return date.join("")},headTemplate:"<thead>"+"<tr>"+'<th class="prev">&#171;</th>'+'<th colspan="5" class="datepicker-switch"></th>'+'<th class="next">&#187;</th>'+"</tr>"+"</thead>",contTemplate:'<tbody><tr><td colspan="7"></td></tr></tbody>',footTemplate:"<tfoot>"+"<tr>"+'<th colspan="7" class="today"></th>'+"</tr>"+"<tr>"+'<th colspan="7" class="clear"></th>'+"</tr>"+"</tfoot>"};DPGlobal.template='<div class="datepicker">'+'<div class="datepicker-days">'+'<table class=" table-condensed">'+DPGlobal.headTemplate+"<tbody></tbody>"+DPGlobal.footTemplate+"</table>"+"</div>"+'<div class="datepicker-months">'+'<table class="table-condensed">'+DPGlobal.headTemplate+DPGlobal.contTemplate+DPGlobal.footTemplate+"</table>"+"</div>"+'<div class="datepicker-years">'+'<table class="table-condensed">'+DPGlobal.headTemplate+DPGlobal.contTemplate+DPGlobal.footTemplate+"</table>"+"</div>"+"</div>";$.fn.datepicker.DPGlobal=DPGlobal;$.fn.datepicker.noConflict=function(){$.fn.datepicker=old;return this};$.fn.datepicker.version="1.4.0";$(document).on("focus.datepicker.data-api click.datepicker.data-api",'[data-provide="datepicker"]',function(e){var $this=$(this);if($this.data("datepicker"))return;e.preventDefault();datepickerPlugin.call($this,"show")});$(function(){datepickerPlugin.call($('[data-provide="datepicker-inline"]'))})})(window.jQuery);
(function(){var t=[].slice;!function(e,o){"use strict";var n;return n=function(){function t(t,o){null==o&&(o={}),this.$element=e(t),this.options=e.extend({},e.fn.bootstrapSwitch.defaults,o,{state:this.$element.is(":checked"),size:this.$element.data("size"),animate:this.$element.data("animate"),disabled:this.$element.is(":disabled"),readonly:this.$element.is("[readonly]"),onColor:this.$element.data("on-color"),offColor:this.$element.data("off-color"),onText:this.$element.data("on-text"),offText:this.$element.data("off-text"),labelText:this.$element.data("label-text")}),this.$on=e("<span>",{"class":""+this.name+"-handle-on "+this.name+"-"+this.options.onColor,html:this.options.onText}),this.$off=e("<span>",{"class":""+this.name+"-handle-off "+this.name+"-"+this.options.offColor,html:this.options.offText}),this.$label=e("<label>",{"for":this.$element.attr("id"),html:this.options.labelText}),this.$wrapper=e("<div>"),this.$wrapper.addClass(function(t){return function(){var e;return e=[""+t.name],e.push(t.options.state?""+t.name+"-on":""+t.name+"-off"),null!=t.options.size&&e.push(""+t.name+"-"+t.options.size),t.options.animate&&e.push(""+t.name+"-animate"),t.options.disabled&&e.push(""+t.name+"-disabled"),t.options.readonly&&e.push(""+t.name+"-readonly"),t.$element.attr("id")&&e.push(""+t.name+"-id-"+t.$element.attr("id")),e.join(" ")}}(this)),this.$element.on("init",function(t){return function(){return t.options.on.init.call()}}(this)),this.$element.on("switchChange",function(t){return function(){return t.options.on.switchChange.call()}}(this)),this.$div=this.$element.wrap(e("<div>")).parent(),this.$wrapper=this.$div.wrap(this.$wrapper).parent(),this.$element.before(this.$on).before(this.$label).before(this.$off).trigger("init"),this._elementHandlers(),this._handleHandlers(),this._labelHandlers(),this._formHandler()}return t.prototype.name="bootstrap-switch",t.prototype._constructor=t,t.prototype.state=function(t,e){return"undefined"==typeof t?this.options.state:this.options.disabled||this.options.readonly?this.$element:(t=!!t,this.$element.prop("checked",t).trigger("change.bootstrapSwitch",e),this.$element)},t.prototype.toggleState=function(t){return this.options.disabled||this.options.readonly?this.$element:this.$element.prop("checked",!this.options.state).trigger("change.bootstrapSwitch",t)},t.prototype.size=function(t){return"undefined"==typeof t?this.options.size:(null!=this.options.size&&this.$wrapper.removeClass(""+this.name+"-"+this.options.size),this.$wrapper.addClass(""+this.name+"-"+t),this.options.size=t,this.$element)},t.prototype.animate=function(t){return"undefined"==typeof t?this.options.animate:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-animate"),this.options.animate=t,this.$element)},t.prototype.disabled=function(t){return"undefined"==typeof t?this.options.disabled:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-disabled"),this.$element.prop("disabled",t),this.options.disabled=t,this.$element)},t.prototype.toggleDisabled=function(){return this.$element.prop("disabled",!this.options.disabled),this.$wrapper.toggleClass(""+this.name+"-disabled"),this.options.disabled=!this.options.disabled,this.$element},t.prototype.readonly=function(t){return"undefined"==typeof t?this.options.readonly:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-readonly"),this.$element.prop("readonly",t),this.options.readonly=t,this.$element)},t.prototype.toggleReadonly=function(){return this.$element.prop("readonly",!this.options.readonly),this.$wrapper.toggleClass(""+this.name+"-readonly"),this.options.readonly=!this.options.readonly,this.$element},t.prototype.onColor=function(t){var e;return e=this.options.onColor,"undefined"==typeof t?e:(null!=e&&this.$on.removeClass(""+this.name+"-"+e),this.$on.addClass(""+this.name+"-"+t),this.options.onColor=t,this.$element)},t.prototype.offColor=function(t){var e;return e=this.options.offColor,"undefined"==typeof t?e:(null!=e&&this.$off.removeClass(""+this.name+"-"+e),this.$off.addClass(""+this.name+"-"+t),this.options.offColor=t,this.$element)},t.prototype.onText=function(t){return"undefined"==typeof t?this.options.onText:(this.$on.html(t),this.options.onText=t,this.$element)},t.prototype.offText=function(t){return"undefined"==typeof t?this.options.offText:(this.$off.html(t),this.options.offText=t,this.$element)},t.prototype.labelText=function(t){return"undefined"==typeof t?this.options.labelText:(this.$label.html(t),this.options.labelText=t,this.$element)},t.prototype.destroy=function(){var t;return t=this.$element.closest("form"),t.length&&t.off("reset.bootstrapSwitch").removeData("bootstrap-switch"),this.$div.children().not(this.$element).remove(),this.$element.unwrap().unwrap().off(".bootstrapSwitch").removeData("bootstrap-switch"),this.$element},t.prototype._elementHandlers=function(){return this.$element.on({"change.bootstrapSwitch":function(t){return function(o,n){var i;return o.preventDefault(),o.stopPropagation(),o.stopImmediatePropagation(),i=t.$element.is(":checked"),i!==t.options.state?(t.options.state=i,t.$wrapper.removeClass(i?""+t.name+"-off":""+t.name+"-on").addClass(i?""+t.name+"-on":""+t.name+"-off"),n?void 0:(t.$element.is(":radio")&&e("[name='"+t.$element.attr("name")+"']").not(t.$element).prop("checked",!1).trigger("change.bootstrapSwitch",!0),t.$element.trigger("switchChange",{el:t.$element,value:i}))):void 0}}(this),"focus.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.$wrapper.addClass(""+t.name+"-focused")}}(this),"blur.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.$wrapper.removeClass(""+t.name+"-focused")}}(this),"keydown.bootstrapSwitch":function(t){return function(e){if(e.which&&!t.options.disabled&&!t.options.readonly)switch(e.which){case 32:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.toggleState();case 37:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.state(!1);case 39:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.state(!0)}}}(this)})},t.prototype._handleHandlers=function(){return this.$on.on("click.bootstrapSwitch",function(t){return function(){return t.state(!1),t.$element.trigger("focus.bootstrapSwitch")}}(this)),this.$off.on("click.bootstrapSwitch",function(t){return function(){return t.state(!0),t.$element.trigger("focus.bootstrapSwitch")}}(this))},t.prototype._labelHandlers=function(){return this.$label.on({"mousemove.bootstrapSwitch":function(t){return function(e){var o,n,i;if(t.drag)return n=(e.pageX-t.$wrapper.offset().left)/t.$wrapper.width()*100,o=25,i=75,o>n?n=o:n>i&&(n=i),t.$div.css("margin-left",""+(n-i)+"%"),t.$element.trigger("focus.bootstrapSwitch")}}(this),"mousedown.bootstrapSwitch":function(t){return function(){return t.drag||t.options.disabled||t.options.readonly?void 0:(t.drag=!0,t.options.animate&&t.$wrapper.removeClass(""+t.name+"-animate"),t.$element.trigger("focus.bootstrapSwitch"))}}(this),"mouseup.bootstrapSwitch":function(t){return function(){return t.drag?(t.drag=!1,t.$element.prop("checked",parseInt(t.$div.css("margin-left"),10)>-25).trigger("change.bootstrapSwitch"),t.$div.css("margin-left",""),t.options.animate?t.$wrapper.addClass(""+t.name+"-animate"):void 0):void 0}}(this),"mouseleave.bootstrapSwitch":function(t){return function(){return t.$label.trigger("mouseup.bootstrapSwitch")}}(this),"click.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopImmediatePropagation(),t.toggleState(),t.$element.trigger("focus.bootstrapSwitch")}}(this)})},t.prototype._formHandler=function(){var t;return t=this.$element.closest("form"),t.data("bootstrap-switch")?void 0:t.on("reset.bootstrapSwitch",function(){return o.setTimeout(function(){return t.find("input").filter(function(){return e(this).data("bootstrap-switch")}).each(function(){return e(this).bootstrapSwitch("state",!1)})},1)}).data("bootstrap-switch",!0)},t}(),e.fn.bootstrapSwitch=function(){var o,i,s;return i=arguments[0],o=2<=arguments.length?t.call(arguments,1):[],s=this,this.each(function(){var t,r;return t=e(this),r=t.data("bootstrap-switch"),r||t.data("bootstrap-switch",r=new n(this,i)),"string"==typeof i?s=r[i].apply(r,o):void 0}),s},e.fn.bootstrapSwitch.Constructor=n,e.fn.bootstrapSwitch.defaults={state:!0,size:null,animate:!0,disabled:!1,readonly:!1,onColor:"primary",offColor:"default",onText:"ON",offText:"OFF",labelText:"&nbsp;",on:{init:function(){},switchChange:function(){}}}}(window.jQuery,window)}).call(this);
(function($){"use strict";var jCarousel=$.jCarousel={};jCarousel.version="0.3.0";var rRelativeTarget=/^([+\-]=)?(.+)$/;jCarousel.parseTarget=function(target){var relative=false,parts=typeof target!=="object"?rRelativeTarget.exec(target):null;if(parts){target=parseInt(parts[2],10)||0;if(parts[1]){relative=true;if(parts[1]==="-="){target*=-1}}}else if(typeof target!=="object"){target=parseInt(target,10)||0}return{target:target,relative:relative}};jCarousel.detectCarousel=function(element){var carousel;while(element.length>0){carousel=element.filter("[data-jcarousel]");if(carousel.length>0){return carousel}carousel=element.find("[data-jcarousel]");if(carousel.length>0){return carousel}element=element.parent()}return null};jCarousel.base=function(pluginName){return{version:jCarousel.version,_options:{},_element:null,_carousel:null,_init:$.noop,_create:$.noop,_destroy:$.noop,_reload:$.noop,create:function(){this._element.attr("data-"+pluginName.toLowerCase(),true).data(pluginName,this);if(false===this._trigger("create")){return this}this._create();this._trigger("createend");return this},destroy:function(){if(false===this._trigger("destroy")){return this}this._destroy();this._trigger("destroyend");this._element.removeData(pluginName).removeAttr("data-"+pluginName.toLowerCase());return this},reload:function(options){if(false===this._trigger("reload")){return this}if(options){this.options(options)}this._reload();this._trigger("reloadend");return this},element:function(){return this._element},options:function(key,value){if(arguments.length===0){return $.extend({},this._options)}if(typeof key==="string"){if(typeof value==="undefined"){return typeof this._options[key]==="undefined"?null:this._options[key]}this._options[key]=value}else{this._options=$.extend({},this._options,key)}return this},carousel:function(){if(!this._carousel){this._carousel=jCarousel.detectCarousel(this.options("carousel")||this._element);if(!this._carousel){$.error('Could not detect carousel for plugin "'+pluginName+'"')}}return this._carousel},_trigger:function(type,element,data){var event,defaultPrevented=false;data=[this].concat(data||[]);(element||this._element).each(function(){event=$.Event((pluginName+":"+type).toLowerCase());$(this).trigger(event,data);if(event.isDefaultPrevented()){defaultPrevented=true}});return!defaultPrevented}}};jCarousel.plugin=function(pluginName,pluginPrototype){var Plugin=$[pluginName]=function(element,options){this._element=$(element);this.options(options);this._init();this.create()};Plugin.fn=Plugin.prototype=$.extend({},jCarousel.base(pluginName),pluginPrototype);$.fn[pluginName]=function(options){var args=Array.prototype.slice.call(arguments,1),returnValue=this;if(typeof options==="string"){this.each(function(){var instance=$(this).data(pluginName);if(!instance){return $.error("Cannot call methods on "+pluginName+" prior to initialization; "+'attempted to call method "'+options+'"')}if(!$.isFunction(instance[options])||options.charAt(0)==="_"){return $.error('No such method "'+options+'" for '+pluginName+" instance")}var methodValue=instance[options].apply(instance,args);if(methodValue!==instance&&typeof methodValue!=="undefined"){returnValue=methodValue;return false}})}else{this.each(function(){var instance=$(this).data(pluginName);if(instance instanceof Plugin){instance.reload(options)}else{new Plugin(this,options)}})}return returnValue};return Plugin}})(jQuery);(function($,window){"use strict";var toFloat=function(val){return parseFloat(val)||0};$.jCarousel.plugin("jcarousel",{animating:false,tail:0,inTail:false,resizeTimer:null,lt:null,vertical:false,rtl:false,circular:false,underflow:false,relative:false,_options:{list:function(){return this.element().children().eq(0)},items:function(){return this.list().children()},animation:400,transitions:false,wrap:null,vertical:null,rtl:null,center:false},_list:null,_items:null,_target:null,_first:null,_last:null,_visible:null,_fullyvisible:null,_init:function(){var self=this;this.onWindowResize=function(){if(self.resizeTimer){clearTimeout(self.resizeTimer)}self.resizeTimer=setTimeout(function(){self.reload()},100)};return this},_create:function(){this._reload();$(window).on("resize.jcarousel",this.onWindowResize)},_destroy:function(){$(window).off("resize.jcarousel",this.onWindowResize)},_reload:function(){this.vertical=this.options("vertical");if(this.vertical==null){this.vertical=this.list().height()>this.list().width()}this.rtl=this.options("rtl");if(this.rtl==null){this.rtl=function(element){if((""+element.attr("dir")).toLowerCase()==="rtl"){return true}var found=false;element.parents("[dir]").each(function(){if(/rtl/i.test($(this).attr("dir"))){found=true;return false}});return found}(this._element)}this.lt=this.vertical?"top":"left";this.relative=this.list().css("position")==="relative";this._list=null;this._items=null;var item=this._target&&this.index(this._target)>=0?this._target:this.closest();this.circular=this.options("wrap")==="circular";this.underflow=false;var props={left:0,top:0};if(item.length>0){this._prepare(item);this.list().find("[data-jcarousel-clone]").remove();this._items=null;this.underflow=this._fullyvisible.length>=this.items().length;this.circular=this.circular&&!this.underflow;props[this.lt]=this._position(item)+"px"}this.move(props);return this},list:function(){if(this._list===null){var option=this.options("list");this._list=$.isFunction(option)?option.call(this):this._element.find(option)}return this._list},items:function(){if(this._items===null){var option=this.options("items");this._items=($.isFunction(option)?option.call(this):this.list().find(option)).not("[data-jcarousel-clone]")}return this._items},index:function(item){return this.items().index(item)},closest:function(){var self=this,pos=this.list().position()[this.lt],closest=$(),stop=false,lrb=this.vertical?"bottom":this.rtl&&!this.relative?"left":"right",width;if(this.rtl&&this.relative&&!this.vertical){pos+=this.list().width()-this.clipping()}this.items().each(function(){closest=$(this);if(stop){return false}var dim=self.dimension(closest);pos+=dim;if(pos>=0){width=dim-toFloat(closest.css("margin-"+lrb));if(Math.abs(pos)-dim+width/2<=0){stop=true}else{return false}}});return closest},target:function(){return this._target},first:function(){return this._first},last:function(){return this._last},visible:function(){return this._visible},fullyvisible:function(){return this._fullyvisible},hasNext:function(){if(false===this._trigger("hasnext")){return true}var wrap=this.options("wrap"),end=this.items().length-1;return end>=0&&(wrap&&wrap!=="first"||this.index(this._last)<end||this.tail&&!this.inTail)?true:false},hasPrev:function(){if(false===this._trigger("hasprev")){return true}var wrap=this.options("wrap");return this.items().length>0&&(wrap&&wrap!=="last"||this.index(this._first)>0||this.tail&&this.inTail)?true:false},clipping:function(){return this._element["inner"+(this.vertical?"Height":"Width")]()},dimension:function(element){return element["outer"+(this.vertical?"Height":"Width")](true)},scroll:function(target,animate,callback){if(this.animating){return this}if(false===this._trigger("scroll",null,[target,animate])){return this}if($.isFunction(animate)){callback=animate;animate=true}var parsed=$.jCarousel.parseTarget(target);if(parsed.relative){var end=this.items().length-1,scroll=Math.abs(parsed.target),wrap=this.options("wrap"),current,first,index,start,curr,isVisible,props,i;if(parsed.target>0){var last=this.index(this._last);if(last>=end&&this.tail){if(!this.inTail){this._scrollTail(animate,callback)}else{if(wrap==="both"||wrap==="last"){this._scroll(0,animate,callback)}else{if($.isFunction(callback)){callback.call(this,false)}}}}else{current=this.index(this._target);if(this.underflow&&current===end&&(wrap==="circular"||wrap==="both"||wrap==="last")||!this.underflow&&last===end&&(wrap==="both"||wrap==="last")){this._scroll(0,animate,callback)}else{index=current+scroll;if(this.circular&&index>end){i=end;curr=this.items().get(-1);while(i++<index){curr=this.items().eq(0);isVisible=this._visible.index(curr)>=0;if(isVisible){curr.after(curr.clone(true).attr("data-jcarousel-clone",true))}this.list().append(curr);if(!isVisible){props={};props[this.lt]=this.dimension(curr);this.moveBy(props)}this._items=null}this._scroll(curr,animate,callback)}else{this._scroll(Math.min(index,end),animate,callback)}}}}else{if(this.inTail){this._scroll(Math.max(this.index(this._first)-scroll+1,0),animate,callback)}else{first=this.index(this._first);current=this.index(this._target);start=this.underflow?current:first;index=start-scroll;if(start<=0&&(this.underflow&&wrap==="circular"||wrap==="both"||wrap==="first")){this._scroll(end,animate,callback)}else{if(this.circular&&index<0){i=index;curr=this.items().get(0);while(i++<0){curr=this.items().eq(-1);isVisible=this._visible.index(curr)>=0;if(isVisible){curr.after(curr.clone(true).attr("data-jcarousel-clone",true))}this.list().prepend(curr);this._items=null;var dim=this.dimension(curr);props={};props[this.lt]=-dim;this.moveBy(props)}this._scroll(curr,animate,callback)}else{this._scroll(Math.max(index,0),animate,callback)}}}}}else{this._scroll(parsed.target,animate,callback)}this._trigger("scrollend");return this},moveBy:function(properties,opts){var position=this.list().position(),multiplier=1,correction=0;if(this.rtl&&!this.vertical){multiplier=-1;if(this.relative){correction=this.list().width()-this.clipping()}}if(properties.left){properties.left=position.left+correction+toFloat(properties.left)*multiplier+"px"}if(properties.top){properties.top=position.top+correction+toFloat(properties.top)*multiplier+"px"}return this.move(properties,opts)},move:function(properties,opts){opts=opts||{};var option=this.options("transitions"),transitions=!!option,transforms=!!option.transforms,transforms3d=!!option.transforms3d,duration=opts.duration||0,list=this.list();if(!transitions&&duration>0){list.animate(properties,opts);return}var complete=opts.complete||$.noop,css={};if(transitions){var backup=list.css(["transitionDuration","transitionTimingFunction","transitionProperty"]),oldComplete=complete;complete=function(){$(this).css(backup);oldComplete.call(this)};css={transitionDuration:(duration>0?duration/1e3:0)+"s",transitionTimingFunction:option.easing||opts.easing,transitionProperty:duration>0?function(){if(transforms||transforms3d){return"all"}return properties.left?"left":"top"}():"none",transform:"none"}}if(transforms3d){css.transform="translate3d("+(properties.left||0)+","+(properties.top||0)+",0)"}else if(transforms){css.transform="translate("+(properties.left||0)+","+(properties.top||0)+")"}else{$.extend(css,properties)}if(transitions&&duration>0){list.one("transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd",complete)}list.css(css);if(duration<=0){list.each(function(){complete.call(this)})}},_scroll:function(item,animate,callback){if(this.animating){if($.isFunction(callback)){callback.call(this,false)}return this}if(typeof item!=="object"){item=this.items().eq(item)}else if(typeof item.jquery==="undefined"){item=$(item)}if(item.length===0){if($.isFunction(callback)){callback.call(this,false)}return this}this.inTail=false;this._prepare(item);var pos=this._position(item),currPos=this.list().position()[this.lt];if(pos===currPos){if($.isFunction(callback)){callback.call(this,false)}return this}var properties={};properties[this.lt]=pos+"px";this._animate(properties,animate,callback);return this},_scrollTail:function(animate,callback){if(this.animating||!this.tail){if($.isFunction(callback)){callback.call(this,false)}return this}var pos=this.list().position()[this.lt];if(this.rtl&&this.relative&&!this.vertical){pos+=this.list().width()-this.clipping()}if(this.rtl&&!this.vertical){pos+=this.tail}else{pos-=this.tail}this.inTail=true;var properties={};properties[this.lt]=pos+"px";this._update({target:this._target.next(),fullyvisible:this._fullyvisible.slice(1).add(this._visible.last())});this._animate(properties,animate,callback);return this},_animate:function(properties,animate,callback){callback=callback||$.noop;if(false===this._trigger("animate")){callback.call(this,false);return this}this.animating=true;var animation=this.options("animation"),complete=$.proxy(function(){this.animating=false;var c=this.list().find("[data-jcarousel-clone]");if(c.length>0){c.remove();this._reload()}this._trigger("animateend");callback.call(this,true)},this);var opts=typeof animation==="object"?$.extend({},animation):{duration:animation},oldComplete=opts.complete||$.noop;if(animate===false){opts.duration=0}else if(typeof $.fx.speeds[opts.duration]!=="undefined"){opts.duration=$.fx.speeds[opts.duration]}opts.complete=function(){complete();oldComplete.call(this)};this.move(properties,opts);return this},_prepare:function(item){var index=this.index(item),idx=index,wh=this.dimension(item),clip=this.clipping(),lrb=this.vertical?"bottom":this.rtl?"left":"right",center=this.options("center"),update={target:item,first:item,last:item,visible:item,fullyvisible:wh<=clip?item:$()},curr,isVisible,margin,dim;if(center){wh/=2;clip/=2}if(wh<clip){while(true){curr=this.items().eq(++idx);if(curr.length===0){if(!this.circular){break}curr=this.items().eq(0);if(item.get(0)===curr.get(0)){break}isVisible=this._visible.index(curr)>=0;if(isVisible){curr.after(curr.clone(true).attr("data-jcarousel-clone",true))}this.list().append(curr);if(!isVisible){var props={};props[this.lt]=this.dimension(curr);this.moveBy(props)}this._items=null}dim=this.dimension(curr);if(dim===0){break}wh+=dim;update.last=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css("margin-"+lrb));if(wh-margin<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}if(!this.circular&&!center&&wh<clip){idx=index;while(true){if(--idx<0){break}curr=this.items().eq(idx);if(curr.length===0){break}dim=this.dimension(curr);if(dim===0){break}wh+=dim;update.first=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css("margin-"+lrb));if(wh-margin<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}this._update(update);this.tail=0;if(!center&&this.options("wrap")!=="circular"&&this.options("wrap")!=="custom"&&this.index(update.last)===this.items().length-1){wh-=toFloat(update.last.css("margin-"+lrb));if(wh>clip){this.tail=wh-clip}}return this},_position:function(item){var first=this._first,pos=first.position()[this.lt],center=this.options("center"),centerOffset=center?this.clipping()/2-this.dimension(first)/2:0;if(this.rtl&&!this.vertical){if(this.relative){pos-=this.list().width()-this.dimension(first)}else{pos-=this.clipping()-this.dimension(first)}pos+=centerOffset}else{pos-=centerOffset}if(!center&&(this.index(item)>this.index(first)||this.inTail)&&this.tail){pos=this.rtl&&!this.vertical?pos-this.tail:pos+this.tail;this.inTail=true}else{this.inTail=false}return-pos},_update:function(update){var self=this,current={target:this._target||$(),first:this._first||$(),last:this._last||$(),visible:this._visible||$(),fullyvisible:this._fullyvisible||$()},back=this.index(update.first||current.first)<this.index(current.first),key,doUpdate=function(key){var elIn=[],elOut=[];update[key].each(function(){if(current[key].index(this)<0){elIn.push(this)}});current[key].each(function(){if(update[key].index(this)<0){elOut.push(this)}});if(back){elIn=elIn.reverse()}else{elOut=elOut.reverse()}self._trigger(key+"in",$(elIn));self._trigger(key+"out",$(elOut));self["_"+key]=update[key]};for(key in update){doUpdate(key)}return this}})})(jQuery,window);(function($){"use strict";$.jcarousel.fn.scrollIntoView=function(target,animate,callback){var parsed=$.jCarousel.parseTarget(target),first=this.index(this._fullyvisible.first()),last=this.index(this._fullyvisible.last()),index;if(parsed.relative){index=parsed.target<0?Math.max(0,first+parsed.target):last+parsed.target}else{index=typeof parsed.target!=="object"?parsed.target:this.index(parsed.target)}if(index<first){return this.scroll(index,animate,callback)}if(index>=first&&index<=last){if($.isFunction(callback)){callback.call(this,false)}return this}var items=this.items(),clip=this.clipping(),lrb=this.vertical?"bottom":this.rtl?"left":"right",wh=0,curr;while(true){curr=items.eq(index);if(curr.length===0){break}wh+=this.dimension(curr);if(wh>=clip){var margin=parseFloat(curr.css("margin-"+lrb))||0;if(wh-margin!==clip){index++}break}if(index<=0){break}index--}return this.scroll(index,animate,callback)}})(jQuery);(function($){"use strict";$.jCarousel.plugin("jcarouselControl",{_options:{target:"+=1",event:"click",method:"scroll"},_active:null,_init:function(){this.onDestroy=$.proxy(function(){this._destroy();this.carousel().one("jcarousel:createend",$.proxy(this._create,this))},this);this.onReload=$.proxy(this._reload,this);this.onEvent=$.proxy(function(e){e.preventDefault();var method=this.options("method");if($.isFunction(method)){method.call(this)}else{this.carousel().jcarousel(this.options("method"),this.options("target"))}},this)},_create:function(){this.carousel().one("jcarousel:destroy",this.onDestroy).on("jcarousel:reloadend jcarousel:scrollend",this.onReload);this._element.on(this.options("event")+".jcarouselcontrol",this.onEvent);this._reload()},_destroy:function(){this._element.off(".jcarouselcontrol",this.onEvent);this.carousel().off("jcarousel:destroy",this.onDestroy).off("jcarousel:reloadend jcarousel:scrollend",this.onReload)},_reload:function(){var parsed=$.jCarousel.parseTarget(this.options("target")),carousel=this.carousel(),active;if(parsed.relative){active=carousel.jcarousel(parsed.target>0?"hasNext":"hasPrev")}else{var target=typeof parsed.target!=="object"?carousel.jcarousel("items").eq(parsed.target):parsed.target;active=carousel.jcarousel("target").index(target)>=0}if(this._active!==active){this._trigger(active?"active":"inactive");this._active=active}return this}})})(jQuery);(function($){"use strict";$.jCarousel.plugin("jcarouselPagination",{_options:{perPage:null,item:function(page){return'<a href="#'+page+'">'+page+"</a>"},event:"click",method:"scroll"},_pages:{},_items:{},_currentPage:null,_init:function(){this.onDestroy=$.proxy(function(){this._destroy();this.carousel().one("jcarousel:createend",$.proxy(this._create,this))},this);this.onReload=$.proxy(this._reload,this);this.onScroll=$.proxy(this._update,this)},_create:function(){this.carousel().one("jcarousel:destroy",this.onDestroy).on("jcarousel:reloadend",this.onReload).on("jcarousel:scrollend",this.onScroll);this._reload()},_destroy:function(){this._clear();this.carousel().off("jcarousel:destroy",this.onDestroy).off("jcarousel:reloadend",this.onReload).off("jcarousel:scrollend",this.onScroll)},_reload:function(){var perPage=this.options("perPage");this._pages={};this._items={};if($.isFunction(perPage)){perPage=perPage.call(this)}if(perPage==null){this._pages=this._calculatePages()}else{var pp=parseInt(perPage,10)||0,items=this.carousel().jcarousel("items"),page=1,i=0,curr;while(true){curr=items.eq(i++);if(curr.length===0){break}if(!this._pages[page]){this._pages[page]=curr}else{this._pages[page]=this._pages[page].add(curr)}if(i%pp===0){page++}}}this._clear();var self=this,carousel=this.carousel().data("jcarousel"),element=this._element,item=this.options("item");$.each(this._pages,function(page,carouselItems){var currItem=self._items[page]=$(item.call(self,page,carouselItems));currItem.on(self.options("event")+".jcarouselpagination",$.proxy(function(){var target=carouselItems.eq(0);if(carousel.circular){var currentIndex=carousel.index(carousel.target()),newIndex=carousel.index(target);if(parseFloat(page)>parseFloat(self._currentPage)){if(newIndex<currentIndex){target="+="+(carousel.items().length-currentIndex+newIndex)}}else{if(newIndex>currentIndex){target="-="+(currentIndex+(carousel.items().length-newIndex))}}}carousel[this.options("method")](target)},self));element.append(currItem)});this._update()},_update:function(){var target=this.carousel().jcarousel("target"),currentPage;$.each(this._pages,function(page,carouselItems){carouselItems.each(function(){if(target.is(this)){currentPage=page;return false}});if(currentPage){return false}});if(this._currentPage!==currentPage){this._trigger("inactive",this._items[this._currentPage]);this._trigger("active",this._items[currentPage])}this._currentPage=currentPage},items:function(){return this._items},_clear:function(){this._element.empty();this._currentPage=null},_calculatePages:function(){var carousel=this.carousel().data("jcarousel"),items=carousel.items(),clip=carousel.clipping(),wh=0,idx=0,page=1,pages={},curr;while(true){curr=items.eq(idx++);if(curr.length===0){break}if(!pages[page]){pages[page]=curr}else{pages[page]=pages[page].add(curr)}wh+=carousel.dimension(curr);if(wh>=clip){page++;wh=0}}return pages}})})(jQuery);(function($){"use strict";$.jCarousel.plugin("jcarouselAutoscroll",{_options:{target:"+=1",interval:3e3,autostart:true},_timer:null,_init:function(){this.onDestroy=$.proxy(function(){this._destroy();this.carousel().one("jcarousel:createend",$.proxy(this._create,this))},this);this.onAnimateEnd=$.proxy(this.start,this)},_create:function(){this.carousel().one("jcarousel:destroy",this.onDestroy);if(this.options("autostart")){this.start()}},_destroy:function(){this.stop();this.carousel().off("jcarousel:destroy",this.onDestroy)},start:function(){this.stop();this.carousel().one("jcarousel:animateend",this.onAnimateEnd);this._timer=setTimeout($.proxy(function(){this.carousel().jcarousel("scroll",this.options("target"))},this),this.options("interval"));return this},stop:function(){if(this._timer){this._timer=clearTimeout(this._timer)}this.carousel().off("jcarousel:animateend",this.onAnimateEnd);return this}})})(jQuery);
/*!
 * Colcade v0.2.0
 * Lightweight masonry layout
 * by David DeSandro
 * MIT license
 */

/*jshint browser: true, undef: true, unused: true */

( function( window, factory ) {
	// universal module definition
	/*jshint strict: false */
	/*global define: false, module: false */
	if ( typeof define == 'function' && define.amd ) {
		// AMD
		define( factory );
	} else if ( typeof module == 'object' && module.exports ) {
		// CommonJS
		module.exports = factory();
	} else {
		// browser global
		window.Colcade = factory();
	}

}( window, function factory() {

// -------------------------- Colcade -------------------------- //

	function Colcade( element, options ) {
		element = getQueryElement( element );

		// do not initialize twice on same element
		if ( element && element.colcadeGUID ) {
			var instance = instances[ element.colcadeGUID ];
			instance.option( options );
			return instance;
		}

		this.element = element;
		// options
		this.options = {};
		this.option( options );
		// kick things off
		this.create();
	}

	var proto = Colcade.prototype;

	proto.option = function( options ) {
		this.options = extend( this.options, options );
	};

// globally unique identifiers
	var GUID = 0;
// internal store of all Colcade intances
	var instances = {};

	proto.create = function() {
		this.errorCheck();
		// add guid for Colcade.data
		var guid = this.guid = ++GUID;
		this.element.colcadeGUID = guid;
		instances[ guid ] = this; // associate via id
		// update initial properties & layout
		this.reload();
		// events
		this._windowResizeHandler = this.onWindowResize.bind( this );
		this._loadHandler = this.onLoad.bind( this );
		window.addEventListener( 'resize', this._windowResizeHandler );
		this.element.addEventListener( 'load', this._loadHandler, true );
	};

	proto.errorCheck = function() {
		var errors = [];
		if ( !this.element ) {
			errors.push( 'Bad element: ' + this.element );
		}
		if ( !this.options.columns ) {
			errors.push( 'columns option required: ' + this.options.columns );
		}
		if ( !this.options.items ) {
			errors.push( 'items option required: ' + this.options.items );
		}

		if ( errors.length ) {
			throw new Error( '[Colcade error] ' + errors.join('. ') );
		}
	};

// update properties and do layout
	proto.reload = function() {
		this.updateColumns();
		this.updateItems();
		this.layout();
	};

	proto.updateColumns = function() {
		this.columns = querySelect( this.options.columns, this.element );
	};

	proto.updateItems = function() {
		this.items = querySelect( this.options.items, this.element );
	};

	proto.getActiveColumns = function() {
		return this.columns.filter( function( column ) {
			var style = getComputedStyle( column );
			return style.display != 'none';
		});
	};

// ----- layout ----- //

// public, updates activeColumns
	proto.layout = function() {
		this.activeColumns = this.getActiveColumns();
		this._layout();
	};

// private, does not update activeColumns
	proto._layout = function() {
		// reset column heights
		this.columnHeights = this.activeColumns.map( function() {
			return 0;
		});
		// layout all items
		this.layoutItems( this.items );
	};

	proto.layoutItems = function( items ) {
		items.forEach( this.layoutItem, this );
	};

	proto.layoutItem = function( item ) {
		// layout item by appending to column
		var minHeight = Math.min.apply( Math, this.columnHeights );
		var index = this.columnHeights.indexOf( minHeight );
		this.activeColumns[ index ].appendChild( item );
		// at least 1px, if item hasn't loaded
		// Not exactly accurate, but it's cool
		this.columnHeights[ index ] += item.offsetHeight || 1;
	};

// ----- adding items ----- //

	proto.append = function( elems ) {
		var items = this.getQueryItems( elems );
		// add items to collection
		this.items = this.items.concat( items );
		// lay them out
		this.layoutItems( items );
	};

	proto.prepend = function( elems ) {
		var items = this.getQueryItems( elems );
		// add items to collection
		this.items = items.concat( this.items );
		// lay out everything
		this._layout();
	};

	proto.getQueryItems = function( elems ) {
		elems = makeArray( elems );
		var fragment = document.createDocumentFragment();
		elems.forEach( function( elem ) {
			fragment.appendChild( elem );
		});
		return querySelect( this.options.items, fragment );
	};

// ----- measure column height ----- //

	proto.measureColumnHeight = function( elem ) {
		var boundingRect = this.element.getBoundingClientRect();
		this.activeColumns.forEach( function( column, i ) {
			// if elem, measure only that column
			// if no elem, measure all columns
			if ( !elem || column.contains( elem ) ) {
				var lastChildRect = column.lastElementChild.getBoundingClientRect();
				// not an exact calculation as it includes top border, and excludes item bottom margin
				this.columnHeights[ i ] = lastChildRect.bottom - boundingRect.top;
			}
		}, this );
	};

// ----- events ----- //

	proto.onWindowResize = function() {
		clearTimeout( this.resizeTimeout );
		this.resizeTimeout = setTimeout( function() {
			this.onDebouncedResize();
		}.bind( this ), 100 );
	};

	proto.onDebouncedResize = function() {
		var activeColumns = this.getActiveColumns();
		// check if columns changed
		var isSameLength = activeColumns.length == this.activeColumns.length;
		var isSameColumns = true;
		this.activeColumns.forEach( function( column, i ) {
			isSameColumns = isSameColumns && column == activeColumns[i];
		});
		if ( isSameLength && isSameColumns ) {
			return;
		}
		// activeColumns changed
		this.activeColumns = activeColumns;
		this._layout();
	};

	proto.onLoad = function( event ) {
		this.measureColumnHeight( event.target );
	};

// ----- destroy ----- //

	proto.destroy = function() {
		// move items back to container
		this.items.forEach( function( item ) {
			this.element.appendChild( item );
		}, this );
		// remove events
		window.removeEventListener( 'resize', this._windowResizeHandler );
		this.element.removeEventListener( 'load', this._loadHandler, true );
		// remove data
		delete this.element.colcadeGUID;
		delete instances[ this.guid ];
	};

// -------------------------- HTML init -------------------------- //

	docReady( function() {
		var dataElems = querySelect('[data-colcade]');
		dataElems.forEach( htmlInit );
	});

	function htmlInit( elem ) {
		// convert attribute "foo: bar, qux: baz" into object
		var attr = elem.getAttribute('data-colcade');
		var attrParts = attr.split(',');
		var options = {};
		attrParts.forEach( function( part ) {
			var pair = part.split(':');
			var key = pair[0].trim();
			var value = pair[1].trim();
			options[ key ] = value;
		});

		new Colcade( elem, options );
	}

	Colcade.data = function( elem ) {
		elem = getQueryElement( elem );
		var id = elem && elem.colcadeGUID;
		return id && instances[ id ];
	};

// -------------------------- jQuery -------------------------- //

	Colcade.makeJQueryPlugin = function( $ ) {
		$ = $ || window.jQuery;
		if ( !$ ) {
			return;
		}

		$.fn.colcade = function( arg0 /*, arg1 */) {
			// method call $().colcade( 'method', { options } )
			if ( typeof arg0 == 'string' ) {
				// shift arguments by 1
				var args = Array.prototype.slice.call( arguments, 1 );
				return methodCall( this, arg0, args );
			}
			// just $().colcade({ options })
			plainCall( this, arg0 );
			return this;
		};

		function methodCall( $elems, methodName, args ) {
			var returnValue;
			$elems.each( function( i, elem ) {
				// get instance
				var colcade = $.data( elem, 'colcade' );
				if ( !colcade ) {
					return;
				}
				// apply method, get return value
				var value = colcade[ methodName ].apply( colcade, args );
				// set return value if value is returned, use only first value
				returnValue = returnValue === undefined ? value : returnValue;
			});
			return returnValue !== undefined ? returnValue : $elems;
		}

		function plainCall( $elems, options ) {
			$elems.each( function( i, elem ) {
				var colcade = $.data( elem, 'colcade' );
				if ( colcade ) {
					// set options & init
					colcade.option( options );
					colcade.layout();
				} else {
					// initialize new instance
					colcade = new Colcade( elem, options );
					$.data( elem, 'colcade', colcade );
				}
			});
		}
	};

// try making plugin
	Colcade.makeJQueryPlugin();

// -------------------------- utils -------------------------- //

	function extend( a, b ) {
		for ( var prop in b ) {
			a[ prop ] = b[ prop ];
		}
		return a;
	}

// turn element or nodeList into an array
	function makeArray( obj ) {
		var ary = [];
		if ( Array.isArray( obj ) ) {
			// use object if already an array
			ary = obj;
		} else if ( obj && typeof obj.length == 'number' ) {
			// convert nodeList to array
			for ( var i=0; i < obj.length; i++ ) {
				ary.push( obj[i] );
			}
		} else {
			// array of single index
			ary.push( obj );
		}
		return ary;
	}

// get array of elements
	function querySelect( selector, elem ) {
		elem = elem || document;
		var elems = elem.querySelectorAll( selector );
		return makeArray( elems );
	}

	function getQueryElement( elem ) {
		if ( typeof elem == 'string' ) {
			elem = document.querySelector( elem );
		}
		return elem;
	}

	function docReady( onReady ) {
		if ( document.readyState == 'complete' ) {
			onReady();
			return;
		}
		document.addEventListener( 'DOMContentLoaded', onReady );
	}

// -------------------------- end -------------------------- //

	return Colcade;

}));

/**!
 * TableSorter 2.17.7 - Client-side table sorting with ease!
 * @requires jQuery v1.2.6+
 *
 * Copyright (c) 2007 Christian Bach
 * Examples and docs at: http://tablesorter.com
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * @type jQuery
 * @name tablesorter
 * @cat Plugins/Tablesorter
 * @author Christian Bach/christian.bach@polyester.se
 * @contributor Rob Garrison/https://github.com/Mottie/tablesorter
 */
/*jshint browser:true, jquery:true, unused:false, expr: true */
/*global console:false, alert:false */
!(function($) {
	"use strict";
	$.extend({
		/*jshint supernew:true */
		tablesorter: new function() {

			var ts = this;

			ts.version = "2.17.7";

			ts.parsers = [];
			ts.widgets = [];
			ts.defaults = {

				// *** appearance
				theme            : 'default',  // adds tablesorter-{theme} to the table for styling
				widthFixed       : false,      // adds colgroup to fix widths of columns
				showProcessing   : false,      // show an indeterminate timer icon in the header when the table is sorted or filtered.

				headerTemplate   : '{content}',// header layout template (HTML ok); {content} = innerHTML, {icon} = <i/> (class from cssIcon)
				onRenderTemplate : null,       // function(index, template){ return template; }, (template is a string)
				onRenderHeader   : null,       // function(index){}, (nothing to return)

				// *** functionality
				cancelSelection  : true,       // prevent text selection in the header
				tabIndex         : true,       // add tabindex to header for keyboard accessibility
				dateFormat       : 'mmddyyyy', // other options: "ddmmyyy" or "yyyymmdd"
				sortMultiSortKey : 'shiftKey', // key used to select additional columns
				sortResetKey     : 'ctrlKey',  // key used to remove sorting on a column
				usNumberFormat   : true,       // false for German "1.234.567,89" or French "1 234 567,89"
				delayInit        : false,      // if false, the parsed table contents will not update until the first sort
				serverSideSorting: false,      // if true, server-side sorting should be performed because client-side sorting will be disabled, but the ui and events will still be used.

				// *** sort options
				headers          : {},         // set sorter, string, empty, locked order, sortInitialOrder, filter, etc.
				ignoreCase       : true,       // ignore case while sorting
				sortForce        : null,       // column(s) first sorted; always applied
				sortList         : [],         // Initial sort order; applied initially; updated when manually sorted
				sortAppend       : null,       // column(s) sorted last; always applied
				sortStable       : false,      // when sorting two rows with exactly the same content, the original sort order is maintained

				sortInitialOrder : 'asc',      // sort direction on first click
				sortLocaleCompare: false,      // replace equivalent character (accented characters)
				sortReset        : false,      // third click on the header will reset column to default - unsorted
				sortRestart      : false,      // restart sort to "sortInitialOrder" when clicking on previously unsorted columns

				emptyTo          : 'bottom',   // sort empty cell to bottom, top, none, zero
				stringTo         : 'max',      // sort strings in numerical column as max, min, top, bottom, zero
				textExtraction   : 'basic',    // text extraction method/function - function(node, table, cellIndex){}
				textAttribute    : 'data-text',// data-attribute that contains alternate cell text (used in textExtraction function)
				textSorter       : null,       // choose overall or specific column sorter function(a, b, direction, table, columnIndex) [alt: ts.sortText]
				numberSorter     : null,       // choose overall numeric sorter function(a, b, direction, maxColumnValue)

				// *** widget options
				widgets: [],                   // method to add widgets, e.g. widgets: ['zebra']
				widgetOptions    : {
					zebra : [ 'even', 'odd' ]    // zebra widget alternating row class names
				},
				initWidgets      : true,       // apply widgets on tablesorter initialization

				// *** callbacks
				initialized      : null,       // function(table){},

				// *** extra css class names
				tableClass       : '',
				cssAsc           : '',
				cssDesc          : '',
				cssNone          : '',
				cssHeader        : '',
				cssHeaderRow     : '',
				cssProcessing    : '', // processing icon applied to header during sort/filter

				cssChildRow      : 'tablesorter-childRow', // class name indiciating that a row is to be attached to the its parent 
				cssIcon          : 'tablesorter-icon',     //  if this class exists, a <i> will be added to the header automatically
				cssInfoBlock     : 'tablesorter-infoOnly', // don't sort tbody with this class name (only one class name allowed here!)

				// *** selectors
				selectorHeaders  : '> thead th, > thead td',
				selectorSort     : 'th, td',   // jQuery selector of content within selectorHeaders that is clickable to trigger a sort
				selectorRemove   : '.remove-me',

				// *** advanced
				debug            : false,

				// *** Internal variables
				headerList: [],
				empties: {},
				strings: {},
				parsers: []

				// deprecated; but retained for backwards compatibility
				// widgetZebra: { css: ["even", "odd"] }

			};

			// internal css classes - these will ALWAYS be added to
			// the table and MUST only contain one class name - fixes #381
			ts.css = {
				table      : 'tablesorter',
				cssHasChild: 'tablesorter-hasChildRow',
				childRow   : 'tablesorter-childRow',
				header     : 'tablesorter-header',
				headerRow  : 'tablesorter-headerRow',
				headerIn   : 'tablesorter-header-inner',
				icon       : 'tablesorter-icon',
				info       : 'tablesorter-infoOnly',
				processing : 'tablesorter-processing',
				sortAsc    : 'tablesorter-headerAsc',
				sortDesc   : 'tablesorter-headerDesc',
				sortNone   : 'tablesorter-headerUnSorted'
			};

			// labels applied to sortable headers for accessibility (aria) support
			ts.language = {
				sortAsc  : 'Ascending sort applied, ',
				sortDesc : 'Descending sort applied, ',
				sortNone : 'No sort applied, ',
				nextAsc  : 'activate to apply an ascending sort',
				nextDesc : 'activate to apply a descending sort',
				nextNone : 'activate to remove the sort'
			};

			/* debuging utils */
			function log() {
				var a = arguments[0],
						s = arguments.length > 1 ? Array.prototype.slice.call(arguments) : a;
				if (typeof console !== "undefined" && typeof console.log !== "undefined") {
					console[ /error/i.test(a) ? 'error' : /warn/i.test(a) ? 'warn' : 'log' ](s);
				} else {
					alert(s);
				}
			}

			function benchmark(s, d) {
				log(s + " (" + (new Date().getTime() - d.getTime()) + "ms)");
			}

			ts.log = log;
			ts.benchmark = benchmark;

			// $.isEmptyObject from jQuery v1.4
			function isEmptyObject(obj) {
				/*jshint forin: false */
				for (var name in obj) {
					return false;
				}
				return true;
			}

			function getElementText(table, node, cellIndex) {
				if (!node) { return ""; }
				var te, c = table.config,
						t = c.textExtraction || '',
						text = "";
				if (t === "basic") {
					// check data-attribute first
					text = $(node).attr(c.textAttribute) || node.textContent || node.innerText || $(node).text() || "";
				} else {
					if (typeof(t) === "function") {
						text = t(node, table, cellIndex);
					} else if (typeof (te = ts.getColumnData( table, t, cellIndex )) === 'function') {
						text = te(node, table, cellIndex);
					} else {
						// previous "simple" method
						text = node.textContent || node.innerText || $(node).text() || "";
					}
				}
				return $.trim(text);
			}

			function detectParserForColumn(table, rows, rowIndex, cellIndex) {
				var cur,
						i = ts.parsers.length,
						node = false,
						nodeValue = '',
						keepLooking = true;
				while (nodeValue === '' && keepLooking) {
					rowIndex++;
					if (rows[rowIndex]) {
						node = rows[rowIndex].cells[cellIndex];
						nodeValue = getElementText(table, node, cellIndex);
						if (table.config.debug) {
							log('Checking if value was empty on row ' + rowIndex + ', column: ' + cellIndex + ': "' + nodeValue + '"');
						}
					} else {
						keepLooking = false;
					}
				}
				while (--i >= 0) {
					cur = ts.parsers[i];
					// ignore the default text parser because it will always be true
					if (cur && cur.id !== 'text' && cur.is && cur.is(nodeValue, table, node)) {
						return cur;
					}
				}
				// nothing found, return the generic parser (text)
				return ts.getParserById('text');
			}

			function buildParserCache(table) {
				var c = table.config,
				// update table bodies in case we start with an empty table
						tb = c.$tbodies = c.$table.children('tbody:not(.' + c.cssInfoBlock + ')'),
						rows, list, l, i, h, ch, np, p, e, time,
						j = 0,
						parsersDebug = "",
						len = tb.length;
				if ( len === 0) {
					return c.debug ? log('Warning: *Empty table!* Not building a parser cache') : '';
				} else if (c.debug) {
					time = new Date();
					log('Detecting parsers for each column');
				}
				list = {
					extractors: [],
					parsers: []
				};
				while (j < len) {
					rows = tb[j].rows;
					if (rows[j]) {
						l = c.columns; // rows[j].cells.length;
						for (i = 0; i < l; i++) {
							h = c.$headers.filter('[data-column="' + i + '"]:last');
							// get column indexed table cell
							ch = ts.getColumnData( table, c.headers, i );
							// get column parser/extractor
							e = ts.getParserById( ts.getData(h, ch, 'extractor') );
							p = ts.getParserById( ts.getData(h, ch, 'sorter') );
							np = ts.getData(h, ch, 'parser') === 'false';
							// empty cells behaviour - keeping emptyToBottom for backwards compatibility
							c.empties[i] = ts.getData(h, ch, 'empty') || c.emptyTo || (c.emptyToBottom ? 'bottom' : 'top' );
							// text strings behaviour in numerical sorts
							c.strings[i] = ts.getData(h, ch, 'string') || c.stringTo || 'max';
							if (np) {
								p = ts.getParserById('no-parser');
							}
							if (!e) {
								// For now, maybe detect someday
								e = false;
							}
							if (!p) {
								p = detectParserForColumn(table, rows, -1, i);
							}
							if (c.debug) {
								parsersDebug += "column:" + i + "; extractor:" + e.id + "; parser:" + p.id + "; string:" + c.strings[i] + '; empty: ' + c.empties[i] + "\n";
							}
							list.parsers[i] = p;
							list.extractors[i] = e;
						}
					}
					j += (list.parsers.length) ? len : 1;
				}
				if (c.debug) {
					log(parsersDebug ? parsersDebug : "No parsers detected");
					benchmark("Completed detecting parsers", time);
				}
				c.parsers = list.parsers;
				c.extractors = list.extractors;
			}

			/* utils */
			function buildCache(table) {
				var cc, t, tx, v, i, j, k, $row, rows, cols, cacheTime,
						totalRows, rowData, colMax,
						c = table.config,
						$tb = c.$table.children('tbody'),
						extractors = c.extractors,
						parsers = c.parsers;
				c.cache = {};
				c.totalRows = 0;
				// if no parsers found, return - it's an empty table.
				if (!parsers) {
					return c.debug ? log('Warning: *Empty table!* Not building a cache') : '';
				}
				if (c.debug) {
					cacheTime = new Date();
				}
				// processing icon
				if (c.showProcessing) {
					ts.isProcessing(table, true);
				}
				for (k = 0; k < $tb.length; k++) {
					colMax = []; // column max value per tbody
					cc = c.cache[k] = {
						normalized: [] // array of normalized row data; last entry contains "rowData" above
						// colMax: #   // added at the end
					};

					// ignore tbodies with class name from c.cssInfoBlock
					if (!$tb.eq(k).hasClass(c.cssInfoBlock)) {
						totalRows = ($tb[k] && $tb[k].rows.length) || 0;
						for (i = 0; i < totalRows; ++i) {
							rowData = {
								// order: original row order #
								// $row : jQuery Object[]
								child: [] // child row text (filter widget)
							};
							/** Add the table data to main data array */
							$row = $($tb[k].rows[i]);
							rows = [ new Array(c.columns) ];
							cols = [];
							// if this is a child row, add it to the last row's children and continue to the next row
							// ignore child row class, if it is the first row
							if ($row.hasClass(c.cssChildRow) && i !== 0) {
								t = cc.normalized.length - 1;
								cc.normalized[t][c.columns].$row = cc.normalized[t][c.columns].$row.add($row);
								// add "hasChild" class name to parent row
								if (!$row.prev().hasClass(c.cssChildRow)) {
									$row.prev().addClass(ts.css.cssHasChild);
								}
								// save child row content (un-parsed!)
								rowData.child[t] = $.trim( $row[0].textContent || $row[0].innerText || $row.text() || "" );
								// go to the next for loop
								continue;
							}
							rowData.$row = $row;
							rowData.order = i; // add original row position to rowCache
							for (j = 0; j < c.columns; ++j) {
								if (typeof parsers[j] === 'undefined') {
									if (c.debug) {
										log('No parser found for cell:', $row[0].cells[j], 'does it have a header?');
									}
									continue;
								}
								t = getElementText(table, $row[0].cells[j], j);
								// do extract before parsing if there is one
								if (typeof extractors[j].id === 'undefined') {
									tx = t;
								} else {
									tx = extractors[j].format(t, table, $row[0].cells[j], j);
								}
								// allow parsing if the string is empty, previously parsing would change it to zero,
								// in case the parser needs to extract data from the table cell attributes
								v = parsers[j].id === 'no-parser' ? '' : parsers[j].format(tx, table, $row[0].cells[j], j);
								cols.push( c.ignoreCase && typeof v === 'string' ? v.toLowerCase() : v );
								if ((parsers[j].type || '').toLowerCase() === "numeric") {
									// determine column max value (ignore sign)
									colMax[j] = Math.max(Math.abs(v) || 0, colMax[j] || 0);
								}
							}
							// ensure rowData is always in the same location (after the last column)
							cols[c.columns] = rowData;
							cc.normalized.push(cols);
						}
						cc.colMax = colMax;
						// total up rows, not including child rows
						c.totalRows += cc.normalized.length;
					}
				}
				if (c.showProcessing) {
					ts.isProcessing(table); // remove processing icon
				}
				if (c.debug) {
					benchmark("Building cache for " + totalRows + " rows", cacheTime);
				}
			}

			// init flag (true) used by pager plugin to prevent widget application
			function appendToTable(table, init) {
				var c = table.config,
						wo = c.widgetOptions,
						b = table.tBodies,
						rows = [],
						cc = c.cache,
						n, totalRows, $bk, $tb,
						i, k, appendTime;
				// empty table - fixes #206/#346
				if (isEmptyObject(cc)) {
					// run pager appender in case the table was just emptied
					return c.appender ? c.appender(table, rows) :
							table.isUpdating ? c.$table.trigger("updateComplete", table) : ''; // Fixes #532
				}
				if (c.debug) {
					appendTime = new Date();
				}
				for (k = 0; k < b.length; k++) {
					$bk = $(b[k]);
					if ($bk.length && !$bk.hasClass(c.cssInfoBlock)) {
						// get tbody
						$tb = ts.processTbody(table, $bk, true);
						n = cc[k].normalized;
						totalRows = n.length;
						for (i = 0; i < totalRows; i++) {
							rows.push(n[i][c.columns].$row);
							// removeRows used by the pager plugin; don't render if using ajax - fixes #411
							if (!c.appender || (c.pager && (!c.pager.removeRows || !wo.pager_removeRows) && !c.pager.ajax)) {
								$tb.append(n[i][c.columns].$row);
							}
						}
						// restore tbody
						ts.processTbody(table, $tb, false);
					}
				}
				if (c.appender) {
					c.appender(table, rows);
				}
				if (c.debug) {
					benchmark("Rebuilt table", appendTime);
				}
				// apply table widgets; but not before ajax completes
				if (!init && !c.appender) { ts.applyWidget(table); }
				if (table.isUpdating) {
					c.$table.trigger("updateComplete", table);
				}
			}

			function formatSortingOrder(v) {
				// look for "d" in "desc" order; return true
				return (/^d/i.test(v) || v === 1);
			}

			function buildHeaders(table) {
				var ch, $t,
						h, i, t, lock, time,
						c = table.config;
				c.headerList = [];
				c.headerContent = [];
				if (c.debug) {
					time = new Date();
				}
				// children tr in tfoot - see issue #196 & #547
				c.columns = ts.computeColumnIndex( c.$table.children('thead, tfoot').children('tr') );
				// add icon if cssIcon option exists
				i = c.cssIcon ? '<i class="' + ( c.cssIcon === ts.css.icon ? ts.css.icon : c.cssIcon + ' ' + ts.css.icon ) + '"></i>' : '';
				// redefine c.$headers here in case of an updateAll that replaces or adds an entire header cell - see #683
				c.$headers = $(table).find(c.selectorHeaders).each(function(index) {
					$t = $(this);
					// make sure to get header cell & not column indexed cell
					ch = ts.getColumnData( table, c.headers, index, true );
					// save original header content
					c.headerContent[index] = $(this).html();
					// set up header template
					t = c.headerTemplate.replace(/\{content\}/g, $(this).html()).replace(/\{icon\}/g, i);
					if (c.onRenderTemplate) {
						h = c.onRenderTemplate.apply($t, [index, t]);
						if (h && typeof h === 'string') { t = h; } // only change t if something is returned
					}
					$(this).html('<div class="' + ts.css.headerIn + '">' + t + '</div>'); // faster than wrapInner

					if (c.onRenderHeader) { c.onRenderHeader.apply($t, [index]); }
					this.column = parseInt( $(this).attr('data-column'), 10);
					this.order = formatSortingOrder( ts.getData($t, ch, 'sortInitialOrder') || c.sortInitialOrder ) ? [1,0,2] : [0,1,2];
					this.count = -1; // set to -1 because clicking on the header automatically adds one
					this.lockedOrder = false;
					lock = ts.getData($t, ch, 'lockedOrder') || false;
					if (typeof lock !== 'undefined' && lock !== false) {
						this.order = this.lockedOrder = formatSortingOrder(lock) ? [1,1,1] : [0,0,0];
					}
					$t.addClass(ts.css.header + ' ' + c.cssHeader);
					// add cell to headerList
					c.headerList[index] = this;
					// add to parent in case there are multiple rows
					$t.parent().addClass(ts.css.headerRow + ' ' + c.cssHeaderRow);
					// allow keyboard cursor to focus on element
					if (c.tabIndex) { $t.attr("tabindex", 0); }
				}).attr({
					scope: 'col'
				});
				// enable/disable sorting
				updateHeader(table);
				if (c.debug) {
					benchmark("Built headers:", time);
					log(c.$headers);
				}
			}

			function commonUpdate(table, resort, callback) {
				var c = table.config;
				// remove rows/elements before update
				c.$table.find(c.selectorRemove).remove();
				// rebuild parsers
				buildParserCache(table);
				// rebuild the cache map
				buildCache(table);
				checkResort(c.$table, resort, callback);
			}

			function updateHeader(table) {
				var s, $th, col,
						c = table.config;
				c.$headers.each(function(index, th){
					$th = $(th);
					col = ts.getColumnData( table, c.headers, index, true );
					// add "sorter-false" class if "parser-false" is set
					s = ts.getData( th, col, 'sorter' ) === 'false' || ts.getData( th, col, 'parser' ) === 'false';
					th.sortDisabled = s;
					$th[ s ? 'addClass' : 'removeClass' ]('sorter-false').attr('aria-disabled', '' + s);
					// aria-controls - requires table ID
					if (table.id) {
						if (s) {
							$th.removeAttr('aria-controls');
						} else {
							$th.attr('aria-controls', table.id);
						}
					}
				});
			}

			function setHeadersCss(table) {
				var f, i, j,
						c = table.config,
						list = c.sortList,
						len = list.length,
						none = ts.css.sortNone + ' ' + c.cssNone,
						css = [ts.css.sortAsc + ' ' + c.cssAsc, ts.css.sortDesc + ' ' + c.cssDesc],
						aria = ['ascending', 'descending'],
				// find the footer
						$t = $(table).find('tfoot tr').children().add(c.$extraHeaders).removeClass(css.join(' '));
				// remove all header information
				c.$headers
						.removeClass(css.join(' '))
						.addClass(none).attr('aria-sort', 'none');
				for (i = 0; i < len; i++) {
					// direction = 2 means reset!
					if (list[i][1] !== 2) {
						// multicolumn sorting updating - choose the :last in case there are nested columns
						f = c.$headers.not('.sorter-false').filter('[data-column="' + list[i][0] + '"]' + (len === 1 ? ':last' : '') );
						if (f.length) {
							for (j = 0; j < f.length; j++) {
								if (!f[j].sortDisabled) {
									f.eq(j).removeClass(none).addClass(css[list[i][1]]).attr('aria-sort', aria[list[i][1]]);
								}
							}
							// add sorted class to footer & extra headers, if they exist
							if ($t.length) {
								$t.filter('[data-column="' + list[i][0] + '"]').removeClass(none).addClass(css[list[i][1]]);
							}
						}
					}
				}
				// add verbose aria labels
				c.$headers.not('.sorter-false').each(function(){
					var $this = $(this),
							nextSort = this.order[(this.count + 1) % (c.sortReset ? 3 : 2)],
							txt = $this.text() + ': ' +
									ts.language[ $this.hasClass(ts.css.sortAsc) ? 'sortAsc' : $this.hasClass(ts.css.sortDesc) ? 'sortDesc' : 'sortNone' ] +
									ts.language[ nextSort === 0 ? 'nextAsc' : nextSort === 1 ? 'nextDesc' : 'nextNone' ];
					$this.attr('aria-label', txt );
				});
			}

			// automatically add col group, and column sizes if set
			function fixColumnWidth(table) {
				if (table.config.widthFixed && $(table).find('colgroup').length === 0) {
					var colgroup = $('<colgroup>'),
							overallWidth = $(table).width();
					// only add col for visible columns - fixes #371
					$(table.tBodies[0]).find("tr:first").children(":visible").each(function() {
						colgroup.append($('<col>').css('width', parseInt(($(this).width()/overallWidth)*1000, 10)/10 + '%'));
					});
					$(table).prepend(colgroup);
				}
			}

			function updateHeaderSortCount(table, list) {
				var s, t, o, col, primary,
						c = table.config,
						sl = list || c.sortList;
				c.sortList = [];
				$.each(sl, function(i,v){
					// ensure all sortList values are numeric - fixes #127
					col = parseInt(v[0], 10);
					// make sure header exists
					o = c.$headers.filter('[data-column="' + col + '"]:last')[0];
					if (o) { // prevents error if sorton array is wrong
						// o.count = o.count + 1;
						t = ('' + v[1]).match(/^(1|d|s|o|n)/);
						t = t ? t[0] : '';
						// 0/(a)sc (default), 1/(d)esc, (s)ame, (o)pposite, (n)ext
						switch(t) {
							case '1': case 'd': // descending
							t = 1;
							break;
							case 's': // same direction (as primary column)
								// if primary sort is set to "s", make it ascending
								t = primary || 0;
								break;
							case 'o':
								s = o.order[(primary || 0) % (c.sortReset ? 3 : 2)];
								// opposite of primary column; but resets if primary resets
								t = s === 0 ? 1 : s === 1 ? 0 : 2;
								break;
							case 'n':
								o.count = o.count + 1;
								t = o.order[(o.count) % (c.sortReset ? 3 : 2)];
								break;
							default: // ascending
								t = 0;
								break;
						}
						primary = i === 0 ? t : primary;
						s = [ col, parseInt(t, 10) || 0 ];
						c.sortList.push(s);
						t = $.inArray(s[1], o.order); // fixes issue #167
						o.count = t >= 0 ? t : s[1] % (c.sortReset ? 3 : 2);
					}
				});
			}

			function getCachedSortType(parsers, i) {
				return (parsers && parsers[i]) ? parsers[i].type || '' : '';
			}

			function initSort(table, cell, event){
				if (table.isUpdating) {
					// let any updates complete before initializing a sort
					return setTimeout(function(){ initSort(table, cell, event); }, 50);
				}
				var arry, indx, col, order, s,
						c = table.config,
						key = !event[c.sortMultiSortKey],
						$table = c.$table;
				// Only call sortStart if sorting is enabled
				$table.trigger("sortStart", table);
				// get current column sort order
				cell.count = event[c.sortResetKey] ? 2 : (cell.count + 1) % (c.sortReset ? 3 : 2);
				// reset all sorts on non-current column - issue #30
				if (c.sortRestart) {
					indx = cell;
					c.$headers.each(function() {
						// only reset counts on columns that weren't just clicked on and if not included in a multisort
						if (this !== indx && (key || !$(this).is('.' + ts.css.sortDesc + ',.' + ts.css.sortAsc))) {
							this.count = -1;
						}
					});
				}
				// get current column index
				indx = cell.column;
				// user only wants to sort on one column
				if (key) {
					// flush the sort list
					c.sortList = [];
					if (c.sortForce !== null) {
						arry = c.sortForce;
						for (col = 0; col < arry.length; col++) {
							if (arry[col][0] !== indx) {
								c.sortList.push(arry[col]);
							}
						}
					}
					// add column to sort list
					order = cell.order[cell.count];
					if (order < 2) {
						c.sortList.push([indx, order]);
						// add other columns if header spans across multiple
						if (cell.colSpan > 1) {
							for (col = 1; col < cell.colSpan; col++) {
								c.sortList.push([indx + col, order]);
							}
						}
					}
					// multi column sorting
				} else {
					// get rid of the sortAppend before adding more - fixes issue #115 & #523
					if (c.sortAppend && c.sortList.length > 1) {
						for (col = 0; col < c.sortAppend.length; col++) {
							s = ts.isValueInArray(c.sortAppend[col][0], c.sortList);
							if (s >= 0) {
								c.sortList.splice(s,1);
							}
						}
					}
					// the user has clicked on an already sorted column
					if (ts.isValueInArray(indx, c.sortList) >= 0) {
						// reverse the sorting direction
						for (col = 0; col < c.sortList.length; col++) {
							s = c.sortList[col];
							order = c.$headers.filter('[data-column="' + s[0] + '"]:last')[0];
							if (s[0] === indx) {
								// order.count seems to be incorrect when compared to cell.count
								s[1] = order.order[cell.count];
								if (s[1] === 2) {
									c.sortList.splice(col,1);
									order.count = -1;
								}
							}
						}
					} else {
						// add column to sort list array
						order = cell.order[cell.count];
						if (order < 2) {
							c.sortList.push([indx, order]);
							// add other columns if header spans across multiple
							if (cell.colSpan > 1) {
								for (col = 1; col < cell.colSpan; col++) {
									c.sortList.push([indx + col, order]);
								}
							}
						}
					}
				}
				if (c.sortAppend !== null) {
					arry = c.sortAppend;
					for (col = 0; col < arry.length; col++) {
						if (arry[col][0] !== indx) {
							c.sortList.push(arry[col]);
						}
					}
				}
				// sortBegin event triggered immediately before the sort
				$table.trigger("sortBegin", table);
				// setTimeout needed so the processing icon shows up
				setTimeout(function(){
					// set css for headers
					setHeadersCss(table);
					multisort(table);
					appendToTable(table);
					$table.trigger("sortEnd", table);
				}, 1);
			}

			// sort multiple columns
			function multisort(table) { /*jshint loopfunc:true */
				var i, k, num, col, sortTime, colMax,
						cache, order, sort, x, y,
						dir = 0,
						c = table.config,
						cts = c.textSorter || '',
						sortList = c.sortList,
						l = sortList.length,
						bl = table.tBodies.length;
				if (c.serverSideSorting || isEmptyObject(c.cache)) { // empty table - fixes #206/#346
					return;
				}
				if (c.debug) { sortTime = new Date(); }
				for (k = 0; k < bl; k++) {
					colMax = c.cache[k].colMax;
					cache = c.cache[k].normalized;

					cache.sort(function(a, b) {
						// cache is undefined here in IE, so don't use it!
						for (i = 0; i < l; i++) {
							col = sortList[i][0];
							order = sortList[i][1];
							// sort direction, true = asc, false = desc
							dir = order === 0;

							if (c.sortStable && a[col] === b[col] && l === 1) {
								return a[c.columns].order - b[c.columns].order;
							}

							// fallback to natural sort since it is more robust
							num = /n/i.test(getCachedSortType(c.parsers, col));
							if (num && c.strings[col]) {
								// sort strings in numerical columns
								if (typeof (c.string[c.strings[col]]) === 'boolean') {
									num = (dir ? 1 : -1) * (c.string[c.strings[col]] ? -1 : 1);
								} else {
									num = (c.strings[col]) ? c.string[c.strings[col]] || 0 : 0;
								}
								// fall back to built-in numeric sort
								// var sort = $.tablesorter["sort" + s](table, a[c], b[c], c, colMax[c], dir);
								sort = c.numberSorter ? c.numberSorter(a[col], b[col], dir, colMax[col], table) :
										ts[ 'sortNumeric' + (dir ? 'Asc' : 'Desc') ](a[col], b[col], num, colMax[col], col, table);
							} else {
								// set a & b depending on sort direction
								x = dir ? a : b;
								y = dir ? b : a;
								// text sort function
								if (typeof(cts) === 'function') {
									// custom OVERALL text sorter
									sort = cts(x[col], y[col], dir, col, table);
								} else if (typeof(cts) === 'object' && cts.hasOwnProperty(col)) {
									// custom text sorter for a SPECIFIC COLUMN
									sort = cts[col](x[col], y[col], dir, col, table);
								} else {
									// fall back to natural sort
									sort = ts[ 'sortNatural' + (dir ? 'Asc' : 'Desc') ](a[col], b[col], col, table, c);
								}
							}
							if (sort) { return sort; }
						}
						return a[c.columns].order - b[c.columns].order;
					});
				}
				if (c.debug) { benchmark("Sorting on " + sortList.toString() + " and dir " + order + " time", sortTime); }
			}

			function resortComplete($table, callback){
				var table = $table[0];
				if (table.isUpdating) {
					$table.trigger('updateComplete');
				}
				if ($.isFunction(callback)) {
					callback($table[0]);
				}
			}

			function checkResort($table, flag, callback) {
				var sl = $table[0].config.sortList;
				// don't try to resort if the table is still processing
				// this will catch spamming of the updateCell method
				if (flag !== false && !$table[0].isProcessing && sl.length) {
					$table.trigger("sorton", [sl, function(){
						resortComplete($table, callback);
					}, true]);
				} else {
					resortComplete($table, callback);
					ts.applyWidget($table[0], false);
				}
			}

			function bindMethods(table){
				var c = table.config,
						$table = c.$table;
				// apply easy methods that trigger bound events
				$table
						.unbind('sortReset update updateRows updateCell updateAll addRows updateComplete sorton appendCache updateCache applyWidgetId applyWidgets refreshWidgets destroy mouseup mouseleave '.split(' ').join(c.namespace + ' '))
						.bind("sortReset" + c.namespace, function(e, callback){
							e.stopPropagation();
							c.sortList = [];
							setHeadersCss(table);
							multisort(table);
							appendToTable(table);
							if ($.isFunction(callback)) {
								callback(table);
							}
						})
						.bind("updateAll" + c.namespace, function(e, resort, callback){
							e.stopPropagation();
							table.isUpdating = true;
							ts.refreshWidgets(table, true, true);
							ts.restoreHeaders(table);
							buildHeaders(table);
							ts.bindEvents(table, c.$headers, true);
							bindMethods(table);
							commonUpdate(table, resort, callback);
						})
						.bind("update" + c.namespace + " updateRows" + c.namespace, function(e, resort, callback) {
							e.stopPropagation();
							table.isUpdating = true;
							// update sorting (if enabled/disabled)
							updateHeader(table);
							commonUpdate(table, resort, callback);
						})
						.bind("updateCell" + c.namespace, function(e, cell, resort, callback) {
							e.stopPropagation();
							table.isUpdating = true;
							$table.find(c.selectorRemove).remove();
							// get position from the dom
							var v, t, row, icell,
									$tb = $table.find('tbody'),
									$cell = $(cell),
							// update cache - format: function(s, table, cell, cellIndex)
							// no closest in jQuery v1.2.6 - tbdy = $tb.index( $(cell).closest('tbody') ),$row = $(cell).closest('tr');
									tbdy = $tb.index( $.fn.closest ? $cell.closest('tbody') : $cell.parents('tbody').filter(':first') ),
									$row = $.fn.closest ? $cell.closest('tr') : $cell.parents('tr').filter(':first');
							cell = $cell[0]; // in case cell is a jQuery object
							// tbody may not exist if update is initialized while tbody is removed for processing
							if ($tb.length && tbdy >= 0) {
								row = $tb.eq(tbdy).find('tr').index( $row );
								icell = $cell.index();
								c.cache[tbdy].normalized[row][c.columns].$row = $row;
								if (typeof c.extractors[icell].id === 'undefined') {
									t = getElementText(table, cell, icell);
								} else {
									t = c.extractors[icell].format( getElementText(table, cell, icell), table, cell, icell );
								}
								v = c.parsers[icell].id === 'no-parser' ? '' :
										c.parsers[icell].format( t, table, cell, icell );
								c.cache[tbdy].normalized[row][icell] = c.ignoreCase && typeof v === 'string' ? v.toLowerCase() : v;
								if ((c.parsers[icell].type || '').toLowerCase() === "numeric") {
									// update column max value (ignore sign)
									c.cache[tbdy].colMax[icell] = Math.max(Math.abs(v) || 0, c.cache[tbdy].colMax[icell] || 0);
								}
								checkResort($table, resort, callback);
							}
						})
						.bind("addRows" + c.namespace, function(e, $row, resort, callback) {
							e.stopPropagation();
							table.isUpdating = true;
							if (isEmptyObject(c.cache)) {
								// empty table, do an update instead - fixes #450
								updateHeader(table);
								commonUpdate(table, resort, callback);
							} else {
								$row = $($row); // make sure we're using a jQuery object
								var i, j, l, t, v, rowData, cells,
										rows = $row.filter('tr').length,
										tbdy = $table.find('tbody').index( $row.parents('tbody').filter(':first') );
								// fixes adding rows to an empty table - see issue #179
								if (!(c.parsers && c.parsers.length)) {
									buildParserCache(table);
								}
								// add each row
								for (i = 0; i < rows; i++) {
									l = $row[i].cells.length;
									cells = [];
									rowData = {
										child: [],
										$row : $row.eq(i),
										order: c.cache[tbdy].normalized.length
									};
									// add each cell
									for (j = 0; j < l; j++) {
										if (typeof c.extractors[j].id === 'undefined') {
											t = getElementText(table, $row[i].cells[j], j);
										} else {
											t = c.extractors[j].format( getElementText(table, $row[i].cells[j], j), table, $row[i].cells[j], j );
										}
										v = c.parsers[j].id === 'no-parser' ? '' :
												c.parsers[j].format( t, table, $row[i].cells[j], j );
										cells[j] = c.ignoreCase && typeof v === 'string' ? v.toLowerCase() : v;
										if ((c.parsers[j].type || '').toLowerCase() === "numeric") {
											// update column max value (ignore sign)
											c.cache[tbdy].colMax[j] = Math.max(Math.abs(cells[j]) || 0, c.cache[tbdy].colMax[j] || 0);
										}
									}
									// add the row data to the end
									cells.push(rowData);
									// update cache
									c.cache[tbdy].normalized.push(cells);
								}
								// resort using current settings
								checkResort($table, resort, callback);
							}
						})
						.bind("updateComplete" + c.namespace, function(){
							table.isUpdating = false;
						})
						.bind("sorton" + c.namespace, function(e, list, callback, init) {
							var c = table.config;
							e.stopPropagation();
							$table.trigger("sortStart", this);
							// update header count index
							updateHeaderSortCount(table, list);
							// set css for headers
							setHeadersCss(table);
							// fixes #346
							if (c.delayInit && isEmptyObject(c.cache)) { buildCache(table); }
							$table.trigger("sortBegin", this);
							// sort the table and append it to the dom
							multisort(table);
							appendToTable(table, init);
							$table.trigger("sortEnd", this);
							ts.applyWidget(table);
							if ($.isFunction(callback)) {
								callback(table);
							}
						})
						.bind("appendCache" + c.namespace, function(e, callback, init) {
							e.stopPropagation();
							appendToTable(table, init);
							if ($.isFunction(callback)) {
								callback(table);
							}
						})
						.bind("updateCache" + c.namespace, function(e, callback){
							// rebuild parsers
							if (!(c.parsers && c.parsers.length)) {
								buildParserCache(table);
							}
							// rebuild the cache map
							buildCache(table);
							if ($.isFunction(callback)) {
								callback(table);
							}
						})
						.bind("applyWidgetId" + c.namespace, function(e, id) {
							e.stopPropagation();
							ts.getWidgetById(id).format(table, c, c.widgetOptions);
						})
						.bind("applyWidgets" + c.namespace, function(e, init) {
							e.stopPropagation();
							// apply widgets
							ts.applyWidget(table, init);
						})
						.bind("refreshWidgets" + c.namespace, function(e, all, dontapply){
							e.stopPropagation();
							ts.refreshWidgets(table, all, dontapply);
						})
						.bind("destroy" + c.namespace, function(e, c, cb){
							e.stopPropagation();
							ts.destroy(table, c, cb);
						})
						.bind("resetToLoadState" + c.namespace, function(){
							// remove all widgets
							ts.refreshWidgets(table, true, true);
							// restore original settings; this clears out current settings, but does not clear
							// values saved to storage.
							c = $.extend(true, ts.defaults, c.originalSettings);
							table.hasInitialized = false;
							// setup the entire table again
							ts.setup( table, c );
						});
			}

			/* public methods */
			ts.construct = function(settings) {
				return this.each(function() {
					var table = this,
					// merge & extend config options
							c = $.extend(true, {}, ts.defaults, settings);
					// save initial settings
					c.originalSettings = settings;
					// create a table from data (build table widget)
					if (!table.hasInitialized && ts.buildTable && this.tagName !== 'TABLE') {
						// return the table (in case the original target is the table's container)
						ts.buildTable(table, c);
					} else {
						ts.setup(table, c);
					}
				});
			};

			ts.setup = function(table, c) {
				// if no thead or tbody, or tablesorter is already present, quit
				if (!table || !table.tHead || table.tBodies.length === 0 || table.hasInitialized === true) {
					return c.debug ? log('ERROR: stopping initialization! No table, thead, tbody or tablesorter has already been initialized') : '';
				}

				var k = '',
						$table = $(table),
						m = $.metadata;
				// initialization flag
				table.hasInitialized = false;
				// table is being processed flag
				table.isProcessing = true;
				// make sure to store the config object
				table.config = c;
				// save the settings where they read
				$.data(table, "tablesorter", c);
				if (c.debug) { $.data( table, 'startoveralltimer', new Date()); }

				// removing this in version 3 (only supports jQuery 1.7+)
				c.supportsDataObject = (function(version) {
					version[0] = parseInt(version[0], 10);
					return (version[0] > 1) || (version[0] === 1 && parseInt(version[1], 10) >= 4);
				})($.fn.jquery.split("."));
				// digit sort text location; keeping max+/- for backwards compatibility
				c.string = { 'max': 1, 'min': -1, 'emptyMin': 1, 'emptyMax': -1, 'zero': 0, 'none': 0, 'null': 0, 'top': true, 'bottom': false };
				// add table theme class only if there isn't already one there
				if (!/tablesorter\-/.test($table.attr('class'))) {
					k = (c.theme !== '' ? ' tablesorter-' + c.theme : '');
				}
				c.table = table;
				c.$table = $table
						.addClass(ts.css.table + ' ' + c.tableClass + k);
				c.$headers = $table.find(c.selectorHeaders);

				// give the table a unique id, which will be used in namespace binding
				if (!c.namespace) {
					c.namespace = '.tablesorter' + Math.random().toString(16).slice(2);
				} else {
					// make sure namespace starts with a period & doesn't have weird characters
					c.namespace = '.' + c.namespace.replace(/\W/g,'');
				}

				c.$table.children().children('tr');
				c.$tbodies = $table.children('tbody:not(.' + c.cssInfoBlock + ')').attr({
					'aria-live' : 'polite',
					'aria-relevant' : 'all'
				});
				if (c.$table.find('caption').length) {
					c.$table.attr('aria-labelledby', 'theCaption');
				}
				c.widgetInit = {}; // keep a list of initialized widgets
				// change textExtraction via data-attribute
				c.textExtraction = c.$table.attr('data-text-extraction') || c.textExtraction || 'basic';
				// build headers
				buildHeaders(table);
				// fixate columns if the users supplies the fixedWidth option
				// do this after theme has been applied
				fixColumnWidth(table);
				// try to auto detect column type, and store in tables config
				buildParserCache(table);
				// start total row count at zero
				c.totalRows = 0;
				// build the cache for the tbody cells
				// delayInit will delay building the cache until the user starts a sort
				if (!c.delayInit) { buildCache(table); }
				// bind all header events and methods
				ts.bindEvents(table, c.$headers, true);
				bindMethods(table);
				// get sort list from jQuery data or metadata
				// in jQuery < 1.4, an error occurs when calling $table.data()
				if (c.supportsDataObject && typeof $table.data().sortlist !== 'undefined') {
					c.sortList = $table.data().sortlist;
				} else if (m && ($table.metadata() && $table.metadata().sortlist)) {
					c.sortList = $table.metadata().sortlist;
				}
				// apply widget init code
				ts.applyWidget(table, true);
				// if user has supplied a sort list to constructor
				if (c.sortList.length > 0) {
					$table.trigger("sorton", [c.sortList, {}, !c.initWidgets, true]);
				} else {
					setHeadersCss(table);
					if (c.initWidgets) {
						// apply widget format
						ts.applyWidget(table, false);
					}
				}

				// show processesing icon
				if (c.showProcessing) {
					$table
							.unbind('sortBegin' + c.namespace + ' sortEnd' + c.namespace)
							.bind('sortBegin' + c.namespace + ' sortEnd' + c.namespace, function(e) {
								clearTimeout(c.processTimer);
								ts.isProcessing(table);
								if (e.type === 'sortBegin') {
									c.processTimer = setTimeout(function(){
										ts.isProcessing(table, true);
									}, 500);
								}
							});
				}

				// initialized
				table.hasInitialized = true;
				table.isProcessing = false;
				if (c.debug) {
					ts.benchmark("Overall initialization time", $.data( table, 'startoveralltimer'));
				}
				$table.trigger('tablesorter-initialized', table);
				if (typeof c.initialized === 'function') { c.initialized(table); }
			};

			ts.getColumnData = function(table, obj, indx, getCell){
				if (typeof obj === 'undefined' || obj === null) { return; }
				table = $(table)[0];
				var result, $h, k,
						c = table.config;
				if (obj[indx]) {
					return getCell ? obj[indx] : obj[c.$headers.index( c.$headers.filter('[data-column="' + indx + '"]:last') )];
				}
				for (k in obj) {
					if (typeof k === 'string') {
						if (getCell) {
							// get header cell
							$h = c.$headers.eq(indx).filter(k);
						} else {
							// get column indexed cell
							$h = c.$headers.filter('[data-column="' + indx + '"]:last').filter(k);
						}
						if ($h.length) {
							return obj[k];
						}
					}
				}
				return result;
			};

			// computeTableHeaderCellIndexes from:
			// http://www.javascripttoolbox.com/lib/table/examples.php
			// http://www.javascripttoolbox.com/temp/table_cellindex.html
			ts.computeColumnIndex = function(trs) {
				var matrix = [],
						lookup = {},
						cols = 0, // determine the number of columns
						i, j, k, l, $cell, cell, cells, rowIndex, cellId, rowSpan, colSpan, firstAvailCol, matrixrow;
				for (i = 0; i < trs.length; i++) {
					cells = trs[i].cells;
					for (j = 0; j < cells.length; j++) {
						cell = cells[j];
						$cell = $(cell);
						rowIndex = cell.parentNode.rowIndex;
						cellId = rowIndex + "-" + $cell.index();
						rowSpan = cell.rowSpan || 1;
						colSpan = cell.colSpan || 1;
						if (typeof(matrix[rowIndex]) === "undefined") {
							matrix[rowIndex] = [];
						}
						// Find first available column in the first row
						for (k = 0; k < matrix[rowIndex].length + 1; k++) {
							if (typeof(matrix[rowIndex][k]) === "undefined") {
								firstAvailCol = k;
								break;
							}
						}
						lookup[cellId] = firstAvailCol;
						cols = Math.max(firstAvailCol, cols);
						// add data-column
						$cell.attr({ 'data-column' : firstAvailCol }); // 'data-row' : rowIndex
						for (k = rowIndex; k < rowIndex + rowSpan; k++) {
							if (typeof(matrix[k]) === "undefined") {
								matrix[k] = [];
							}
							matrixrow = matrix[k];
							for (l = firstAvailCol; l < firstAvailCol + colSpan; l++) {
								matrixrow[l] = "x";
							}
						}
					}
				}
				// may not be accurate if # header columns !== # tbody columns
				return cols + 1; // add one because it's a zero-based index
			};

			// *** Process table ***
			// add processing indicator
			ts.isProcessing = function(table, toggle, $ths) {
				table = $(table);
				var c = table[0].config,
				// default to all headers
						$h = $ths || table.find('.' + ts.css.header);
				if (toggle) {
					// don't use sortList if custom $ths used
					if (typeof $ths !== 'undefined' && c.sortList.length > 0) {
						// get headers from the sortList
						$h = $h.filter(function(){
							// get data-column from attr to keep  compatibility with jQuery 1.2.6
							return this.sortDisabled ? false : ts.isValueInArray( parseFloat($(this).attr('data-column')), c.sortList) >= 0;
						});
					}
					table.add($h).addClass(ts.css.processing + ' ' + c.cssProcessing);
				} else {
					table.add($h).removeClass(ts.css.processing + ' ' + c.cssProcessing);
				}
			};

			// detach tbody but save the position
			// don't use tbody because there are portions that look for a tbody index (updateCell)
			ts.processTbody = function(table, $tb, getIt){
				table = $(table)[0];
				var holdr;
				if (getIt) {
					table.isProcessing = true;
					$tb.before('<span class="tablesorter-savemyplace"/>');
					holdr = ($.fn.detach) ? $tb.detach() : $tb.remove();
					return holdr;
				}
				holdr = $(table).find('span.tablesorter-savemyplace');
				$tb.insertAfter( holdr );
				holdr.remove();
				table.isProcessing = false;
			};

			ts.clearTableBody = function(table) {
				$(table)[0].config.$tbodies.children().detach();
			};

			ts.bindEvents = function(table, $headers, core){
				table = $(table)[0];
				var downTime,
						c = table.config;
				if (core !== true) {
					c.$extraHeaders = c.$extraHeaders ? c.$extraHeaders.add($headers) : $headers;
				}
				// apply event handling to headers and/or additional headers (stickyheaders, scroller, etc)
				$headers
					// http://stackoverflow.com/questions/5312849/jquery-find-self;
						.find(c.selectorSort).add( $headers.filter(c.selectorSort) )
						.unbind('mousedown mouseup sort keyup '.split(' ').join(c.namespace + ' '))
						.bind('mousedown mouseup sort keyup '.split(' ').join(c.namespace + ' '), function(e, external) {
							var cell, type = e.type;
							// only recognize left clicks or enter
							if ( ((e.which || e.button) !== 1 && !/sort|keyup/.test(type)) || (type === 'keyup' && e.which !== 13) ) {
								return;
							}
							// ignore long clicks (prevents resizable widget from initializing a sort)
							if (type === 'mouseup' && external !== true && (new Date().getTime() - downTime > 250)) { return; }
							// set timer on mousedown
							if (type === 'mousedown') {
								downTime = new Date().getTime();
								return /(input|select|button|textarea)/i.test(e.target.tagName) ? '' : !c.cancelSelection;
							}
							if (c.delayInit && isEmptyObject(c.cache)) { buildCache(table); }
							// jQuery v1.2.6 doesn't have closest()
							cell = $.fn.closest ? $(this).closest('th, td')[0] : /TH|TD/.test(this.tagName) ? this : $(this).parents('th, td')[0];
							// reference original table headers and find the same cell
							cell = c.$headers[ $headers.index( cell ) ];
							if (!cell.sortDisabled) {
								initSort(table, cell, e);
							}
						});
				if (c.cancelSelection) {
					// cancel selection
					$headers
							.attr('unselectable', 'on')
							.bind('selectstart', false)
							.css({
								'user-select': 'none',
								'MozUserSelect': 'none' // not needed for jQuery 1.8+
							});
				}
			};

			// restore headers
			ts.restoreHeaders = function(table){
				var c = $(table)[0].config;
				// don't use c.$headers here in case header cells were swapped
				c.$table.find(c.selectorHeaders).each(function(i){
					// only restore header cells if it is wrapped
					// because this is also used by the updateAll method
					if ($(this).find('.' + ts.css.headerIn).length){
						$(this).html( c.headerContent[i] );
					}
				});
			};

			ts.destroy = function(table, removeClasses, callback){
				table = $(table)[0];
				if (!table.hasInitialized) { return; }
				// remove all widgets
				ts.refreshWidgets(table, true, true);
				var $t = $(table), c = table.config,
						$h = $t.find('thead:first'),
						$r = $h.find('tr.' + ts.css.headerRow).removeClass(ts.css.headerRow + ' ' + c.cssHeaderRow),
						$f = $t.find('tfoot:first > tr').children('th, td');
				if (removeClasses === false && $.inArray('uitheme', c.widgets) >= 0) {
					// reapply uitheme classes, in case we want to maintain appearance
					$t.trigger('applyWidgetId', ['uitheme']);
					$t.trigger('applyWidgetId', ['zebra']);
				}
				// remove widget added rows, just in case
				$h.find('tr').not($r).remove();
				// disable tablesorter
				$t
						.removeData('tablesorter')
						.unbind('sortReset update updateAll updateRows updateCell addRows updateComplete sorton appendCache updateCache applyWidgetId applyWidgets refreshWidgets destroy mouseup mouseleave keypress sortBegin sortEnd resetToLoadState '.split(' ').join(c.namespace + ' '));
				c.$headers.add($f)
						.removeClass( [ts.css.header, c.cssHeader, c.cssAsc, c.cssDesc, ts.css.sortAsc, ts.css.sortDesc, ts.css.sortNone].join(' ') )
						.removeAttr('data-column')
						.removeAttr('aria-label')
						.attr('aria-disabled', 'true');
				$r.find(c.selectorSort).unbind('mousedown mouseup keypress '.split(' ').join(c.namespace + ' '));
				ts.restoreHeaders(table);
				$t.toggleClass(ts.css.table + ' ' + c.tableClass + ' tablesorter-' + c.theme, removeClasses === false);
				// clear flag in case the plugin is initialized again
				table.hasInitialized = false;
				delete table.config.cache;
				if (typeof callback === 'function') {
					callback(table);
				}
			};

			// *** sort functions ***
			// regex used in natural sort
			ts.regex = {
				chunk : /(^([+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?)?$|^0x[0-9a-f]+$|\d+)/gi, // chunk/tokenize numbers & letters
				chunks: /(^\\0|\\0$)/, // replace chunks @ ends
				hex: /^0x[0-9a-f]+$/i // hex
			};

			// Natural sort - https://github.com/overset/javascript-natural-sort (date sorting removed)
			// this function will only accept strings, or you'll see "TypeError: undefined is not a function"
			// I could add a = a.toString(); b = b.toString(); but it'll slow down the sort overall
			ts.sortNatural = function(a, b) {
				if (a === b) { return 0; }
				var xN, xD, yN, yD, xF, yF, i, mx,
						r = ts.regex;
				// first try and sort Hex codes
				if (r.hex.test(b)) {
					xD = parseInt(a.match(r.hex), 16);
					yD = parseInt(b.match(r.hex), 16);
					if ( xD < yD ) { return -1; }
					if ( xD > yD ) { return 1; }
				}
				// chunk/tokenize
				xN = a.replace(r.chunk, '\\0$1\\0').replace(r.chunks, '').split('\\0');
				yN = b.replace(r.chunk, '\\0$1\\0').replace(r.chunks, '').split('\\0');
				mx = Math.max(xN.length, yN.length);
				// natural sorting through split numeric strings and default strings
				for (i = 0; i < mx; i++) {
					// find floats not starting with '0', string or 0 if not defined
					xF = isNaN(xN[i]) ? xN[i] || 0 : parseFloat(xN[i]) || 0;
					yF = isNaN(yN[i]) ? yN[i] || 0 : parseFloat(yN[i]) || 0;
					// handle numeric vs string comparison - number < string - (Kyle Adams)
					if (isNaN(xF) !== isNaN(yF)) { return (isNaN(xF)) ? 1 : -1; }
					// rely on string comparison if different types - i.e. '02' < 2 != '02' < '2'
					if (typeof xF !== typeof yF) {
						xF += '';
						yF += '';
					}
					if (xF < yF) { return -1; }
					if (xF > yF) { return 1; }
				}
				return 0;
			};

			ts.sortNaturalAsc = function(a, b, col, table, c) {
				if (a === b) { return 0; }
				var e = c.string[ (c.empties[col] || c.emptyTo ) ];
				if (a === '' && e !== 0) { return typeof e === 'boolean' ? (e ? -1 : 1) : -e || -1; }
				if (b === '' && e !== 0) { return typeof e === 'boolean' ? (e ? 1 : -1) : e || 1; }
				return ts.sortNatural(a, b);
			};

			ts.sortNaturalDesc = function(a, b, col, table, c) {
				if (a === b) { return 0; }
				var e = c.string[ (c.empties[col] || c.emptyTo ) ];
				if (a === '' && e !== 0) { return typeof e === 'boolean' ? (e ? -1 : 1) : e || 1; }
				if (b === '' && e !== 0) { return typeof e === 'boolean' ? (e ? 1 : -1) : -e || -1; }
				return ts.sortNatural(b, a);
			};

			// basic alphabetical sort
			ts.sortText = function(a, b) {
				return a > b ? 1 : (a < b ? -1 : 0);
			};

			// return text string value by adding up ascii value
			// so the text is somewhat sorted when using a digital sort
			// this is NOT an alphanumeric sort
			ts.getTextValue = function(a, num, mx) {
				if (mx) {
					// make sure the text value is greater than the max numerical value (mx)
					var i, l = a ? a.length : 0, n = mx + num;
					for (i = 0; i < l; i++) {
						n += a.charCodeAt(i);
					}
					return num * n;
				}
				return 0;
			};

			ts.sortNumericAsc = function(a, b, num, mx, col, table) {
				if (a === b) { return 0; }
				var c = table.config,
						e = c.string[ (c.empties[col] || c.emptyTo ) ];
				if (a === '' && e !== 0) { return typeof e === 'boolean' ? (e ? -1 : 1) : -e || -1; }
				if (b === '' && e !== 0) { return typeof e === 'boolean' ? (e ? 1 : -1) : e || 1; }
				if (isNaN(a)) { a = ts.getTextValue(a, num, mx); }
				if (isNaN(b)) { b = ts.getTextValue(b, num, mx); }
				return a - b;
			};

			ts.sortNumericDesc = function(a, b, num, mx, col, table) {
				if (a === b) { return 0; }
				var c = table.config,
						e = c.string[ (c.empties[col] || c.emptyTo ) ];
				if (a === '' && e !== 0) { return typeof e === 'boolean' ? (e ? -1 : 1) : e || 1; }
				if (b === '' && e !== 0) { return typeof e === 'boolean' ? (e ? 1 : -1) : -e || -1; }
				if (isNaN(a)) { a = ts.getTextValue(a, num, mx); }
				if (isNaN(b)) { b = ts.getTextValue(b, num, mx); }
				return b - a;
			};

			ts.sortNumeric = function(a, b) {
				return a - b;
			};

			// used when replacing accented characters during sorting
			ts.characterEquivalents = {
				"a" : "\u00e1\u00e0\u00e2\u00e3\u00e4\u0105\u00e5", // áàâãäąå
				"A" : "\u00c1\u00c0\u00c2\u00c3\u00c4\u0104\u00c5", // ÁÀÂÃÄĄÅ
				"c" : "\u00e7\u0107\u010d", // çćč
				"C" : "\u00c7\u0106\u010c", // ÇĆČ
				"e" : "\u00e9\u00e8\u00ea\u00eb\u011b\u0119", // éèêëěę
				"E" : "\u00c9\u00c8\u00ca\u00cb\u011a\u0118", // ÉÈÊËĚĘ
				"i" : "\u00ed\u00ec\u0130\u00ee\u00ef\u0131", // íìİîïı
				"I" : "\u00cd\u00cc\u0130\u00ce\u00cf", // ÍÌİÎÏ
				"o" : "\u00f3\u00f2\u00f4\u00f5\u00f6", // óòôõö
				"O" : "\u00d3\u00d2\u00d4\u00d5\u00d6", // ÓÒÔÕÖ
				"ss": "\u00df", // ß (s sharp)
				"SS": "\u1e9e", // ẞ (Capital sharp s)
				"u" : "\u00fa\u00f9\u00fb\u00fc\u016f", // úùûüů
				"U" : "\u00da\u00d9\u00db\u00dc\u016e" // ÚÙÛÜŮ
			};
			ts.replaceAccents = function(s) {
				var a, acc = '[', eq = ts.characterEquivalents;
				if (!ts.characterRegex) {
					ts.characterRegexArray = {};
					for (a in eq) {
						if (typeof a === 'string') {
							acc += eq[a];
							ts.characterRegexArray[a] = new RegExp('[' + eq[a] + ']', 'g');
						}
					}
					ts.characterRegex = new RegExp(acc + ']');
				}
				if (ts.characterRegex.test(s)) {
					for (a in eq) {
						if (typeof a === 'string') {
							s = s.replace( ts.characterRegexArray[a], a );
						}
					}
				}
				return s;
			};

			// *** utilities ***
			ts.isValueInArray = function(column, arry) {
				var indx, len = arry.length;
				for (indx = 0; indx < len; indx++) {
					if (arry[indx][0] === column) {
						return indx;
					}
				}
				return -1;
			};

			ts.addParser = function(parser) {
				var i, l = ts.parsers.length, a = true;
				for (i = 0; i < l; i++) {
					if (ts.parsers[i].id.toLowerCase() === parser.id.toLowerCase()) {
						a = false;
					}
				}
				if (a) {
					ts.parsers.push(parser);
				}
			};

			ts.getParserById = function(name) {
				/*jshint eqeqeq:false */
				if (name == 'false') { return false; }
				var i, l = ts.parsers.length;
				for (i = 0; i < l; i++) {
					if (ts.parsers[i].id.toLowerCase() === (name.toString()).toLowerCase()) {
						return ts.parsers[i];
					}
				}
				return false;
			};

			ts.addWidget = function(widget) {
				ts.widgets.push(widget);
			};

			ts.hasWidget = function(table, name){
				table = $(table);
				return table.length && table[0].config && table[0].config.widgetInit[name] || false;
			};

			ts.getWidgetById = function(name) {
				var i, w, l = ts.widgets.length;
				for (i = 0; i < l; i++) {
					w = ts.widgets[i];
					if (w && w.hasOwnProperty('id') && w.id.toLowerCase() === name.toLowerCase()) {
						return w;
					}
				}
			};

			ts.applyWidget = function(table, init) {
				table = $(table)[0]; // in case this is called externally
				var c = table.config,
						wo = c.widgetOptions,
						widgets = [],
						time, w, wd;
				// prevent numerous consecutive widget applications
				if (init !== false && table.hasInitialized && (table.isApplyingWidgets || table.isUpdating)) { return; }
				if (c.debug) { time = new Date(); }
				if (c.widgets.length) {
					table.isApplyingWidgets = true;
					// ensure unique widget ids
					c.widgets = $.grep(c.widgets, function(v, k){
						return $.inArray(v, c.widgets) === k;
					});
					// build widget array & add priority as needed
					$.each(c.widgets || [], function(i,n){
						wd = ts.getWidgetById(n);
						if (wd && wd.id) {
							// set priority to 10 if not defined
							if (!wd.priority) { wd.priority = 10; }
							widgets[i] = wd;
						}
					});
					// sort widgets by priority
					widgets.sort(function(a, b){
						return a.priority < b.priority ? -1 : a.priority === b.priority ? 0 : 1;
					});
					// add/update selected widgets
					$.each(widgets, function(i,w){
						if (w) {
							if (init || !(c.widgetInit[w.id])) {
								// set init flag first to prevent calling init more than once (e.g. pager)
								c.widgetInit[w.id] = true;
								if (w.hasOwnProperty('options')) {
									wo = table.config.widgetOptions = $.extend( true, {}, w.options, wo );
								}
								if (w.hasOwnProperty('init')) {
									w.init(table, w, c, wo);
								}
							}
							if (!init && w.hasOwnProperty('format')) {
								w.format(table, c, wo, false);
							}
						}
					});
				}
				setTimeout(function(){
					table.isApplyingWidgets = false;
				}, 0);
				if (c.debug) {
					w = c.widgets.length;
					benchmark("Completed " + (init === true ? "initializing " : "applying ") + w + " widget" + (w !== 1 ? "s" : ""), time);
				}
			};

			ts.refreshWidgets = function(table, doAll, dontapply) {
				table = $(table)[0]; // see issue #243
				var i, c = table.config,
						cw = c.widgets,
						w = ts.widgets, l = w.length;
				// remove previous widgets
				for (i = 0; i < l; i++){
					if ( w[i] && w[i].id && (doAll || $.inArray( w[i].id, cw ) < 0) ) {
						if (c.debug) { log( 'Refeshing widgets: Removing "' + w[i].id + '"' ); }
						// only remove widgets that have been initialized - fixes #442
						if (w[i].hasOwnProperty('remove') && c.widgetInit[w[i].id]) {
							w[i].remove(table, c, c.widgetOptions);
							c.widgetInit[w[i].id] = false;
						}
					}
				}
				if (dontapply !== true) {
					ts.applyWidget(table, doAll);
				}
			};

			// get sorter, string, empty, etc options for each column from
			// jQuery data, metadata, header option or header class name ("sorter-false")
			// priority = jQuery data > meta > headers option > header class name
			ts.getData = function(h, ch, key) {
				var val = '', $h = $(h), m, cl;
				if (!$h.length) { return ''; }
				m = $.metadata ? $h.metadata() : false;
				cl = ' ' + ($h.attr('class') || '');
				if (typeof $h.data(key) !== 'undefined' || typeof $h.data(key.toLowerCase()) !== 'undefined'){
					// "data-lockedOrder" is assigned to "lockedorder"; but "data-locked-order" is assigned to "lockedOrder"
					// "data-sort-initial-order" is assigned to "sortInitialOrder"
					val += $h.data(key) || $h.data(key.toLowerCase());
				} else if (m && typeof m[key] !== 'undefined') {
					val += m[key];
				} else if (ch && typeof ch[key] !== 'undefined') {
					val += ch[key];
				} else if (cl !== ' ' && cl.match(' ' + key + '-')) {
					// include sorter class name "sorter-text", etc; now works with "sorter-my-custom-parser"
					val = cl.match( new RegExp('\\s' + key + '-([\\w-]+)') )[1] || '';
				}
				return $.trim(val);
			};

			ts.formatFloat = function(s, table) {
				if (typeof s !== 'string' || s === '') { return s; }
				// allow using formatFloat without a table; defaults to US number format
				var i,
						t = table && table.config ? table.config.usNumberFormat !== false :
								typeof table !== "undefined" ? table : true;
				if (t) {
					// US Format - 1,234,567.89 -> 1234567.89
					s = s.replace(/,/g,'');
				} else {
					// German Format = 1.234.567,89 -> 1234567.89
					// French Format = 1 234 567,89 -> 1234567.89
					s = s.replace(/[\s|\.]/g,'').replace(/,/g,'.');
				}
				if(/^\s*\([.\d]+\)/.test(s)) {
					// make (#) into a negative number -> (10) = -10
					s = s.replace(/^\s*\(([.\d]+)\)/, '-$1');
				}
				i = parseFloat(s);
				// return the text instead of zero
				return isNaN(i) ? $.trim(s) : i;
			};

			ts.isDigit = function(s) {
				// replace all unwanted chars and match
				return isNaN(s) ? (/^[\-+(]?\d+[)]?$/).test(s.toString().replace(/[,.'"\s]/g, '')) : true;
			};

		}()
	});

	// make shortcut
	var ts = $.tablesorter;

	// extend plugin scope
	$.fn.extend({
		tablesorter: ts.construct
	});

	// add default parsers
	ts.addParser({
		id: 'no-parser',
		is: function() {
			return false;
		},
		format: function() {
			return '';
		},
		type: 'text'
	});

	ts.addParser({
		id: "text",
		is: function() {
			return true;
		},
		format: function(s, table) {
			var c = table.config;
			if (s) {
				s = $.trim( c.ignoreCase ? s.toLocaleLowerCase() : s );
				s = c.sortLocaleCompare ? ts.replaceAccents(s) : s;
			}
			return s;
		},
		type: "text"
	});

	ts.addParser({
		id: "digit",
		is: function(s) {
			return ts.isDigit(s);
		},
		format: function(s, table) {
			var n = ts.formatFloat((s || '').replace(/[^\w,. \-()]/g, ""), table);
			return s && typeof n === 'number' ? n : s ? $.trim( s && table.config.ignoreCase ? s.toLocaleLowerCase() : s ) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "currency",
		is: function(s) {
			return (/^\(?\d+[\u00a3$\u20ac\u00a4\u00a5\u00a2?.]|[\u00a3$\u20ac\u00a4\u00a5\u00a2?.]\d+\)?$/).test((s || '').replace(/[+\-,. ]/g,'')); // £$€¤¥¢
		},
		format: function(s, table) {
			var n = ts.formatFloat((s || '').replace(/[^\w,. \-()]/g, ""), table);
			return s && typeof n === 'number' ? n : s ? $.trim( s && table.config.ignoreCase ? s.toLocaleLowerCase() : s ) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "ipAddress",
		is: function(s) {
			return (/^\d{1,3}[\.]\d{1,3}[\.]\d{1,3}[\.]\d{1,3}$/).test(s);
		},
		format: function(s, table) {
			var i, a = s ? s.split(".") : '',
					r = "",
					l = a.length;
			for (i = 0; i < l; i++) {
				r += ("00" + a[i]).slice(-3);
			}
			return s ? ts.formatFloat(r, table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "url",
		is: function(s) {
			return (/^(https?|ftp|file):\/\//).test(s);
		},
		format: function(s) {
			return s ? $.trim(s.replace(/(https?|ftp|file):\/\//, '')) : s;
		},
		type: "text"
	});

	ts.addParser({
		id: "isoDate",
		is: function(s) {
			return (/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}/).test(s);
		},
		format: function(s, table) {
			return s ? ts.formatFloat((s !== "") ? (new Date(s.replace(/-/g, "/")).getTime() || s) : "", table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "percent",
		is: function(s) {
			return (/(\d\s*?%|%\s*?\d)/).test(s) && s.length < 15;
		},
		format: function(s, table) {
			return s ? ts.formatFloat(s.replace(/%/g, ""), table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "usLongDate",
		is: function(s) {
			// two digit years are not allowed cross-browser
			// Jan 01, 2013 12:34:56 PM or 01 Jan 2013
			return (/^[A-Z]{3,10}\.?\s+\d{1,2},?\s+(\d{4})(\s+\d{1,2}:\d{2}(:\d{2})?(\s+[AP]M)?)?$/i).test(s) || (/^\d{1,2}\s+[A-Z]{3,10}\s+\d{4}/i).test(s);
		},
		format: function(s, table) {
			return s ? ts.formatFloat( (new Date(s.replace(/(\S)([AP]M)$/i, "$1 $2")).getTime() || s), table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "shortDate", // "mmddyyyy", "ddmmyyyy" or "yyyymmdd"
		is: function(s) {
			// testing for ##-##-#### or ####-##-##, so it's not perfect; time can be included
			return (/(^\d{1,2}[\/\s]\d{1,2}[\/\s]\d{4})|(^\d{4}[\/\s]\d{1,2}[\/\s]\d{1,2})/).test((s || '').replace(/\s+/g," ").replace(/[\-.,]/g, "/"));
		},
		format: function(s, table, cell, cellIndex) {
			if (s) {
				var c = table.config,
						ci = c.$headers.filter('[data-column=' + cellIndex + ']:last'),
						format = ci.length && ci[0].dateFormat || ts.getData( ci, ts.getColumnData( table, c.headers, cellIndex ), 'dateFormat') || c.dateFormat;
				s = s.replace(/\s+/g," ").replace(/[\-.,]/g, "/"); // escaped - because JSHint in Firefox was showing it as an error
				if (format === "mmddyyyy") {
					s = s.replace(/(\d{1,2})[\/\s](\d{1,2})[\/\s](\d{4})/, "$3/$1/$2");
				} else if (format === "ddmmyyyy") {
					s = s.replace(/(\d{1,2})[\/\s](\d{1,2})[\/\s](\d{4})/, "$3/$2/$1");
				} else if (format === "yyyymmdd") {
					s = s.replace(/(\d{4})[\/\s](\d{1,2})[\/\s](\d{1,2})/, "$1/$2/$3");
				}
			}
			return s ? ts.formatFloat( (new Date(s).getTime() || s), table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "time",
		is: function(s) {
			return (/^(([0-2]?\d:[0-5]\d)|([0-1]?\d:[0-5]\d\s?([AP]M)))$/i).test(s);
		},
		format: function(s, table) {
			return s ? ts.formatFloat( (new Date("2000/01/01 " + s.replace(/(\S)([AP]M)$/i, "$1 $2")).getTime() || s), table) : s;
		},
		type: "numeric"
	});

	ts.addParser({
		id: "metadata",
		is: function() {
			return false;
		},
		format: function(s, table, cell) {
			var c = table.config,
					p = (!c.parserMetadataName) ? 'sortValue' : c.parserMetadataName;
			return $(cell).metadata()[p];
		},
		type: "numeric"
	});

	// add default widgets
	ts.addWidget({
		id: "zebra",
		priority: 90,
		format: function(table, c, wo) {
			var $tb, $tv, $tr, row, even, time, k, l,
					child = new RegExp(c.cssChildRow, 'i'),
					b = c.$tbodies;
			if (c.debug) {
				time = new Date();
			}
			for (k = 0; k < b.length; k++ ) {
				// loop through the visible rows
				$tb = b.eq(k);
				l = $tb.children('tr').length;
				if (l > 1) {
					row = 0;
					$tv = $tb.children('tr:visible').not(c.selectorRemove);
					// revered back to using jQuery each - strangely it's the fastest method
					/*jshint loopfunc:true */
					$tv.each(function(){
						$tr = $(this);
						// style children rows the same way the parent row was styled
						if (!child.test(this.className)) { row++; }
						even = (row % 2 === 0);
						$tr.removeClass(wo.zebra[even ? 1 : 0]).addClass(wo.zebra[even ? 0 : 1]);
					});
				}
			}
			if (c.debug) {
				ts.benchmark("Applying Zebra widget", time);
			}
		},
		remove: function(table, c, wo){
			var k, $tb,
					b = c.$tbodies,
					rmv = (wo.zebra || [ "even", "odd" ]).join(' ');
			for (k = 0; k < b.length; k++ ){
				$tb = $.tablesorter.processTbody(table, b.eq(k), true); // remove tbody
				$tb.children().removeClass(rmv);
				$.tablesorter.processTbody(table, $tb, false); // restore tbody
			}
		}
	});

})(jQuery);

(function($){$.extend({tablesorterPager:new function(){function updatePageDisplay(c){var s=$(c.cssPageDisplay,c.container).val(c.page+1+c.seperator+c.totalPages)}function setPageSize(table,size){var c=table.config;c.size=size;c.totalPages=Math.ceil(c.totalRows/c.size);c.pagerPositionSet=false;moveToPage(table);fixPosition(table)}function fixPosition(table){var c=table.config;if(!c.pagerPositionSet&&c.positionFixed){var c=table.config,o=$(table);if(o.offset){c.container.css({top:o.offset().top+o.height()+"px",position:"absolute"})}c.pagerPositionSet=true}}function moveToFirstPage(table){var c=table.config;c.page=0;moveToPage(table)}function moveToLastPage(table){var c=table.config;c.page=c.totalPages-1;moveToPage(table)}function moveToNextPage(table){var c=table.config;c.page++;if(c.page>=c.totalPages-1){c.page=c.totalPages-1}moveToPage(table)}function moveToPrevPage(table){var c=table.config;c.page--;if(c.page<=0){c.page=0}moveToPage(table)}function moveToPage(table){var c=table.config;if(c.page<0||c.page>c.totalPages-1){c.page=0}renderTable(table,c.rowsCopy)}function renderTable(table,rows){var c=table.config;var l=rows.length;var s=c.page*c.size;var e=s+c.size;if(e>rows.length){e=rows.length}var tableBody=$(table.tBodies[0]);$.tablesorter.clearTableBody(table);for(var i=s;i<e;i++){var o=rows[i];var l=o.length;for(var j=0;j<l;j++){tableBody[0].appendChild(o[j])}}fixPosition(table,tableBody);$(table).trigger("applyWidgets");if(c.page>=c.totalPages){moveToLastPage(table)}updatePageDisplay(c)}this.appender=function(table,rows){var c=table.config;c.rowsCopy=rows;c.totalRows=rows.length;c.totalPages=Math.ceil(c.totalRows/c.size);renderTable(table,rows)};this.defaults={size:10,offset:0,page:0,totalRows:0,totalPages:0,container:null,cssNext:".next",cssPrev:".prev",cssFirst:".first",cssLast:".last",cssPageDisplay:".pagedisplay",cssPageSize:".pageSize",seperator:"/",positionFixed:true,appender:this.appender};this.construct=function(settings){return this.each(function(){config=$.extend(this.config,$.tablesorterPager.defaults,settings);var table=this,pager=config.container;$(this).trigger("appendCache");config.size=parseInt($(".pageSize",pager).val());$(config.cssFirst,pager).click(function(){moveToFirstPage(table);return false});$(config.cssNext,pager).click(function(){moveToNextPage(table);return false});$(config.cssPrev,pager).click(function(){moveToPrevPage(table);return false});$(config.cssLast,pager).click(function(){moveToLastPage(table);return false});$(config.cssPageSize,pager).change(function(){setPageSize(table,parseInt($(this).val()));return false})})}}});$.fn.extend({tablesorterPager:$.tablesorterPager.construct})})(jQuery);
/*! tableSorter 2.16+ widgets - updated 8/9/2014 (v2.17.7)
 *
 * Column Styles
 * Column Filters
 * Column Resizing
 * Sticky Header
 * UI Theme (generalized)
 * Save Sort
 * [ "columns", "filter", "resizable", "stickyHeaders", "uitheme", "saveSort" ]
 */
/*jshint browser:true, jquery:true, unused:false, loopfunc:true */
/*global jQuery: false, localStorage: false, navigator: false */
;(function($) {
	"use strict";
	var ts = $.tablesorter = $.tablesorter || {};

	ts.themes = {
		"bootstrap" : {
			table      : 'table table-bordered table-striped',
			caption    : 'caption',
			header     : 'bootstrap-header', // give the header a gradient background
			footerRow  : '',
			footerCells: '',
			icons      : '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
			sortNone   : 'bootstrap-icon-unsorted',
			sortAsc    : 'icon-chevron-up glyphicon glyphicon-chevron-up',
			sortDesc   : 'icon-chevron-down glyphicon glyphicon-chevron-down',
			active     : '', // applied when column is sorted
			hover      : '', // use custom css here - bootstrap class may not override it
			filterRow  : '', // filter row class
			even       : '', // even row zebra striping
			odd        : ''  // odd row zebra striping
		},
		"jui" : {
			table      : 'ui-widget ui-widget-content ui-corner-all', // table classes
			caption    : 'ui-widget-content ui-corner-all',
			header     : 'ui-widget-header ui-corner-all ui-state-default', // header classes
			footerRow  : '',
			footerCells: '',
			icons      : 'ui-icon', // icon class added to the <i> in the header
			sortNone   : 'ui-icon-carat-2-n-s',
			sortAsc    : 'ui-icon-carat-1-n',
			sortDesc   : 'ui-icon-carat-1-s',
			active     : 'ui-state-active', // applied when column is sorted
			hover      : 'ui-state-hover',  // hover class
			filterRow  : '',
			even       : 'ui-widget-content', // even row zebra striping
			odd        : 'ui-state-default'   // odd row zebra striping
		}
	};

	$.extend(ts.css, {
		filterRow : 'tablesorter-filter-row',   // filter
		filter    : 'tablesorter-filter',
		wrapper   : 'tablesorter-wrapper',      // ui theme & resizable
		resizer   : 'tablesorter-resizer',      // resizable
		sticky    : 'tablesorter-stickyHeader', // stickyHeader
		stickyVis : 'tablesorter-sticky-visible'
	});

// *** Store data in local storage, with a cookie fallback ***
	/* IE7 needs JSON library for JSON.stringify - (http://caniuse.com/#search=json)
	 if you need it, then include https://github.com/douglascrockford/JSON-js

	 $.parseJSON is not available is jQuery versions older than 1.4.1, using older
	 versions will only allow storing information for one page at a time

	 // *** Save data (JSON format only) ***
	 // val must be valid JSON... use http://jsonlint.com/ to ensure it is valid
	 var val = { "mywidget" : "data1" }; // valid JSON uses double quotes
	 // $.tablesorter.storage(table, key, val);
	 $.tablesorter.storage(table, 'tablesorter-mywidget', val);

	 // *** Get data: $.tablesorter.storage(table, key); ***
	 v = $.tablesorter.storage(table, 'tablesorter-mywidget');
	 // val may be empty, so also check for your data
	 val = (v && v.hasOwnProperty('mywidget')) ? v.mywidget : '';
	 alert(val); // "data1" if saved, or "" if not
	 */
	ts.storage = function(table, key, value, options) {
		table = $(table)[0];
		var cookieIndex, cookies, date,
				hasLocalStorage = false,
				values = {},
				c = table.config,
				$table = $(table),
				id = options && options.id || $table.attr(options && options.group ||
						'data-table-group') || table.id || $('.tablesorter').index( $table ),
				url = options && options.url || $table.attr(options && options.page ||
						'data-table-page') || c && c.fixedUrl || window.location.pathname;
		// https://gist.github.com/paulirish/5558557
		if ("localStorage" in window) {
			try {
				window.localStorage.setItem('_tmptest', 'temp');
				hasLocalStorage = true;
				window.localStorage.removeItem('_tmptest');
			} catch(error) {}
		}
		// *** get value ***
		if ($.parseJSON) {
			if (hasLocalStorage) {
				values = $.parseJSON(localStorage[key] || '{}');
			} else {
				// old browser, using cookies
				cookies = document.cookie.split(/[;\s|=]/);
				// add one to get from the key to the value
				cookieIndex = $.inArray(key, cookies) + 1;
				values = (cookieIndex !== 0) ? $.parseJSON(cookies[cookieIndex] || '{}') : {};
			}
		}
		// allow value to be an empty string too
		if ((value || value === '') && window.JSON && JSON.hasOwnProperty('stringify')) {
			// add unique identifiers = url pathname > table ID/index on page > data
			if (!values[url]) {
				values[url] = {};
			}
			values[url][id] = value;
			// *** set value ***
			if (hasLocalStorage) {
				localStorage[key] = JSON.stringify(values);
			} else {
				date = new Date();
				date.setTime(date.getTime() + (31536e+6)); // 365 days
				document.cookie = key + '=' + (JSON.stringify(values)).replace(/\"/g,'\"') + '; expires=' + date.toGMTString() + '; path=/';
			}
		} else {
			return values && values[url] ? values[url][id] : '';
		}
	};

// Add a resize event to table headers
// **************************
	ts.addHeaderResizeEvent = function(table, disable, settings) {
		table = $(table)[0]; // make sure we're usig a dom element
		var headers,
				defaults = {
					timer : 250
				},
				options = $.extend({}, defaults, settings),
				c = table.config,
				wo = c.widgetOptions,
				checkSizes = function(triggerEvent) {
					wo.resize_flag = true;
					headers = [];
					c.$headers.each(function() {
						var $header = $(this),
								sizes = $header.data('savedSizes') || [0,0], // fixes #394
								width = this.offsetWidth,
								height = this.offsetHeight;
						if (width !== sizes[0] || height !== sizes[1]) {
							$header.data('savedSizes', [ width, height ]);
							headers.push(this);
						}
					});
					if (headers.length && triggerEvent !== false) {
						c.$table.trigger('resize', [ headers ]);
					}
					wo.resize_flag = false;
				};
		checkSizes(false);
		clearInterval(wo.resize_timer);
		if (disable) {
			wo.resize_flag = false;
			return false;
		}
		wo.resize_timer = setInterval(function() {
			if (wo.resize_flag) { return; }
			checkSizes();
		}, options.timer);
	};

// Widget: General UI theme
// "uitheme" option in "widgetOptions"
// **************************
	ts.addWidget({
		id: "uitheme",
		priority: 10,
		format: function(table, c, wo) {
			var i, time, classes, $header, $icon, $tfoot, $h,
					themesAll = ts.themes,
					$table = c.$table,
					$headers = c.$headers,
					theme = c.theme || 'jui',
					themes = themesAll[theme] || themesAll.jui,
					remove = themes.sortNone + ' ' + themes.sortDesc + ' ' + themes.sortAsc;
			if (c.debug) { time = new Date(); }
			// initialization code - run once
			if (!$table.hasClass('tablesorter-' + theme) || c.theme === theme || !table.hasInitialized) {
				// update zebra stripes
				if (themes.even !== '') { wo.zebra[0] += ' ' + themes.even; }
				if (themes.odd !== '') { wo.zebra[1] += ' ' + themes.odd; }
				// add caption style
				$table.find('caption').addClass(themes.caption);
				// add table/footer class names
				$tfoot = $table
					// remove other selected themes
						.removeClass( c.theme === '' ? '' : 'tablesorter-' + c.theme )
						.addClass('tablesorter-' + theme + ' ' + themes.table) // add theme widget class name
						.find('tfoot');
				if ($tfoot.length) {
					$tfoot
							.find('tr').addClass(themes.footerRow)
							.children('th, td').addClass(themes.footerCells);
				}
				// update header classes
				$headers
						.addClass(themes.header)
						.not('.sorter-false')
						.bind('mouseenter.tsuitheme mouseleave.tsuitheme', function(event) {
							// toggleClass with switch added in jQuery 1.3
							$(this)[ event.type === 'mouseenter' ? 'addClass' : 'removeClass' ](themes.hover);
						});
				if (!$headers.find('.' + ts.css.wrapper).length) {
					// Firefox needs this inner div to position the resizer correctly
					$headers.wrapInner('<div class="' + ts.css.wrapper + '" style="position:relative;height:100%;width:100%"></div>');
				}
				if (c.cssIcon) {
					// if c.cssIcon is '', then no <i> is added to the header
					$headers.find('.' + ts.css.icon).addClass(themes.icons);
				}
				if ($table.hasClass('hasFilters')) {
					$headers.find('.' + ts.css.filterRow).addClass(themes.filterRow);
				}
			}
			for (i = 0; i < c.columns; i++) {
				$header = c.$headers.add(c.$extraHeaders).filter('[data-column="' + i + '"]');
				$icon = (ts.css.icon) ? $header.find('.' + ts.css.icon) : $header;
				$h = c.$headers.filter('[data-column="' + i + '"]:last');
				if ($h.length) {
					if ($h[0].sortDisabled) {
						// no sort arrows for disabled columns!
						$header.removeClass(remove);
						$icon.removeClass(remove + ' ' + themes.icons);
					} else {
						classes = ($header.hasClass(ts.css.sortAsc)) ?
								themes.sortAsc :
								($header.hasClass(ts.css.sortDesc)) ? themes.sortDesc :
										$header.hasClass(ts.css.header) ? themes.sortNone : '';
						$header[classes === themes.sortNone ? 'removeClass' : 'addClass'](themes.active);
						$icon.removeClass(remove).addClass(classes);
					}
				}
			}
			if (c.debug) {
				ts.benchmark("Applying " + theme + " theme", time);
			}
		},
		remove: function(table, c, wo) {
			var $table = c.$table,
					theme = c.theme || 'jui',
					themes = ts.themes[ theme ] || ts.themes.jui,
					$headers = $table.children('thead').children(),
					remove = themes.sortNone + ' ' + themes.sortDesc + ' ' + themes.sortAsc;
			$table
					.removeClass('tablesorter-' + theme + ' ' + themes.table)
					.find(ts.css.header).removeClass(themes.header);
			$headers
					.unbind('mouseenter.tsuitheme mouseleave.tsuitheme') // remove hover
					.removeClass(themes.hover + ' ' + remove + ' ' + themes.active)
					.find('.' + ts.css.filterRow)
					.removeClass(themes.filterRow);
			$headers.find('.' + ts.css.icon).removeClass(themes.icons);
		}
	});

// Widget: Column styles
// "columns", "columns_thead" (true) and
// "columns_tfoot" (true) options in "widgetOptions"
// **************************
	ts.addWidget({
		id: "columns",
		priority: 30,
		options : {
			columns : [ "primary", "secondary", "tertiary" ]
		},
		format: function(table, c, wo) {
			var time, $tbody, tbodyIndex, $rows, rows, $row, $cells, remove, indx,
					$table = c.$table,
					$tbodies = c.$tbodies,
					sortList = c.sortList,
					len = sortList.length,
			// removed c.widgetColumns support
					css = wo && wo.columns || [ "primary", "secondary", "tertiary" ],
					last = css.length - 1;
			remove = css.join(' ');
			if (c.debug) {
				time = new Date();
			}
			// check if there is a sort (on initialization there may not be one)
			for (tbodyIndex = 0; tbodyIndex < $tbodies.length; tbodyIndex++ ) {
				$tbody = ts.processTbody(table, $tbodies.eq(tbodyIndex), true); // detach tbody
				$rows = $tbody.children('tr');
				// loop through the visible rows
				$rows.each(function() {
					$row = $(this);
					if (this.style.display !== 'none') {
						// remove all columns class names
						$cells = $row.children().removeClass(remove);
						// add appropriate column class names
						if (sortList && sortList[0]) {
							// primary sort column class
							$cells.eq(sortList[0][0]).addClass(css[0]);
							if (len > 1) {
								for (indx = 1; indx < len; indx++) {
									// secondary, tertiary, etc sort column classes
									$cells.eq(sortList[indx][0]).addClass( css[indx] || css[last] );
								}
							}
						}
					}
				});
				ts.processTbody(table, $tbody, false);
			}
			// add classes to thead and tfoot
			rows = wo.columns_thead !== false ? ['thead tr'] : [];
			if (wo.columns_tfoot !== false) {
				rows.push('tfoot tr');
			}
			if (rows.length) {
				$rows = $table.find( rows.join(',') ).children().removeClass(remove);
				if (len) {
					for (indx = 0; indx < len; indx++) {
						// add primary. secondary, tertiary, etc sort column classes
						$rows.filter('[data-column="' + sortList[indx][0] + '"]').addClass(css[indx] || css[last]);
					}
				}
			}
			if (c.debug) {
				ts.benchmark("Applying Columns widget", time);
			}
		},
		remove: function(table, c, wo) {
			var tbodyIndex, $tbody,
					$tbodies = c.$tbodies,
					remove = (wo.columns || [ "primary", "secondary", "tertiary" ]).join(' ');
			c.$headers.removeClass(remove);
			c.$table.children('tfoot').children('tr').children('th, td').removeClass(remove);
			for (tbodyIndex = 0; tbodyIndex < $tbodies.length; tbodyIndex++ ) {
				$tbody = ts.processTbody(table, $tbodies.eq(tbodyIndex), true); // remove tbody
				$tbody.children('tr').each(function() {
					$(this).children().removeClass(remove);
				});
				ts.processTbody(table, $tbody, false); // restore tbody
			}
		}
	});

// Widget: filter
// **************************
	ts.addWidget({
		id: "filter",
		priority: 50,
		options : {
			filter_childRows     : false, // if true, filter includes child row content in the search
			filter_columnFilters : true,  // if true, a filter will be added to the top of each table column
			filter_cssFilter     : '',    // css class name added to the filter row & each input in the row (tablesorter-filter is ALWAYS added)
			filter_external      : '',    // jQuery selector string (or jQuery object) of external filters
			filter_filteredRow   : 'filtered', // class added to filtered rows; needed by pager plugin
			filter_formatter     : null,  // add custom filter elements to the filter row
			filter_functions     : null,  // add custom filter functions using this option
			filter_hideEmpty     : true,  // hide filter row when table is empty
			filter_hideFilters   : false, // collapse filter row when mouse leaves the area
			filter_ignoreCase    : true,  // if true, make all searches case-insensitive
			filter_liveSearch    : true,  // if true, search column content while the user types (with a delay)
			filter_onlyAvail     : 'filter-onlyAvail', // a header with a select dropdown & this class name will only show available (visible) options within the drop down
			filter_placeholder   : { search : '', select : '' }, // default placeholder text (overridden by any header "data-placeholder" setting)
			filter_reset         : null,  // jQuery selector string of an element used to reset the filters
			filter_saveFilters   : false, // Use the $.tablesorter.storage utility to save the most recent filters
			filter_searchDelay   : 300,   // typing delay in milliseconds before starting a search
			filter_searchFiltered: true,  // allow searching through already filtered rows in special circumstances; will speed up searching in large tables if true
			filter_selectSource  : null,  // include a function to return an array of values to be added to the column filter select
			filter_startsWith    : false, // if true, filter start from the beginning of the cell contents
			filter_useParsedData : false, // filter all data using parsed content
			filter_serversideFiltering : false, // if true, server-side filtering should be performed because client-side filtering will be disabled, but the ui and events will still be used.
			filter_defaultAttrib : 'data-value', // data attribute in the header cell that contains the default filter value
			filter_selectSourceSeparator : '|' // filter_selectSource array text left of the separator is added to the option value, right into the option text
		},
		format: function(table, c, wo) {
			if (!c.$table.hasClass('hasFilters')) {
				ts.filter.init(table, c, wo);
			}
		},
		remove: function(table, c, wo) {
			var tbodyIndex, $tbody,
					$table = c.$table,
					$tbodies = c.$tbodies;
			$table
					.removeClass('hasFilters')
				// add .tsfilter namespace to all BUT search
					.unbind('addRows updateCell update updateRows updateComplete appendCache filterReset filterEnd search '.split(' ').join(c.namespace + 'filter '))
					.find('.' + ts.css.filterRow).remove();
			for (tbodyIndex = 0; tbodyIndex < $tbodies.length; tbodyIndex++ ) {
				$tbody = ts.processTbody(table, $tbodies.eq(tbodyIndex), true); // remove tbody
				$tbody.children().removeClass(wo.filter_filteredRow).show();
				ts.processTbody(table, $tbody, false); // restore tbody
			}
			if (wo.filter_reset) {
				$(document).undelegate(wo.filter_reset, 'click.tsfilter');
			}
		}
	});

	ts.filter = {

		// regex used in filter "check" functions - not for general use and not documented
		regex: {
			regex     : /^\/((?:\\\/|[^\/])+)\/([mig]{0,3})?$/, // regex to test for regex
			child     : /tablesorter-childRow/, // child row class name; this gets updated in the script
			filtered  : /filtered/, // filtered (hidden) row class name; updated in the script
			type      : /undefined|number/, // check type
			exact     : /(^[\"|\'|=]+)|([\"|\'|=]+$)/g, // exact match (allow '==')
			nondigit  : /[^\w,. \-()]/g, // replace non-digits (from digit & currency parser)
			operators : /[<>=]/g // replace operators
		},
		// function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed )
		// filter = array of filter input values; iFilter = same array, except lowercase
		// exact = table cell text (or parsed data if column parser enabled)
		// iExact = same as exact, except lowercase
		// cached = table cell text from cache, so it has been parsed
		// index = column index; table = table element (DOM)
		// wo = widget options (table.config.widgetOptions)
		// parsed = array (by column) of boolean values (from filter_useParsedData or "filter-parsed" class)
		types: {
			// Look for regex
			regex: function( filter, iFilter, exact, iExact ) {
				if ( ts.filter.regex.regex.test(iFilter) ) {
					var matches,
							regex = ts.filter.regex.regex.exec(iFilter);
					try {
						matches = new RegExp(regex[1], regex[2]).test( iExact );
					} catch (error) {
						matches = false;
					}
					return matches;
				}
				return null;
			},
			// Look for operators >, >=, < or <=
			operators: function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed ) {
				if ( /^[<>]=?/.test(iFilter) ) {
					var cachedValue, result,
							c = table.config,
							query = ts.formatFloat( iFilter.replace(ts.filter.regex.operators, ''), table ),
							parser = c.parsers[index],
							savedSearch = query;
					// parse filter value in case we're comparing numbers (dates)
					if (parsed[index] || parser.type === 'numeric') {
						result = ts.filter.parseFilter(table, $.trim('' + iFilter.replace(ts.filter.regex.operators, '')), index, parsed[index], true);
						query = ( typeof result === "number" && result !== '' && !isNaN(result) ) ? result : query;
					}

					// iExact may be numeric - see issue #149;
					// check if cached is defined, because sometimes j goes out of range? (numeric columns)
					cachedValue = ( parsed[index] || parser.type === 'numeric' ) && !isNaN(query) && typeof cached !== 'undefined' ? cached :
							isNaN(iExact) ? ts.formatFloat( iExact.replace(ts.filter.regex.nondigit, ''), table) :
									ts.formatFloat( iExact, table );

					if ( />/.test(iFilter) ) { result = />=/.test(iFilter) ? cachedValue >= query : cachedValue > query; }
					if ( /</.test(iFilter) ) { result = /<=/.test(iFilter) ? cachedValue <= query : cachedValue < query; }
					// keep showing all rows if nothing follows the operator
					if ( !result && savedSearch === '' ) { result = true; }
					return result;
				}
				return null;
			},
			// Look for a not match
			notMatch: function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed ) {
				if ( /^\!/.test(iFilter) ) {
					iFilter = ts.filter.parseFilter(table, iFilter.replace('!', ''), index, parsed[index]);
					if (ts.filter.regex.exact.test(iFilter)) {
						// look for exact not matches - see #628
						iFilter = iFilter.replace(ts.filter.regex.exact, '');
						return iFilter === '' ? true : $.trim(iFilter) !== iExact;
					} else {
						var indx = iExact.search( $.trim(iFilter) );
						return iFilter === '' ? true : !(wo.filter_startsWith ? indx === 0 : indx >= 0);
					}
				}
				return null;
			},
			// Look for quotes or equals to get an exact match; ignore type since iExact could be numeric
			exact: function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed, rowArray ) {
				/*jshint eqeqeq:false */
				if (ts.filter.regex.exact.test(iFilter)) {
					var fltr = ts.filter.parseFilter(table, iFilter.replace(ts.filter.regex.exact, ''), index, parsed[index]);
					return rowArray ? $.inArray(fltr, rowArray) >= 0 : fltr == iExact;
				}
				return null;
			},
			// Look for an AND or && operator (logical and)
			and : function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed ) {
				if ( ts.filter.regex.andTest.test(filter) ) {
					var query = iFilter.split( ts.filter.regex.andSplit ),
							result = iExact.search( $.trim(ts.filter.parseFilter(table, query[0], index, parsed[index])) ) >= 0,
							indx = query.length - 1;
					while (result && indx) {
						result = result && iExact.search( $.trim(ts.filter.parseFilter(table, query[indx], index, parsed[index])) ) >= 0;
						indx--;
					}
					return result;
				}
				return null;
			},
			// Look for a range (using " to " or " - ") - see issue #166; thanks matzhu!
			range : function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed ) {
				if ( ts.filter.regex.toTest.test(iFilter) ) {
					var result, tmp,
							c = table.config,
					// make sure the dash is for a range and not indicating a negative number
							query = iFilter.split( ts.filter.regex.toSplit ),
							range1 = ts.formatFloat( ts.filter.parseFilter(table, query[0].replace(ts.filter.regex.nondigit, ''), index, parsed[index]), table ),
							range2 = ts.formatFloat( ts.filter.parseFilter(table, query[1].replace(ts.filter.regex.nondigit, ''), index, parsed[index]), table );
					// parse filter value in case we're comparing numbers (dates)
					if (parsed[index] || c.parsers[index].type === 'numeric') {
						result = c.parsers[index].format('' + query[0], table, c.$headers.eq(index), index);
						range1 = (result !== '' && !isNaN(result)) ? result : range1;
						result = c.parsers[index].format('' + query[1], table, c.$headers.eq(index), index);
						range2 = (result !== '' && !isNaN(result)) ? result : range2;
					}
					result = ( parsed[index] || c.parsers[index].type === 'numeric' ) && !isNaN(range1) && !isNaN(range2) ? cached :
							isNaN(iExact) ? ts.formatFloat( iExact.replace(ts.filter.regex.nondigit, ''), table) :
									ts.formatFloat( iExact, table );
					if (range1 > range2) { tmp = range1; range1 = range2; range2 = tmp; } // swap
					return (result >= range1 && result <= range2) || (range1 === '' || range2 === '');
				}
				return null;
			},
			// Look for wild card: ? = single, * = multiple, or | = logical OR
			wild : function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed, rowArray ) {
				if ( /[\?|\*]/.test(iFilter) || ts.filter.regex.orReplace.test(filter) ) {
					var c = table.config,
							query = ts.filter.parseFilter(table, iFilter.replace(ts.filter.regex.orReplace, "|"), index, parsed[index]);
					// look for an exact match with the "or" unless the "filter-match" class is found
					if (!c.$headers.filter('[data-column="' + index + '"]:last').hasClass('filter-match') && /\|/.test(query)) {
						query = $.isArray(rowArray) ? '(' + query + ')' : '^(' + query + ')$';
					}
					// parsing the filter may not work properly when using wildcards =/
					return new RegExp( query.replace(/\?/g, '\\S{1}').replace(/\*/g, '\\S*') ).test(iExact);
				}
				return null;
			},
			// fuzzy text search; modified from https://github.com/mattyork/fuzzy (MIT license)
			fuzzy: function( filter, iFilter, exact, iExact, cached, index, table, wo, parsed ) {
				if ( /^~/.test(iFilter) ) {
					var indx,
							patternIndx = 0,
							len = iExact.length,
							pattern = ts.filter.parseFilter(table, iFilter.slice(1), index, parsed[index]);
					for (indx = 0; indx < len; indx++) {
						if (iExact[indx] === pattern[patternIndx]) {
							patternIndx += 1;
						}
					}
					if (patternIndx === pattern.length) {
						return true;
					}
					return false;
				}
				return null;
			}
		},
		init: function(table, c, wo) {
			// filter language options
			ts.language = $.extend(true, {}, {
				to  : 'to',
				or  : 'or',
				and : 'and'
			}, ts.language);

			var options, string, txt, $header, column, filters, val, time, fxn, noSelect,
					regex = ts.filter.regex;
			if (c.debug) {
				time = new Date();
			}
			c.$table.addClass('hasFilters');

			// define timers so using clearTimeout won't cause an undefined error
			wo.searchTimer = null;
			wo.filter_initTimer = null;
			wo.filter_formatterCount = 0;
			wo.filter_formatterInit = [];

			$.extend( regex, {
				child : new RegExp(c.cssChildRow),
				filtered : new RegExp(wo.filter_filteredRow),
				alreadyFiltered : new RegExp('(\\s+(' + ts.language.or + '|-|' + ts.language.to + ')\\s+)', 'i'),
				toTest : new RegExp('\\s+(-|' + ts.language.to + ')\\s+', 'i'),
				toSplit : new RegExp('(?:\\s+(?:-|' + ts.language.to + ')\\s+)' ,'gi'),
				andTest : new RegExp('\\s+(' + ts.language.and + '|&&)\\s+', 'i'),
				andSplit : new RegExp('(?:\\s+(?:' + ts.language.and + '|&&)\\s+)', 'gi'),
				orReplace : new RegExp('\\s+(' + ts.language.or + ')\\s+', 'gi')
			});

			// don't build filter row if columnFilters is false or all columns are set to "filter-false" - issue #156
			if (wo.filter_columnFilters !== false && c.$headers.filter('.filter-false, .parser-false').length !== c.$headers.length) {
				// build filter row
				ts.filter.buildRow(table, c, wo);
			}

			c.$table.bind('addRows updateCell update updateRows updateComplete appendCache filterReset filterEnd search '.split(' ').join(c.namespace + 'filter '), function(event, filter) {
				c.$table.find('.' + ts.css.filterRow).toggle( !(wo.filter_hideEmpty && $.isEmptyObject(c.cache) && !(c.delayInit && event.type === 'appendCache')) ); // fixes #450
				if ( !/(search|filter)/.test(event.type) ) {
					event.stopPropagation();
					ts.filter.buildDefault(table, true);
				}
				if (event.type === 'filterReset') {
					c.$table.find('.' + ts.css.filter).add(wo.filter_$externalFilters).val('');
					ts.filter.searching(table, []);
				} else if (event.type === 'filterEnd') {
					ts.filter.buildDefault(table, true);
				} else {
					// send false argument to force a new search; otherwise if the filter hasn't changed, it will return
					filter = event.type === 'search' ? filter : event.type === 'updateComplete' ? c.$table.data('lastSearch') : '';
					if (/(update|add)/.test(event.type) && event.type !== "updateComplete") {
						// force a new search since content has changed
						c.lastCombinedFilter = null;
						c.lastSearch = [];
					}
					// pass true (skipFirst) to prevent the tablesorter.setFilters function from skipping the first input
					// ensures all inputs are updated when a search is triggered on the table $('table').trigger('search', [...]);
					ts.filter.searching(table, filter, true);
				}
				return false;
			});

			// reset button/link
			if (wo.filter_reset) {
				if (wo.filter_reset instanceof $) {
					// reset contains a jQuery object, bind to it
					wo.filter_reset.click(function(){
						c.$table.trigger('filterReset');
					});
				} else if ($(wo.filter_reset).length) {
					// reset is a jQuery selector, use event delegation
					$(document)
							.undelegate(wo.filter_reset, 'click.tsfilter')
							.delegate(wo.filter_reset, 'click.tsfilter', function() {
								// trigger a reset event, so other functions (filterFormatter) know when to reset
								c.$table.trigger('filterReset');
							});
				}
			}
			if (wo.filter_functions) {
				for (column = 0; column < c.columns; column++) {
					fxn = ts.getColumnData( table, wo.filter_functions, column );
					if (fxn) {
						// remove "filter-select" from header otherwise the options added here are replaced with all options
						$header = c.$headers.filter('[data-column="' + column + '"]:last').removeClass('filter-select');
						// don't build select if "filter-false" or "parser-false" set
						noSelect = !($header.hasClass('filter-false') || $header.hasClass('parser-false'));
						options = '';
						if ( fxn === true && noSelect ) {
							ts.filter.buildSelect(table, column);
						} else if ( typeof fxn === 'object' && noSelect ) {
							// add custom drop down list
							for (string in fxn) {
								if (typeof string === 'string') {
									options += options === '' ?
											'<option value="">' + ($header.data('placeholder') || $header.attr('data-placeholder') || wo.filter_placeholder.select || '') + '</option>' : '';
									val = string;
									txt = string;
									if (string.indexOf(wo.filter_selectSourceSeparator) >= 0) {
										val = string.split(wo.filter_selectSourceSeparator);
										txt = val[1];
										val = val[0];
									}
									options += '<option ' + (txt === val ? '' : 'data-function-name="' + string + '" ') + 'value="' + val + '">' + txt + '</option>';
								}
							}
							c.$table.find('thead').find('select.' + ts.css.filter + '[data-column="' + column + '"]').append(options);
						}
					}
				}
			}
			// not really updating, but if the column has both the "filter-select" class & filter_functions set to true,
			// it would append the same options twice.
			ts.filter.buildDefault(table, true);

			ts.filter.bindSearch( table, c.$table.find('.' + ts.css.filter), true );
			if (wo.filter_external) {
				ts.filter.bindSearch( table, wo.filter_external );
			}

			if (wo.filter_hideFilters) {
				ts.filter.hideFilters(table, c);
			}

			// show processing icon
			if (c.showProcessing) {
				c.$table.bind('filterStart' + c.namespace + 'filter filterEnd' + c.namespace + 'filter', function(event, columns) {
					// only add processing to certain columns to all columns
					$header = (columns) ? c.$table.find('.' + ts.css.header).filter('[data-column]').filter(function() {
						return columns[$(this).data('column')] !== '';
					}) : '';
					ts.isProcessing(table, event.type === 'filterStart', columns ? $header : '');
				});
			}

			// set filtered rows count (intially unfiltered)
			c.filteredRows = c.totalRows;

			if (c.debug) {
				ts.benchmark("Applying Filter widget", time);
			}
			// add default values
			c.$table.bind('tablesorter-initialized pagerInitialized', function() {
				// redefine "wo" as it does not update properly inside this callback
				var wo = this.config.widgetOptions;
				filters = ts.filter.setDefaults(table, c, wo) || [];
				if (filters.length) {
					// prevent delayInit from triggering a cache build if filters are empty
					if ( !(c.delayInit && filters.join('') === '') ) {
						ts.setFilters(table, filters, true);
					}
				}
				c.$table.trigger('filterFomatterUpdate');
				// trigger init after setTimeout to prevent multiple filterStart/End/Init triggers
				setTimeout(function(){
					if (!wo.filter_initialized) {
						ts.filter.filterInitComplete(c);
					}
				}, 100);
			});
			// if filter widget is added after pager has initialized; then set filter init flag
			if (c.pager && c.pager.initialized && !wo.filter_initialized) {
				c.$table.trigger('filterFomatterUpdate');
				setTimeout(function(){
					ts.filter.filterInitComplete(c);
				}, 100);
			}
		},
		// $cell parameter, but not the config, is passed to the
		// filter_formatters, so we have to work with it instead
		formatterUpdated: function($cell, column) {
			var wo = $cell.closest('table')[0].config.widgetOptions;
			if (!wo.filter_initialized) {
				// add updates by column since this function
				// may be called numerous times before initialization
				wo.filter_formatterInit[column] = 1;
			}
		},
		filterInitComplete: function(c){
			var wo = c.widgetOptions,
					count = 0;
			$.each( wo.filter_formatterInit, function(i, val) {
				if (val === 1) {
					count++;
				}
			});
			clearTimeout(wo.filter_initTimer);
			if (!wo.filter_initialized && count === wo.filter_formatterCount) {
				// filter widget initialized
				wo.filter_initialized = true;
				c.$table.trigger('filterInit', c);
			} else if (!wo.filter_initialized) {
				// fall back in case a filter_formatter doesn't call
				// $.tablesorter.filter.formatterUpdated($cell, column), and the count is off
				wo.filter_initTimer = setTimeout(function(){
					wo.filter_initialized = true;
					c.$table.trigger('filterInit', c);
				}, 500);
			}
		},
		setDefaults: function(table, c, wo) {
			var isArray, saved, indx,
			// get current (default) filters
					filters = ts.getFilters(table) || [];
			if (wo.filter_saveFilters && ts.storage) {
				saved = ts.storage( table, 'tablesorter-filters' ) || [];
				isArray = $.isArray(saved);
				// make sure we're not just getting an empty array
				if ( !(isArray && saved.join('') === '' || !isArray) ) { filters = saved; }
			}
			// if no filters saved, then check default settings
			if (filters.join('') === '') {
				for (indx = 0; indx < c.columns; indx++) {
					filters[indx] = c.$headers.filter('[data-column="' + indx + '"]:last').attr(wo.filter_defaultAttrib) || filters[indx];
				}
			}
			c.$table.data('lastSearch', filters);
			return filters;
		},
		parseFilter: function(table, filter, column, parsed, forceParse){
			var c = table.config;
			return forceParse || parsed ?
					c.parsers[column].format( filter, table, [], column ) :
					filter;
		},
		buildRow: function(table, c, wo) {
			var col, column, $header, buildSelect, disabled, name, ffxn,
			// c.columns defined in computeThIndexes()
					columns = c.columns,
					buildFilter = '<tr class="' + ts.css.filterRow + '">';
			for (column = 0; column < columns; column++) {
				buildFilter += '<td></td>';
			}
			c.$filters = $(buildFilter += '</tr>').appendTo( c.$table.children('thead').eq(0) ).find('td');
			// build each filter input
			for (column = 0; column < columns; column++) {
				disabled = false;
				// assuming last cell of a column is the main column
				$header = c.$headers.filter('[data-column="' + column + '"]:last');
				ffxn = ts.getColumnData( table, wo.filter_functions, column );
				buildSelect = (wo.filter_functions && ffxn && typeof ffxn !== "function" ) ||
						$header.hasClass('filter-select');
				// get data from jQuery data, metadata, headers option or header class name
				col = ts.getColumnData( table, c.headers, column );
				disabled = ts.getData($header[0], col, 'filter') === 'false' || ts.getData($header[0], col, 'parser') === 'false';

				if (buildSelect) {
					buildFilter = $('<select>').appendTo( c.$filters.eq(column) );
				} else {
					ffxn = ts.getColumnData( table, wo.filter_formatter, column );
					if (ffxn) {
						wo.filter_formatterCount++;
						buildFilter = ffxn( c.$filters.eq(column), column );
						// no element returned, so lets go find it
						if (buildFilter && buildFilter.length === 0) {
							buildFilter = c.$filters.eq(column).children('input');
						}
						// element not in DOM, so lets attach it
						if ( buildFilter && (buildFilter.parent().length === 0 ||
								(buildFilter.parent().length && buildFilter.parent()[0] !== c.$filters[column])) ) {
							c.$filters.eq(column).append(buildFilter);
						}
					} else {
						buildFilter = $('<input type="search" aria-label="Filter column ' + column + '">').appendTo( c.$filters.eq(column) );
					}
					if (buildFilter) {
						buildFilter.attr('placeholder', $header.data('placeholder') || $header.attr('data-placeholder') || wo.filter_placeholder.search || '');
					}
				}
				if (buildFilter) {
					// add filter class name
					name = ( $.isArray(wo.filter_cssFilter) ?
							(typeof wo.filter_cssFilter[column] !== 'undefined' ? wo.filter_cssFilter[column] || '' : '') :
							wo.filter_cssFilter ) || '';
					buildFilter.addClass( ts.css.filter + ' ' + name ).attr('data-column', column);
					if (disabled) {
						buildFilter.attr('placeholder', '').addClass('disabled')[0].disabled = true; // disabled!
					}
				}
			}
		},
		bindSearch: function(table, $el, internal) {
			table = $(table)[0];
			$el = $($el); // allow passing a selector string
			if (!$el.length) { return; }
			var c = table.config,
					wo = c.widgetOptions,
					$ext = wo.filter_$externalFilters;
			if (internal !== true) {
				// save anyMatch element
				wo.filter_$anyMatch = $el.filter('[data-column="all"]');
				if ($ext && $ext.length) {
					wo.filter_$externalFilters = wo.filter_$externalFilters.add( $el );
				} else {
					wo.filter_$externalFilters = $el;
				}
				// update values (external filters added after table initialization)
				ts.setFilters(table, c.$table.data('lastSearch') || [], internal === false);
			}
			$el
				// use data attribute instead of jQuery data since the head is cloned without including the data/binding
					.attr('data-lastSearchTime', new Date().getTime())
					.unbind('keypress keyup search change '.split(' ').join(c.namespace + 'filter '))
				// include change for select - fixes #473
					.bind('keyup' + c.namespace + 'filter', function(event) {
						$(this).attr('data-lastSearchTime', new Date().getTime());
						// emulate what webkit does.... escape clears the filter
						if (event.which === 27) {
							this.value = '';
							// live search
						} else if ( wo.filter_liveSearch === false ) {
							return;
							// don't return if the search value is empty (all rows need to be revealed)
						} else if ( this.value !== '' && (
							// liveSearch can contain a min value length; ignore arrow and meta keys, but allow backspace
								( typeof wo.filter_liveSearch === 'number' && this.value.length < wo.filter_liveSearch ) ||
									// let return & backspace continue on, but ignore arrows & non-valid characters
										( event.which !== 13 && event.which !== 8 && ( event.which < 32 || (event.which >= 37 && event.which <= 40) ) ) ) ) {
							return;
						}
						// change event = no delay; last true flag tells getFilters to skip newest timed input
						ts.filter.searching( table, true, true );
					})
					.bind('search change keypress '.split(' ').join(c.namespace + 'filter '), function(event){
						var column = $(this).data('column');
						// don't allow "change" event to process if the input value is the same - fixes #685
						if (event.which === 13 || event.type === 'search' || event.type === 'change' && this.value !== c.lastSearch[column]) {
							event.preventDefault();
							// init search with no delay
							$(this).attr('data-lastSearchTime', new Date().getTime());
							ts.filter.searching( table, false, true );
						}
					});
		},
		searching: function(table, filter, skipFirst) {
			var wo = table.config.widgetOptions;
			clearTimeout(wo.searchTimer);
			if (typeof filter === 'undefined' || filter === true) {
				// delay filtering
				wo.searchTimer = setTimeout(function() {
					ts.filter.checkFilters(table, filter, skipFirst );
				}, wo.filter_liveSearch ? wo.filter_searchDelay : 10);
			} else {
				// skip delay
				ts.filter.checkFilters(table, filter, skipFirst);
			}
		},
		checkFilters: function(table, filter, skipFirst) {
			var c = table.config,
					wo = c.widgetOptions,
					filterArray = $.isArray(filter),
					filters = (filterArray) ? filter : ts.getFilters(table, true),
					combinedFilters = (filters || []).join(''); // combined filter values
			// prevent errors if delay init is set
			if ($.isEmptyObject(c.cache)) {
				// update cache if delayInit set & pager has initialized (after user initiates a search)
				if (c.delayInit && c.pager && c.pager.initialized) {
					c.$table.trigger('updateCache', [function(){
						ts.filter.checkFilters(table, false, skipFirst);
					}] );
				}
				return;
			}
			// add filter array back into inputs
			if (filterArray) {
				ts.setFilters( table, filters, false, skipFirst !== true );
				if (!wo.filter_initialized) { c.lastCombinedFilter = ''; }
			}
			if (wo.filter_hideFilters) {
				// show/hide filter row as needed
				c.$table.find('.' + ts.css.filterRow).trigger( combinedFilters === '' ? 'mouseleave' : 'mouseenter' );
			}
			// return if the last search is the same; but filter === false when updating the search
			// see example-widget-filter.html filter toggle buttons
			if (c.lastCombinedFilter === combinedFilters && filter !== false) {
				return;
			} else if (filter === false) {
				// force filter refresh
				c.lastCombinedFilter = null;
				c.lastSearch = [];
			}
			if (wo.filter_initialized) { c.$table.trigger('filterStart', [filters]); }
			if (c.showProcessing) {
				// give it time for the processing icon to kick in
				setTimeout(function() {
					ts.filter.findRows(table, filters, combinedFilters);
					return false;
				}, 30);
			} else {
				ts.filter.findRows(table, filters, combinedFilters);
				return false;
			}
		},
		hideFilters: function(table, c) {
			var $filterRow, $filterRow2, timer;
			$(table)
					.find('.' + ts.css.filterRow)
					.addClass('hideme')
					.bind('mouseenter mouseleave', function(e) {
						// save event object - http://bugs.jquery.com/ticket/12140
						var event = e;
						$filterRow = $(this);
						clearTimeout(timer);
						timer = setTimeout(function() {
							if ( /enter|over/.test(event.type) ) {
								$filterRow.removeClass('hideme');
							} else {
								// don't hide if input has focus
								// $(':focus') needs jQuery 1.6+
								if ( $(document.activeElement).closest('tr')[0] !== $filterRow[0] ) {
									// don't hide row if any filter has a value
									if (c.lastCombinedFilter === '') {
										$filterRow.addClass('hideme');
									}
								}
							}
						}, 200);
					})
					.find('input, select').bind('focus blur', function(e) {
						$filterRow2 = $(this).closest('tr');
						clearTimeout(timer);
						var event = e;
						timer = setTimeout(function() {
							// don't hide row if any filter has a value
							if (ts.getFilters(c.$table).join('') === '') {
								$filterRow2[ event.type === 'focus' ? 'removeClass' : 'addClass']('hideme');
							}
						}, 200);
					});
		},
		findRows: function(table, filters, combinedFilters) {
			if (table.config.lastCombinedFilter === combinedFilters) { return; }
			var cached, len, $rows, rowIndex, tbodyIndex, $tbody, $cells, columnIndex,
					childRow, childRowText, exact, iExact, iFilter, lastSearch, matches, result,
					notFiltered, searchFiltered, filterMatched, showRow, time, val, indx,
					anyMatch, iAnyMatch, rowArray, rowText, iRowText, rowCache, fxn, ffxn,
					regex = ts.filter.regex,
					c = table.config,
					wo = c.widgetOptions,
					columns = c.columns,
					$tbodies = c.$table.children('tbody'), // target all tbodies #568
			// anyMatch really screws up with these types of filters
					anyMatchNotAllowedTypes = [ 'range', 'notMatch',  'operators' ],
			// parse columns after formatter, in case the class is added at that point
					parsed = c.$headers.map(function(columnIndex) {
						return c.parsers && c.parsers[columnIndex] && c.parsers[columnIndex].parsed ||
							// getData won't return "parsed" if other "filter-" class names exist (e.g. <th class="filter-select filter-parsed">)
								ts.getData && ts.getData(c.$headers.filter('[data-column="' + columnIndex + '"]:last'), ts.getColumnData( table, c.headers, columnIndex ), 'filter') === 'parsed' ||
								$(this).hasClass('filter-parsed');
					}).get();
			if (c.debug) { time = new Date(); }
			// filtered rows count
			c.filteredRows = 0;
			c.totalRows = 0;
			for (tbodyIndex = 0; tbodyIndex < $tbodies.length; tbodyIndex++ ) {
				if ($tbodies.eq(tbodyIndex).hasClass(c.cssInfoBlock || ts.css.info)) { continue; } // ignore info blocks, issue #264
				$tbody = ts.processTbody(table, $tbodies.eq(tbodyIndex), true);
				// skip child rows & widget added (removable) rows - fixes #448 thanks to @hempel!
				// $rows = $tbody.children('tr').not(c.selectorRemove);
				columnIndex = c.columns;
				// convert stored rows into a jQuery object
				$rows = $( $.map(c.cache[tbodyIndex].normalized, function(el){ return el[columnIndex].$row.get(); }) );

				if (combinedFilters === '' || wo.filter_serversideFiltering) {
					$rows.removeClass(wo.filter_filteredRow).not('.' + c.cssChildRow).show();
				} else {
					// filter out child rows
					$rows = $rows.not('.' + c.cssChildRow);
					len = $rows.length;
					// optimize searching only through already filtered rows - see #313
					searchFiltered = wo.filter_searchFiltered;
					lastSearch = c.lastSearch || c.$table.data('lastSearch') || [];
					if (searchFiltered) {
						// cycle through all filters; include last (columnIndex + 1 = match any column). Fixes #669
						for (indx = 0; indx < columnIndex + 1; indx++) {
							val = filters[indx] || '';
							// break out of loop if we've already determined not to search filtered rows
							if (!searchFiltered) { indx = columnIndex; }
							// search already filtered rows if...
							searchFiltered = searchFiltered && lastSearch.length &&
								// there are no changes from beginning of filter
									val.indexOf(lastSearch[indx] || '') === 0 &&
								// if there is NOT a logical "or", or range ("to" or "-") in the string
									!regex.alreadyFiltered.test(val) &&
								// if we are not doing exact matches, using "|" (logical or) or not "!"
									!/[=\"\|!]/.test(val) &&
								// don't search only filtered if the value is negative ('> -10' => '> -100' will ignore hidden rows)
									!(/(>=?\s*-\d)/.test(val) || /(<=?\s*\d)/.test(val)) &&
								// if filtering using a select without a "filter-match" class (exact match) - fixes #593
									!( val !== '' && c.$filters && c.$filters.eq(indx).find('select').length && !c.$headers.filter('[data-column="' + indx + '"]:last').hasClass('filter-match') );
						}
					}
					notFiltered = $rows.not('.' + wo.filter_filteredRow).length;
					// can't search when all rows are hidden - this happens when looking for exact matches
					if (searchFiltered && notFiltered === 0) { searchFiltered = false; }
					if (c.debug) {
						ts.log( "Searching through " + ( searchFiltered && notFiltered < len ? notFiltered : "all" ) + " rows" );
					}
					if ((wo.filter_$anyMatch && wo.filter_$anyMatch.length) || filters[c.columns]) {
						anyMatch = wo.filter_$anyMatch && wo.filter_$anyMatch.val() || filters[c.columns] || '';
						if (c.sortLocaleCompare) {
							// replace accents
							anyMatch = ts.replaceAccents(anyMatch);
						}
						iAnyMatch = anyMatch.toLowerCase();
					}
					// loop through the rows
					for (rowIndex = 0; rowIndex < len; rowIndex++) {
						childRow = $rows[rowIndex].className;
						// skip child rows & already filtered rows
						if ( regex.child.test(childRow) || (searchFiltered && regex.filtered.test(childRow)) ) { continue; }
						showRow = true;
						// *** nextAll/nextUntil not supported by Zepto! ***
						childRow = $rows.eq(rowIndex).nextUntil('tr:not(.' + c.cssChildRow + ')');
						// so, if "table.config.widgetOptions.filter_childRows" is true and there is
						// a match anywhere in the child row, then it will make the row visible
						// checked here so the option can be changed dynamically
						childRowText = (childRow.length && wo.filter_childRows) ? childRow.text() : '';
						childRowText = wo.filter_ignoreCase ? childRowText.toLocaleLowerCase() : childRowText;
						$cells = $rows.eq(rowIndex).children();

						if (anyMatch) {
							rowArray = $cells.map(function(i){
								var txt;
								if (parsed[i]) {
									txt = c.cache[tbodyIndex].normalized[rowIndex][i];
								} else {
									txt = wo.filter_ignoreCase ? $(this).text().toLowerCase() : $(this).text();
									if (c.sortLocaleCompare) {
										txt = ts.replaceAccents(txt);
									}
								}
								return txt;
							}).get();
							rowText = rowArray.join(' ');
							iRowText = rowText.toLowerCase();
							rowCache = c.cache[tbodyIndex].normalized[rowIndex].slice(0,-1).join(' ');
							filterMatched = null;
							$.each(ts.filter.types, function(type, typeFunction) {
								if ($.inArray(type, anyMatchNotAllowedTypes) < 0) {
									matches = typeFunction( anyMatch, iAnyMatch, rowText, iRowText, rowCache, columns, table, wo, parsed, rowArray );
									if (matches !== null) {
										filterMatched = matches;
										return false;
									}
								}
							});
							if (filterMatched !== null) {
								showRow = filterMatched;
							} else {
								if (wo.filter_startsWith) {
									showRow = false;
									columnIndex = columns;
									while (!showRow && columnIndex > 0) {
										columnIndex--;
										showRow = showRow || rowArray[columnIndex].indexOf(iAnyMatch) === 0;
									}
								} else {
									showRow = (iRowText + childRowText).indexOf(iAnyMatch) >= 0;
								}
							}
						}

						for (columnIndex = 0; columnIndex < columns; columnIndex++) {
							// ignore if filter is empty or disabled
							if (filters[columnIndex]) {
								cached = c.cache[tbodyIndex].normalized[rowIndex][columnIndex];
								// check if column data should be from the cell or from parsed data
								if (wo.filter_useParsedData || parsed[columnIndex]) {
									exact = cached;
								} else {
									// using older or original tablesorter
									exact = $.trim($cells.eq(columnIndex).text());
									exact = c.sortLocaleCompare ? ts.replaceAccents(exact) : exact; // issue #405
								}
								iExact = !regex.type.test(typeof exact) && wo.filter_ignoreCase ? exact.toLocaleLowerCase() : exact;
								result = showRow; // if showRow is true, show that row

								// in case select filter option has a different value vs text "a - z|A through Z"
								ffxn = wo.filter_columnFilters ?
										c.$filters.add(c.$externalFilters).filter('[data-column="'+ columnIndex + '"]').find('select option:selected').attr('data-function-name') || '' : '';

								// replace accents - see #357
								filters[columnIndex] = c.sortLocaleCompare ? ts.replaceAccents(filters[columnIndex]) : filters[columnIndex];
								// val = case insensitive, filters[columnIndex] = case sensitive
								iFilter = wo.filter_ignoreCase ? (filters[columnIndex] || '').toLocaleLowerCase() : filters[columnIndex];
								fxn = ts.getColumnData( table, wo.filter_functions, columnIndex );
								if (fxn) {
									if (fxn === true) {
										// default selector; no "filter-select" class
										result = (c.$headers.filter('[data-column="' + columnIndex + '"]:last').hasClass('filter-match')) ?
												iExact.search(iFilter) >= 0 : filters[columnIndex] === exact;
									} else if (typeof fxn === 'function') {
										// filter callback( exact cell content, parser normalized content, filter input value, column index, jQuery row object )
										result = fxn(exact, cached, filters[columnIndex], columnIndex, $rows.eq(rowIndex));
									} else if (typeof fxn[ffxn || filters[columnIndex]] === 'function') {
										// selector option function
										result = fxn[ffxn || filters[columnIndex]](exact, cached, filters[columnIndex], columnIndex, $rows.eq(rowIndex));
									}
								} else {
									filterMatched = null;
									// cycle through the different filters
									// filters return a boolean or null if nothing matches
									$.each(ts.filter.types, function(type, typeFunction) {
										matches = typeFunction( filters[columnIndex], iFilter, exact, iExact, cached, columnIndex, table, wo, parsed );
										if (matches !== null) {
											filterMatched = matches;
											return false;
										}
									});
									if (filterMatched !== null) {
										result = filterMatched;
										// Look for match, and add child row data for matching
									} else {
										exact = (iExact + childRowText).indexOf( ts.filter.parseFilter(table, iFilter, columnIndex, parsed[columnIndex]) );
										result = ( (!wo.filter_startsWith && exact >= 0) || (wo.filter_startsWith && exact === 0) );
									}
								}
								showRow = (result) ? showRow : false;
							}
						}
						$rows.eq(rowIndex)
								.toggle(showRow)
								.toggleClass(wo.filter_filteredRow, !showRow);
						if (childRow.length) {
							childRow.toggleClass(wo.filter_filteredRow, !showRow);
						}
					}
				}
				c.filteredRows += $rows.not('.' + wo.filter_filteredRow).length;
				c.totalRows += $rows.length;
				ts.processTbody(table, $tbody, false);
			}
			c.lastCombinedFilter = combinedFilters; // save last search
			c.lastSearch = filters;
			c.$table.data('lastSearch', filters);
			if (wo.filter_saveFilters && ts.storage) {
				ts.storage( table, 'tablesorter-filters', filters );
			}
			if (c.debug) {
				ts.benchmark("Completed filter widget search", time);
			}
			if (wo.filter_initialized) { c.$table.trigger('filterEnd', c ); }
			setTimeout(function(){
				c.$table.trigger('applyWidgets'); // make sure zebra widget is applied
			}, 0);
		},
		getOptionSource: function(table, column, onlyAvail) {
			var cts,
					c = table.config,
					wo = c.widgetOptions,
					parsed = [],
					arry = false,
					source = wo.filter_selectSource,
					last = c.$table.data('lastSearch') || [],
					fxn = $.isFunction(source) ? true : ts.getColumnData( table, source, column );

			if (onlyAvail && last[column] !== '') {
				onlyAvail = false;
			}

			// filter select source option
			if (fxn === true) {
				// OVERALL source
				arry = source(table, column, onlyAvail);
			} else if ( fxn instanceof $ || ($.type(fxn) === 'string' && fxn.indexOf('</option>') >= 0) ) {
				// selectSource is a jQuery object or string of options
				return fxn;
			} else if ($.isArray(fxn)) {
				arry = fxn;
			} else if ($.type(source) === 'object' && fxn) {
				// custom select source function for a SPECIFIC COLUMN
				arry = fxn(table, column, onlyAvail);
			}
			if (arry === false) {
				// fall back to original method
				arry = ts.filter.getOptions(table, column, onlyAvail);
			}

			// get unique elements and sort the list
			// if $.tablesorter.sortText exists (not in the original tablesorter),
			// then natural sort the list otherwise use a basic sort
			arry = $.grep(arry, function(value, indx) {
				return $.inArray(value, arry) === indx;
			});

			if (c.$headers.filter('[data-column="' + column + '"]:last').hasClass('filter-select-nosort')) {
				// unsorted select options
				return arry;
			} else {
				// parse select option values
				$.each(arry, function(i, v){
					// parse array data using set column parser; this DOES NOT pass the original
					// table cell to the parser format function
					parsed.push({ t : v, p : c.parsers && c.parsers[column].format( v, table, [], column ) });
				});

				// sort parsed select options
				cts = c.textSorter || '';
				parsed.sort(function(a, b){
					// sortNatural breaks if you don't pass it strings
					var x = a.p.toString(), y = b.p.toString();
					if ($.isFunction(cts)) {
						// custom OVERALL text sorter
						return cts(x, y, true, column, table);
					} else if (typeof(cts) === 'object' && cts.hasOwnProperty(column)) {
						// custom text sorter for a SPECIFIC COLUMN
						return cts[column](x, y, true, column, table);
					} else if (ts.sortNatural) {
						// fall back to natural sort
						return ts.sortNatural(x, y);
					}
					// using an older version! do a basic sort
					return true;
				});
				// rebuild arry from sorted parsed data
				arry = [];
				$.each(parsed, function(i, v){
					arry.push(v.t);
				});
				return arry;
			}
		},
		getOptions: function(table, column, onlyAvail) {
			var rowIndex, tbodyIndex, len, row, cache, cell,
					c = table.config,
					wo = c.widgetOptions,
					$tbodies = c.$table.children('tbody'),
					arry = [];
			for (tbodyIndex = 0; tbodyIndex < $tbodies.length; tbodyIndex++ ) {
				if (!$tbodies.eq(tbodyIndex).hasClass(c.cssInfoBlock)) {
					cache = c.cache[tbodyIndex];
					len = c.cache[tbodyIndex].normalized.length;
					// loop through the rows
					for (rowIndex = 0; rowIndex < len; rowIndex++) {
						// get cached row from cache.row (old) or row data object (new; last item in normalized array)
						row = cache.row ? cache.row[rowIndex] : cache.normalized[rowIndex][c.columns].$row[0];
						// check if has class filtered
						if (onlyAvail && row.className.match(wo.filter_filteredRow)) { continue; }
						// get non-normalized cell content
						if (wo.filter_useParsedData || c.parsers[column].parsed || c.$headers.filter('[data-column="' + column + '"]:last').hasClass('filter-parsed')) {
							arry.push( '' + cache.normalized[rowIndex][column] );
						} else {
							cell = row.cells[column];
							if (cell) {
								arry.push( $.trim( cell.textContent || cell.innerText || $(cell).text() ) );
							}
						}
					}
				}
			}
			return arry;
		},
		buildSelect: function(table, column, arry, updating, onlyAvail) {
			table = $(table)[0];
			column = parseInt(column, 10);
			if (!table.config.cache || $.isEmptyObject(table.config.cache)) { return; }
			var indx, val, txt, t, $filters, $filter,
					c = table.config,
					wo = c.widgetOptions,
					node = c.$headers.filter('[data-column="' + column + '"]:last'),
			// t.data('placeholder') won't work in jQuery older than 1.4.3
					options = '<option value="">' + ( node.data('placeholder') || node.attr('data-placeholder') || wo.filter_placeholder.select || '' ) + '</option>',
			// Get curent filter value
					currentValue = c.$table.find('thead').find('select.' + ts.css.filter + '[data-column="' + column + '"]').val();
			// nothing included in arry (external source), so get the options from filter_selectSource or column data
			if (typeof arry === 'undefined' || arry === '') {
				arry = ts.filter.getOptionSource(table, column, onlyAvail);
			}

			if ($.isArray(arry)) {
				// build option list
				for (indx = 0; indx < arry.length; indx++) {
					txt = arry[indx] = ('' + arry[indx]).replace(/\"/g, "&quot;");
					val = txt;
					// allow including a symbol in the selectSource array
					// "a-z|A through Z" so that "a-z" becomes the option value
					// and "A through Z" becomes the option text
					if (txt.indexOf(wo.filter_selectSourceSeparator) >= 0) {
						t = txt.split(wo.filter_selectSourceSeparator);
						val = t[0];
						txt = t[1];
					}
					// replace quotes - fixes #242 & ignore empty strings - see http://stackoverflow.com/q/14990971/145346
					options += arry[indx] !== '' ? '<option ' + (val === txt ? '' : 'data-function-name="' + arry[indx] + '" ') + 'value="' + val + '">' + txt + '</option>' : '';
				}
				// clear arry so it doesn't get appended twice
				arry = [];
			}

			// update all selects in the same column (clone thead in sticky headers & any external selects) - fixes 473
			$filters = ( c.$filters ? c.$filters : c.$table.children('thead') ).find('.' + ts.css.filter);
			if (wo.filter_$externalFilters) {
				$filters = $filters && $filters.length ? $filters.add(wo.filter_$externalFilters) : wo.filter_$externalFilters;
			}
			$filter = $filters.filter('select[data-column="' + column + '"]');

			// make sure there is a select there!
			if ($filter.length) {
				$filter[ updating ? 'html' : 'append' ](options);
				if (!$.isArray(arry)) {
					// append options if arry is provided externally as a string or jQuery object
					// options (default value) was already added
					$filter.append(arry).val(currentValue);
				}
				$filter.val(currentValue);
			}
		},
		buildDefault: function(table, updating) {
			var columnIndex, $header, noSelect,
					c = table.config,
					wo = c.widgetOptions,
					columns = c.columns;
			// build default select dropdown
			for (columnIndex = 0; columnIndex < columns; columnIndex++) {
				$header = c.$headers.filter('[data-column="' + columnIndex + '"]:last');
				noSelect = !($header.hasClass('filter-false') || $header.hasClass('parser-false'));
				// look for the filter-select class; build/update it if found
				if (($header.hasClass('filter-select') || ts.getColumnData( table, wo.filter_functions, columnIndex ) === true) && noSelect) {
					ts.filter.buildSelect(table, columnIndex, '', updating, $header.hasClass(wo.filter_onlyAvail));
				}
			}
		}
	};

	ts.getFilters = function(table, getRaw, setFilters, skipFirst) {
		var i, $filters, $column,
				filters = false,
				c = table ? $(table)[0].config : '',
				wo = c ? c.widgetOptions : '';
		if (getRaw !== true && wo && !wo.filter_columnFilters) {
			return $(table).data('lastSearch');
		}
		if (c) {
			if (c.$filters) {
				$filters = c.$filters.find('.' + ts.css.filter);
			}
			if (wo.filter_$externalFilters) {
				$filters = $filters && $filters.length ? $filters.add(wo.filter_$externalFilters) : wo.filter_$externalFilters;
			}
			if ($filters && $filters.length) {
				filters = setFilters || [];
				for (i = 0; i < c.columns + 1; i++) {
					$column = $filters.filter('[data-column="' + (i === c.columns ? 'all' : i) + '"]');
					if ($column.length) {
						// move the latest search to the first slot in the array
						$column = $column.sort(function(a, b){
							return $(b).attr('data-lastSearchTime') - $(a).attr('data-lastSearchTime');
						});
						if ($.isArray(setFilters)) {
							// skip first (latest input) to maintain cursor position while typing
							(skipFirst ? $column.slice(1) : $column).val( setFilters[i] ).trigger('change.tsfilter');
						} else {
							filters[i] = $column.val() || '';
							// don't change the first... it will move the cursor
							$column.slice(1).val( filters[i] );
						}
						// save any match input dynamically
						if (i === c.columns && $column.length) {
							wo.filter_$anyMatch = $column;
						}
					}
				}
			}
		}
		if (filters.length === 0) {
			filters = false;
		}
		return filters;
	};

	ts.setFilters = function(table, filter, apply, skipFirst) {
		var c = table ? $(table)[0].config : '',
				valid = ts.getFilters(table, true, filter, skipFirst);
		if (c && apply) {
			// ensure new set filters are applied, even if the search is the same
			c.lastCombinedFilter = null;
			c.lastSearch = [];
			ts.filter.searching(c.$table[0], filter, skipFirst);
			c.$table.trigger('filterFomatterUpdate');
		}
		return !!valid;
	};

// Widget: Sticky headers
// based on this awesome article:
// http://css-tricks.com/13465-persistent-headers/
// and https://github.com/jmosbech/StickyTableHeaders by Jonas Mosbech
// **************************
	ts.addWidget({
		id: "stickyHeaders",
		priority: 60, // sticky widget must be initialized after the filter widget!
		options: {
			stickyHeaders : '',       // extra class name added to the sticky header row
			stickyHeaders_attachTo : null, // jQuery selector or object to attach sticky header to
			stickyHeaders_offset : 0, // number or jquery selector targeting the position:fixed element
			stickyHeaders_filteredToTop: true, // scroll table top into view after filtering
			stickyHeaders_cloneId : '-sticky', // added to table ID, if it exists
			stickyHeaders_addResizeEvent : true, // trigger "resize" event on headers
			stickyHeaders_includeCaption : true, // if false and a caption exist, it won't be included in the sticky header
			stickyHeaders_zIndex : 2 // The zIndex of the stickyHeaders, allows the user to adjust this to their needs
		},
		format: function(table, c, wo) {
			// filter widget doesn't initialize on an empty table. Fixes #449
			if ( c.$table.hasClass('hasStickyHeaders') || ($.inArray('filter', c.widgets) >= 0 && !c.$table.hasClass('hasFilters')) ) {
				return;
			}
			var $table = c.$table,
					$attach = $(wo.stickyHeaders_attachTo),
					$thead = $table.children('thead:first'),
					$win = $attach.length ? $attach : $(window),
					$header = $thead.children('tr').not('.sticky-false').children(),
					innerHeader = '.' + ts.css.headerIn,
					$tfoot = $table.find('tfoot'),
					$stickyOffset = isNaN(wo.stickyHeaders_offset) ? $(wo.stickyHeaders_offset) : '',
					stickyOffset = $attach.length ? 0 : $stickyOffset.length ?
							$stickyOffset.height() || 0 : parseInt(wo.stickyHeaders_offset, 10) || 0,
					$stickyTable = wo.$sticky = $table.clone()
							.addClass('containsStickyHeaders')
							.css({
								position   : $attach.length ? 'absolute' : 'fixed',
								margin     : 0,
								top        : stickyOffset,
								left       : 0,
								visibility : 'hidden',
								zIndex     : wo.stickyHeaders_zIndex ? wo.stickyHeaders_zIndex : 2
							}),
					$stickyThead = $stickyTable.children('thead:first').addClass(ts.css.sticky + ' ' + wo.stickyHeaders),
					$stickyCells,
					laststate = '',
					spacing = 0,
					nonwkie = $table.css('border-collapse') !== 'collapse' && !/(webkit|msie)/i.test(navigator.userAgent),
					resizeHeader = function() {
						stickyOffset = $stickyOffset.length ? $stickyOffset.height() || 0 : parseInt(wo.stickyHeaders_offset, 10) || 0;
						spacing = 0;
						// yes, I dislike browser sniffing, but it really is needed here :(
						// webkit automatically compensates for border spacing
						if (nonwkie) {
							// Firefox & Opera use the border-spacing
							// update border-spacing here because of demos that switch themes
							spacing = parseInt($header.eq(0).css('border-left-width'), 10) * 2;
						}
						$stickyTable.css({
							left : $attach.length ? (parseInt($attach.css('padding-left'), 10) || 0) + parseInt(c.$table.css('padding-left'), 10) +
									parseInt(c.$table.css('margin-left'), 10) + parseInt($table.css('border-left-width'), 10) :
									$thead.offset().left - $win.scrollLeft() - spacing,
							width: $table.width()
						});
						$stickyCells.filter(':visible').each(function(i) {
							var $cell = $header.filter(':visible').eq(i),
							// some wibbly-wobbly... timey-wimey... stuff, to make columns line up in Firefox
									offset = nonwkie && $(this).attr('data-column') === ( '' + parseInt(c.columns/2, 10) ) ? 1 : 0;
							$(this)
									.css({ width: $cell.width() - spacing })
									.find(innerHeader).width( $cell.find(innerHeader).width() - offset );
						});
					};
			// fix clone ID, if it exists - fixes #271
			if ($stickyTable.attr('id')) { $stickyTable[0].id += wo.stickyHeaders_cloneId; }
			// clear out cloned table, except for sticky header
			// include caption & filter row (fixes #126 & #249) - don't remove cells to get correct cell indexing
			$stickyTable.find('thead:gt(0), tr.sticky-false').hide();
			$stickyTable.find('tbody, tfoot').remove();
			if (!wo.stickyHeaders_includeCaption) {
				$stickyTable.find('caption').remove();
			} else {
				$stickyTable.find('caption').css( 'margin-left', '-1px' );
			}
			// issue #172 - find td/th in sticky header
			$stickyCells = $stickyThead.children().children();
			$stickyTable.css({ height:0, width:0, padding:0, margin:0, border:0 });
			// remove resizable block
			$stickyCells.find('.' + ts.css.resizer).remove();
			// update sticky header class names to match real header after sorting
			$table
					.addClass('hasStickyHeaders')
					.bind('pagerComplete.tsSticky', function() {
						resizeHeader();
					});

			ts.bindEvents(table, $stickyThead.children().children('.tablesorter-header'));

			// add stickyheaders AFTER the table. If the table is selected by ID, the original one (first) will be returned.
			$table.after( $stickyTable );
			// make it sticky!
			$win.bind('scroll.tsSticky resize.tsSticky', function(event) {
				if (!$table.is(':visible')) { return; } // fixes #278
				var prefix = 'tablesorter-sticky-',
						offset = $table.offset(),
						captionHeight = (wo.stickyHeaders_includeCaption ? 0 : $table.find('caption').outerHeight(true)),
						scrollTop = ($attach.length ? $attach.offset().top : $win.scrollTop()) + stickyOffset - captionHeight,
						tableHeight = $table.height() - ($stickyTable.height() + ($tfoot.height() || 0)),
						isVisible = (scrollTop > offset.top) && (scrollTop < offset.top + tableHeight) ? 'visible' : 'hidden',
						cssSettings = { visibility : isVisible };
				if ($attach.length) {
					cssSettings.top = $attach.scrollTop();
				} else {
					// adjust when scrolling horizontally - fixes issue #143
					cssSettings.left = $thead.offset().left - $win.scrollLeft() - spacing;
				}
				$stickyTable
						.removeClass(prefix + 'visible ' + prefix + 'hidden')
						.addClass(prefix + isVisible)
						.css(cssSettings);
				if (isVisible !== laststate || event.type === 'resize') {
					// make sure the column widths match
					resizeHeader();
					laststate = isVisible;
				}
			});
			if (wo.stickyHeaders_addResizeEvent) {
				ts.addHeaderResizeEvent(table);
			}

			// look for filter widget
			if ($table.hasClass('hasFilters')) {
				// scroll table into view after filtering, if sticky header is active - #482
				$table.bind('filterEnd', function() {
					// $(':focus') needs jQuery 1.6+
					var $td = $(document.activeElement).closest('td'),
							column = $td.parent().children().index($td);
					// only scroll if sticky header is active
					if ($stickyTable.hasClass(ts.css.stickyVis) && wo.stickyHeaders_filteredToTop) {
						// scroll to original table (not sticky clone)
						window.scrollTo(0, $table.position().top);
						// give same input/select focus; check if c.$filters exists; fixes #594
						if (column >= 0 && c.$filters) {
							c.$filters.eq(column).find('a, select, input').filter(':visible').focus();
						}
					}
				});
				ts.filter.bindSearch( $table, $stickyCells.find('.' + ts.css.filter) );
				// support hideFilters
				if (wo.filter_hideFilters) {
					ts.filter.hideFilters($stickyTable, c);
				}
			}

			$table.trigger('stickyHeadersInit');

		},
		remove: function(table, c, wo) {
			c.$table
					.removeClass('hasStickyHeaders')
					.unbind('pagerComplete.tsSticky')
					.find('.' + ts.css.sticky).remove();
			if (wo.$sticky && wo.$sticky.length) { wo.$sticky.remove(); } // remove cloned table
			// don't unbind if any table on the page still has stickyheaders applied
			if (!$('.hasStickyHeaders').length) {
				$(window).unbind('scroll.tsSticky resize.tsSticky');
			}
			ts.addHeaderResizeEvent(table, false);
		}
	});

// Add Column resizing widget
// this widget saves the column widths if
// $.tablesorter.storage function is included
// **************************
	ts.addWidget({
		id: "resizable",
		priority: 40,
		options: {
			resizable : true,
			resizable_addLastColumn : false,
			resizable_widths : [],
			resizable_throttle : false // set to true (5ms) or any number 0-10 range
		},
		format: function(table, c, wo) {
			if (c.$table.hasClass('hasResizable')) { return; }
			c.$table.addClass('hasResizable');
			ts.resizableReset(table, true); // set default widths
			var $rows, $columns, $column, column, timer,
					storedSizes = {},
					$table = c.$table,
					mouseXPosition = 0,
					$target = null,
					$next = null,
					fullWidth = Math.abs($table.parent().width() - $table.width()) < 20,
					mouseMove = function(event){
						if (mouseXPosition === 0 || !$target) { return; }
						// resize columns
						var leftEdge = event.pageX - mouseXPosition,
								targetWidth = $target.width();
						$target.width( targetWidth + leftEdge );
						if ($target.width() !== targetWidth && fullWidth) {
							$next.width( $next.width() - leftEdge );
						}
						mouseXPosition = event.pageX;
					},
					stopResize = function() {
						if (ts.storage && $target && $next) {
							storedSizes = {};
							storedSizes[$target.index()] = $target.width();
							storedSizes[$next.index()] = $next.width();
							$target.width( storedSizes[$target.index()] );
							$next.width( storedSizes[$next.index()] );
							if (wo.resizable !== false) {
								// save all column widths
								ts.storage(table, 'tablesorter-resizable', c.$headers.map(function(){ return $(this).width(); }).get() );
							}
						}
						mouseXPosition = 0;
						$target = $next = null;
						$(window).trigger('resize'); // will update stickyHeaders, just in case
					};
			storedSizes = (ts.storage && wo.resizable !== false) ? ts.storage(table, 'tablesorter-resizable') : {};
			// process only if table ID or url match
			if (storedSizes) {
				for (column in storedSizes) {
					if (!isNaN(column) && column < c.$headers.length) {
						c.$headers.eq(column).width(storedSizes[column]); // set saved resizable widths
					}
				}
			}
			$rows = $table.children('thead:first').children('tr');
			// add resizable-false class name to headers (across rows as needed)
			$rows.children().each(function() {
				var canResize,
						$column = $(this);
				column = $column.attr('data-column');
				canResize = ts.getData( $column, ts.getColumnData( table, c.headers, column ), 'resizable') === "false";
				$rows.children().filter('[data-column="' + column + '"]')[canResize ? 'addClass' : 'removeClass']('resizable-false');
			});
			// add wrapper inside each cell to allow for positioning of the resizable target block
			$rows.each(function() {
				$column = $(this).children().not('.resizable-false');
				if (!$(this).find('.' + ts.css.wrapper).length) {
					// Firefox needs this inner div to position the resizer correctly
					$column.wrapInner('<div class="' + ts.css.wrapper + '" style="position:relative;height:100%;width:100%"></div>');
				}
				// don't include the last column of the row
				if (!wo.resizable_addLastColumn) { $column = $column.slice(0,-1); }
				$columns = $columns ? $columns.add($column) : $column;
			});
			$columns
					.each(function() {
						var $column = $(this),
								padding = parseInt($column.css('padding-right'), 10) + 10; // 10 is 1/2 of the 20px wide resizer
						$column
								.find('.' + ts.css.wrapper)
								.append('<div class="' + ts.css.resizer + '" style="cursor:w-resize;position:absolute;z-index:1;right:-' +
										padding + 'px;top:0;height:100%;width:20px;"></div>');
					})
					.find('.' + ts.css.resizer)
					.bind('mousedown', function(event) {
						// save header cell and mouse position
						$target = $(event.target).closest('th');
						var $header = c.$headers.filter('[data-column="' + $target.attr('data-column') + '"]');
						if ($header.length > 1) { $target = $target.add($header); }
						// if table is not as wide as it's parent, then resize the table
						$next = event.shiftKey ? $target.parent().find('th').not('.resizable-false').filter(':last') : $target.nextAll(':not(.resizable-false)').eq(0);
						mouseXPosition = event.pageX;
					});
			$(document)
					.bind('mousemove.tsresize', function(event) {
						// ignore mousemove if no mousedown
						if (mouseXPosition === 0 || !$target) { return; }
						if (wo.resizable_throttle) {
							clearTimeout(timer);
							timer = setTimeout(function(){
								mouseMove(event);
							}, isNaN(wo.resizable_throttle) ? 5 : wo.resizable_throttle );
						} else {
							mouseMove(event);
						}
					})
					.bind('mouseup.tsresize', function() {
						stopResize();
					});

			// right click to reset columns to default widths
			$table.find('thead:first').bind('contextmenu.tsresize', function() {
				ts.resizableReset(table);
				// $.isEmptyObject() needs jQuery 1.4+; allow right click if already reset
				var allowClick = $.isEmptyObject ? $.isEmptyObject(storedSizes) : true;
				storedSizes = {};
				return allowClick;
			});
		},
		remove: function(table, c) {
			c.$table
					.removeClass('hasResizable')
					.children('thead')
					.unbind('mouseup.tsresize mouseleave.tsresize contextmenu.tsresize')
					.children('tr').children()
					.unbind('mousemove.tsresize mouseup.tsresize')
				// don't remove "tablesorter-wrapper" as uitheme uses it too
					.find('.' + ts.css.resizer).remove();
			ts.resizableReset(table);
		}
	});
	ts.resizableReset = function(table, nosave) {
		$(table).each(function(){
			var $t,
					c = this.config,
					wo = c && c.widgetOptions;
			if (table && c) {
				c.$headers.each(function(i){
					$t = $(this);
					if (wo.resizable_widths[i]) {
						$t.css('width', wo.resizable_widths[i]);
					} else if (!$t.hasClass('resizable-false')) {
						// don't clear the width of any column that is not resizable
						$t.css('width','');
					}
				});
				if (ts.storage && !nosave) { ts.storage(this, 'tablesorter-resizable', {}); }
			}
		});
	};

// Save table sort widget
// this widget saves the last sort only if the
// saveSort widget option is true AND the
// $.tablesorter.storage function is included
// **************************
	ts.addWidget({
		id: 'saveSort',
		priority: 20,
		options: {
			saveSort : true
		},
		init: function(table, thisWidget, c, wo) {
			// run widget format before all other widgets are applied to the table
			thisWidget.format(table, c, wo, true);
		},
		format: function(table, c, wo, init) {
			var stored, time,
					$table = c.$table,
					saveSort = wo.saveSort !== false, // make saveSort active/inactive; default to true
					sortList = { "sortList" : c.sortList };
			if (c.debug) {
				time = new Date();
			}
			if ($table.hasClass('hasSaveSort')) {
				if (saveSort && table.hasInitialized && ts.storage) {
					ts.storage( table, 'tablesorter-savesort', sortList );
					if (c.debug) {
						ts.benchmark('saveSort widget: Saving last sort: ' + c.sortList, time);
					}
				}
			} else {
				// set table sort on initial run of the widget
				$table.addClass('hasSaveSort');
				sortList = '';
				// get data
				if (ts.storage) {
					stored = ts.storage( table, 'tablesorter-savesort' );
					sortList = (stored && stored.hasOwnProperty('sortList') && $.isArray(stored.sortList)) ? stored.sortList : '';
					if (c.debug) {
						ts.benchmark('saveSort: Last sort loaded: "' + sortList + '"', time);
					}
					$table.bind('saveSortReset', function(event) {
						event.stopPropagation();
						ts.storage( table, 'tablesorter-savesort', '' );
					});
				}
				// init is true when widget init is run, this will run this widget before all other widgets have initialized
				// this method allows using this widget in the original tablesorter plugin; but then it will run all widgets twice.
				if (init && sortList && sortList.length > 0) {
					c.sortList = sortList;
				} else if (table.hasInitialized && sortList && sortList.length > 0) {
					// update sort change
					$table.trigger('sorton', [sortList]);
				}
			}
		},
		remove: function(table) {
			// clear storage
			if (ts.storage) { ts.storage( table, 'tablesorter-savesort', '' ); }
		}
	});

})(jQuery);


/*! jQuery Validation Plugin - v1.19.1 - 6/15/2019
 * https://jqueryvalidation.org/
 * Copyright (c) 2019 Jörn Zaefferer; Licensed MIT */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):"object"==typeof module&&module.exports?module.exports=a(require("jquery")):a(jQuery)}(function(a){a.extend(a.fn,{validate:function(b){if(!this.length)return void(b&&b.debug&&window.console&&console.warn("Nothing selected, can't validate, returning nothing."));var c=a.data(this[0],"validator");return c?c:(this.attr("novalidate","novalidate"),c=new a.validator(b,this[0]),a.data(this[0],"validator",c),c.settings.onsubmit&&(this.on("click.validate",":submit",function(b){c.submitButton=b.currentTarget,a(this).hasClass("cancel")&&(c.cancelSubmit=!0),void 0!==a(this).attr("formnovalidate")&&(c.cancelSubmit=!0)}),this.on("submit.validate",function(b){function d(){var d,e;return c.submitButton&&(c.settings.submitHandler||c.formSubmitted)&&(d=a("<input type='hidden'/>").attr("name",c.submitButton.name).val(a(c.submitButton).val()).appendTo(c.currentForm)),!(c.settings.submitHandler&&!c.settings.debug)||(e=c.settings.submitHandler.call(c,c.currentForm,b),d&&d.remove(),void 0!==e&&e)}return c.settings.debug&&b.preventDefault(),c.cancelSubmit?(c.cancelSubmit=!1,d()):c.form()?c.pendingRequest?(c.formSubmitted=!0,!1):d():(c.focusInvalid(),!1)})),c)},valid:function(){var b,c,d;return a(this[0]).is("form")?b=this.validate().form():(d=[],b=!0,c=a(this[0].form).validate(),this.each(function(){b=c.element(this)&&b,b||(d=d.concat(c.errorList))}),c.errorList=d),b},rules:function(b,c){var d,e,f,g,h,i,j=this[0],k="undefined"!=typeof this.attr("contenteditable")&&"false"!==this.attr("contenteditable");if(null!=j&&(!j.form&&k&&(j.form=this.closest("form")[0],j.name=this.attr("name")),null!=j.form)){if(b)switch(d=a.data(j.form,"validator").settings,e=d.rules,f=a.validator.staticRules(j),b){case"add":a.extend(f,a.validator.normalizeRule(c)),delete f.messages,e[j.name]=f,c.messages&&(d.messages[j.name]=a.extend(d.messages[j.name],c.messages));break;case"remove":return c?(i={},a.each(c.split(/\s/),function(a,b){i[b]=f[b],delete f[b]}),i):(delete e[j.name],f)}return g=a.validator.normalizeRules(a.extend({},a.validator.classRules(j),a.validator.attributeRules(j),a.validator.dataRules(j),a.validator.staticRules(j)),j),g.required&&(h=g.required,delete g.required,g=a.extend({required:h},g)),g.remote&&(h=g.remote,delete g.remote,g=a.extend(g,{remote:h})),g}}}),a.extend(a.expr.pseudos||a.expr[":"],{blank:function(b){return!a.trim(""+a(b).val())},filled:function(b){var c=a(b).val();return null!==c&&!!a.trim(""+c)},unchecked:function(b){return!a(b).prop("checked")}}),a.validator=function(b,c){this.settings=a.extend(!0,{},a.validator.defaults,b),this.currentForm=c,this.init()},a.validator.format=function(b,c){return 1===arguments.length?function(){var c=a.makeArray(arguments);return c.unshift(b),a.validator.format.apply(this,c)}:void 0===c?b:(arguments.length>2&&c.constructor!==Array&&(c=a.makeArray(arguments).slice(1)),c.constructor!==Array&&(c=[c]),a.each(c,function(a,c){b=b.replace(new RegExp("\\{"+a+"\\}","g"),function(){return c})}),b)},a.extend(a.validator,{defaults:{messages:{},groups:{},rules:{},errorClass:"error",pendingClass:"pending",validClass:"valid",errorElement:"label",focusCleanup:!1,focusInvalid:!0,errorContainer:a([]),errorLabelContainer:a([]),onsubmit:!0,ignore:":hidden",ignoreTitle:!1,onfocusin:function(a){this.lastActive=a,this.settings.focusCleanup&&(this.settings.unhighlight&&this.settings.unhighlight.call(this,a,this.settings.errorClass,this.settings.validClass),this.hideThese(this.errorsFor(a)))},onfocusout:function(a){this.checkable(a)||!(a.name in this.submitted)&&this.optional(a)||this.element(a)},onkeyup:function(b,c){var d=[16,17,18,20,35,36,37,38,39,40,45,144,225];9===c.which&&""===this.elementValue(b)||a.inArray(c.keyCode,d)!==-1||(b.name in this.submitted||b.name in this.invalid)&&this.element(b)},onclick:function(a){a.name in this.submitted?this.element(a):a.parentNode.name in this.submitted&&this.element(a.parentNode)},highlight:function(b,c,d){"radio"===b.type?this.findByName(b.name).addClass(c).removeClass(d):a(b).addClass(c).removeClass(d)},unhighlight:function(b,c,d){"radio"===b.type?this.findByName(b.name).removeClass(c).addClass(d):a(b).removeClass(c).addClass(d)}},setDefaults:function(b){a.extend(a.validator.defaults,b)},messages:{required:"This field is required.",remote:"Please fix this field.",email:"Please enter a valid email address.",url:"Please enter a valid URL.",date:"Please enter a valid date.",dateISO:"Please enter a valid date (ISO).",number:"Please enter a valid number.",digits:"Please enter only digits.",equalTo:"Please enter the same value again.",maxlength:a.validator.format("Please enter no more than {0} characters."),minlength:a.validator.format("Please enter at least {0} characters."),rangelength:a.validator.format("Please enter a value between {0} and {1} characters long."),range:a.validator.format("Please enter a value between {0} and {1}."),max:a.validator.format("Please enter a value less than or equal to {0}."),min:a.validator.format("Please enter a value greater than or equal to {0}."),step:a.validator.format("Please enter a multiple of {0}.")},autoCreateRanges:!1,prototype:{init:function(){function b(b){var c="undefined"!=typeof a(this).attr("contenteditable")&&"false"!==a(this).attr("contenteditable");if(!this.form&&c&&(this.form=a(this).closest("form")[0],this.name=a(this).attr("name")),d===this.form){var e=a.data(this.form,"validator"),f="on"+b.type.replace(/^validate/,""),g=e.settings;g[f]&&!a(this).is(g.ignore)&&g[f].call(e,this,b)}}this.labelContainer=a(this.settings.errorLabelContainer),this.errorContext=this.labelContainer.length&&this.labelContainer||a(this.currentForm),this.containers=a(this.settings.errorContainer).add(this.settings.errorLabelContainer),this.submitted={},this.valueCache={},this.pendingRequest=0,this.pending={},this.invalid={},this.reset();var c,d=this.currentForm,e=this.groups={};a.each(this.settings.groups,function(b,c){"string"==typeof c&&(c=c.split(/\s/)),a.each(c,function(a,c){e[c]=b})}),c=this.settings.rules,a.each(c,function(b,d){c[b]=a.validator.normalizeRule(d)}),a(this.currentForm).on("focusin.validate focusout.validate keyup.validate",":text, [type='password'], [type='file'], select, textarea, [type='number'], [type='search'], [type='tel'], [type='url'], [type='email'], [type='datetime'], [type='date'], [type='month'], [type='week'], [type='time'], [type='datetime-local'], [type='range'], [type='color'], [type='radio'], [type='checkbox'], [contenteditable], [type='button']",b).on("click.validate","select, option, [type='radio'], [type='checkbox']",b),this.settings.invalidHandler&&a(this.currentForm).on("invalid-form.validate",this.settings.invalidHandler)},form:function(){return this.checkForm(),a.extend(this.submitted,this.errorMap),this.invalid=a.extend({},this.errorMap),this.valid()||a(this.currentForm).triggerHandler("invalid-form",[this]),this.showErrors(),this.valid()},checkForm:function(){this.prepareForm();for(var a=0,b=this.currentElements=this.elements();b[a];a++)this.check(b[a]);return this.valid()},element:function(b){var c,d,e=this.clean(b),f=this.validationTargetFor(e),g=this,h=!0;return void 0===f?delete this.invalid[e.name]:(this.prepareElement(f),this.currentElements=a(f),d=this.groups[f.name],d&&a.each(this.groups,function(a,b){b===d&&a!==f.name&&(e=g.validationTargetFor(g.clean(g.findByName(a))),e&&e.name in g.invalid&&(g.currentElements.push(e),h=g.check(e)&&h))}),c=this.check(f)!==!1,h=h&&c,c?this.invalid[f.name]=!1:this.invalid[f.name]=!0,this.numberOfInvalids()||(this.toHide=this.toHide.add(this.containers)),this.showErrors(),a(b).attr("aria-invalid",!c)),h},showErrors:function(b){if(b){var c=this;a.extend(this.errorMap,b),this.errorList=a.map(this.errorMap,function(a,b){return{message:a,element:c.findByName(b)[0]}}),this.successList=a.grep(this.successList,function(a){return!(a.name in b)})}this.settings.showErrors?this.settings.showErrors.call(this,this.errorMap,this.errorList):this.defaultShowErrors()},resetForm:function(){a.fn.resetForm&&a(this.currentForm).resetForm(),this.invalid={},this.submitted={},this.prepareForm(),this.hideErrors();var b=this.elements().removeData("previousValue").removeAttr("aria-invalid");this.resetElements(b)},resetElements:function(a){var b;if(this.settings.unhighlight)for(b=0;a[b];b++)this.settings.unhighlight.call(this,a[b],this.settings.errorClass,""),this.findByName(a[b].name).removeClass(this.settings.validClass);else a.removeClass(this.settings.errorClass).removeClass(this.settings.validClass)},numberOfInvalids:function(){return this.objectLength(this.invalid)},objectLength:function(a){var b,c=0;for(b in a)void 0!==a[b]&&null!==a[b]&&a[b]!==!1&&c++;return c},hideErrors:function(){this.hideThese(this.toHide)},hideThese:function(a){a.not(this.containers).text(""),this.addWrapper(a).hide()},valid:function(){return 0===this.size()},size:function(){return this.errorList.length},focusInvalid:function(){if(this.settings.focusInvalid)try{a(this.findLastActive()||this.errorList.length&&this.errorList[0].element||[]).filter(":visible").trigger("focus").trigger("focusin")}catch(b){}},findLastActive:function(){var b=this.lastActive;return b&&1===a.grep(this.errorList,function(a){return a.element.name===b.name}).length&&b},elements:function(){var b=this,c={};return a(this.currentForm).find("input, select, textarea, [contenteditable]").not(":submit, :reset, :image, :disabled").not(this.settings.ignore).filter(function(){var d=this.name||a(this).attr("name"),e="undefined"!=typeof a(this).attr("contenteditable")&&"false"!==a(this).attr("contenteditable");return!d&&b.settings.debug&&window.console&&console.error("%o has no name assigned",this),e&&(this.form=a(this).closest("form")[0],this.name=d),this.form===b.currentForm&&(!(d in c||!b.objectLength(a(this).rules()))&&(c[d]=!0,!0))})},clean:function(b){return a(b)[0]},errors:function(){var b=this.settings.errorClass.split(" ").join(".");return a(this.settings.errorElement+"."+b,this.errorContext)},resetInternals:function(){this.successList=[],this.errorList=[],this.errorMap={},this.toShow=a([]),this.toHide=a([])},reset:function(){this.resetInternals(),this.currentElements=a([])},prepareForm:function(){this.reset(),this.toHide=this.errors().add(this.containers)},prepareElement:function(a){this.reset(),this.toHide=this.errorsFor(a)},elementValue:function(b){var c,d,e=a(b),f=b.type,g="undefined"!=typeof e.attr("contenteditable")&&"false"!==e.attr("contenteditable");return"radio"===f||"checkbox"===f?this.findByName(b.name).filter(":checked").val():"number"===f&&"undefined"!=typeof b.validity?b.validity.badInput?"NaN":e.val():(c=g?e.text():e.val(),"file"===f?"C:\\fakepath\\"===c.substr(0,12)?c.substr(12):(d=c.lastIndexOf("/"),d>=0?c.substr(d+1):(d=c.lastIndexOf("\\"),d>=0?c.substr(d+1):c)):"string"==typeof c?c.replace(/\r/g,""):c)},check:function(b){b=this.validationTargetFor(this.clean(b));var c,d,e,f,g=a(b).rules(),h=a.map(g,function(a,b){return b}).length,i=!1,j=this.elementValue(b);"function"==typeof g.normalizer?f=g.normalizer:"function"==typeof this.settings.normalizer&&(f=this.settings.normalizer),f&&(j=f.call(b,j),delete g.normalizer);for(d in g){e={method:d,parameters:g[d]};try{if(c=a.validator.methods[d].call(this,j,b,e.parameters),"dependency-mismatch"===c&&1===h){i=!0;continue}if(i=!1,"pending"===c)return void(this.toHide=this.toHide.not(this.errorsFor(b)));if(!c)return this.formatAndAdd(b,e),!1}catch(k){throw this.settings.debug&&window.console&&console.log("Exception occurred when checking element "+b.id+", check the '"+e.method+"' method.",k),k instanceof TypeError&&(k.message+=".  Exception occurred when checking element "+b.id+", check the '"+e.method+"' method."),k}}if(!i)return this.objectLength(g)&&this.successList.push(b),!0},customDataMessage:function(b,c){return a(b).data("msg"+c.charAt(0).toUpperCase()+c.substring(1).toLowerCase())||a(b).data("msg")},customMessage:function(a,b){var c=this.settings.messages[a];return c&&(c.constructor===String?c:c[b])},findDefined:function(){for(var a=0;a<arguments.length;a++)if(void 0!==arguments[a])return arguments[a]},defaultMessage:function(b,c){"string"==typeof c&&(c={method:c});var d=this.findDefined(this.customMessage(b.name,c.method),this.customDataMessage(b,c.method),!this.settings.ignoreTitle&&b.title||void 0,a.validator.messages[c.method],"<strong>Warning: No message defined for "+b.name+"</strong>"),e=/\$?\{(\d+)\}/g;return"function"==typeof d?d=d.call(this,c.parameters,b):e.test(d)&&(d=a.validator.format(d.replace(e,"{$1}"),c.parameters)),d},formatAndAdd:function(a,b){var c=this.defaultMessage(a,b);this.errorList.push({message:c,element:a,method:b.method}),this.errorMap[a.name]=c,this.submitted[a.name]=c},addWrapper:function(a){return this.settings.wrapper&&(a=a.add(a.parent(this.settings.wrapper))),a},defaultShowErrors:function(){var a,b,c;for(a=0;this.errorList[a];a++)c=this.errorList[a],this.settings.highlight&&this.settings.highlight.call(this,c.element,this.settings.errorClass,this.settings.validClass),this.showLabel(c.element,c.message);if(this.errorList.length&&(this.toShow=this.toShow.add(this.containers)),this.settings.success)for(a=0;this.successList[a];a++)this.showLabel(this.successList[a]);if(this.settings.unhighlight)for(a=0,b=this.validElements();b[a];a++)this.settings.unhighlight.call(this,b[a],this.settings.errorClass,this.settings.validClass);this.toHide=this.toHide.not(this.toShow),this.hideErrors(),this.addWrapper(this.toShow).show()},validElements:function(){return this.currentElements.not(this.invalidElements())},invalidElements:function(){return a(this.errorList).map(function(){return this.element})},showLabel:function(b,c){var d,e,f,g,h=this.errorsFor(b),i=this.idOrName(b),j=a(b).attr("aria-describedby");h.length?(h.removeClass(this.settings.validClass).addClass(this.settings.errorClass),h.html(c)):(h=a("<"+this.settings.errorElement+">").attr("id",i+"-error").addClass(this.settings.errorClass).html(c||""),d=h,this.settings.wrapper&&(d=h.hide().show().wrap("<"+this.settings.wrapper+"/>").parent()),this.labelContainer.length?this.labelContainer.append(d):this.settings.errorPlacement?this.settings.errorPlacement.call(this,d,a(b)):d.insertAfter(b),h.is("label")?h.attr("for",i):0===h.parents("label[for='"+this.escapeCssMeta(i)+"']").length&&(f=h.attr("id"),j?j.match(new RegExp("\\b"+this.escapeCssMeta(f)+"\\b"))||(j+=" "+f):j=f,a(b).attr("aria-describedby",j),e=this.groups[b.name],e&&(g=this,a.each(g.groups,function(b,c){c===e&&a("[name='"+g.escapeCssMeta(b)+"']",g.currentForm).attr("aria-describedby",h.attr("id"))})))),!c&&this.settings.success&&(h.text(""),"string"==typeof this.settings.success?h.addClass(this.settings.success):this.settings.success(h,b)),this.toShow=this.toShow.add(h)},errorsFor:function(b){var c=this.escapeCssMeta(this.idOrName(b)),d=a(b).attr("aria-describedby"),e="label[for='"+c+"'], label[for='"+c+"'] *";return d&&(e=e+", #"+this.escapeCssMeta(d).replace(/\s+/g,", #")),this.errors().filter(e)},escapeCssMeta:function(a){return a.replace(/([\\!"#$%&'()*+,.\/:;<=>?@\[\]^`{|}~])/g,"\\$1")},idOrName:function(a){return this.groups[a.name]||(this.checkable(a)?a.name:a.id||a.name)},validationTargetFor:function(b){return this.checkable(b)&&(b=this.findByName(b.name)),a(b).not(this.settings.ignore)[0]},checkable:function(a){return/radio|checkbox/i.test(a.type)},findByName:function(b){return a(this.currentForm).find("[name='"+this.escapeCssMeta(b)+"']")},getLength:function(b,c){switch(c.nodeName.toLowerCase()){case"select":return a("option:selected",c).length;case"input":if(this.checkable(c))return this.findByName(c.name).filter(":checked").length}return b.length},depend:function(a,b){return!this.dependTypes[typeof a]||this.dependTypes[typeof a](a,b)},dependTypes:{"boolean":function(a){return a},string:function(b,c){return!!a(b,c.form).length},"function":function(a,b){return a(b)}},optional:function(b){var c=this.elementValue(b);return!a.validator.methods.required.call(this,c,b)&&"dependency-mismatch"},startRequest:function(b){this.pending[b.name]||(this.pendingRequest++,a(b).addClass(this.settings.pendingClass),this.pending[b.name]=!0)},stopRequest:function(b,c){this.pendingRequest--,this.pendingRequest<0&&(this.pendingRequest=0),delete this.pending[b.name],a(b).removeClass(this.settings.pendingClass),c&&0===this.pendingRequest&&this.formSubmitted&&this.form()?(a(this.currentForm).submit(),this.submitButton&&a("input:hidden[name='"+this.submitButton.name+"']",this.currentForm).remove(),this.formSubmitted=!1):!c&&0===this.pendingRequest&&this.formSubmitted&&(a(this.currentForm).triggerHandler("invalid-form",[this]),this.formSubmitted=!1)},previousValue:function(b,c){return c="string"==typeof c&&c||"remote",a.data(b,"previousValue")||a.data(b,"previousValue",{old:null,valid:!0,message:this.defaultMessage(b,{method:c})})},destroy:function(){this.resetForm(),a(this.currentForm).off(".validate").removeData("validator").find(".validate-equalTo-blur").off(".validate-equalTo").removeClass("validate-equalTo-blur").find(".validate-lessThan-blur").off(".validate-lessThan").removeClass("validate-lessThan-blur").find(".validate-lessThanEqual-blur").off(".validate-lessThanEqual").removeClass("validate-lessThanEqual-blur").find(".validate-greaterThanEqual-blur").off(".validate-greaterThanEqual").removeClass("validate-greaterThanEqual-blur").find(".validate-greaterThan-blur").off(".validate-greaterThan").removeClass("validate-greaterThan-blur")}},classRuleSettings:{required:{required:!0},email:{email:!0},url:{url:!0},date:{date:!0},dateISO:{dateISO:!0},number:{number:!0},digits:{digits:!0},creditcard:{creditcard:!0}},addClassRules:function(b,c){b.constructor===String?this.classRuleSettings[b]=c:a.extend(this.classRuleSettings,b)},classRules:function(b){var c={},d=a(b).attr("class");return d&&a.each(d.split(" "),function(){this in a.validator.classRuleSettings&&a.extend(c,a.validator.classRuleSettings[this])}),c},normalizeAttributeRule:function(a,b,c,d){/min|max|step/.test(c)&&(null===b||/number|range|text/.test(b))&&(d=Number(d),isNaN(d)&&(d=void 0)),d||0===d?a[c]=d:b===c&&"range"!==b&&(a[c]=!0)},attributeRules:function(b){var c,d,e={},f=a(b),g=b.getAttribute("type");for(c in a.validator.methods)"required"===c?(d=b.getAttribute(c),""===d&&(d=!0),d=!!d):d=f.attr(c),this.normalizeAttributeRule(e,g,c,d);return e.maxlength&&/-1|2147483647|524288/.test(e.maxlength)&&delete e.maxlength,e},dataRules:function(b){var c,d,e={},f=a(b),g=b.getAttribute("type");for(c in a.validator.methods)d=f.data("rule"+c.charAt(0).toUpperCase()+c.substring(1).toLowerCase()),""===d&&(d=!0),this.normalizeAttributeRule(e,g,c,d);return e},staticRules:function(b){var c={},d=a.data(b.form,"validator");return d.settings.rules&&(c=a.validator.normalizeRule(d.settings.rules[b.name])||{}),c},normalizeRules:function(b,c){return a.each(b,function(d,e){if(e===!1)return void delete b[d];if(e.param||e.depends){var f=!0;switch(typeof e.depends){case"string":f=!!a(e.depends,c.form).length;break;case"function":f=e.depends.call(c,c)}f?b[d]=void 0===e.param||e.param:(a.data(c.form,"validator").resetElements(a(c)),delete b[d])}}),a.each(b,function(d,e){b[d]=a.isFunction(e)&&"normalizer"!==d?e(c):e}),a.each(["minlength","maxlength"],function(){b[this]&&(b[this]=Number(b[this]))}),a.each(["rangelength","range"],function(){var c;b[this]&&(a.isArray(b[this])?b[this]=[Number(b[this][0]),Number(b[this][1])]:"string"==typeof b[this]&&(c=b[this].replace(/[\[\]]/g,"").split(/[\s,]+/),b[this]=[Number(c[0]),Number(c[1])]))}),a.validator.autoCreateRanges&&(null!=b.min&&null!=b.max&&(b.range=[b.min,b.max],delete b.min,delete b.max),null!=b.minlength&&null!=b.maxlength&&(b.rangelength=[b.minlength,b.maxlength],delete b.minlength,delete b.maxlength)),b},normalizeRule:function(b){if("string"==typeof b){var c={};a.each(b.split(/\s/),function(){c[this]=!0}),b=c}return b},addMethod:function(b,c,d){a.validator.methods[b]=c,a.validator.messages[b]=void 0!==d?d:a.validator.messages[b],c.length<3&&a.validator.addClassRules(b,a.validator.normalizeRule(b))},methods:{required:function(b,c,d){if(!this.depend(d,c))return"dependency-mismatch";if("select"===c.nodeName.toLowerCase()){var e=a(c).val();return e&&e.length>0}return this.checkable(c)?this.getLength(b,c)>0:void 0!==b&&null!==b&&b.length>0},email:function(a,b){return this.optional(b)||/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(a)},url:function(a,b){return this.optional(b)||/^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[\/?#]\S*)?$/i.test(a)},date:function(){var a=!1;return function(b,c){return a||(a=!0,this.settings.debug&&window.console&&console.warn("The `date` method is deprecated and will be removed in version '2.0.0'.\nPlease don't use it, since it relies on the Date constructor, which\nbehaves very differently across browsers and locales. Use `dateISO`\ninstead or one of the locale specific methods in `localizations/`\nand `additional-methods.js`.")),this.optional(c)||!/Invalid|NaN/.test(new Date(b).toString())}}(),dateISO:function(a,b){return this.optional(b)||/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/.test(a)},number:function(a,b){return this.optional(b)||/^(?:-?\d+|-?\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(a)},digits:function(a,b){return this.optional(b)||/^\d+$/.test(a)},minlength:function(b,c,d){var e=a.isArray(b)?b.length:this.getLength(b,c);return this.optional(c)||e>=d},maxlength:function(b,c,d){var e=a.isArray(b)?b.length:this.getLength(b,c);return this.optional(c)||e<=d},rangelength:function(b,c,d){var e=a.isArray(b)?b.length:this.getLength(b,c);return this.optional(c)||e>=d[0]&&e<=d[1]},min:function(a,b,c){return this.optional(b)||a>=c},max:function(a,b,c){return this.optional(b)||a<=c},range:function(a,b,c){return this.optional(b)||a>=c[0]&&a<=c[1]},step:function(b,c,d){var e,f=a(c).attr("type"),g="Step attribute on input type "+f+" is not supported.",h=["text","number","range"],i=new RegExp("\\b"+f+"\\b"),j=f&&!i.test(h.join()),k=function(a){var b=(""+a).match(/(?:\.(\d+))?$/);return b&&b[1]?b[1].length:0},l=function(a){return Math.round(a*Math.pow(10,e))},m=!0;if(j)throw new Error(g);return e=k(d),(k(b)>e||l(b)%l(d)!==0)&&(m=!1),this.optional(c)||m},equalTo:function(b,c,d){var e=a(d);return this.settings.onfocusout&&e.not(".validate-equalTo-blur").length&&e.addClass("validate-equalTo-blur").on("blur.validate-equalTo",function(){a(c).valid()}),b===e.val()},remote:function(b,c,d,e){if(this.optional(c))return"dependency-mismatch";e="string"==typeof e&&e||"remote";var f,g,h,i=this.previousValue(c,e);return this.settings.messages[c.name]||(this.settings.messages[c.name]={}),i.originalMessage=i.originalMessage||this.settings.messages[c.name][e],this.settings.messages[c.name][e]=i.message,d="string"==typeof d&&{url:d}||d,h=a.param(a.extend({data:b},d.data)),i.old===h?i.valid:(i.old=h,f=this,this.startRequest(c),g={},g[c.name]=b,a.ajax(a.extend(!0,{mode:"abort",port:"validate"+c.name,dataType:"json",data:g,context:f.currentForm,success:function(a){var d,g,h,j=a===!0||"true"===a;f.settings.messages[c.name][e]=i.originalMessage,j?(h=f.formSubmitted,f.resetInternals(),f.toHide=f.errorsFor(c),f.formSubmitted=h,f.successList.push(c),f.invalid[c.name]=!1,f.showErrors()):(d={},g=a||f.defaultMessage(c,{method:e,parameters:b}),d[c.name]=i.message=g,f.invalid[c.name]=!0,f.showErrors(d)),i.valid=j,f.stopRequest(c,j)}},d)),"pending")}}});var b,c={};return a.ajaxPrefilter?a.ajaxPrefilter(function(a,b,d){var e=a.port;"abort"===a.mode&&(c[e]&&c[e].abort(),c[e]=d)}):(b=a.ajax,a.ajax=function(d){var e=("mode"in d?d:a.ajaxSettings).mode,f=("port"in d?d:a.ajaxSettings).port;return"abort"===e?(c[f]&&c[f].abort(),c[f]=b.apply(this,arguments),c[f]):b.apply(this,arguments)}),a});
(function($){$.fn.touchwipe=function(settings){var config={min_move_x:20,min_move_y:20,wipeLeft:function(){},wipeRight:function(){},wipeUp:function(){},wipeDown:function(){},preventDefaultEvents:true};if(settings)$.extend(config,settings);this.each(function(){var startX;var startY;var isMoving=false;function cancelTouch(){this.removeEventListener("touchmove",onTouchMove);startX=null;startY=null;isMoving=false}function onTouchMove(e){if(config.preventDefaultEvents){e.preventDefault()}if(isMoving){var x=e.touches[0].pageX;var y=e.touches[0].pageY;var dx=startX-x;var dy=startY-y;if(Math.abs(dx)>=config.min_move_x){cancelTouch();if(dx>0){config.wipeLeft(Math.abs(dx))}else{config.wipeRight(Math.abs(dx))}}else if(Math.abs(dy)>=config.min_move_y){cancelTouch();if(dy>0){config.wipeDown(Math.abs(dy))}else{config.wipeUp(Math.abs(dy))}}}}function onTouchStart(e){if(e.touches.length==1){startX=e.touches[0].pageX;startY=e.touches[0].pageY;isMoving=true;this.addEventListener("touchmove",onTouchMove,false)}}if("ontouchstart"in document.documentElement){this.addEventListener("touchstart",onTouchStart,false)}});return this}})(jQuery);
(function($){$.fn.rwdImageMaps=function(){var $img=this;var rwdImageMap=function(){$img.each(function(){if(typeof $(this).attr("usemap")=="undefined")return;var that=this,$that=$(that);$("<img />").on("load",function(){var attrW="width",attrH="height",w=$that.attr(attrW),h=$that.attr(attrH);if(!w||!h){var temp=new Image;temp.src=$that.attr("src");if(!w)w=temp.width;if(!h)h=temp.height}var wPercent=$that.width()/100,hPercent=$that.height()/100,map=$that.attr("usemap").replace("#",""),c="coords";$('map[name="'+map+'"]').find("area").each(function(){var $this=$(this);if(!$this.data(c))$this.data(c,$this.attr(c));var coords=$this.data(c).split(","),coordsPercent=new Array(coords.length);for(var i=0;i<coordsPercent.length;++i){if(i%2===0)coordsPercent[i]=parseInt(coords[i]/w*100*wPercent);else coordsPercent[i]=parseInt(coords[i]/h*100*hPercent)}$this.attr(c,coordsPercent.toString())})}).attr("src",$that.attr("src"))})};$(window).resize(rwdImageMap).trigger("resize");return this}})(jQuery);
(function(){function stripHtml(value){return value.replace(/<.[^<>]*?>/g," ").replace(/&nbsp;|&#160;/gi," ").replace(/[0-9.(),;:!?%#$'"_+=\/-]*/g,"")}jQuery.validator.addMethod("maxWords",function(value,element,params){return this.optional(element)||stripHtml(value).match(/\b\w+\b/g).length<params},jQuery.validator.format("Please enter {0} words or less."));jQuery.validator.addMethod("minWords",function(value,element,params){return this.optional(element)||stripHtml(value).match(/\b\w+\b/g).length>=params},jQuery.validator.format("Please enter at least {0} words."));jQuery.validator.addMethod("rangeWords",function(value,element,params){return this.optional(element)||stripHtml(value).match(/\b\w+\b/g).length>=params[0]&&value.match(/bw+b/g).length<params[1]},jQuery.validator.format("Please enter between {0} and {1} words."))})();jQuery.validator.addMethod("letterswithbasicpunc",function(value,element){return this.optional(element)||/^[a-z-.,()'\"\s]+$/i.test(value)},"Letters or punctuation only please");jQuery.validator.addMethod("alphanumeric",function(value,element){return this.optional(element)||/^\w+$/i.test(value)},"Letters, numbers, spaces or underscores only please");jQuery.validator.addMethod("lettersonly",function(value,element){return this.optional(element)||/^[a-z]+$/i.test(value)},"Letters only please");jQuery.validator.addMethod("nowhitespace",function(value,element){return this.optional(element)||/^\S+$/i.test(value)},"No white space please");jQuery.validator.addMethod("ziprange",function(value,element){return this.optional(element)||/^90[2-5]\d\{2}-\d{4}$/.test(value)},"Your ZIP-code must be in the range 902xx-xxxx to 905-xx-xxxx");jQuery.validator.addMethod("integer",function(value,element){return this.optional(element)||/^-?\d+$/.test(value)},"A positive or negative non-decimal number please");jQuery.validator.addMethod("vinUS",function(v){if(v.length!=17)return false;var i,n,d,f,cd,cdv;var LL=["A","B","C","D","E","F","G","H","J","K","L","M","N","P","R","S","T","U","V","W","X","Y","Z"];var VL=[1,2,3,4,5,6,7,8,1,2,3,4,5,7,9,2,3,4,5,6,7,8,9];var FL=[8,7,6,5,4,3,2,10,0,9,8,7,6,5,4,3,2];var rs=0;for(i=0;i<17;i++){f=FL[i];d=v.slice(i,i+1);if(i==8){cdv=d}if(!isNaN(d)){d*=f}else{for(n=0;n<LL.length;n++){if(d.toUpperCase()===LL[n]){d=VL[n];d*=f;if(isNaN(cdv)&&n==8){cdv=LL[n]}break}}}rs+=d}cd=rs%11;if(cd==10){cd="X"}if(cd==cdv){return true}return false},"The specified vehicle identification number (VIN) is invalid.");jQuery.validator.addMethod("dateITA",function(value,element){var check=false;var re=/^\d{1,2}\/\d{1,2}\/\d{4}$/;if(re.test(value)){var adata=value.split("/");var gg=parseInt(adata[0],10);var mm=parseInt(adata[1],10);var aaaa=parseInt(adata[2],10);var xdata=new Date(aaaa,mm-1,gg);if(xdata.getFullYear()==aaaa&&xdata.getMonth()==mm-1&&xdata.getDate()==gg)check=true;else check=false}else check=false;return this.optional(element)||check},"Please enter a correct date");jQuery.validator.addMethod("dateNL",function(value,element){return this.optional(element)||/^\d\d?[\.\/-]\d\d?[\.\/-]\d\d\d?\d?$/.test(value)},"Vul hier een geldige datum in.");jQuery.validator.addMethod("time",function(value,element){return this.optional(element)||/^([01][0-9])|(2[0123]):([0-5])([0-9])$/.test(value)},"Please enter a valid time, between 00:00 and 23:59");jQuery.validator.addMethod("phoneUS",function(phone_number,element){phone_number=phone_number.replace(/\s+/g,"");return this.optional(element)||phone_number.length>9&&phone_number.match(/^(1-?)?(\([2-9]\d{2}\)|[2-9]\d{2})-?[2-9]\d{2}-?\d{4}$/)},"Please specify a valid phone number");jQuery.validator.addMethod("phoneUK",function(phone_number,element){return this.optional(element)||phone_number.length>9&&phone_number.match(/^(\(?(0|\+44)[1-9]{1}\d{1,4}?\)?\s?\d{3,4}\s?\d{3,4})$/)},"Please specify a valid phone number");jQuery.validator.addMethod("mobileUK",function(phone_number,element){return this.optional(element)||phone_number.length>9&&phone_number.match(/^((0|\+44)7(5|6|7|8|9){1}\d{2}\s?\d{6})$/)},"Please specify a valid mobile number");jQuery.validator.addMethod("strippedminlength",function(value,element,param){return jQuery(value).text().length>=param},jQuery.validator.format("Please enter at least {0} characters"));jQuery.validator.addMethod("email2",function(value,element,param){return this.optional(element)||/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)*(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(value)},jQuery.validator.messages.email);jQuery.validator.addMethod("url2",function(value,element,param){return this.optional(element)||/^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)*(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value)},jQuery.validator.messages.url);jQuery.validator.addMethod("creditcardtypes",function(value,element,param){if(/[^0-9-]+/.test(value))return false;value=value.replace(/\D/g,"");var validTypes=0;if(param.mastercard)validTypes|=1;if(param.visa)validTypes|=2;if(param.amex)validTypes|=4;if(param.dinersclub)validTypes|=8;if(param.enroute)validTypes|=16;if(param.discover)validTypes|=32;if(param.jcb)validTypes|=64;if(param.unknown)validTypes|=128;if(param.all)validTypes=1|2|4|8|16|32|64|128;if(validTypes&1&&/^(51|52|53|54|55)/.test(value)){return value.length==16}if(validTypes&2&&/^(4)/.test(value)){return value.length==16}if(validTypes&4&&/^(34|37)/.test(value)){return value.length==15}if(validTypes&8&&/^(300|301|302|303|304|305|36|38)/.test(value)){return value.length==14}if(validTypes&16&&/^(2014|2149)/.test(value)){return value.length==15}if(validTypes&32&&/^(6011)/.test(value)){return value.length==16}if(validTypes&64&&/^(3)/.test(value)){return value.length==16}if(validTypes&64&&/^(2131|1800)/.test(value)){return value.length==15}if(validTypes&128){return true}return false},"Please enter a valid credit card number.");jQuery.validator.addMethod("ipv4",function(value,element,param){return this.optional(element)||/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/i.test(value)},"Please enter a valid IP v4 address.");jQuery.validator.addMethod("ipv6",function(value,element,param){return this.optional(element)||/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i.test(value)},"Please enter a valid IP v6 address.");
$.fn.rater=function(options){var opts=$.extend({},$.fn.rater.defaults,options);return this.each(function(){var $this=$(this),$on=$this.find(".ui-rater-starsOn"),$off=$this.find(".ui-rater-starsOff");if(opts.size==undefined)opts.size=$off.height();if(opts.rating==undefined){opts.rating=$on.width()/$off.width()}else{$on.width($off.width()*(opts.rating/opts.ratings.length))}if(opts.id==undefined)opts.id=$this.attr("id");var initialRating=opts.rating;if(!$this.hasClass("ui-rater-bindings-done")){$this.addClass("ui-rater-bindings-done");$off.mousemove(function(e){var left=e.clientX-$off.offset().left,width=$off.width()-($off.width()-left);width=Math.min(Math.ceil(width/(opts.size/opts.step))*opts.size/opts.step,opts.size*opts.ratings.length);$on.width(width);var r=Math.round($on.width()/$off.width()*(opts.ratings.length*opts.step))/opts.step;$this.attr("title",'Click to Rate "'+r+' stars"')}).hover(function(e){$on.addClass("ui-rater-starsHover")},function(e){$on.removeClass("ui-rater-starsHover");$on.width(initialRating*opts.size)}).click(function(e){var r=Math.round($on.width()/$off.width()*(opts.ratings.length*opts.step))/opts.step;$.fn.rater.rate($this,opts,r)}).css("cursor","pointer");$on.css("cursor","pointer")}})};$.fn.rater.defaults={url:location.href,ratings:["Hated It","Didn't Like It","Liked It","Really Liked It","Loved It"],step:1};$.fn.rater.rate=function($this,opts,rating){if(Globals.loggedIn){var $on=$this.find(".ui-rater-starsOn"),$off=$this.find(".ui-rater-starsOff");$off.fadeTo(600,.4,function(){$.getJSON(opts.url,{id:opts.id,rating:rating},function(data){if(data.error)AspenDiscovery.showMessage(data.error);if(data.rating){opts.rating=data.rating;$on.css("cursor","default");$off.unbind("click").unbind("mousemove").unbind("mouseenter").unbind("mouseleave").css("cursor","default").fadeTo(600,.1,function(){$on.removeClass("ui-rater-starsHover").width(opts.rating*opts.size).addClass("userRated");$off.fadeTo(500,1);$this.attr("title","Your rating: "+rating.toFixed(1));if($this.data("show_review")==true){AspenDiscovery.Ratings.doRatingReview(opts.id)}})}}).fail(AspenDiscovery.ajaxFail)})}else{AspenDiscovery.Account.ajaxLogin(null,function(){$.fn.rater.rate($this,opts,rating)})}};
(function(){var h,k=this,ba=function(){},l=function(a){var b=typeof a;if("object"==b)if(a){if(a instanceof Array)return"array";if(a instanceof Object)return b;var c=Object.prototype.toString.call(a);if("[object Window]"==c)return"object";if("[object Array]"==c||"number"==typeof a.length&&"undefined"!=typeof a.splice&&"undefined"!=typeof a.propertyIsEnumerable&&!a.propertyIsEnumerable("splice"))return"array";if("[object Function]"==c||"undefined"!=typeof a.call&&"undefined"!=typeof a.propertyIsEnumerable&&!a.propertyIsEnumerable("call"))return"function"}else return"null";else if("function"==b&&"undefined"==typeof a.call)return"object";return b},n=function(a){return"array"==l(a)},ca=function(a){var b=l(a);return"array"==b||"object"==b&&"number"==typeof a.length},p=function(a){return"string"==typeof a},q=function(a){return"function"==l(a)},r=function(a){var b=typeof a;return"object"==b&&null!=a||"function"==b},da=function(a,b,c){return a.call.apply(a.bind,arguments)},ea=function(a,b,c){if(!a)throw Error();if(2<arguments.length){var d=Array.prototype.slice.call(arguments,2);return function(){var c=Array.prototype.slice.call(arguments);Array.prototype.unshift.apply(c,d);return a.apply(b,c)}}return function(){return a.apply(b,arguments)}},s=function(a,b,c){s=Function.prototype.bind&&-1!=Function.prototype.bind.toString().indexOf("native code")?da:ea;return s.apply(null,arguments)},fa=function(a,b){var c=Array.prototype.slice.call(arguments,1);return function(){var b=c.slice();b.push.apply(b,arguments);return a.apply(this,b)}},ga=Date.now||function(){return+new Date},ha=null,t=function(a,b){var c=a.split("."),d=k;c[0]in d||!d.execScript||d.execScript("var "+c[0]);for(var e;c.length&&(e=c.shift());)c.length||void 0===b?d=d[e]?d[e]:d[e]={}:d[e]=b},u=function(a,b){function c(){}c.prototype=b.prototype;a.superClass_=b.prototype;a.prototype=new c;a.prototype.constructor=a;a.base=function(a,c,g){return b.prototype[c].apply(a,Array.prototype.slice.call(arguments,2))}};Function.prototype.bind=Function.prototype.bind||function(a,b){if(1<arguments.length){var c=Array.prototype.slice.call(arguments,1);c.unshift(this,a);return s.apply(null,c)}return s(this,a)};var v={};t("RecaptchaTemplates",v);v.VertHtml='<table id="recaptcha_table" class="recaptchatable" > <tr> <td colspan="6" class=\'recaptcha_r1_c1\'></td> </tr> <tr> <td class=\'recaptcha_r2_c1\'></td> <td colspan="4" class=\'recaptcha_image_cell\'><center><div id="recaptcha_image"></div></center></td> <td class=\'recaptcha_r2_c2\'></td> </tr> <tr> <td rowspan="6" class=\'recaptcha_r3_c1\'></td> <td colspan="4" class=\'recaptcha_r3_c2\'></td> <td rowspan="6" class=\'recaptcha_r3_c3\'></td> </tr> <tr> <td rowspan="3" class=\'recaptcha_r4_c1\' height="49"> <div class="recaptcha_input_area"> <input name="recaptcha_response_field" id="recaptcha_response_field" type="text" autocorrect="off" autocapitalize="off" placeholder="" /> <span id="recaptcha_privacy" class="recaptcha_only_if_privacy"></span> </div> </td> <td rowspan="4" class=\'recaptcha_r4_c2\'></td> <td><a id=\'recaptcha_reload_btn\'><img id=\'recaptcha_reload\' width="25" height="17" /></a></td> <td rowspan="4" class=\'recaptcha_r4_c4\'></td> </tr> <tr> <td><a id=\'recaptcha_switch_audio_btn\' class="recaptcha_only_if_image"><img id=\'recaptcha_switch_audio\' width="25" height="16" alt="" /></a><a id=\'recaptcha_switch_img_btn\' class="recaptcha_only_if_audio"><img id=\'recaptcha_switch_img\' width="25" height="16" alt=""/></a></td> </tr> <tr> <td><a id=\'recaptcha_whatsthis_btn\'><img id=\'recaptcha_whatsthis\' width="25" height="16" /></a></td> </tr> <tr> <td class=\'recaptcha_r7_c1\'></td> <td class=\'recaptcha_r8_c1\'></td> </tr> </table> ';v.CleanCss=".recaptchatable td img{display:block}.recaptchatable .recaptcha_image_cell center img{height:57px}.recaptchatable .recaptcha_image_cell center{height:57px}.recaptchatable .recaptcha_image_cell{background-color:white;height:57px;padding:7px!important}.recaptchatable,#recaptcha_area tr,#recaptcha_area td,#recaptcha_area th{margin:0!important;border:0!important;border-collapse:collapse!important;vertical-align:middle!important}.recaptchatable *{margin:0;padding:0;border:0;color:black;position:static;top:auto;left:auto;right:auto;bottom:auto}.recaptchatable #recaptcha_image{position:relative;margin:auto;border:1px solid #dfdfdf!important}.recaptchatable #recaptcha_image #recaptcha_challenge_image{display:block}.recaptchatable #recaptcha_image #recaptcha_ad_image{display:block;position:absolute;top:0}.recaptchatable a img{border:0}.recaptchatable a,.recaptchatable a:hover{cursor:pointer;outline:none;border:0!important;padding:0!important;text-decoration:none;color:blue;background:none!important;font-weight:normal}.recaptcha_input_area{position:relative!important;background:none!important}.recaptchatable label.recaptcha_input_area_text{border:1px solid #dfdfdf!important;margin:0!important;padding:0!important;position:static!important;top:auto!important;left:auto!important;right:auto!important;bottom:auto!important}.recaptcha_theme_red label.recaptcha_input_area_text,.recaptcha_theme_white label.recaptcha_input_area_text{color:black!important}.recaptcha_theme_blackglass label.recaptcha_input_area_text{color:white!important}.recaptchatable #recaptcha_response_field{font-size:11pt}.recaptcha_theme_blackglass #recaptcha_response_field,.recaptcha_theme_white #recaptcha_response_field{border:1px solid gray}.recaptcha_theme_red #recaptcha_response_field{border:1px solid #cca940}.recaptcha_audio_cant_hear_link{font-size:7pt;color:black}.recaptchatable{line-height:1em;border:1px solid #dfdfdf!important}.recaptcha_error_text{color:red}.recaptcha_only_if_privacy{float:right;text-align:right;margin-right:7px}#recaptcha-ad-choices{position:absolute;height:15px;top:0;right:0}#recaptcha-ad-choices img{height:15px}.recaptcha-ad-choices-collapsed{width:30px;height:15px;display:block}.recaptcha-ad-choices-expanded{width:75px;height:15px;display:none}#recaptcha-ad-choices:hover .recaptcha-ad-choices-collapsed{display:none}#recaptcha-ad-choices:hover .recaptcha-ad-choices-expanded{display:block}";v.CleanHtml='<table id="recaptcha_table" class="recaptchatable"> <tr height="73"> <td class=\'recaptcha_image_cell\' width="302"><center><div id="recaptcha_image"></div></center></td> <td style="padding: 10px 7px 7px 7px;"> <a id=\'recaptcha_reload_btn\'><img id=\'recaptcha_reload\' width="25" height="18" alt="" /></a> <a id=\'recaptcha_switch_audio_btn\' class="recaptcha_only_if_image"><img id=\'recaptcha_switch_audio\' width="25" height="15" alt="" /></a><a id=\'recaptcha_switch_img_btn\' class="recaptcha_only_if_audio"><img id=\'recaptcha_switch_img\' width="25" height="15" alt=""/></a> <a id=\'recaptcha_whatsthis_btn\'><img id=\'recaptcha_whatsthis\' width="25" height="16" /></a> </td> <td style="padding: 18px 7px 18px 7px;"> <img id=\'recaptcha_logo\' alt="" width="71" height="36" /> </td> </tr> <tr> <td style="padding-left: 7px;"> <div class="recaptcha_input_area" style="padding-top: 2px; padding-bottom: 7px;"> <input style="border: 1px solid #3c3c3c; width: 302px;" name="recaptcha_response_field" id="recaptcha_response_field" type="text" /> </div> </td> <td colspan=2><span id="recaptcha_privacy" class="recaptcha_only_if_privacy"></span></td> </tr> </table> ';v.VertCss=".recaptchatable td img{display:block}.recaptchatable .recaptcha_r1_c1{background:url('IMGROOT/sprite.png') 0 -63px no-repeat;width:318px;height:9px}.recaptchatable .recaptcha_r2_c1{background:url('IMGROOT/sprite.png') -18px 0 no-repeat;width:9px;height:57px}.recaptchatable .recaptcha_r2_c2{background:url('IMGROOT/sprite.png') -27px 0 no-repeat;width:9px;height:57px}.recaptchatable .recaptcha_r3_c1{background:url('IMGROOT/sprite.png') 0 0 no-repeat;width:9px;height:63px}.recaptchatable .recaptcha_r3_c2{background:url('IMGROOT/sprite.png') -18px -57px no-repeat;width:300px;height:6px}.recaptchatable .recaptcha_r3_c3{background:url('IMGROOT/sprite.png') -9px 0 no-repeat;width:9px;height:63px}.recaptchatable .recaptcha_r4_c1{background:url('IMGROOT/sprite.png') -43px 0 no-repeat;width:171px;height:49px}.recaptchatable .recaptcha_r4_c2{background:url('IMGROOT/sprite.png') -36px 0 no-repeat;width:7px;height:57px}.recaptchatable .recaptcha_r4_c4{background:url('IMGROOT/sprite.png') -214px 0 no-repeat;width:97px;height:57px}.recaptchatable .recaptcha_r7_c1{background:url('IMGROOT/sprite.png') -43px -49px no-repeat;width:171px;height:8px}.recaptchatable .recaptcha_r8_c1{background:url('IMGROOT/sprite.png') -43px -49px no-repeat;width:25px;height:8px}.recaptchatable .recaptcha_image_cell center img{height:57px}.recaptchatable .recaptcha_image_cell center{height:57px}.recaptchatable .recaptcha_image_cell{background-color:white;height:57px}#recaptcha_area,#recaptcha_table{width:318px!important}.recaptchatable,#recaptcha_area tr,#recaptcha_area td,#recaptcha_area th{margin:0!important;border:0!important;padding:0!important;border-collapse:collapse!important;vertical-align:middle!important}.recaptchatable *{margin:0;padding:0;border:0;font-family:helvetica,sans-serif;font-size:8pt;color:black;position:static;top:auto;left:auto;right:auto;bottom:auto}.recaptchatable #recaptcha_image{position:relative;margin:auto}.recaptchatable #recaptcha_image #recaptcha_challenge_image{display:block}.recaptchatable #recaptcha_image #recaptcha_ad_image{display:block;position:absolute;top:0}.recaptchatable img{border:0!important;margin:0!important;padding:0!important}.recaptchatable a,.recaptchatable a:hover{cursor:pointer;outline:none;border:0!important;padding:0!important;text-decoration:none;color:blue;background:none!important;font-weight:normal}.recaptcha_input_area{position:relative!important;width:153px!important;height:45px!important;margin-left:7px!important;margin-right:7px!important;background:none!important}.recaptchatable label.recaptcha_input_area_text{margin:0!important;padding:0!important;position:static!important;top:auto!important;left:auto!important;right:auto!important;bottom:auto!important;background:none!important;height:auto!important;width:auto!important}.recaptcha_theme_red label.recaptcha_input_area_text,.recaptcha_theme_white label.recaptcha_input_area_text{color:black!important}.recaptcha_theme_blackglass label.recaptcha_input_area_text{color:white!important}.recaptchatable #recaptcha_response_field{width:153px!important;position:relative!important;bottom:7px!important;padding:0!important;margin:15px 0 0 0!important;font-size:10pt}.recaptcha_theme_blackglass #recaptcha_response_field,.recaptcha_theme_white #recaptcha_response_field{border:1px solid gray}.recaptcha_theme_red #recaptcha_response_field{border:1px solid #cca940}.recaptcha_audio_cant_hear_link{font-size:7pt;color:black}.recaptchatable{line-height:1!important}#recaptcha_instructions_error{color:red!important}.recaptcha_only_if_privacy{float:right;text-align:right}#recaptcha-ad-choices{position:absolute;height:15px;top:0;right:0}#recaptcha-ad-choices img{height:15px}.recaptcha-ad-choices-collapsed{width:30px;height:15px;display:block}.recaptcha-ad-choices-expanded{width:75px;height:15px;display:none}#recaptcha-ad-choices:hover .recaptcha-ad-choices-collapsed{display:none}#recaptcha-ad-choices:hover .recaptcha-ad-choices-expanded{display:block}";var w={visual_challenge:"Get a visual challenge",audio_challenge:"Get an audio challenge",refresh_btn:"Get a new challenge",instructions_visual:"Type the text:",instructions_audio:"Type what you hear:",help_btn:"Help",play_again:"Play sound again",cant_hear_this:"Download sound as MP3",incorrect_try_again:"Incorrect. Try again.",image_alt_text:"reCAPTCHA challenge image",privacy_and_terms:"Privacy & Terms"},ia={visual_challenge:"الحصول على تحدٍ مرئي",audio_challenge:"الحصول على تحدٍ صوتي",refresh_btn:"الحصول على تحدٍ جديد",instructions_visual:"يرجى كتابة النص:",instructions_audio:"اكتب ما تسمعه:",help_btn:"مساعدة",play_again:"تشغيل الصوت مرة أخرى",cant_hear_this:"تنزيل الصوت بتنسيق MP3",incorrect_try_again:"غير صحيح. أعد المحاولة.",image_alt_text:"صورة التحدي من reCAPTCHA",privacy_and_terms:"الخصوصية والبنود"},ja={visual_challenge:"Obtener una pista visual",audio_challenge:"Obtener una pista sonora",refresh_btn:"Obtener una pista nueva",instructions_visual:"Introduzca el texto:",instructions_audio:"Escribe lo que oigas:",help_btn:"Ayuda",play_again:"Volver a reproducir el sonido",cant_hear_this:"Descargar el sonido en MP3",incorrect_try_again:"Incorrecto. Vuélvelo a intentar.",image_alt_text:"Pista de imagen reCAPTCHA",privacy_and_terms:"Privacidad y condiciones"},ka={visual_challenge:"Kumuha ng pagsubok na visual",audio_challenge:"Kumuha ng pagsubok na audio",refresh_btn:"Kumuha ng bagong pagsubok",instructions_visual:"I-type ang teksto:",instructions_audio:"I-type ang iyong narinig",help_btn:"Tulong",play_again:"I-play muli ang tunog",cant_hear_this:"I-download ang tunog bilang MP3",incorrect_try_again:"Hindi wasto. Muling subukan.",image_alt_text:"larawang panghamon ng reCAPTCHA",privacy_and_terms:"Privacy at Mga Tuntunin"},la={visual_challenge:"Test visuel",audio_challenge:"Test audio",refresh_btn:"Nouveau test",instructions_visual:"Saisissez le texte :",instructions_audio:"Qu'entendez-vous ?",help_btn:"Aide",play_again:"Réécouter",cant_hear_this:"Télécharger l'audio au format MP3",incorrect_try_again:"Incorrect. Veuillez réessayer.",image_alt_text:"Image reCAPTCHA",privacy_and_terms:"Confidentialité et conditions d'utilisation"},ma={visual_challenge:"Dapatkan kata pengujian berbentuk visual",audio_challenge:"Dapatkan kata pengujian berbentuk audio",refresh_btn:"Dapatkan kata pengujian baru",instructions_visual:"Ketik teks:",instructions_audio:"Ketik yang Anda dengar:",help_btn:"Bantuan",play_again:"Putar suara sekali lagi",cant_hear_this:"Unduh suara sebagai MP3",incorrect_try_again:"Salah. Coba lagi.",image_alt_text:"Gambar tantangan reCAPTCHA",privacy_and_terms:"Privasi & Persyaratan"},na={visual_challenge:"קבל אתגר חזותי",audio_challenge:"קבל אתגר שמע",refresh_btn:"קבל אתגר חדש",instructions_visual:"הקלד את הטקסט:",instructions_audio:"הקלד את מה שאתה שומע:",help_btn:"עזרה",play_again:"הפעל שוב את השמע",cant_hear_this:"הורד שמע כ-3MP",incorrect_try_again:"שגוי. נסה שוב.",image_alt_text:"תמונת אתגר של reCAPTCHA",privacy_and_terms:"פרטיות ותנאים"},oa={visual_challenge:"Obter um desafio visual",audio_challenge:"Obter um desafio de áudio",refresh_btn:"Obter um novo desafio",instructions_visual:"Digite o texto:",instructions_audio:"Digite o que você ouve:",help_btn:"Ajuda",play_again:"Reproduzir som novamente",cant_hear_this:"Fazer download do som no formato MP3",incorrect_try_again:"Incorreto. Tente novamente.",image_alt_text:"Imagem de desafio reCAPTCHA",privacy_and_terms:"Privacidade e Termos"},pa={visual_challenge:"Obţineţi un cod captcha vizual",audio_challenge:"Obţineţi un cod captcha audio",refresh_btn:"Obţineţi un nou cod captcha",instructions_visual:"Introduceți textul:",instructions_audio:"Introduceţi ceea ce auziţi:",help_btn:"Ajutor",play_again:"Redaţi sunetul din nou",cant_hear_this:"Descărcaţi fişierul audio ca MP3",incorrect_try_again:"Incorect. Încercaţi din nou.",image_alt_text:"Imagine de verificare reCAPTCHA",privacy_and_terms:"Confidenţialitate şi termeni"},qa={visual_challenge:"收到一个视频邀请",audio_challenge:"换一组音频验证码",refresh_btn:"换一组验证码",instructions_visual:"输入文字：",instructions_audio:"请键入您听到的内容：",help_btn:"帮助",play_again:"重新播放",cant_hear_this:"以 MP3 格式下载声音",incorrect_try_again:"不正确，请重试。",image_alt_text:"reCAPTCHA 验证图片",privacy_and_terms:"隐私权和使用条款"},ra={en:w,af:{visual_challenge:"Kry 'n visuele verifiëring",audio_challenge:"Kry 'n klankverifiëring",refresh_btn:"Kry 'n nuwe verifiëring",instructions_visual:"",instructions_audio:"Tik wat jy hoor:",help_btn:"Hulp",play_again:"Speel geluid weer",cant_hear_this:"Laai die klank af as MP3",incorrect_try_again:"Verkeerd. Probeer weer.",image_alt_text:"reCAPTCHA-uitdagingprent",privacy_and_terms:"Privaatheid en bepalings"},am:{visual_challenge:"የእይታ ተጋጣሚ አግኝ",audio_challenge:"ሌላ አዲስ የድምጽ ጥያቄ ይቅረብ",refresh_btn:"ሌላ አዲስ ጥያቄ ይቅረብ",instructions_visual:"",instructions_audio:"የምትሰማውን ተይብ፡-",help_btn:"እገዛ",play_again:"ድምጹን እንደገና አጫውት",cant_hear_this:"ድምጹን በMP3 ቅርጽ አውርድ",incorrect_try_again:"ትክክል አይደለም። እንደገና ሞክር።",image_alt_text:"reCAPTCHA ምስል ግጠም",privacy_and_terms:"ግላዊነት እና ውል"},ar:ia,"ar-EG":ia,bg:{visual_challenge:"Получаване на визуална проверка",audio_challenge:"Зареждане на аудиотест",refresh_btn:"Зареждане на нов тест",instructions_visual:"Въведете текста:",instructions_audio:"Въведете чутото:",help_btn:"Помощ",play_again:"Повторно пускане на звука",cant_hear_this:"Изтегляне на звука във формат MP3",incorrect_try_again:"Неправилно. Опитайте отново.",image_alt_text:"Изображение на проверката с reCAPTCHA",privacy_and_terms:"Поверителност и Общи условия"},bn:{visual_challenge:"একটি দৃশ্যমান প্রতিদ্বন্দ্বিতা পান",audio_challenge:"একটি অডিও প্রতিদ্বন্দ্বিতা  পান",refresh_btn:"একটি নতুন প্রতিদ্বন্দ্বিতা  পান",instructions_visual:"",instructions_audio:"আপনি যা শুনছেন তা লিখুন:",help_btn:"সহায়তা",play_again:"আবার সাউন্ড প্লে করুন",cant_hear_this:"MP3 রূপে শব্দ ডাউনলোড করুন",incorrect_try_again:"বেঠিক৷ আবার চেষ্টা করুন৷",image_alt_text:"reCAPTCHA চ্যালেঞ্জ চিত্র",privacy_and_terms:"গোপনীয়তা ও শর্তাবলী"},ca:{visual_challenge:"Obtén un repte visual",audio_challenge:"Obteniu una pista sonora",refresh_btn:"Obteniu una pista nova",instructions_visual:"Escriviu el text:",instructions_audio:"Escriviu el que escolteu:",help_btn:"Ajuda",play_again:"Torna a reproduir el so",cant_hear_this:"Baixa el so com a MP3",incorrect_try_again:"No és correcte. Torna-ho a provar.",image_alt_text:"Imatge del repte de reCAPTCHA",privacy_and_terms:"Privadesa i condicions"},cs:{visual_challenge:"Zobrazit vizuální podobu výrazu",audio_challenge:"Přehrát zvukovou podobu výrazu",refresh_btn:"Zobrazit nový výraz",instructions_visual:"Zadejte text:",instructions_audio:"Napište, co jste slyšeli:",help_btn:"Nápověda",play_again:"Znovu přehrát zvuk",cant_hear_this:"Stáhnout zvuk ve formátu MP3",incorrect_try_again:"Špatně. Zkuste to znovu.",image_alt_text:"Obrázek reCAPTCHA",privacy_and_terms:"Ochrana soukromí a smluvní podmínky"},da:{visual_challenge:"Hent en visuel udfordring",audio_challenge:"Hent en lydudfordring",refresh_btn:"Hent en ny udfordring",instructions_visual:"Indtast teksten:",instructions_audio:"Indtast det, du hører:",help_btn:"Hjælp",play_again:"Afspil lyden igen",cant_hear_this:"Download lyd som MP3",incorrect_try_again:"Forkert. Prøv igen.",image_alt_text:"reCAPTCHA-udfordringsbillede",privacy_and_terms:"Privatliv og vilkår"},de:{visual_challenge:"Captcha abrufen",audio_challenge:"Audio-Captcha abrufen",refresh_btn:"Neues Captcha abrufen",instructions_visual:"Geben Sie den angezeigten Text ein:",instructions_audio:"Geben Sie das Gehörte ein:",help_btn:"Hilfe",play_again:"Wort erneut abspielen",cant_hear_this:"Wort als MP3 herunterladen",incorrect_try_again:"Falsch. Bitte versuchen Sie es erneut.",image_alt_text:"reCAPTCHA-Bild",privacy_and_terms:"Datenschutzerklärung & Nutzungsbedingungen"},el:{visual_challenge:"Οπτική πρόκληση",audio_challenge:"Ηχητική πρόκληση",refresh_btn:"Νέα πρόκληση",instructions_visual:"Πληκτρολογήστε το κείμενο:",instructions_audio:"Πληκτρολογήστε ότι ακούτε:",help_btn:"Βοήθεια",play_again:"Αναπαραγωγή ήχου ξανά",cant_hear_this:"Λήψη ήχου ως ΜΡ3",incorrect_try_again:"Λάθος. Δοκιμάστε ξανά.",image_alt_text:"Εικόνα πρόκλησης reCAPTCHA",privacy_and_terms:"Απόρρητο και όροι"},"en-GB":w,"en-US":w,es:ja,"es-419":{visual_challenge:"Enfrentar un desafío visual",audio_challenge:"Enfrentar un desafío de audio",refresh_btn:"Enfrentar un nuevo desafío",instructions_visual:"Escriba el texto:",instructions_audio:"Escribe lo que escuchas:",help_btn:"Ayuda",play_again:"Reproducir sonido de nuevo",cant_hear_this:"Descargar sonido en formato MP3",incorrect_try_again:"Incorrecto. Vuelve a intentarlo.",image_alt_text:"Imagen del desafío de la reCAPTCHA",privacy_and_terms:"Privacidad y condiciones"},"es-ES":ja,et:{visual_challenge:"Kuva kuvapõhine robotilõks",audio_challenge:"Kuva helipõhine robotilõks",refresh_btn:"Kuva uus robotilõks",instructions_visual:"Tippige tekst:",instructions_audio:"Tippige, mida kuulete.",help_btn:"Abi",play_again:"Esita heli uuesti",cant_hear_this:"Laadi heli alla MP3-vormingus",incorrect_try_again:"Vale. Proovige uuesti.",image_alt_text:"reCAPTCHA robotilõksu kujutis",privacy_and_terms:"Privaatsus ja tingimused"},eu:{visual_challenge:"Eskuratu ikusizko erronka",audio_challenge:"Eskuratu audio-erronka",refresh_btn:"Eskuratu erronka berria",instructions_visual:"",instructions_audio:"Idatzi entzuten duzuna:",help_btn:"Laguntza",play_again:"Erreproduzitu soinua berriro",cant_hear_this:"Deskargatu soinua MP3 gisa",incorrect_try_again:"Ez da zuzena. Saiatu berriro.",image_alt_text:"reCAPTCHA erronkaren irudia",privacy_and_terms:"Pribatutasuna eta baldintzak"},fa:{visual_challenge:"دریافت یک معمای دیداری",audio_challenge:"دریافت یک معمای صوتی",refresh_btn:"دریافت یک معمای جدید",instructions_visual:"",instructions_audio:"آنچه را که می‌شنوید تایپ کنید:",help_btn:"راهنمایی",play_again:"پخش مجدد صدا",cant_hear_this:"دانلود صدا به صورت MP3",incorrect_try_again:"نادرست. دوباره امتحان کنید.",image_alt_text:"تصویر چالشی reCAPTCHA",privacy_and_terms:"حریم خصوصی و شرایط"},fi:{visual_challenge:"Kuvavahvistus",audio_challenge:"Äänivahvistus",refresh_btn:"Uusi kuva",instructions_visual:"Kirjoita teksti:",instructions_audio:"Kirjoita kuulemasi:",help_btn:"Ohje",play_again:"Toista ääni uudelleen",cant_hear_this:"Lataa ääni MP3-tiedostona",incorrect_try_again:"Väärin. Yritä uudelleen.",image_alt_text:"reCAPTCHA-kuva",privacy_and_terms:"Tietosuoja ja käyttöehdot"},fil:ka,fr:la,"fr-CA":{visual_challenge:"Obtenir un test visuel",audio_challenge:"Obtenir un test audio",refresh_btn:"Obtenir un nouveau test",instructions_visual:"Saisissez le texte :",instructions_audio:"Tapez ce que vous entendez :",help_btn:"Aide",play_again:"Jouer le son de nouveau",cant_hear_this:"Télécharger le son en format MP3",incorrect_try_again:"Erreur, essayez à nouveau",image_alt_text:"Image reCAPTCHA",privacy_and_terms:"Confidentialité et conditions d'utilisation"},"fr-FR":la,gl:{visual_challenge:"Obter unha proba visual",audio_challenge:"Obter unha proba de audio",refresh_btn:"Obter unha proba nova",instructions_visual:"",instructions_audio:"Escribe o que escoitas:",help_btn:"Axuda",play_again:"Reproducir o son de novo",cant_hear_this:"Descargar son como MP3",incorrect_try_again:"Incorrecto. Téntao de novo.",image_alt_text:"Imaxe de proba de reCAPTCHA",privacy_and_terms:"Privacidade e condicións"},gu:{visual_challenge:"એક દૃશ્યાત્મક પડકાર મેળવો",audio_challenge:"એક ઑડિઓ પડકાર મેળવો",refresh_btn:"એક નવો પડકાર મેળવો",instructions_visual:"",instructions_audio:"તમે જે સાંભળો છો તે લખો:",help_btn:"સહાય",play_again:"ધ્વનિ ફરીથી ચલાવો",cant_hear_this:"MP3 તરીકે ધ્વનિને ડાઉનલોડ કરો",incorrect_try_again:"ખોટું. ફરી પ્રયાસ કરો.",image_alt_text:"reCAPTCHA પડકાર છબી",privacy_and_terms:"ગોપનીયતા અને શરતો"},hi:{visual_challenge:"कोई विजुअल चुनौती लें",audio_challenge:"कोई ऑडियो चुनौती लें",refresh_btn:"कोई नई चुनौती लें",instructions_visual:"टेक्स्ट टाइप करें:",instructions_audio:"जो आप सुन रहे हैं उसे लिखें:",help_btn:"सहायता",play_again:"ध्‍वनि पुन: चलाएं",cant_hear_this:"ध्‍वनि को MP3 के रूप में डाउनलोड करें",incorrect_try_again:"गलत. पुन: प्रयास करें.",image_alt_text:"reCAPTCHA चुनौती चित्र",privacy_and_terms:"गोपनीयता और शर्तें"},hr:{visual_challenge:"Dohvati vizualni upit",audio_challenge:"Dohvati zvučni upit",refresh_btn:"Dohvati novi upit",instructions_visual:"Unesite tekst:",instructions_audio:"Upišite što čujete:",help_btn:"Pomoć",play_again:"Ponovi zvuk",cant_hear_this:"Preuzmi zvuk u MP3 formatu",incorrect_try_again:"Nije točno. Pokušajte ponovno.",image_alt_text:"Slikovni izazov reCAPTCHA",privacy_and_terms:"Privatnost i odredbe"},hu:{visual_challenge:"Vizuális kihívás kérése",audio_challenge:"Hangkihívás kérése",refresh_btn:"Új kihívás kérése",instructions_visual:"Írja be a szöveget:",instructions_audio:"Írja le, amit hall:",help_btn:"Súgó",play_again:"Hang ismételt lejátszása",cant_hear_this:"Hang letöltése MP3 formátumban",incorrect_try_again:"Hibás. Próbálkozzon újra.",image_alt_text:"reCAPTCHA ellenőrző kép",privacy_and_terms:"Adatvédelem és Szerződési Feltételek"},hy:{visual_challenge:"Ստանալ տեսողական խնդիր",audio_challenge:"Ստանալ ձայնային խնդիր",refresh_btn:"Ստանալ նոր խնդիր",instructions_visual:"Մուտքագրեք տեքստը՝",instructions_audio:"Մուտքագրեք այն, ինչ լսում եք՝",help_btn:"Օգնություն",play_again:"Նվագարկել ձայնը կրկին",cant_hear_this:"Բեռնել ձայնը որպես MP3",incorrect_try_again:"Սխալ է: Փորձեք կրկին:",image_alt_text:"reCAPTCHA պատկերով խնդիր",privacy_and_terms:"Գաղտնիության & պայմաններ"},id:ma,is:{visual_challenge:"Fá aðgangspróf sem mynd",audio_challenge:"Fá aðgangspróf sem hljóðskrá",refresh_btn:"Fá nýtt aðgangspróf",instructions_visual:"",instructions_audio:"Sláðu inn það sem þú heyrir:",help_btn:"Hjálp",play_again:"Spila hljóð aftur",cant_hear_this:"Sækja hljóð sem MP3",incorrect_try_again:"Rangt. Reyndu aftur.",image_alt_text:"mynd reCAPTCHA aðgangsprófs",privacy_and_terms:"Persónuvernd og skilmálar"},it:{visual_challenge:"Verifica visiva",audio_challenge:"Verifica audio",refresh_btn:"Nuova verifica",instructions_visual:"Digita il testo:",instructions_audio:"Digita ciò che senti:",help_btn:"Guida",play_again:"Riproduci di nuovo audio",cant_hear_this:"Scarica audio in MP3",incorrect_try_again:"Sbagliato. Riprova.",image_alt_text:"Immagine di verifica reCAPTCHA",privacy_and_terms:"Privacy e Termini"},iw:na,ja:{visual_challenge:"画像で確認します",audio_challenge:"音声で確認します",refresh_btn:"別の単語でやり直します",instructions_visual:"テキストを入力:",instructions_audio:"聞こえた単語を入力します:",help_btn:"ヘルプ",play_again:"もう一度聞く",cant_hear_this:"MP3 で音声をダウンロード",incorrect_try_again:"正しくありません。もう一度やり直してください。",image_alt_text:"reCAPTCHA 確認用画像",privacy_and_terms:"プライバシーと利用規約"},kn:{visual_challenge:"ದೃಶ್ಯ ಸವಾಲೊಂದನ್ನು ಸ್ವೀಕರಿಸಿ",audio_challenge:"ಆಡಿಯೋ ಸವಾಲೊಂದನ್ನು ಸ್ವೀಕರಿಸಿ",refresh_btn:"ಹೊಸ ಸವಾಲೊಂದನ್ನು ಪಡೆಯಿರಿ",instructions_visual:"",instructions_audio:"ನಿಮಗೆ ಕೇಳಿಸುವುದನ್ನು ಟೈಪ್‌ ಮಾಡಿ:",help_btn:"ಸಹಾಯ",play_again:"ಧ್ವನಿಯನ್ನು ಮತ್ತೆ ಪ್ಲೇ ಮಾಡಿ",cant_hear_this:"ಧ್ವನಿಯನ್ನು MP3 ರೂಪದಲ್ಲಿ ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ",incorrect_try_again:"ತಪ್ಪಾಗಿದೆ. ಮತ್ತೊಮ್ಮೆ ಪ್ರಯತ್ನಿಸಿ.",image_alt_text:"reCAPTCHA ಸವಾಲು ಚಿತ್ರ",privacy_and_terms:"ಗೌಪ್ಯತೆ ಮತ್ತು ನಿಯಮಗಳು"},ko:{visual_challenge:"그림으로 보안문자 받기",audio_challenge:"음성으로 보안문자 받기",refresh_btn:"보안문자 새로 받기",instructions_visual:"텍스트 입력:",instructions_audio:"음성 보안문자 입력:",help_btn:"도움말",play_again:"음성 다시 듣기",cant_hear_this:"음성을 MP3로 다운로드",incorrect_try_again:"틀렸습니다. 다시 시도해 주세요.",image_alt_text:"reCAPTCHA 보안문자 이미지",privacy_and_terms:"개인정보 보호 및 약관"},ln:la,lt:{visual_challenge:"Gauti vaizdinį atpažinimo testą",audio_challenge:"Gauti garso atpažinimo testą",refresh_btn:"Gauti naują atpažinimo testą",instructions_visual:"Įveskite tekstą:",instructions_audio:"Įveskite tai, ką girdite:",help_btn:"Pagalba",play_again:"Dar kartą paleisti garsą",cant_hear_this:"Atsisiųsti garsą kaip MP3",incorrect_try_again:"Neteisingai. Bandykite dar kartą.",image_alt_text:"Testo „reCAPTCHA“ vaizdas",privacy_and_terms:"Privatumas ir sąlygos"},lv:{visual_challenge:"Saņemt vizuālu izaicinājumu",audio_challenge:"Saņemt audio izaicinājumu",refresh_btn:"Saņemt jaunu izaicinājumu",instructions_visual:"Ievadiet tekstu:",instructions_audio:"Ierakstiet dzirdamo:",help_btn:"Palīdzība",play_again:"Vēlreiz atskaņot skaņu",cant_hear_this:"Lejupielādēt skaņu MP3 formātā",incorrect_try_again:"Nepareizi. Mēģiniet vēlreiz.",image_alt_text:"reCAPTCHA izaicinājuma attēls",privacy_and_terms:"Konfidencialitāte un noteikumi"},ml:{visual_challenge:"ഒരു ദൃശ്യ ചലഞ്ച് നേടുക",audio_challenge:"ഒരു ഓഡിയോ ചലഞ്ച് നേടുക",refresh_btn:"ഒരു പുതിയ ചലഞ്ച് നേടുക",instructions_visual:"",instructions_audio:"കേൾക്കുന്നത് ടൈപ്പ് ചെയ്യൂ:",help_btn:"സഹായം",play_again:"ശബ്‌ദം വീണ്ടും പ്ലേ ചെയ്യുക",cant_hear_this:"ശബ്‌ദം MP3 ആയി ഡൗൺലോഡ് ചെയ്യുക",incorrect_try_again:"തെറ്റാണ്. വീണ്ടും ശ്രമിക്കുക.",image_alt_text:"reCAPTCHA ചലഞ്ച് ഇമേജ്",privacy_and_terms:"സ്വകാര്യതയും നിബന്ധനകളും"},mr:{visual_challenge:"दृश्‍यमान आव्हान प्राप्त करा",audio_challenge:"ऑडीओ आव्हान प्राप्त करा",refresh_btn:"एक नवीन आव्हान प्राप्त करा",instructions_visual:"",instructions_audio:"आपल्याला जे ऐकू येईल ते टाइप करा:",help_btn:"मदत",play_again:"ध्‍वनी पुन्हा प्‍ले करा",cant_hear_this:"MP3 रुपात ध्‍वनी डाउनलोड करा",incorrect_try_again:"अयोग्‍य. पुन्‍हा प्रयत्‍न करा.",image_alt_text:"reCAPTCHA आव्‍हान प्रतिमा",privacy_and_terms:"गोपनीयता आणि अटी"},ms:{visual_challenge:"Dapatkan cabaran visual",audio_challenge:"Dapatkan cabaran audio",refresh_btn:"Dapatkan cabaran baru",instructions_visual:"Taipkan teksnya:",instructions_audio:"Taip apa yang didengari:",help_btn:"Bantuan",play_again:"Mainkan bunyi sekali lagi",cant_hear_this:"Muat turun bunyi sebagai MP3",incorrect_try_again:"Tidak betul. Cuba lagi.",image_alt_text:"Imej cabaran reCAPTCHA",privacy_and_terms:"Privasi & Syarat"},nl:{visual_challenge:"Een visuele uitdaging proberen",audio_challenge:"Een audio-uitdaging proberen",refresh_btn:"Een nieuwe uitdaging proberen",instructions_visual:"Typ de tekst:",instructions_audio:"Typ wat u hoort:",help_btn:"Help",play_again:"Geluid opnieuw afspelen",cant_hear_this:"Geluid downloaden als MP3",incorrect_try_again:"Onjuist. Probeer het opnieuw.",image_alt_text:"reCAPTCHA-uitdagingsafbeelding",privacy_and_terms:"Privacy en voorwaarden"},no:{visual_challenge:"Få en bildeutfordring",audio_challenge:"Få en lydutfordring",refresh_btn:"Få en ny utfordring",instructions_visual:"Skriv inn teksten:",instructions_audio:"Skriv inn det du hører:",help_btn:"Hjelp",play_again:"Spill av lyd på nytt",cant_hear_this:"Last ned lyd som MP3",incorrect_try_again:"Feil. Prøv på nytt.",image_alt_text:"reCAPTCHA-utfordringsbilde",privacy_and_terms:"Personvern og vilkår"},pl:{visual_challenge:"Pokaż podpowiedź wizualną",audio_challenge:"Odtwórz podpowiedź dźwiękową",refresh_btn:"Nowa podpowiedź",instructions_visual:"Przepisz tekst:",instructions_audio:"Wpisz usłyszane słowa:",help_btn:"Pomoc",play_again:"Odtwórz dźwięk ponownie",cant_hear_this:"Pobierz dźwięk jako plik MP3",incorrect_try_again:"Nieprawidłowo. Spróbuj ponownie.",image_alt_text:"Zadanie obrazkowe reCAPTCHA",privacy_and_terms:"Prywatność i warunki"},pt:oa,"pt-BR":oa,"pt-PT":{visual_challenge:"Obter um desafio visual",audio_challenge:"Obter um desafio de áudio",refresh_btn:"Obter um novo desafio",instructions_visual:"Introduza o texto:",instructions_audio:"Escreva o que ouvir:",help_btn:"Ajuda",play_again:"Reproduzir som novamente",cant_hear_this:"Transferir som como MP3",incorrect_try_again:"Incorreto. Tente novamente.",image_alt_text:"Imagem de teste reCAPTCHA",privacy_and_terms:"Privacidade e Termos de Utilização"},ro:pa,ru:{visual_challenge:"Визуальная проверка",audio_challenge:"Звуковая проверка",refresh_btn:"Обновить",instructions_visual:"Введите текст:",instructions_audio:"Введите то, что слышите:",help_btn:"Справка",play_again:"Прослушать еще раз",cant_hear_this:"Загрузить MP3-файл",incorrect_try_again:"Неправильно. Повторите попытку.",image_alt_text:"Проверка по слову reCAPTCHA",privacy_and_terms:"Правила и принципы"},sk:{visual_challenge:"Zobraziť vizuálnu podobu",audio_challenge:"Prehrať zvukovú podobu",refresh_btn:"Zobraziť nový výraz",instructions_visual:"Zadajte text:",instructions_audio:"Zadajte, čo počujete:",help_btn:"Pomocník",play_again:"Znova prehrať zvuk",cant_hear_this:"Prevziať zvuk v podobe súboru MP3",incorrect_try_again:"Nesprávne. Skúste to znova.",image_alt_text:"Obrázok zadania reCAPTCHA",privacy_and_terms:"Ochrana osobných údajov a Zmluvné podmienky"},sl:{visual_challenge:"Vizualni preskus",audio_challenge:"Zvočni preskus",refresh_btn:"Nov preskus",instructions_visual:"Vnesite besedilo:",instructions_audio:"Natipkajte, kaj slišite:",help_btn:"Pomoč",play_again:"Znova predvajaj zvok",cant_hear_this:"Prenesi zvok kot MP3",incorrect_try_again:"Napačno. Poskusite znova.",image_alt_text:"Slika izziva reCAPTCHA",privacy_and_terms:"Zasebnost in pogoji"},sr:{visual_challenge:"Примите визуелни упит",audio_challenge:"Примите аудио упит",refresh_btn:"Примите нови упит",instructions_visual:"Унесите текст:",instructions_audio:"Откуцајте оно што чујете:",help_btn:"Помоћ",play_again:"Поново пусти звук",cant_hear_this:"Преузми звук као MP3 снимак",incorrect_try_again:"Нетачно. Покушајте поново.",image_alt_text:"Слика reCAPTCHA провере",privacy_and_terms:"Приватност и услови"},sv:{visual_challenge:"Hämta captcha i bildformat",audio_challenge:"Hämta captcha i ljudformat",refresh_btn:"Hämta ny captcha",instructions_visual:"Skriv texten:",instructions_audio:"Skriv det du hör:",help_btn:"Hjälp",play_again:"Spela upp ljudet igen",cant_hear_this:"Hämta ljud som MP3",incorrect_try_again:"Fel. Försök igen.",image_alt_text:"reCAPTCHA-bild",privacy_and_terms:"Sekretess och villkor"},sw:{visual_challenge:"Pata herufi za kusoma",audio_challenge:"Pata herufi za kusikiliza",refresh_btn:"Pata herufi mpya",instructions_visual:"",instructions_audio:"Charaza unachosikia:",help_btn:"Usaidizi",play_again:"Cheza sauti tena",cant_hear_this:"Pakua sauti kama MP3",incorrect_try_again:"Sio sahihi. Jaribu tena.",image_alt_text:"picha ya changamoto ya reCAPTCHA",privacy_and_terms:"Faragha & Masharti"},ta:{visual_challenge:"பார்வை சேலஞ்சைப் பெறுக",audio_challenge:"ஆடியோ சேலஞ்சைப் பெறுக",refresh_btn:"புதிய சேலஞ்சைப் பெறுக",instructions_visual:"",instructions_audio:"கேட்பதை டைப் செய்க:",help_btn:"உதவி",play_again:"ஒலியை மீண்டும் இயக்கு",cant_hear_this:"ஒலியை MP3 ஆக பதிவிறக்குக",incorrect_try_again:"தவறானது. மீண்டும் முயலவும்.",image_alt_text:"reCAPTCHA சேலஞ்ச் படம்",privacy_and_terms:"தனியுரிமை & விதிமுறைகள்"},te:{visual_challenge:"ఒక దృశ్యమాన సవాలును స్వీకరించండి",audio_challenge:"ఒక ఆడియో సవాలును స్వీకరించండి",refresh_btn:"క్రొత్త సవాలును స్వీకరించండి",instructions_visual:"",instructions_audio:"మీరు విన్నది టైప్ చేయండి:",help_btn:"సహాయం",play_again:"ధ్వనిని మళ్లీ ప్లే చేయి",cant_hear_this:"ధ్వనిని MP3 వలె డౌన్‌లోడ్ చేయి",incorrect_try_again:"తప్పు. మళ్లీ ప్రయత్నించండి.",image_alt_text:"reCAPTCHA సవాలు చిత్రం",privacy_and_terms:"గోప్యత & నిబంధనలు"},th:{visual_challenge:"รับความท้าทายด้านภาพ",audio_challenge:"รับความท้าทายด้านเสียง",refresh_btn:"รับความท้าทายใหม่",instructions_visual:"พิมพ์ข้อความนี้:",instructions_audio:"พิมพ์สิ่งที่คุณได้ยิน:",help_btn:"ความช่วยเหลือ",play_again:"เล่นเสียงอีกครั้ง",cant_hear_this:"ดาวโหลดเสียงเป็น MP3",incorrect_try_again:"ไม่ถูกต้อง ลองอีกครั้ง",image_alt_text:"รหัสภาพ reCAPTCHA",privacy_and_terms:"นโยบายส่วนบุคคลและข้อกำหนด"},tr:{visual_challenge:"Görsel sorgu al",audio_challenge:"Sesli sorgu al",refresh_btn:"Yeniden yükle",instructions_visual:"Metni yazın:",instructions_audio:"Duyduğunuzu yazın:",help_btn:"Yardım",play_again:"Sesi tekrar çal",cant_hear_this:"Sesi MP3 olarak indir",incorrect_try_again:"Yanlış. Tekrar deneyin.",image_alt_text:"reCAPTCHA sorusu resmi",privacy_and_terms:"Gizlilik ve Şartlar"},uk:{visual_challenge:"Отримати візуальний текст",audio_challenge:"Отримати аудіозапис",refresh_btn:"Оновити текст",instructions_visual:"Введіть текст:",instructions_audio:"Введіть почуте:",help_btn:"Довідка",play_again:"Відтворити запис ще раз",cant_hear_this:"Завантажити запис як MP3",incorrect_try_again:"Неправильно. Спробуйте ще раз.",image_alt_text:"Зображення завдання reCAPTCHA",privacy_and_terms:"Конфіденційність і умови"},ur:{visual_challenge:"ایک مرئی چیلنج حاصل کریں",audio_challenge:"ایک آڈیو چیلنج حاصل کریں",refresh_btn:"ایک نیا چیلنج حاصل کریں",instructions_visual:"",instructions_audio:"جو سنائی دیتا ہے وہ ٹائپ کریں:",help_btn:"مدد",play_again:"آواز دوبارہ چلائیں",cant_hear_this:"آواز کو MP3 کے بطور ڈاؤن لوڈ کریں",incorrect_try_again:"غلط۔ دوبارہ کوشش کریں۔",image_alt_text:"reCAPTCHA چیلنج والی شبیہ",privacy_and_terms:"رازداری و شرائط"},vi:{visual_challenge:"Nhận thử thách hình ảnh",audio_challenge:"Nhận thử thách âm thanh",refresh_btn:"Nhận thử thách mới",instructions_visual:"Nhập văn bản:",instructions_audio:"Nhập nội dung bạn nghe thấy:",help_btn:"Trợ giúp",play_again:"Phát lại âm thanh",cant_hear_this:"Tải âm thanh xuống dưới dạng MP3",incorrect_try_again:"Không chính xác. Hãy thử lại.",image_alt_text:"Hình xác thực reCAPTCHA",privacy_and_terms:"Bảo mật và điều khoản"},"zh-CN":qa,"zh-HK":{visual_challenge:"回答圖像驗證問題",audio_challenge:"取得語音驗證問題",refresh_btn:"換一個驗證問題",instructions_visual:"輸入文字：",instructions_audio:"鍵入您所聽到的：",help_btn:"說明",play_again:"再次播放聲音",cant_hear_this:"將聲音下載為 MP3",incorrect_try_again:"不正確，再試一次。",image_alt_text:"reCAPTCHA 驗證文字圖片",privacy_and_terms:"私隱權與條款"},"zh-TW":{visual_challenge:"取得圖片驗證問題",audio_challenge:"取得語音驗證問題",refresh_btn:"取得新的驗證問題",instructions_visual:"請輸入圖片中的文字：",instructions_audio:"請輸入語音內容：",help_btn:"說明",play_again:"再次播放",cant_hear_this:"以 MP3 格式下載聲音",incorrect_try_again:"驗證碼有誤，請再試一次。",image_alt_text:"reCAPTCHA 驗證文字圖片",privacy_and_terms:"隱私權與條款"},zu:{visual_challenge:"Thola inselelo ebonakalayo",audio_challenge:"Thola inselelo yokulalelwayo",refresh_btn:"Thola inselelo entsha",instructions_visual:"",instructions_audio:"Bhala okuzwayo:",help_btn:"Usizo",play_again:"Phinda udlale okulalelwayo futhi",cant_hear_this:"Layisha umsindo njenge-MP3",incorrect_try_again:"Akulungile. Zama futhi.",image_alt_text:"umfanekiso oyinselelo we-reCAPTCHA",privacy_and_terms:"Okwangasese kanye nemigomo"},tl:ka,he:na,"in":ma,mo:pa,zh:qa};
var x=function(a){if(Error.captureStackTrace)Error.captureStackTrace(this,x);else{var b=Error().stack;b&&(this.stack=b)}a&&(this.message=String(a))};u(x,Error);x.prototype.name="CustomError";var sa;var ta=function(a,b){for(var c=a.split("%s"),d="",e=Array.prototype.slice.call(arguments,1);e.length&&1<c.length;)d+=c.shift()+e.shift();return d+c.join("%s")},Ba=function(a){if(!ua.test(a))return a;-1!=a.indexOf("&")&&(a=a.replace(va,"&amp;"));-1!=a.indexOf("<")&&(a=a.replace(wa,"&lt;"));-1!=a.indexOf(">")&&(a=a.replace(xa,"&gt;"));-1!=a.indexOf('"')&&(a=a.replace(ya,"&quot;"));-1!=a.indexOf("'")&&(a=a.replace(za,"&#39;"));-1!=a.indexOf("\x00")&&(a=a.replace(Aa,"&#0;"));return a},va=/&/g,wa=/</g,xa=/>/g,ya=/"/g,za=/'/g,Aa=/\x00/g,ua=/[\x00&<>"']/,Ca=function(a,b){return a<b?-1:a>b?1:0},Da=function(a){return String(a).replace(/\-([a-z])/g,function(a,c){return c.toUpperCase()})},Ea=function(a){var b=p(void 0)?"undefined".replace(/([-()\[\]{}+?*.$\^|,:#<!\\])/g,"\\$1").replace(/\x08/g,"\\x08"):"\\s";return a.replace(new RegExp("(^"+(b?"|["+b+"]+":"")+")([a-z])","g"),function(a,b,e){return b+e.toUpperCase()})};var Fa=function(a,b){b.unshift(a);x.call(this,ta.apply(null,b));b.shift()};u(Fa,x);Fa.prototype.name="AssertionError";var Ga=function(a,b,c,d){var e="Assertion failed";if(c)var e=e+(": "+c),g=d;else a&&(e+=": "+a,g=b);throw new Fa(""+e,g||[])},y=function(a,b,c){a||Ga("",null,b,Array.prototype.slice.call(arguments,2))},Ha=function(a,b){throw new Fa("Failure"+(a?": "+a:""),Array.prototype.slice.call(arguments,1))},Ia=function(a,b,c){p(a)||Ga("Expected string but got %s: %s.",[l(a),a],b,Array.prototype.slice.call(arguments,2));return a},Ja=function(a,b,c){q(a)||Ga("Expected function but got %s: %s.",[l(a),a],b,Array.prototype.slice.call(arguments,2))};var z=Array.prototype,Ka=z.indexOf?function(a,b,c){y(null!=a.length);return z.indexOf.call(a,b,c)}:function(a,b,c){c=null==c?0:0>c?Math.max(0,a.length+c):c;if(p(a))return p(b)&&1==b.length?a.indexOf(b,c):-1;for(;c<a.length;c++)if(c in a&&a[c]===b)return c;return-1},La=z.forEach?function(a,b,c){y(null!=a.length);z.forEach.call(a,b,c)}:function(a,b,c){for(var d=a.length,e=p(a)?a.split(""):a,g=0;g<d;g++)g in e&&b.call(c,e[g],g,a)},Ma=z.map?function(a,b,c){y(null!=a.length);return z.map.call(a,b,c)}:function(a,b,c){for(var d=a.length,e=Array(d),g=p(a)?a.split(""):a,f=0;f<d;f++)f in g&&(e[f]=b.call(c,g[f],f,a));return e},Na=z.some?function(a,b,c){y(null!=a.length);return z.some.call(a,b,c)}:function(a,b,c){for(var d=a.length,e=p(a)?a.split(""):a,g=0;g<d;g++)if(g in e&&b.call(c,e[g],g,a))return!0;return!1},Oa=function(a,b){var c=Ka(a,b),d;if(d=0<=c)y(null!=a.length),z.splice.call(a,c,1);return d},Pa=function(a){var b=a.length;if(0<b){for(var c=Array(b),d=0;d<b;d++)c[d]=a[d];return c}return[]},Qa=function(a,b,c){y(null!=a.length);return 2>=arguments.length?z.slice.call(a,b):z.slice.call(a,b,c)};var A;t:{var Ra=k.navigator;if(Ra){var Sa=Ra.userAgent;if(Sa){A=Sa;break t}}A=""}var B=function(a){return-1!=A.indexOf(a)};var Ta=B("Opera")||B("OPR"),C=B("Trident")||B("MSIE"),E=B("Gecko")&&-1==A.toLowerCase().indexOf("webkit")&&!(B("Trident")||B("MSIE")),F=-1!=A.toLowerCase().indexOf("webkit"),Ua=function(){var a=k.document;return a?a.documentMode:void 0},Va=function(){var a="",b;if(Ta&&k.opera)return a=k.opera.version,q(a)?a():a;E?b=/rv\:([^\);]+)(\)|;)/:C?b=/\b(?:MSIE|rv)[: ]([^\);]+)(\)|;)/:F&&(b=/WebKit\/(\S+)/);b&&(a=(a=b.exec(A))?a[1]:"");return C&&(b=Ua(),b>parseFloat(a))?String(b):a}(),Wa={},G=function(a){var b;if(!(b=Wa[a])){b=0;for(var c=String(Va).replace(/^[\s\xa0]+|[\s\xa0]+$/g,"").split("."),d=String(a).replace(/^[\s\xa0]+|[\s\xa0]+$/g,"").split("."),e=Math.max(c.length,d.length),g=0;0==b&&g<e;g++){var f=c[g]||"",m=d[g]||"",$=RegExp("(\\d*)(\\D*)","g"),J=RegExp("(\\d*)(\\D*)","g");do{var D=$.exec(f)||["","",""],aa=J.exec(m)||["","",""];if(0==D[0].length&&0==aa[0].length)break;b=Ca(0==D[1].length?0:parseInt(D[1],10),0==aa[1].length?0:parseInt(aa[1],10))||Ca(0==D[2].length,0==aa[2].length)||Ca(D[2],aa[2])}while(0==b)}b=Wa[a]=0<=b}return b},Xa=k.document,Ya=Xa&&C?Ua()||("CSS1Compat"==Xa.compatMode?parseInt(Va,10):5):void 0;var Za=function(a){if(8192>a.length)return String.fromCharCode.apply(null,a);for(var b="",c=0;c<a.length;c+=8192)var d=Qa(a,c,c+8192),b=b+String.fromCharCode.apply(null,d);return b},$a=function(a){return Ma(a,function(a){a=a.toString(16);return 1<a.length?a:"0"+a}).join("")};var ab=null,bb=null,cb=function(a){if(!ab){ab={};bb={};for(var b=0;65>b;b++)ab[b]="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".charAt(b),bb[ab[b]]=b}for(var b=bb,c=[],d=0;d<a.length;){var e=b[a.charAt(d++)],g=d<a.length?b[a.charAt(d)]:0;++d;var f=d<a.length?b[a.charAt(d)]:64;++d;var m=d<a.length?b[a.charAt(d)]:64;++d;if(null==e||null==g||null==f||null==m)throw Error();c.push(e<<2|g>>4);64!=f&&(c.push(g<<4&240|f>>2),64!=m&&c.push(f<<6&192|m))}return c};var H=function(){};H.prototype.disposed_=!1;H.prototype.dispose=function(){this.disposed_||(this.disposed_=!0,this.disposeInternal())};var db=function(a,b){a.onDisposeCallbacks_||(a.onDisposeCallbacks_=[]);a.onDisposeCallbacks_.push(b)};H.prototype.disposeInternal=function(){if(this.onDisposeCallbacks_)for(;this.onDisposeCallbacks_.length;)this.onDisposeCallbacks_.shift()()};var eb=function(a){a&&"function"==typeof a.dispose&&a.dispose()};var fb=function(a,b){for(var c in a)b.call(void 0,a[c],c,a)},gb=function(a){var b=[],c=0,d;for(d in a)b[c++]=d;return b},hb=function(a){for(var b in a)return!1;return!0},jb=function(){var a=ib()?k.google_ad:null,b={},c;for(c in a)b[c]=a[c];return b},kb="constructor hasOwnProperty isPrototypeOf propertyIsEnumerable toLocaleString toString valueOf".split(" "),lb=function(a,b){for(var c,d,e=1;e<arguments.length;e++){d=arguments[e];for(c in d)a[c]=d[c];for(var g=0;g<kb.length;g++)c=kb[g],Object.prototype.hasOwnProperty.call(d,c)&&(a[c]=d[c])}},mb=function(a){var b=arguments.length;if(1==b&&n(arguments[0]))return mb.apply(null,arguments[0]);for(var c={},d=0;d<b;d++)c[arguments[d]]=!0;return c};var nb=!C||C&&9<=Ya;!E&&!C||C&&C&&9<=Ya||E&&G("1.9.1");C&&G("9");var ob=function(a,b){var c;c=a.className;c=p(c)&&c.match(/\S+/g)||[];for(var d=Qa(arguments,1),e=c.length+d.length,g=c,f=0;f<d.length;f++)0<=Ka(g,d[f])||g.push(d[f]);a.className=c.join(" ");return c.length==e};var rb=function(a){return a?new pb(qb(a)):sa||(sa=new pb)},sb=function(a,b){return p(b)?a.getElementById(b):b},ub=function(a,b){fb(b,function(b,d){"style"==d?a.style.cssText=b:"class"==d?a.className=b:"for"==d?a.htmlFor=b:d in tb?a.setAttribute(tb[d],b):0==d.lastIndexOf("aria-",0)||0==d.lastIndexOf("data-",0)?a.setAttribute(d,b):a[d]=b})},tb={cellpadding:"cellPadding",cellspacing:"cellSpacing",colspan:"colSpan",frameborder:"frameBorder",height:"height",maxlength:"maxLength",role:"role",rowspan:"rowSpan",type:"type",usemap:"useMap",valign:"vAlign",width:"width"},wb=function(a,b,c){return vb(document,arguments)},vb=function(a,b){var c=b[0],d=b[1];if(!nb&&d&&(d.name||d.type)){c=["<",c];d.name&&c.push(' name="',Ba(d.name),'"');if(d.type){c.push(' type="',Ba(d.type),'"');var e={};lb(e,d);delete e.type;d=e}c.push(">");c=c.join("")}c=a.createElement(c);d&&(p(d)?c.className=d:n(d)?ob.apply(null,[c].concat(d)):ub(c,d));2<b.length&&xb(a,c,b);return c},xb=function(a,b,c){function d(c){c&&b.appendChild(p(c)?a.createTextNode(c):c)}for(var e=2;e<c.length;e++){var g=c[e];!ca(g)||r(g)&&0<g.nodeType?d(g):La(yb(g)?Pa(g):g,d)}},zb=function(a){for(var b;b=a.firstChild;)a.removeChild(b)},Ab=function(a){a&&a.parentNode&&a.parentNode.removeChild(a)},qb=function(a){y(a,"Node cannot be null or undefined.");return 9==a.nodeType?a:a.ownerDocument||a.document},yb=function(a){if(a&&"number"==typeof a.length){if(r(a))return"function"==typeof a.item||"string"==typeof a.item;if(q(a))return"function"==typeof a.item}return!1},pb=function(a){this.document_=a||k.document||document};h=pb.prototype;h.getDomHelper=rb;h.getElement=function(a){return sb(this.document_,a)};h.$=pb.prototype.getElement;h.createDom=function(a,b,c){return vb(this.document_,arguments)};h.createElement=function(a){return this.document_.createElement(a)};h.createTextNode=function(a){return this.document_.createTextNode(String(a))};h.appendChild=function(a,b){a.appendChild(b)};var Bb=function(a){k.setTimeout(function(){throw a},0)},Cb,Db=function(){if(k.Promise&&k.Promise.resolve){var a=k.Promise.resolve();return function(b){a.then(function(){try{b()}catch(a){Bb(a)}})}}var b=k.MessageChannel;"undefined"===typeof b&&"undefined"!==typeof window&&window.postMessage&&window.addEventListener&&(b=function(){var a=document.createElement("iframe");a.style.display="none";a.src="";document.documentElement.appendChild(a);var b=a.contentWindow,a=b.document;a.open();a.write("");a.close();var c="callImmediate"+Math.random(),d=b.location.protocol+"//"+b.location.host,a=s(function(a){if(a.origin==d||a.data==c)this.port1.onmessage()},this);b.addEventListener("message",a,!1);this.port1={};this.port2={postMessage:function(){b.postMessage(c,d)}}});if("undefined"!==typeof b){var c=new b,d={},e=d;c.port1.onmessage=function(){d=d.next;var a=d.cb;d.cb=null;a()};return function(a){e.next={cb:a};e=e.next;c.port2.postMessage(0)}}return"undefined"!==typeof document&&"onreadystatechange"in document.createElement("script")?function(a){var b=document.createElement("script");b.onreadystatechange=function(){b.onreadystatechange=null;b.parentNode.removeChild(b);b=null;a();a=null};document.documentElement.appendChild(b)}:function(a){k.setTimeout(a,0)}};var Ib=function(a,b){if(!Eb){var c=Fb;q(k.setImmediate)?k.setImmediate(c):(Cb||(Cb=Db()),Cb(c));Eb=!0}Gb.push(new Hb(a,b))},Eb=!1,Gb=[],Fb=function(){for(;Gb.length;){var a=Gb;Gb=[];for(var b=0;b<a.length;b++){var c=a[b];try{c.fn.call(c.scope)}catch(d){Bb(d)}}}Eb=!1},Hb=function(a,b){this.fn=a;this.scope=b};var Jb=function(a){a.prototype.then=a.prototype.then;a.prototype.$goog_Thenable=!0},Kb=function(a){if(!a)return!1;try{return!!a.$goog_Thenable}catch(b){return!1}};var K=function(a,b){this.state_=0;this.result_=void 0;this.callbackEntries_=this.parent_=null;this.hadUnhandledRejection_=this.executing_=!1;this.stack_=[];Lb(this,Error("created"));this.currentStep_=0;try{var c=this;a.call(b,function(a){I(c,2,a)},function(a){I(c,3,a)})}catch(d){I(this,3,d)}};K.prototype.then=function(a,b,c){null!=a&&Ja(a,"opt_onFulfilled should be a function.");null!=b&&Ja(b,"opt_onRejected should be a function. Did you pass opt_context as the second argument instead of the third?");Lb(this,Error("then"));return Mb(this,q(a)?a:null,q(b)?b:null,c)};Jb(K);K.prototype.cancel=function(a){0==this.state_&&Ib(function(){var b=new Nb(a);Ob(this,b)},this)};var Ob=function(a,b){if(0==a.state_)if(a.parent_){var c=a.parent_;if(c.callbackEntries_){for(var d=0,e=-1,g=0,f;f=c.callbackEntries_[g];g++)if(f=f.child)if(d++,f==a&&(e=g),0<=e&&1<d)break;0<=e&&(0==c.state_&&1==d?Ob(c,b):(d=c.callbackEntries_.splice(e,1)[0],Pb(c),d.onRejected(b)))}}else I(a,3,b)},Rb=function(a,b){a.callbackEntries_&&a.callbackEntries_.length||2!=a.state_&&3!=a.state_||Qb(a);a.callbackEntries_||(a.callbackEntries_=[]);a.callbackEntries_.push(b)},Mb=function(a,b,c,d){var e={child:null,onFulfilled:null,onRejected:null};e.child=new K(function(a,f){e.onFulfilled=b?function(c){try{var e=b.call(d,c);a(e)}catch(J){f(J)}}:a;e.onRejected=c?function(b){try{var e=c.call(d,b);void 0===e&&b instanceof Nb?f(b):a(e)}catch(J){f(J)}}:f});e.child.parent_=a;Rb(a,e);return e.child};K.prototype.unblockAndFulfill_=function(a){y(1==this.state_);this.state_=0;I(this,2,a)};K.prototype.unblockAndReject_=function(a){y(1==this.state_);this.state_=0;I(this,3,a)};var I=function(a,b,c){if(0==a.state_){if(a==c)b=3,c=new TypeError("Promise cannot resolve to itself");else{if(Kb(c)){a.state_=1;c.then(a.unblockAndFulfill_,a.unblockAndReject_,a);return}if(r(c))try{var d=c.then;if(q(d)){Sb(a,c,d);return}}catch(e){b=3,c=e}}a.result_=c;a.state_=b;Qb(a);3!=b||c instanceof Nb||Tb(a,c)}},Sb=function(a,b,c){a.state_=1;var d=!1,e=function(b){d||(d=!0,a.unblockAndFulfill_(b))},g=function(b){d||(d=!0,a.unblockAndReject_(b))};try{c.call(b,e,g)}catch(f){g(f)}},Qb=function(a){a.executing_||(a.executing_=!0,Ib(a.executeCallbacks_,a))};K.prototype.executeCallbacks_=function(){for(;this.callbackEntries_&&this.callbackEntries_.length;){var a=this.callbackEntries_;this.callbackEntries_=[];for(var b=0;b<a.length;b++){this.currentStep_++;var c=a[b],d=this.result_;if(2==this.state_)c.onFulfilled(d);else Pb(this),c.onRejected(d)}}this.executing_=!1};var Lb=function(a,b){if(p(b.stack)){var c=b.stack.split("\n",4)[3],d=b.message,d=d+Array(11-d.length).join(" ");a.stack_.push(d+c)}},Pb=function(a){for(;a&&a.hadUnhandledRejection_;a=a.parent_)a.hadUnhandledRejection_=!1},Tb=function(a,b){a.hadUnhandledRejection_=!0;Ib(function(){if(a.hadUnhandledRejection_){if(b&&p(b.stack)&&a.stack_.length){for(var c=["Promise trace:"],d=a;d;d=d.parent_){for(var e=a.currentStep_;0<=e;e--)c.push(d.stack_[e]);c.push("Value: ["+(3==d.state_?"REJECTED":"FULFILLED")+"] <"+String(d.result_)+">")}b.stack+="\n\n"+c.join("\n")}Ub.call(null,b)}})},Ub=Bb,Nb=function(a){x.call(this,a)};u(Nb,x);Nb.prototype.name="cancel";var L=function(a,b){this.sequence_=[];this.onCancelFunction_=a;this.defaultScope_=b||null;this.hadError_=this.fired_=!1;this.result_=void 0;this.silentlyCanceled_=this.blocking_=this.blocked_=!1;this.unhandledErrorId_=0;this.parent_=null;this.branches_=0;this.constructorStack_=null;if(Error.captureStackTrace){var c={stack:""};Error.captureStackTrace(c,L);"string"==typeof c.stack&&(this.constructorStack_=c.stack.replace(/^[^\n]*\n/,""))}};L.prototype.cancel=function(a){if(this.fired_)this.result_ instanceof L&&this.result_.cancel();else{if(this.parent_){var b=this.parent_;delete this.parent_;a?b.cancel(a):(b.branches_--,0>=b.branches_&&b.cancel())}this.onCancelFunction_?this.onCancelFunction_.call(this.defaultScope_,this):this.silentlyCanceled_=!0;this.fired_||Vb(this,new Wb)}};L.prototype.continue_=function(a,b){this.blocked_=!1;Xb(this,a,b)};var Xb=function(a,b,c){a.fired_=!0;a.result_=c;a.hadError_=!b;Yb(a)},$b=function(a){if(a.fired_){if(!a.silentlyCanceled_)throw new Zb;a.silentlyCanceled_=!1}};L.prototype.callback=function(a){$b(this);ac(a);Xb(this,!0,a)};var Vb=function(a,b){$b(a);ac(b);bc(a,b);Xb(a,!1,b)},bc=function(a,b){a.constructorStack_&&r(b)&&b.stack&&/^[^\n]+(\n   [^\n]+)+/.test(b.stack)&&(b.stack=b.stack+"\nDEFERRED OPERATION:\n"+a.constructorStack_)},ac=function(a){y(!(a instanceof L),"An execution sequence may not be initiated with a blocking Deferred.")},cc=function(a,b,c,d){y(!a.blocking_,"Blocking Deferreds can not be re-used");a.sequence_.push([b,c,d]);a.fired_&&Yb(a)};L.prototype.then=function(a,b,c){var d,e,g=new K(function(a,b){d=a;e=b});cc(this,d,function(a){a instanceof Wb?g.cancel():e(a)});return g.then(a,b,c)};Jb(L);var dc=function(a){return Na(a.sequence_,function(a){return q(a[1])})},Yb=function(a){if(a.unhandledErrorId_&&a.fired_&&dc(a)){var b=a.unhandledErrorId_,c=ec[b];c&&(k.clearTimeout(c.id_),delete ec[b]);a.unhandledErrorId_=0}a.parent_&&(a.parent_.branches_--,delete a.parent_);for(var b=a.result_,d=c=!1;a.sequence_.length&&!a.blocked_;){var e=a.sequence_.shift(),g=e[0],f=e[1],e=e[2];if(g=a.hadError_?f:g)try{var m=g.call(e||a.defaultScope_,b);void 0!==m&&(a.hadError_=a.hadError_&&(m==b||m instanceof Error),a.result_=b=m);Kb(b)&&(d=!0,a.blocked_=!0)}catch($){b=$,a.hadError_=!0,bc(a,b),dc(a)||(c=!0)}}a.result_=b;d&&(m=s(a.continue_,a,!0),d=s(a.continue_,a,!1),b instanceof L?(cc(b,m,d),b.blocking_=!0):b.then(m,d));c&&(b=new fc(b),ec[b.id_]=b,a.unhandledErrorId_=b.id_)},Zb=function(){x.call(this)};u(Zb,x);Zb.prototype.message="Deferred has already fired";Zb.prototype.name="AlreadyCalledError";var Wb=function(){x.call(this)};u(Wb,x);Wb.prototype.message="Deferred was canceled";Wb.prototype.name="CanceledError";var fc=function(a){this.id_=k.setTimeout(s(this.throwError,this),0);this.error_=a};fc.prototype.throwError=function(){y(ec[this.id_],"Cannot throw an error that is not scheduled.");delete ec[this.id_];throw this.error_};var ec={};var kc=function(a){var b={},c=b.document||document,d=document.createElement("SCRIPT"),e={script_:d,timeout_:void 0},g=new L(gc,e),f=null,m=null!=b.timeout?b.timeout:5e3;0<m&&(f=window.setTimeout(function(){hc(d,!0);Vb(g,new ic(1,"Timeout reached for loading script "+a))},m),e.timeout_=f);d.onload=d.onreadystatechange=function(){d.readyState&&"loaded"!=d.readyState&&"complete"!=d.readyState||(hc(d,b.cleanupWhenDone||!1,f),g.callback(null))};d.onerror=function(){hc(d,!0,f);Vb(g,new ic(0,"Error while loading script "+a))};ub(d,{type:"text/javascript",charset:"UTF-8",src:a});jc(c).appendChild(d);return g},jc=function(a){var b=a.getElementsByTagName("HEAD");return b&&0!=b.length?b[0]:a.documentElement},gc=function(){if(this&&this.script_){var a=this.script_;a&&"SCRIPT"==a.tagName&&hc(a,!0,this.timeout_)}},hc=function(a,b,c){null!=c&&k.clearTimeout(c);a.onload=ba;a.onerror=ba;a.onreadystatechange=ba;b&&window.setTimeout(function(){Ab(a)},0)},ic=function(a,b){var c="Jsloader error (code #"+a+")";b&&(c+=": "+b);x.call(this,c);this.code=a};u(ic,x);var lc=function(a){lc[" "](a);return a};lc[" "]=ba;var mc=!C||C&&9<=Ya,nc=C&&!G("9");!F||G("528");E&&G("1.9b")||C&&G("8")||Ta&&G("9.5")||F&&G("528");E&&!G("8")||C&&G("9");var M=function(a,b){this.type=a;this.currentTarget=this.target=b;this.defaultPrevented=this.propagationStopped_=!1;this.returnValue_=!0};M.prototype.disposeInternal=function(){};M.prototype.dispose=function(){};M.prototype.preventDefault=function(){this.defaultPrevented=!0;this.returnValue_=!1};var N=function(a,b){M.call(this,a?a.type:"");this.relatedTarget=this.currentTarget=this.target=null;this.charCode=this.keyCode=this.button=this.screenY=this.screenX=this.clientY=this.clientX=this.offsetY=this.offsetX=0;this.metaKey=this.shiftKey=this.altKey=this.ctrlKey=!1;this.event_=this.state=null;if(a){var c=this.type=a.type;this.target=a.target||a.srcElement;this.currentTarget=b;var d=a.relatedTarget;if(d){if(E){var e;t:{try{lc(d.nodeName);e=!0;break t}catch(g){}e=!1}e||(d=null)}}else"mouseover"==c?d=a.fromElement:"mouseout"==c&&(d=a.toElement);this.relatedTarget=d;this.offsetX=F||void 0!==a.offsetX?a.offsetX:a.layerX;this.offsetY=F||void 0!==a.offsetY?a.offsetY:a.layerY;this.clientX=void 0!==a.clientX?a.clientX:a.pageX;this.clientY=void 0!==a.clientY?a.clientY:a.pageY;this.screenX=a.screenX||0;this.screenY=a.screenY||0;this.button=a.button;this.keyCode=a.keyCode||0;this.charCode=a.charCode||("keypress"==c?a.keyCode:0);this.ctrlKey=a.ctrlKey;this.altKey=a.altKey;this.shiftKey=a.shiftKey;this.metaKey=a.metaKey;this.state=a.state;this.event_=a;a.defaultPrevented&&this.preventDefault()}};u(N,M);N.prototype.preventDefault=function(){N.superClass_.preventDefault.call(this);var a=this.event_;if(a.preventDefault)a.preventDefault();else if(a.returnValue=!1,nc)try{if(a.ctrlKey||112<=a.keyCode&&123>=a.keyCode)a.keyCode=-1}catch(b){}};N.prototype.disposeInternal=function(){};var oc="closure_listenable_"+(1e6*Math.random()|0),pc=0;var qc=function(a,b,c,d,e){this.listener=a;this.proxy=null;this.src=b;this.type=c;this.capture=!!d;this.handler=e;this.key=++pc;this.removed=this.callOnce=!1},rc=function(a){a.removed=!0;a.listener=null;a.proxy=null;a.src=null;a.handler=null};var O=function(a){this.src=a;this.listeners={};this.typeCount_=0};O.prototype.add=function(a,b,c,d,e){var g=a.toString();a=this.listeners[g];a||(a=this.listeners[g]=[],this.typeCount_++);var f=sc(a,b,d,e);-1<f?(b=a[f],c||(b.callOnce=!1)):(b=new qc(b,this.src,g,!!d,e),b.callOnce=c,a.push(b));return b};O.prototype.remove=function(a,b,c,d){a=a.toString();if(!(a in this.listeners))return!1;var e=this.listeners[a];b=sc(e,b,c,d);return-1<b?(rc(e[b]),y(null!=e.length),z.splice.call(e,b,1),0==e.length&&(delete this.listeners[a],this.typeCount_--),!0):!1};var tc=function(a,b){var c=b.type;if(!(c in a.listeners))return!1;var d=Oa(a.listeners[c],b);d&&(rc(b),0==a.listeners[c].length&&(delete a.listeners[c],a.typeCount_--));return d};O.prototype.removeAll=function(a){a=a&&a.toString();var b=0,c;for(c in this.listeners)if(!a||c==a){for(var d=this.listeners[c],e=0;e<d.length;e++)++b,rc(d[e]);delete this.listeners[c];this.typeCount_--}return b};O.prototype.getListener=function(a,b,c,d){a=this.listeners[a.toString()];var e=-1;a&&(e=sc(a,b,c,d));return-1<e?a[e]:null};var sc=function(a,b,c,d){for(var e=0;e<a.length;++e){var g=a[e];if(!g.removed&&g.listener==b&&g.capture==!!c&&g.handler==d)return e}return-1};var uc="closure_lm_"+(1e6*Math.random()|0),vc={},wc=0,xc=function(a,b,c,d,e){if(n(b)){for(var g=0;g<b.length;g++)xc(a,b[g],c,d,e);return null}c=yc(c);if(a&&a[oc])a=a.listen(b,c,d,e);else{if(!b)throw Error("Invalid event type");var g=!!d,f=zc(a);f||(a[uc]=f=new O(a));c=f.add(b,c,!1,d,e);c.proxy||(d=Ac(),c.proxy=d,d.src=a,d.listener=c,a.addEventListener?a.addEventListener(b.toString(),d,g):a.attachEvent(Bc(b.toString()),d),wc++);a=c}return a},Ac=function(){var a=Cc,b=mc?function(c){return a.call(b.src,b.listener,c)}:function(c){c=a.call(b.src,b.listener,c);if(!c)return c};return b},Dc=function(a,b,c,d,e){if(n(b))for(var g=0;g<b.length;g++)Dc(a,b[g],c,d,e);else c=yc(c),a&&a[oc]?a.unlisten(b,c,d,e):a&&(a=zc(a))&&(b=a.getListener(b,c,!!d,e))&&Ec(b)},Ec=function(a){if("number"==typeof a||!a||a.removed)return!1;var b=a.src;if(b&&b[oc])return tc(b.eventTargetListeners_,a);var c=a.type,d=a.proxy;b.removeEventListener?b.removeEventListener(c,d,a.capture):b.detachEvent&&b.detachEvent(Bc(c),d);wc--;(c=zc(b))?(tc(c,a),0==c.typeCount_&&(c.src=null,b[uc]=null)):rc(a);return!0},Bc=function(a){return a in vc?vc[a]:vc[a]="on"+a},Gc=function(a,b,c,d){var e=1;if(a=zc(a))if(b=a.listeners[b.toString()])for(b=b.concat(),a=0;a<b.length;a++){var g=b[a];g&&g.capture==c&&!g.removed&&(e&=!1!==Fc(g,d))}return Boolean(e)},Fc=function(a,b){var c=a.listener,d=a.handler||a.src;a.callOnce&&Ec(a);return c.call(d,b)},Cc=function(a,b){if(a.removed)return!0;if(!mc){var c;if(!(c=b))t:{c=["window","event"];for(var d=k,e;e=c.shift();)if(null!=d[e])d=d[e];else{c=null;break t}c=d}e=c;c=new N(e,this);d=!0;if(!(0>e.keyCode||void 0!=e.returnValue)){t:{var g=!1;if(0==e.keyCode)try{e.keyCode=-1;break t}catch(f){g=!0}if(g||void 0==e.returnValue)e.returnValue=!0}e=[];for(g=c.currentTarget;g;g=g.parentNode)e.push(g);for(var g=a.type,m=e.length-1;!c.propagationStopped_&&0<=m;m--)c.currentTarget=e[m],d&=Gc(e[m],g,!0,c);for(m=0;!c.propagationStopped_&&m<e.length;m++)c.currentTarget=e[m],d&=Gc(e[m],g,!1,c)}return d}return Fc(a,new N(b,this))},zc=function(a){a=a[uc];return a instanceof O?a:null},Hc="__closure_events_fn_"+(1e9*Math.random()>>>0),yc=function(a){y(a,"Listener can not be null.");if(q(a))return a;y(a.handleEvent,"An object listener must have handleEvent method.");return a[Hc]||(a[Hc]=function(b){return a.handleEvent(b)})};var P=function(a){this.handler_=a;this.keys_={}};u(P,H);var Ic=[];h=P.prototype;h.listen=function(a,b,c,d){n(b)||(b&&(Ic[0]=b.toString()),b=Ic);for(var e=0;e<b.length;e++){var g=xc(a,b[e],c||this.handleEvent,d||!1,this.handler_||this);if(!g)break;this.keys_[g.key]=g}return this};h.unlisten=function(a,b,c,d,e){if(n(b))for(var g=0;g<b.length;g++)this.unlisten(a,b[g],c,d,e);else c=c||this.handleEvent,e=e||this.handler_||this,c=yc(c),d=!!d,b=a&&a[oc]?a.getListener(b,c,d,e):a?(a=zc(a))?a.getListener(b,c,d,e):null:null,b&&(Ec(b),delete this.keys_[b.key]);return this};h.removeAll=function(){fb(this.keys_,Ec);this.keys_={}};h.disposeInternal=function(){P.superClass_.disposeInternal.call(this);this.removeAll()};h.handleEvent=function(){throw Error("EventHandler.handleEvent not implemented")};var Q=function(){this.eventTargetListeners_=new O(this);this.actualEventTarget_=this};u(Q,H);Q.prototype[oc]=!0;h=Q.prototype;h.parentEventTarget_=null;h.setParentEventTarget=function(a){this.parentEventTarget_=a};h.addEventListener=function(a,b,c,d){xc(this,a,b,c,d)};h.removeEventListener=function(a,b,c,d){Dc(this,a,b,c,d)};h.dispatchEvent=function(a){Jc(this);var b,c=this.parentEventTarget_;if(c){b=[];for(var d=1;c;c=c.parentEventTarget_)b.push(c),y(1e3>++d,"infinite loop")}c=this.actualEventTarget_;d=a.type||a;if(p(a))a=new M(a,c);else if(a instanceof M)a.target=a.target||c;else{var e=a;a=new M(d,c);lb(a,e)}var e=!0,g;if(b)for(var f=b.length-1;!a.propagationStopped_&&0<=f;f--)g=a.currentTarget=b[f],e=Kc(g,d,!0,a)&&e;a.propagationStopped_||(g=a.currentTarget=c,e=Kc(g,d,!0,a)&&e,a.propagationStopped_||(e=Kc(g,d,!1,a)&&e));if(b)for(f=0;!a.propagationStopped_&&f<b.length;f++)g=a.currentTarget=b[f],e=Kc(g,d,!1,a)&&e;return e};h.disposeInternal=function(){Q.superClass_.disposeInternal.call(this);this.eventTargetListeners_&&this.eventTargetListeners_.removeAll(void 0);this.parentEventTarget_=null};h.listen=function(a,b,c,d){Jc(this);return this.eventTargetListeners_.add(String(a),b,!1,c,d)};h.unlisten=function(a,b,c,d){return this.eventTargetListeners_.remove(String(a),b,c,d)};var Kc=function(a,b,c,d){b=a.eventTargetListeners_.listeners[String(b)];if(!b)return!0;b=b.concat();for(var e=!0,g=0;g<b.length;++g){var f=b[g];if(f&&!f.removed&&f.capture==c){var m=f.listener,$=f.handler||f.src;f.callOnce&&tc(a.eventTargetListeners_,f);e=!1!==m.call($,d)&&e}}return e&&!1!=d.returnValue_};Q.prototype.getListener=function(a,b,c,d){return this.eventTargetListeners_.getListener(String(a),b,c,d)};var Jc=function(a){y(a.eventTargetListeners_,"Event target is not initialized. Did you call the superclass (goog.events.EventTarget) constructor?")};var R=function(a){Q.call(this);this.imageIdToRequestMap_={};this.imageIdToImageMap_={};this.handler_=new P(this);this.parent_=a};u(R,Q);var Lc=[C&&!G("11")?"readystatechange":"load","abort","error"],Mc=function(a,b,c){(c=p(c)?c:c.src)&&(a.imageIdToRequestMap_[b]={src:c,corsRequestType:null})};R.prototype.start=function(){var a=this.imageIdToRequestMap_;La(gb(a),function(b){var c=a[b];if(c&&(delete a[b],!this.disposed_)){var d;d=this.parent_?rb(this.parent_).createDom("img"):new Image;c.corsRequestType&&(d.crossOrigin=c.corsRequestType);this.handler_.listen(d,Lc,this.onNetworkEvent_);this.imageIdToImageMap_[b]=d;d.id=b;d.src=c.src}},this)};R.prototype.onNetworkEvent_=function(a){var b=a.currentTarget;if(b){if("readystatechange"==a.type)if("complete"==b.readyState)a.type="load";else return;"undefined"==typeof b.naturalWidth&&("load"==a.type?(b.naturalWidth=b.width,b.naturalHeight=b.height):(b.naturalWidth=0,b.naturalHeight=0));this.dispatchEvent({type:a.type,target:b});!this.disposed_&&(a=b.id,delete this.imageIdToRequestMap_[a],b=this.imageIdToImageMap_[a])&&(delete this.imageIdToImageMap_[a],this.handler_.unlisten(b,Lc,this.onNetworkEvent_),hb(this.imageIdToImageMap_)&&hb(this.imageIdToRequestMap_)&&this.dispatchEvent("complete"))}};R.prototype.disposeInternal=function(){delete this.imageIdToRequestMap_;delete this.imageIdToImageMap_;eb(this.handler_);R.superClass_.disposeInternal.call(this)};var S=function(){};S.getInstance=function(){return S.instance_?S.instance_:S.instance_=new S};S.prototype.nextId_=0;var T=function(a){Q.call(this);this.dom_=a||rb()};u(T,Q);h=T.prototype;h.idGenerator_=S.getInstance();h.id_=null;h.inDocument_=!1;h.element_=null;h.parent_=null;h.children_=null;h.childIndex_=null;h.wasDecorated_=!1;h.getElement=function(){return this.element_};h.setParentEventTarget=function(a){if(this.parent_&&this.parent_!=a)throw Error("Method not supported");T.superClass_.setParentEventTarget.call(this,a)};h.getDomHelper=function(){return this.dom_};h.createDom=function(){this.element_=this.dom_.createElement("div")};var Oc=function(a,b){if(a.inDocument_)throw Error("Component already rendered");a.element_||a.createDom();b?b.insertBefore(a.element_,null):a.dom_.document_.body.appendChild(a.element_);a.parent_&&!a.parent_.inDocument_||Nc(a)},Nc=function(a){a.inDocument_=!0;Pc(a,function(a){!a.inDocument_&&a.getElement()&&Nc(a)})},Qc=function(a){Pc(a,function(a){a.inDocument_&&Qc(a)});a.googUiComponentHandler_&&a.googUiComponentHandler_.removeAll();a.inDocument_=!1};T.prototype.disposeInternal=function(){this.inDocument_&&Qc(this);this.googUiComponentHandler_&&(this.googUiComponentHandler_.dispose(),delete this.googUiComponentHandler_);Pc(this,function(a){a.dispose()});!this.wasDecorated_&&this.element_&&Ab(this.element_);this.parent_=this.element_=this.childIndex_=this.children_=null;T.superClass_.disposeInternal.call(this)};var Pc=function(a,b){a.children_&&La(a.children_,b,void 0)};T.prototype.removeChild=function(a,b){if(a){var c=p(a)?a:a.id_||(a.id_=":"+(a.idGenerator_.nextId_++).toString(36)),d;this.childIndex_&&c?(d=this.childIndex_,d=(c in d?d[c]:void 0)||null):d=null;a=d;if(c&&a){d=this.childIndex_;c in d&&delete d[c];Oa(this.children_,a);b&&(Qc(a),a.element_&&Ab(a.element_));c=a;if(null==c)throw Error("Unable to set parent component");c.parent_=null;T.superClass_.setParentEventTarget.call(c,null)}}if(!a)throw Error("Child is not in parent component");return a};var U=function(a,b,c){T.call(this,c);this.captchaImage_=a;this.adImage_=b&&300==b.naturalWidth&&57==b.naturalHeight?b:null};u(U,T);U.prototype.createDom=function(){U.superClass_.createDom.call(this);var a=this.getElement();this.captchaImage_.alt=V.image_alt_text;this.getDomHelper().appendChild(a,this.captchaImage_);this.adImage_&&(this.adImage_.alt=V.image_alt_text,this.getDomHelper().appendChild(a,this.adImage_),this.adImage_&&Rc(this.adImage_)&&(a.innerHTML+='<div id="recaptcha-ad-choices"><div class="recaptcha-ad-choices-collapsed"><img height="15" width="30" alt="AdChoices" border="0" src="//www.gstatic.com/recaptcha/api/img/adicon.png"/></div><div class="recaptcha-ad-choices-expanded"><a href="https://support.google.com/adsense/troubleshooter/1631343" target="_blank"><img height="15" width="75" alt="AdChoices" border="0" src="//www.gstatic.com/recaptcha/api/img/adchoices.png"/></a></div></div>'))};var Rc=function(a){var b=Sc(a,"visibility");a=Sc(a,"display");return"hidden"!=b&&"none"!=a},Sc=function(a,b){var c;t:{c=qb(a);if(c.defaultView&&c.defaultView.getComputedStyle&&(c=c.defaultView.getComputedStyle(a,null))){c=c[b]||c.getPropertyValue(b)||"";break t}c=""}if(!(c=c||(a.currentStyle?a.currentStyle[b]:null))&&(c=a.style[Da(b)],"undefined"===typeof c)){c=a.style;var d;t:if(d=Da(b),void 0===a.style[d]){var e=(F?"Webkit":E?"Moz":C?"ms":Ta?"O":null)+Ea(d);if(void 0!==a.style[e]){d=e;break t}}c=c[d]||""}return c};U.prototype.disposeInternal=function(){delete this.captchaImage_;delete this.adImage_;U.superClass_.disposeInternal.call(this)};var Tc=function(a,b,c){this.listener_=a;this.interval_=b||0;this.handler_=c;this.callback_=s(this.doAction_,this)};u(Tc,H);h=Tc.prototype;h.id_=0;h.disposeInternal=function(){Tc.superClass_.disposeInternal.call(this);this.stop();delete this.listener_;delete this.handler_};h.start=function(a){this.stop();var b=this.callback_;a=void 0!==a?a:this.interval_;if(!q(b))if(b&&"function"==typeof b.handleEvent)b=s(b.handleEvent,b);else throw Error("Invalid listener argument");this.id_=2147483647<a?-1:k.setTimeout(b,a||0)};h.stop=function(){this.isActive()&&k.clearTimeout(this.id_);this.id_=0};h.isActive=function(){return 0!=this.id_};h.doAction_=function(){this.id_=0;this.listener_&&this.listener_.call(this.handler_)};var Uc=function(a,b){this.listener_=a;this.handler_=b;this.delay_=new Tc(s(this.onTick_,this),0,this)};u(Uc,H);h=Uc.prototype;h.interval_=0;h.runUntil_=0;h.disposeInternal=function(){this.delay_.dispose();delete this.listener_;delete this.handler_;Uc.superClass_.disposeInternal.call(this)};h.start=function(a,b){this.stop();var c=b||0;this.interval_=Math.max(a||0,0);this.runUntil_=0>c?-1:ga()+c;this.delay_.start(0>c?this.interval_:Math.min(this.interval_,c))};h.stop=function(){this.delay_.stop()};h.isActive=function(){return this.delay_.isActive()};h.onSuccess=function(){};h.onFailure=function(){};h.onTick_=function(){if(this.listener_.call(this.handler_))this.onSuccess();else if(0>this.runUntil_)this.delay_.start(this.interval_);else{var a=this.runUntil_-ga();if(0>=a)this.onFailure();else this.delay_.start(Math.min(this.interval_,a))
}};mb("area base br col command embed hr img input keygen link meta param source track wbr".split(" "));mb("action","cite","data","formaction","href","manifest","poster","src");mb("link","script","style");Ba("".implementsGoogStringTypedString?"".getTypedStringValue():"");var Vc={sanitizedContentKindHtml:!0},Wc={sanitizedContentJsStrChars:!0},Xc={sanitizedContentKindText:!0},W=function(){throw Error("Do not instantiate directly")};W.prototype.contentDir=null;W.prototype.toString=function(){return this.content};var bd=function(a){var b=Yc;y(b,"Soy template may not be null.");var c=rb().createElement("DIV");a=Zc(b(a||$c,void 0,void 0));b=a.match(ad);y(!b,"This template starts with a %s, which cannot be a child of a <div>, as required by soy internals. Consider using goog.soy.renderElement instead.\nTemplate output: %s",b&&b[0],a);c.innerHTML=a;return 1==c.childNodes.length&&(a=c.firstChild,1==a.nodeType)?a:c},Zc=function(a){if(!r(a))return String(a);if(a instanceof W){if(a.contentKind===Vc)return Ia(a.content);if(a.contentKind===Xc)return Ba(a.content)}Ha("Soy template output is unsafe for use as HTML: "+a);return"zSoyz"},ad=/^<(body|caption|col|colgroup|head|html|tr|td|tbody|thead|tfoot)>/i,$c={};C&&G(8);var cd=function(){W.call(this)};u(cd,W);cd.prototype.contentKind=Vc;var dd=function(){W.call(this)};u(dd,W);dd.prototype.contentKind=Wc;var ed=function(a){function b(){}b.prototype=a.prototype;return function(a,d){var e=new b;e.content=String(a);void 0!==d&&(e.contentDir=d);return e}},fd=ed(cd);ed(dd);(function(a){function b(){}b.prototype=a.prototype;return function(a,d){if(!String(a))return"";var e=new b;e.content=String(a);void 0!==d&&(e.contentDir=d);return e}})(cd);var id=function(a){return null!=a&&a.contentKind===Wc?(y(a.constructor===dd),a.content):String(a).replace(gd,hd)},jd={"\x00":"\\x00","\b":"\\x08","	":"\\t","\n":"\\n","":"\\x0b","\f":"\\f","\r":"\\r",'"':"\\x22",$:"\\x24","&":"\\x26","'":"\\x27","(":"\\x28",")":"\\x29","*":"\\x2a","+":"\\x2b",",":"\\x2c","-":"\\x2d",".":"\\x2e","/":"\\/",":":"\\x3a","<":"\\x3c","=":"\\x3d",">":"\\x3e","?":"\\x3f","[":"\\x5b","\\":"\\\\","]":"\\x5d","^":"\\x5e","{":"\\x7b","|":"\\x7c","}":"\\x7d","":"\\x85","\u2028":"\\u2028","\u2029":"\\u2029"},hd=function(a){return jd[a]},gd=/[\x00\x08-\x0d\x22\x26\x27\/\x3c-\x3e\\\x85\u2028\u2029]/g;var Yc=function(a){return fd('<script type="text/javascript">var challenge = \''+id(a.challenge)+"'; var publisherId = '"+id(a.publisherId)+"';"+("ca-mongoogle"==a.publisherId?'google_page_url = "3pcerttesting.com/dab/recaptcha.html";':"")+"\n    google_ad_client = publisherId;\n    google_ad_type = 'html';\n    google_ad_output = 'js';\n    google_image_size = '300x57';\n    google_captcha_token = challenge;\n    google_ad_request_done = function(ad) {\n      window.parent.recaptcha.ads.adutils.googleAdRequestDone(ad);\n    };\n    </script><script type=\"text/javascript\" src=\"//pagead2.googlesyndication.com/pagead/show_ads.js\"></script>")};Yc.soyTemplateName="recaptcha.soy.ads.iframeAdsLoader.main";var ib=function(){var a=k.google_ad;return!!(a&&a.token&&a.imageAdUrl&&a.hashedAnswer&&a.salt&&a.delayedImpressionUrl&&a.engagementUrl)},kd=function(){k.google_ad&&(k.google_ad=null)},ld=function(a){a=a||document.body;var b=k.google_ad;b&&b.searchUpliftUrl&&(b=wb("iframe",{src:'data:text/html;charset=utf-8,<body><img src="'+b.searchUpliftUrl+'"></img></body>',style:"display:none"}),a.appendChild(b))},md=0,nd=function(a){var b=new R;Mc(b,"recaptcha-url-"+md++,a);b.start()},od=function(a,b){var c=RecaptchaState.publisher_id;kd();var d=wb("iframe",{id:"recaptcha-loader-"+md++,style:"display: none"});document.body.appendChild(d);var e=d.contentWindow?d.contentWindow.document:d.contentDocument;e.open("text/html","replace");e.write(bd({challenge:a,publisherId:c}).innerHTML);e.close();c=new Uc(function(){return!!k.google_ad});c.onSuccess=function(){Ab(d);b()};c.onFailure=function(){Ab(d);b()};c.start(50,500)};t("recaptcha.ads.adutils.googleAdRequestDone",function(a){k.google_ad=a});var pd=function(){this.blockSize=-1};var qd=function(){this.blockSize=-1;this.blockSize=64;this.chain_=Array(4);this.block_=Array(this.blockSize);this.totalLength_=this.blockLength_=0;this.reset()};u(qd,pd);qd.prototype.reset=function(){this.chain_[0]=1732584193;this.chain_[1]=4023233417;this.chain_[2]=2562383102;this.chain_[3]=271733878;this.totalLength_=this.blockLength_=0};var rd=function(a,b,c){c||(c=0);var d=Array(16);if(p(b))for(var e=0;16>e;++e)d[e]=b.charCodeAt(c++)|b.charCodeAt(c++)<<8|b.charCodeAt(c++)<<16|b.charCodeAt(c++)<<24;else for(e=0;16>e;++e)d[e]=b[c++]|b[c++]<<8|b[c++]<<16|b[c++]<<24;b=a.chain_[0];c=a.chain_[1];var e=a.chain_[2],g=a.chain_[3],f=0,f=b+(g^c&(e^g))+d[0]+3614090360&4294967295;b=c+(f<<7&4294967295|f>>>25);f=g+(e^b&(c^e))+d[1]+3905402710&4294967295;g=b+(f<<12&4294967295|f>>>20);f=e+(c^g&(b^c))+d[2]+606105819&4294967295;e=g+(f<<17&4294967295|f>>>15);f=c+(b^e&(g^b))+d[3]+3250441966&4294967295;c=e+(f<<22&4294967295|f>>>10);f=b+(g^c&(e^g))+d[4]+4118548399&4294967295;b=c+(f<<7&4294967295|f>>>25);f=g+(e^b&(c^e))+d[5]+1200080426&4294967295;g=b+(f<<12&4294967295|f>>>20);f=e+(c^g&(b^c))+d[6]+2821735955&4294967295;e=g+(f<<17&4294967295|f>>>15);f=c+(b^e&(g^b))+d[7]+4249261313&4294967295;c=e+(f<<22&4294967295|f>>>10);f=b+(g^c&(e^g))+d[8]+1770035416&4294967295;b=c+(f<<7&4294967295|f>>>25);f=g+(e^b&(c^e))+d[9]+2336552879&4294967295;g=b+(f<<12&4294967295|f>>>20);f=e+(c^g&(b^c))+d[10]+4294925233&4294967295;e=g+(f<<17&4294967295|f>>>15);f=c+(b^e&(g^b))+d[11]+2304563134&4294967295;c=e+(f<<22&4294967295|f>>>10);f=b+(g^c&(e^g))+d[12]+1804603682&4294967295;b=c+(f<<7&4294967295|f>>>25);f=g+(e^b&(c^e))+d[13]+4254626195&4294967295;g=b+(f<<12&4294967295|f>>>20);f=e+(c^g&(b^c))+d[14]+2792965006&4294967295;e=g+(f<<17&4294967295|f>>>15);f=c+(b^e&(g^b))+d[15]+1236535329&4294967295;c=e+(f<<22&4294967295|f>>>10);f=b+(e^g&(c^e))+d[1]+4129170786&4294967295;b=c+(f<<5&4294967295|f>>>27);f=g+(c^e&(b^c))+d[6]+3225465664&4294967295;g=b+(f<<9&4294967295|f>>>23);f=e+(b^c&(g^b))+d[11]+643717713&4294967295;e=g+(f<<14&4294967295|f>>>18);f=c+(g^b&(e^g))+d[0]+3921069994&4294967295;c=e+(f<<20&4294967295|f>>>12);f=b+(e^g&(c^e))+d[5]+3593408605&4294967295;b=c+(f<<5&4294967295|f>>>27);f=g+(c^e&(b^c))+d[10]+38016083&4294967295;g=b+(f<<9&4294967295|f>>>23);f=e+(b^c&(g^b))+d[15]+3634488961&4294967295;e=g+(f<<14&4294967295|f>>>18);f=c+(g^b&(e^g))+d[4]+3889429448&4294967295;c=e+(f<<20&4294967295|f>>>12);f=b+(e^g&(c^e))+d[9]+568446438&4294967295;b=c+(f<<5&4294967295|f>>>27);f=g+(c^e&(b^c))+d[14]+3275163606&4294967295;g=b+(f<<9&4294967295|f>>>23);f=e+(b^c&(g^b))+d[3]+4107603335&4294967295;e=g+(f<<14&4294967295|f>>>18);f=c+(g^b&(e^g))+d[8]+1163531501&4294967295;c=e+(f<<20&4294967295|f>>>12);f=b+(e^g&(c^e))+d[13]+2850285829&4294967295;b=c+(f<<5&4294967295|f>>>27);f=g+(c^e&(b^c))+d[2]+4243563512&4294967295;g=b+(f<<9&4294967295|f>>>23);f=e+(b^c&(g^b))+d[7]+1735328473&4294967295;e=g+(f<<14&4294967295|f>>>18);f=c+(g^b&(e^g))+d[12]+2368359562&4294967295;c=e+(f<<20&4294967295|f>>>12);f=b+(c^e^g)+d[5]+4294588738&4294967295;b=c+(f<<4&4294967295|f>>>28);f=g+(b^c^e)+d[8]+2272392833&4294967295;g=b+(f<<11&4294967295|f>>>21);f=e+(g^b^c)+d[11]+1839030562&4294967295;e=g+(f<<16&4294967295|f>>>16);f=c+(e^g^b)+d[14]+4259657740&4294967295;c=e+(f<<23&4294967295|f>>>9);f=b+(c^e^g)+d[1]+2763975236&4294967295;b=c+(f<<4&4294967295|f>>>28);f=g+(b^c^e)+d[4]+1272893353&4294967295;g=b+(f<<11&4294967295|f>>>21);f=e+(g^b^c)+d[7]+4139469664&4294967295;e=g+(f<<16&4294967295|f>>>16);f=c+(e^g^b)+d[10]+3200236656&4294967295;c=e+(f<<23&4294967295|f>>>9);f=b+(c^e^g)+d[13]+681279174&4294967295;b=c+(f<<4&4294967295|f>>>28);f=g+(b^c^e)+d[0]+3936430074&4294967295;g=b+(f<<11&4294967295|f>>>21);f=e+(g^b^c)+d[3]+3572445317&4294967295;e=g+(f<<16&4294967295|f>>>16);f=c+(e^g^b)+d[6]+76029189&4294967295;c=e+(f<<23&4294967295|f>>>9);f=b+(c^e^g)+d[9]+3654602809&4294967295;b=c+(f<<4&4294967295|f>>>28);f=g+(b^c^e)+d[12]+3873151461&4294967295;g=b+(f<<11&4294967295|f>>>21);f=e+(g^b^c)+d[15]+530742520&4294967295;e=g+(f<<16&4294967295|f>>>16);f=c+(e^g^b)+d[2]+3299628645&4294967295;c=e+(f<<23&4294967295|f>>>9);f=b+(e^(c|~g))+d[0]+4096336452&4294967295;b=c+(f<<6&4294967295|f>>>26);f=g+(c^(b|~e))+d[7]+1126891415&4294967295;g=b+(f<<10&4294967295|f>>>22);f=e+(b^(g|~c))+d[14]+2878612391&4294967295;e=g+(f<<15&4294967295|f>>>17);f=c+(g^(e|~b))+d[5]+4237533241&4294967295;c=e+(f<<21&4294967295|f>>>11);f=b+(e^(c|~g))+d[12]+1700485571&4294967295;b=c+(f<<6&4294967295|f>>>26);f=g+(c^(b|~e))+d[3]+2399980690&4294967295;g=b+(f<<10&4294967295|f>>>22);f=e+(b^(g|~c))+d[10]+4293915773&4294967295;e=g+(f<<15&4294967295|f>>>17);f=c+(g^(e|~b))+d[1]+2240044497&4294967295;c=e+(f<<21&4294967295|f>>>11);f=b+(e^(c|~g))+d[8]+1873313359&4294967295;b=c+(f<<6&4294967295|f>>>26);f=g+(c^(b|~e))+d[15]+4264355552&4294967295;g=b+(f<<10&4294967295|f>>>22);f=e+(b^(g|~c))+d[6]+2734768916&4294967295;e=g+(f<<15&4294967295|f>>>17);f=c+(g^(e|~b))+d[13]+1309151649&4294967295;c=e+(f<<21&4294967295|f>>>11);f=b+(e^(c|~g))+d[4]+4149444226&4294967295;b=c+(f<<6&4294967295|f>>>26);f=g+(c^(b|~e))+d[11]+3174756917&4294967295;g=b+(f<<10&4294967295|f>>>22);f=e+(b^(g|~c))+d[2]+718787259&4294967295;e=g+(f<<15&4294967295|f>>>17);f=c+(g^(e|~b))+d[9]+3951481745&4294967295;a.chain_[0]=a.chain_[0]+b&4294967295;a.chain_[1]=a.chain_[1]+(e+(f<<21&4294967295|f>>>11))&4294967295;a.chain_[2]=a.chain_[2]+e&4294967295;a.chain_[3]=a.chain_[3]+g&4294967295};qd.prototype.update=function(a,b){void 0===b&&(b=a.length);for(var c=b-this.blockSize,d=this.block_,e=this.blockLength_,g=0;g<b;){if(0==e)for(;g<=c;)rd(this,a,g),g+=this.blockSize;if(p(a))for(;g<b;){if(d[e++]=a.charCodeAt(g++),e==this.blockSize){rd(this,d);e=0;break}}else for(;g<b;)if(d[e++]=a[g++],e==this.blockSize){rd(this,d);e=0;break}}this.blockLength_=e;this.totalLength_+=b};var X=function(){P.call(this);this.callback_=this.element_=null;this.md5_=new qd};u(X,P);var sd=function(a,b,c,d,e){a.unwatch();a.element_=b;a.callback_=e;a.listen(b,"keyup",s(a.onChanged_,a,c,d))};X.prototype.unwatch=function(){this.element_&&this.callback_&&(this.removeAll(),this.callback_=this.element_=null)};X.prototype.onChanged_=function(a,b){var c;c=(c=this.element_.value)?c.replace(/[\s\xa0]+/g,"").toLowerCase():"";this.md5_.reset();this.md5_.update(c+"."+b);c=this.md5_;var d=Array((56>c.blockLength_?c.blockSize:2*c.blockSize)-c.blockLength_);d[0]=128;for(var e=1;e<d.length-8;++e)d[e]=0;for(var g=8*c.totalLength_,e=d.length-8;e<d.length;++e)d[e]=g&255,g/=256;c.update(d);d=Array(16);for(e=g=0;4>e;++e)for(var f=0;32>f;f+=8)d[g++]=c.chain_[e]>>>f&255;$a(d).toLowerCase()==a.toLowerCase()&&this.callback_()};X.prototype.disposeInternal=function(){this.element_=null;X.superClass_.disposeInternal.call(this)};var ud=function(a,b,c){this.adObject_=a;this.captchaImageUrl_=b;this.opt_successCallback_=c||null;td(this)};u(ud,H);var td=function(a){var b=new R;db(a,fa(eb,b));Mc(b,"recaptcha_challenge_image",a.captchaImageUrl_);Mc(b,"recaptcha_ad_image",a.adObject_.imageAdUrl);var c={};xc(b,"load",s(function(a,b){a[b.target.id]=b.target},a,c));xc(b,"complete",s(a.handleImagesLoaded_,a,c));b.start()};ud.prototype.handleImagesLoaded_=function(a){a=new U(a.recaptcha_challenge_image,a.recaptcha_ad_image);db(this,fa(eb,a));var b=sb(document,"recaptcha_image");zb(b);Oc(a,b);a.adImage_&&Rc(a.adImage_)&&(nd(this.adObject_.delayedImpressionUrl),a=new X,db(this,fa(eb,a)),sd(a,sb(document,"recaptcha_response_field"),this.adObject_.hashedAnswer,this.adObject_.salt,s(function(a,b){a.unwatch();nd(b)},this,a,this.adObject_.engagementUrl)),this.opt_successCallback_&&this.opt_successCallback_("04"+this.adObject_.token))};var V=w;t("RecaptchaStr",V);var Y=k.RecaptchaOptions;t("RecaptchaOptions",Y);var vd={tabindex:0,theme:"red",callback:null,lang:null,custom_theme_widget:null,custom_translations:null};t("RecaptchaDefaultOptions",vd);var Z={widget:null,timer_id:-1,style_set:!1,theme:null,type:"image",ajax_verify_cb:null,th1:null,th2:null,th3:null,element:"",ad_captcha_plugin:null,reload_timeout:-1,force_reload:!1,$:function(a){return"string"==typeof a?document.getElementById(a):a},attachEvent:function(a,b,c){a&&a.addEventListener?a.addEventListener(b,c,!1):a&&a.attachEvent&&a.attachEvent("on"+b,c)},create:function(a,b,c){Z.destroy();b&&(Z.widget=Z.$(b),Z.element=b);Z._init_options(c);Z._call_challenge(a)},destroy:function(){var a=Z.$("recaptcha_challenge_field");a&&a.parentNode.removeChild(a);-1!=Z.timer_id&&clearInterval(Z.timer_id);Z.timer_id=-1;if(a=Z.$("recaptcha_image"))a.innerHTML="";Z.update_widget();Z.widget&&("custom"!=Z.theme?Z.widget.innerHTML="":Z.widget.style.display="none",Z.widget=null)},focus_response_field:function(){var a=Z.$("recaptcha_response_field");a&&a.focus()},get_challenge:function(){return"undefined"==typeof RecaptchaState?null:RecaptchaState.challenge},get_response:function(){var a=Z.$("recaptcha_response_field");return a?a.value:null},ajax_verify:function(a){Z.ajax_verify_cb=a;a=Z.get_challenge()||"";var b=Z.get_response()||"";a=Z._get_api_server()+"/ajaxverify?c="+encodeURIComponent(a)+"&response="+encodeURIComponent(b);Z._add_script(a)},_ajax_verify_callback:function(a){Z.ajax_verify_cb(a)},_get_overridable_url:function(a){var b=window.location.protocol;if("undefined"!=typeof _RecaptchaOverrideApiServer)a=_RecaptchaOverrideApiServer;else if("undefined"!=typeof RecaptchaState&&"string"==typeof RecaptchaState.server&&0<RecaptchaState.server.length)return RecaptchaState.server.replace(/\/+$/,"");return b+"//"+a},_get_api_server:function(){return Z._get_overridable_url("www.google.com/recaptcha/api")},_get_static_url_root:function(){return Z._get_overridable_url("www.gstatic.com/recaptcha/api")},_call_challenge:function(a){a=Z._get_api_server()+"/challenge?k="+a+"&ajax=1&cachestop="+Math.random();Z.getLang_()&&(a+="&lang="+Z.getLang_());"undefined"!=typeof Y.extra_challenge_params&&(a+="&"+Y.extra_challenge_params);Z._add_script(a)},_add_script:function(a){var b=document.createElement("script");b.type="text/javascript";b.src=a;Z._get_script_area().appendChild(b)},_get_script_area:function(){var a=document.getElementsByTagName("head");return a=!a||1>a.length?document.body:a[0]},_hash_merge:function(a){for(var b={},c=0;c<a.length;c++)for(var d in a[c])b[d]=a[c][d];return b},_init_options:function(a){Y=Z._hash_merge([vd,a||{}])},challenge_callback_internal:function(){Z.update_widget();Z._reset_timer();V=Z._hash_merge([w,ra[Z.getLang_()]||{},Y.custom_translations||{}]);window.addEventListener&&window.addEventListener("unload",function(){Z.destroy()},!1);Z._is_ie()&&window.attachEvent&&window.attachEvent("onbeforeunload",function(){});if(0<navigator.userAgent.indexOf("KHTML")){var a=document.createElement("iframe");a.src="about:blank";a.style.height="0px";a.style.width="0px";a.style.visibility="hidden";a.style.border="none";a.appendChild(document.createTextNode("This frame prevents back/forward cache problems in Safari."));document.body.appendChild(a)}Z._finish_widget()},_add_css:function(a){if(-1!=navigator.appVersion.indexOf("MSIE 5"))document.write('<style type="text/css">'+a+"</style>");else{var b=document.createElement("style");b.type="text/css";b.styleSheet?b.styleSheet.cssText=a:b.appendChild(document.createTextNode(a));Z._get_script_area().appendChild(b)}},_set_style:function(a){Z.style_set||(Z.style_set=!0,Z._add_css(a+"\n\n.recaptcha_is_showing_audio .recaptcha_only_if_image,.recaptcha_isnot_showing_audio .recaptcha_only_if_audio,.recaptcha_had_incorrect_sol .recaptcha_only_if_no_incorrect_sol,.recaptcha_nothad_incorrect_sol .recaptcha_only_if_incorrect_sol{display:none !important}"))},_init_builtin_theme:function(){var a=Z.$,b=Z._get_static_url_root(),c=v.VertCss,d=v.VertHtml,e=b+"/img/"+Z.theme,g="gif",b=Z.theme;"clean"==b&&(c=v.CleanCss,d=v.CleanHtml,g="png");c=c.replace(/IMGROOT/g,e);Z._set_style(c);Z.update_widget();Z.widget.innerHTML='<div id="recaptcha_area">'+d+"</div>";c=Z.getLang_();a("recaptcha_privacy")&&null!=c&&"en"==c.substring(0,2).toLowerCase()&&null!=V.privacy_and_terms&&0<V.privacy_and_terms.length&&(c=document.createElement("a"),c.href="http://www.google.com/intl/en/policies/",c.target="_blank",c.innerHTML=V.privacy_and_terms,a("recaptcha_privacy").appendChild(c));c=function(b,c,d,J){var D=a(b);D.src=e+"/"+c+"."+g;c=V[d];D.alt=c;b=a(b+"_btn");b.title=c;Z.attachEvent(b,"click",J)};c("recaptcha_reload","refresh","refresh_btn",function(){Z.reload_internal("r")});c("recaptcha_switch_audio","audio","audio_challenge",function(){Z.switch_type("audio")});c("recaptcha_switch_img","text","visual_challenge",function(){Z.switch_type("image")});c("recaptcha_whatsthis","help","help_btn",Z.showhelp);"clean"==b&&(a("recaptcha_logo").src=e+"/logo."+g);a("recaptcha_table").className="recaptchatable recaptcha_theme_"+Z.theme;b=function(b,c){var d=a(b);d&&(RecaptchaState.rtl&&"span"==d.tagName.toLowerCase()&&(d.dir="rtl"),d.appendChild(document.createTextNode(V[c])))};b("recaptcha_instructions_image","instructions_visual");b("recaptcha_instructions_audio","instructions_audio");b("recaptcha_instructions_error","incorrect_try_again");a("recaptcha_instructions_image")||a("recaptcha_instructions_audio")||(b="audio"==Z.type?V.instructions_audio:V.instructions_visual,b=b.replace(/:$/,""),a("recaptcha_response_field").setAttribute("placeholder",b))},_finish_widget:function(){var a=Z.$,b=Y,c=b.theme;c in{blackglass:1,clean:1,custom:1,red:1,white:1}||(c="red");Z.theme||(Z.theme=c);"custom"!=Z.theme?Z._init_builtin_theme():Z._set_style("");c=document.createElement("span");c.id="recaptcha_challenge_field_holder";c.style.display="none";a("recaptcha_response_field").parentNode.insertBefore(c,a("recaptcha_response_field"));a("recaptcha_response_field").setAttribute("autocomplete","off");a("recaptcha_image").style.width="300px";a("recaptcha_image").style.height="57px";a("recaptcha_challenge_field_holder").innerHTML='<input type="hidden" name="recaptcha_challenge_field" id="recaptcha_challenge_field" value=""/>';Z.th_init();Z.should_focus=!1;Z.th3||Z.force_reload?(Z._set_challenge(RecaptchaState.challenge,"image",!0),setTimeout(function(){Z.reload_internal("i")},100)):Z._set_challenge(RecaptchaState.challenge,"image",!1);Z.updateTabIndexes_();Z.update_widget();Z.widget&&(Z.widget.style.display="");b.callback&&b.callback()},updateTabIndexes_:function(){var a=Z.$,b=Y;b.tabindex&&(b=b.tabindex,a("recaptcha_response_field").tabIndex=b++,"audio"==Z.type&&a("recaptcha_audio_play_again")&&(a("recaptcha_audio_play_again").tabIndex=b++,a("recaptcha_audio_download"),a("recaptcha_audio_download").tabIndex=b++),"custom"!=Z.theme&&(a("recaptcha_reload_btn").tabIndex=b++,a("recaptcha_switch_audio_btn").tabIndex=b++,a("recaptcha_switch_img_btn").tabIndex=b++,a("recaptcha_whatsthis_btn").tabIndex=b,a("recaptcha_privacy").tabIndex=b++))},switch_type:function(a){if(!((new Date).getTime()<Z.reload_timeout)&&(Z.type=a,Z.reload_internal("audio"==Z.type?"a":"v"),"custom"!=Z.theme)){a=Z.$;var b="audio"==Z.type?V.instructions_audio:V.instructions_visual,b=b.replace(/:$/,"");a("recaptcha_response_field").setAttribute("placeholder",b)}},reload:function(){Z.reload_internal("r")},reload_internal:function(a){var b=Y,c=RecaptchaState,d=(new Date).getTime();d<Z.reload_timeout||(Z.reload_timeout=d+1e3,"undefined"==typeof a&&(a="r"),d=Z._get_api_server()+"/reload?c="+c.challenge+"&k="+c.site+"&reason="+a+"&type="+Z.type,Z.getLang_()&&(d+="&lang="+Z.getLang_()),"undefined"!=typeof b.extra_challenge_params&&(d+="&"+b.extra_challenge_params),Z.th_callback_invoke(),Z.th1&&(d+="&th="+Z.th1,Z.th1=""),"audio"==Z.type&&(d=b.audio_beta_12_08?d+"&audio_beta_12_08=1":d+"&new_audio_default=1"),Z.should_focus="t"!=a&&"i"!=a,Z._add_script(d),eb(Z.ad_captcha_plugin),c.publisher_id=null)},th_callback_invoke:function(){if(Z.th3)try{var a=Z.th3.exec();a&&1600>a.length&&(Z.th1=a)}catch(b){Z.th1=""}},finish_reload:function(a,b,c,d){RecaptchaState.payload_url=c;RecaptchaState.is_incorrect=!1;RecaptchaState.publisher_id=d;Z._set_challenge(a,b,!1);Z.updateTabIndexes_()},_set_challenge:function(a,b,c){"image"==b&&RecaptchaState.publisher_id?od(a,function(){Z._set_challenge_internal(a,b,c)}):Z._set_challenge_internal(a,b,c)},_set_challenge_internal:function(a,b,c){var d=Z.$,e=RecaptchaState;e.challenge=a;Z.type=b;d("recaptcha_challenge_field").value=e.challenge;c||("audio"==b?(d("recaptcha_image").innerHTML=Z.getAudioCaptchaHtml(),Z._loop_playback()):"image"==b&&(a=e.payload_url,a||(a=Z._get_api_server()+"/image?c="+e.challenge,Z.th_callback_invoke(),Z.th1&&(a+="&th="+Z.th1,Z.th1="")),ld(d("recaptcha_widget_div")),ib()?Z.ad_captcha_plugin=new ud(jb(),a,function(a){RecaptchaState.challenge=a;d("recaptcha_challenge_field").value=a}):d("recaptcha_image").innerHTML='<img id="recaptcha_challenge_image" alt="'+V.image_alt_text+'" height="57" width="300" src="'+a+'" />',kd()));Z._css_toggle("recaptcha_had_incorrect_sol","recaptcha_nothad_incorrect_sol",e.is_incorrect);Z._css_toggle("recaptcha_is_showing_audio","recaptcha_isnot_showing_audio","audio"==b);Z._clear_input();Z.should_focus&&Z.focus_response_field();Z._reset_timer()},_reset_timer:function(){clearInterval(Z.timer_id);var a=Math.max(1e3*(RecaptchaState.timeout-60),6e4);Z.timer_id=setInterval(function(){Z.reload_internal("t")},a);return a},showhelp:function(){window.open(Z._get_help_link(),"recaptcha_popup","width=460,height=580,location=no,menubar=no,status=no,toolbar=no,scrollbars=yes,resizable=yes")},_clear_input:function(){Z.$("recaptcha_response_field").value=""},_displayerror:function(a){var b=Z.$;b("recaptcha_image").innerHTML="";b("recaptcha_image").appendChild(document.createTextNode(a))},reloaderror:function(a){Z._displayerror(a)},_is_ie:function(){return 0<navigator.userAgent.indexOf("MSIE")&&!window.opera},_css_toggle:function(a,b,c){Z.update_widget();var d=Z.widget;d||(d=document.body);var e=d.className,e=e.replace(new RegExp("(^|\\s+)"+a+"(\\s+|$)")," "),e=e.replace(new RegExp("(^|\\s+)"+b+"(\\s+|$)")," ");d.className=e+(" "+(c?a:b))},_get_help_link:function(){var a=Z._get_api_server().replace(/\/[a-zA-Z0-9]+\/?$/,"/help"),a=a+("?c="+RecaptchaState.challenge);Z.getLang_()&&(a+="&hl="+Z.getLang_());return a},playAgain:function(){Z.$("recaptcha_image").innerHTML=Z.getAudioCaptchaHtml();Z._loop_playback()},_loop_playback:function(){var a=Z.$("recaptcha_audio_play_again");a&&Z.attachEvent(a,"click",function(){Z.playAgain();return!1})},getAudioCaptchaHtml:function(){var a=RecaptchaState.payload_url;a||(a=Z._get_api_server()+"/audio.mp3?c="+RecaptchaState.challenge,Z.th_callback_invoke(),Z.th1&&(a+="&th="+Z.th1,Z.th1=""));var b=Z._get_static_url_root()+"/img/audiocaptcha.swf?v2",b=Z._is_ie()?'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" id="audiocaptcha" width="0" height="0" codebase="https://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab"><param name="movie" value="'+b+'" /><param name="quality" value="high" /><param name="bgcolor" value="#869ca7" /><param name="allowScriptAccess" value="always" /></object><br/>':'<embed src="'+b+'" quality="high" bgcolor="#869ca7" width="0" height="0" name="audiocaptcha" align="middle" play="true" loop="false" quality="high" allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" /></embed>',c="";Z.checkFlashVer()&&(c="<br/>"+Z.getSpan_('<a id="recaptcha_audio_play_again" class="recaptcha_audio_cant_hear_link">'+V.play_again+"</a>"));c+="<br/>"+Z.getSpan_('<a id="recaptcha_audio_download" class="recaptcha_audio_cant_hear_link" target="_blank" href="'+a+'">'+V.cant_hear_this+"</a>");return b+c},getSpan_:function(a){return"<span"+(RecaptchaState&&RecaptchaState.rtl?' dir="rtl"':"")+">"+a+"</span>"},gethttpwavurl:function(){if("audio"!=Z.type)return"";var a=RecaptchaState.payload_url;a||(a=Z._get_api_server()+"/image?c="+RecaptchaState.challenge,Z.th_callback_invoke(),Z.th1&&(a+="&th="+Z.th1,Z.th1=""));return a},checkFlashVer:function(){var a=-1!=navigator.appVersion.indexOf("MSIE"),b=-1!=navigator.appVersion.toLowerCase().indexOf("win"),c=-1!=navigator.userAgent.indexOf("Opera"),d=-1;if(null!=navigator.plugins&&0<navigator.plugins.length){if(navigator.plugins["Shockwave Flash 2.0"]||navigator.plugins["Shockwave Flash"])d=navigator.plugins["Shockwave Flash"+(navigator.plugins["Shockwave Flash 2.0"]?" 2.0":"")].description.split(" ")[2].split(".")[0]}else if(a&&b&&!c)try{d=new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7").GetVariable("$version").split(" ")[1].split(",")[0]}catch(e){}return 9<=d},getLang_:function(){return Y.lang?Y.lang:"undefined"!=typeof RecaptchaState&&RecaptchaState.lang?RecaptchaState.lang:null},challenge_callback:function(){Z.force_reload=!!RecaptchaState.force_reload;if(RecaptchaState.t3){var a=RecaptchaState.t1?Za(cb(RecaptchaState.t1)):"",b=RecaptchaState.t2?Za(cb(RecaptchaState.t2)):"",c=RecaptchaState.t3?Za(cb(RecaptchaState.t3)):"";Z.th2=c;if(a)b=kc(a),cc(b,Z.challenge_callback_internal,null,void 0),cc(b,null,Z.challenge_callback_internal,void 0);else{if(k.execScript)k.execScript(b,"JavaScript");else if(k.eval)null==ha&&(k.eval("var _et_ = 1;"),"undefined"!=typeof k._et_?(delete k._et_,ha=!0):ha=!1),ha?k.eval(b):(a=k.document,c=a.createElement("script"),c.type="text/javascript",c.defer=!1,c.appendChild(a.createTextNode(b)),a.body.appendChild(c),a.body.removeChild(c));else throw Error("goog.globalEval not available");Z.challenge_callback_internal()}}else Z.challenge_callback_internal()},th_init:function(){try{k.thintinel&&k.thintinel.th&&(Z.th3=new k.thintinel.th(Z.th2),Z.th2="")}catch(a){}},update_widget:function(){Z.element&&(Z.widget=Z.$(Z.element))}};t("Recaptcha",Z)})();
var Globals = (function () {
	//Do setup work here
	return {
		path: '',
		url:  '',
		loggedIn:  false,
		opac:  false, // true prevents browser storage of user viewing settings
		automaticTimeoutLength: 0,
		automaticTimeoutLengthLoggedOut: 0,
		repositoryUrl: '',
		encodedRepositoryUrl: '',
		activeAction: '',
		activeModule: ''
	}
})(Globals || {});
var AspenDiscovery = (function(){

	// This provides a check to interrupt AjaxFail Calls on page redirects;
	 window.onbeforeunload = function(){
		Globals.LeavingPage = true
	};

	$(document).ready(function(){
		AspenDiscovery.initializeModalDialogs();
		AspenDiscovery.setupFieldSetToggles();
		AspenDiscovery.initCarousels();

		$("#modalDialog").modal({show:false});
		$('[data-toggle="tooltip"]').tooltip();

		$('.panel')
				.on('show.bs.collapse', function () {
					$(this).addClass('active');
				})
				.on('hide.bs.collapse', function () {
					$(this).removeClass('active');
				});

		$(window).on("popstate", function () {
			// if the state is the page you expect, pull the name and load it.
			if (history.state && history.state.page === "MapExhibit") {
				AspenDiscovery.Archive.handleMapClick(history.state.marker, history.state.exhibitPid, history.state.placePid, history.state.label, false, history.state.showTimeline);
			}else if (history.state && history.state.page === "Book") {
				AspenDiscovery.Archive.handleBookClick(history.state.bookPid, history.state.pagePid, history.state.viewer);
			}else if (history.state && history.state.page === "Checkouts") {
				let selector = '#checkoutsTab a[href="#' + history.state.source + '"]';
				$(selector).tab('show');
			}else if (history.state && history.state.page === "Holds") {
				let selector = '#holdsTab a[href="#' + history.state.source + '"]';
				$(selector).tab('show');
			}else if (history.state && history.state.page === "ReadingHistory") {
				AspenDiscovery.Account.loadReadingHistory(history.state.selectedUser, history.state.sort, history.state.pageNumber, history.state.showCovers, history.state.filter);
			}else if (history.state && history.state.page === "Browse") {
				if (history.state.subBrowseCategory){
					AspenDiscovery.Browse.changeBrowseSubCategory(history.state.subBrowseCategory, history.state.selectedBrowseCategory, false);
				}else{
					AspenDiscovery.Browse.changeBrowseCategory(history.state.selectedBrowseCategory, false);
				}
			}
		});
	});

	return {
		buildUrl: function(base, key, value) {
			let sep = (base.indexOf('?') > -1) ? '&' : '?';
			return base + sep + key + '=' + value;
		},

		changePageSize: function(){
			let url = window.location.href;
			if (url.match(/[&?]pageSize=\d+/)) {
				url = url.replace(/pageSize=\d+/, "pageSize=" + $("#pageSize").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&pageSize=" + $("#pageSize").val();
				}else{
					url = url+ "?pageSize=" + $("#pageSize").val();
				}
			}
			window.location.href = url;
		},

		closeLightbox: function(callback){
			let modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
				if (callback !== undefined){
					modalDialog.on('hidden.bs.modal', function (e) {
						modalDialog.off('hidden.bs.modal');
						callback();
					});
				}
			}
		},

		goToAnchor: function(anchorName) {
			$('html,body').animate({scrollTop: $("#" + anchorName).offset().top},'slow');
		},

		initCarousels: function(carouselClass){
			carouselClass = carouselClass || '.jcarousel';
			var jcarousel = $(carouselClass);
			let wrapper   = jcarousel.parents('.jcarousel-wrapper');
			// console.log('init Carousels called for ', jcarousel);

			jcarousel.on('jcarousel:reload jcarousel:create', function() {

				let Carousel	   = $(this);
				let width		  = Carousel.innerWidth();
				let liTags		 = Carousel.find('li');
				if (liTags == null ||liTags.length === 0){
					return;
				}
				let leftMargin	 = +liTags.css('margin-left').replace('px', '');
				let rightMargin	= +liTags.css('margin-right').replace('px', '');
				let numCategories  = Carousel.jcarousel('items').length || 1;
				let numItemsToShow = 1;

				// Adjust Browse Category Carousels
				if (jcarousel.is('#browse-category-carousel')){

					// set the number of categories to show; if there aren't enough categories, show all the categories instead
					if (width > 1000) {
						numItemsToShow = Math.min(5, numCategories);
					} else if (width > 700) {
						numItemsToShow = Math.min(4, numCategories);
					} else if (width > 500) {
						numItemsToShow = Math.min(3, numCategories);
					} else if (width > 400) {
						numItemsToShow = Math.min(2, numCategories);
					}

				}

				// Default Generic Carousel;
				else {
					if (width >= 800) {
						numItemsToShow = Math.min(5, numCategories);
					} else if (width >= 600) {
						numItemsToShow = Math.min(4, numCategories);
					} else if (width >= 400) {
						numItemsToShow = Math.min(3, numCategories);
					} else if (width >= 300) {
						numItemsToShow = Math.min(2, numCategories);
					}
				}

				// Set the width of each item in the carousel
				var calcWidth = (width - numItemsToShow*(leftMargin + rightMargin))/numItemsToShow;
				Carousel.jcarousel('items').css('width', Math.floor(calcWidth) + 'px');// Set Width

				if (numItemsToShow >= numCategories){
					$(this).offsetParent().children('.jcarousel-control-prev').hide();
					$(this).offsetParent().children('.jcarousel-control-next').hide();
				}

			})
			.jcarousel({
				wrap: 'circular'
			});

			// These Controls could possibly be replaced with data-api attributes
			$('.jcarousel-control-prev', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '-=1'
					});

			$('.jcarousel-control-next', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.jcarouselControl({
						target: '+=1'
					});

			$('.jcarousel-pagination', wrapper)
					//.not('.ajax-carousel-control') // ajax carousels get initiated when content is loaded
					.on('jcarouselpagination:active', 'a', function() {
						$(this).addClass('active');
					})
					.on('jcarouselpagination:inactive', 'a', function() {
						$(this).removeClass('active');
					})
					.on('click', function(e) {
						e.preventDefault();
					})
					.jcarouselPagination({
						perPage: 1,
						item: function(page) {
							return '<a href="#' + page + '">' + page + '</a>';
						}
					});

			// If Browse Category js is set, initialize those functions
			if (typeof AspenDiscovery.Browse.initializeBrowseCategory == 'function') {
				AspenDiscovery.Browse.initializeBrowseCategory();
			}
		},

		initializeModalDialogs: function() {
			$(".modalDialogTrigger").each(function(){
				$(this).click(function(){
					let trigger = $(this);
					let dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					let dialogDestination = trigger.attr("href");
					$("#myModalLabel").text(dialogTitle);
					$(".modal-body").html('Loading.').load(dialogDestination);
					$(".extraModalButton").hide();
					$("#modalDialog").modal("show");
					return false;
				});
			});
		},

		getQuerystringParameters: function(){
			let vars = [];
			let q = location.search.substr(1);
			if(q !== undefined){
				q = q.split('&');
				for(var i = 0; i < q.length; i++){
					var hash = q[i].split('=');
					vars[hash[0]] = hash[1];
				}
			}
			return vars;
		},

		//// Quick Way to get a single URL parameter value (parameterName must be in the url query string)
		//getQueryParameterValue: function (parameterName) {
		//	return location.search.split(parameterName + '=')[1].split('&')[0]
		//},

		replaceQueryParam : function (param, newValue, search) {
			if (typeof search == 'undefined') search = location.search;
			let regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
			let query = search.replace(regex, "$1").replace(/&$/, '');
			return newValue ? (query.length > 2 ? query + "&" : "?") + param + "=" + newValue : query;
		},

		getSelectedTitles: function(){
			let selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length === 0){
				let ret = confirm('You have not selected any items, process all items?');
				if (ret === true){
					let titleSelect = $("input.titleSelect");
					titleSelect.attr('checked', 'checked');
					selectedTitles = titleSelect.map(function() {
						return $(this).attr('name') + "=" + $(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
		},

		pwdToText: function(fieldId){
			let elem = document.getElementById(fieldId);
			let input = document.createElement('input');
			input.id = elem.id;
			input.name = elem.name;
			input.value = elem.value;
			input.size = elem.size;
			input.onfocus = elem.onfocus;
			input.onblur = elem.onblur;
			input.className = elem.className;
			input.maxLength = elem.maxLength;
			if (elem.type === 'text' ){
				input.type = 'password';
			} else {
				input.type = 'text';
			}

			elem.parentNode.replaceChild(input, elem);
			return input;
		},

		setupFieldSetToggles: function (){
			$('legend.collapsible').each(function(){
				$(this).siblings().hide()
				.addClass("collapsed")
				.click(function() {
					$(this).toggleClass("expanded collapsed")
					.siblings().slideToggle();
					return false;
				});
			});

			$('fieldset.fieldset-collapsible').each(function() {
				let collapsible = $(this);
				let legend = collapsible.find('legend:first');
				legend.addClass('fieldset-collapsible-label').bind('click', {collapsible: collapsible}, function(event) {
					let collapsible = event.data.collapsible;
					if (collapsible.hasClass('fieldset-collapsed')) {
						collapsible.removeClass('fieldset-collapsed');
					} else {
						collapsible.addClass('fieldset-collapsed');
					}
				});
				// Init.
				collapsible.addClass('fieldset-collapsed');
			});
		},

		showMessage: function(title, body, autoClose, refreshAfterClose){
			// if autoclose is set as number greater than 1 autoClose will be the custom timeout interval in milliseconds, otherwise
			//	 autoclose is treated as an on/off switch. Default timeout interval of 3 seconds.
			// if refreshAfterClose is set but not autoClose, the page will reload when the box is closed by the user.
			if (autoClose === undefined){
				autoClose = false;
			}
			if (refreshAfterClose === undefined){
				refreshAfterClose = false;
			}
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html('');
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
			if (autoClose) {
				setTimeout(function(){
					if (refreshAfterClose) location.reload(true);
					else AspenDiscovery.closeLightbox();
				}, autoClose > 1 ? autoClose : 3000);
			}else if (refreshAfterClose) {
				modalDialog.on('hide.bs.modal', function(){
					location.reload(true)
				})
			}
		},

		showMessageWithButtons: function(title, body, buttons){
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html(buttons);
			$("#modalDialog").modal('show');
		},

		// common loading message for lightbox while waiting for AJAX processes to complete.
		loadingMessage: function() {
			AspenDiscovery.showMessage('Loading', 'Loading, please wait.')
		},

		// common message for when an AJAX call has failed.
		ajaxFail: function() {
			if (!Globals.LeavingPage) AspenDiscovery.showMessage('Request Failed', 'There was an error with this AJAX Request.');
		},

		showElementInPopup: function(title, elementId, buttonsElementId){
			// buttonsElementId is optional
			let modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				AspenDiscovery.closeLightbox(function(){AspenDiscovery.showElementInPopup(title, elementId)});
			}else{
				$(".modal-title").html(title);
				let elementText = $(elementId).html();
				let elementButtons = buttonsElementId ? $(buttonsElementId).html() : '';
				$(".modal-body").html(elementText);
				$('.modal-buttons').html(elementButtons);

				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(){
			let selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			let toggle = $(toggleSelector);
			let value = toggle.prop('checked');
			$(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode === 13){
				$(formToSubmit).submit();
			}
		},

		hasLocalStorage: function () {
			// arguments.callee.haslocalStorage is the function's "static" variable for whether or not we have tested the
			// that the localStorage system is available to us.

			//console.log(typeof arguments.callee.haslocalStorage);
			if(typeof arguments.callee.haslocalStorage == "undefined") {
				if ("localStorage" in window) {
					try {
						window.localStorage.setItem('_tmptest', 'temp');
						arguments.callee.haslocalStorage = (window.localStorage.getItem('_tmptest') === 'temp');
						// if we get the same info back, we are good. Otherwise, we don't have localStorage.
						window.localStorage.removeItem('_tmptest');
					} catch(error) { // something failed, so we don't have localStorage available.
						arguments.callee.haslocalStorage = false;
					}
				} else arguments.callee.haslocalStorage = false;
			}
			return arguments.callee.haslocalStorage;
		},

		saveLanguagePreferences:function(){
			let preference = $("#searchPreferenceLanguage option:selected").val();
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
				method : 'saveLanguagePreference',
				searchPreferenceLanguage : preference
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						if (data.message.length > 0){
							//User was logged in, show a message about how to update
							AspenDiscovery.showMessage('Success', data.message, true, true);
						}else{
							//Refresh the page
							// noinspection SillyAssignmentJS
							window.location.href = window.location.href;
						}
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		setLanguage: function(selectedLanguage) {
			//Update the user interface with the selected language
			if (selectedLanguage === undefined) {
				selectedLanguage = $("#selected-language option:selected").val();
			}
			let curLocation = window.location.href;
			let newParam = 'myLang=' + selectedLanguage;
			if (curLocation.indexOf(newParam) === -1){
				let newLocation = curLocation.replace(new RegExp('([?&])myLang=(.*?)(?:&|$)'), '$1' + newParam);
				if (newLocation === curLocation){
					newLocation = AspenDiscovery.buildUrl(curLocation, 'myLang', selectedLanguage);
				}
				window.location.href = newLocation;
			}

			return false;
		},

		showTranslateForm: function(termId) {
			let url = Globals.path + "/AJAX/JSON?method=getTranslationForm&termId=" + termId;
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		saveTranslation: function(){
			let termId = $("#termId").val();
			let translationId = $("#translationId").val();
			let translation = $("#translation").val();
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
				method : 'saveTranslation',
				translationId : translationId,
				translation : translation
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						$(".term_" + termId ).html(translation);
						$(".translation_id_" + translationId ).removeClass('not_translated').addClass("translated");
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
		},
		deleteTranslationTerm: function(termId) {
			let url = Globals.path + "/AJAX/JSON";
			let params =  {
				method : 'deleteTranslationTerm',
				termId : termId
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						$("#term_" + termId ).hide();
						AspenDiscovery.closeLightbox();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		toggleMenu: function() {
			let headerMenu = $('#header-menu');
			let menuButton = $('#menuToggleButton > a');
			let menuButtonIcon = $('#menuToggleButton > a > i');
			if (headerMenu.is(':visible')){
				this.closeMenu();
			}else{
				this.closeAccountMenu();
				menuButton.addClass('selected');
				headerMenu.slideDown('slow');
				menuButtonIcon.removeClass('fa-bars');
				menuButtonIcon.addClass('fa-times');
			}
			return false;
		},
		closeMenu: function(){
			let headerMenu = $('#header-menu');
			let menuButton = $('#menuToggleButton > a');
			let menuButtonIcon = $('#menuToggleButton > a > i');
			headerMenu.slideUp('slow');
			menuButtonIcon.addClass('fa-bars');
			menuButtonIcon.removeClass('fa-times');
			menuButton.removeClass('selected');
		},
		toggleMenuSection: function(categoryName) {
			let menuSectionHeaderIcon = $('#' + categoryName + "MenuSection > i");
			let menuSectionBody = $('#' + categoryName + "MenuSectionBody");
			if (menuSectionBody.is(':visible')){
				menuSectionBody.slideUp();
				menuSectionHeaderIcon.addClass('fa-caret-right');
				menuSectionHeaderIcon.removeClass('fa-caret-down');
			}else{
				menuSectionBody.slideDown();
				menuSectionHeaderIcon.removeClass('fa-caret-right');
				menuSectionHeaderIcon.addClass('fa-caret-down');
			}
			return false;
		},
		toggleAccountMenu: function() {
			let accountMenu = $('#account-menu');
			let accountMenuButton = $('#accountMenuToggleButton > a');
			if (accountMenu.is(':visible')){
				this.closeAccountMenu();
			}else{
				this.closeMenu();
				accountMenuButton.addClass('selected');
				accountMenu.slideDown('slow');
			}
			return false;
		},
		closeAccountMenu: function(){
			let accountMenu = $('#account-menu');
			let accountMenuButton = $('#accountMenuToggleButton > a');
			accountMenu.slideUp('slow');
			accountMenuButton.removeClass('selected');
		}
	}

}(AspenDiscovery || {}));

jQuery.validator.addMethod("multiemail", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	let emails = value.split(/[,;]/);
	let valid = true;
	for (let i = 0, limit = emails.length; i < limit; i++) {
		value = emails[i];
		valid = valid && jQuery.validator.methods.email.call(this, value, element);
	}
	return valid;
}, "Invalid email format: please use a comma to separate multiple email addresses.");

/**
 *  Modified from above code, for Aspen Discovery self registration form.
 *
 * Return true, if the value is a valid date, also making this formal check mm-dd-yyyy.
 *
 * @example jQuery.validator.methods.date("01-01-1900")
 * @result true
 *
 * @example jQuery.validator.methods.date("01-13-1990")
 * @result false
 *
 * @example jQuery.validator.methods.date("01.01.1900")
 * @result false
 *
 * @example <input name="pippo" class="{dateAspen:true}" />
 * @desc Declares an optional input element whose value must be a valid date.
 *
 * @name jQuery.validator.methods.dateAspen
 * @type Boolean
 * @cat Plugins/Validate/Methods
 */
jQuery.validator.addMethod(
	"dateAspen",
	function(value, element) {
		let check = false;
		let re = /^\d{1,2}(-)\d{1,2}(-)\d{4}$/;
		if( re.test(value)){
			let adata = value.split('-');
			let mm = parseInt(adata[0],10);
			let dd = parseInt(adata[1],10);
			let aaaa = parseInt(adata[2],10);
			let xdata = new Date(aaaa,mm-1,dd);
			if ( ( xdata.getFullYear() == aaaa ) && ( xdata.getMonth () == mm - 1 ) && ( xdata.getDate() == dd ) )
				check = true;
			else
				check = false;
		} else
			check = false;
		return this.optional(element) || check;
	},
	"Please enter a correct date"
);

$.validator.addMethod('repeat', function(value, element){
	if(element.id.lastIndexOf('Repeat') === element.id.length - 6) {
		let idOriginal = element.id.slice(0,-6);
		let valueOriginal = $('#' + idOriginal).val();
		return value === valueOriginal;
	}
}, "Repeat fields do not match");
AspenDiscovery.Account = (function(){

	// noinspection JSUnusedGlobalSymbols
	return {
		ajaxCallback: null,
		closeModalOnAjaxSuccess: false,
		showCovers: null,

		addAccountLink: function(){
			const url = Globals.path + "/MyAccount/AJAX?method=getAddAccountLinkForm";
			AspenDiscovery.Account.ajaxLightbox(url, true);
		},

		/**
		 * Creates a new list in the system for the active user.
		 *
		 * Called from createListForm.tpl
		 * @returns {boolean}
		 */
		addList: function(){
			let form = $("#addListForm");
			let source = form.find("input[name=source]").val();
			let sourceId = form.find("input[name=sourceId]").val();
			let isPublic = form.find("#public").prop("checked");
			let title = form.find("input[name=title]").val();
			let desc = $("#listDesc").val();
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				'method':'addList',
				title: title,
				public: isPublic,
				desc: desc,
				source: source,
				sourceId: sourceId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params,function (data) {
				if (data.success) {
					AspenDiscovery.showMessage("Added Successfully", data.message, true, false);
					AspenDiscovery.Account.loadListData();
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		/**
		 * Do an ajax process, but only if the user is logged in.
		 * If the user is not logged in, force them to login and then do the process.
		 * Can also be called without the ajax callback to just login and not go anywhere
		 *
		 * @param trigger
		 * @param ajaxCallback
		 * @param closeModalOnAjaxSuccess
		 * @returns {boolean}
		 */
		ajaxLogin: function (trigger, ajaxCallback, closeModalOnAjaxSuccess) {
			if (Globals.loggedIn) {
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					ajaxCallback();
				} else if (AspenDiscovery.Account.ajaxCallback != null && typeof(AspenDiscovery.Account.ajaxCallback) === "function") {
					AspenDiscovery.Account.ajaxCallback();
					AspenDiscovery.Account.ajaxCallback = null;
				}
			} else {
				let multiStep = false;
				let loginLink = false;
				if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
					multiStep = true;
				}
				AspenDiscovery.Account.ajaxCallback = ajaxCallback;
				AspenDiscovery.Account.closeModalOnAjaxSuccess = closeModalOnAjaxSuccess;
				let dialogTitle = "Login";
				if (trigger !== undefined && trigger !== null) {
					dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					loginLink = trigger.data('login');
				}
				let dialogDestination = Globals.path + '/MyAccount/AJAX?method=getLoginForm';
				if (multiStep && !loginLink){
					dialogDestination += "&multiStep=true";
				}
				let modalDialog = $("#modalDialog");
				$('.modal-body').html("Loading...");
				$(".modal-content").load(dialogDestination);
				$(".modal-title").text(dialogTitle);
				modalDialog.modal("show");
			}
			return false;
		},

		changeLinkedAccount: function(){
			let patronId = $("#patronId option:selected").val();
			document.location.href = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'patronId', patronId);
		},

		exportCheckouts: function(source, sort){
			let url = Globals.path + "/MyAccount/AJAX?method=exportCheckouts&source=" + source;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			document.location.href = url;
			return false;
		},

		exportHolds: function(source, availableHoldsSort, unavailableHoldsSort){
			let url = Globals.path + "/MyAccount/AJAX?method=exportHolds&source=" + source;
			if (availableHoldsSort !== undefined){
				url += "&availableHoldsSort=" + availableHoldsSort;
			}
			if (unavailableHoldsSort !== undefined){
				url += "&unavailableHoldsSort=" + unavailableHoldsSort;
			}
			document.location.href = url;
			return false;
		},

		followLinkIfLoggedIn: function (trigger, linkDestination) {
			if (trigger === undefined) {
				alert("You must provide the trigger to follow a link after logging in.");
			}
			let jqTrigger = $(trigger);
			if (linkDestination === undefined) {
				linkDestination = jqTrigger.attr("href");
			}
			this.ajaxLogin(jqTrigger, function () {
				document.location = linkDestination;
			}, true);
			return false;
		},

		loadCheckouts: function(source, sort, showCovers){
			let url = Globals.path + "/MyAccount/AJAX?method=getCheckouts&source=" + source;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			let stateObj = {
				page: 'Checkouts',
				source: source,
				sort: sort,
				showCovers: showCovers
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href ){
				let label = 'Checkouts';
				if (source === 'ils'){
					label = 'Physical Checkouts';
				}else if (source === 'overdrive'){
					label = 'OverDrive Checkouts';
				}else if (source === 'hoopla'){
					label = 'Hoopla Checkouts';
				}else if (source === 'rbdigital'){
					label = 'RBdigital Checkouts';
				}else if (source === 'cloud_library'){
					label = 'Cloud Library Checkouts';
				}else if (source === 'axis_360'){
					label = 'Axis 360 Checkouts';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#" + source + "CheckoutsPlaceholder").html(data.checkouts);
				}else{
					$("#" + source + "CheckoutsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadHolds: function(source, availableHoldSort, unavailableHoldSort, showCovers){
			let url = Globals.path + "/MyAccount/AJAX?method=getHolds&source=" + source;
			if (availableHoldSort !== undefined){
				url += "&availableHoldSort=" + availableHoldSort;
			}
			if (unavailableHoldSort !== undefined){
				url += "&unavailableHoldSort=" + unavailableHoldSort;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			let stateObj = {
				page: 'Holds',
				source: source,
				availableHoldSort: availableHoldSort,
				unavailableHoldSort: unavailableHoldSort,
				showCovers: showCovers
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'source', source);
			if (document.location.href ){
				let label = 'Holds';
				if (source === 'ils'){
					label = 'Physical Holds';
				}else if (source === 'overdrive'){
					label = 'OverDrive Holds';
				}else if (source === 'rbdigital'){
					label = 'RBdigital Holds';
				}else if (source === 'axis_360'){
					label = 'Axis 360 Holds';
				}
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#" + source + "HoldsPlaceholder").html(data.holds);
				}else{
					$("#" + source + "HoldsPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadReadingHistory: function(selectedUser, sort, page, showCovers, filter){
			let url = Globals.path + "/MyAccount/AJAX?method=getReadingHistory&patronId=" + selectedUser;
			if (sort !== undefined){
				url += "&sort=" + sort;
			}
			if (page !== undefined){
				url += "&page=" + page;
			}else{
				page = 1;
			}
			if (showCovers !== undefined){
				url += "&showCovers=" + showCovers;
			}
			if (filter !== undefined){
				url += "&readingHistoryFilter=" + filter;
			}
			let stateObj = {
				page: 'ReadingHistory',
				pageNumber: page,
				selectedUser: selectedUser,
				sort: sort,
				showCovers: showCovers,
				readingHistoryFilter: filter,
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'selectedUser', selectedUser);
			newUrl = AspenDiscovery.buildUrl(newUrl, 'page', page);
			if (filter !== undefined){
				newUrl = AspenDiscovery.buildUrl(newUrl, 'readingHistoryFilter', filter);
			}
			if (document.location.href ){
				let label = 'Reading History page ' . page;
				history.pushState(stateObj, label, newUrl);
			}
			document.body.style.cursor = "wait";
			// noinspection JSUnresolvedFunction
			$.getJSON(url, function(data){
				document.body.style.cursor = "default";
				if (data.success){
					$("#readingHistoryListPlaceholder").html(data.readingHistory);
				}else{
					$("#readingHistoryListPlaceholder").html(data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		loadListData: function (){
			let url = Globals.path + "/MyAccount/AJAX?method=getListData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function(data){
				$("#lists-placeholder").html(data.lists);
			});
			return false;
		},

		loadRatingsData: function (){
			let url = Globals.path + "/MyAccount/AJAX?method=getRatingsData&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(url, function(data){
				$(".ratings-placeholder").html(data.ratings);
				$(".recommendations-placeholder").html(data.recommendations);
			});
			return false;
		},

		loadMenuData: function (){
			let ilsUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataIls&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			let totalCheckouts = 0;
			let totalHolds = 0;
			$.getJSON(ilsUrl, function(data){
				if (data.success) {
					$(".ils-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					if (data.summary.numOverdue > 0) {
						$(".ils-overdue-placeholder").html(data.summary.numOverdue);
						$(".ils-overdue").show();
					}
					$(".ils-holds-placeholder").html(data.summary.numHolds);
					totalHolds += parseInt(data.summary.numHolds);
					$(".holds-placeholder").html(totalHolds);
					if (data.summary.numAvailableHolds > 0) {
						$(".ils-available-holds-placeholder").html(data.summary.numAvailableHolds);
						$(".ils-available-holds").show();
					}
					$(".readingHistory-placeholder").html(data.summary.readingHistory);
					$(".materialsRequests-placeholder").html(data.summary.materialsRequests);
					$(".bookings-placeholder").html(data.summary.bookings);
					$(".expirationFinesNotice-placeholder").html(data.summary.expirationFinesNotice);
				}
			});
			let rbdigitalUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataRBdigital&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(rbdigitalUrl, function(data){
				if (data.success) {
					$(".rbdigital-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					$(".rbdigital-holds-placeholder").html(data.summary.numUnavailableHolds);
					totalHolds += parseInt(data.summary.numUnavailableHolds);
					$(".holds-placeholder").html(totalHolds);
				}
			});
			let cloudLibraryUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataCloudLibrary&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(cloudLibraryUrl, function(data){
				if (data.success) {
					$(".cloud_library-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					$(".cloud_library-holds-placeholder").html(data.summary.numHolds);
					totalHolds += parseInt(data.summary.numHolds);
					$(".holds-placeholder").html(totalHolds);
					if (data.summary.numAvailableHolds > 0) {
						$(".cloud_library-available-holds-placeholder").html(data.summary.numAvailableHolds);
						$(".cloud_library-available-holds").show();
					}
				}
			});
			let hooplaUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataHoopla&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(hooplaUrl, function(data){
				if (data.success) {
					$(".hoopla-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
				}
			});
			let overdriveUrl = Globals.path + "/MyAccount/AJAX?method=getMenuDataOverDrive&activeModule=" + Globals.activeModule + '&activeAction=' + Globals.activeAction;
			$.getJSON(overdriveUrl, function(data){
				if (data.success) {
					$(".overdrive-checkouts-placeholder").html(data.summary.numCheckedOut);
					totalCheckouts += parseInt(data.summary.numCheckedOut);
					$(".checkouts-placeholder").html(totalCheckouts);
					$(".overdrive-holds-placeholder").html(data.summary.numHolds);
					totalHolds += parseInt(data.summary.numHolds);
					$(".holds-placeholder").html(totalHolds);
					if (data.summary.numAvailableHolds > 0) {
						$(".overdrive-available-holds-placeholder").html(data.summary.numAvailableHolds);
						$(".overdrive-available-holds").show();
					}
				}
			});

			return false;
		},

		preProcessLogin: function (){
			let username = $("#username").val(),
				password = $("#password").val(),
				loginErrorElem = $('#loginError');
			if (!username || !password) {
				loginErrorElem
						.text($("#missingLoginPrompt").text())
						.show();
				return false;
			}
			if (AspenDiscovery.hasLocalStorage()){
				let rememberMe = $("#rememberMe").prop('checked');
				let showPwd = $('#showPwd').prop('checked');
				if (rememberMe){
					window.localStorage.setItem('lastUserName', username);
					window.localStorage.setItem('lastPwd', password);
					window.localStorage.setItem('showPwd', showPwd);
					window.localStorage.setItem('rememberMe', rememberMe);
				}else{
					window.localStorage.removeItem('lastUserName');
					window.localStorage.removeItem('lastPwd');
					window.localStorage.removeItem('showPwd');
					window.localStorage.removeItem('rememberMe');
				}
			}
			return true;
		},

		processAjaxLogin: function (ajaxCallback) {
			if(this.preProcessLogin()) {
				let username = $("#username").val();
				let password = $("#password").val();
				let rememberMe = $("#rememberMe").prop('checked');
				let loginErrorElem = $('#loginError');
				let loadingElem = $('#loading');
				let url = Globals.path + "/AJAX/JSON?method=loginUser";
				let params = {username: username, password: password, rememberMe: rememberMe};
				if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
					let showCovers = window.localStorage.getItem('showCovers') || false;
					if (showCovers && showCovers.length > 0) { // if there is a set value, pass it back with the login info
						params.showCovers = showCovers
					}
				}
				loginErrorElem.hide();
				loadingElem.show();
				// noinspection JSUnresolvedFunction
				$.post(url, params, function(response){
					loadingElem.hide();
					if (response.result.success === true) {
						// Hide "log in" options and show "log out" options:
						$('.loginOptions, #loginOptions').hide();
						$('.logoutOptions, #logoutOptions').show();

						// Show user name on page in case page doesn't reload
						let name = $.trim(response.result.name);
						//name = 'Logged In As ' + name.slice(0, name.lastIndexOf(' ') + 2) + '.';
						name = 'Logged In As ' + name.slice(0, 1) + '. ' + name.slice(name.lastIndexOf(' ') + 1, name.length) + '.';
						$('#side-bar #myAccountNameLink').html(name);

						if (AspenDiscovery.Account.closeModalOnAjaxSuccess) {
							AspenDiscovery.closeLightbox();
						}

						Globals.loggedIn = true;
						if (ajaxCallback !== undefined && typeof(ajaxCallback) === "function") {
							ajaxCallback();
						} else if (AspenDiscovery.Account.ajaxCallback !== undefined && typeof(AspenDiscovery.Account.ajaxCallback) === "function") {
							AspenDiscovery.Account.ajaxCallback();
							AspenDiscovery.Account.ajaxCallback = null;
						}
					} else {
						loginErrorElem.text(response.result.message).show();
					}
				}, 'json').fail(function(){
					loginErrorElem.text("There was an error processing your login, please try again.").show();
				})
			}
			return false;
		},

		processAddLinkedUser: function (){
			if(this.preProcessLogin()) {
				let username = $("#username").val();
				let password = $("#password").val();
				let loginErrorElem = $('#loginError');
				let url = Globals.path + "/MyAccount/AJAX?method=addAccountLink";
				loginErrorElem.hide();
				$.ajax({
					url: url,
					data: {username: username, password: password},
					success: function (response) {
						if (response.result === true) {
							AspenDiscovery.showMessage("Account to Manage", response.message ? response.message : "Successfully linked the account.", true, true);
						} else {
							loginErrorElem.text(response.message);
							loginErrorElem.show();
						}
					},
					error: function () {
						loginErrorElem.text("There was an error processing the account, please try again.")
								.show();
					},
					dataType: 'json',
					type: 'post'
				});
			}
			return false;
		},


		removeLinkedUser: function(idToRemove){
			if (confirm("Are you sure you want to stop managing this account?")){
				let url = Globals.path + "/MyAccount/AJAX?method=removeAccountLink&idToRemove=" + idToRemove;
				$.getJSON(url, function(data){
					if (data.result === true){
						AspenDiscovery.showMessage('Linked Account Removed', data.message, true, true);
					}else{
						AspenDiscovery.showMessage('Unable to Remove Account Link', data.message);
					}
				});
			}
			return false;
		},

		renewTitle: function(patronId, recordId, renewIndicator) {
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId + "&renewIndicator="+renewIndicator, function(data){
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success); // automatically close when successful
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					this.renewTitle(renewIndicator);
				}, false)
			}
			return false;
		},

		renewAll: function() {
			if (Globals.loggedIn) {
				if (confirm('Renew All Items?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewAll", function (data) {
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success);
						// automatically close when all successful
						if (data.success || data.renewed > 0) {
							// Refresh page on close when a item has been successfully renewed, otherwise stay
							// noinspection JSUnusedLocalSymbols
							$("#modalDialog").on('hidden.bs.modal', function (e) {
								location.reload();
							});
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, this.renewAll, true);
				//auto close so that if user opts out of renew, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false;
		},

		renewSelectedTitles: function () {
			if (Globals.loggedIn) {
				let selectedTitles = AspenDiscovery.getSelectedTitles();
				if (selectedTitles) {
					if (confirm('Renew selected Items?')) {
						AspenDiscovery.loadingMessage();
						// noinspection JSUnresolvedFunction
						$.getJSON(Globals.path + "/MyAccount/AJAX?method=renewSelectedItems&" + selectedTitles, function (data) {
							let reload = data.success || data.renewed > 0;
							AspenDiscovery.showMessage(data.title, data.modalBody, data.success, reload);
						}).fail(AspenDiscovery.ajaxFail);
					}
				}
			} else {
				this.ajaxLogin(null, this.renewSelectedTitles, true);
				 //auto close so that if user opts out of renew, the login window closes; if the users continues, follow-up operations will reopen modal
			}
			return false
		},

		ajaxLightbox: function (urlToDisplay, requireLogin) {
			if (requireLogin === undefined) {
				requireLogin = false;
			}
			if (requireLogin && !Globals.loggedIn) {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Account.ajaxLightbox(urlToDisplay, requireLogin);
				}, false);
			} else {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(urlToDisplay, function(data){
					if (data.success){
						data = data.result;
					}
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		confirmCancelHold: function(patronId, recordId, holdIdToCancel) {
			AspenDiscovery.loadingMessage();
			// noinspection JSUnresolvedFunction
			$.getJSON(Globals.path + "/MyAccount/AJAX?method=confirmCancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons); // automatically close when successful
			}).fail(AspenDiscovery.ajaxFail);

			return false
		},

		cancelHold: function(patronId, recordId, holdIdToCancel){
			if (Globals.loggedIn) {
				AspenDiscovery.loadingMessage();
				// noinspection JSUnresolvedFunction
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + recordId + "&cancelId="+holdIdToCancel, function(data){
					AspenDiscovery.showMessage(data.title, data.body, data.success);
					if (data.success){
						let tmpRecordId = recordId.replace('.', '_').replace('~', '_');
						let tmpHoldIdToCancel = holdIdToCancel.replace('.', '_').replace('~', '_');
						let holdClass = '.ilsHold_' + tmpRecordId + '_' + tmpHoldIdToCancel;
						$(holdClass).hide();
						AspenDiscovery.Account.loadMenuData();
					}
				}).fail(AspenDiscovery.ajaxFail)
			} else {
				this.ajaxLogin(null, function () {
					AspenDiscovery.Account.cancelHold(patronId, recordId, holdIdToCancel)
				}, false);
			}

			return false
		},

		cancelBooking: function(patronId, cancelId){
			if (confirm("Are you sure you want to cancel this scheduled item?")){
				if (Globals.loggedIn) {
					AspenDiscovery.loadingMessage();
					let c = {};
					c[patronId] = cancelId;
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX", {method:"cancelBooking", cancelId:c}, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled item from page
							let escapedId = cancelId.replace(/:/g, "\\:"); // needed for jquery selector to work correctly
							// first backslash for javascript escaping, second for css escaping (within jquery)
							$('div.result').has('#selected'+escapedId).remove();
						}
					}).fail(AspenDiscovery.ajaxFail)
				} else {
					this.ajaxLogin(null, function () {
						AspenDiscovery.Account.cancelBooking(cancelId)
					}, false);
				}
			}

			return false
		},

		cancelSelectedBookings: function(){
			if (Globals.loggedIn) {
				let selectedTitles = this.getSelectedTitles();
				let numBookings = $("input.titleSelect:checked").length;
				// if numBookings equals 0, quit because user has canceled in getSelectedTitles()
				if (numBookings > 0 && confirm('Cancel ' + numBookings + ' selected scheduled item' + (numBookings > 1 ? 's' : '') + '?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&"+selectedTitles, function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect:checked").closest('div.result').remove();
						} else {
							if (data.failed) { // remove items that didn't fail
								let searchArray = data.failed.map(function(ele){return ele.toString()});
								// convert any number values to string, this is needed bcs inArray() below does strict comparisons
								// & id will be a string. (sometimes the id values are of type number )
								$("input.titleSelect:checked").each(function(){
									let id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
									if ($.inArray(id, searchArray) === -1) // if the item isn't one of the failed cancels, get rid of its containing div.
										$(this).closest('div.result').remove();
								});
							}
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, AspenDiscovery.Account.cancelSelectedBookings, false);
			}
			return false;

		},

		cancelAllBookings: function(){
			if (Globals.loggedIn) {
				if (confirm('Cancel all of your scheduled items?')) {
					AspenDiscovery.loadingMessage();
					// noinspection JSUnresolvedFunction
					$.getJSON(Globals.path + "/MyAccount/AJAX?method=cancelBooking&cancelAll=1", function(data){
						AspenDiscovery.showMessage(data.title, data.modalBody, data.success); // automatically close when successful
						if (data.success) {
							// remove canceled items from page
							$("input.titleSelect").closest('div.result').remove();
						} else {
							if (data.failed) { // remove items that didn't fail
								let searchArray = data.failed.map(function (ele) {
									return ele.toString()
								});
								// convert any number values to string, this is needed bcs inArray() below does strict comparisons
								// & id will be a string. (sometimes the id values are of type number )
								$("input.titleSelect").each(function () {
									let id = $(this).attr('id').replace(/selected/g, ''); //strip down to just the id part
									if ($.inArray(id, searchArray) === -1) // if the item isn't one of the failed cancels, get rid of its containing div.
										$(this).closest('div.result').remove();
								});
							}
						}
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				this.ajaxLogin(null, AspenDiscovery.Account.cancelAllBookings, false);
			}
			return false;
		},

		changeAccountSort: function (newSort, sortParameterName){
			if (typeof sortParameterName === 'undefined') {
				sortParameterName = 'accountSort'
			}
			let paramString = AspenDiscovery.replaceQueryParam(sortParameterName, newSort);
			location.replace(location.pathname + paramString)
		},

		changeHoldPickupLocation: function (patronId, recordId, holdId, currentLocation){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/MyAccount/AJAX?method=getChangeHoldLocationForm&patronId=" + patronId + "&recordId=" + recordId + "&holdId=" + holdId + "&currentLocation=" + currentLocation, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					return AspenDiscovery.Account.changeHoldPickupLocation(patronId, recordId, holdId, currentLocation);
				}, false);
			}
			return false;
		},

		deleteSearch: function(searchId){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Searches.saveSearch(searchId);
				}, false);
			}else{
				let url = Globals.path + "/MyAccount/AJAX";
				let params = "method=deleteSearch&searchId=" + encodeURIComponent(searchId);
				$.getJSON(url + '?' + params,
					function(data) {
						if (data.result) {
							AspenDiscovery.showMessage("Success", data.message);
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
				);
			}
			return false;
		},

		doChangeHoldLocation: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				'method': 'changeHoldLocation'
				,patronId : $('#patronId').val()
				,recordId : $('#recordId').val()
				,holdId : $('#holdId').val()
				,newLocation : $('#newPickupLocation').val()
			};

			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		freezeHold: function(patronId, recordId, holdId, promptForReactivationDate, caller){
			AspenDiscovery.loadingMessage();
			let url = Globals.path + '/MyAccount/AJAX';
			let params = {
				patronId : patronId
				,recordId : recordId
				,holdId : holdId
			};
			if (promptForReactivationDate){
				//Prompt the user for the date they want to reactivate the hold
				params['method'] = 'getReactivationDateForm'; // set method for this form
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
				}).fail(AspenDiscovery.ajaxFail);

			}else{
				let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
				AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
				params['method'] = 'freezeHold'; //set method for this ajax call
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					if (data.success) {
						AspenDiscovery.showMessage("Success", data.message, true, true);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			let params = {
				'method' : 'freezeHold'
				,patronId : $('#patronId').val()
				,recordId : $('#recordId').val()
				,holdId : $("#holdId").val()
				,reactivationDate : $("#reactivationDate").val()
			};
			let url = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		getSelectedTitles: function(promptForSelectAll){
			if (promptForSelectAll === undefined){
				promptForSelectAll = true;
			}
			let selectedTitles = $("input.titleSelect:checked ");
			if (selectedTitles.length === 0 && promptForSelectAll && confirm('You have not selected any items, process all items?')) {
				selectedTitles = $("input.titleSelect")
					.attr('checked', 'checked');
			}
			// noinspection UnnecessaryLocalVariableJS
			let queryString = selectedTitles.map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");

			return queryString;
		},

		saveSearch: function(searchId){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Account.saveSearch(searchId);
				}, false);
			}else{
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {method :'saveSearch', searchId :searchId};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params,
						function(data){
							if (data.result) {
								AspenDiscovery.showMessage("Success", data.message);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		showCreateListForm: function(source, sourceId){
			if (Globals.loggedIn){
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {method:"getCreateListForm"};
				if (source !== undefined){
					params.source= source;
				}
				if (sourceId !== undefined){
					params.sourceId= sourceId;
				}
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($trigger, function(){
					return AspenDiscovery.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},

		thawHold: function(patronId, recordId, holdId, caller){
			let popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			let url = Globals.path + '/MyAccount/AJAX';
			let params = {
				'method' : 'thawHold'
				,patronId : patronId
				,recordId : recordId
				,holdId : holdId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		toggleShowCovers: function(showCovers){
			this.showCovers = showCovers;
			let paramString = AspenDiscovery.replaceQueryParam('showCovers', this.showCovers ? 'on': 'off'); // set variable
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
				window.localStorage.setItem('showCovers', this.showCovers ? 'on' : 'off');
			}
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		validateCookies: function(){
			if (navigator.cookieEnabled === false){
				$("#cookiesError").show();
			}
		},

		getMasqueradeForm: function () {
			AspenDiscovery.loadingMessage();
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {method:"getMasqueradeAsForm"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initiateMasquerade: function() {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method:"initiateMasquerade",
				cardNumber:$('#cardNumber').val()
			};
			$('#masqueradeAsError').hide();
			$('#masqueradeLoading').show();
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					location.href = Globals.path + '/MyAccount/Home';
				} else {
					$('#masqueradeLoading').hide();
					$('#masqueradeAsError').html(data.error).show();
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		endMasquerade: function () {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {method:"endMasquerade"};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).done(function(){
				location.href = Globals.path + '/MyAccount/Home';
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		dismissMessage: function(messageId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "dismissMessage",
				messageId: messageId
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		enableAccountLinking: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "enableAccountLinking",
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		stopAccountLinking: function(){
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "stopAccountLinking",
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		createPayPalOrder: function(finesFormId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "createPayPalOrder",
				patronId: $(finesFormId + " input[name=patronId]").val(),
				fineTotal: $(finesFormId + " input[name=totalToPay]").val(),
			};
			$(finesFormId + " .selectedFine:checked").each(
				function() {
					let name = $(this).attr('name');
					params[name] = $(this).val();

					let fineAmount = $(finesFormId + " #amountToPay" + $(this).data("fine_id"));
					if (fineAmount){
						params[fineAmount.attr('name')] = fineAmount.val();
					}
				}
			);
			let orderInfo = false;
			// noinspection JSUnresolvedFunction
			$.ajax({
				url: url,
				data: params,
				dataType: 'json',
				async: false,
				method: 'GET'
			}).success(
				function (response){
					if (response.success === false){
						AspenDiscovery.showMessage("Error", response.message);
						return false;
					}else{
						orderInfo = response.orderID;
					}
				}
			).fail(AspenDiscovery.ajaxFail);

			return orderInfo;
		},

		completePayPalOrder: function(orderId, patronId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "completePayPalOrder",
				patronId: patronId,
				orderId: orderId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage('Thank you', 'Your payment was processed successfully, thank you', false, true);
				} else {
					AspenDiscovery.showMessage('Error', 'Unable to process your payment, please visit the library with your receipt', false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},
		updateFineTotal: function(finesFormId, userId, paymentType) {
			let totalFineAmt = 0;
			let totalOutstandingAmt = 0;
			$(finesFormId + " .selectedFine:checked").each(
				function() {
					if (paymentType === "1"){
						totalFineAmt += $(this).data('fine_amt') * 1;
						totalOutstandingAmt += $(this).data('outstanding_amt') * 1;
					}else{
						let fineId = $(this).data('fine_id');
						let fineAmountInput = $("#amountToPay" + fineId);
						totalFineAmt += fineAmountInput.val() * 1;
						totalOutstandingAmt += fineAmountInput.val() * 1;
					}
				}
			);
			$('#formattedTotal' + userId).text("$" + totalFineAmt.toFixed(2));
			$('#formattedOutstandingTotal' + userId).text("$" + totalOutstandingAmt.toFixed(2));
		},
		dismissPlacard:function(patronId, placardId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "dismissPlacard",
				placardId: placardId,
				patronId: patronId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					$("#placard" + placardId).hide();
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		updateAutoRenewal:function(patronId) {
			let url = Globals.path + "/MyAccount/AJAX";
			let params = {
				method: "updateAutoRenewal",
				allowAutoRenewal: $('#allowAutoRenewal').prop("checked"),
				patronId: patronId,
			};
			// noinspection JSUnresolvedFunction
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage('Success', data.message, true);
				} else {
					AspenDiscovery.showMessage('Error', data.message, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showSaveToListForm:function (trigger, source, id) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {
					method: "getSaveToListForm",
					sourceId: id,
					source: source
				}
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.Account.showSaveToListForm(trigger, source, id);
				});
			}
			return false;
		},

		saveToList: function(){
			if (Globals.loggedIn){
				let url = Globals.path + "/MyAccount/AJAX";
				let params = {
					'method':'saveToList',
					'notes':$('#addToList-notes').val(),
					'listId':$('#addToList-list').val(),
					'source':$('#source').val(),
					'sourceId':$('#sourceId').val()
				};
				// noinspection JSUnresolvedFunction
				$.getJSON(url, params,function(data) {
					if (data.success) {
						AspenDiscovery.showMessage("Added Successfully", data.message, 2000); // auto-close after 2 seconds.
						AspenDiscovery.Account.loadListData();
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},
	};
}(AspenDiscovery.Account || {}));
AspenDiscovery.Admin = (function(){
	return {
		showRecordGroupingNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getRecordGroupingNotes&id=" + id, true);
			return false;
		},
		showReindexNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
			return false;
		},
		showCronNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
			return false;
		},
		showCronProcessNotes: function (id){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
			return false;
		},
		toggleCronProcessInfo: function (id){
			$("#cronEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},

		showExtractNotes: function (id, source){
			AspenDiscovery.Account.ajaxLightbox("/Admin/AJAX?method=getExtractNotes&id=" + id + "&source=" + source, true);
			return false;
		},
		loadGoogleFontPreview: function (fontSelector) {
			let fontElement = $("#" + fontSelector);
			let fontName = fontElement.val();

			$('head').append('<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=' + fontName + '">');
			$('#' + fontSelector + '-sample-text').css('font-family', fontName);
		},
		checkContrast: function (property1, property2,oneWay=false){
			let color1 = $('#' + property1).val();
			let color2 = $('#' + property2).val();
			if (color1.length === 7 && color2.length === 7){
				let luminance1 = AspenDiscovery.Admin.getLuminanceForColor(color1);
				let luminance2 = AspenDiscovery.Admin.getLuminanceForColor(color2);
				let contrastRatio = 0;
				if (luminance1 > luminance2) {
					contrastRatio = ((luminance1 + 0.05) / (luminance2 + 0.05));
				} else {
					contrastRatio = ((luminance2 + 0.05) / (luminance1 + 0.05));
				}
				let contrastSpan1 = $("#contrast_" + property1);
				let contrastSpan2 = $("#contrast_" + property2);
				contrastSpan1.text(contrastRatio.toFixed(2));
				contrastSpan2.text(contrastRatio.toFixed(2));
				if (contrastRatio < 3.5) {
					contrastSpan1.addClass("alert-danger");
					contrastSpan2.addClass("alert-danger");
					contrastSpan1.removeClass("alert-warning");
					contrastSpan2.removeClass("alert-warning");
					contrastSpan1.removeClass("alert-success");
					contrastSpan2.removeClass("alert-success");
				}else if (contrastRatio < 4.5) {
					contrastSpan1.removeClass("alert-danger");
					contrastSpan2.removeClass("alert-danger");
					contrastSpan1.addClass("alert-warning");
					contrastSpan2.addClass("alert-warning");
					contrastSpan1.removeClass("alert-success");
					contrastSpan2.removeClass("alert-success");
				}else{
					contrastSpan1.removeClass("alert-danger");
					contrastSpan2.removeClass("alert-danger");
					contrastSpan1.removeClass("alert-warning");
					contrastSpan2.removeClass("alert-warning");
					contrastSpan1.addClass("alert-success");
					contrastSpan2.addClass("alert-success");
				}
			}else{
				$("#contrastCheck_" + property1).hide();
				if (!oneWay) {
					$("#contrastCheck_" + property2).hide();
				}
				$("#contrast_" + property1).innerHTML = 'Unknown';
				if (!oneWay) {
					$("#contrast_" + property2).innerHTML = 'Unknown';
				}
			}

		},
		getLuminanceForColor: function(color){
			let r = AspenDiscovery.Admin.getLuminanceComponent(color, 1, 2);
			let g = AspenDiscovery.Admin.getLuminanceComponent(color, 3, 2);
			let b = AspenDiscovery.Admin.getLuminanceComponent(color, 5, 2);
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		},
		getLuminanceComponent: function(color, start, length){
			let component = parseInt(color.substring(start, start + length), 16) / 255;
			if (component <= 0.03928) {
				return component / 12.92;
			} else {
				return Math.pow((component + 0.055) / 1.055, 2.4);
			}
		},

		updateMaterialsRequestFields: function(){
			let materialRequestType = $("#enableMaterialsRequestSelect option:selected").val();
			if (materialRequestType === "0" || materialRequestType === "2"){
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide()
			}else if (materialRequestType === "1"){
				$("#propertyRowexternalMaterialsRequestUrl").hide();
				$("#propertyRowmaxRequestsPerYear").show();
				$("#propertyRowmaxOpenRequests").show();
				$("#propertyRowmaterialsRequestDaysToPreserve").show();
				$("#propertyRowmaterialsRequestFieldsToDisplay").show();
				$("#propertyRowmaterialsRequestFormats").show();
				$("#propertyRowmaterialsRequestFormFields").show()
			}else if (materialRequestType === "3"){
				$("#propertyRowexternalMaterialsRequestUrl").show();
				$("#propertyRowmaxRequestsPerYear").hide();
				$("#propertyRowmaxOpenRequests").hide();
				$("#propertyRowmaterialsRequestDaysToPreserve").hide();
				$("#propertyRowmaterialsRequestFieldsToDisplay").hide();
				$("#propertyRowmaterialsRequestFormats").hide();
				$("#propertyRowmaterialsRequestFormFields").hide()
			}
			return false;
		},
		validateCompare: function() {
			let selectedObjects = $('.selectedObject:checked');
			if (selectedObjects.length === 2){
				return true;
			}else{
				AspenDiscovery.showMessage("Error", "Please select only two objects to compare");
				return false;
			}
		},
		displayReleaseNotes: function() {
			let url = Globals.path + "/Admin/AJAX";
			let selectedNotes = $('#releaseSelector').val();
			let params =  {
				method : 'getReleaseNotes',
				release : selectedNotes
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						$("#releaseNotes").html(data.releaseNotes);
					} else {
						$("#releaseNotes").html("Error + " + data.message);
					}
				}
			).fail(AspenDiscovery.ajaxFail);
			return false;
		},
		updateBrowseSearchForSource: function () {
			let selectedSource = $('#sourceSelect').val();
			if (selectedSource === 'List') {
				$("#propertyRowsearchTerm").hide();
				$("#propertyRowdefaultFilter").hide();
				$("#propertyRowdefaultSort").hide();
				$("#propertyRowsourceListId").show();
			}else{
				$("#propertyRowsearchTerm").show();
				$("#propertyRowdefaultFilter").show();
				$("#propertyRowdefaultSort").show();
				$("#propertyRowsourceListId").hide();
			}
		},
		updateIndexingProfileFields: function () {
			let audienceType = $('#determineAudienceBySelect').val();
			if (audienceType === '3') {
				$("#propertyRowaudienceSubfield").show();
			}else{
				$("#propertyRowaudienceSubfield").hide();
			}
		}
	};
}(AspenDiscovery.Admin || {}));

AspenDiscovery.Archive = (function(){
	var date = new Date();
	date.setTime(date.getTime() + (1 /*days*/ * 24 * 60 * 60 * 1000));
	expires = "; expires=" + date.toGMTString();
	document.cookie = encodeURIComponent('exhibitNavigation') + "=" + encodeURIComponent(0) + expires + "; path=/";
	document.cookie = encodeURIComponent('collectionPid') + "=" + encodeURIComponent('') + expires + "; path=/";
	// document.cookie = encodeURIComponent('exhibitInAExhibitParentPid') + "=" + encodeURIComponent('') + expires + "; path=/";

	return {
		archive_map: null,
		archive_info_window: null,
		curPage: 1,
		markers: [],
		geomarkers: [],
		sort: 'title',
		openSeaDragonViewer: null,
		pageDetails: [],
		multiPage: false,
		allowPDFView: true,
		activeBookViewer: 'jp2',
		activeBookPage: null,
		activeBookPid: null,

		// Archive Collection Display Mode (different from search)
		displayMode: 'list', // default display Mode for collections
		displayModeClasses: { // browse mode to css class correspondence
			covers: 'home-page-browse-thumbnails',
			list: ''
		},

		getPreferredDisplayMode: function(){
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
				temp = window.localStorage.getItem('archiveCollectionDisplayMode');
				if (AspenDiscovery.Archive.displayModeClasses.hasOwnProperty(temp)) {
					AspenDiscovery.Archive.displayMode = temp; // if stored value is empty or a bad value, fall back on default setting ("null" is returned from local storage when not set)
				}
			}
		},

		toggleDisplayMode : function(selectedMode){
			var mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode; // check that selected mode is a valid option
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (!Globals.opac && AspenDiscovery.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('archiveCollectionDisplayMode', this.displayMode);
			}
			if (mode == 'list') $('#hideSearchCoversSwitch').show(); else $('#hideSearchCoversSwitch').hide();
			this.ajaxReloadCallback()
		},

		ajaxReloadCallback: function () {
			// Placeholder for the function that will be call when the display mode is toggled.
		},

		toggleShowCovers: function(showCovers){
			AspenDiscovery.Account.showCovers = showCovers;
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()) { // store setting in browser if not an opac computer
				window.localStorage.setItem('showCovers', this.showCovers ? 'on' : 'off');
			}
			this.ajaxReloadCallback()
		},


		openSeadragonViewerSettings: function(){
			return {
				"id": "custom-openseadragon",
				"prefixUrl": Globals.encodedRepositoryUrl + "\/sites\/all\/libraries\/openseadragon\/images\/",
				"debugMode": false,
				"djatokaServerBaseURL": Globals.encodedRepositoryUrl + "\/AJAX\/DjatokaResolver",
				"tileSize": 256,
				"tileOverlap": 0,
				"animationTime": 1.5,
				"blendTime": 0.1,
				"alwaysBlend": false,
				"autoHideControls": 1,
				"immediateRender": true,
				"wrapHorizontal": false,
				"wrapVertical": false,
				"wrapOverlays": false,
				"panHorizontal": 1,
				"panVertical": 1,
				"minZoomImageRatio": 0.35,
				"maxZoomPixelRatio": 2,
				"visibilityRatio": 0.5,
				"springStiffness": 5,
				"imageLoaderLimit": 5,
				"clickTimeThreshold": 300,
				"clickDistThreshold": 5,
				"zoomPerClick": 2,
				"zoomPerScroll": 1.2,
				"zoomPerSecond": 2,
				"showNavigator": 1,
				"defaultZoomLevel": 0,
				"homeFillsViewer": false
			}
		},

		changeActiveBookViewer: function(viewerName, pagePid){
			this.activeBookViewer = viewerName;
			// $('#view-toggle').children(".btn .active").removeClass('active');
			if (viewerName == 'pdf' && this.allowPDFView){
				$('#view-toggle-pdf').prop('checked', true);
						// .parent('.btn').addClass('active');
				$("#view-pdf").show();
				$("#view-image").hide();
				$("#view-transcription").hide();
				$("#view-audio").hide();
				$("#view-video").hide();
			}else if (viewerName == 'image' || (viewerName == 'pdf' && !this.allowPDFView)){
				$('#view-toggle-image').prop('checked', true);
						// .parent('.btn').addClass('active');
				$("#view-image").show();
				$("#view-pdf").hide();
				$("#view-transcription").hide();
				$("#view-audio").hide();
				$("#view-video").hide();
				this.activeBookViewer = 'image';
			}else if (viewerName == 'transcription'){
				$('#view-toggle-transcription').prop('checked', true);
					// .parent('.btn').addClass('active');
				$("#view-transcription").show();
				$("#view-pdf").hide();
				$("#view-image").hide();
				$("#view-audio").hide();
				$("#view-video").hide();
			}else if (viewerName == 'audio'){
				$('#view-toggle-transcription').prop('checked', true);
				// .parent('.btn').addClass('active');
				$("#view-audio").show();
				$("#view-pdf").hide();
				$("#view-image").hide();
				$("#view-transcription").hide();
				$("#view-video").hide();
			}else if (viewerName == 'video'){
				$('#view-toggle-transcription').prop('checked', true);
				// .parent('.btn').addClass('active');
				$("#view-video").show();
				$("#view-pdf").hide();
				$("#view-image").hide();
				$("#view-transcription").hide();
				$("#view-audio").hide();

			}
			return this.loadPage(pagePid);
		},

		clearCache:function(id){
			var url = Globals.path + "/Archive/AJAX?id=" + encodeURI(id) + "&method=clearCache";
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Cache Cleared Successfully", data.message, 2000); // auto-close after 2 seconds.
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initializeOpenSeadragon: function(viewer){

		},

		getMoreExhibitResults: function(exhibitPid, reloadHeader){
			this.curPage = this.curPage +1;
			if (typeof reloadHeader == 'undefined') {
				reloadHeader = 0;
			}
			if (reloadHeader) {
				$("#exhibit-results-loading").show();
				this.curPage = 1;
			}
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForExhibit&collectionId=" + exhibitPid + "&page=" + this.curPage + "&sort=" + this.sort + '&archiveCollectionView=' + this.displayMode + '&showCovers=' + AspenDiscovery.Account.showCovers;
			url = url + "&reloadHeader=" + reloadHeader;

			$.getJSON(url, function(data){
				if (data.success){
					if (reloadHeader){
						$("#related-objects-for-exhibit").hide().html(data.relatedObjects).fadeIn('slow');
					}else{
						$("#nextInsertPoint").hide().replaceWith(data.relatedObjects).fadeIn('slow');
					}
					$("#exhibit-results-loading").hide();
				}
			});
		},

		getMoreMapResults: function(exhibitPid, placePid, showTimeline){
			this.curPage = this.curPage +1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForMappedCollection&collectionId=" + exhibitPid + "&placeId=" + placePid + "&page=" + this.curPage + "&sort=" + this.sort + "&showTimeline=" + showTimeline;
			$("input[name=dateFilter]:checked").each(function(){
				url = url + "&dateFilter="+$(this).val();
			});
			url = url + "&reloadHeader=0";

			$.getJSON(url, function(data){
				if (data.success){
					$("#nextInsertPoint").replaceWith(data.relatedObjects);
				}
			});
		},

		getMoreTimelineResults: function(exhibitPid){
			this.curPage = this.curPage +1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForTimelineExhibit&collectionId=" + exhibitPid + "&page=" + this.curPage + "&sort=" + this.sort;
			$("input[name=dateFilter]:checked").each(function(){
				url = url + "&dateFilter="+$(this).val();
			});
			url = url + "&reloadHeader=0";

			$.getJSON(url, function(data){
				if (data.success){
					$("#nextInsertPoint").replaceWith(data.relatedObjects);
				}
			});
		},

		getMoreScrollerResults: function(pid){
			this.curPage = this.curPage +1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForScroller&pid=" + pid + "&page=" + this.curPage + "&sort=" + this.sort;

			$.getJSON(url, function(data){
				if (data.success){
					$("#nextInsertPoint").replaceWith(data.relatedObjects);
				}
			});
		},

		handleMapClick: function(markerIndex, exhibitPid, placePid, label, redirect, showTimeline){
			$("#exhibit-results-loading").show();
			this.archive_info_window.setContent(label);
			if (markerIndex >= 0){
				this.archive_info_window.open(this.archive_map, this.markers[markerIndex]);
			}

			if (redirect != "undefined" && redirect === true){
				var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'placePid', placePid);
				newUrl = AspenDiscovery.buildUrl(newUrl, 'style', 'map');
				document.location.href = newUrl;
			}
			if (showTimeline == "undefined"){
				showTimeline = true;
			}
			$.getJSON(Globals.path + "/Archive/AJAX?method=getRelatedObjectsForMappedCollection&collectionId=" + exhibitPid + "&placeId=" + placePid + "&showTimeline=" + showTimeline, function(data){
				if (data.success){
					$("#related-objects-for-exhibit").html(data.relatedObjects);
					$("#exhibit-results-loading").hide();
				}
			});
			var stateObj = {
				marker: markerIndex,
				exhibitPid: exhibitPid,
				placePid: placePid,
				label: label,
				showTimeline: showTimeline,
				page: "MapExhibit"
			};
			var newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'placePid', placePid);
			var currentParameters = AspenDiscovery.getQuerystringParameters();
			if (currentParameters["style"] != undefined){
				newUrl = AspenDiscovery.buildUrl(newUrl, 'style', currentParameters["style"]);
			}
			//Push the new url, but only if we aren't going back where we just were.
			if (document.location.href != newUrl){
				history.pushState(stateObj, label, newUrl);
			}
			return false;
		},

		handleTimelineClick: function(exhibitPid){
			$("#exhibit-results-loading").show();

			$.getJSON(Globals.path + "/Archive/AJAX?method=getRelatedObjectsForTimelineExhibit&collectionId=" + exhibitPid, function(data){
				if (data.success){
					$("#related-objects-for-exhibit").html(data.relatedObjects);
					$("#exhibit-results-loading").hide();
				}
			});
			return false;
		},

		handleCollectionScrollerClick: function(pid){
			$("#exhibit-results-loading").show();

			$.getJSON(Globals.path + "/Archive/AJAX?method=getRelatedObjectsForScroller&pid=" + pid, function(data){
				if (data.success){
					$("#related-objects-for-exhibit").html(data.relatedObjects);
					$("#exhibit-results-loading").hide();
				}
			});
			return false;
		},

		handleBookClick: function(bookPid, pagePid, bookViewer) {
			// Load specified page & viewer
			//Loading message
			//Load Page  set-up
			AspenDiscovery.Archive.activeBookPid = bookPid;
			AspenDiscovery.Archive.changeActiveBookViewer(bookViewer, pagePid);

			// store in browser history
			let stateObj = {
				bookPid: bookPid,
				pagePid: pagePid,
				viewer: bookViewer,
				page: 'Book'
			};
			let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'bookPid', bookPid);
			newUrl = AspenDiscovery.buildUrl(newUrl, 'pagePid', pagePid);
			newUrl = AspenDiscovery.buildUrl(newUrl, 'viewer', bookViewer);
			//Push the new url, but only if we aren't going back where we just were.
			if (document.location.href != newUrl){
				history.pushState(stateObj, '', newUrl);
			}
			return false;

		},

		reloadMapResults: function(exhibitPid, placePid, reloadHeader,showTimeline){
			$("#exhibit-results-loading").show();
			this.curPage = 1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForMappedCollection&collectionId=" + exhibitPid + "&placeId=" + placePid + "&page=" + this.curPage + "&sort=" + this.sort + '&archiveCollectionView=' + this.displayMode + '&showCovers=' + AspenDiscovery.Account.showCovers + '&showTimeline=' + showTimeline;
			$("input[name=dateFilter]:checked").each(function(){
				url = url + "&dateFilter="+$(this).val();
			});
			url = url + "&reloadHeader=" + reloadHeader;

			$.getJSON(url, function(data){
				if (data.success){
					if (reloadHeader){
						$("#related-objects-for-exhibit").html(data.relatedObjects);
					}else{
						$("#results").html(data.relatedObjects);
					}
					$("#exhibit-results-loading").hide();
				}
			});
		},

		reloadTimelineResults: function(exhibitPid, reloadHeader){
			$("#exhibit-results-loading").show();
			this.curPage = 1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForTimelineExhibit&collectionId=" + exhibitPid + "&page=" + this.curPage + "&sort=" + this.sort + '&archiveCollectionView=' + this.displayMode + '&showCovers=' + AspenDiscovery.Account.showCovers;
			$("input[name=dateFilter]:checked").each(function(){
				url = url + "&dateFilter="+$(this).val();
			});
			url = url + "&reloadHeader=" + reloadHeader;

			$.getJSON(url, function(data){
				if (data.success){
					if (reloadHeader){
						$("#related-objects-for-exhibit").html(data.relatedObjects);
					}else{
						$("#results").html(data.relatedObjects);
					}
					$("#exhibit-results-loading").hide();
				}
			});
		},

		reloadScrollerResults: function(pid, reloadHeader){
			$("#exhibit-results-loading").show();
			this.curPage = 1;
			var url = Globals.path + "/Archive/AJAX?method=getRelatedObjectsForScroller&pid=" + pid + "&page=" + this.curPage + "&sort=" + this.sort + '&archiveCollectionView=' + this.displayMode + '&showCovers=' + AspenDiscovery.Account.showCovers;
			url = url + "&reloadHeader=" + reloadHeader;

			$.getJSON(url, function(data){
				if (data.success){
					if (reloadHeader){
						$("#related-objects-for-exhibit").html(data.relatedObjects);
					}else{
						$("#results").html(data.relatedObjects);
					}
					$("#exhibit-results-loading").hide();
				}
			});
		},

		loadMetadata: function(pid, secondaryId){
			var url = Globals.path + "/Archive/AJAX?id=" + encodeURI(pid) + "&method=getMetadata";
			if (secondaryId !== undefined){
				url += "&secondaryId=" + secondaryId;
			}
			var metadataTarget = $('#archive-metadata');
			metadataTarget.html("Please wait while we load information about this object...")
			$.getJSON(url, function(data) {
				if (data.success) {
					metadataTarget.html(data.metadata);
				}
			}).fail(
					function(){metadataTarget.html("Could not load metadata.")}
			);
		},

		/**
		 * Load a new page into the active viewer
		 *
		 * @param pid
		 */
		loadPage: function(pid){
			if (pid == null){
				return false;
			}
			var pageChanged = false;
			if (this.activeBookPage != pid){
				pageChanged = true;
				this.curPage = this.pageDetails[pid]['index'];
			}
			this.activeBookPage = pid;
			// console.log('Page: '+ this.activeBookPage, 'Active Viewer : '+ this.activeBookViewer);
			if (this.pageDetails[pid]['transcript'] == ''){
				$('#view-toggle-transcription').parent().hide();
				if (this.activeBookViewer == 'transcription') {
					this.changeActiveBookViewer('image', pid);
					return false;
				}
			}else{
				$('#view-toggle-transcription').parent().show();
			}
			if (this.pageDetails[pid]['pdf'] == ''){
				$('#view-toggle-pdf').parent().hide();
			}else{
				$('#view-toggle-pdf').parent().show();
			}
			if (this.pageDetails[pid]['jp2'] == ''){
				$('#view-toggle-image').parent().hide();
			}else{
				$('#view-toggle-image').parent().show();
			}
			if (this.pageDetails[pid]['audio'] == ''){
				$('#view-toggle-audio').parent().hide();
			}else{
				$('#view-toggle-audio').parent().show();
			}
			if (this.pageDetails[pid]['video'] == ''){
				$('#view-toggle-video').parent().hide();
			}else{
				$('#view-toggle-video').parent().show();
			}

			if (this.activeBookViewer == 'pdf') {
				// console.log('PDF View called');
				$('#view-pdf').html(
						$('<object />').attr({
							type: 'application/pdf',
							data: this.pageDetails[pid]['pdf'],
							class: 'book-pdf' // Class that styles/sizes the PDF page
						})
				);
			}else if(this.activeBookViewer == 'transcription') {
				// console.log('Transcript Viewer called');
				var transcriptIdentifier = this.pageDetails[pid]['transcript'];
				var url = Globals.path + "/Archive/AJAX?transcriptId=" + encodeURI(transcriptIdentifier) + "&method=getTranscript";
				var transcriptionTarget = $('#view-transcription');
				transcriptionTarget.html("Loading Transcript, please wait.");
				$.getJSON(url, function(data) {
					if (data.success) {
						transcriptionTarget.html(data.transcript);
					}
				}).fail(
					function(){transcriptionTarget.html("Could not load Transcript.")}
				);

				// var islandoraURL = this.pageDetails[pid]['transcript'];
				// var reverseProxy = islandoraURL.replace(/([^\/]*)(?=\/islandora\/)/, location.host);
				// // reverseProxy = reverseProxy.replace('https', 'http'); // TODO: remove, for local instance only (no https)
				// // console.log('Fetching: '+reverseProxy);
				//
				// $('#view-transcription').load(reverseProxy);
			}else if (this.activeBookViewer == 'image'){
				var tile = new OpenSeadragon.DjatokaTileSource(
						Globals.url + "/AJAX/DjatokaResolver",
						this.pageDetails[pid]['jp2'],
						AspenDiscovery.Archive.openSeadragonViewerSettings()
				);
				if (!$('#custom-openseadragon').hasClass('processed')) {
					$('#custom-openseadragon').addClass('processed');
					settings = AspenDiscovery.Archive.openSeadragonViewerSettings();
					settings.tileSources = [];
					settings.tileSources.push(tile);
					AspenDiscovery.Archive.openSeaDragonViewer = new OpenSeadragon(settings);
				}else{
					AspenDiscovery.Archive.openSeaDragonViewer.open(tile);
				}
			}else if(this.activeBookViewer == 'audio') {
				$('#view-audio').show();
				$('#audio-player-src').attr('src', this.pageDetails[pid]['audio']);
				var audioPlayer = document.getElementById("audio-player");
				audioPlayer.load();
			}else if(this.activeBookViewer == 'video') {
				$('#view-video').show();
				$('#video-player-src').attr('src', this.pageDetails[pid]['video']);
				var videoPlayer = document.getElementById("video-player");
				videoPlayer.load();
			}
			if (pageChanged && this.multiPage){
				var numSectionsShown = 0;
				if (this.pageDetails[pid]['transcript'] == ''){
					$('#view-toggle-transcription').parent().hide();
				}else{
					$('#view-toggle-transcription').parent().show();
					numSectionsShown++;
				}
				if (this.pageDetails[pid]['pdf'] == ''){
					$('#view-toggle-pdf').parent().hide();
				}else{
					$('#view-toggle-pdf').parent().show();
					imageOnlyShown = false;
					numSectionsShown++;
				}
				if (this.pageDetails[pid]['jp2'] == ''){
					$('#view-toggle-image').parent().hide();
				}else{
					$('#view-toggle-image').parent().show();
					numSectionsShown++;
				}
				if (this.pageDetails[pid]['audio'] == ''){
					$('#view-toggle-audio').parent().hide();
				}else{
					$('#view-toggle-audio').parent().show();
					numSectionsShown++;
				}
				if (this.pageDetails[pid]['video'] == ''){
					$('#view-toggle-video').parent().hide();
				}else{
					$('#view-toggle-video').parent().show();
					numSectionsShown++;
				}
				if (numSectionsShown <= 1){
					$('#view-toggle').hide();
				}else{
					$('#view-toggle').show();
				}

				this.loadMetadata(this.activeBookPid, pid);
				//$("#downloadPageAsPDF").href = Globals.path + "/Archive/" + pid + "/DownloadPDF";
				url = Globals.path + "/Archive/AJAX?method=getAdditionalRelatedObjects&id=" + pid;
				var additionalRelatedObjectsTarget = $("#additional-related-objects");
				additionalRelatedObjectsTarget.html("");
				$.getJSON(url, function(data) {
					if (data.success) {
						additionalRelatedObjectsTarget.html(data.additionalObjects);
					}
				});

				var pageScroller = $("#book-sections .jcarousel");
				if (pageScroller){
					pageScroller.jcarousel('scroll', this.curPage - 1, true);
					$('#book-sections li').removeClass('active');
					$('#book-sections .jcarousel li:eq(' + (this.curPage - 1) + ')').addClass('active');
				}
			}
			//alert("Changing display to pid " + pid + " active viewer is " + this.activeBookViewer)
			return false;
		},

		nextRandomObject: function(pid){
			var url = Globals.path + "/Archive/AJAX?id=" + encodeURI(pid) + "&method=getNextRandomObject";
			$.getJSON(url, function(data){
				$('#randomImagePlaceholder').html(data.image);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		setForExhibitInAExhibitNavigation : function (exhibitInAExhibitParentPid) {
			var date = new Date();
			date.setTime(date.getTime() + (1 /*days*/ * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
			document.cookie = encodeURIComponent('exhibitInAExhibitParentPid') + "=" + encodeURIComponent(exhibitInAExhibitParentPid) + expires + "; path=/";
		},

		setForExhibitNavigation : function (recordIndex, page, collectionPid) {
			var date = new Date();
			date.setTime(date.getTime() + (1 /*days*/ * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
			if (typeof recordIndex != 'undefined') {
				document.cookie = encodeURIComponent('recordIndex') + "=" + encodeURIComponent(recordIndex) + expires + "; path=/";
			}
			if (typeof page != 'undefined') {
				document.cookie = encodeURIComponent('page') + "=" + encodeURIComponent(page) + expires + "; path=/";
			}
			if (typeof collectionPid != 'undefined') {
				document.cookie = encodeURIComponent('collectionPid') + "=" + encodeURIComponent(collectionPid) + expires + "; path=/";
			}
			document.cookie = encodeURIComponent('exhibitNavigation') + "=" + encodeURIComponent(1) + expires + "; path=/";
		},

		showBrowseEntityFilterPopup: function(exhibitPid, facetName, title){
			var url = Globals.path + "/Archive/AJAX?id=" + encodeURI(exhibitPid) + "&method=getEntityFacetValuesForExhibit&facetName=" + encodeURI(facetName);
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				AspenDiscovery.showMessage(title, data.modalBody);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showBrowseFilterPopup: function(exhibitPid, facetName, title){
			let url = Globals.path + "/Archive/AJAX?id=" + encodeURI(exhibitPid) + "&method=getFacetValuesForExhibit&facetName=" + encodeURI(facetName);
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				AspenDiscovery.showMessage(title, data.modalBody);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showObjectInPopup: function(pid, recordIndex, page){
			var url = Globals.path + "/Archive/AJAX?id=" + encodeURI(pid) + "&method=getObjectInfo";
					// (typeof collectionSearchId == 'undefined' ? '' : '&collectionSearchId=' + encodeURI(collectionSearchId)) +
					// (typeof recordIndex == 'undefined' ? '' : '&recordIndex=' + encodeURI(recordIndex));
			AspenDiscovery.loadingMessage();
			this.setForExhibitNavigation(recordIndex, page);

			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

	}

}(AspenDiscovery.Archive || {}));
AspenDiscovery.Authors = (function(){
    return{
        loadEnrichmentInfo: function(id){
            let url = Globals.path + "/Author/AJAX?method=getEnrichmentInfo&workId=" + id;
            $.getJSON(url, function(data){
                let similarAuthorsNovelist = data.similarAuthorsNovelist;
                if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
                    $("#similar-authors-placeholder-sidebar").html(similarAuthorsNovelist);
                    $("#similar-authors").fadeIn();
                    $('#similar-authors [data-toggle="tooltip"]').tooltip();
                }
            });
        }
    };
}(AspenDiscovery.Authors));
AspenDiscovery.Axis360 = (function () {
	return {
		cancelHold: function (patronId, id) {
			let url = Globals.path + "/Axis360/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$("#axis360Hold_" + id).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				let promptInfo = AspenDiscovery.Axis360.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.Axis360.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.checkOutTitle(id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				let ajaxUrl = Globals.path + "/Axis360/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							if (data.noCopies === true) {
								AspenDiscovery.closeLightbox();
								let ret = confirm(data.message);
								if (ret === true) {
									AspenDiscovery.Axis360.doHold(patronId, id);
								}
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Axis 360.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		doHold: function (patronId, id) {
			let url = Globals.path + "/Axis360/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.availableForCheckout) {
						AspenDiscovery.Axis360.doCheckOut(patronId, id);
					} else {
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts: function (id) {
			let url = Globals.path + "/Axis360/" + id + "/AJAX?method=getCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		getHoldPrompts: function (id) {
			let url = Globals.path + "/Axis360/" + id + "/AJAX?method=getHoldPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request in Axis 360.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.Axis360.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.Axis360.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Axis360.placeHold(id);
				});
			}
			return false;
		},

		processCheckoutPrompts: function () {
			let id = $("#id").val();
			let checkoutType = $("#checkoutType").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.Axis360.doCheckOut(patronId, id);
		},

		processHoldPrompts: function () {
			let id = $("#id").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.Axis360.doHold(patronId, id);
		},

		renewCheckout: function (patronId, recordId) {
			let url = Globals.path + "/Axis360/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					} else {
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, recordId) {
			let url = Globals.path + "/Axis360/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Returned", data.message, true);
						$("#axis360Checkout_" + recordId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in Axis 360.  Please try again in a few minutes.", false);
				}
			});
		},

		getStaffView: function (id) {
			let url = Globals.path + "/Axis360/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	}
}(AspenDiscovery.Axis360 || {}));
AspenDiscovery.Browse = (function(){
	return {
		colcade: null,
		curPage: 1,
		curCategory: '',
		curSubCategory : '',
		browseMode: 'covers',
		browseModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			grid:'home-page-browse-grid'
		},
		changingDisplay: false,

		addToHomePage: function(searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Browse/AJAX?method=getAddBrowseCategoryForm&searchId=' + searchId, true);
			return false;
		},

		initializeBrowseCategory: function(){
			if (!$('#home-page-browse-results .grid').length){
				return;
			}
			AspenDiscovery.Browse.colcade = new Colcade( '#home-page-browse-results .grid', {
				columns: '.grid-col',
				items: '.grid-item'
			});

			// wrapper for setting events and connecting w/ AspenDiscovery.initCarousels() in base.js

			let browseCategoryCarousel = $("#browse-category-carousel");

			// connect the browse catalog functions to the jcarousel controls
			browseCategoryCarousel.on('jcarousel:targetin', 'li', function(){
				let categoryId = $(this).data('category-id');
				AspenDiscovery.Browse.changeBrowseCategory(categoryId);
			});

			if ($('#browse-category-picker .jcarousel-control-prev').css('display') !== 'none') {
				// only enable if the carousel features are being used.
				// as of now, basalt & vail are not. plb 12-1-2014
				// TODO: when disabling the carousel feature is turned into an option, change this code to check that setting.

				// attach jcarousel navigation to clicking on a category
				browseCategoryCarousel.find('li').click(function(){
					$("#browse-category-carousel").jcarousel('scroll', $(this));
				});

				// Incorporate swiping gestures into the browse category selector. pascal 11-26-2014
				let scrollFactor = 15; // swipe size per item to scroll.
				browseCategoryCarousel.touchwipe({
					wipeLeft: function (dx) {
						let scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
						$("#browse-category-carousel").jcarousel('scroll', '+=' + scrollInterval);
					},
					wipeRight: function (dx) {
						let scrollInterval = Math.round(dx / scrollFactor); // vary scroll interval based on wipe length
						$("#browse-category-carousel").jcarousel('scroll', '-=' + scrollInterval);
					}
				});

				// implements functions for libraries not using the carousel functionality
			} else {
				// bypass jcarousel navigation on a category click
				browseCategoryCarousel.find('li').click(function(){
					$(this).trigger('jcarousel:targetin');
				});
			}

		},

		toggleBrowseMode : function(selectedMode){
			let mode = this.browseModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.browseMode; // check that selected mode is a valid option
			let categoryTextId = this.curCategory || $('#browse-category-carousel .selected').data('category-id');
			let subCategoryTextId = this.curSubCategory || $('#browse-sub-category-menu .selected').data('sub-category-id');
			this.browseMode = mode; // set the mode officially
			if (!Globals.opac && AspenDiscovery.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('browseMode', this.browseMode);
			}
			// re-load the browse category
			if (subCategoryTextId) {
				return this.changeBrowseSubCategory(subCategoryTextId);
			} else {
				return this.changeBrowseCategory(categoryTextId);
			} 
		},

		resetBrowseResults : function(){
			// let classes = (function(){ // return list of all associated css classes (class list can be expanded without changing this code.)
			// 	let str = '', object = AspenDiscovery.Browse.browseModeClasses;
			// 	for (property in object) { str += object[property]+' ' }
			// 	return str;
			// })();
			// let selectedClass = this.browseModeClasses[this.browseMode];

			// hide current results while fetching new results
			AspenDiscovery.Browse.colcade.destroy();
			$('.grid-item').fadeOut().remove();

			AspenDiscovery.Browse.colcade = new Colcade( '#home-page-browse-results .grid', {
				columns: '.grid-col',
				items: '.grid-item'
			});
		},

		changeBrowseCategory: function(categoryTextId, addToHistory = true) {
			if (AspenDiscovery.Browse.changingDisplay){
				return;
			}
			AspenDiscovery.Browse.changingDisplay = true;
			let url = Globals.path + '/Browse/AJAX';
			let params = {
				method: 'getBrowseCategoryInfo'
				, textId: categoryTextId || AspenDiscovery.Browse.curCategory
				, browseMode: this.browseMode
			};
			// Set selected Carousel
			$('.browse-category').removeClass('selected');
			// the carousel clones these divs sometimes, so grab only the text from the first one.
			let loadingID = 'initial';
			let newLabel = "";
			if (categoryTextId !== undefined){
				newLabel = $('#browse-category-' + categoryTextId + ' div').first().text(); // get label from corresponding li div
				loadingID = categoryTextId;
				$('#browse-category-' + categoryTextId).addClass('selected');
			}

			$('#selected-browse-search-link').attr('href', '#'); // clear the search results link so that

			// Set the new browse category labels (below the carousel)
			$('.selected-browse-label-search-text,.selected-browse-sub-category-label-search-text').fadeOut(function(){
				$('.selected-browse-label-search-text').html(newLabel).fadeIn()
			});

			// Hide current sub-categories while fetching new ones
			$('#browse-sub-category-menu').children().fadeOut(function(){
				$(this).remove() // delete sub-category buttons
			});

			// Hide current results while fetching new results
			this.resetBrowseResults();

			// Set a flag for the results we are currently loading
			//   so that if the user moves onto another category before we get results, we won't do anything
			this.loadingCategory = loadingID;
			$.getJSON(url, params, function(data){
				if (AspenDiscovery.Browse.loadingCategory === loadingID) {
					if (data.success === false) {
						if (data.message) {
							AspenDiscovery.showMessage("Error loading browse information", data.message);
						} else {
							AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
						}
					} else {
						let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'browseCategory', categoryTextId);
						categoryTextId = data.textId;
						let stateObj = {
							page: 'Browse',
							selectedBrowseCategory: categoryTextId
						};
						if (document.location.href && addToHistory){
							let label = 'Browse Catalog - ' + data.label;
							history.pushState(stateObj, label, newUrl);
						}

						$('#browse-category-' + categoryTextId).addClass('selected');
						$('.selected-browse-label-search-text').html(data.label); // update label

						AspenDiscovery.Browse.curPage = 1;
						AspenDiscovery.Browse.curCategory = data.textId;
						AspenDiscovery.Browse.curSubCategory = data.subCategoryTextId || '';
						// should be the first div only
						let resultsPanel = $('#home-page-browse-results');
						resultsPanel.fadeOut('fast', function () {
							$('.grid-item').remove();
							AspenDiscovery.Browse.colcade.append($(data.records));
							resultsPanel.fadeIn('slow');
						});

						$('#selected-browse-search-link').attr('href', data.searchUrl); // set the Label's link

						// scroll to the correct category
						$("#browse-category-carousel").jcarousel('scroll', $("#browse-category-" + data.textId));

						// Display Sub-Categories
						if (data.subcategories) {
							$('#browse-sub-category-menu').html(data.subcategories).fadeIn();
							if (data.subCategoryTextId) { // selected sub category
								// Set and Show sub-category label
								$('.selected-browse-sub-category-label-search-text')
									.html($('#browse-sub-category-' + data.subCategoryTextId).addClass('selected').text())
									.fadeIn()
							}
						}
					}
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$('#home-page-browse-results div').html('').show(); // should be first div
				//$('.home-page-browse-thumbnails').html('').show();
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function() {
				AspenDiscovery.Browse.loadingCategory = null;  // done loading category, empty flag
				AspenDiscovery.Browse.changingDisplay = false;
			});
			return false;
		},

		changeBrowseSubCategory: function (subCategoryTextId, categoryId = undefined, addToHistory = true) {
			if (AspenDiscovery.Browse.changingDisplay){
				return;
			}
			AspenDiscovery.Browse.changingDisplay = true;
			let url = Globals.path + '/Browse/AJAX';
			if (categoryId === undefined){
				categoryId = AspenDiscovery.Browse.curCategory;
			}
			let params = {
				method : 'getBrowseSubCategoryInfo'
				,textId : categoryId
				,subCategoryTextId : subCategoryTextId
				,browseMode : this.browseMode
			};
			// clear previous selections
			$('#browse-sub-category-menu button').removeClass('selected');
			$('.selected-browse-sub-category-label-search-text').fadeOut();

			if (categoryId !== undefined && categoryId !== AspenDiscovery.Browse.curCategory){
				$('.browse-category').removeClass('selected');

				let newLabel = $('#browse-category-' + categoryId + ' div').first().text(); // get label from corresponding li div
				$('#browse-category-' + categoryId).addClass('selected');

				$('#selected-browse-search-link').attr('href', '#'); // clear the search results link so that

				// Set the new browse category labels (below the carousel)
				$('.selected-browse-label-search-text,.selected-browse-sub-category-label-search-text').fadeOut(function(){
					$('.selected-browse-label-search-text').html(newLabel).fadeIn()
				});

				// Hide current sub-categories while fetching new ones
				$('#browse-sub-category-menu').children().fadeOut(function(){
					$(this).remove() // delete sub-category buttons
				});

				$("#browse-category-carousel").jcarousel('scroll', $("#browse-category-" + categoryId));
			}

			// Hide current results while fetching new results
			this.resetBrowseResults();

			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					let newUrl = AspenDiscovery.buildUrl(document.location.origin + document.location.pathname, 'browseCategory', AspenDiscovery.Browse.curCategory);
					newUrl += "&subCategory=" + subCategoryTextId;
					let stateObj = {
						page: 'Browse',
						selectedBrowseCategory: data.textId,
						subBrowseCategory: subCategoryTextId
					};
					let label = 'Browse Catalog - ';
					if (data.label) {
						label += data.label;
						$('.selected-browse-label-search-text').html(data.label);
					} // update label // needed when sub-category is specified via URL
					if (data.subCategoryLabel) {
						label += ' - ' + data.subCategoryLabel;
						$('.selected-browse-sub-category-label-search-text').html(data.subCategoryLabel);
					} else {
						$('.selected-browse-sub-category-label-search-text').fadeOut(); // Hide if no sub-category
					}
					if (document.location.href && addToHistory){
						history.pushState(stateObj, label, newUrl);
					}

					// Display Sub-Categories
					if (data.subcategories) {
						$('#browse-sub-category-menu').html(data.subcategories).fadeIn();
					}

					let newSubCategoryLabel = data.subCategoryLabel; // get label from corresponding button
					// Set the new browse category label (below the carousel)


					if (data.subCategoryTextId) { // selected sub category
						// Set and Show sub-category label
						$('.selected-browse-sub-category-label-search-text')
							.html($('#browse-sub-category-' + data.subCategoryTextId).addClass('selected').text())
							.fadeIn();
					}

					AspenDiscovery.Browse.curPage = 1;
					if (data.textId) AspenDiscovery.Browse.curCategory = data.textId;
					if (data.subCategoryTextId) AspenDiscovery.Browse.curSubCategory = data.subCategoryTextId || '';

					AspenDiscovery.Browse.colcade.append($(data.records));

					$('#selected-browse-search-link').attr('href', data.searchUrl); // update the search link
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$('#home-page-browse-results div.row').html('').show(); // should be first div
				$('.selected-browse-sub-category-label-search-text').fadeOut(); // hide sub-category Label
				AspenDiscovery.Browse.changingDisplay = false;
			}).done(function(){
				AspenDiscovery.Browse.changingDisplay = false;
			});
			return false;
		},

		createBrowseCategory: function(){
			let url = Globals.path + "/Browse/AJAX";
			let	params = {
				method:'createBrowseCategory'
				,categoryName:$('#categoryName').val()
				,addAsSubCategoryOf:$('#addAsSubCategoryOfSelect').val()
			};
			let searchId = $("#searchId");
			if (searchId){
				params['searchId'] = searchId.val()
			}
			let listId = $("#listId");
			if (listId){
				params['listId'] = listId.val()
			}
			$.getJSON(url, params, function (data) {
				if (data.success === false) {
					AspenDiscovery.showMessage("Unable to create category", data.message);
				} else {
					AspenDiscovery.showMessage("Successfully added", "This search was added to the homepage successfully.", true);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		getMoreResults: function(){
			//Increment the current page in case the button is clicked rapidly
			this.curPage += 1;
			var url = Globals.path + '/Browse/AJAX',
					params = {
						method : 'getMoreBrowseResults'
						,textId :  this.curSubCategory || this.curCategory
						  // if sub-category is currently selected fetch that, otherwise fetch the main category
						,pageToLoad : this.curPage
						,browseMode : this.browseMode
					},
					divClass = this.browseModeClasses[this.browseMode]; //|| this.browseModeClasses[Object.keys(this.browseModeClasses)[0]]; // if browseMode isn't set grab the first class
			$.getJSON(url, params, function(data){
				if (data.success === false){
					AspenDiscovery.showMessage("Error loading browse information", "Sorry, we were not able to find titles for that category");
				}else{
					AspenDiscovery.Browse.colcade.append($(data.records));
					if (data.lastPage){
						$('#more-browse-results').hide(); // hide the load more results TODO: implement server side
					}
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}

	}
}(AspenDiscovery.Browse || {}));

AspenDiscovery.CloudLibrary = (function () {
	return {
		cancelHold: function (patronId, id) {
			let url = Globals.path + "/CloudLibrary/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$("#cloudLibraryHold_" + id).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				let promptInfo = AspenDiscovery.CloudLibrary.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.CloudLibrary.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.checkOutTitle(id);
				});
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				let ajaxUrl = Globals.path + "/CloudLibrary/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							if (data.noCopies === true) {
								AspenDiscovery.closeLightbox();
								let ret = confirm(data.message);
								if (ret === true) {
									AspenDiscovery.CloudLibrary.doHold(patronId, id);
								}
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in Cloud Library.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		doHold: function (patronId, id) {
			let url = Globals.path + "/CloudLibrary/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.availableForCheckout) {
						AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
					} else {
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts: function (id) {
			let url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		getHoldPrompts: function (id) {
			let url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getHoldPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request in Cloud Library.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.CloudLibrary.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.CloudLibrary.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.CloudLibrary.placeHold(id);
				});
			}
			return false;
		},

		processCheckoutPrompts: function () {
			let id = $("#id").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.CloudLibrary.doCheckOut(patronId, id);
		},

		processHoldPrompts: function () {
			let id = $("#id").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.CloudLibrary.doHold(patronId, id);
		},

		renewCheckout: function (patronId, recordId) {
			let url = Globals.path + "/CloudLibrary/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					} else {
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, recordId) {
			let url = Globals.path + "/CloudLibrary/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Returned", data.message, true);
						$(".cloudLibraryCheckout_" + recordId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Title", data.message, true);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in Cloud Library.  Please try again in a few minutes.", false);
				}
			});
		},

		getStaffView: function (id) {
			let url = Globals.path + "/CloudLibrary/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	}
}(AspenDiscovery.CloudLibrary || {}));
AspenDiscovery.DPLA = (function(){
	return {
		getDPLAResults: function(searchTerm){
			let url = Globals.path + "/Search/AJAX";
			let params = "method=getDplaResults&searchTerm=" + encodeURIComponent(searchTerm);
			let fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				dataType:"json",
				success: function(data) {
					let searchResults = data.formattedResults;
					if (searchResults) {
						if (searchResults.length > 0){
							$("#dplaSearchResultsPlaceholder").html(searchResults);
						}
					}
				}
			});
		}
	}
}(AspenDiscovery.DPLA || {}));
AspenDiscovery.EBSCO = (function () {
	return {
		dismissResearchStarter: function(id){
			if (Globals.loggedIn){
				let ajaxUrl = Globals.path + "/EBSCO/JSON";
				let params = {
					'method':'dismissResearchStarter',
					id: id
				};
				$.getJSON(ajaxUrl, params,function (data) {
					$('#researchStarter-' + id).hide();
					AspenDiscovery.showMessage(data.title, data.message, true, false);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.EBSCO.dismissResearchStarter(id);
				}, true);
			}
			return false;
		},

		getResearchStarters: function(searchTerm){
			let ajaxUrl = Globals.path + "/EBSCO/JSON";
			let params = {
				'method':'getResearchStarters',
				lookfor: searchTerm
			};
			$.getJSON(ajaxUrl, params,function (data) {
				$('#research-starter-placeholder').html(data.researchStarters);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		trackEdsUsage: function (id) {
			let ajaxUrl = Globals.path + "/EBSCO/JSON?method=trackEdsUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.EBSCO || {}));
AspenDiscovery.EContent = (function(){
	return {
		submitHelpForm: function(){
			$.post(Globals.path + '/Help/eContentSupport', $("#eContentSupport").serialize(),
					function(data){
						AspenDiscovery.showMessage(data.title, data.message);
					},
					'json').fail(function(){AspenDiscovery.ajaxFail()});
			return false;
		}
	}
}(AspenDiscovery.EContent));

AspenDiscovery.GroupedWork = (function(){
	return {
		hasTableOfContentsInRecord: false,

		clearUserRating: function (groupedWorkId){
			let url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX?method=clearUserRating';
			$.getJSON(url, function(data){
				if (data.result === true){
					$('.rate' + groupedWorkId).find('.ui-rater-starsOn').width(0);
					$('#myRating' + groupedWorkId).hide();
					AspenDiscovery.showMessage('Success', data.message, true);
				}else{
					AspenDiscovery.showMessage('Sorry', data.message);
				}
			});
			return false;
		},

		clearNotInterested: function (notInterestedId){
			let url = Globals.path + '/GroupedWork/' + notInterestedId + '/AJAX?method=clearNotInterested';
			$.getJSON(
					url, function(data){
						if (data.result === false){
							AspenDiscovery.showMessage('Sorry', "There was an error updating the title.");
						}else{
							$("#notInterested" + notInterestedId).hide();
						}
					}
			);
		},

		deleteReview: function(id, reviewId){
			if (confirm("Are you sure you want to delete this review?")){
				let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=deleteUserReview';
				$.getJSON(url, function(data){
					if (data.result === true){
						$('#review_' + reviewId).hide();
						AspenDiscovery.showMessage('Success', data.message, true);
					}else{
						AspenDiscovery.showMessage('Sorry', data.message);
					}
				});
			}
			return false;
		},

		forceReindex: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=forceReindex';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessage("Success", data.message, true, false);
					setTimeout("AspenDiscovery.closeLightbox();", 3000);
				}
			);
			return false;
		},

		getGoDeeperData: function (id, dataType){
			let placeholder;
			if (dataType === 'excerpt') {
				placeholder = $("#excerptPlaceholder");
			} else if (dataType === 'avSummary') {
				placeholder = $("#avSummaryPlaceholder");
			} else if (dataType === 'tableOfContents') {
				placeholder = $("#tableOfContentsPlaceholder");
			} else if (dataType === 'authornotes') {
				placeholder = $("#authornotesPlaceholder");
			}
			if (placeholder.hasClass("loaded")) return;
			placeholder.show();
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
					params = {'method': 'GetGoDeeperData', dataType:dataType};
			$.getJSON(url, params, function(data) {
				placeholder.html(data.formattedData).addClass('loaded');
			});
		},

		getGoodReadsComments: function (isbn){
			// noinspection HtmlDeprecatedAttribute
			$("#goodReadsPlaceHolder").replaceWith(
				"<iframe id='goodreads_iframe' class='goodReadsIFrame' src='https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn=" + isbn + "&links=660&review_back=fff&stars=000&text=000' width='100%' height='400px' frameborder='0'></iframe>"
			);
		},

		loadDescription: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=getDescription';
			$.getJSON(url, function (data){
					if (data.success){
						$("#descriptionPlaceholder").html(data.description);
					}
				}
			);
			return false;
		},

		loadEnrichmentInfo: function (id, forceReload) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
					params = {'method':'getEnrichmentInfo'};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					let seriesData = data.seriesInfo;
					if (seriesData && seriesData.titles.length > 0) {
						seriesScroller = new TitleScroller('titleScrollerSeries', 'Series', 'seriesList');
						$('#seriesInfo').show();
						seriesScroller.loadTitlesFromJsonData(seriesData);
						$('#seriesPanel').show();
					}else{
						$('#seriesPanel').hide();
					}
					let seriesSummary = data.seriesSummary;
					if (seriesSummary){
						$('#seriesPlaceholder' + id).html(seriesSummary);
					}
					let showGoDeeperData = data.showGoDeeper;
					if (showGoDeeperData) {
						//$('#goDeeperLink').show();
						let goDeeperOptions = data.goDeeperOptions;
						//add a tab before citation for each item
						for (let option in goDeeperOptions){
							if (option === 'excerpt') {
								$("#excerptPanel").show();
							} else if (option === 'avSummary') {
								$("#avSummaryPlaceholder,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option === 'tableOfContents') {
								$("#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
							} else if (option === 'authorNotes') {
								$('#authornotesPlaceholder,#authornotesPanel').show();
							}
						}
					}
					if (AspenDiscovery.GroupedWork.hasTableOfContentsInRecord){
						$("#tableofcontentstab_label,#tableOfContentsPlaceholder,#tableOfContentsPanel").show();
					}
					let similarTitlesNovelist = data.similarTitlesNovelist;
					if (similarTitlesNovelist && similarTitlesNovelist.length > 0){
						$("#novelistTitlesPlaceholder").html(similarTitlesNovelist);
						$("#novelistTab_label,#similarTitlesPanel").show()
						;
					}

					let similarAuthorsNovelist = data.similarAuthorsNovelist;
					if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
						$("#novelistAuthorsPlaceholder").html(similarAuthorsNovelist);
						$("#novelistTab_label,#similarAuthorsPanel").show();
					}

					let similarSeriesNovelist = data.similarSeriesNovelist;
					if (similarSeriesNovelist && similarSeriesNovelist.length > 0){
						$("#novelistSeriesPlaceholder").html(similarSeriesNovelist);
						$("#novelistTab_label,#similarSeriesPanel").show();
					}

					// Show Explore More Sidebar Section loaded above
					$('.ajax-carousel', '#explore-more-body')
						.parents('.jcarousel-wrapper').show()
						.prev('.sectionHeader').show();
					// Initiate Any Explore More JCarousels
					AspenDiscovery.initCarousels('.ajax-carousel');

				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},

		loadMoreLikeThis: function (id, forceReload) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			let params = {
				'method':'getMoreLikeThis'
			};
			if (forceReload !== undefined){
				params['reload'] = true;
			}
			$.getJSON(url, params, function(data) {
				try{
					let similarTitleData = data.similarTitles;
					if (similarTitleData && similarTitleData.titles.length > 0) {
						morelikethisScroller = new TitleScroller('titleScrollerMoreLikeThis', 'MoreLikeThis', 'morelikethisList');
						$('#moreLikeThisInfo').show();
						morelikethisScroller.loadTitlesFromJsonData(similarTitleData);
					}else{
						$('#moreLikeThisPanel').hide();
					}

				} catch (e) {
					alert("error loading enrichment: " + e);
				}
			});
		},

		loadReviewInfo: function (id) {
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewInfo";
			$.getJSON(url, function(data) {
				if (data.numSyndicatedReviews === 0){
					$("#syndicatedReviewsPanel").hide();
				}else{
					let syndicatedReviewsData = data.syndicatedReviewsHtml;
					if (syndicatedReviewsData && syndicatedReviewsData.length > 0) {
						$("#syndicatedReviewPlaceholder").html(syndicatedReviewsData);
					}
				}

				if (data.numCustomerReviews === 0){
					$("#borrowerReviewsPanel").hide();
				}else{
					let customerReviewsData = data.customerReviewsHtml;
					if (customerReviewsData && customerReviewsData.length > 0) {
						$("#customerReviewPlaceholder").html(customerReviewsData);
					}
				}
			});
		},

		markNotInterested: function (recordId){
			if (Globals.loggedIn){
				let url = Globals.path + '/GroupedWork/' + recordId + '/AJAX?method=markNotInterested';
				$.getJSON(
						url, function(data){
							if (data.result === true){
								$("#notInterested" + recordId).css('background-color', '#f73d3d').css('color', 'white').prop("disabled", true);
							}else{
								AspenDiscovery.showMessage('Sorry', data.message);
							}
						}
				);
				return false;
			}else{
				return AspenDiscovery.Account.ajaxLogin(null, function(){markNotInterested(source, recordId)}, false);
			}
		},

		reloadCover: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadCover';
			$.getJSON(url, function (data){
						AspenDiscovery.showMessage("Success", data.message, true, true);
					}
			);
			return false;
		},

		reloadEnrichment: function (id){
			AspenDiscovery.GroupedWork.loadEnrichmentInfo(id, true);
		},

		reloadIslandora: function(id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=reloadIslandora';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessage("Success", data.message, true, true);
				}
			);
			return false;
		},

		saveReview: function(id){
			if (!Globals.loggedIn){
				AspenDiscovery.Account.ajaxLogin(null, function(){
					this.saveReview(id)
				})
			} else {
				var comment = $('#comment' + id).val(),
						rating = $('#rating' + id).val(),
						url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX",
						params =  {
							method : 'saveReview'
							,comment : comment
							,rating : rating
						};
				$.getJSON(url, params,
					function(data) {
						if (data.success) {
							if (data.newReview){
								$("#customerReviewPlaceholder").append(data.reviewHtml);
							}else{
								$("#review_" + data.reviewId).replaceWith(data.reviewHtml);
							}
							AspenDiscovery.closeLightbox();
						} else {
							AspenDiscovery.showMessage("Error", data.message);
						}
					}
				).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		sendEmail: function(id){
			if (Globals.loggedIn){
				let from = $('#from').val();
				let to = $('#to').val();
				let message = $('#message').val();
				let related_record = $('#related_record').val();
				let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
				let params = {
					'method' : 'sendEmail',
					from : from,
					to : to,
					message : message,
					related_record : related_record
				};
				$.getJSON(url, params, function(data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		showCopyDetails: function(id, format, recordId){
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			let params = {
				'method' : 'getCopyDetails',
				format : format,
				recordId : recordId,
			};
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showEmailForm: function(trigger, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/GroupedWork/" + id + "/AJAX?method=getEmailForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					return AspenDiscovery.GroupedWork.showEmailForm(trigger, id);
				}, false);
			}
			return false;
		},


		showGroupedWorkInfo:function(id, browseCategoryId){
			let url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getWorkInfo";
			if (browseCategoryId !== undefined){
				url += "&browseCategoryId=" + browseCategoryId;
			}
			AspenDiscovery.loadingMessage();
			$.getJSON(url, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		showReviewForm: function(trigger, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				$.getJSON(Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX?method=getReviewForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					return AspenDiscovery.GroupedWork.showReviewForm(trigger, id);
				}, false);
			}
			return false;
		},

		getUploadCoverForm: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=getUploadCoverForm';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		uploadCover: function (id){
			let url = Globals.path + '/GroupedWork/' + id + '/AJAX?method=uploadCover';
			let uploadCoverData = new FormData($("#uploadCoverForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadCoverData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},
		getGroupWithForm: function(trigger, id) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithForm";
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.getGroupWithForm(id);
				});
			}
			return false;
		},
		getGroupWithSearchForm: function (trigger, id, searchId, page) {
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getGroupWithSearchForm&searchId=" + searchId + "&page=" + page;
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.getGroupWithForm(id);
				});
			}
			return false;
		},

		getGroupWithInfo: function() {
			let groupWithId = $('#workToGroupWithId').val().trim();
			if (groupWithId.length === 36){
				let url = Globals.path + "/GroupedWork/" + groupWithId + "/AJAX?method=getGroupWithInfo";
				$.getJSON(url, function(data){
					$("#groupWithInfo").html(data.message);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				$("groupWithInfo").html("");
			}
		},
		processGroupWithForm: function() {
			let id = $('#id').val();
			let groupWithId = $('#workToGroupWithId').val().trim();
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=processGroupWithForm&groupWithId=" + groupWithId;
			//AspenDiscovery.closeLightbox();
			$.getJSON(url, function(data){
				if (data.success){
					AspenDiscovery.showMessage("Success", data.message, true, false);
				}else{
					AspenDiscovery.showMessage("An error occurred", data.message, false, false);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		ungroupRecord: function(trigger, recordId) {
			if (Globals.loggedIn){
				let url = Globals.path + "/Admin/AJAX?method=ungroupRecord&recordId=" + recordId;
				$.getJSON(url, function(data){
					if (data.success){
						AspenDiscovery.showMessage("Success", data.message);
					}else{
						AspenDiscovery.showMessage("An error occurred", data.message);
					}

				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin($(trigger), function (){
					AspenDiscovery.GroupedWork.ungroupRecord(id);
				});
			}
			return false;
		},

		getStaffView: function (id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getWhileYouWait: function (id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getWhileYouWait";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		getYouMightAlsoLike: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getYouMightAlsoLike";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		deleteAlternateTitle: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=deleteAlternateTitle";
			$.getJSON(url, function (data){
				if (data.success){
					$("#alternateTitle" + id).hide();
				}else{
					AspenDiscovery.showMessage(data.title, data.body);
				}
			});
			return false;
		},

		getDisplayInfoForm: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=getDisplayInfoForm";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			});
			return false;
		},

		processGroupedWorkDisplayInfoForm: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX";
			let params = {
				"method": "processDisplayInfoForm",
				"title" : $("#title").val(),
				"author" : $("#author").val(),
				"seriesName" : $("#seriesName").val(),
				"seriesDisplayOrder" : $("#seriesDisplayOrder").val()
			}
			$.getJSON(url, params, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		deleteDisplayInfo: function(id) {
			let url = Globals.path + "/GroupedWork/" + id + "/AJAX?method=deleteDisplayInfo";
			$.getJSON(url, function (data){
				if (data.success){
					$("#groupedWorkDisplayInfo").hide();
					AspenDiscovery.showMessage(data.title, data.message);
				}else{
					AspenDiscovery.showMessage(data.title, data.message);
				}
			});
			return false;
		},

		selectFileDownload: function( groupedWorkId, type) {
			let url = Globals.path + '/GroupedWork/' + groupedWorkId + '/AJAX';
			let params = {
				method: 'showSelectDownloadForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		downloadSelectedFile: function () {
			let id = $('#id').val();
			let fileType = $('#fileType').val();
			let selectedFile = $('#selectedFile').val();
			if (fileType === 'RecordPDF'){
				window.location = Globals.path + '/GroupedWork/' + id + '/DownloadPDF?fileId=' + selectedFile;
			}else{
				window.location = Globals.path + '/GroupedWork/' + id + '/DownloadSupplementalFile?fileId=' + selectedFile;
			}
			return false;
		},

		selectFileToView: function( recordId, type) {
			let url = Globals.path + '/GroupedWork/' + recordId + '/AJAX';
			let params = {
				method: 'showSelectFileToViewForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		viewSelectedFile: function () {
			let id = $('#id').val();
			let selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Files/' + selectedFile + '/ViewPDF';
			return false;
		},
	};
}(AspenDiscovery.GroupedWork || {}));
AspenDiscovery.Lists = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		addToHomePage: function(listId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getAddBrowseCategoryFromListForm&listId=' + listId, true);
			return false;
		},

		editListAction: function (){
			$('#listDescription,#listTitle,#FavEdit').hide();
			$('#listEditControls,#FavSave').show();
			return false;
		},

		submitListForm: function(action){
			$('#myListActionHead').val(action);
			$('#myListFormHead').submit();
			AspenDiscovery.Account.loadListData();
			return false;
		},

		makeListPublicAction: function (){
			return this.submitListForm('makePublic');
		},

		makeListPrivateAction: function (){
			return this.submitListForm('makePrivate');
		},

		deleteListAction: function (){
			if (confirm("Are you sure you want to delete this list?")){
				this.submitListForm('deleteList');
			}
			return false;
		},

		updateListAction: function (){
			return this.submitListForm('saveList');
		},

		emailListAction: function (listId) {
			let urlToDisplay = Globals.path + '/MyAccount/AJAX';
			AspenDiscovery.loadingMessage();
			$.getJSON(urlToDisplay, {
					method  : 'getEmailMyListForm'
					,listId : listId
				},
				function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		sendMyListEmail: function () {
			let url = Globals.path + "/MyAccount/AJAX";

			$.getJSON(url,
				{ // form inputs passed as data
					listId   : $('#emailListForm input[name="listId"]').val()
					,to      : $('#emailListForm input[name="to"]').val()
					,from    : $('#emailListForm input[name="from"]').val()
					,message : $('#emailListForm textarea[name="message"]').val()
					,method  : 'sendMyListEmail'
				},
				function(data) {
					if (data.result) {
						AspenDiscovery.showMessage("Success", data.message);
					} else {
						AspenDiscovery.showMessage("Error", data.message);
					}
				}
			);
		},

		citeListAction: function (id) {
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX?method=getCitationFormatsForm&listId=' + id, false);
			//return false;
			//TODO: ajax call not working
		},

		processCiteListForm: function(){
			$("#citeListForm").submit();
		},

		batchAddToListAction: function (id){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + '/MyAccount/AJAX/?method=getBulkAddToListForm&listId=' + id);
			//return false;
		},

		processBulkAddForm: function(){
			$("#bulkAddToList").submit();
		},

		changeList: function (){
			let availableLists = $("#availableLists");
			window.location = Globals.path + "/MyAccount/MyList/" + availableLists.val();
		},

		printListAction: function (){
			window.print();
			return false;
		},

		importListsFromClassic: function (){
			if (confirm("This will import any lists you had defined in the old catalog.  This may take several minutes depending on the size of your lists. Are you sure you want to continue?")){
				window.location = Globals.path + "/MyAccount/ImportListsFromClassic";
			}
			return false;
		}
	};
}(AspenDiscovery.Lists || {}));
AspenDiscovery.CollectionSpotlights = (function(){
	return {
		createSpotlightFromList: function (listId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToSpotlightForm&source=list&id=' + listId, true);
			return false;
		},
		createSpotlightFromSearch: function (searchId){
			AspenDiscovery.Account.ajaxLightbox(Globals.path + '/Admin/AJAX?method=getAddToSpotlightForm&source=search&id=' + searchId, true);
			return false;
		}
	};
}(AspenDiscovery.CollectionSpotlights || {}));
AspenDiscovery.MaterialsRequest = (function(){
	return {
		getWorldCatIdentifiers: function(){
			var title = $("#title").val();
			var author = $("#author").val();
			var format = $("#format").val();
			if (title == '' && author == ''){
				alert("Please enter a title and author before checking for an ISBN and OCLC Number");
			}else{
				var requestUrl = Globals.path + "/MaterialsRequest/AJAX?method=GetWorldCatIdentifiers&title=" + encodeURIComponent(title) + "&author=" + encodeURIComponent(author)  + "&format=" + encodeURIComponent(format);
				$.getJSON(requestUrl, function(data){
					if (data.success == true){
						//Dislay the results of the suggestions
						var suggestedIdentifiers = $("#suggestedIdentifiers");
						suggestedIdentifiers.html(data.formattedSuggestions);
						suggestedIdentifiers.slideDown();
					}else{
						alert(data.error);
					}
				});
			}
			return false;
		},

		cancelMaterialsRequest: function(id){
			if (confirm("Are you sure you want to cancel this request?")){
				var url = Globals.path + "/MaterialsRequest/AJAX?method=cancelRequest&id=" + id;
				$.getJSON(
						url,
						function(data){
							if (data.success){
								alert("Your request was cancelled successfully.");
								window.location.reload();
							}else{
								alert(data.error);
							}
						}
				);
				return false;
			}else{
				return false;
			}
		},

		showMaterialsRequestDetails: function(id, staffView){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id + "&staffView=" +staffView, true);
		},

		updateMaterialsRequest: function(id){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=updateMaterialsRequest&id=" +id, true);
		},

		exportSelectedRequests: function(){
			let selectedRequests = this.getSelectedRequests(true);
			if (selectedRequests.length == 0){
				return false;
			}
			$("#updateRequests").submit();
			return true;
		},

		showImportRequestForm: function(){
			let url = Globals.path + '/MaterialsRequest/AJAX?method=getImportRequestForm';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		importRequests: function(){
			let url = Globals.path + '/MaterialsRequest/AJAX?method=importRequests';
			let importRequestsData = new FormData($("#importRequestsForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: importRequestsData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		updateSelectedRequests: function(){
			var newStatus = $("#newStatus").val();
			if (newStatus == "unselected"){
				alert("Please select a status to update the requests to.");
				return false;
			}
			var selectedRequests = this.getSelectedRequests(false);
			if (selectedRequests.length != 0){
				$("#updateRequests").submit();
			}
			return false;
		},

		assignSelectedRequests: function(){
			var newAssignee = $("#newAssignee").val();
			if (newAssignee == "unselected"){
				alert("Please select a user to assign the requests to.");
				return false;
			}
			var selectedRequests = this.getSelectedRequests(false);
			if (selectedRequests.length != 0){
				$("#updateRequests").submit();
			}
			return false;
		},

		getSelectedRequests: function(promptToSelectAll){
			var selectedRequests = $("input.select:checked").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedRequests.length == 0){
				if (promptToSelectAll){
					var ret = confirm('You have not selected any requests, process all requests?');
					if (ret == true){
						selectedRequests = $("input.select").map(function() {
							return $(this).attr('name') + "=on";
						}).get().join("&");
						$('.select').attr('checked', 'checked');
					}
				}else{
					alert("Please select one or more requests to update");
				}
			}
			return selectedRequests;
		},

		setIsbnAndOclcNumber: function(title, author, isbn, oclcNumber){
			$("#title").val(title);
			$("#author").val(author);
			$("#isbn").val(isbn);
			$("#oclcNumber").val(oclcNumber);
			$("#suggestedIdentifiers").slideUp();
		},

		setFieldVisibility: function(){
			$(".formatSpecificField").hide();
			//Get the selected format
			var selectedFormat = $("#format").find("option:selected").val(),
					hasSpecialFields = typeof AspenDiscovery.MaterialsRequest.specialFields != 'undefined';

			$(".specialFormatField").hide(); // hide all the special fields
			$(".specialFormatHideField").show(); // show all the special format hide fields
			this.updateHoldOptions();
			if (hasSpecialFields){
				if (AspenDiscovery.MaterialsRequest.specialFields[selectedFormat]) {
					AspenDiscovery.MaterialsRequest.specialFields[selectedFormat].forEach(function (specifiedOption) {
						switch (specifiedOption) {
							case 'Abridged/Unabridged':
								$(".abridgedField").show();
								$(".abridgedHideField").hide();
								break;
							case 'Article Field':
								$(".articleField").show();
								$(".articleHideField").hide();
								break;
							case 'Eaudio format':
								$(".eaudioField").show();
								$(".eaudioHideField").hide();
								break;
							case 'Ebook format':
								$(".ebookField").show();
								$(".ebookHideField").hide();
								break;
							case 'Season':
								$(".seasonField").show();
								$(".seasonHideField").hide();
								break;
						}
					})
				}
			}


			//Update labels as needed
			if (AspenDiscovery.MaterialsRequest.authorLabels){
				if (AspenDiscovery.MaterialsRequest.authorLabels[selectedFormat]) {
					$("#authorFieldLabel").html(AspenDiscovery.MaterialsRequest.authorLabels[selectedFormat] + ': ');
				//	TODO: Set when required
				}
			}

			if ((hasSpecialFields && AspenDiscovery.MaterialsRequest.specialFields[selectedFormat] && AspenDiscovery.MaterialsRequest.specialFields[selectedFormat].indexOf('Article Field') > -1)){
				$("#magazineTitle,#acceptCopyrightYes").addClass('required');
				$("#acceptCopyrightYes").addClass('required');
				$("#copyright").show();
				$("#supplementalDetails").hide();
				$("#titleLabel").html("Article Title <span class='requiredIndicator'>*</span>");
			}else{
				$("#magazineTitle,#acceptCopyrightYes").removeClass('required');
				$("#copyright").hide();
				$("#supplementalDetails").show();
				$("#titleLabel").html("Title <span class='requiredIndicator'>*</span>");
			}

		},

		updateHoldOptions: function(){
			var placeHold = $("input[name=placeHoldWhenAvailable]:checked").val() == 1 || $("input[name=illItem]:checked").val() == 1;
			// comparison needed to change placeHold to a boolean
			if (placeHold){
				$("#pickupLocationField").show();
				if ($("#pickupLocation").find("option:selected").val() == 'bookmobile'){
					$("#bookmobileStopField").show();
				}else{
					$("#bookmobileStopField").hide();
				}
			}else{
				$("#bookmobileStopField").hide();
				$("#pickupLocationField").hide();
			}
		}

		// no uses for this found. plb 12-29-2017
		// printRequestBody: function(){
		// 	$("#request_details_body").printElement();
		// }
	};
}(AspenDiscovery.MaterialsRequest || {}));
AspenDiscovery.OverDrive = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		cancelOverDriveHold: function(patronId, overdriveId){
			if (confirm("Are you sure you want to cancel this hold?")){
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=cancelHold&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success){
							AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
							//remove the row from the holds list
							$("#overDriveHold_" + overdriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}else{
							AspenDiscovery.showMessage("Error Cancelling Hold", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
					}
				});
			}
			return false;
		},

		freezeHold: function(patronId, overDriveId){
			AspenDiscovery.loadingMessage();
			let url = Globals.path + '/OverDrive/AJAX';
			let params = {
				patronId : patronId
				,overDriveId : overDriveId
			};
			//Prompt the user for the date they want to reactivate the hold
			params['method'] = 'getReactivationDateForm'; // set method for this form
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).error(AspenDiscovery.ajaxFail);
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			let params = {
				'method' : 'freezeHold'
				,patronId : $('#patronId').val()
				,overDriveId : $('#overDriveId').val()
				,reactivationDate : $("#reactivationDate").val()
			};
			let url = Globals.path + '/OverDrive/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		thawHold: function(patronId, overDriveId, caller){
			let popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			let url = Globals.path + '/OverDrive/AJAX';
			let params = {
				'method' : 'thawHold'
				,patronId : patronId
				,overDriveId : overDriveId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		getCheckOutPrompts: function(overDriveId){
			let url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					result = data;
					if (data.promptNeeded){
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				}
			});
			return result;
		},

		checkOutTitle: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.OverDrive.getCheckOutPrompts(overDriveId, 'hold');
				if (!promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveCheckout(promptInfo.patronId, overDriveId);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overDriveId);
				});
			}
			return false;
		},

		processOverDriveCheckoutPrompts: function(){
			let overdriveCheckoutPromptsForm = $("#overdriveCheckoutPromptsForm");
			let patronId = $("#patronId").val();
			let overdriveId = overdriveCheckoutPromptsForm.find("input[name=overdriveId]").val();
			AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
		},

		doOverDriveCheckout: function(patronId, overdriveId){
			if (Globals.loggedIn){
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=checkOutTitle&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success === true){
							AspenDiscovery.showMessageWithButtons("Title Checked Out Successfully", data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						}else{
							if (data.noCopies === true){
								AspenDiscovery.closeLightbox();
								let ret = confirm(data.message);
								if (ret === true){
									AspenDiscovery.OverDrive.placeHold(overdriveId);
								}
							}else{
								AspenDiscovery.showMessage("Error Checking Out Title", data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					}
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overdriveId);
				}, false);
			}
			return false;
		},

		doOverDriveHold: function(patronId, overDriveId, overdriveEmail, promptForOverdriveEmail){
			let url = Globals.path + "/OverDrive/AJAX?method=placeHold&patronId=" + patronId + "&overDriveId=" + overDriveId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.availableForCheckout){
						AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
					}else{
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		followOverDriveDownloadLink: function(patronId, overDriveId, formatId){
			let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=getDownloadLink&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + formatId;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.success){
						//Reload the page
						let win = window.open(data.downloadUrl, '_blank');
						win.focus();
						//window.location.href = data.downloadUrl ;
					}else{
						AspenDiscovery.showMessage('An Error occurred', data.message);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				}
			});
		},

		getOverDriveHoldPrompts: function(overDriveId){
			let url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getHoldPrompts";
			let result = false;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.success){
						result = data;
						if (data.promptNeeded){
							AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
						}
					}else{
						AspenDiscovery.showMessage('An Error occurred', data.message);
					}

				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage('An Error occurred', "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
				}
			});
			return result;
		},

		placeHold: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.OverDrive.getOverDriveHoldPrompts(overDriveId, 'hold');
				if (promptInfo !== false && !promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveHold(promptInfo.patronId, overDriveId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.placeHold(overDriveId);
				});
			}
			return false;
		},

		processOverDriveHoldPrompts: function(){
			let overdriveHoldPromptsForm = $("#overdriveHoldPromptsForm");
			let patronId = $("#patronId").val();
			let overdriveId = overdriveHoldPromptsForm.find("input[name=overdriveId]").val();
			let promptForOverdriveEmail;
			if (overdriveHoldPromptsForm.find("input[name=promptForOverdriveEmail]").is(":checked")){
				promptForOverdriveEmail = 0;
			}else{
				promptForOverdriveEmail = 1;
			}
			let overdriveEmail = overdriveHoldPromptsForm.find("input[name=overdriveEmail]").val();
			AspenDiscovery.OverDrive.doOverDriveHold(patronId, overdriveId, overdriveEmail, promptForOverdriveEmail);
		},

		renewCheckout: function(patronId, recordId){
			let url = Globals.path + "/OverDrive/AJAX?method=renewCheckout&patronId=" + patronId + "&overDriveId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					}else{
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, overDriveId){
			if (confirm('Are you sure you want to return this title?')){
				AspenDiscovery.showMessage("Returning Title", "Returning your title in OverDrive.  This may take a minute.");
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=returnCheckout&patronId=" + patronId + "&overDriveId=" + overDriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						AspenDiscovery.showMessage("Title Returned", data.message, data.success);
						if (data.success){
							$(".overdrive_checkout_" + overDriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Returning Title", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					}
				});
			}
			return false;
		},

		selectOverDriveDownloadFormat: function(patronId, overDriveId, time){
			let selectedOption = $("#downloadFormat_" + overDriveId + "_" + time + " option:selected");
			let selectedFormatId = selectedOption.val();
			let selectedFormatText = selectedOption.text();
			// noinspection EqualityComparisonWithCoercionJS
			if (selectedFormatId == -1){
				alert("Please select a format to download.");
			}else{
				if (confirm("Are you sure you want to download the " + selectedFormatText + " format? You cannot change format after downloading.")){
					let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=selectOverDriveDownloadFormat&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + selectedFormatId;
					$.ajax({
						url: ajaxUrl,
						cache: false,
						success: function(data){
							if (data.success){
								//Reload the page
								window.location.href = data.downloadUrl;
							}else{
								AspenDiscovery.showMessage("Error Selecting Format", data.message);
							}
						},
						dataType: 'json',
						async: false,
						error: function(){
							AspenDiscovery.showMessage("Error Selecting Format", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
						}
					});
				}
			}
			return false;
		},

		getStaffView: function (id) {
			let url = Globals.path + "/OverDrive/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	}
}(AspenDiscovery.OverDrive || {}));
AspenDiscovery.OpenArchives = (function () {
	return {
		trackUsage: function (id) {
			let ajaxUrl = Globals.path + "/OpenArchives/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.OpenArchives || {}));
AspenDiscovery.Hoopla = (function(){
	return {
		checkOutHooplaTitle: function (hooplaId, patronId) {
			if (Globals.loggedIn) {
				if (typeof patronId === 'undefined') {
					patronId = $('#patronId', '#pickupLocationOptions').val(); // Lookup selected user from the options form
				}
				let url = Globals.path + '/Hoopla/'+ hooplaId + '/AJAX';
				let	params = {
					'method' : 'checkOutHooplaTitle',
					patronId : patronId
				};
				if ($('#stopHooplaConfirmation').prop('checked')){
					params['stopHooplaConfirmation'] = true;
				}
				$.getJSON(url, params, function (data) {
					if (data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Checking Out Title", data.message);
					}
				}).fail(AspenDiscovery.ajaxFail)
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Hoopla.checkOutHooplaTitle(hooplaId, patronId);
				}, false);
			}
			return false;
		},

		getCheckOutPrompts: function (hooplaId) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX?method=getCheckOutPrompts";
				$.getJSON(url, function (data) {
					AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
				}).fail(AspenDiscovery.ajaxFail);
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.getCheckOutPrompts(hooplaId);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, hooplaId) {
			if (Globals.loggedIn) {
				if (confirm('Are you sure you want to return this title?')) {
					AspenDiscovery.showMessage("Returning Title", "Returning your title in Hoopla.");
					var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX",
							params = {
								'method': 'returnCheckout'
								,patronId: patronId
							};
					$.getJSON(url, params, function (data) {
						AspenDiscovery.showMessage(data.success ? 'Success' : 'Error', data.message, data.success, data.success);
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.returnCheckout(patronId, hooplaId);
					AspenDiscovery.Account.loadMenuData();
				}, false);
			}
			return false;
		}

	}
}(AspenDiscovery.Hoopla || {}));
AspenDiscovery.Prospector = (function(){
	return {
		getProspectorResults: function(prospectorNumTitlesToLoad, prospectorSavedSearchId){
			var url = Globals.path + "/Search/AJAX";
			var params = "method=getProspectorResults&prospectorNumTitlesToLoad=" + encodeURIComponent(prospectorNumTitlesToLoad) + "&prospectorSavedSearchId=" + encodeURIComponent(prospectorSavedSearchId);
			var fullUrl = url + "?" + params;
			$.ajax({
				url: fullUrl,
				success: function(data) {
					var prospectorSearchResults = $(data).find("ProspectorSearchResults").text();
					if (prospectorSearchResults) {
						if (prospectorSearchResults.length > 0){
							$("#prospectorSearchResultsPlaceholder").html(prospectorSearchResults);
						}
					}
				}
			});
		},

		loadRelatedProspectorTitles: function (id) {
			var url;
			url = Globals.path + "/GroupedWork/" + encodeURIComponent(id) + "/AJAX";
			var params = "method=getProspectorInfo";
			var fullUrl = url + "?" + params;
			$.getJSON(fullUrl, function(data) {
				if (data.numTitles == 0){
					$("#prospectorPanel").hide();
				}else{
					$("#inProspectorPlaceholder").html(data.formattedData);
				}
			});
		},

		removeBlankThumbnail: function(imgElem, elemToHide, isForceRemove) {
			var $img = $(imgElem);
			//when the content providers cannot find a bookjacket, they return a 1x1 pixel
			//remove the wrapping div, for consistent spacing with other results
			if ($img.height() == 1 && $img.width() == 1 || isForceRemove) {
				$(elemToHide).remove();
			}
		}
	}
}(AspenDiscovery.Prospector || {}));
AspenDiscovery.Ratings = (function(){
	$(function(){
		AspenDiscovery.Ratings.initializeRaters();
	});
	return{
		initializeRaters: function(){
			$(".rater").each(function(){
				var ratingElement = $(this),
						userRating = ratingElement.data("user_rating"),
						id = ratingElement.data("id"),
						options = {
							id: id,
							rating: parseFloat(userRating > 0 ? userRating : ratingElement.data("average_rating")),
							//url: Globals.path +"AJAX" // only works for grouped works
							//url: location.protocol+'\\'+location.host+ "/GroupedWork/AJAX" // full path
							//url: Globals.path + "/GroupedWork/AJAX" // full path // works on our servers but not locally. plb 12-29-2015
							url: Globals.path + "/GroupedWork/"+ encodeURIComponent( id ) + "/AJAX" // full path
						};
				ratingElement.rater(options);
			});
		},

		doRatingReview: function (id){
			$.getJSON(Globals.path + "/GroupedWork/"+id+"/AJAX?method=getPromptForReviewForm", function(data){
				if (data.prompt) AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons); // only ask if user hasn't set the setting already
				if (data.error)  AspenDiscovery.showMessage('Error', data.message);
			}).fail(AspenDiscovery.ajaxFail)
		},

		doNoRatingReviews : function (){
			$.getJSON(Globals.path + "/GroupedWork/AJAX?method=setNoMoreReviews", function(data){
				if (data.success) AspenDiscovery.showMessage('Success', 'You will no longer be asked to give a review.', true)
				else AspenDiscovery.showMessage('Error', 'Failed to save your setting.')
			}).fail(AspenDiscovery.ajaxFail);
		}
	};
}(AspenDiscovery.Ratings));

/*
*  Jquery Ratings Plugin, Adapted for Aspen Discovery
 *
* */
//copyright 2008 Jarrett Vance
//http://jvance.com
$.fn.rater = function(options) {
	var opts = $.extend( {}, $.fn.rater.defaults, options);
	return this.each(function() {
		var $this = $(this),
				$on = $this.find('.ui-rater-starsOn'),
				$off = $this.find('.ui-rater-starsOff');

		if (opts.size == undefined) opts.size = $off.height();
		if (opts.rating == undefined) {
			opts.rating = $on.width() / $off.width();
		}else{
			$on.width($off.width() * (opts.rating / opts.ratings.length));
		}
		if (opts.id == undefined) opts.id = $this.attr('id');
		var initialRating = opts.rating;

		if (!$this.hasClass('ui-rater-bindings-done')) {
			$this.addClass('ui-rater-bindings-done');
			$off.mousemove(function(e) {
				var left = e.clientX - $off.offset().left,
						width = $off.width() - ($off.width() - left);
				width = Math.min(Math.ceil(width / (opts.size / opts.step)) * opts.size / opts.step, opts.size * opts.ratings.length);
				$on.width(width);
				var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
				//$this.attr('title', 'Click to Rate "' + (opts.ratings[r - 1] == undefined ? r : opts.ratings[r - 1]) + '"');
				// TODO ratings label's are customized now.
				$this.attr('title', 'Click to Rate "' +  r  + ' stars"');
			}).hover(
					function(e) { // Hover In
						$on.addClass('ui-rater-starsHover');
					},
					function(e) { // Hover out
						$on.removeClass('ui-rater-starsHover');
						$on.width(initialRating * opts.size); // restore to original rating if none was selected.
					}
			).click(function(e) {
						var r = Math.round($on.width() / $off.width() * (opts.ratings.length * opts.step)) / opts.step;
						$.fn.rater.rate($this, opts, r);
					}).css('cursor', 'pointer'); $on.css('cursor', 'pointer');
		}
	});
};


$.fn.rater.defaults = {
	url : location.href,
	ratings: ['Hated It', "Didn't Like It", 'Liked It', 'Really Liked It', 'Loved It'],
	step : 1
};

$.fn.rater.rate = function($this, opts, rating) {
	if (Globals.loggedIn){
		var $on = $this.find('.ui-rater-starsOn'),
				$off = $this.find('.ui-rater-starsOff');
		$off.fadeTo(600, 0.4, function() {
			$.getJSON(opts.url, {method: 'rateTitle', id: opts.id, rating: rating}, function(data) {
				if (data.error) {
					AspenDiscovery.showMessage('Error', data.error);
					$off.fadeTo(500, 1).mouseleave(); // Reset rater in light of failure
				}
				if (data.rating) { // success
					opts.rating = data.rating;
					//$on.css('cursor', 'default');
					$off
						// detach rater.
						//	.unbind('click').unbind('mousemove').unbind('mouseenter').unbind('mouseleave')
							//.css('cursor', 'default')

						// wrap-up
							.fadeTo(600, 0.1, function() {
								$on.removeClass('ui-rater-starsHover').width(opts.rating * opts.size).addClass('userRated');
								$off.fadeTo(500, 1);
								$this.attr('title', 'Your rating: ' + rating.toFixed(1));
								if ($this.data('show_review') == true){
									AspenDiscovery.Ratings.doRatingReview(opts.id);
								}
							});
				}
			}).fail(function(){
				AspenDiscovery.ajaxFail();
				$off.fadeTo(500, 1).mouseleave(); // Reset rater in light of failure
			});

		});
	}else{
		AspenDiscovery.Account.ajaxLogin(null, function(){
			$.fn.rater.rate($this, opts, rating);
		}, true);
	}
};
AspenDiscovery.RBdigital = (function () {
	return {
		cancelHold: function (patronId, id) {
			let url = Globals.path + "/RBdigital/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
						$("#rbdigitalHold_" + id).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
				}
			});
		},

		checkOutTitle: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				let promptInfo = AspenDiscovery.RBdigital.getCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.RBdigital.doCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.RBdigital.checkOutTitle(id);
				});
			}
			return false;
		},

		checkOutMagazine: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for checking out a title
				let promptInfo = AspenDiscovery.RBdigital.getMagazineCheckOutPrompts(id);
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.RBdigital.doMagazineCheckOut(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.RBdigital.checkOutMagazine(id);
				});
			}
			return false;
		},

		createAccount: function (action, patronId, id) {
			if (Globals.loggedIn) {
				//Check form validation
				let accountForm = $('#createRBdigitalAccount');

				if (!accountForm[0].checkValidity()) {
					// If the form is invalid, submit it. The form won't actually submit;
					// this will just cause the browser to display the native HTML5 error messages.
					accountForm.find(':submit').click();
					return false;
				}

				let formValues = 'username=' + encodeURIComponent($("#username").val());
				let password1 = encodeURIComponent($('#password1').val());
				let password2 = encodeURIComponent($('#password2').val());
				if (password1 !== password2) {
					$("#password_validation").show().focus();
					return false;
				} else {
					$("#password_validation").hide();
				}
				formValues += '&password=' + password1;
				formValues += '&libraryCard=' + encodeURIComponent($('#libraryCard').val());
				formValues += '&firstName=' + encodeURIComponent($('#firstName').val());
				formValues += '&lastName=' + encodeURIComponent($('#lastName').val());
				formValues += '&email=' + encodeURIComponent($('#email').val());
				formValues += '&postalCode=' + encodeURIComponent($('#postalCode').val());
				formValues += '&followupAction=' + encodeURIComponent(action);
				formValues += '&patronId=' + encodeURIComponent(patronId);
				formValues += '&id=' + encodeURIComponent(id);
				formValues += '&method=createAccount';

				let ajaxUrl = Globals.path + "/RBdigital/AJAX?" + formValues;

				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons("Success", data.message, data.buttons);
						} else {
							AspenDiscovery.showMessage("Error", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						AspenDiscovery.showMessage("Error", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
					}
				});
			} else {
				AspenDiscovery.showMessage("Error", "You must be logged in before creating an RBdigital account.", false);
			}
			return false;
		},

		doCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				let ajaxUrl = Globals.path + "/RBdigital/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							if (data.noCopies === true) {
								AspenDiscovery.closeLightbox();
								let ret = confirm(data.message);
								if (ret === true) {
									AspenDiscovery.RBdigital.doHold(patronId, id);
								}
							} else {
								AspenDiscovery.showMessage(data.title, data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.RBdigital.checkOutTitle(id);
				}, false);
			}
			return false;
		},

		doMagazineCheckOut: function (patronId, id) {
			if (Globals.loggedIn) {
				let ajaxUrl = Globals.path + "/RBdigital/AJAX?method=checkOutMagazine&patronId=" + patronId + "&id=" + id;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function (data) {
						if (data.success === true) {
							AspenDiscovery.showMessageWithButtons("Magazine Checked Out Successfully", data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						} else {
							// noinspection JSUnresolvedVariable
							AspenDiscovery.showMessage("Error Checking Out Magazine", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function () {
						alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.RBdigital.checkOutMagazine(id);
				}, false);
			}
			return false;
		},

		doHold: function (patronId, id) {
			let url = Globals.path + "/RBdigital/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					// noinspection JSUnresolvedVariable
					if (data.availableForCheckout) {
						AspenDiscovery.RBdigital.doCheckOut(patronId, id);
					} else {
						AspenDiscovery.showMessage("Placed Hold", data.message, !data.hasWhileYouWait);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
				}
			});
		},

		getCheckOutPrompts: function (id) {
			let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		getMagazineCheckOutPrompts: function (id) {
			let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getMagazineCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		getHoldPrompts: function (id) {
			let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getHoldPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					result = data;
					// noinspection JSUnresolvedVariable
					if (data.promptNeeded) {
						// noinspection JSUnresolvedVariable
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function () {
					alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function (id) {
			if (Globals.loggedIn) {
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.RBdigital.getHoldPrompts(id, 'hold');
				// noinspection JSUnresolvedVariable
				if (!promptInfo.promptNeeded) {
					AspenDiscovery.RBdigital.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.RBdigital.placeHold(id);
				});
			}
			return false;
		},

		processCheckoutPrompts: function () {
			let id = $("#id").val();
			let checkoutType = $("#checkoutType").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			if (checkoutType === 'book') {
				return AspenDiscovery.RBdigital.doCheckOut(patronId, id);
			} else {
				return AspenDiscovery.RBdigital.doMagazineCheckOut(patronId, id);
			}
		},

		processHoldPrompts: function () {
			let id = $("#id").val();
			let patronId = $("#patronId option:selected").val();
			AspenDiscovery.closeLightbox();
			return AspenDiscovery.RBdigital.doHold(patronId, id);
		},

		renewCheckout: function (patronId, recordId) {
			let url = Globals.path + "/RBdigital/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					} else {
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, recordId) {
			let url = Globals.path + "/RBdigital/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Title Returned", data.message, true);
						$("#rbdigitalCheckout_" + recordId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
				}
			});
		},

		returnMagazine: function (patronId, recordId) {
			let url = Globals.path + "/RBdigital/AJAX?method=returnMagazine&patronId=" + patronId + "&recordId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage("Magazine Returned", data.message, true);
						$(".rbdigitalMagazineCheckout_" + recordId).hide();
						AspenDiscovery.Account.loadMenuData();
					} else {
						AspenDiscovery.showMessage("Error Returning Magazine", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function () {
					AspenDiscovery.showMessage("Error Returning Magazine", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
				}
			});
		},

		getStaffView: function (id) {
			let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		},

		getMagazineStaffView: function (id) {
			let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getMagazineStaffView";
			$.getJSON(url, function (data) {
				if (!data.success) {
					AspenDiscovery.showMessage('Error', data.message);
				} else {
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	}
}(AspenDiscovery.RBdigital || {}));
AspenDiscovery.Account.ReadingHistory = (function(){
	return {
		deleteEntry: function (patronId, id){
			if (confirm('The item will be irreversibly deleted from your reading history.  Proceed?')){
				let url = Globals.path + "/MyAccount/AJAX?method=deleteReadingHistoryEntry&patronId=" + patronId + "&permanentId=" + id;
				$.getJSON(url, function(data){
					if (data.success){
						$("#readingHistoryEntry" + id).hide();
					}else{
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(AspenDiscovery.ajaxFail);
			}
			return false;
		},

		deletedMarkedAction: function (){
			if (confirm('The marked items will be irreversibly deleted from your reading history.  Proceed?')){
				$('#readingHistoryAction').val('deleteMarked');
				$('#readingListForm').submit();
			}
			return false;
		},

		deleteAllAction: function (){
			if (confirm('Your entire reading history will be irreversibly deleted.  Proceed?')){
				$('#readingHistoryAction').val('deleteAll');
				$('#readingListForm').submit();
			}
			return false;
		},

		optOutAction: function (){
			if (confirm('Opting out of Reading History will also delete your entire reading history irreversibly.  Proceed?')){
				$('#readingHistoryAction').val('optOut');
				$('#readingListForm').submit();
			}
			return false;
		},

		optInAction: function (){
			$('#readingHistoryAction').val('optIn');
			$('#readingListForm').submit();
			return false;
		},

		exportListAction: function (){
			let url = Globals.path + "/MyAccount/AJAX?method=exportReadingHistory";
			document.location.href = url;
			return false;
		}
	};
}(AspenDiscovery.Account.ReadingHistory || {}));

AspenDiscovery.Record = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		showPlaceHold: function(module, source, id, volume){
			if (Globals.loggedIn){
				document.body.style.cursor = "wait";
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldForm&recordSource=" + source;
				if (volume !== undefined){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					document.body.style.cursor = "default";
					if (data.holdFormBypassed){
						if (data.success){
							AspenDiscovery.showMessage('Hold Placed Successfully', data.message, false, false);
							AspenDiscovery.Account.loadMenuData();
						}else{
							AspenDiscovery.showMessage('Hold Failed', data.message, false, false);
						}
					}
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHold(module, source, id, volume);
				}, false);
			}
			return false;
		},

		showPlaceHoldEditions: function (module, source, id, volume) {
			if (Globals.loggedIn){
				let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getPlaceHoldEditionsForm&recordSource=" + source;
				if (volume !== undefined){
					url += "&volume=" + volume;
				}
				$.getJSON(url, function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail);
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showPlaceHoldEditions(module, source, id, volume);
				}, false);
			}
			return false;

		},

		showBookMaterial: function(module, id){
			if (Globals.loggedIn){
				AspenDiscovery.loadingMessage();
				//var source; // source not used for booking at this time
				if (id.indexOf(":") > 0){
					let idParts = id.split(":", 2);
					//source = idParts[0];
					id = idParts[1];
				//}else{
				//	source = 'ils';
				}
				$.getJSON(Globals.path + "/" + module + "/" + id + "/AJAX?method=getBookMaterialForm", function(data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}).fail(AspenDiscovery.ajaxFail)
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Record.showBookMaterial(id);
				}, false)
			}
			return false;
		},

		submitBookMaterialForm: function(){
			let params = $('#bookMaterialForm').serialize();
			let module = $('#module').val();
			AspenDiscovery.showMessage('Scheduling', 'Processing, please wait.');
			$.getJSON(Globals.path + "/" + module +"/AJAX", params+'&method=bookMaterial', function(data){
				if (data.modalBody) AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
					// For errors that can be fixed by the user, the form will be re-displayed
				if (data.success) AspenDiscovery.showMessage('Success', data.message/*, true*/);
				else if (data.message) AspenDiscovery.showMessage('Error', data.message);
			}).fail(AspenDiscovery.ajaxFail);
		},

		submitHoldForm: function(){
			let id = $('#id').val();
			let autoLogOut = $('#autologout').prop('checked');
			let selectedItem = $('#selectedItem');
			let module = $('#module').val();
			let volume = $('#volume');
			let params = {
				'method': 'placeHold',
				pickupBranch: $('#pickupBranch').val(),
				selectedUser: $('#user').val(),
				cancelDate: $('#cancelDate').val(),
				recordSource: $('#recordSource').val(),
				account: $('#account').val(),
				rememberHoldPickupLocation: $('#rememberHoldPickupLocation').prop('checked')
			};
			if (autoLogOut){
				params['autologout'] = true;
			}
			if (selectedItem.length > 0){
				params['selectedItem'] = selectedItem.val();
			}
			if (volume.length > 0){
				params['volume'] = volume.val();
			}
			if (params['pickupBranch'] === 'undefined'){
				alert("Please select a location to pick up your hold when it is ready.");
				return false;
			}
			let holdType = $('#holdType');
			if (holdType.length > 0){
				params['holdType'] = holdType.val();
			}else{
				if ($('#holdTypeBib').attr('checked')){
					params['holdType'] = 'bib';
				}else{
					params['holdType'] = 'item';
				}
			}
			$.getJSON(Globals.path + "/" + module +  "/" + id + "/AJAX", params, function(data){
				if (data.success){
					if (data.needsItemLevelHold){
						$('.modal-body').html(data.message);
					}else{
						AspenDiscovery.showMessage('Hold Placed Successfully', data.message, false, autoLogOut);
						AspenDiscovery.Account.loadMenuData();
					}
				}else{
					AspenDiscovery.showMessage('Hold Failed', data.message, false, autoLogOut);
				}
			}).fail(AspenDiscovery.ajaxFail);
		},

		moreContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="none";
			document.getElementById('additionalContributors').style.display="block";
		},

		lessContributors: function(){
			document.getElementById('showAdditionalContributorsLink').style.display="block";
			document.getElementById('additionalContributors').style.display="none";
		},

		uploadPDF: function (id){
			let url = Globals.path + '/Record/' + id + '/AJAX?method=uploadPDF';
			let uploadPDFData = new FormData($("#uploadPDFForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadPDFData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		uploadSupplementalFile: function (id){
			let url = Globals.path + '/Record/' + id + '/AJAX?method=uploadSupplementalFile';
			let uploadSupplementalFileData = new FormData($("#uploadSupplementalFileForm")[0]);
			$.ajax({
				url: url,
				type: 'POST',
				data: uploadSupplementalFileData,
				dataType: 'json',
				success: function(data) {
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				},
				async: false,
				contentType: false,
				processData: false
			});
			return false;
		},

		deleteUploadedFile: function(id, fileId) {
			if (confirm("Are you sure you want to delete this file?")){
				let url = Globals.path + '/Record/' + id + '/AJAX?method=deleteUploadedFile&fileId=' +fileId;
				$.getJSON(url, function (data){
					AspenDiscovery.showMessage(data.title, data.message, true, data.success);
				});
			}
			return false;
		},

		getUploadPDFForm: function (id){
			let url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadPDFForm';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		getUploadSupplementalFileForm: function (id) {
			let url = Globals.path + '/Record/' + id + '/AJAX?method=getUploadSupplementalFileForm';
			$.getJSON(url, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileDownload: function( recordId, type) {
			let url = Globals.path + '/Record/' + recordId + '/AJAX';
			let params = {
				method: 'showSelectDownloadForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		selectFileToView: function( recordId, type) {
			let url = Globals.path + '/Record/' + recordId + '/AJAX';
			let params = {
				method: 'showSelectFileToViewForm',
				type: type,
			};
			$.getJSON(url, params, function (data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
			});
			return false;
		},

		downloadSelectedFile: function () {
			let id = $('#id').val();
			let fileType = $('#fileType').val();
			let selectedFile = $('#selectedFile').val();
			if (fileType === 'RecordPDF'){
				window.location = Globals.path + '/Record/' + id + '/DownloadPDF?fileId=' + selectedFile;
			}else{
				window.location = Globals.path + '/Record/' + id + '/DownloadSupplementalFile?fileId=' + selectedFile;
			}
			return false;
		},

		viewSelectedFile: function () {
			let id = $('#id').val();
			let selectedFile = $('#selectedFile').val();
			window.location = Globals.path + '/Files/' + selectedFile + '/ViewPDF';
			return false;
		},

		getStaffView: function (module, id) {
			let url = Globals.path + "/" + module + "/" + id + "/AJAX?method=getStaffView";
			$.getJSON(url, function (data){
				if (!data.success){
					AspenDiscovery.showMessage('Error', data.message);
				}else{
					$("#staffViewPlaceHolder").replaceWith(data.staffView);
				}
			});
		}
	};
}(AspenDiscovery.Record || {}));
AspenDiscovery.Responsive = (function(){
	$(function(){
		// auto adjust the height of the search box
		// (Only side bar search box for now)
		$('#lookfor', '#home-page-search').on( 'keyup', function (event ){
			$(this).height( 0 );
			if (this.scrollHeight < 32){
				$(this).height( 18 );
			}else{
				$(this).height( this.scrollHeight );
			}
		}).keyup(); //This keyup triggers the resize

		$('#lookfor').on( 'keydown', function (event ){
			if (event.which == 13 || event.which == 10){
				event.preventDefault();
				event.stopPropagation();
				$("#searchForm").submit();
				return false;
			}
		}).on( 'keypress', function (event ){
			if (event.which == 13 || event.which == 10){
				event.preventDefault();
				event.stopPropagation();
				return false;
			}
		})
	});

	try{
		var mediaQueryList = window.matchMedia('print');
		mediaQueryList.addListener(function(mql) {
			AspenDiscovery.Responsive.isPrint = mql.matches;
		});
	}catch(err){
		//For now, just ignore this error.
	}

	window.onbeforeprint = function() {
		AspenDiscovery.Responsive.isPrint = true;
	};


	return {
		originalSidebarHeight: -1,
		adjustLayout: function(){
			// get resolution
			var resolutionX = document.documentElement.clientWidth;

			if (resolutionX >= 768 && !AspenDiscovery.Responsive.isPrint) {
				//Make the sidebar and main content the same size
				var mainContentElement = $("#main-content-with-sidebar");
				var sidebarContentElem = $("#sidebar-content");

				if (AspenDiscovery.Responsive.originalSidebarHeight == -1){
					AspenDiscovery.Responsive.originalSidebarHeight = sidebarContentElem.height();
				}
				//var heightToTest = Math.min(sidebarContentElem.height(), AspenDiscovery.Responsive.originalSidebarHeight);
				var heightToTest = sidebarContentElem.height();
				var maxHeight = Math.max(mainContentElement.height() + 15, heightToTest);
				if (mainContentElement.height() + 15 != maxHeight){
					mainContentElement.height(maxHeight);
				}
				if (sidebarContentElem.height() != maxHeight){
					sidebarContentElem.height(maxHeight);
				}
			}
		}
	};
}(AspenDiscovery.Responsive || {}));
AspenDiscovery.ResultsList = (function(){
	return {
		statusList: [],
		seriesList: [],

		addIdToSeriesList: function(isbn){
			this.seriesList[this.seriesList.length] = isbn;
		},

		initializeDescriptions: function(){
			$(".descriptionTrigger").each(function(){
				let descElement = $(this);
				let descriptionContentClass = descElement.data("content_class");
				let options = {
					html: true,
					trigger: 'hover',
					title: 'Description',
					content: AspenDiscovery.ResultsList.loadDescription(descriptionContentClass)
				};
				descElement.popover(options);
			});
		},

		lessFacets: function(name){
			document.getElementById("more" + name).style.display="block";
			document.getElementById("narrowGroupHidden_" + name).style.display="none";
		},

		loadDescription: function(descriptionContentClass){
			var contentHolder = $(descriptionContentClass);
			return contentHolder[0].innerHTML;
		},

		moreFacets: function(name){
			document.getElementById("more" + name).style.display="none";
			document.getElementById("narrowGroupHidden_" + name).style.display="block";
		},

		moreFacetPopup: function(title, name){
			AspenDiscovery.showMessage(title, $("#moreFacetPopup_" + name).html());
		},

		multiSelectMoreFacetPopup: function(title, name){
			let button = "<a class='btn btn-primary' onclick='$(\"#facetPopup_" + name + "\").submit();'>Apply Filters</a>";
			AspenDiscovery.showMessageWithButtons(title, $("#moreFacetPopup_" + name).html(), button);
		},

		processMultiSelectMoreFacetForm: function(formId, fieldName){
			let newUrl = location.origin + location.pathname + "?";
			//Remove existing parameters for the facet from the url
			let existingQuery = location.search.substr(1);
			let firstTerm = true;
			if(existingQuery !== undefined){
				existingQuery = existingQuery.split('&');
				for(let i = 0; i < existingQuery.length; i++){
					let queryTerm = existingQuery[i].split('=');
					if (queryTerm[0] === 'filter[]'){
						//Check to see if we should include or not
						if (!queryTerm[1].startsWith(fieldName)){
							if (!firstTerm) {
								newUrl += "&";
							}else{
								firstTerm = false;
							}
							newUrl += existingQuery[i];
						}
					}else{
						if (!firstTerm){
							newUrl += "&";
						}else{
							firstTerm = false;
						}
						newUrl += existingQuery[i];
					}
				}
			}
			$(".modal-body " + formId + " input[type=checkbox]:checked").each(function() {
				if (!firstTerm) {
					newUrl += "&";
				} else {
					firstTerm = false;
				}
				let name = $(this).attr('name');
				let value = $(this).attr('value');
				newUrl += (name + '=' + value);
			});

			document.location.href = newUrl;
			return false;
		},

		toggleRelatedManifestations: function(manifestationId){
			let relatedRecordPopup = $('#relatedRecordPopup_' + manifestationId);
			if (relatedRecordPopup.is(":visible")){
				relatedRecordPopup.slideUp();
			}else{
				relatedRecordPopup.slideDown();
			}
			//relatedRecordPopup.toggleClass('hidden');
			return false;

		}

	};
}(AspenDiscovery.ResultsList || {}));

AspenDiscovery.Searches = (function(){
	$(document).ready(function(){
		AspenDiscovery.Searches.initAutoComplete();

		// Add Browser-stored showCovers setting to the search form if there is a stored value set, and
		// this is not a OPAC Machine, and the user is not logged in, and there is not a hidden value
		// already set in the search form.
		// This allows a preset showCovers setting to be sent back with the first search without requiring login or
		// a page reload on the search results page.
		if (!Globals.opac && !Globals.loggedIn && AspenDiscovery.hasLocalStorage() && $('input[name="showCovers"]').length === 0){
			let showCovers = window.localStorage.getItem('showCovers') || false;
			if (showCovers.length > 0) {
				$("<input>").attr({
					type: 'hidden',
					name: 'showCovers',
					value: showCovers
				}).appendTo('#searchForm');
			}
		}
	});
	return{
		searchGroups: [],
		curPage: 1,
		displayMode: 'list', // default display Mode for results
		displayModeClasses: { // browse mode to css class correspondence
			covers:'home-page-browse-thumbnails',
			list:''
		},

		getCombinedResults: function(fullId, shortId, source, searchTerm, searchType, numberOfResults){
			let url = Globals.path + '/Union/AJAX';
			let params = '?method=getCombinedResults&source=' + source + '&numberOfResults=' + numberOfResults + "&id=" + fullId + "&searchTerm=" + searchTerm + "&searchType=" + searchType;
			if ($('#hideCovers').is(':checked')){
				params += "&showCovers=off";
			}else{
				params += "&showCovers=on";
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					AspenDiscovery.showMessage("Error loading results", data.error);
				}else{
					$('#combined-results-section-results-' + shortId).html(data.results);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		combinedResultsDefinedOrder: [],
		reorderCombinedResults: function () {
			if ($('#combined-results-column-0').is(':visible')) {
				if ($('.combined-results-column-0', '#combined-results-column-0').length === 0){
					$('.combined-results-column-0').detach().appendTo('#combined-results-column-0');
					$('.combined-results-column-1').detach().appendTo('#combined-results-column-1');
				}
			} else {
				if ($('.combined-results-section', '#combined-results-all-column').length === 0) {
					$.each(AspenDiscovery.Searches.combinedResultsDefinedOrder, function (i, id) {
						el = $(id).parents('.combined-results-section').detach().appendTo('#combined-results-all-column');
					});
				}
			}
			return false;
		},

		getPreferredDisplayMode: function(){
			if (!Globals.opac && AspenDiscovery.hasLocalStorage()){
				temp = window.localStorage.getItem('searchResultsDisplayMode');
				if (AspenDiscovery.Searches.displayModeClasses.hasOwnProperty(temp)) {
					AspenDiscovery.Searches.displayMode = temp; // if stored value is empty or a bad value, fall back on default setting ("null" is returned from local storage when not set)
					$('input[name="view"]','#searchForm').val(AspenDiscovery.Searches.displayMode); // set the user's preferred search view mode on the search box.
				}
			}
		},

		toggleDisplayMode : function(selectedMode){
			let mode = this.displayModeClasses.hasOwnProperty(selectedMode) ? selectedMode : this.displayMode, // check that selected mode is a valid option
					searchBoxView = $('input[name="view"]','#searchForm'), // display mode variable associated with the search box
					paramString = AspenDiscovery.replaceQueryParam('page', '', AspenDiscovery.replaceQueryParam('view',mode)); // set view in url and unset page variable
			this.displayMode = mode; // set the mode officially
			this.curPage = 1; // reset js page counting
			if (searchBoxView) searchBoxView.val(this.displayMode); // set value in search form, if present
			if (!Globals.opac && AspenDiscovery.hasLocalStorage() ) { // store setting in browser if not an opac computer
				window.localStorage.setItem('searchResultsDisplayMode', this.displayMode);
			}
			if (mode === 'list') $('#hideSearchCoversSwitch').show(); else $('#hideSearchCoversSwitch').hide();
			location.replace(location.pathname + paramString); // reloads page without adding entry to history
		},

		getMoreResults: function(){
			let url = Globals.path + '/Search/AJAX',
					params = AspenDiscovery.replaceQueryParam('page', this.curPage+1)+'&method=getMoreSearchResults',
					divClass = this.displayModeClasses[this.displayMode];
			params = AspenDiscovery.replaceQueryParam('view', this.displayMode, params); // set the view url parameter just in case.
			if (params.search(/[?;&]replacementTerm=/) !== -1) {
				let searchTerm = location.search.split('replacementTerm=')[1].split('&')[0];
				params = AspenDiscovery.replaceQueryParam('lookfor', searchTerm, params);
			}
			$.getJSON(url+params, function(data){
				if (data.success === 'false'){
					AspenDiscovery.showMessage("Error loading search information", "Sorry, we were not able to retrieve additional results.");
				}else{
					let newDiv = $(data.records).hide();
					$('.'+divClass).filter(':last').after(newDiv);
					newDiv.fadeIn('slow');
					if (data.lastPage) $('#more-browse-results').hide(); // hide the load more results
					else AspenDiscovery.Searches.curPage++;
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		},

		initAutoComplete: function(){
			try{
				let searchTermInput = $("#lookfor");
				if (searchTermInput.length){
					searchTermInput.autocomplete({
						source:function(request,response){
							let url=Globals.path+"/Search/AJAX?method=getAutoSuggestList&searchTerm=" + $("#lookfor").val() + "&searchIndex=" + $("#searchIndex").val() + "&searchSource=" + $("#searchSource").val();
							$.ajax({
								url:url,
								dataType:"json",
								success:function(data){
									response(data);
								}
							});
						},
						position:{
							my:"left top",
							at:"left bottom",
							of:"#lookfor",
							collision:"none"
						},
						minLength:4,
						delay:600
					}).data('ui-autocomplete')._renderItem = function( ul, item ) {
						return $( "<li></li>" )
							.data( "ui-autocomplete-item", item.value )
							.append( '<a>' + item.label + '</a>' )
							.appendTo( ul );
					};
				}

			}catch(e){
				alert("error during autocomplete setup"+e);
			}
		},

		sendEmail: function(){
			if (Globals.loggedIn){
				let from = $('#from').val();
				let to = $('#to').val();
				let message = $('#message').val();
				let sourceUrl = window.location.href;

				let url = Globals.path + "/Search/AJAX";
				$.getJSON(url,
						{ // pass parameters as data
							method     : 'sendEmail'
							,from      : from
							,to        : to
							,message   : message
							,sourceUrl : sourceUrl
						},
						function(data) {
							if (data.result) {
								AspenDiscovery.showMessage("Success", data.message);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
						}
				);
			}
			return false;
		},

		loadSearchTypes: function(){
			let searchTypeElement = $("#searchSource");
			let catalogType = "catalog";
			if (searchTypeElement){
				let selectedSearchType = $(searchTypeElement.find(":selected"));
				if (selectedSearchType){
					catalogType = selectedSearchType.data("catalog_type");
				}
			}
			let url = "/Search/AJAX";
			$.getJSON(url,
				{ // pass parameters as data
					method : 'getSearchIndexes',
					searchSource : catalogType
				},
				function(data) {
					if (data.success) {
						let searchIndexElement = $("#searchIndex");
						if (searchIndexElement) {
							//Clear the existing options and load with the new ones
							searchIndexElement.empty();
							for(let searchIndex in data.searchIndexes) {
								let selected = "";
								if (searchIndex === data.selectedIndex){
									selected = " selected"
								}
								let defaultSearch = "";
								if (searchIndex === data.defaultSearchIndex){
									defaultSearch = " id='default_search_type'";
								}
								searchIndexElement.append("<option value='" + searchIndex + "'" + selected + defaultSearch + ">" + data.searchIndexes[searchIndex] + "</option>")
							}
						}
					}
				}
			);
		},

		loadExploreMoreBar: function(section, searchTerm){
			let url = Globals.path + "/Search/AJAX";
			let params = "method=loadExploreMoreBar&section=" + encodeURIComponent(section);
			params += "&searchTerm=" + encodeURIComponent(searchTerm);
			let fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#explore-more-bar-placeholder").html(data.exploreMoreBar);
						AspenDiscovery.initCarousels();
					}
				}
			);
		},

		lockFacet: function (clusterName) {
			event.stopPropagation();
			let url = Globals.path + "/Search/AJAX";
			let params = "method=lockFacet&facet=" + encodeURIComponent(clusterName);
			let fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#facetLock_lockIcon_" + clusterName).hide();
						$("#facetLock_unlockIcon_" + clusterName).show();
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},

		unlockFacet: function (clusterName) {
			event.stopPropagation();
			let url = Globals.path + "/Search/AJAX";
			let params = "method=unlockFacet&facet=" + encodeURIComponent(clusterName);
			let fullUrl = url + "?" + params;
			$.getJSON(fullUrl,
				function(data) {
					if (data.success === true){
						$("#facetLock_lockIcon_" + clusterName).show();
						$("#facetLock_unlockIcon_" + clusterName).hide();
					}else{
						AspenDiscovery.showMessage('Error', data.message, true);
					}
				}
			);
			return false;
		},
	}
}(AspenDiscovery.Searches || {}));
AspenDiscovery.SideLoads = (function(){
	return {
		deleteMarc: function (sideLoadId, fileName, fileIndex) {
			if (!confirm('Are you sure you want to delete this ' + fileName + '?')){
				return false;
			}
			let url = Globals.path + "/SideLoads/AJAX?method=deleteMarc&id=" + sideLoadId + "&file=" + fileName;
			let params = {
				method : 'deleteMarc',
				id: sideLoadId,
				file: fileName
			};

			$.getJSON(Globals.path + "/SideLoads/AJAX",params, function(data){
				if (data.success){
					$("#file" + fileIndex).hide();
				}else{
					AspenDiscovery.showMessage('Delete Failed', data.message, false, autoLogOut);
				}
			}).fail(AspenDiscovery.ajaxFail);
			return false;
		}
	}
}(AspenDiscovery.SideLoads || {}));
/**
 * Create a title scroller object for display
 * 
 * @param scrollerId - the id of the scroller which will hold the titles
 * @param scrollerShortName
 * @param container - a container to display if any titles are found
 * @param autoScroll - whether or not the selected title should change automatically
 * @param style - The style of the scroller:  vertical, horizontal, single or text-list
 * @return
 */
function TitleScroller(scrollerId, scrollerShortName, container,
		autoScroll, style) {
	this.scrollerTitles = [];
	this.currentScrollerIndex = 0;
	this.numScrollerTitles = 0;
	this.scrollerId = scrollerId;
	this.scrollerShortName = scrollerShortName;
	this.container = container;
	this.scrollInterval = 0;
	this.swipeInterval = 5;
	this.autoScroll = (typeof autoScroll == "undefined") ? false : autoScroll;
	this.style = (typeof style == "undefined") ? 'horizontal' : style;
}

TitleScroller.prototype.loadTitlesFrom = function(jsonUrl) {
	jsonUrl = decodeURIComponent(jsonUrl);
	var scroller = this,
			scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	scrollerBody.hide();
	$("#titleScrollerSelectedTitle" + this.scrollerShortName+",#titleScrollerSelectedAuthor" + this.scrollerShortName).html("");
	$(".scrollerLoadingContainer").show();
	$.getJSON(jsonUrl, function(data) {
		scroller.loadTitlesFromJsonData(data);
	}).error(function(){
		scrollerBody.html("Unable to load titles. Please try again later.").show();
		$(".scrollerLoadingContainer").hide();
	});
};

TitleScroller.prototype.loadTitlesFromJsonData = function(data) {
	var scroller = this,
			scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		if (data.error) throw {description:data.error}; // throw exceptions for server error messages.
		if (data.titles.length === 0){
			scrollerBody.html("No titles were found for this list. Please try again later.");
			$('#' + this.scrollerId + " .scrollerBodyContainer .scrollerLoadingContainer").hide();
			scrollerBody.show();
		}else{
			scroller.scrollerTitles = [];
			var i = 0;
			// TODO: try direct assignment instead of loop. don't see the need to loop, other than resetting key. plb
			$.each(data.titles, function(key, val) {
				scroller.scrollerTitles[i++] = val;
			});
			if (scroller.container && data.titles.length > 0) {
				$("#" + scroller.container).fadeIn();
			}
			scroller.numScrollerTitles = data.titles.length;
			if (this.style === 'horizontal' || this.style === 'vertical'){
				// vertical or horizontal scrollers should start in the middle of the data. plb 11-24-2014
				scroller.currentScrollerIndex = data.currentIndex;
			}else{
				scroller.currentScrollerIndex = 0;
			}
			//console.log('current index is : '+scroller.currentScrollerIndex);
			TitleScroller.prototype.updateScroller.call(scroller);
		}
	} catch (err) {
		//alert("error loading titles from data " + err.description);
		if (scrollerBody != null){
			scrollerBody.html("Error loading titles from data : '" + err.description + "' Please try again later.").show();
			$(".scrollerLoadingContainer").hide();
		}
		//else{
		//	//alert("Could not find scroller body for " + this.scrollerId);
		//}
	}
};

TitleScroller.prototype.updateScroller = function() {
	var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	try {
		var scrollerBodyContents = "",
				curScroller = this;
		if (this.style === 'horizontal'){
			for ( let i in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[i]['formattedTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.width(this.scrollerTitles.length * 300) // use a large enough interval to accomodate medium covers sizes
					.waitForImages(function() {
						TitleScroller.prototype.finishLoadingScroller.call(curScroller);
					});
		}else if (this.style === 'vertical'){
			for ( let j in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[j]['formattedTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.height(this.scrollerTitles.length * 131)
					.waitForImages(function() {
						//console.log(scrollerBody);
						TitleScroller.prototype.finishLoadingScroller.call(curScroller);
					});
		}else if (this.style === 'text-list'){
			for ( var i in this.scrollerTitles) {
				scrollerBodyContents += this.scrollerTitles[i]['formattedTextOnlyTitle'];
			}
			scrollerBody.html(scrollerBodyContents)
					.height(this.scrollerTitles.length * 40); //TODO re-calibrate

			TitleScroller.prototype.finishLoadingScroller.call(curScroller);
		}else{
			this.currentScrollerIndex = 0;
			scrollerBody.html(this.scrollerTitles[this.currentScrollerIndex]['formattedTitle']);
			TitleScroller.prototype.finishLoadingScroller.call(this);
		}
		
	} catch (err) {
		alert("error in updateScroller for scroller " + this.scrollerId + " " + err.description);
		scrollerBody.html("Error loading titles from data: '" + err + "' Please try again later.").show();
		$(".scrollerLoadingContainer").hide();
	}

};

TitleScroller.prototype.finishLoadingScroller = function() {
	$(".scrollerLoadingContainer").hide();
	//var scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody");
	//scrollerBody.show();
	$('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody").show();
	TitleScroller.prototype.activateCurrentTitle.call(this);
	var curScroller = this;

	// Whether we are hovering over an individual title or not.
	$('.scrollerTitle').bind('mouseover', {scroller: curScroller}, function() {
		curScroller.hovered = true;
		//console.log('over');
	}).bind('mouseout', {scroller: curScroller}, function() {
		curScroller.hovered = false;
		//console.log('out');
	});

	// Set initial state.
	curScroller.hovered = false;

	if (this.autoScroll && this.scrollInterval == 0){
		this.scrollInterval = setInterval(function() {
			// Only proceed if not hovering.
			if (!curScroller.hovered) {
				curScroller.scrollToRight();
			}
		}, 5000);
	}
};

TitleScroller.prototype.scrollToRight = function() {
	this.currentScrollerIndex++;
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.scrollToLeft = function() {
	this.currentScrollerIndex--;
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.swipeToRight = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex -= customSwipeInterval; // swipes progress the opposite of scroll buttons
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.swipeToLeft = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex += customSwipeInterval; // swipes progress the opposite of scroll buttons
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.swipeUp = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex -= customSwipeInterval;
	if (this.currentScrollerIndex < 0)
		this.currentScrollerIndex = this.numScrollerTitles - 1;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.swipeDown = function(customSwipeInterval) {
	customSwipeInterval  = (typeof customSwipeInterval === 'undefined') ? this.swipeInterval : customSwipeInterval;
	this.currentScrollerIndex += customSwipeInterval;
	if (this.currentScrollerIndex > this.numScrollerTitles - 1)
		this.currentScrollerIndex = 0;
	TitleScroller.prototype.activateCurrentTitle.call(this);
};

TitleScroller.prototype.activateCurrentTitle = function() {
	if (this.numScrollerTitles == 0) {
		return;
	}
	var scrollerTitles = this.scrollerTitles,
			scrollerShortName = this.scrollerShortName,
			currentScrollerIndex = this.currentScrollerIndex,
			scrollerBody = $('#' + this.scrollerId + " .scrollerBodyContainer .scrollerBody"),
			scrollerTitleId = "#scrollerTitle" + this.scrollerShortName + currentScrollerIndex;

	$("#tooltip").hide();  //Make sure to clear the current tooltip if any

	// Update the actual display
	if (this.style == 'horizontal'){
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		if ($(scrollerTitleId).length != 0) {
				var widthItemsLeft = $(scrollerTitleId).position().left,
						widthCurrent = $(scrollerTitleId).width(),
						containerWidth = $('#' + this.scrollerId + " .scrollerBodyContainer").width(),
						// center the book in the container
						leftPosition = -((widthItemsLeft + widthCurrent / 2) - (containerWidth / 2));
				scrollerBody.animate({
					left : leftPosition + "px"
				}, 400, function() {
					for ( var i in scrollerTitles) {
						var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
						$(scrollerTitleId2).removeClass('selected');
					}
					$(scrollerTitleId).addClass('selected');
				});
		}
	}else if (this.style == 'vertical'){
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		// Scroll Upwards/Downwards
		if ($(scrollerTitleId).length != 0) {
			//Move top of the current title to the top of the scroller.
			var relativeTopOfElement = $(scrollerTitleId).position().top,
					// center the book in the container
					topPosition = 25 - relativeTopOfElement;
			scrollerBody.animate( {
				top : topPosition + "px"
			}, 400, function() {
				for ( var i in scrollerTitles) {
					var scrollerTitleId2 = "#scrollerTitle" + scrollerShortName + i;
					$(scrollerTitleId2).removeClass('selected');
				}
				$(scrollerTitleId).addClass('selected');
			});
		}
	}else if (this.style == 'text-list'){
		// No Action Needed
	}else{
		$("#titleScrollerSelectedTitle" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['title']);
		$("#titleScrollerSelectedAuthor" + scrollerShortName).html(scrollerTitles[currentScrollerIndex]['author']);

		scrollerBody.left = "0px";
		scrollerBody.html(this.scrollerTitles[currentScrollerIndex]['formattedTitle']);
	}
};

/*
 * waitForImages 1.1.2
 * -----------------
 * Provides a callback when all images have loaded in your given selector.
 * http://www.alexanderdickson.com/
 *
 *
 * Copyright (c) 2011 Alex Dickson
 * Licensed under the MIT licenses.
 * See website for more info.
 *
 */

(function($) {
	$.fn.waitForImages = function(finishedCallback, eachCallback) {

		eachCallback = eachCallback || function() {};

		if ( ! $.isFunction(finishedCallback) || ! $.isFunction(eachCallback)) {
			throw {
				name: 'invalid_callback',
				message: 'An invalid callback was supplied.'
			};
		}

		var objs = $(this),
				allImgs = objs.find('img'),
				allImgsLength = allImgs.length,
				allImgsLoaded = 0;

		if (allImgsLength == 0) {
			finishedCallback.call(this);
		}else{
			//Don't wait more than 10 seconds for all images to load.
			setTimeout (function() {finishedCallback.call(this); }, 10000);
		}

		return objs.each(function() {
			var obj = $(this),
					imgs = obj.find('img');

			if (imgs.length == 0) {
				return;
			}

			imgs.each(function() {
				var image = new Image,
						imgElement = this;

				image.onload = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded == allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				//Also handle errors and aborts
				image.onabort = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded == allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				image.onerror = function() {
					allImgsLoaded++;
					eachCallback.call(imgElement, allImgsLoaded, allImgsLength);
					if (allImgsLoaded == allImgsLength) {
						finishedCallback.call(obj[0]);
					}
					return false;
				};

				image.src = this.src;
			});
		});
	};
})(jQuery);

AspenDiscovery.Websites = (function () {
	return {
		trackUsage: function (id) {
			let ajaxUrl = Globals.path + "/Websites/JSON?method=trackUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	};
}(AspenDiscovery.Websites || {}));
AspenDiscovery.Wikipedia = (function(){
	return{
		getWikipediaArticle: function(articleName){
			let url = Globals.path + "/Author/AJAX?method=getWikipediaData&articleName=" + articleName;
			$.getJSON(url, function(data){
				if (data.success) {
					// noinspection JSUnresolvedVariable
					$("#wikipedia_placeholder").html(data.formatted_article).fadeIn();
				}
			});
		}
	};
}(AspenDiscovery.Wikipedia));
