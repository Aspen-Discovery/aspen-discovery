/*! jсarouselSwipe - v0.3.4 - 2016-02-18
* Copyright (c) 2015 Evgeniy Pelmenev; Licensed MIT */
(function($) {
    'use strict';

    $.jCarousel.plugin('jcarouselSwipe', {
        _options: {
            perSwipe: 1,
            draggable: true,
            method: 'scroll'
        },
        _init: function() {
            var self = this;
            this.carousel().on('jcarousel:reloadend', function() {
                self._reload();
            });
        },
        _create: function() {
            this._instance = this.carousel().data('jcarousel');
            this._instance._element.css('touch-action', (!this._instance.vertical ? 'pan-y' : 'pan-x'));
            this._carouselOffset = this.carousel().offset()[this._instance.lt] + parseInt(this.carousel().css((!this._instance.vertical ? 'border-left-width' : 'border-top-width'))) + parseInt(this.carousel().css((!this._instance.vertical ? 'padding-left' : 'padding-top')));
            this._slidesCount = this._instance.items().length;
            this.carousel().find('img, a')
                .attr('draggable', false)
                .css('user-select', 'none')
                .on('dragstart', function(event) { event.preventDefault() });

            this._destroy();

            if (this._instance.items().length > this._instance.fullyvisible().length) {
                this._initGestures();
            }
        },
        _initGestures: function() {
            var self = this;
            var startTouch = {};
            var currentTouch = {};
            var started = false;
            var xKey = !this._instance.vertical ? 'x' : 'y';
            var yKey = !this._instance.vertical ? 'y' : 'x';
            var edgeLT, lastItem;
            var startTarget;

            this._element.on('touchstart.jcarouselSwipe mousedown.jcarouselSwipe', dragStart);

            function dragStart(event) {
                event = event.originalEvent || event || window.event;
                startTouch = getTouches(event);
                startTarget = event.target || event.srcElement;

                if (self._options.draggable && !self._instance.animating) {
                    $(document).on('touchmove.jcarouselSwipe mousemove.jcarouselSwipe', dragMove);
                }
                $(document).on('touchend.jcarouselSwipe touchcancel.jcarouselSwipe mouseup.jcarouselSwipe', dragEnd);
            }

            function dragMove(event) {
                var delta, newLT, itemsOption;
                event = event.originalEvent || event || window.event;
                currentTouch = getTouches(event);
                var xDiff = Math.abs(startTouch[xKey] - currentTouch[xKey]);
                var yDiff = Math.abs(startTouch[yKey] - currentTouch[yKey]);

                if (started) {
                    event.preventDefault();
                }

                if (yDiff > 10 && yDiff > xDiff && !started) {
                    $(document).off('touchmove.jcarouselSwipe mousemove.jcarouselSwipe');
                    return;
                }

                if (!self._instance.animating && xDiff > 10 || started) {
                    delta = startTouch[xKey] - currentTouch[xKey];

                    if (!started) {
                        started = true;
                        self._addClones();
                        self._currentLT = self._getListPosition();
                        itemsOption = self._instance.options('items');
                        lastItem = ($.isFunction(itemsOption) ? itemsOption.call(self._instance) : self._instance.list().find(itemsOption)).last();
                        edgeLT = self._instance.rtl && !self._instance.vertical ?
                            (self._instance.dimension(self._instance.list()) - lastItem.position()[self._instance.lt] - self._instance.clipping()) :
                            (lastItem.position()[self._instance.lt] + self._instance.dimension(lastItem) - self._instance.clipping()) * -1;
                    }

                    if (self._instance._options.wrap === 'circular') {
                        newLT = self._currentLT - delta;
                    } else if (self._instance.rtl && !self._instance.vertical) {
                        newLT = Math.max(0, Math.min(self._currentLT - delta, edgeLT));
                    } else {
                        newLT = Math.min(0, Math.max(self._currentLT - delta, edgeLT));
                    }


                    self._setListPosition(newLT + 'px');
                }
            }

            function dragEnd(event) {
                event = event.originalEvent || event || window.event;
                currentTouch = getTouches(event);
                var xDiff = Math.abs(startTouch[xKey] - currentTouch[xKey]);
                var yDiff = Math.abs(startTouch[yKey] - currentTouch[yKey]);

                if (started || (!self._options.draggable && xDiff > 10 && xDiff > yDiff)) {
                    var newTarget = self._getNewTarget(startTouch[xKey] - currentTouch[xKey] > 0);
                    newTarget = self._instance._options.wrap === 'circular' ? newTarget.relative : newTarget.static;

                    if (startTarget === event.target) {
                        $(event.target).on("click.disable", function (event) {
                            event.stopImmediatePropagation();
                            event.stopPropagation();
                            event.preventDefault();
                            $(event.target).off("click.disable");
                        });
                    }

                    if (self._instance._options.wrap === 'circular') {
                        self._removeClones();
                        self._instance._items = null;
                    }

                    started = false;
                    self._instance[self._options.method](newTarget, function() {
                        if (self._instance._options.wrap !== 'circular') {
                            self._removeClones();
                            self._instance._items = null;
                        }
                    });

                }

                $(document).off('touchmove.jcarouselSwipe mousemove.jcarouselSwipe');
                $(document).off('touchend.jcarouselSwipe touchcancel.jcarouselSwipe mouseup.jcarouselSwipe');
            }

            function getTouches(event) {
                if (event.touches !== undefined && event.touches.length > 0) {
                    return {
                        x: event.touches[0].pageX,
                        y: event.touches[0].pageY
                    }
                } else if (event.changedTouches !== undefined && event.changedTouches.length > 0) {
                    return {
                        x: event.changedTouches[0].pageX,
                        y: event.changedTouches[0].pageY
                    }
                } else {
                    if (event.pageX !== undefined) {
                        return {
                            x: event.pageX,
                            y: event.pageY
                        }
                    } else {
                        return {
                            x: event.clientX,
                            y: event.clientY
                        }
                    }
                }
            }
        },
        _getNewTarget: function(isSwipeToLT) {
            var target = this._instance.target();
            var staticTarget = this._instance.index(target);
            var relativeTarget = 0;
            var isToNext = this._instance.rtl && !this._instance.vertical ? !isSwipeToLT : isSwipeToLT;
            var offsetDiff;

            if (this._options.draggable) {
                while(true) {
                    if (this._instance.rtl && !this._instance.vertical) {
                        offsetDiff = (target.offset()[this._instance.lt] + this._instance.dimension(target)) - (this._carouselOffset + this._instance.clipping());
                    } else {
                        offsetDiff = target.offset()[this._instance.lt] - this._carouselOffset;
                    }

                    if (!target.length ||
                        isSwipeToLT && offsetDiff >= 0 ||
                        !isSwipeToLT && offsetDiff <= 0) {
                        break;
                    }

                    if (isToNext) {
                        target = target.next();
                        if (!target.length) break;
                        staticTarget = staticTarget + 1;
                    } else {
                        target = target.prev();
                        if (!target.length) break;
                        staticTarget = staticTarget - 1;
                    }

                    relativeTarget++;
                }
            } else {
                staticTarget = isToNext ? staticTarget + 1 : staticTarget - 1;
                relativeTarget = 1;
            }

            if (isToNext) {
                staticTarget = staticTarget + Math.abs(relativeTarget - this._options.perSwipe * Math.ceil(relativeTarget / this._options.perSwipe));
            } else {
                staticTarget = staticTarget - Math.abs(relativeTarget - this._options.perSwipe * Math.ceil(relativeTarget / this._options.perSwipe));
            }

            if (this._instance._options.wrap === 'first') {
                staticTarget = Math.min(this._slidesCount - 1, staticTarget);
            } else if (this._instance._options.wrap === 'last') {
                staticTarget = Math.max(0, staticTarget);
            } else if (!this._instance._options.wrap) {
                staticTarget = Math.max(0, Math.min(this._slidesCount - 1, staticTarget));
            }

            staticTarget = staticTarget % this._slidesCount;
            relativeTarget = this._options.perSwipe * Math.ceil(relativeTarget / this._options.perSwipe);

            return {
                static: staticTarget,
                relative: (isToNext ? '+' : '-') + '=' + relativeTarget
            };
        },
        _getListPosition: function() {
            var list = this._instance.list();
            var position = list.position();

            if (this._instance.rtl) {
                position.left = list.width() + position.left - this._carousel.width();
            }

            return position[this._instance.lt];
        },
        _setListPosition: function(position) {
            var option       = this._instance.options('transitions');
            var transforms   = !!option.transforms;
            var transforms3d = !!option.transforms3d;
            var css = {};
            var isLeft = this._instance.lt === 'left';
            position = position || 0;

            if (transforms3d) {
                css.transform = 'translate3d(' + (isLeft ? position : 0) + ',' + (isLeft ? 0 : position) + ',0)';
            } else if (transforms) {
                css.transform = 'translate(' + (isLeft ? position : 0) + ',' + (isLeft ? 0 : position) + ')';
            } else {
                css[this._instance.lt] = position;
            }

            this._instance.list().css(css);
        },
        _addClones: function() {
            var self = this;
            var inst = this._instance;
            var items = inst.items();
            var first = inst.first();
            var last = inst.last();
            var clip = inst.dimension($(window));
            var curr;
            var wh;
            var index;
            var clonesBefore = [];
            var clonesAfter = [];
            var lt = self._getListPosition();
            var moveObj = {};

            if (!inst._options.wrap) {
                return false;
            }

            if (inst._options.wrap !== 'last') {
                for (wh = 0, index = 0, curr = first; wh < clip;) {
                    curr = curr.prev();

                    if (curr.length === 0) {
                        index = --index < -items.length ? -1 : index;
                        wh += inst.dimension(items.eq(index));
                        clonesBefore.push(items.eq(index).clone().attr('data-jcarousel-clone', true));
                    } else {
                        wh += inst.dimension(curr);
                    }
                }

                lt = (inst.rtl ? Math.max(lt, wh) : Math.min(lt, - wh)) + 'px';
                inst._items.first().before(clonesBefore.reverse());
                moveObj[inst.lt] = lt;
                inst.move(moveObj);
            }

            if (inst._options.wrap !== 'first') {
                for (wh = 0, index = -1, curr = last; wh < clip;) {
                    curr = curr.next();

                    if (curr.length === 0) {
                        index = ++index > items.length - 1 ? 0 : index;
                        wh += inst.dimension(items.eq(index));
                        clonesAfter.push(items.eq(index).clone().attr('data-jcarousel-clone', true));
                    } else {
                        wh += inst.dimension(curr);
                    }
                }

                inst._items.last().after(clonesAfter);
            }
        },
        _removeClones: function() {
            var startPosition = this._instance.first().position()[this._instance.lt];
            var removeCLonesWidth;
            var moveObj = {};
            this._instance.list().find('[data-jcarousel-clone]').remove();
            removeCLonesWidth = startPosition - this._instance.first().position()[this._instance.lt];
            if (removeCLonesWidth) {
                moveObj[this._instance.lt] = this._getListPosition() + removeCLonesWidth + 'px';
                this._instance.move(moveObj);
            }
        },
        _destroy: function() {
            this._element.off('touchstart.jcarouselSwipe mousedown.jcarouselSwipe');
            $(document).off('touchmove.jcarouselSwipe mousemove.jcarouselSwipe touchend.jcarouselSwipe touchcancel.jcarouselSwipe mouseup.jcarouselSwipe');
        },
        _reload: function() {
            this._create();
        }
    });
}(jQuery));