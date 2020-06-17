// xAnimation.scroll r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.scroll = function(e,x,y,t,a,b,oe)
{
  var i = this;
  i.axes(2);
  i.init(e);
  i.win = i.e.nodeType==1 ? false:true;
  i.a[0].i = xScrollLeft(i.e, i.win); i.a[1].i = xScrollTop(i.e, i.win); // initial position
  i.a[0].t = Math.round(x); i.a[1].t = Math.round(y); // target position
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    var x = Math.round(i.a[0].v), y = Math.round(i.a[1].v);
    if (i.win) i.e.scrollTo(x, y);
    else { i.e.scrollLeft = x; i.e.scrollTop = y; }
  }
};
