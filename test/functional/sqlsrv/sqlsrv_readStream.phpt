--TEST--
Read varchar(max) fields from a stream
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $stmt = sqlsrv_prepare($conn, "SELECT review1 FROM cd_info");

    sqlsrv_execute($stmt);

    while (sqlsrv_fetch($stmt)) {
        $strlen = 0;
        $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
        while (!feof($stream)) {
            $str = fread($stream, 80);
            $strlen += strlen($str);
            echo "$str\n";
        }
        echo "length = $strlen\n\n";
    }

    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);
?>
--EXPECT--
Source: Amazon.com - As it turned out, Led Zeppelins infamous 1969 debut album w
as indicative of the decade to come--one that, fittingly, this band helped defin
e with its decadently exaggerated, bowdlerized blues-rock. In shrieker Robert Pl
ant, ex-Yardbird Jimmy Page found a vocalist who could match his guitar pyrotech
nics, and the band pounded out its music with swaggering ferocity and Richter-sc
ale-worthy volume. Pumping up blues classics such as Otis Rushs I Cant Quit You 
Baby and Howlin Wolfs How Many More Times into near-cartoon parodies, the band a
lso hinted at things to come with the manic Communication Breakdown and the lumb
ering set stopper Dazed and Confused. <I>--Billy Altman</I>
length = 699

Source: Amazon.com essential recording - Most critics complain <I>Back in Black<
/I>, the album AC/DC recorded after the death of their original lead screamer Bo
n Scott, is ridiculously juvenile, obvious, snickering, bludgeoning, derivative,
 single-minded about sex and booze, a big cartoon. All true, of course, and--on 
rock n ragers like What Do You Do For Money Honey, You Shook Me All Night Long, 
and the title track--all great. As Scotts replacement Brian Johnson reminds us, 
loud and crunchy, no-holds-barred rock and roll aint noise pollution...it makes 
good, good sense. Never trust anyone who refuses to drink domestic beer, laugh a
t the Three Stooges, or crank <I>Back in Black</I>. <i>--David Cantwell</i>
length = 715

Source: Amazon.com - At the time of its release, <I>One Hot Minute</I> was viewe
d as the beginning of a new direction for the Red Hot Chili Peppers. Guitarist J
ohn Frusciante had departed and former Janes Addiction guitarist Dave Navarro jo
ined the ranks after some false starts with short-lived replacements. Band chemi
stry here isnt quite up to past standards. Navarro stretches out throughout the 
album, imbuing tunes with a heavy dose of hard rock and psychedelia and providin
g a stark contrast from Frusciantes dexterous noodling. Tracks such as Warped an
d Aeroplane display a band prone to exploring a less frenetic hard rock, while S
hallow Be Thy Game sounds like the old band. Frusciante eventually returned to t
he fold, so this 1995 collection now stands as a curious intermission for the Pe
ppers. <I>--Rob OConnor</I>
length = 827


length = 0


length = 0

Source: Amazon.com essential recording - The Chili Peppers finally hit their str
ide with <I>Mothers Milk</I>, for the first time making their breakneck mix of f
unk, rap, and metal smooth enough to attract the masses, while keeping it raw en
ough not to alienate old fans. Theyve straddled that edge ever since.  It didnt 
hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Ground
 to introduce the album. That single though, and the rest of <I>Mothers Milk</I>
 (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from 
Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was al
so guitarist John Frusciantes debut with the group and he shines, especially on 
Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording 
- The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the f
irst time making their breakneck mix of funk, rap, and metal smooth enough to at
tract the masses, while keeping it raw enough not to alienate old fans. Theyve s
traddled that edge ever since.  It didnt hurt that they offered a pretty mainstr
eam cover of Stevie Wonders Higher Ground to introduce the album. That single th
ough, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy
 Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to 
Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut wit
h the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</
I>Source: Amazon.com essential recording - The Chili Peppers finally hit their s
tride with <I>Mothers Milk</I>, for the first time making their breakneck mix of
 funk, rap, and metal smooth enough to attract the masses, while keeping it raw 
enough not to alienate old fans. Theyve straddled that edge ever since.  It didn
t hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Grou
nd to introduce the album. That single though, and the rest of <I>Mothers Milk</
I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--fro
m Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was 
also guitarist John Frusciantes debut with the group and he shines, especially o
n Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recordin
g - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the
 first time making their breakneck mix of funk, rap, and metal smooth enough to 
attract the masses, while keeping it raw enough not to alienate old fans. Theyve
 straddled that edge ever since.  It didnt hurt that they offered a pretty mains
tream cover of Stevie Wonders Higher Ground to introduce the album. That single 
though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the ran
dy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals t
o Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut w
ith the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby
</I>Source: Amazon.com essential recording - The Chili Peppers finally hit their
 stride with <I>Mothers Milk</I>, for the first time making their breakneck mix 
of funk, rap, and metal smooth enough to attract the masses, while keeping it ra
w enough not to alienate old fans. Theyve straddled that edge ever since.  It di
dnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Gr
ound to introduce the album. That single though, and the rest of <I>Mothers Milk
</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--f
rom Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> wa
s also guitarist John Frusciantes debut with the group and he shines, especially
 on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential record
ing - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for t
he first time making their breakneck mix of funk, rap, and metal smooth enough t
o attract the masses, while keeping it raw enough not to alienate old fans. They
ve straddled that edge ever since.  It didnt hurt that they offered a pretty mai
nstream cover of Stevie Wonders Higher Ground to introduce the album. That singl
e though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the r
andy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals
 to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut
 with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ru
by</I>Source: Amazon.com essential recording - The Chili Peppers finally hit the
ir stride with <I>Mothers Milk</I>, for the first time making their breakneck mi
x of funk, rap, and metal smooth enough to attract the masses, while keeping it 
raw enough not to alienate old fans. Theyve straddled that edge ever since.  It 
didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher 
Ground to introduce the album. That single though, and the rest of <I>Mothers Mi
lk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper-
-from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> 
was also guitarist John Frusciantes debut with the group and he shines, especial
ly on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential reco
rding - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for
 the first time making their breakneck mix of funk, rap, and metal smooth enough
 to attract the masses, while keeping it raw enough not to alienate old fans. Th
eyve straddled that edge ever since.  It didnt hurt that they offered a pretty m
ainstream cover of Stevie Wonders Higher Ground to introduce the album. That sin
gle though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the
 randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face voca
ls to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes deb
ut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael 
Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit t
heir stride with <I>Mothers Milk</I>, for the first time making their breakneck 
mix of funk, rap, and metal smooth enough to attract the masses, while keeping i
t raw enough not to alienate old fans. Theyve straddled that edge ever since.  I
t didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Highe
r Ground to introduce the album. That single though, and the rest of <I>Mothers 
Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Peppe
r--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I
> was also guitarist John Frusciantes debut with the group and he shines, especi
ally on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential re
cording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, f
or the first time making their breakneck mix of funk, rap, and metal smooth enou
gh to attract the masses, while keeping it raw enough not to alienate old fans. 
Theyve straddled that edge ever since.  It didnt hurt that they offered a pretty
 mainstream cover of Stevie Wonders Higher Ground to introduce the album. That s
ingle though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and t
he randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vo
cals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes d
ebut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michae
l Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit
 their stride with <I>Mothers Milk</I>, for the first time making their breaknec
k mix of funk, rap, and metal smooth enough to attract the masses, while keeping
 it raw enough not to alienate old fans. Theyve straddled that edge ever since. 
 It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Hig
her Ground to introduce the album. That single though, and the rest of <I>Mother
s Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pep
per--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk<
/I> was also guitarist John Frusciantes debut with the group and he shines, espe
cially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential 
recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>,
 for the first time making their breakneck mix of funk, rap, and metal smooth en
ough to attract the masses, while keeping it raw enough not to alienate old fans
. Theyve straddled that edge ever since.  It didnt hurt that they offered a pret
ty mainstream cover of Stevie Wonders Higher Ground to introduce the album. That
 single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and
 the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face 
vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes
 debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Mich
ael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally h
it their stride with <I>Mothers Milk</I>, for the first time making their breakn
eck mix of funk, rap, and metal smooth enough to attract the masses, while keepi
ng it raw enough not to alienate old fans. Theyve straddled that edge ever since
.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders H
igher Ground to introduce the album. That single though, and the rest of <I>Moth
ers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure P
epper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Mil
k</I> was also guitarist John Frusciantes debut with the group and he shines, es
pecially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essentia
l recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I
>, for the first time making their breakneck mix of funk, rap, and metal smooth 
enough to attract the masses, while keeping it raw enough not to alienate old fa
ns. Theyve straddled that edge ever since.  It didnt hurt that they offered a pr
etty mainstream cover of Stevie Wonders Higher Ground to introduce the album. Th
at single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down a
nd the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-fac
e vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciant
es debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Mi
chael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally
 hit their stride with <I>Mothers Milk</I>, for the first time making their brea
kneck mix of funk, rap, and metal smooth enough to attract the masses, while kee
ping it raw enough not to alienate old fans. Theyve straddled that edge ever sin
ce.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders
 Higher Ground to introduce the album. That single though, and the rest of <I>Mo
thers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure
 Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>M
ilk</I> was also guitarist John Frusciantes debut with the group and he shines, 
especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essent
ial recording - The Chili Peppers finally hit their stride with <I>Mothers Milk<
/I>, for the first time making their breakneck mix of funk, rap, and metal smoot
h enough to attract the masses, while keeping it raw enough not to alienate old 
fans. Theyve straddled that edge ever since.  It didnt hurt that they offered a 
pretty mainstream cover of Stevie Wonders Higher Ground to introduce the album. 
That single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down
 and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-f
ace vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Fruscia
ntes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--
Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers final
ly hit their stride with <I>Mothers Milk</I>, for the first time making their br
eakneck mix of funk, rap, and metal smooth enough to attract the masses, while k
eeping it raw enough not to alienate old fans. Theyve straddled that edge ever s
ince.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonde
rs Higher Ground to introduce the album. That single though, and the rest of <I>
Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pu
re Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I
>Milk</I> was also guitarist John Frusciantes debut with the group and he shines
, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com esse
ntial recording - The Chili Peppers finally hit their stride with <I>Mothers Mil
k</I>, for the first time making their breakneck mix of funk, rap, and metal smo
oth enough to attract the masses, while keeping it raw enough not to alienate ol
d fans. Theyve straddled that edge ever since.  It didnt hurt that they offered 
a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the album
. That single though, and the rest of <I>Mothers Milk</I> (including Knock Me Do
wn and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your
-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusc
iantes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>
--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers fin
ally hit their stride with <I>Mothers Milk</I>, for the first time making their 
breakneck mix of funk, rap, and metal smooth enough to attract the masses, while
 keeping it raw enough not to alienate old fans. Theyve straddled that edge ever
 since.  It didnt hurt that they offered a pretty mainstream cover of Stevie Won
ders Higher Ground to introduce the album. That single though, and the rest of <
I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is 
pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. 
<I>Milk</I> was also guitarist John Frusciantes debut with the group and he shin
es, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com es
sential recording - The Chili Peppers finally hit their stride with <I>Mothers M
ilk</I>, for the first time making their breakneck mix of funk, rap, and metal s
mooth enough to attract the masses, while keeping it raw enough not to alienate 
old fans. Theyve straddled that edge ever since.  It didnt hurt that they offere
d a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the alb
um. That single though, and the rest of <I>Mothers Milk</I> (including Knock Me 
Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-yo
ur-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Fru
sciantes debut with the group and he shines, especially on Jimi Hendrixs Fire. <
I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers f
inally hit their stride with <I>Mothers Milk</I>, for the first time making thei
r breakneck mix of funk, rap, and metal smooth enough to attract the masses, whi
le keeping it raw enough not to alienate old fans. Theyve straddled that edge ev
er since.  It didnt hurt that they offered a pretty mainstream cover of Stevie W
onders Higher Ground to introduce the album. That single though, and the rest of
 <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) i
s pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass
. <I>Milk</I> was also guitarist John Frusciantes debut with the group and he sh
ines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com 
essential recording - The Chili Peppers finally hit their stride with <I>Mothers
 Milk</I>, for the first time making their breakneck mix of funk, rap, and metal
 smooth enough to attract the masses, while keeping it raw enough not to alienat
e old fans. Theyve straddled that edge ever since.  It didnt hurt that they offe
red a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the a
lbum. That single though, and the rest of <I>Mothers Milk</I> (including Knock M
e Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-
your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John F
rusciantes debut with the group and he shines, especially on Jimi Hendrixs Fire.
 <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers
 finally hit their stride with <I>Mothers Milk</I>, for the first time making th
eir breakneck mix of funk, rap, and metal smooth enough to attract the masses, w
hile keeping it raw enough not to alienate old fans. Theyve straddled that edge 
ever since.  It didnt hurt that they offered a pretty mainstream cover of Stevie
 Wonders Higher Ground to introduce the album. That single though, and the rest 
of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid)
 is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering ba
ss. <I>Milk</I> was also guitarist John Frusciantes debut with the group and he 
shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.co
m essential recording - The Chili Peppers finally hit their stride with <I>Mothe
rs Milk</I>, for the first time making their breakneck mix of funk, rap, and met
al smooth enough to attract the masses, while keeping it raw enough not to alien
ate old fans. Theyve straddled that edge ever since.  It didnt hurt that they of
fered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the
 album. That single though, and the rest of <I>Mothers Milk</I> (including Knock
 Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss i
n-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John
 Frusciantes debut with the group and he shines, especially on Jimi Hendrixs Fir
e. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppe
rs finally hit their stride with <I>Mothers Milk</I>, for the first time making 
their breakneck mix of funk, rap, and metal smooth enough to attract the masses,
 while keeping it raw enough not to alienate old fans. Theyve straddled that edg
e ever since.  It didnt hurt that they offered a pretty mainstream cover of Stev
ie Wonders Higher Ground to introduce the album. That single though, and the res
t of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Mai
d) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering 
bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group and h
e shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.
com essential recording - The Chili Peppers finally hit their stride with <I>Mot
hers Milk</I>, for the first time making their breakneck mix of funk, rap, and m
etal smooth enough to attract the masses, while keeping it raw enough not to ali
enate old fans. Theyve straddled that edge ever since.  It didnt hurt that they 
offered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce t
he album. That single though, and the rest of <I>Mothers Milk</I> (including Kno
ck Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss
 in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist Jo
hn Frusciantes debut with the group and he shines, especially on Jimi Hendrixs F
ire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Pep
pers finally hit their stride with <I>Mothers Milk</I>, for the first time makin
g their breakneck mix of funk, rap, and metal smooth enough to attract the masse
s, while keeping it raw enough not to alienate old fans. Theyve straddled that e
dge ever since.  It didnt hurt that they offered a pretty mainstream cover of St
evie Wonders Higher Ground to introduce the album. That single though, and the r
est of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican M
aid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chatterin
g bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group and
 he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazo
n.com essential recording - The Chili Peppers finally hit their stride with <I>M
others Milk</I>, for the first time making their breakneck mix of funk, rap, and
 metal smooth enough to attract the masses, while keeping it raw enough not to a
lienate old fans. Theyve straddled that edge ever since.  It didnt hurt that the
y offered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce
 the album. That single though, and the rest of <I>Mothers Milk</I> (including K
nock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiedi
ss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist 
John Frusciantes debut with the group and he shines, especially on Jimi Hendrixs
 Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili P
eppers finally hit their stride with <I>Mothers Milk</I>, for the first time mak
ing their breakneck mix of funk, rap, and metal smooth enough to attract the mas
ses, while keeping it raw enough not to alienate old fans. Theyve straddled that
 edge ever since.  It didnt hurt that they offered a pretty mainstream cover of 
Stevie Wonders Higher Ground to introduce the album. That single though, and the
 rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican
 Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chatter
ing bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group a
nd he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Ama
zon.com essential recording - The Chili Peppers finally hit their stride with <I
>Mothers Milk</I>, for the first time making their breakneck mix of funk, rap, a
nd metal smooth enough to attract the masses, while keeping it raw enough not to
 alienate old fans. Theyve straddled that edge ever since.  It didnt hurt that t
hey offered a pretty mainstream cover of Stevie Wonders Higher Ground to introdu
ce the album. That single though, and the rest of <I>Mothers Milk</I> (including
 Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kie
diss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitaris
t John Frusciantes debut with the group and he shines, especially on Jimi Hendri
xs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili
 Peppers finally hit their stride with <I>Mothers Milk</I>, for the first time m
aking their breakneck mix of funk, rap, and metal smooth enough to attract the m
asses, while keeping it raw enough not to alienate old fans. Theyve straddled th
at edge ever since.  It didnt hurt that they offered a pretty mainstream cover o
f Stevie Wonders Higher Ground to introduce the album. That single though, and t
he rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexic
an Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chatt
ering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group
 and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: A
mazon.com essential recording - The Chili Peppers finally hit their stride with 
<I>Mothers Milk</I>, for the first time making their breakneck mix of funk, rap,
 and metal smooth enough to attract the masses, while keeping it raw enough not 
to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt that
 they offered a pretty mainstream cover of Stevie Wonders Higher Ground to intro
duce the album. That single though, and the rest of <I>Mothers Milk</I> (includi
ng Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony K
iediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitar
ist John Frusciantes debut with the group and he shines, especially on Jimi Hend
rixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chi
li Peppers finally hit their stride with <I>Mothers Milk</I>, for the first time
 making their breakneck mix of funk, rap, and metal smooth enough to attract the
 masses, while keeping it raw enough not to alienate old fans. Theyve straddled 
that edge ever since.  It didnt hurt that they offered a pretty mainstream cover
 of Stevie Wonders Higher Ground to introduce the album. That single though, and
 the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mex
ican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas cha
ttering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the gro
up and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source:
 Amazon.com essential recording - The Chili Peppers finally hit their stride wit
h <I>Mothers Milk</I>, for the first time making their breakneck mix of funk, ra
p, and metal smooth enough to attract the masses, while keeping it raw enough no
t to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt th
at they offered a pretty mainstream cover of Stevie Wonders Higher Ground to int
roduce the album. That single though, and the rest of <I>Mothers Milk</I> (inclu
ding Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony
 Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guit
arist John Frusciantes debut with the group and he shines, especially on Jimi He
ndrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The C
hili Peppers finally hit their stride with <I>Mothers Milk</I>, for the first ti
me making their breakneck mix of funk, rap, and metal smooth enough to attract t
he masses, while keeping it raw enough not to alienate old fans. Theyve straddle
d that edge ever since.  It didnt hurt that they offered a pretty mainstream cov
er of Stevie Wonders Higher Ground to introduce the album. That single though, a
nd the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy M
exican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas c
hattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the g
roup and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Sourc
e: Amazon.com essential recording - The Chili Peppers finally hit their stride w
ith <I>Mothers Milk</I>, for the first time making their breakneck mix of funk, 
rap, and metal smooth enough to attract the masses, while keeping it raw enough 
not to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt 
that they offered a pretty mainstream cover of Stevie Wonders Higher Ground to i
ntroduce the album. That single though, and the rest of <I>Mothers Milk</I> (inc
luding Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Antho
ny Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also gu
itarist John Frusciantes debut with the group and he shines, especially on Jimi 
Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The
 Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the first 
time making their breakneck mix of funk, rap, and metal smooth enough to attract
 the masses, while keeping it raw enough not to alienate old fans. Theyve stradd
led that edge ever since.  It didnt hurt that they offered a pretty mainstream c
over of Stevie Wonders Higher Ground to introduce the album. That single though,
 and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy
 Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas
 chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the
 group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Sou
rce: Amazon.com essential recording - The Chili Peppers finally hit their stride
 with <I>Mothers Milk</I>, for the first time making their breakneck mix of funk
, rap, and metal smooth enough to attract the masses, while keeping it raw enoug
h not to alienate old fans. Theyve straddled that edge ever since.  It didnt hur
t that they offered a pretty mainstream cover of Stevie Wonders Higher Ground to
 introduce the album. That single though, and the rest of <I>Mothers Milk</I> (i
ncluding Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Ant
hony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also 
guitarist John Frusciantes debut with the group and he shines, especially on Jim
i Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - T
he Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the firs
t time making their breakneck mix of funk, rap, and metal smooth enough to attra
ct the masses, while keeping it raw enough not to alienate old fans. Theyve stra
ddled that edge ever since.  It didnt hurt that they offered a pretty mainstream
 cover of Stevie Wonders Higher Ground to introduce the album. That single thoug
h, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Se
xy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fle
as chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with t
he group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>S
ource: Amazon.com essential recording - The Chili Peppers finally hit their stri
de with <I>Mothers Milk</I>, for the first time making their breakneck mix of fu
nk, rap, and metal smooth enough to attract the masses, while keeping it raw eno
ugh not to alienate old fans. Theyve straddled that edge ever since.  It didnt h
urt that they offered a pretty mainstream cover of Stevie Wonders Higher Ground 
to introduce the album. That single though, and the rest of <I>Mothers Milk</I> 
(including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from A
nthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was als
o guitarist John Frusciantes debut with the group and he shines, especially on J
imi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording -
 The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the fi
rst time making their breakneck mix of funk, rap, and metal smooth enough to att
ract the masses, while keeping it raw enough not to alienate old fans. Theyve st
raddled that edge ever since.  It didnt hurt that they offered a pretty mainstre
am cover of Stevie Wonders Higher Ground to introduce the album. That single tho
ugh, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy 
Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to F
leas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with
 the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I
>Source: Amazon.com essential recording - The Chili Peppers finally hit their st
ride with <I>Mothers Milk</I>, for the first time making their breakneck mix of 
funk, rap, and metal smooth enough to attract the masses, while keeping it raw e
nough not to alienate old fans. Theyve straddled that edge ever since.  It didnt
 hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Groun
d to introduce the album. That single though, and the rest of <I>Mothers Milk</I
> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from
 Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was a
lso guitarist John Frusciantes debut with the group and he shines, especially on
 Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording
 - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the 
first time making their breakneck mix of funk, rap, and metal smooth enough to a
ttract the masses, while keeping it raw enough not to alienate old fans. Theyve 
straddled that edge ever since.  It didnt hurt that they offered a pretty mainst
ream cover of Stevie Wonders Higher Ground to introduce the album. That single t
hough, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the rand
y Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to
 Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut wi
th the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby<
/I>Source: Amazon.com essential recording - The Chili Peppers finally hit their 
stride with <I>Mothers Milk</I>, for the first time making their breakneck mix o
f funk, rap, and metal smooth enough to attract the masses, while keeping it raw
 enough not to alienate old fans. Theyve straddled that edge ever since.  It did
nt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Gro
und to introduce the album. That single though, and the rest of <I>Mothers Milk<
/I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--fr
om Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was
 also guitarist John Frusciantes debut with the group and he shines, especially 
on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recordi
ng - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for th
e first time making their breakneck mix of funk, rap, and metal smooth enough to
 attract the masses, while keeping it raw enough not to alienate old fans. Theyv
e straddled that edge ever since.  It didnt hurt that they offered a pretty main
stream cover of Stevie Wonders Higher Ground to introduce the album. That single
 though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the ra
ndy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals 
to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut 
with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Rub
y</I>Source: Amazon.com essential recording - The Chili Peppers finally hit thei
r stride with <I>Mothers Milk</I>, for the first time making their breakneck mix
 of funk, rap, and metal smooth enough to attract the masses, while keeping it r
aw enough not to alienate old fans. Theyve straddled that edge ever since.  It d
idnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher G
round to introduce the album. That single though, and the rest of <I>Mothers Mil
k</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--
from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> w
as also guitarist John Frusciantes debut with the group and he shines, especiall
y on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recor
ding - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for 
the first time making their breakneck mix of funk, rap, and metal smooth enough 
to attract the masses, while keeping it raw enough not to alienate old fans. The
yve straddled that edge ever since.  It didnt hurt that they offered a pretty ma
instream cover of Stevie Wonders Higher Ground to introduce the album. That sing
le though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the 
randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocal
s to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debu
t with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael R
uby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit th
eir stride with <I>Mothers Milk</I>, for the first time making their breakneck m
ix of funk, rap, and metal smooth enough to attract the masses, while keeping it
 raw enough not to alienate old fans. Theyve straddled that edge ever since.  It
 didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher
 Ground to introduce the album. That single though, and the rest of <I>Mothers M
ilk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper
--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I>
 was also guitarist John Frusciantes debut with the group and he shines, especia
lly on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential rec
ording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, fo
r the first time making their breakneck mix of funk, rap, and metal smooth enoug
h to attract the masses, while keeping it raw enough not to alienate old fans. T
heyve straddled that edge ever since.  It didnt hurt that they offered a pretty 
mainstream cover of Stevie Wonders Higher Ground to introduce the album. That si
ngle though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and th
e randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face voc
als to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes de
but with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael
 Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit 
their stride with <I>Mothers Milk</I>, for the first time making their breakneck
 mix of funk, rap, and metal smooth enough to attract the masses, while keeping 
it raw enough not to alienate old fans. Theyve straddled that edge ever since.  
It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders High
er Ground to introduce the album. That single though, and the rest of <I>Mothers
 Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepp
er--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</
I> was also guitarist John Frusciantes debut with the group and he shines, espec
ially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential r
ecording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, 
for the first time making their breakneck mix of funk, rap, and metal smooth eno
ugh to attract the masses, while keeping it raw enough not to alienate old fans.
 Theyve straddled that edge ever since.  It didnt hurt that they offered a prett
y mainstream cover of Stevie Wonders Higher Ground to introduce the album. That 
single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and 
the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face v
ocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes 
debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Micha
el Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hi
t their stride with <I>Mothers Milk</I>, for the first time making their breakne
ck mix of funk, rap, and metal smooth enough to attract the masses, while keepin
g it raw enough not to alienate old fans. Theyve straddled that edge ever since.
  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Hi
gher Ground to introduce the album. That single though, and the rest of <I>Mothe
rs Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pe
pper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk
</I> was also guitarist John Frusciantes debut with the group and he shines, esp
ecially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential
 recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>
, for the first time making their breakneck mix of funk, rap, and metal smooth e
nough to attract the masses, while keeping it raw enough not to alienate old fan
s. Theyve straddled that edge ever since.  It didnt hurt that they offered a pre
tty mainstream cover of Stevie Wonders Higher Ground to introduce the album. Tha
t single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down an
d the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face
 vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciante
s debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Mic
hael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally 
hit their stride with <I>Mothers Milk</I>, for the first time making their break
neck mix of funk, rap, and metal smooth enough to attract the masses, while keep
ing it raw enough not to alienate old fans. Theyve straddled that edge ever sinc
e.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders 
Higher Ground to introduce the album. That single though, and the rest of <I>Mot
hers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure 
Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Mi
lk</I> was also guitarist John Frusciantes debut with the group and he shines, e
specially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essenti
al recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</
I>, for the first time making their breakneck mix of funk, rap, and metal smooth
 enough to attract the masses, while keeping it raw enough not to alienate old f
ans. Theyve straddled that edge ever since.  It didnt hurt that they offered a p
retty mainstream cover of Stevie Wonders Higher Ground to introduce the album. T
hat single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down 
and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-fa
ce vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Fruscian
tes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--M
ichael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finall
y hit their stride with <I>Mothers Milk</I>, for the first time making their bre
akneck mix of funk, rap, and metal smooth enough to attract the masses, while ke
eping it raw enough not to alienate old fans. Theyve straddled that edge ever si
nce.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonder
s Higher Ground to introduce the album. That single though, and the rest of <I>M
others Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pur
e Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>
Milk</I> was also guitarist John Frusciantes debut with the group and he shines,
 especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essen
tial recording - The Chili Peppers finally hit their stride with <I>Mothers Milk
</I>, for the first time making their breakneck mix of funk, rap, and metal smoo
th enough to attract the masses, while keeping it raw enough not to alienate old
 fans. Theyve straddled that edge ever since.  It didnt hurt that they offered a
 pretty mainstream cover of Stevie Wonders Higher Ground to introduce the album.
 That single though, and the rest of <I>Mothers Milk</I> (including Knock Me Dow
n and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-
face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusci
antes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>-
-Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers fina
lly hit their stride with <I>Mothers Milk</I>, for the first time making their b
reakneck mix of funk, rap, and metal smooth enough to attract the masses, while 
keeping it raw enough not to alienate old fans. Theyve straddled that edge ever 
since.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wond
ers Higher Ground to introduce the album. That single though, and the rest of <I
>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is p
ure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <
I>Milk</I> was also guitarist John Frusciantes debut with the group and he shine
s, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com ess
ential recording - The Chili Peppers finally hit their stride with <I>Mothers Mi
lk</I>, for the first time making their breakneck mix of funk, rap, and metal sm
ooth enough to attract the masses, while keeping it raw enough not to alienate o
ld fans. Theyve straddled that edge ever since.  It didnt hurt that they offered
 a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the albu
m. That single though, and the rest of <I>Mothers Milk</I> (including Knock Me D
own and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-you
r-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frus
ciantes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I
>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers fi
nally hit their stride with <I>Mothers Milk</I>, for the first time making their
 breakneck mix of funk, rap, and metal smooth enough to attract the masses, whil
e keeping it raw enough not to alienate old fans. Theyve straddled that edge eve
r since.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wo
nders Higher Ground to introduce the album. That single though, and the rest of 
<I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is
 pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass.
 <I>Milk</I> was also guitarist John Frusciantes debut with the group and he shi
nes, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com e
ssential recording - The Chili Peppers finally hit their stride with <I>Mothers 
Milk</I>, for the first time making their breakneck mix of funk, rap, and metal 
smooth enough to attract the masses, while keeping it raw enough not to alienate
 old fans. Theyve straddled that edge ever since.  It didnt hurt that they offer
ed a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the al
bum. That single though, and the rest of <I>Mothers Milk</I> (including Knock Me
 Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-y
our-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Fr
usciantes debut with the group and he shines, especially on Jimi Hendrixs Fire. 
<I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers 
finally hit their stride with <I>Mothers Milk</I>, for the first time making the
ir breakneck mix of funk, rap, and metal smooth enough to attract the masses, wh
ile keeping it raw enough not to alienate old fans. Theyve straddled that edge e
ver since.  It didnt hurt that they offered a pretty mainstream cover of Stevie 
Wonders Higher Ground to introduce the album. That single though, and the rest o
f <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) 
is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bas
s. <I>Milk</I> was also guitarist John Frusciantes debut with the group and he s
hines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com
 essential recording - The Chili Peppers finally hit their stride with <I>Mother
s Milk</I>, for the first time making their breakneck mix of funk, rap, and meta
l smooth enough to attract the masses, while keeping it raw enough not to aliena
te old fans. Theyve straddled that edge ever since.  It didnt hurt that they off
ered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce the 
album. That single though, and the rest of <I>Mothers Milk</I> (including Knock 
Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in
-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John 
Frusciantes debut with the group and he shines, especially on Jimi Hendrixs Fire
. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Pepper
s finally hit their stride with <I>Mothers Milk</I>, for the first time making t
heir breakneck mix of funk, rap, and metal smooth enough to attract the masses, 
while keeping it raw enough not to alienate old fans. Theyve straddled that edge
 ever since.  It didnt hurt that they offered a pretty mainstream cover of Stevi
e Wonders Higher Ground to introduce the album. That single though, and the rest
 of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid
) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering b
ass. <I>Milk</I> was also guitarist John Frusciantes debut with the group and he
 shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.c
om essential recording - The Chili Peppers finally hit their stride with <I>Moth
ers Milk</I>, for the first time making their breakneck mix of funk, rap, and me
tal smooth enough to attract the masses, while keeping it raw enough not to alie
nate old fans. Theyve straddled that edge ever since.  It didnt hurt that they o
ffered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce th
e album. That single though, and the rest of <I>Mothers Milk</I> (including Knoc
k Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss 
in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist Joh
n Frusciantes debut with the group and he shines, especially on Jimi Hendrixs Fi
re. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Pepp
ers finally hit their stride with <I>Mothers Milk</I>, for the first time making
 their breakneck mix of funk, rap, and metal smooth enough to attract the masses
, while keeping it raw enough not to alienate old fans. Theyve straddled that ed
ge ever since.  It didnt hurt that they offered a pretty mainstream cover of Ste
vie Wonders Higher Ground to introduce the album. That single though, and the re
st of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican Ma
id) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering
 bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group and 
he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon
.com essential recording - The Chili Peppers finally hit their stride with <I>Mo
thers Milk</I>, for the first time making their breakneck mix of funk, rap, and 
metal smooth enough to attract the masses, while keeping it raw enough not to al
ienate old fans. Theyve straddled that edge ever since.  It didnt hurt that they
 offered a pretty mainstream cover of Stevie Wonders Higher Ground to introduce 
the album. That single though, and the rest of <I>Mothers Milk</I> (including Kn
ock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiedis
s in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist J
ohn Frusciantes debut with the group and he shines, especially on Jimi Hendrixs 
Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili Pe
ppers finally hit their stride with <I>Mothers Milk</I>, for the first time maki
ng their breakneck mix of funk, rap, and metal smooth enough to attract the mass
es, while keeping it raw enough not to alienate old fans. Theyve straddled that 
edge ever since.  It didnt hurt that they offered a pretty mainstream cover of S
tevie Wonders Higher Ground to introduce the album. That single though, and the 
rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexican 
Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chatteri
ng bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group an
d he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amaz
on.com essential recording - The Chili Peppers finally hit their stride with <I>
Mothers Milk</I>, for the first time making their breakneck mix of funk, rap, an
d metal smooth enough to attract the masses, while keeping it raw enough not to 
alienate old fans. Theyve straddled that edge ever since.  It didnt hurt that th
ey offered a pretty mainstream cover of Stevie Wonders Higher Ground to introduc
e the album. That single though, and the rest of <I>Mothers Milk</I> (including 
Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kied
iss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitarist
 John Frusciantes debut with the group and he shines, especially on Jimi Hendrix
s Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chili 
Peppers finally hit their stride with <I>Mothers Milk</I>, for the first time ma
king their breakneck mix of funk, rap, and metal smooth enough to attract the ma
sses, while keeping it raw enough not to alienate old fans. Theyve straddled tha
t edge ever since.  It didnt hurt that they offered a pretty mainstream cover of
 Stevie Wonders Higher Ground to introduce the album. That single though, and th
e rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexica
n Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chatte
ring bass. <I>Milk</I> was also guitarist John Frusciantes debut with the group 
and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Am
azon.com essential recording - The Chili Peppers finally hit their stride with <
I>Mothers Milk</I>, for the first time making their breakneck mix of funk, rap, 
and metal smooth enough to attract the masses, while keeping it raw enough not t
o alienate old fans. Theyve straddled that edge ever since.  It didnt hurt that 
they offered a pretty mainstream cover of Stevie Wonders Higher Ground to introd
uce the album. That single though, and the rest of <I>Mothers Milk</I> (includin
g Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Ki
ediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guitari
st John Frusciantes debut with the group and he shines, especially on Jimi Hendr
ixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Chil
i Peppers finally hit their stride with <I>Mothers Milk</I>, for the first time 
making their breakneck mix of funk, rap, and metal smooth enough to attract the 
masses, while keeping it raw enough not to alienate old fans. Theyve straddled t
hat edge ever since.  It didnt hurt that they offered a pretty mainstream cover 
of Stevie Wonders Higher Ground to introduce the album. That single though, and 
the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Mexi
can Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas chat
tering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the grou
p and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: 
Amazon.com essential recording - The Chili Peppers finally hit their stride with
 <I>Mothers Milk</I>, for the first time making their breakneck mix of funk, rap
, and metal smooth enough to attract the masses, while keeping it raw enough not
 to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt tha
t they offered a pretty mainstream cover of Stevie Wonders Higher Ground to intr
oduce the album. That single though, and the rest of <I>Mothers Milk</I> (includ
ing Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthony 
Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also guita
rist John Frusciantes debut with the group and he shines, especially on Jimi Hen
drixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The Ch
ili Peppers finally hit their stride with <I>Mothers Milk</I>, for the first tim
e making their breakneck mix of funk, rap, and metal smooth enough to attract th
e masses, while keeping it raw enough not to alienate old fans. Theyve straddled
 that edge ever since.  It didnt hurt that they offered a pretty mainstream cove
r of Stevie Wonders Higher Ground to introduce the album. That single though, an
d the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy Me
xican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas ch
attering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the gr
oup and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source
: Amazon.com essential recording - The Chili Peppers finally hit their stride wi
th <I>Mothers Milk</I>, for the first time making their breakneck mix of funk, r
ap, and metal smooth enough to attract the masses, while keeping it raw enough n
ot to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt t
hat they offered a pretty mainstream cover of Stevie Wonders Higher Ground to in
troduce the album. That single though, and the rest of <I>Mothers Milk</I> (incl
uding Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anthon
y Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also gui
tarist John Frusciantes debut with the group and he shines, especially on Jimi H
endrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - The 
Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the first t
ime making their breakneck mix of funk, rap, and metal smooth enough to attract 
the masses, while keeping it raw enough not to alienate old fans. Theyve straddl
ed that edge ever since.  It didnt hurt that they offered a pretty mainstream co
ver of Stevie Wonders Higher Ground to introduce the album. That single though, 
and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sexy 
Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fleas 
chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with the 
group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Sour
ce: Amazon.com essential recording - The Chili Peppers finally hit their stride 
with <I>Mothers Milk</I>, for the first time making their breakneck mix of funk,
 rap, and metal smooth enough to attract the masses, while keeping it raw enough
 not to alienate old fans. Theyve straddled that edge ever since.  It didnt hurt
 that they offered a pretty mainstream cover of Stevie Wonders Higher Ground to 
introduce the album. That single though, and the rest of <I>Mothers Milk</I> (in
cluding Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from Anth
ony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also g
uitarist John Frusciantes debut with the group and he shines, especially on Jimi
 Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - Th
e Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the first
 time making their breakneck mix of funk, rap, and metal smooth enough to attrac
t the masses, while keeping it raw enough not to alienate old fans. Theyve strad
dled that edge ever since.  It didnt hurt that they offered a pretty mainstream 
cover of Stevie Wonders Higher Ground to introduce the album. That single though
, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy Sex
y Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Flea
s chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with th
e group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>So
urce: Amazon.com essential recording - The Chili Peppers finally hit their strid
e with <I>Mothers Milk</I>, for the first time making their breakneck mix of fun
k, rap, and metal smooth enough to attract the masses, while keeping it raw enou
gh not to alienate old fans. Theyve straddled that edge ever since.  It didnt hu
rt that they offered a pretty mainstream cover of Stevie Wonders Higher Ground t
o introduce the album. That single though, and the rest of <I>Mothers Milk</I> (
including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from An
thony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was also
 guitarist John Frusciantes debut with the group and he shines, especially on Ji
mi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording - 
The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the fir
st time making their breakneck mix of funk, rap, and metal smooth enough to attr
act the masses, while keeping it raw enough not to alienate old fans. Theyve str
addled that edge ever since.  It didnt hurt that they offered a pretty mainstrea
m cover of Stevie Wonders Higher Ground to introduce the album. That single thou
gh, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy S
exy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to Fl
eas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut with 
the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>
Source: Amazon.com essential recording - The Chili Peppers finally hit their str
ide with <I>Mothers Milk</I>, for the first time making their breakneck mix of f
unk, rap, and metal smooth enough to attract the masses, while keeping it raw en
ough not to alienate old fans. Theyve straddled that edge ever since.  It didnt 
hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Ground
 to introduce the album. That single though, and the rest of <I>Mothers Milk</I>
 (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--from 
Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was al
so guitarist John Frusciantes debut with the group and he shines, especially on 
Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recording 
- The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the f
irst time making their breakneck mix of funk, rap, and metal smooth enough to at
tract the masses, while keeping it raw enough not to alienate old fans. Theyve s
traddled that edge ever since.  It didnt hurt that they offered a pretty mainstr
eam cover of Stevie Wonders Higher Ground to introduce the album. That single th
ough, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the randy
 Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals to 
Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut wit
h the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby</
I>Source: Amazon.com essential recording - The Chili Peppers finally hit their s
tride with <I>Mothers Milk</I>, for the first time making their breakneck mix of
 funk, rap, and metal smooth enough to attract the masses, while keeping it raw 
enough not to alienate old fans. Theyve straddled that edge ever since.  It didn
t hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Grou
nd to introduce the album. That single though, and the rest of <I>Mothers Milk</
I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--fro
m Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> was 
also guitarist John Frusciantes debut with the group and he shines, especially o
n Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential recordin
g - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for the
 first time making their breakneck mix of funk, rap, and metal smooth enough to 
attract the masses, while keeping it raw enough not to alienate old fans. Theyve
 straddled that edge ever since.  It didnt hurt that they offered a pretty mains
tream cover of Stevie Wonders Higher Ground to introduce the album. That single 
though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the ran
dy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals t
o Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut w
ith the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ruby
</I>Source: Amazon.com essential recording - The Chili Peppers finally hit their
 stride with <I>Mothers Milk</I>, for the first time making their breakneck mix 
of funk, rap, and metal smooth enough to attract the masses, while keeping it ra
w enough not to alienate old fans. Theyve straddled that edge ever since.  It di
dnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher Gr
ound to introduce the album. That single though, and the rest of <I>Mothers Milk
</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper--f
rom Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> wa
s also guitarist John Frusciantes debut with the group and he shines, especially
 on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential record
ing - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for t
he first time making their breakneck mix of funk, rap, and metal smooth enough t
o attract the masses, while keeping it raw enough not to alienate old fans. They
ve straddled that edge ever since.  It didnt hurt that they offered a pretty mai
nstream cover of Stevie Wonders Higher Ground to introduce the album. That singl
e though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the r
andy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vocals
 to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes debut
 with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael Ru
by</I>Source: Amazon.com essential recording - The Chili Peppers finally hit the
ir stride with <I>Mothers Milk</I>, for the first time making their breakneck mi
x of funk, rap, and metal smooth enough to attract the masses, while keeping it 
raw enough not to alienate old fans. Theyve straddled that edge ever since.  It 
didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Higher 
Ground to introduce the album. That single though, and the rest of <I>Mothers Mi
lk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pepper-
-from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I> 
was also guitarist John Frusciantes debut with the group and he shines, especial
ly on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential reco
rding - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, for
 the first time making their breakneck mix of funk, rap, and metal smooth enough
 to attract the masses, while keeping it raw enough not to alienate old fans. Th
eyve straddled that edge ever since.  It didnt hurt that they offered a pretty m
ainstream cover of Stevie Wonders Higher Ground to introduce the album. That sin
gle though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and the
 randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face voca
ls to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes deb
ut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michael 
Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit t
heir stride with <I>Mothers Milk</I>, for the first time making their breakneck 
mix of funk, rap, and metal smooth enough to attract the masses, while keeping i
t raw enough not to alienate old fans. Theyve straddled that edge ever since.  I
t didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Highe
r Ground to introduce the album. That single though, and the rest of <I>Mothers 
Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Peppe
r--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk</I
> was also guitarist John Frusciantes debut with the group and he shines, especi
ally on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential re
cording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>, f
or the first time making their breakneck mix of funk, rap, and metal smooth enou
gh to attract the masses, while keeping it raw enough not to alienate old fans. 
Theyve straddled that edge ever since.  It didnt hurt that they offered a pretty
 mainstream cover of Stevie Wonders Higher Ground to introduce the album. That s
ingle though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and t
he randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face vo
cals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes d
ebut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Michae
l Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally hit
 their stride with <I>Mothers Milk</I>, for the first time making their breaknec
k mix of funk, rap, and metal smooth enough to attract the masses, while keeping
 it raw enough not to alienate old fans. Theyve straddled that edge ever since. 
 It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders Hig
her Ground to introduce the album. That single though, and the rest of <I>Mother
s Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure Pep
per--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Milk<
/I> was also guitarist John Frusciantes debut with the group and he shines, espe
cially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essential 
recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I>,
 for the first time making their breakneck mix of funk, rap, and metal smooth en
ough to attract the masses, while keeping it raw enough not to alienate old fans
. Theyve straddled that edge ever since.  It didnt hurt that they offered a pret
ty mainstream cover of Stevie Wonders Higher Ground to introduce the album. That
 single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down and
 the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-face 
vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciantes
 debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Mich
ael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally h
it their stride with <I>Mothers Milk</I>, for the first time making their breakn
eck mix of funk, rap, and metal smooth enough to attract the masses, while keepi
ng it raw enough not to alienate old fans. Theyve straddled that edge ever since
.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders H
igher Ground to introduce the album. That single though, and the rest of <I>Moth
ers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure P
epper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>Mil
k</I> was also guitarist John Frusciantes debut with the group and he shines, es
pecially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essentia
l recording - The Chili Peppers finally hit their stride with <I>Mothers Milk</I
>, for the first time making their breakneck mix of funk, rap, and metal smooth 
enough to attract the masses, while keeping it raw enough not to alienate old fa
ns. Theyve straddled that edge ever since.  It didnt hurt that they offered a pr
etty mainstream cover of Stevie Wonders Higher Ground to introduce the album. Th
at single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down a
nd the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-fac
e vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Frusciant
es debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--Mi
chael Ruby</I>Source: Amazon.com essential recording - The Chili Peppers finally
 hit their stride with <I>Mothers Milk</I>, for the first time making their brea
kneck mix of funk, rap, and metal smooth enough to attract the masses, while kee
ping it raw enough not to alienate old fans. Theyve straddled that edge ever sin
ce.  It didnt hurt that they offered a pretty mainstream cover of Stevie Wonders
 Higher Ground to introduce the album. That single though, and the rest of <I>Mo
thers Milk</I> (including Knock Me Down and the randy Sexy Mexican Maid) is pure
 Pepper--from Anthony Kiediss in-your-face vocals to Fleas chattering bass. <I>M
ilk</I> was also guitarist John Frusciantes debut with the group and he shines, 
especially on Jimi Hendrixs Fire. <I>--Michael Ruby</I>Source: Amazon.com essent
ial recording - The Chili Peppers finally hit their stride with <I>Mothers Milk<
/I>, for the first time making their breakneck mix of funk, rap, and metal smoot
h enough to attract the masses, while keeping it raw enough not to alienate old 
fans. Theyve straddled that edge ever since.  It didnt hurt that they offered a 
pretty mainstream cover of Stevie Wonders Higher Ground to introduce the album. 
That single though, and the rest of <I>Mothers Milk</I> (including Knock Me Down
 and the randy Sexy Mexican Maid) is pure Pepper--from Anthony Kiediss in-your-f
ace vocals to Fleas chattering bass. <I>Milk</I> was also guitarist John Fruscia
ntes debut with the group and he shines, especially on Jimi Hendrixs Fire. <I>--
Michael Ruby</I>
length = 73056

Source: Amazon.com essential recording - What <I>Highway to Hell</I> has that <I
>Back in Black</I> doesnt is Bon Scott, AC/DCs original lead singer who died jus
t months after this album was released. Scott had a rusty, raspy, scream of a vo
ice, like he might break into a coughing fit at any moment. In other words, on c
runchy, hook-heavy metal classics like the title track, and on Get It Hot which 
is more roadhouse rock than metal, he had the perfect instrument for such wild-l
iving anthems. Too perfect, it turned out.  <i>--David Cantwell</i>
length = 547