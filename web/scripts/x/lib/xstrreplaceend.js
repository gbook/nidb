// xStrReplaceEnd r1, Copyright 2004-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xStrReplaceEnd( s, newEnd )
{
  if( !xStr(s,newEnd) ) return s;
  var l = s.length;
  var r = l - newEnd.length;
  if( r > 0 ) return s.substring( 0, r )+newEnd;
  return newEnd;
}
