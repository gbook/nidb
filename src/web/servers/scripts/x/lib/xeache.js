// xEachE r2, Copyright 2009-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xEachE()
{
  var i, a = arguments, n = a.length - 1;
  for (i = 0; i < n; ++i) {
    a[n](xGetElementById(a[i]), i);
  }
}
