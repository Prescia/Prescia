/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

/*
config.toolbar_Full =
[
    ['Source','-','Save','NewPage','Preview','-','Templates'],
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
    ['BidiLtr', 'BidiRtl'],
    '/',
    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['Link','Unlink','Anchor'],
    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
    '/',
    ['Styles','Format','Font','FontSize'],
    ['TextColor','BGColor'],
    ['Maximize', 'ShowBlocks','-','About']
];
*/

CKEDITOR.editorConfig = function( config )
{

	// Define changes to default configuration here. For example:
	config.language = 'pt-br';
	config.uiColor = '#eeeeee';
	config.toolbar = 'MyToolbar';
	//config.forcePasteAsPlainText = true;
	//config.forceSimpleAmpersand = true;
	//config.htmlEncodeOutput = true; // < turns &lt; when you save
	config.entities = false;
	config.removePlugins = 'elementspath,save,scayt,templates';
	config.resize_dir = 'vertical';
	config.tabSpaces = 4;
	config.skin = 'office2003';
	config.disableNativeSpellChecker = false;
	// semantics:
	config.coreStyles_bold = { element : 'b', overrides : 'strong' };
	config.coreStyles_italic = { element : 'i', overrides : 'em' };


    config.toolbar_MyToolbar =
    [
        ['Maximize'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
        ['Cut','Copy','Paste','PasteText','PasteFromWord','RemoveFormat'],
        ['Undo','Redo','-','Find','Replace','SelectAll'],
        ['Image','Table','HorizontalRule'],
        ['Link','Unlink'],
        ['About'],
        '/',
        ['Styles','Font','FontSize','TextColor'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Bold','Italic','Subscript','Superscript'],
        ['Source']
    ];
    config.toolbar_MiniToolbar =
        [
            ['Maximize','-','Cut','Copy','Paste','PasteText','PasteFromWord','RemoveFormat'],
            ['Undo','Redo','-','Find','Replace','SelectAll'],
            ['Link','Unlink','-','Bold','Italic','Strike'],
            ['Source'],
            ['About']
        ];
    config.toolbar_PubToolbar =
        [
            ['Maximize','-','Cut','Copy','Paste','PasteText','PasteFromWord','RemoveFormat'],
            ['Undo','Redo'],
            ['FontSize','TextColor','Styles'],
            ['Link','Unlink','-','Bold','Italic','Strike'],
            ['Outdent','Indent','Blockquote'],
            ['Source']
        ];
};
