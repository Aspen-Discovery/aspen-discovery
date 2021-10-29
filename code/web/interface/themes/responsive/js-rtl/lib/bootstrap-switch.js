/* ========================================================================
 * bootstrap-switch - v3.0.0
 * http://www.bootstrap-switch.org
 * ========================================================================
 * Copyright 2012-2013 Mattia Larentis
 *
 * ========================================================================
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================================
 */

(function(){var t=[].slice;!function(e,o){"use strict";var n;return n=function(){function t(t,o){null==o&&(o={}),this.$element=e(t),this.options=e.extend({},e.fn.bootstrapSwitch.defaults,o,{state:this.$element.is(":checked"),size:this.$element.data("size"),animate:this.$element.data("animate"),disabled:this.$element.is(":disabled"),readonly:this.$element.is("[readonly]"),onColor:this.$element.data("on-color"),offColor:this.$element.data("off-color"),onText:this.$element.data("on-text"),offText:this.$element.data("off-text"),labelText:this.$element.data("label-text")}),this.$on=e("<span>",{"class":""+this.name+"-handle-on "+this.name+"-"+this.options.onColor,html:this.options.onText}),this.$off=e("<span>",{"class":""+this.name+"-handle-off "+this.name+"-"+this.options.offColor,html:this.options.offText}),this.$label=e("<label>",{"for":this.$element.attr("id"),html:this.options.labelText}),this.$wrapper=e("<div>"),this.$wrapper.addClass(function(t){return function(){var e;return e=[""+t.name],e.push(t.options.state?""+t.name+"-on":""+t.name+"-off"),null!=t.options.size&&e.push(""+t.name+"-"+t.options.size),t.options.animate&&e.push(""+t.name+"-animate"),t.options.disabled&&e.push(""+t.name+"-disabled"),t.options.readonly&&e.push(""+t.name+"-readonly"),t.$element.attr("id")&&e.push(""+t.name+"-id-"+t.$element.attr("id")),e.join(" ")}}(this)),this.$element.on("init",function(t){return function(){return t.options.on.init.call()}}(this)),this.$element.on("switchChange",function(t){return function(){return t.options.on.switchChange.call()}}(this)),this.$div=this.$element.wrap(e("<div>")).parent(),this.$wrapper=this.$div.wrap(this.$wrapper).parent(),this.$element.before(this.$on).before(this.$label).before(this.$off).trigger("init"),this._elementHandlers(),this._handleHandlers(),this._labelHandlers(),this._formHandler()}return t.prototype.name="bootstrap-switch",t.prototype._constructor=t,t.prototype.state=function(t,e){return"undefined"==typeof t?this.options.state:this.options.disabled||this.options.readonly?this.$element:(t=!!t,this.$element.prop("checked",t).trigger("change.bootstrapSwitch",e),this.$element)},t.prototype.toggleState=function(t){return this.options.disabled||this.options.readonly?this.$element:this.$element.prop("checked",!this.options.state).trigger("change.bootstrapSwitch",t)},t.prototype.size=function(t){return"undefined"==typeof t?this.options.size:(null!=this.options.size&&this.$wrapper.removeClass(""+this.name+"-"+this.options.size),this.$wrapper.addClass(""+this.name+"-"+t),this.options.size=t,this.$element)},t.prototype.animate=function(t){return"undefined"==typeof t?this.options.animate:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-animate"),this.options.animate=t,this.$element)},t.prototype.disabled=function(t){return"undefined"==typeof t?this.options.disabled:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-disabled"),this.$element.prop("disabled",t),this.options.disabled=t,this.$element)},t.prototype.toggleDisabled=function(){return this.$element.prop("disabled",!this.options.disabled),this.$wrapper.toggleClass(""+this.name+"-disabled"),this.options.disabled=!this.options.disabled,this.$element},t.prototype.readonly=function(t){return"undefined"==typeof t?this.options.readonly:(t=!!t,this.$wrapper[t?"addClass":"removeClass"](""+this.name+"-readonly"),this.$element.prop("readonly",t),this.options.readonly=t,this.$element)},t.prototype.toggleReadonly=function(){return this.$element.prop("readonly",!this.options.readonly),this.$wrapper.toggleClass(""+this.name+"-readonly"),this.options.readonly=!this.options.readonly,this.$element},t.prototype.onColor=function(t){var e;return e=this.options.onColor,"undefined"==typeof t?e:(null!=e&&this.$on.removeClass(""+this.name+"-"+e),this.$on.addClass(""+this.name+"-"+t),this.options.onColor=t,this.$element)},t.prototype.offColor=function(t){var e;return e=this.options.offColor,"undefined"==typeof t?e:(null!=e&&this.$off.removeClass(""+this.name+"-"+e),this.$off.addClass(""+this.name+"-"+t),this.options.offColor=t,this.$element)},t.prototype.onText=function(t){return"undefined"==typeof t?this.options.onText:(this.$on.html(t),this.options.onText=t,this.$element)},t.prototype.offText=function(t){return"undefined"==typeof t?this.options.offText:(this.$off.html(t),this.options.offText=t,this.$element)},t.prototype.labelText=function(t){return"undefined"==typeof t?this.options.labelText:(this.$label.html(t),this.options.labelText=t,this.$element)},t.prototype.destroy=function(){var t;return t=this.$element.closest("form"),t.length&&t.off("reset.bootstrapSwitch").removeData("bootstrap-switch"),this.$div.children().not(this.$element).remove(),this.$element.unwrap().unwrap().off(".bootstrapSwitch").removeData("bootstrap-switch"),this.$element},t.prototype._elementHandlers=function(){return this.$element.on({"change.bootstrapSwitch":function(t){return function(o,n){var i;return o.preventDefault(),o.stopPropagation(),o.stopImmediatePropagation(),i=t.$element.is(":checked"),i!==t.options.state?(t.options.state=i,t.$wrapper.removeClass(i?""+t.name+"-off":""+t.name+"-on").addClass(i?""+t.name+"-on":""+t.name+"-off"),n?void 0:(t.$element.is(":radio")&&e("[name='"+t.$element.attr("name")+"']").not(t.$element).prop("checked",!1).trigger("change.bootstrapSwitch",!0),t.$element.trigger("switchChange",{el:t.$element,value:i}))):void 0}}(this),"focus.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.$wrapper.addClass(""+t.name+"-focused")}}(this),"blur.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.$wrapper.removeClass(""+t.name+"-focused")}}(this),"keydown.bootstrapSwitch":function(t){return function(e){if(e.which&&!t.options.disabled&&!t.options.readonly)switch(e.which){case 32:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.toggleState();case 37:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.state(!1);case 39:return e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation(),t.state(!0)}}}(this)})},t.prototype._handleHandlers=function(){return this.$on.on("click.bootstrapSwitch",function(t){return function(){return t.state(!1),t.$element.trigger("focus.bootstrapSwitch")}}(this)),this.$off.on("click.bootstrapSwitch",function(t){return function(){return t.state(!0),t.$element.trigger("focus.bootstrapSwitch")}}(this))},t.prototype._labelHandlers=function(){return this.$label.on({"mousemove.bootstrapSwitch":function(t){return function(e){var o,n,i;if(t.drag)return n=(e.pageX-t.$wrapper.offset().left)/t.$wrapper.width()*100,o=25,i=75,o>n?n=o:n>i&&(n=i),t.$div.css("margin-left",""+(n-i)+"%"),t.$element.trigger("focus.bootstrapSwitch")}}(this),"mousedown.bootstrapSwitch":function(t){return function(){return t.drag||t.options.disabled||t.options.readonly?void 0:(t.drag=!0,t.options.animate&&t.$wrapper.removeClass(""+t.name+"-animate"),t.$element.trigger("focus.bootstrapSwitch"))}}(this),"mouseup.bootstrapSwitch":function(t){return function(){return t.drag?(t.drag=!1,t.$element.prop("checked",parseInt(t.$div.css("margin-left"),10)>-25).trigger("change.bootstrapSwitch"),t.$div.css("margin-left",""),t.options.animate?t.$wrapper.addClass(""+t.name+"-animate"):void 0):void 0}}(this),"mouseleave.bootstrapSwitch":function(t){return function(){return t.$label.trigger("mouseup.bootstrapSwitch")}}(this),"click.bootstrapSwitch":function(t){return function(e){return e.preventDefault(),e.stopImmediatePropagation(),t.toggleState(),t.$element.trigger("focus.bootstrapSwitch")}}(this)})},t.prototype._formHandler=function(){var t;return t=this.$element.closest("form"),t.data("bootstrap-switch")?void 0:t.on("reset.bootstrapSwitch",function(){return o.setTimeout(function(){return t.find("input").filter(function(){return e(this).data("bootstrap-switch")}).each(function(){return e(this).bootstrapSwitch("state",!1)})},1)}).data("bootstrap-switch",!0)},t}(),e.fn.bootstrapSwitch=function(){var o,i,s;return i=arguments[0],o=2<=arguments.length?t.call(arguments,1):[],s=this,this.each(function(){var t,r;return t=e(this),r=t.data("bootstrap-switch"),r||t.data("bootstrap-switch",r=new n(this,i)),"string"==typeof i?s=r[i].apply(r,o):void 0}),s},e.fn.bootstrapSwitch.Constructor=n,e.fn.bootstrapSwitch.defaults={state:!0,size:null,animate:!0,disabled:!1,readonly:!1,onColor:"primary",offColor:"default",onText:"ON",offText:"OFF",labelText:"&nbsp;",on:{init:function(){},switchChange:function(){}}}}(window.jQuery,window)}).call(this);