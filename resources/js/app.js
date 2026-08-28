import './bootstrap';

// Printing #doc-sheet in place (nested under the sidebar/table layout) was
// unreliable across browsers — moving it to be a direct child of <body> right
// before printing sidesteps every containing-block/overflow/pagination quirk in
// one go, then it's moved back to its original spot afterwards. Defined here
// (not an inline <script> in the Livewire view) so it survives Livewire's DOM
// morphing/navigate — inline scripts injected via a morph don't auto-execute.
window.printDocSheet = function () {
    var sheet = document.getElementById('doc-sheet');
    if (!sheet) {
        window.print();
        return;
    }
    var parent = sheet.parentNode;
    var next = sheet.nextSibling;
    document.body.appendChild(sheet);
    window.print();
    if (next) {
        parent.insertBefore(sheet, next);
    } else {
        parent.appendChild(sheet);
    }
};
