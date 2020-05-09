// xEnableDrop r3, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEnableDrop(id, f)
{
  var e = xGetElementById(id);
  if (e) {
    e.xDropEnabled = true;
    xEnableDrag.drops[xEnableDrag.drops.length] = {e:e, f:f};
  }
}

xEnableDrag.drop = function (el, ev) // static method
{
  var i, z, hz = 0, d = null, da = xEnableDrag.drops;
  for (i = 0; i < da.length; ++i) {
    if (da[i] && da[i].e.xDropEnabled && xHasPoint(da[i].e, ev.pageX, ev.pageY)) {
      z = parseInt(da[i].e.style.zIndex) || 0;
      if (z >= hz) {
        hz = z;
        d = da[i];
      } 
    }
  }
  if (d) {
    d.f(d.e, el, ev.pageX, ev.pageY); // drop event
  }
}
