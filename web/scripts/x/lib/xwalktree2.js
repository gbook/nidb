// xWalkTree2 r1, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// My humble contribution to the great cross-browser xLibrary file x_dom.js
//
// This function is compatible with Mike's but adds:
// - 'fnVisit' can return a non null object to stop the walk. This result will be returned to caller.
// See function xFindAfterByClassName below: it uses it.
// - 'fnVisit' accept one optional 'data' parameter
// - 'skip' is an optional element that will be ignored during traversal. It is often useful to skip
// the starting node: when skip == oNode, 'fnVisit' is not called but, of course, child are processed.
function xWalkTree2( oNode, fnVisit, skip, data )
{
  var r=null;
  if(oNode){if(oNode.nodeType==1&&oNode!=skip){r=fnVisit(oNode,data);if(r)return r;}
  for(var c=oNode.firstChild;c;c=c.nextSibling){if(c!=skip)r  =xWalkTree2(c,fnVisit,skip,data);if(r)return r;}}
  return r;
}
