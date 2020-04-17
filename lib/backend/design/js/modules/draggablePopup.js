import style from "./draggablePopup.scss";
import displace from 'displacejs';

export default function(content, op){

    let options = $.extend({
        heading: '',
        className: '',
        top: 200,
        buttons: [],
        draggable: true,
        aroundArea: false,
        beforeRemove: function(){ return true; }
    },op);

    let body = $('body');

    let popupDraggable = $(`<div class="popup-draggable ${options.className}"></div>`);
    body.append(popupDraggable);

    let aroundArea = $(`<div class="around-pop-up"></div>`);
    if (options.aroundArea) {
        body.append(aroundArea);
    }

    let close = $('<div class="pop-up-close"></div>');
    popupDraggable.append(close);

    if (options.heading) {
        let headingWrap = $('<div class="popup-heading"></div>');
        headingWrap.append(options.heading);
        popupDraggable.append(headingWrap);
    }

    let contentWrap = $('<div class="popup-content pop-mess-cont"></div>');
    contentWrap.append(content);
    popupDraggable.append(contentWrap);


    if (options.buttons && options.buttons.length > 0) {
        let buttonsWrap = $('<div class="popup-buttons"></div>');

        options.buttons.forEach(function(item) {
            buttonsWrap.append(item);
        });

        popupDraggable.append(buttonsWrap)
    }

    popupDraggable.css({
        left: ($(window).width() - popupDraggable.width())/2,
        top: $(window).scrollTop() + options.top
    });

    close.on('click', closePopup);
    aroundArea.on('click', closePopup);

    function closePopup() {
        options.beforeRemove();
        popupDraggable.remove();
        aroundArea.remove()
    }

    let handle = {};
    if ($('.popup-heading', popupDraggable).length){
        handle = { handle: '.popup-heading' };
    }

    if (options.draggable) {
        //popupDraggable.draggable(handle);
        const d = displace(popupDraggable.get(0), {});
    }

    return popupDraggable;
}