tl.reducers.products = function(state, actions){
    if (!state) state = entryData.products;
    if (!state) state = [];

    var newState ='';

    switch (actions.type) {
        case 'CHANGE_PRODUCT':
        case 'ADD_PRODUCT':
            newState = JSON.parse(JSON.stringify(state));
            newState[actions.value.id] = actions.value.product;
            return newState;
        case 'ADD_PRODUCTS':
            newState = JSON.parse(JSON.stringify(state));
            for (var id in actions.value.products) {
                newState[id] = actions.value.products[id];
            }
            return newState;
        default:
            return state
    }
}