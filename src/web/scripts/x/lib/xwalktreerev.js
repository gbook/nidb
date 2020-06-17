// xWalkTreeRev r2, Copyright 2005-2007 Olivier Spinelli
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// Same as xWalkTree2 except that traversal is reversed (last to first child).
function xWalkTreeRev( oNode, fnVisit, skip, data )
{
  var r=null;
  if(oNode){if(oNode.nodeType==1&&oNode!=skip){r=fnVisit(oNode,data);if(r)return r;}
  for(var c=oNode.lastChild;c;c=c.previousSibling){if(c!=skip)r=xWalkTreeRev(c,fnVisit,skip,data);if(r)return r;}}
  return r;
}
