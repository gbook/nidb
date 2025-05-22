// xTextArea r1, Copyright 2010 Krum Pet (bitbucket.org/krumpet)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTextArea(textArea) {

    function loadFrom(textArea) {

        var t = {}, // object describing contents of teaxtarea
            text, range, slice;

        t.update = function () { update(t) };

        t.ta = textArea;
        text = textArea.value;

        range = getSelectionRange(t.ta);
        t.scrollTop = t.ta.scrollTop;

        slice = function (s, e) {
            return normalize_newlines(text.substring(s, e) || '');
        }

        t.pre =  slice(0, range.start);
        t.sel = slice(range.start, range.end);
        t.post = slice(range.end, text.length);

        return t;
    }

    function getSelectionRange(textArea) {

        if (xDef(textArea.selectionStart)) {
            return {
                start: textArea.selectionStart,
                end: textArea.selectionEnd
            };
        }

        if( xDef(document.selection) ){

                rSel = document.selection.createRange();
                rPre = rSel.duplicate();
                rPre.moveToElementText( textArea )
                rPre.setEndPoint( 'EndToStart', rSel );

                return {
                    start: rPre.text.length,
                    end: rPre.text.length + rSel.text.length
                };
        }
        return {start: 0, end: 0};
    }

    function setSelectionRange(textArea, start, end) {

        if(xDef(textArea.setSelectionRange)) {
            textArea.setSelectionRange(start, end);
            return;
        }
        if(xDef(textArea.createTextRange)) {
            range = textArea.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    }

    function normalize_newlines(s) {
        return s.replace(/(?:\r\n)|\r/g, '\n');
    }

    function update(t) {

        var start = t.pre.length,
            end = start + t.sel.length,
            txt;

        t.ta.scrollTop = t.scrollTop;

        txt = normalize_newlines(t.pre + t.sel + t.post);
        t.ta.value = txt;

        if (xDef(t.ta.selectionStart)) {
            if (txt.length != t.ta.value.length) {
                start = start + t.pre.split('\n').length - 1;
                end = start + t.sel.length + t.sel.split('\n').length -1;
            }
        }
        setSelectionRange(t.ta, start, end);
    }

    textArea = xGetElementById(textArea);
    if (textArea) return loadFrom(textArea);

}

