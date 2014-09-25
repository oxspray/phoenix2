<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is a playground for framework function tests.
*/

// Load the PHP framework
require_once('../settings.php');
require_once('framework/php/framework.php');

// Session
session_start();
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

//print_r($ps->_filters);

$xml = '
<gl><id>555510127</id>
    <zitf>chV0127</zitf>
    <an>
        <nom>125</nom>
        <d>1269, mai</d>
        <d0>0000/00/00</d0>
        <scripta>-</scripta>
        <loc>-</loc>
        <loc0>-</loc0>
        <soc>-</soc>
        <soc0>-</soc0>
        <type>charte:</type>
        <r>Confirmation par Ferri, doyen, et par le chapitre de Saint-Dié, de l\'accord conclu entre les chanoines et Renaut, sire de Romont<fua>Cette confirmation est de mai 1269, alors qu\'une expédition originale du texte ici inséré sans date figure plus bas avec la date du 26 mars 1270 (no. 133). Il semble difficile de rendre compte de cette situation qu\'expliqueraient peut-être des tractations dont nous n\'avons pas conservé la trace.</fua>. Acte passé sous les sceaux de Ferri, doyen du chapitre de Saint-Dié, et de Ferri [III], duc de Lorraine.</r>
        <aut>-</aut>
        <disp>-</disp>
        <s>-</s>
        <b>-</b>
        <act>-</act>
        <rd>-</rd>
        <rd0>-</rd0>
        <sc>-</sc>
        <f>Parchemin scellé sur double queue de deux sceaux subsistant en partie: à gauche, celui de Ferri III, rond, de cire vierge (A. Philippe, Inventaire des sceaux de la série G, no. 16), à droite, celui du doyen du chapitre, ovale, de cire brune (non relevé dans A. Philippe).</f>
        <l>Archives départementales des Vosges, G 776 (fonds du chapitre de Saint-Dié).</l>
        <ed>-</ed>
        <ana>-</ana>
        <ec>-</ec>
        <met>-</met>
        <v>De lai pas dou moulim de Sain Morise (XIVe s.).</v>
        <transcr>-</transcr>
        <resp>Responsable de l\'édition électronique: M.-D. G.</resp>
    </an>
    <txt>
        <int>
            <div n="1"> Nos, Ferris, doiens de l\'englise de Seint Dié,</div>
            <div n="2"> et tous li chapitres de sou moimes lui,</div>
        </int>
        <pub>
            <div n="3"> fasons conissant à tous saus qui ces lettres varront et oront </div>
        </pub>
        <exp>
            <div n="4"> que, com bestans <zw/> et descors fust antre nos d\'une part, et lou signor Regnaut, signor de Romont, d\'autre part, que li sires Regnaus davant dis avoit fait un molin sus la reviere <zw/> de Mortame desous Seint Morise, sa vile, par-quoi nostres molins c\'on apelle Costanmolin est deffais et aleis à-niant,</div>
            <div n="5"> pax et concorde en est faite entre nos et lou signor Regnaut <zw/> ensi con les lettres qu\'il en ait donees à nos lou devisent.</div>
            <div n="6"> Et le tenors de ces lettres est telle:</div>
        </exp> 
        <vid>
            <int>
                <div n="7"> Je, Regnaus, sires de Romont,</div>
            </int>
            <pub>
                <div n="8"> fas savoir à toz saus qui ces letres varront <zw/> et oront </div>
            </pub>
            <exp>
                <div n="9"> que, com bestans et descors fust antre nos d\'une part, et le doien et le chapitre de-l\'englise de Seint Dié d\'autre part, de ceu que je avoie fait un molin sus la reviere de <zw/> Mortame desous Seint Morise, ma vile, par quoi li molins c\'on apelle Constans Molins, qui estoit à doien et au chapitre davant diz, est deffais et aleis à niant,</div>
                <div n="10"> pax et <zw/> concorde en est faite en tel meniere, par consoil de bones gens, que je ai donei et doig à toz jors le davan dit molin à-l\'eglise de Seint Dié davant dite,</div>
                <div n="11"> et li doi garentir et <zw/> je et mi hoir à-tous jor mas vers totes gens qui à-droit vorroient venir, en-tel meniere que je et Sebile, ma feme, panrons ou davandit molin, tote nostre vie, la moitié de <zw/> totes vallances et de tous prowages dou molin,</div>
                <div n="12"> et i meterons musniers et serjans telz com il covient à molin, par comun concort;</div>
                <div n="13"> et se nos ne nos poiens concorder dou metre, <zw/> li doiens et li chapitres davant dis les i meteroit un an, et je ou ma feme l\'autre an;</div>
                <div n="14"> et devons meintenir et retenir lou molin de totes choses en comun despans;</div>
                <div n="15"> <zw/> et se li molins davant dis estoit deffais par aventure ou par guerre ou autrement, je et li devant dite eglisie seriens tenui de refaire autre molin en comun despans, et je segne<zwt/>roie le siege et le box;</div>
                <div n="16"> et devons, je et li eglise davant dite, fare morre à davandit molin par ban et par justice, je, mes homes de Romont et de Seint Morise, et li eglise, les <zw/> suens homes de Moimont,</div>
                <div n="17"> sauf ceu que je et mi home de Romont poons morre quant nos vorrons et porrons à molin de mon estan de Romont,</div>
                <div n="18"> et li home de Moymont <zw/> puent morre autresi à molin de l\'estan de Moymont.</div>
                <div n="19"> Et se par aventure avenoit que li davandis molins ne soffesist à davandites villes, li eglise davant dite porroit <zw/> faire un autre molin sus celle reviere, ou que li plaroit ou ban de Seint Morise et de Romont, et je seroie tenus de segnier lou siege dou molin et lou box et lou <zw/> charroi de ma terre, et li davan dite eglise lou charroi de la sue terre;</div>
                <div n="20"> et si lou feroit dou tot au suen, si averiens, je et ma feme davant-dite, la moitié à nos <zw/> vies en totes choses en celui molin, ensi com ou molin davan dit, et seriens tenui dou retenir et meintenir en comun despans;</div>
                <div n="21"> et aprés la mort de moi et <zw/> de ma feme, avera li eglise devant dite lez diz molins toz quites sans partie de nos hoirs.</div>
                <div n="22"> Et est covans que je ne mi hoir ne poons faire jamas molin <zw/> en la reviere de Mortame devant dite, ne desus ne desous, ou ban de Seint Morise et de Romont.</div>
                <div n="23"> Et en totes ces choses davant dites à tenir et à warder en <zw/> bone foi à-toz jors obligen<fue>Ainsi coupé: <abr>obli gen</abr>.</fue> par ces presentes letres mes hoirs et mon heritage et moi par sarment.</div>
            </exp>
            <cor>
                <div n="24"> Et sui ai je fait par lou loz et par la volontei de mon signor Ferri, <zw/> duc de Lorrenne et marchis,</div>
                <div n="25"> qui en ces presentes lettres ait mis son seel awec le mien, par ma requaste, en tesmognage de veritei.</div>
                <div n="26"> Et wel que li devan diz duz et <zw/> sui hoir, ce je ou mi hoir aleiens<fue>On pourrait lire <abr>aleieus</abr>; le scribe distingue habituellement assez bien <abr>n</abr> et <abr>u</abr>.</fue> de rienns encontre<fue>Ainsi coupé: <abr>en contre</abr>.</fue> ces choses devant dites, le fassent à tenir et joïr l\'eglise devant dite des devans diz molins entierement, <zw/> ensi com il est davant devisei.</div>
                <div n="27"> Et nos, Ferris, duz de Lorrenne et marchis, i avons mis nostre seel awec lou seel lou devandit signor de Romont, par <zw/> sa requaste.</div>
            </cor>
        </vid>
        <cor>
            <div n="28"> Et iceste pax otrions nos et ce nos i acordomes.</div>
            <div n="29"> Et por sui que ce soit ferme chose et astable, seu denons nos à davant dit signor Regnaut <zw/> de Romont ces letre seelees dou seel mon signor Ferri, duc de Lorrenne et marchiz,</div>
            <div n="30"> awec nostre seel, en tesmoignage de cest pax et de ces covenances.</div>
        </cor>
        <dat>
            <div n="31"> <zw/> Ce fuit fait en l\'an de l\'Incarnation Nostre Signor mil et .CC. et sexante et nuef ans, ou moys de mai.</div>
        </dat>
    </txt>
</gl>';

$mp = new XMLMigrationParser($xml,NULL,TRUE,TRUE);
$input_xml = $mp->getOutputXML();

echo $input_xml;

$p = new XMLTextParser();
$p->input_xml= $input_xml;
$p->text_corpusID = 19;

$p->parse();
echo $p->getOutputXML();
//print_r( $p->getLog() );


/*
$t = new Text(16);
$t->delete();
*/

/*
$import_file = '/Users/laeubli/Documents/rom_sem/phoenix/workspace/ph2/data/xml/temp/test_import.xml';

$handle = fopen($import_file, 'r');
$xml = fread($handle, filesize($import_file));
fclose($handle);

$c = new Corpus(2);
echo $c->checkin($xml);
*/

/*
$project = new Project($ps->getActiveProject());
print_r($project->getAssignedCorpora($as_resultset=TRUE));
*/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Phoenix2 Framework Test Playground</title>
</head>

<body>
	<?php  ?>
</body>
</html><?php /* Save ph2session */ $ps->save(); ?>