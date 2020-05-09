// xWalkToFirst r1, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWalkToFirst( oNode, fnVisit, skip, data )
{
  var r = null;
  while(oNode)
  {
    if(oNode.nodeType==1&&oNode!=skip){r=fnVisit(oNode,data);if(r)return r;}
    var n=oNode;
    while(n=n.previousSibling){if(n!=skip){r=xWalkTreeRev(n,fnVisit,skip,data);if(r)return r;}}
    oNode=oNode.parentNode;
  }
  return r;
}
