// xTooltipGroup r10, Copyright 2002-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTooltipGroup(grpClassOrIdList, tipClass, origin, xOffset, yOffset, hideDelay, sticky, textList)
{
  //// Properties

  this.c = tipClass;
  this.o = origin;
  this.x = xOffset;
  this.y = yOffset;
  this.s = sticky;
  this.hd = hideDelay || 0;

  //// Constructor Code

  var i, tips;
  if (xStr(grpClassOrIdList)) {
    tips = xGetElementsByClassName(grpClassOrIdList);
    for (i = 0; i < tips.length; ++i) {
      tips[i].xTooltip = this;
      tips[i].xTooltipText = tips[i].title; // r10
      tips[i].title = '';                   // r10
    }
  }
  else {
    tips = new Array();
    for (i = 0; i < grpClassOrIdList.length; ++i) {
      tips[i] = xGetElementById(grpClassOrIdList[i]);
      if (!tips[i]) {
        alert('Element not found for id = ' + grpClassOrIdList[i]);
      }  
      else {
        tips[i].xTooltip = this;
        tips[i].xTooltipText = textList[i];
      }
    }
  }
  if (!xTooltipGroup.tipEle) { // only execute once
    var te = document.createElement("div");
    if (te) {
      te.id = 'xTooltipElement';
      xTooltipGroup.tipEle = te = document.body.appendChild(te);
      xAddEventListener(document, 'mousemove', xTooltipGroup.docOnMousemove, false);
    }
  }
} // end xTooltipGroup ctor

//// Static Properties

xTooltipGroup.tmr = null; // timer
xTooltipGroup.trgEle = null; // currently active trigger
xTooltipGroup.tipEle = null; // the tooltip element (all groups use the same element)

//// Static Methods

xTooltipGroup.docOnMousemove = function(oEvent)
{
  var t = null, e = new xEvent(oEvent);
  if (e.target) {
    t = e.target;
    while (t && !t.xTooltip) {
      t = t.offsetParent;
    }
    if (t) {
      t.xTooltip.show(t, e.pageX, e.pageY);
    }
    else if (xTooltipGroup.trgEle) {
      t = xTooltipGroup.trgEle.xTooltip;
      if (t && !t.s && !xTooltipGroup.tmr) {
        xTooltipGroup.tHide();
      }
    }
  }
};

xTooltipGroup.teOnClick = function()
{
  xTooltipGroup.hide();
};

xTooltipGroup.tHide = function()
{
  xTooltipGroup.tmr = setTimeout("xTooltipGroup.hide()", xTooltipGroup.trgEle.xTooltip.hd);
};

xTooltipGroup.hide = function()
{
  xMoveTo(xTooltipGroup.tipEle, -1000, -1000);
  xTooltipGroup.trgEle = null;
};

//// xTooltipGroup Public Method

xTooltipGroup.prototype.show = function(trigEle, mx, my)
{
  if (xTooltipGroup.tmr) {
    clearTimeout(xTooltipGroup.tmr);
    xTooltipGroup.tmr = null;
  }
  if (xTooltipGroup.trgEle != trigEle) { // if not active or moved to an adjacent trigger
    xTooltipGroup.tipEle.className = trigEle.xTooltip.c;
    xTooltipGroup.tipEle.innerHTML = trigEle.xTooltipText; // r10
//r9:    xTooltipGroup.tipEle.innerHTML = trigEle.xTooltipText ? trigEle.xTooltipText : trigEle.title;
    xTooltipGroup.trgEle = trigEle;
  }  
  if (this.s) {
    xTooltipGroup.tipEle.title = 'Click To Close';
    xTooltipGroup.tipEle.onclick = xTooltipGroup.teOnClick;
  }
  var x, y, tipW, trgW, trgX;
  tipW = xWidth(xTooltipGroup.tipEle);
  trgW = xWidth(trigEle);
  trgX = xPageX(trigEle);
  switch(this.o) {
    case 'right':
      if (trgX + this.x + trgW + tipW < xClientWidth()) { x = trgX + this.x + trgW; }
      else { x = trgX - tipW - this.x; }
      y = xPageY(trigEle) + this.y;
      break;
    case 'top':
      x = trgX + this.x;
      y = xPageY(trigEle) - xHeight(trigEle) + this.y;
      break;
    case 'mouse':
      if (mx + this.x + tipW < xClientWidth()) { x = mx + this.x; }
      else { x = mx - tipW - this.x; }
      y = my + this.y;
      break;
  }
  xMoveTo(xTooltipGroup.tipEle, x, y);
  xTooltipGroup.tipEle.style.visibility = 'visible';
};
