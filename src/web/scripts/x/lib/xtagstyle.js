// xTagStyle adapted by Charles Belov, SFMTA, www.sfmta.com/webmaster,
// from xStyle r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTagStyle(sProp, sVal)
{
  var i, list;
  for (i = 2; i < arguments.length; ++i) {
    list = xGetElementsByTagName(arguments[i]);
    for (j = 0; j < list.length; ++j) {
      if (list[j].style) {
        try { list[j].style[sProp] = sVal; }
        catch (err) { list[j].style[sProp] = ''; } // ???
      }
    }
  }
}
