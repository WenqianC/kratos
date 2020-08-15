(function () {
    tinymce.create('tinymce.plugins.h2title', {
        init: function (ed, url) {
            ed.addButton('h2title', {
                title: '特色标题',
                image: url + '/images/title.png',
                onclick: function () {
                    ed.selection.setContent('[h2title]' + ed.selection.getContent() + '[/h2title]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('h2title', tinymce.plugins.h2title)

    tinymce.create('tinymce.plugins.mark', {
        init: function (ed, url) {
            ed.addButton('mark', {
                title: '内容标记',
                image: url + '/images/mark.png',
                onclick: function () {
                    ed.selection.setContent('[mark]' + ed.selection.getContent() + '[/mark]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('mark', tinymce.plugins.mark)

    tinymce.create('tinymce.plugins.bdbtn', {
        init: function (ed, url) {
            ed.addButton('bdbtn', {
                title: '下载按钮',
                image: url + '/images/bdbtn.png',
                onclick: function () {
                    ed.selection.setContent('[bdbtn]' + ed.selection.getContent() + '[/bdbtn]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('bdbtn', tinymce.plugins.bdbtn)

    tinymce.create('tinymce.plugins.accordion', {
        init: function (ed, url) {
            ed.addButton('accordion', {
                title: '展开收缩',
                image: url + '/images/accordion.png',
                onclick: function () {
                    ed.selection.setContent('[accordion title="标题内容"]' + ed.selection.getContent() + '[/accordion]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('accordion', tinymce.plugins.accordion)

    tinymce.create('tinymce.plugins.music', {
        init: function (ed, url) {
            ed.addButton('music', {
                title: '网易云音乐',
                image: url + '/images/music.png',
                onclick: function () {
                    ed.selection.setContent('[music]' + ed.selection.getContent() + '[/music]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('music', tinymce.plugins.music)

    tinymce.create('tinymce.plugins.bilibili', {
        init: function (ed, url) {
            ed.addButton('bilibili', {
                title: '哔哩哔哩',
                image: url + '/images/bilibili.png',
                onclick: function () {
                    ed.selection.setContent('[bilibili cid=""]' + ed.selection.getContent() + '[/bilibili]')
                }
            })
        },
        createControl: function (n, cm) {
            return null
        },
    })
    tinymce.PluginManager.add('bilibili', tinymce.plugins.bilibili)

})();