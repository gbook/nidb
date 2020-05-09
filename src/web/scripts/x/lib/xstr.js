// xStr r2, Copyright 2001-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xStr(s)
{
  for (var i=0, l=arguments.length; i<l; ++i) {
    if (typeof(arguments[i]) !== 'string')
      return false;
  }
  return true;
}
