// xRound r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xRound(v, d)
{
  var f = Math.pow(10, d);
  return Math.round(v * f) / f;
}
