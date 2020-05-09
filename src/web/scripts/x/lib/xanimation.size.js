// xAnimation.size r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.size = function(e,w,h,t,a,b,oe)
{
  var i = this;
  i.axes(2);
  i.a[0].i = xWidth(e); i.a[1].i = xHeight(e); // initial size
  i.a[0].t = Math.round(w); i.a[1].t = Math.round(h); // target size
  i.init(e,t,o,o,oe,a,b);
  i.run();
  function o(i) { xWidth(i.e, Math.round(i.a[0].v)); xHeight(i.e, Math.round(i.a[1].v)); } // onRun and onTarget
};
