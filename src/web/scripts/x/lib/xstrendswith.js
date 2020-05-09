// xStrEndsWith r1, Copyright 2004-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xStrEndsWith( s, end )
{
  if( !xStr(s,end) ) return false;
  var l = s.length;
  var r = l - end.length;
  if( r > 0 ) return s.substring( r, l ) == end;
  return s == end;
}
