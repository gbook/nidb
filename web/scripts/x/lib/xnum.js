// xNum r3, Copyright 2001-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xNum()
{
  for (var i=0, l=arguments.length; i<l; ++i) {
    if (isNaN(arguments[i]) || typeof(arguments[i]) !== 'number')
      return false;
  }
  return true;
}
