// xAnimation.opacity r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.opacity = function(e,o,t,a,b,oe)
{
  var i = this;
  i.axes(1);
  i.a[0].i = xOpacity(e); i.a[0].t = o; // initial and target opacity
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) {xOpacity(i.e, i.a[0].v);} // onRun and onTarget
};
