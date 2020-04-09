// xHttpRequest r11, Copyright 2006-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xHttpRequest() // object prototype
{
  // Private Properties
  var
    _i = this, // instance object
    _r = null, // XMLHttpRequest object
    _t = null, // timer
    _f = null, // callback function
    _x = false, // XML response pending
    _o = null, // user data object passed to _f
    _c = false; // self-clean after send() completed?
  // Public Properties
  _i.OK = 0;
  _i.NOXMLOBJ = 1;
  _i.REQERR = 2;
  _i.TIMEOUT = 4;
  _i.RSPERR = 8;
  _i.NOXMLCT = 16;
  _i.ABORTED = 32;
  _i.status = _i.OK;
  _i.error = null;
  _i.busy = false;
  // Private Methods
  function _clean()
  {
    _i = null;
    _r = null;
    _t = null;
    _f = null;
    _x = false;
    _o = null;
    _c = false;
  }
  function _clrTimer()
  {
    if (_t) {
      clearTimeout(_t);
    }
    _t = null;
  }
  function _endCall()
  {
    if (_f) {
      _f(_r, _i.status, _o);
    }
    _f = null; _x = false; _o = null;
    _i.busy = false;
    if (_c) {
      _clean();
    }
  }
  function _abort(s)
  {
    _clrTimer();
    try {
      _r.onreadystatechange = function(){};
      _r.abort();
    }
    catch (e) {
      _i.status |= _i.RSPERR;
      _i.error = e;
    }
    _i.status |= s;
    _endCall();
  }
  function _newXHR()
  {
    try { _r = new XMLHttpRequest(); }
    catch (e) { try { _r = new ActiveXObject('Msxml2.XMLHTTP'); }
    catch (e) { try { _r = new ActiveXObject('Microsoft.XMLHTTP'); }
    catch (e) { _r = null; _i.error = e; }}}
    if (!_r) { _i.status |= _i.NOXMLOBJ; }
  }
  // Private Event Listeners
  function _oc() // onReadyStateChange
  {
    var ct;
    if (_r.readyState == 4) {
      _clrTimer();
      try {
        if (_r.status != 200) _i.status |= _i.RSPERR;
        if (_x) {
          ct = _r.getResponseHeader('Content-Type');
          if (ct && ct.indexOf('xml') == -1) { _i.status |= _i.NOXMLCT; }
        }
        delete _r['onreadystatechange']; // _r.onreadystatechange = null;
      }
      catch (e) {
        _i.status |= _i.RSPERR;
        _i.error = e;
      }
      _endCall();
    }
  }
  function _ot() // onTimeout
  {
    _t = null;
    _abort(_i.TIMEOUT);
  }
  // Public Methods
  this.send = function(m, u, d, t, r, x, o, f, c)
  {
    var q, ct;
    if (!_r || _i.busy) { return false; }
    _c = (c ? true : false);
    m = m.toUpperCase();
    q = (u.indexOf('?') >= 0);
    if (m != 'POST') {
      if (d) {
        u += (q ? '&' : '?') + d;
        q = true;
      }
      d = null;
    }
    if (r) {
      u += (q ? '&' : '?') + r + '=' + Math.random();
    }
    _x = (x ? true : false);
    _o = o;
    _f = f;
    _i.busy = true;
    _i.status = _i.OK;
    _i.error = null;
    if (t) { _t = setTimeout(_ot, t); }
    try {
      _r.open(m, u, true);
      if (m == 'GET') {
        _r.setRequestHeader('Cache-Control', 'no-cache');
        ct = 'text/' + (_x ? 'xml':'plain');
        if (_r.overrideMimeType) {_r.overrideMimeType(ct);}
        _r.setRequestHeader('Content-Type', ct);
      }
      else if (m == 'POST') {
        _r.setRequestHeader('Method', 'POST ' + u + ' HTTP/1.1');
        _r.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      }
      _r.onreadystatechange = _oc;
      _r.send(d);
    }
    catch(e) {
      _clrTimer();
      _f = null; _x = false; _o = null;
      _i.busy = false;
      _i.status |= _i.REQERR;
      _i.error = e;
      if (_c) {
        _clean();
      }
      return false;
    }
    return true;
  };
  this.abort = function()
  {
    if (!_r || !_i.busy) { return false; }
    _abort(_i.ABORTED);
    return true;
  };
  this.reinit = function()
  {
    // Halt any HTTP request that may be in progress.
    this.abort();
    // Set all private vars to initial state.
    _clean();
    _i = this;
    // Set all (non-constant) public properties to initial state.
    _i.status = _i.OK;
    _i.error = null;
    _i.busy = false;
    // Create the private XMLHttpRequest object.
    _newXHR();
    return true;
  };
  // Constructor Code
  _newXHR();
}
