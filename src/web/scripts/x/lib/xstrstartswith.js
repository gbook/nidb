// xStrStartsWith r2, Copyright 2004-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xStrStartsWith( s, beg )
{
  if( !xStr(s,beg) ) return false;
  var l = s.length;
  var r = beg.length;
  if( r > l ) return false;
  if( r < l ) return s.substring( 0, r ) == beg;
  return s == beg;
}
