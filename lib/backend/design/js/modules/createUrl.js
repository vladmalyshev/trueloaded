export default function(url, getParams){

    let newUrl = new URL(url);
    for (let name in getParams) {
        newUrl.searchParams.set(name, getParams[name]);
    }

    return newUrl.href;
}