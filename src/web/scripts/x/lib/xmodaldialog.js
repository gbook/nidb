// xModalDialog r2, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xModalDialog(sDialogId) // Object Prototype
{
  /*@cc_on @if (@_jscript_version >= 5.5) @*/ //  not supported in IE until v5.5
  this.dialog = xGetElementById(sDialogId);
  xModalDialog.instances[sDialogId] = this;
  var e = xModalDialog.grey;
  if (!e) { // only one per page
    e = document.createElement('div');
    e.className = 'xModalDialogGreyElement';
    xModalDialog.grey = document.body.appendChild(e);
  }
  /*@end @*/
}
// Public Methods
xModalDialog.prototype.show = function()
{
  var ds, e = xModalDialog.grey;
  if (e) {
    this.dialog.greyZIndex = xGetComputedStyle(e, 'z-index', 1);
    e.style.zIndex = xGetComputedStyle(this.dialog, 'z-index', 1) - 1;
    ds = xDocSize();
    xMoveTo(e, 0, 0);
    xResizeTo(e, ds.w, ds.h);
    if (this.dialog) {
      xMoveTo(this.dialog,
              xScrollLeft()+(xClientWidth()-this.dialog.offsetWidth)/2,
              xScrollTop()+(xClientHeight()-this.dialog.offsetHeight)/2);
    }
  }
};
xModalDialog.prototype.hide = function(dialogOnly)
{
  var e = xModalDialog.grey;
  if (e) {
    if (!dialogOnly) {
      xResizeTo(e, 10, 10);
      xMoveTo(e, -10, -10);
    }
    if (this.dialog) {
      e.style.zIndex = this.dialog.greyZIndex;
      xMoveTo(this.dialog, -this.dialog.offsetWidth, 0);
    }
  }
};
// Static Properties
xModalDialog.grey = null;
xModalDialog.instances = {};
