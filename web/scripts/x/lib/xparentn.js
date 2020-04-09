// xParentN 2, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xParentN(e, n)
{
  while (e && n--) e = e.parentNode;
  return e;
}
