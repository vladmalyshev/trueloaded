/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

import draggablePopup from "src/draggablePopup";

export default {
    name: 'tool_size',
    async init ({$, NS}) {
        const svgEditor = this;
        const svgCanvas = svgEditor.canvas;

        return {
            name: 'Canvas size',
            svgicons: svgEditor.curConfig.extIconsPath + 'ext-size.xml',
            buttons: [{
                id: 'tool_size',
                icon: svgEditor.curConfig.extIconsPath + 'helloworld.png',
                type: 'context',
                title: 'Canvas size',
                panel: 'editor_panel',
                events: {
                    click () {
                        svgCanvas.setMode('select');
                        openPopup(svgEditor, NS);
                    }
                }
            }],
            mouseDown () {
            },
            mouseUp (opts) {
            }
        };
    }
};

function openPopup(svgEditor, NS){

    let content = {};
    content.NS = NS;
    content.svgEditor = svgEditor;
    let canvas = svgEditor.canvas;

    content.main = $(`<div class="canvas-size"></div>`);
    content.heading = $(`<div>Canvas size</div>`);

    content.width = $('<input type="number">');
    content.height = $('<input type="number">');

    content.main.append(`<span>${window.tr.TEXT_WIDTH}</span>`);
    content.main.append(content.width);
    content.main.append(`<span>${window.tr.TEXT_HEIGHT}</span>`);
    content.main.append(content.height);

    let btnCancel = $(`<span class="btn btn-cancel">${window.tr.IMAGE_CANCEL}</span>`);
    let btnSave = $(`<span class="btn btn-save">${window.tr.IMAGE_SAVE}</span>`);

    content.popup = draggablePopup(content.main, {
        heading: content.heading,
        buttons: [btnSave, btnCancel],
        className: 'default-popup',
    });
    btnCancel.on('click', function(){
        content.popup.remove();
    });
    btnSave.on('click', function(){
        canvas.setResolution(content.width.val(), content.height.val());
        svgEditor.updateCanvas(true);
        content.popup.remove();
    });

    let size = canvas.getResolution();
    content.width.val(size.w);
    content.height.val(size.h);

}