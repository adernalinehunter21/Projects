// specify the fonts you would 
var fonts = ['Sans Serif', 'Serif', 'Sans', 'Arial Black', 'Courier New', 'Arial', 'Courier', 'Impact', 'Lucida Grande', 'Times', 'Tahoma', 'Verdana'];
// generate code friendly names
function getFontName(font) {
    return font.toLowerCase().replace(/\s/g, "-");
}
var fontNames = fonts.map(font => getFontName(font));
// add fonts to style
var fontStyles = "";
fonts.forEach(function (font) {
    var fontName = getFontName(font);
    fontStyles += ".ql-snow .ql-picker.ql-font .ql-picker-label[data-value=" + fontName + "]::before, .ql-snow .ql-picker.ql-font .ql-picker-item[data-value=" + fontName + "]::before {" +
            "content: '" + font + "';" +
            "font-family: '" + font + "', sans-serif;" +
            "}" +
            ".ql-font-" + fontName + "{" +
            " font-family: '" + font + "', sans-serif;" +
            "}";
});
var node = document.createElement('style');
node.innerHTML = fontStyles;
document.body.appendChild(node);

var toolbarOptions = [
    [{'header': [1, 2, 3, 4, 5, 6]}],
    [{'size': ['small', false, 'large', 'huge']}], // custom dropdown
    [{'font': fontNames}],
    ['bold', 'italic', 'underline', 'strike'], // toggled buttons
    ['blockquote', 'code-block', 'link'],
    [{'list': 'ordered'}, {'list': 'bullet'}],
    [{'indent': '-1'}, {'indent': '+1'}], // outdent/indent
    [{'color': []}, {'background': []}], // dropdown with defaults from theme
    [{'align': []}],
    [{'direction': 'rtl'}], // text direction
    ['clean']                                         // remove formatting button
];

// Add fonts to whitelist
var Font = Quill.import('formats/font');
Font.whitelist = fontNames;
Quill.register(Font, true);

var quill = new Quill('#editor-container', {
    modules: {
        toolbar: toolbarOptions
    },
    theme: 'snow'
});