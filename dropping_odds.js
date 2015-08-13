var page = require('webpage').create();
page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.104 Safari/537.36';
var url = 'http://www.oddsportal.com/dropping-odds/3/5/0/#1/5/1.2/soccer';
page.open(url, function (status) {
    window.setTimeout(function() {

        var output = page.evaluate(function() {
            document.getElementByID('period').value = "1";
            document.getElementByID('dropping-odds-bs').value = "5";
            document.getElementByID('bet-type').value = "1.2";
            page.showOdds();
        });

        console.log(page.content);
        phantom.exit();
    }, 1000);
});