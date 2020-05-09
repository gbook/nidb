// xAnimation.rgbByClass r2, Copyright 2009-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.rgbByClass = function(cls,p,v,t,a,b,oe)
{
  var i = this, co, ea;
  i.axes(3);
  ea = xGetElementsByClassName(cls);
  co = xParseColor(xGetComputedStyle(ea[0],p));
  i.a[0].i = co.r; i.a[1].i = co.g; i.a[2].i = co.b; // initial colors
  co = xParseColor(v);
  i.a[0].t = co.r; i.a[1].t = co.g; i.a[2].t = co.b; // target colors
  i.prop = xCamelize(p);
  i.init(ea,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    // In this function i.e == ea
    for (var j = 0; j < i.e.length; ++j) {
      i.e[j].style[i.prop] = xRgbToHex(Math.round(i.a[0].v),Math.round(i.a[1].v),Math.round(i.a[2].v));
    }
  }
};
