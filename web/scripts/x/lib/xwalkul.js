// xWalkUL r3, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xWalkUL(pu,d,f,lv)
{
  var r,cu,li=xFirstChild(pu);
  if (!lv){lv=0;}
  while(li){
    cu=xFirstChild(li,'ul');
    r=f(pu,li,cu,d,lv);
    if(cu){if(!r||!xWalkUL(cu,d,f,lv+1)){return 0;};}
    li=xNextSib(li);
  }
  return 1;
}
