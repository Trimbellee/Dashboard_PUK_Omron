<?php

return [
    'encoding'           => 'UTF-8',
    'finalize'           => true,
    'ignoreNonStrings'   => false,
    'cachePath'          => storage_path('app/purifier'),
    'cacheFileMode'      => 0755,

    'settings' => [
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'p[style|class],br,strong,em,u,s,sub,sup,blockquote,pre[class],code[class],' .
                                            'h1[style|class],h2[style|class],h3[style|class],h4,h5,h6,' .
                                            'ol[style|class],ul[style|class],li[style|class],' .
                                            'a[href|target|rel|class],' .
                                            'img[src|alt|width|height|style|class|title],' .
                                            'iframe[src|width|height|frameborder|class],' .
                                            'table[style|class],thead,tbody,tr,th[colspan|rowspan|style|class],' .
                                            'td[colspan|rowspan|style|class],span[style|class],hr,div[class|style]',
            'CSS.AllowedProperties'    => 'color,background-color,text-align,direction,width,height,float,margin,margin-left,margin-right,display,font-family,font-weight,font-size,line-height,list-style-type,text-decoration,vertical-align',
            'HTML.SafeIframe'          => true,
            'URI.SafeIframeRegexp'     => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
        ],

        'test' => [
            'Attr.EnableID' => 'true',
        ],

        'youtube' => [
            'HTML.SafeIframe'      => 'true',
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ],

        'quill' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'p[style|class],br,strong,em,u,s,sub,sup,blockquote,' .
                                            'pre[class],code[class],h1[style|class],h2[style|class],h3[style|class],' .
                                            'ol[style|class],ul[style|class],li[style|class],' .
                                            'a[href|target|rel|class],' .
                                            'img[src|alt|width|height|style|class|title],' .
                                            'iframe[src|width|height|frameborder|class],' .
                                            'table[style|class],thead,tbody,tr,th[colspan|rowspan|style|class],' .
                                            'td[colspan|rowspan|style|class],span[style|class],hr,div[class|style]',
            'CSS.AllowedProperties'    => 'color,background-color,text-align,direction,' .
                                            'width,height,float,margin,margin-left,margin-right,' .
                                            'display,font-family,font-weight,font-size,line-height,' .
                                            'list-style-type,text-decoration,vertical-align',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
            'HTML.SafeIframe'          => true,
            'URI.SafeIframeRegexp'     => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%',
        ],
    ],

    // PENTING: ini harus SEJAJAR dengan 'settings', BUKAN di dalamnya.
    'custom_definition' => [
        'id'    => 'html5-definitions',
        'rev'   => 2,
        'debug' => false,
        'elements' => [
            ['section', 'Block', 'Flow', 'Common'],
            ['nav',     'Block', 'Flow', 'Common'],
            ['article', 'Block', 'Flow', 'Common'],
            ['aside',   'Block', 'Flow', 'Common'],
            ['header',  'Block', 'Flow', 'Common'],
            ['footer',  'Block', 'Flow', 'Common'],
            ['address', 'Block', 'Flow', 'Common'],
            ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
            ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
            ['figcaption', 'Inline', 'Flow', 'Common'],
            ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                'src' => 'URI',
                'type' => 'Text',
                'width' => 'Length',
                'height' => 'Length',
                'poster' => 'URI',
                'preload' => 'Enum#auto,metadata,none',
                'controls' => 'Bool',
            ]],
            ['source', 'Block', 'Flow', 'Common', [
                'src' => 'URI',
                'type' => 'Text',
            ]],
            ['s',    'Inline', 'Inline', 'Common'],
            ['var',  'Inline', 'Inline', 'Common'],
            ['sub',  'Inline', 'Inline', 'Common'],
            ['sup',  'Inline', 'Inline', 'Common'],
            ['mark', 'Inline', 'Inline', 'Common'],
            ['wbr',  'Inline', 'Empty', 'Core'],
            ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
        ],
        'attributes' => [
            ['table', 'height', 'Text'],
            ['td', 'border', 'Text'],
            ['th', 'border', 'Text'],
            ['tr', 'width', 'Text'],
            ['tr', 'height', 'Text'],
            ['tr', 'border', 'Text'],
        ],
    ],
    'custom_attributes' => [
        ['a', 'target', 'Enum#_blank,_self,_target,_top'],
    ],
    'custom_elements' => [
        ['u', 'Inline', 'Inline', 'Common'],
    ],
];