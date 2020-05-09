// xWalkTree r2, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWalkTree(n, f)
{
  f(n);
  for (var c = n.firstChild; c; c = c.nextSibling) {
    if (c.nodeType == 1) xWalkTree(c, f);
  }
}
