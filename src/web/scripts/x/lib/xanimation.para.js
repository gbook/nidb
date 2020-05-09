// xAnimation.para r4, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.para = function(e,xe,ye,inc,t,oe)
{
  var i = this;
  //i.axes(0); ???
  i.tt = t;
  if (!t) t = 1000;
  i.xe = xe; i.ye = ye; // x and y expression strings
  i.par = 0; i.inc = inc || .005;
  i.init(e,t,h,h,oe,0,0);
  i.run();
  function h(i) { // onRun and onTarget
    var p = i.e.offsetParent, xc, yc;
    xc = (xWidth(p)/2)-(xWidth(e)/2); yc = (xHeight(p)/2)-(xHeight(e)/2); // center of parent
    i.e.style.left = (Math.round((eval(i.xe) * xc) + xc) + xScrollLeft(p)) + 'px';
    i.e.style.top = (Math.round((eval(i.ye) * yc) + yc) + xScrollTop(p)) + 'px';
    i.par += i.inc;
    if (!i.tt) i.t += 1000; // yuck!
  }
};
