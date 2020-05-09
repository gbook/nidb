// xAnimation.arc r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.arc = function(e,xr,yr,a1,a2,t,a,b,oe)
{
  var x0, y0, i = this;
  i.axes(1); // axis: angle
  // initial and target angles
  i.a[0].i = a1 * (Math.PI / 180);
  i.a[0].t = a2 * (Math.PI / 180);
  // start point
  x0 = xLeft(e) + (xWidth(e) / 2);
  y0 = xTop(e) + (xHeight(e) / 2);
  // arc center point
  i.xc = x0 - (xr * Math.cos(i.a[0].i));
  i.yc = y0 - (yr * Math.sin(i.a[0].i));
  // ellipse radii
  i.xr = xr;
  i.yr = yr;
  // init and run
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    i.e.style.left = (Math.round(i.xr * Math.cos(i.a[0].v) + i.xc - (xWidth(i.e) / 2))) + 'px';
    i.e.style.top = (Math.round(i.yr * Math.sin(i.a[0].v) + i.yc - (xHeight(i.e) / 2))) + 'px';
  }
};
