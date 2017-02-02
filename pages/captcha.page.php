<header id="single-header">
        <div class="row">
                <div class="col-md-12 text-center">
                        <h1>
                                Soooo viele <strong>Captchas</strong><br><small>Hilf uns noch mehr Pokémon zu finden!</small>
                        </h1>

                </div>
        </div>
</header>

<div class="row" style="text-align:center">

        <h2>Was muss ich tun?</h2>

        <p>1. Lege ein Lesezeichen mit dem folgenden Link an:</p>

        <h3>--> <a id="bookmark-link" href="javascript:(function(){if(window.location.href!='http://pgorelease.nianticlabs.com/'){window.location.href='http://pgorelease.nianticlabs.com/'}else{document.getElementsByTagName('head')[0].appendChild(document.createElement('script')).src='<?= HOST_URL ?>map/inject.js?'+Math.random();}}());">Captcha lösen</a> <--</h3>

        <br>

        <p>Am schnellsten geht dies, in dem du einfach den Link in deine Lesezeichenleiste ziehst.</p>

        <p>Wenn das aus irgendwechen Gründen nicht geht, erstelle manuell ein Lesezeichen und füge folgende URL ein:</p>

        <p><code>javascript:(function(){if(window.location.href!='http://pgorelease.nianticlabs.com/'){window.location.href='http://pgorelease.nianticlabs.com/'}else{document.getElementsByTagName('head')[0].appendChild(document.createElement('script')).src='<?= HOST_URL ?>map/inject.js?'+Math.random();}}());</code></p>

        <p>Das Lesezeichen muss nur einmal angelegt werden.</p>


        <p>2. Rufe das Lesezeichen auf. Du siehst nun eine Fehlermeldung.</p>


        <p>3. Öffne das Lesezeichen erneut.</p>


        <p>4. Löse das angezeigte Captcha.</p>

        <br>

        <p>Sobald es erfolgreich gelöst wurde, wird ein weiteres angezeigt.</p>

        <br>

        <h3>Danke für deine Hilfe!</h3>

</div>

<script>
document.selectElementById('bookmark-link').addEventListener('click', function(e) {
    e.preventDefault();
    var bookmarkURL = e.target.href;
    var bookmarkTitle = e.target.innerHTML;
    if (window.sidebar && window.sidebar.addPanel) {
        // Firefox version < 23
        window.sidebar.addPanel(bookmarkTitle, bookmarkURL, '');
    } else if ((window.sidebar && /Firefox/i.test(navigator.userAgent)) || (window.opera && window.print)) {
        // Firefox version >= 23 and Opera Hotlist
        $(this).attr({
            href: bookmarkURL,
            title: bookmarkTitle,
            rel: 'sidebar'
        }).off(e);
        return true;
    } else if (window.external && ('AddFavorite' in window.external)) {
        // IE Favorite
        window.external.AddFavorite(bookmarkURL, bookmarkTitle);
    } else {
        // Other browsers (mainly WebKit - Chrome/Safari)
        alert('Bitte einfach den Knopf in deine Lesezeichenleiste ziehen. Alternativ musst du selbst ein Lesezeichen mit der rot dargestellten URL anlegen. Prüfe, ob das Lesezeichen dann auch wirklich mit javascript: beginnt.');
    }
    return false;
});
</script>