// xWalkTree3 r3, Copyright 2005-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWalkTree3(n,f,d,l,b)
{
  if (typeof l == 'undefined') l = 0;
  if (typeof b == 'undefined') b = 0;
  var v = f(n,l,b,d);
  if (!v) return 0;
  if (v == 1) {
    for (var c = n.firstChild; c; c = c.nextSibling) {
      if (c.nodeType == 1) {
        if (!l) ++b;
        if (!xWalkTree3(c,f,d,l+1,b)) return 0;
      }
    }
  }
  return 1;
}
