// xFindBeforeByClassName r1, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xFindBeforeByClassName( ele, clsName )
{
  var re = new RegExp('\\b'+clsName+'\\b', 'i');
  return xWalkToFirst( ele, function(n){if(n.className.search(re) != -1)return n;} );
}
