function utf8_decode(utftext)
{
    var string = "";
    var i = 0;
    var c = c1 = c2 = 0;

    while ( i < utftext.length )
    {
        c = utftext.charCodeAt(i);

        if (c < 128)
        {
            string += String.fromCharCode(c);
            i++;
        }
        else if((c > 191) && (c < 224))
        {
            c2 = utftext.charCodeAt(i+1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
        }
        else
        {
            c2 = utftext.charCodeAt(i+1);
            c3 = utftext.charCodeAt(i+2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return string;
}

b64s='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_"';

function base64ToText(t)
{
    var r=''; var m=0; var a=0; var c;

    for(n=0; n<t.length; n++)
    {
        c=b64s.indexOf(t.charAt(n));
        if(c >= 0)
        {
            if(m)
                r+=String.fromCharCode((c << (8-m))&255 | a)

            a = c >> m;
            m+=2;
            if(m==8)
                m=0;
        }
    }

    return r;
}

function downloadcountdown()
{
    if (count == 1)
    {
        divobj = document.getElementById("divCounter");
        divobj.style.display = 'none';
        dllink = document.getElementById("spndllink");
        dllink.innerHTML = 'Download: ' + link_dec;
    }
    else
    {
        cnt = document.getElementById("spnCountDown");
        count -= 1;
        cnt.innerHTML = count;
        setTimeout('downloadcountdown()', 1000);
    }
}
