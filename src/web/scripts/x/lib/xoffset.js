// xOffset r1, Copyright 2009 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xOffset(c, p)
{
  var o = {x:0, y:0};
  c = xGetElementById(c);
  p = xGetElementById(p);
  while (c && c != p) {
    o.x += c.offsetLeft;
    o.y += c.offsetTop;
    c = c.offsetParent;
  }
  return o;
}
