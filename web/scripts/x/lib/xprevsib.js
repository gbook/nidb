// xPrevSib r4, Copyright 2005-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xPrevSib(e,t)
{
  e = xGetElementById(e);
  var s = e ? e.previousSibling : null;
  while (s) {
    if (s.nodeType == 1 && (!t || s.nodeName.toLowerCase() == t.toLowerCase())){break;}
    s = s.previousSibling;
  }
  return s;
}
