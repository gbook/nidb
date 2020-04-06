// xSequence r1, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xSequence(seq) // object prototype
{
  // Private Properties
  var ai = 0; // current action index of seq
  var stop = true;
  var running = false;
  // Private Method
  function runSeq()
  {
    if (stop) {
      running = false;
      return;
    }
    if (this.onslideend) this.onslideend = null; // during a slideend callback
    if (ai >= seq.length) ai = 0;
    var i = ai;
    ++ai;
    if (seq[i][0] != -1) {
      setTimeout(runSeq, seq[i][0]);
    }
    else {
      if (seq[i][2] && seq[i][2][0]) seq[i][2][0].onslideend = runSeq;
    }
    if (seq[i][1]) {
      if (seq[i][2]) seq[i][1].apply(window, seq[i][2]);
      else seq[i][1]();
    }
  }
  // Public Methods
  this.run = function(si)
  {
    if (!running) {
      if (xDef(si) && si >=0 && si < seq.length) ai = si;
      stop = false;
      running = true;
      runSeq();
    }
  };
  this.stop = function()
  {
    stop = true;
  };
  this.onUnload = function() // is this needed? do I have circular refs?
  {                          // this should already have been done above, don't think it's needed
    if (!window.opera) {
      for (var i=0; i<seq.length; ++i) {
        if (seq[i][2] && seq[i][2][0] && seq[i][2][0].onslideend) seq[i][2][0].onslideend = runSeq;
      }
    }
  };
}
