// xWalkToLast r1, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWalkToLast( oNode, fnVisit, skip, data )
{
  var r = null;
  if( oNode )
  {
    r=xWalkTree2(oNode,fnVisit,skip,data);if(r)return r;
    while(oNode)
    {
      var n=oNode;
      while(n=n.nextSibling){if(n!=skip){r=xWalkTree2(n,fnVisit,skip,data);if(r)return r;}}
      oNode=oNode.parentNode;
    }
  }
  return r;
}
