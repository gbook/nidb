// xFirstChild r4, Copyright 2004-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xFirstChild(e,t)
{
  e = xGetElementById(e);
  var c = e ? e.firstChild : null;
  while (c) {
    if (c.nodeType == 1 && (!t || c.nodeName.toLowerCase() == t.toLowerCase())){break;}
    c = c.nextSibling;
  }
  return c;
}
