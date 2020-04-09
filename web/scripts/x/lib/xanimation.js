// xAnimation r5, Copyright 2006-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAnimation(r)
{
  this.res = r || 10;
}
// Initialize an array of n axis objects.
xAnimation.prototype.axes = function(n)
{
  var j, i = this;
  if (!i.a || i.a.length != n) {
    i.a = [];
    for (j = 0; j < n; ++j) {
      i.a[j] = { i:0, t:0, d:0, v:0 }; // initial value, target value, displacement, instantaneous value
    }
  }
};
// The caller must set the axes' initial and target values before calling init.
xAnimation.prototype.init = function(e,t,or,ot,oe,at,b)
{
  var ai, i = this;
  i.e = xGetElementById(e);
  i.t = t;
  i.or = or; // onRun
  i.ot = ot; // onTarget
  i.oe = oe; // onEnd
  i.at = at || 0; // acceleration type
  i.v = xAnimation.vf[i.at];
  i.qc = 1 + (b || 0); // quarter-cycles
  i.fq = 1 / i.t; // frequency
  if (i.at > 0 && i.at < 4) {
    i.fq *= i.qc * Math.PI;
    if (i.at == 1 || i.at == 2) { i.fq /= 2; }
  }
  // displacements
  for (ai = 0; ai < i.a.length; ++ai) {
    i.a[ai].d = i.a[ai].t - i.a[ai].i;
  }
};
xAnimation.prototype.run = function(r)
{
  var ai, qcm2, rep, i = this;
  if (!r) { i.t1 = new Date().getTime(); }
  if (!i.tmr) i.tmr = setInterval(
    function() {
      i.et = new Date().getTime() - i.t1; // elapsed time
      if (i.et < i.t) {
        // instantaneous values
        i.f = i.v(i.et * i.fq);
        for (ai = 0; ai < i.a.length; ++ai) {
          i.a[ai].v = i.a[ai].d * i.f + i.a[ai].i;
        }
        i.or(i); // call onRun
      }
      else { // target time reached
        clearInterval(i.tmr);
        i.tmr = null;
        qcm2 = i.qc % 2;
        for (ai = 0; ai < i.a.length; ++ai) {
          if (qcm2) { i.a[ai].v = i.a[ai].t; }
          else { i.a[ai].v = i.a[ai].i; }
        }
        i.ot(i); // call onTarget
        // handle onEnd
        rep = false;
        if (typeof i.oe == 'function') { rep = i.oe(i); }
        else if (typeof i.oe == 'string') { rep = eval(i.oe); }
        if (rep) { i.resume(1); }
      }
    }, i.res
  );
};
xAnimation.prototype.pause = function()
{
  clearInterval(this.tmr);
  this.tmr = null;
};
xAnimation.prototype.resume = function(fs)
{
  if (typeof this.tmr != 'undefined' && !this.tmr) {
    this.t1 = new Date().getTime();
    if (!fs) {this.t1 -= this.et;}
    this.run(!fs);
  }
};
// Static array of velocity functions
xAnimation.vf = [
  function(r){return r;},
  function(r){return Math.abs(Math.sin(r));},
  function(r){return 1-Math.abs(Math.cos(r));},
  function(r){return (1-Math.cos(r))/2;},
  function(r) {return (1.0 - Math.exp(-r * 6));}
];
// end xAnimation
