// xMenu6 r5, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xMenu6(sUlId, sMainUlClass, sSubUlClass, sLblLiClass, sItmLiClass, sLblAClass, sItmAClass, sPlusImg, sMinusImg, sImgClass, sItmPadLeft, bLblIsItm, sActiveItmId) // Object Prototype
{
  var me = this;
  xMenu6.instances[sUlId] = this;
  // Public Properties
  this.ul = xGetElementById(sUlId);
  this.pImg = sPlusImg;
  this.mImg = sMinusImg;
  // Private Event Listener
  function click(e)
  {
    if (this.xmChildUL) { // 'this' points to the A element clicked
      var s, uls = this.xmChildUL.style;
      if (uls.display != 'block') {
        s = sMinusImg;
        uls.display = 'block';
        xWalkUL(this.xmParentUL, this.xmChildUL,
          function(p,li,c,d) {
            if (c && c != d && c.style.display != 'none') {
              if (sPlusImg) {
                var a = xFirstChild(li,'a');
                xFirstChild(a,'img').src = sPlusImg;
              }
              c.style.display = 'none';
            }
            return true;
          }
        );
      }
      else {
        s = sPlusImg;
        uls.display = 'none';
      }
      if (sPlusImg) {
        xFirstChild(this,'img').src = s;
      }
      if (typeof this.blur() == 'function') {this.blur();}
      e = e || window.event;
      var t = e.target || e.srcElement;
      if (t.nodeName.toLowerCase() != 'img' && bLblIsItm) {
        return true; // click was on a label and bLblIsItm is true
      }
      return false; // click was on a label and bLblIsItm is false
    }
    return true; // click was on an item
  }
  // Constructor Code
  this.ul.className = sMainUlClass;
  xWalkUL(this.ul, null,
    function(p,li,c) {
      var liCls = sItmLiClass;
      var aCls = sItmAClass;
      var a = xFirstChild(li,'a');
      if (a) {
        var m = 'Click to toggle sub-menu';
        if (c) { // this LI is a label which precedes the submenu c
          if (sPlusImg) {
            // insert the image as the firstChild of the A element
            var i = document.createElement('img');
            i.title = m;
            a.insertBefore(i, a.firstChild);
            i.src = sPlusImg;
            i.className = sImgClass;
          }
          aCls = sLblAClass;
          liCls = sLblLiClass;
          c.className = sSubUlClass;
          c.style.display = 'none';
          a.title = bLblIsItm ? 'Click to follow link' : m;
          a.xmParentUL = p;
          a.xmChildUL = c;
          a.onclick = click;
        }
        else if (sPlusImg) { // this LI is not a label but is an item
          // if we are inserting images in label As then give A items some left padding
          a.style.paddingLeft = sItmPadLeft;
        }
        a.className = aCls;
      }
      li.className = liCls;
      return true;
    }
  );
  if (sActiveItmId) {
    this.open(sActiveItmId);
  }
  this.ul.style.visibility = 'visible';
  xAddEventListener(window, 'unload',
    function(){
      xWalkUL(me.ul, null,
        function(p,li,c) {
          var a = xFirstChild(li,'a');
          if (a && c) { a.xmParentUL = a.xmChildUL = a.onclick = null; }
          return true;
        }
      );
    }, false
  );
} // end xMenu6 prototype

// xMenu6 Public Methods
xMenu6.prototype.open = function (id)
{
  var img, ul, li, a = xGetElementById(id);
  while (a && ul != this.ul) {
    ul = a.xmChildUL;
    if (ul) {
      ul.style.display = 'block';
      if (this.pImg) {
        img = xFirstChild(a, 'img');
        if (img) {img.src = this.mImg;}
      }
    }
    li = a.parentNode; // LI
    ul = li.parentNode; // UL
    li = ul.parentNode; // LI
    a = xFirstChild(li, 'a');
  }
};

xMenu6.instances = {}; // static property
