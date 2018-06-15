// xTableHeaderFixed r12, Copyright 2006-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

//------------------------------------------------------------------------------
// Yuck!!!
var xIE4Up, xIE8Up, xWebKit = navigator.userAgent.indexOf('AppleWebKit') > 0, xGecko = !xWebKit && navigator.product == 'Gecko';
/*@cc_on
@if (@_jscript)
  xIE4Up = true;
  @if (@_jscript_version > 5.7)
    xIE8Up = true;
  @end
@end @*/
//------------------------------------------------------------------------------

function xTableHeaderFixed(tCls, tCon, yOfs)
{
  // Public Methods

  this.init = function(a, b, c, d)
  {
    this.clean();
    return _ctor(a, b, c, d);
  };

  this.paint = function()
  {
    _event({type:'resize'});
  };

  this.clean = function()
  {
    _dtor();
  };

  // Private Properties
  var _i = this,
    _ic, // is collapse
    _ce, // container element
    _ta, // table array
    _iw, // container is window
    _bl = 0, _bt = 0, // container border left and top
    _yo; // y-offset

  // Constructor Code
  if (tCls) { _ctor(tCls, tCon, yOfs); }

  // Private Methods

  function _ctor(tCls, tCon, yOfs)
  {
    var i, h, t;
    _ta = xGetElementsByClassName(tCls, document, 'table');
    _ce = xGetElementById(tCon);
    if (!_ta || !_ta.length || !_ce) { return false; }
    if (!(_iw = xDef(_ce.location))) { _ce.scrollTop = 0; }
    _yo = yOfs;
    // Create a header table for each table with tCls.
    for (i = 0; i < _ta.length; ++i) {
      h = _ta[i].tHead;
      if (h) {
        t = document.createElement('table');
        t.style.position = (_iw ? 'fixed' : 'absolute');
        t.className = tCls + ' xthf-no-print';
        t.style.top = '0';
        if (_ta[i].cellSpacing !== '') { t.cellSpacing = _ta[i].cellSpacing; }
        t.appendChild(h.cloneNode(true));
        t.id = _ta[i].xthfHdrTblId = tCls + '-xthf' + i;
        _ta[i].xthfResize = 3; // do resize while > 0
        if (_iw && xNum(_yo)) {
          if (_yo === 0) { t.style.top = xPageY(_ta[i]) + 'px'; }
          else { t.style.top = _yo + 'px'; }
        }
        document.body.appendChild(t);
      }
      else {
        _ta[i] = null;
      }
    }
    _ic = xGetComputedStyle(_ta[0], 'border-collapse') == 'collapse';
    if (!_iw && !xIE8Up && !window.opera) {
      _bl = xGetComputedStyle(_ce, 'border-left-width', true),
      _bt = xGetComputedStyle(_ce, 'border-top-width', true);
    }
    _event({type:'resize'});
    xAddEventListener(_ce, 'scroll', _event, false);
    xAddEventListener(window, 'resize', _event, false);
    xAddEventListener(window, 'unload', _dtor, false);
    return true;
  }

  function _dtor()
  {
    var i, ht;
    if (_ce) {
      xRemoveEventListener(_ce, 'scroll', _event);
      xRemoveEventListener(window, 'resize', _event);
      xRemoveEventListener(window, 'unload', _dtor);
      // Remove the header tables from the DOM.
      for (i = 0; i < _ta.length; ++i) {
        ht = xGetElementById(_ta[i].xthfHdrTblId);
        if (ht) { document.body.removeChild(ht); }
        _ta[i] = null;
      }
      _ta = null;
      _ce = null;
    }
  }

  function _event(e) // handles scroll and resize events
  {
//    var s = ''; // DEBUG
    var i, r;
    e = e || window.event;
    r = e.type == 'resize';
    for (i = 0; i < _ta.length; ++i) {
//      if (s.length) s += ', '; // DEBUG
//      s += _paint(_ta[i], r); // DEBUG
      _paint(_ta[i], r);
    }
//    xConsole.log(s); // DEBUG
  }

  function _paint(t, r)
  {
//    var s = t.xthfHdrTblId + '['; // DEBUG
    var i, bl = 0, br = 0, c1, c2, cl, ct, ht, pl, sl, st, ty, thy;
    if (!t) { return; }
    ht = xGetElementById(t.xthfHdrTblId);
    st = xScrollTop(_ce, _iw) + (_yo || 0);
    if (_iw) { ty = xPageY(t); }
    else { ty = t.offsetTop; }
    thy = ty + t.rows[0].offsetTop;
    //if (!_iw && !xIE8Up && !window.opera) {
    if (!_iw && !window.opera) {
      thy -= xGetComputedStyle(t, 'border-top-width', 1);
    }

    // Hide or show
    if (st <= thy || st > ty + t.offsetHeight - ht.offsetHeight) {
      t.xthfResize = 3; // do resize while > 0
      if (_yo !== 0) {
        ht.style.left = '-10000px'; // hide it
//        s += 'hide'; // DEBUG
        return;
//        return s + ']'; // DEBUG
      }
//      else s += 'no-hide'; // DEBUG
    } // else show it...
//    else s += 'show'; // DEBUG

    // Position
    ht.style.left = (xPageX(t) - xScrollLeft(_ce, _iw) + _bl) + 'px';
    if (!_iw) {
      if (_yo === 0) { ht.style.top = (xPageY(t) + _bt) + 'px'; }
      else { ht.style.top = (xPageY(_ce) + _bt) + 'px'; }
    }

    // Resize
    if (t.xthfResize || r) {
      if (window.opera) {
        copyCssWidth(t, ht);
      }
      else {
        bl = xGetComputedStyle(t, 'border-left-width', 1);
        br = xGetComputedStyle(t, 'border-right-width', 1);
        if (xIE8Up || (xGecko && _ic)) {
          bl = br = 0;
        }
        else if (xWebKit && _ic) {
          bl = Math.round((bl + br) / 2);
          br = 0;
        }
        ht.style.width = (t.clientWidth + bl + br) + 'px';
      }
      c1 = xGetElementsByTagName('th', t.tHead);
      c2 = xGetElementsByTagName('th', ht.tHead);
      for (i = 0; i < c1.length; ++i) {
        if (xIE4Up) {
          c2[i].style.width = (c1[i].clientWidth - parseInt(c1[i].currentStyle.paddingLeft) - parseInt(c1[i].currentStyle.paddingRight)) + 'px';
        }
        else {
          copyCssWidth(c1[i], c2[i]);
        }
      }
      if (t.xthfResize > 0) --t.xthfResize;
//      s += (',size,ie8:' + xIE8Up + ',gecko:' + xGecko + ',wk:' + xWebKit + ',op:' + xDef(window.opera) + ',bl:' + xGetComputedStyle(t, 'border-left-width') + ',br:' + xGetComputedStyle(t, 'border-right-width') + ',bt:' + xGetComputedStyle(t, 'border-top-width')); // DEBUG
    }
//    else s += ',no-size'; // DEBUG

    // Clip
    if (!_iw) {
      sl = xScrollLeft(_ce);
      pl = xGetComputedStyle(_ce, 'padding-left', 1);
      cr = _ce.clientWidth + sl - pl;
      cl = sl - pl;
      ht.style.clip = 'rect(auto,' + cr + 'px,auto,' + (cl < 0 ? '0' : cl) + 'px)';
    }

//    return s + ']'; // DEBUG
  }
} // end xTableHeaderFixed

function copyCssWidth(s, d)
{
  d.style.borderLeftWidth = xGetComputedStyle(s, 'border-left-width');
  d.style.paddingLeft = xGetComputedStyle(s, 'padding-left');
  d.style.width = xGetComputedStyle(s, 'width');
  d.style.paddingRight = xGetComputedStyle(s, 'padding-right');
  d.style.borderRightWidth = xGetComputedStyle(s, 'border-right-width');

//  var x = ''; // DEBUG
//  x += (d.style.borderLeftWidth = xGetComputedStyle(s, 'border-left-width'));
//  x += ',' + (d.style.paddingLeft = xGetComputedStyle(s, 'padding-left'));
//  x += ',' + (d.style.width = xGetComputedStyle(s, 'width'));
//  x += ',' + (d.style.paddingRight = xGetComputedStyle(s, 'padding-right'));
//  x += ',' + (d.style.borderRightWidth = xGetComputedStyle(s, 'border-right-width'));
//  return x; // DEBUG
}
