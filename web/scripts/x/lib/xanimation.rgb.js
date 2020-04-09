// xAnimation.rgb r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.rgb = function(e,p,v,t,a,b,oe)
{
  var i = this, co = xParseColor(xGetComputedStyle(e,p));
  i.axes(3); // axes: r, g and b
  i.a[0].i = co.r; i.a[1].i = co.g; i.a[2].i = co.b; // initial colors
  co = xParseColor(v);
  i.a[0].t = co.r; i.a[1].t = co.g; i.a[2].t = co.b; // target colors
  i.prop = xCamelize(p);
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    i.e.style[i.prop] = xRgbToHex(Math.round(i.a[0].v),Math.round(i.a[1].v),Math.round(i.a[2].v));
  }
};
