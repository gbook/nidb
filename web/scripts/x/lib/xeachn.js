// xEachN r2, Copyright 2007-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEachN(p,i,d,f)
{
  var e = xGetElementById(p + i);
  while (e) {
    f(e, i, d);
    e = xGetElementById(p + (++i));
  }
}
