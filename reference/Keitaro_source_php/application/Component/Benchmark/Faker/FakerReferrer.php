<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\Faker;

class FakerReferrer
{
    private static $_data = ["http://www.google.com/search?hl=en&ie=UTF-8&q=ko+in+slavic+language&start=10&sa=N", "http://www.google.com.tr/search?hl=tr&sa=X&oi=spell&resnum=0&ct=result&cd=1&q=viagra&spell=1", "http://www.google.com/search?hl=en&q=slavic+tattoo", "http://www.google.com/search?hl=en&q=cialis+online+canada", "http://www.google.pl/search?hl=pl&lr=&client=firefox-a&channel=s&rls=org.mozilla:pl:official&q=rossija+military&start=30&sa=N", "http://www.google.sk/search?hl=sk&sa=X&oi=spell&resnum=1&ct=result&cd=1&q=slavic+tattoo&spell=1", "http://www.google.ru/search?hl=de&q=samie+lut4e+film&btnG=Google-Suche&lr=", "http://www.google.com/search?hl=pl&rlz=1B3GGGL_plPL277PL278&sa=X&oi=spell&resnum=0&ct=result&cd=1&q=slavic+tattoo&spell=1", "http://www.google.ru/search?hl=ru&newwindow=1&client=firefox-a&rls=org.mozilla%3Aen-GB%3Aofficial&hs=Ej2&q=torrent+aleksandr.nevskii.2&btnG=%D0%9F%D0%BE%D0%B8%D1%81%D0%BA&lr=&aq=f&oq=", "http://www.google.com/search?sourceid=navclient&ie=UTF-8&rls=GGIT,GGIT:2007-02,GGIT:en&q=slovio+german+w%c3%b6rterbuch", "http://www.google.pl/search?hl=pl&lr=&client=firefox-a&rls=org.mozilla:en-US:official&hs=tYI&sa=X&oi=spell&resnum=0&ct=result&cd=1&q=slavic+tattoo&spell=1", "http://www.google.com.au/search?hl=en&q=tube+online&btnG=Google+Search&meta=", "http://www.google.lt/search?hl=lt&q=pisajut&btnG=Google+Paie%C5%A1ka&meta=lr%3D", "http://www.google.com/search?hl=en&q=tube+online&btnG=Search&lr=", "http://www.google.com/search?hl=en&client=firefox-a&channel=s&rls=org.mozilla:en-US:official&q=slavic+mtdna&start=40&sa=N", "http://www.google.pl/search?q=slovensko+%2B+nigerci&btnG=Szukaj&hl=pl&lr=&sa=2", "http://www.google.com.my/search?q=&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:official&client=firefox-a", "http://www.google.com/search?hl=en&q=slavicunity", "http://www.google.de/search?hl=de&q=filme+ruski+twilight&meta=", "http://search.live.com/results.aspx?q=downloads", "http://www.google.pl/search?hl=pl&q=slovianska&btnG=Szukaj&lr=lang_pl", "http://search.msn.com/results.aspx?q=slavic+unity&FORM=MSNH11", "http://www.google.hu/search?hl=hu&q=NBC+Cerenany&meta=", "http://search.live.com/results.aspx?q=slavianka", "http://www.google.ge/search?hl=ka&q=%D1%81%D0%B2%D0%BE%D0%BB%D0%BE%D1%87%D0%B8+film&start=60&sa=N", "http://www.google.com/search?hl=en&q=film+czterej+pancerni++you+tube+.com&btnG=Google+Search", "http://www.google.com.au/search?hl=en&safe=off&sa=X&oi=spell&resnum=1&ct=result&cd=1&q=viagra&spell=1", "http://www.google.cz/search?hl=uk&q=%D0%94%D1%80%D0%B0%D0%B1%D0%B8%D0%BD%D0%B0+%D0%B7+%D0%BF%D0%BE%D0%B2%D0%B8%D0%BB%D0%B0%D0%BC%D1%83%D0%B2%D0%B0%D0%BD%D0%B8%D0%BC%D0%B8+%D1%89%D0%B0%D0%B1%D0%BB%D1%8F%D0%BC%D0%B8+&lr=", "http://www.google.pl/search?hl=pl&client=firefox-a&rls=org.mozilla%3Apl%3Aofficial&hs=m7V&q=neoslavism&btnG=Szukaj&lr=", "http://www.google.ca/search?hl=en&sa=X&oi=spell&resnum=0&ct=result&cd=1&q=Gromoviti+znaci+tattoo&spell=1&safe=active", "http://www.google.com/search?hl=pl&client=opera&rls=pl&hs=DDn&q=Tongue+twisters+%C5%82ama%C5%84ce+j%C4%99zykowe+po+ukrai%C5%84sku&btnG=Szukaj&lr=", "http://www.google.com/search?q=slavic%2C+unity%2C+site&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:official&client=firefox-a", "http://search.live.com/results.aspx?q=tehnique", "http://www.google.ru/search?sourceid=navclient&hl=ru&ie=UTF-8&rlz=1T4SKPB_ruRU298RU298&q=%d0%a1%d0%b8%d0%bc%d1%80%d0%b3%d0%bb"];
    public static function get()
    {
        return self::$_data[rand(0, count(self::$_data) - 1)] . "&rand=" . rand(0, 100000);
    }
}

?>