// xInsertRule r2, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xInsertRule(ss, sel, rule, idx)
{
  if (!(ss=xGetElementById(ss))) return false;
  if (ss.insertRule) { ss.insertRule(sel + "{" + rule + "}", (idx>=0?idx:ss.cssRules.length)); } // DOM
  else if (ss.addRule) { ss.addRule(sel, rule, idx); } // IE
  else return false;
  return true;
}
