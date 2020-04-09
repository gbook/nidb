// xAnimation.corner r3, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
xAnimation.prototype.corner = function(e,c,x,y,t,a,b,oe) // needs more testing!
{
  var ex, ey, ew, eh, i = this;
  i.axes(2); // axes: x and y
  // target point
  i.a[0].t = x; i.a[1].t = y;
  // initial point
  ex = xLeft(e); ey = xTop(e);
  ew = xWidth(e); eh = xHeight(e);
  i.cornerStr = c.toLowerCase();
  switch (i.cornerStr) {
    case 'nw': i.a[0].i=ex; i.a[1].i=ey; break;
    case 'sw': i.a[0].i=ex; i.a[1].i=ey+eh; break;
    case 'ne': i.a[0].i=ex+ew; i.a[1].i=ey; break;
    case 'se': i.a[0].i=ex+ew; i.a[1].i=ey+eh; break;
    default: return;
  }
  // init and run
  i.init(e,t,h,h,oe,a,b);
  i.run();
  function h(i) { // onRun and onTarget
    var e = i.e, p = 'px', x = Math.round(i.a[0].v), y = Math.round(i.a[1].v),
      nwx = xLeft(e), nwy = xTop(e), // nw point
      sex = nwx + xWidth(e), sey = nwy + xHeight(e); // se point
    switch (i.cornerStr) {
      case 'nw': e.style.left=x+p; e.style.top=y+p; xResizeTo(e, sex-x, sey-y); break;
      case 'sw': e.style.left=x+p; xWidth(e,sex-x); xHeight(e,y-nwy); break;
      case 'ne': xWidth(e,x-nwx); e.style.top=y+p; xHeight(e,sey-y); break;
      case 'se': xWidth(e,x-nwx); xHeight(e,y-nwy); break;
    }
  }
};
