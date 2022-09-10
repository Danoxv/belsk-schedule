/*
 * Random generators
 */
function randJson(maxDepth = 5) {
    var choices = ["number", "string", "boolean", "array", "object"];
    if (maxDepth === 0) {
        choices = ["number", "string", "boolean"];
    }
    var choice = chooseOne(choices);

    function chooseOne(choices) {
        return choices[parseInt(Math.random() * choices.length)];
    }

    if (choice === "number") {
        return generateRandomNumber();
    }

    function generateRandomNumber() {
        var maxNum = 2 ** 32;
        var number = Math.random() * maxNum;
        var isInteger = chooseOne([true, false]);
        var isNegative = chooseOne([true, false]);
        if (isInteger) number = parseInt(number);
        if (isNegative) number = -number;
        return number;
    }

    if (choice === "string") {
        return generateRandomString();
    }

    function generateRandomString() {
        var alphabet = " !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
        var maxLength = 100;
        var length = Math.random() * maxLength;
        var string = "";
        for (var i = 0; i < length; i++) {
            string += chooseOne(alphabet);
        }
        return string;
    }

    if (choice === "boolean") {
        return generateRandomBoolean();
    }

    function generateRandomBoolean() {
        return chooseOne([true, false]);
    }

    if (choice === "array") {
        return generateRandomArray();
    }

    function generateRandomArray() {
        var maxArrayLength = 5;
        var length = parseInt(Math.random() * maxArrayLength);
        var array = [];
        for (var i = 0; i < length; i++) {
            array[i] = randJson();
        }
        return array;
    }

    if (choice === "object") {
        return generateRandomObject();
    }

    function generateRandomObject() {
        var maxObjectKeys = 5;
        var keyCount = parseInt(Math.random() * maxObjectKeys);
        var object = {};
        for (var i = 0; i < keyCount; i++) {
            var key = generateRandomKeyName();
            object[key] = randJson();
        }
        return object;
    }

    function generateRandomKeyName() {
        var maxKeyLength = 10;
        var keyLength = 1 + parseInt(Math.random() * maxKeyLength);
        var randomString = generateRandomString();
        return randomString.substr(0, keyLength)
    }
}

function randJsonStr(maxDepth = 5) {
    return JSON.stringify(randJson(maxDepth));
}

function randUserAgent() {
    let ua = window.navigator.userAgent;
    if (!ua) ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36';
    return ua.replace(/\d+/g, '' + randInt(1, 999));
}

function randInt(min, max) {
    if (!min) min = 0;
    if (!max) max = Number.MAX_SAFE_INTEGER || 800719925474099;
    return Math.floor(Math.random() * (max - min + 1) ) + min;
}