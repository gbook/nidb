// xAnimation.css r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.css = function(e,p,v,t,a,b,oe)
{
  var i = this;
  i.axes(1);
  i.a[0].i = xGetComputedStyle(e,p,true); // initial value
  i.a[0].t = v; // target value
  i.prop = xCamelize(p);
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) {i.e.style[i.prop] = Math.round(i.a[0].v) + 'px';}
};
