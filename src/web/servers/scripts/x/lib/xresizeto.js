// xResizeTo r2, Copyright 2001-2009 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xResizeTo(e, w, h)
{
  return {
    w: xWidth(e, w),
    h: xHeight(e, h)
  };
}
