// xAnimation.alphaArc r1, Copyright 2009-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.alphaArc = function(e,o,xr,yr,a1,a2,t,a,b,oe)
{
  var x0, y0, i = this;
  i.axes(2); // axes: angle and opacity
  /* arc */
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
  /* opacity */
  // initial and target opacity
  i.a[1].i = xOpacity(e);
  i.a[1].t = o;
  /* init and run */
  i.init(e,t,or,ot,oe,a,b);
  i.run();
  function or(i) { // onRun
    /* arc */
    i.e.style.left = (Math.round(i.xr * Math.cos(i.a[0].v) + i.xc - (xWidth(i.e) / 2))) + 'px';
    i.e.style.top = (Math.round(i.yr * Math.sin(i.a[0].v) + i.yc - (xHeight(i.e) / 2))) + 'px';
    /* opacity */
    xOpacity(i.e, i.a[1].v);
    //window.status = i.a[1].v;/////////////////
  }
  function ot(i) { // onTarget
    or(i);
    // ugh... but it works ;-)
    if (i.a[1].v == 1) {
      i.a[1].i = 1;
      i.a[1].t = 0;
    }
    else if (i.a[1].v == 0) {
      i.a[1].i = 0;
      i.a[1].t = 1;
    }
    i.a[1].v = i.a[1].t - i.a[1].i;
  }
};
