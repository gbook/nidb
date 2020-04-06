// xAnimation.line r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.line = function(e,x,y,t,a,b,oe)
{
  var i = this;
  i.axes(2);
  i.a[0].i = xLeft(e); i.a[1].i = xTop(e); // initial position
  i.a[0].t = Math.round(x); i.a[1].t = Math.round(y); // target position
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    i.e.style.left = Math.round(i.a[0].v) + 'px';
    i.e.style.top = Math.round(i.a[1].v) + 'px';
  }
};
