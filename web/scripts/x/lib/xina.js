// xInA r1, Copyright 2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xInA(a, v)
{
  if (a) {
    for (var i = 0, l = a.length; i < l; ++i) {
      if (a[i] === v) return i;
    }
  }
  return false;
}
