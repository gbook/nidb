// xStrRepeat r1, Copyright 2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xStrRepeat(s, n)
{
  for (var r = s, i = 1; i < n; ++i) r += s;
  return r;
}
