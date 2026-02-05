=== FAPI Signals ===
Contributors: fapi
Tags: conversion, tracking, pixels, analytics, fapi, pageview
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin pro jednoduche nasazeni pixelu, FAPI konverzi a volitelneho server-side PageView.

== Description ==
FAPI Signals vklada merici pixely do `wp_head`, konverzni kody pres FAPI do `wp_head` a `fapi.js` do `wp_footer`. Podporuje server-side PageView pro vybrane platformy a debug logovani do konzole.

Zakladni funkce:
- Pixely se vkladaji do `wp_head`
- FAPI konverzni kody se vkladaji do `wp_head`
- `fapi.js` se vklada do `wp_footer` (vzdy az za konverznimi kody)
- FAPI Rewards script se vklada do `wp_head` (vychozi zapnuto)
- Debug mode s logy do konzole
- Server-side PageView pro podporovane platformy

Poznamka k consent manageru:
- Integrace CMP je v kodu zachovana, ale v UI je sekce skryta a skripty se vkladaji okamzite.

== Installation ==
1) Nahrajte slozku pluginu do `wp-content/plugins/fapi-signals`
2) Aktivujte plugin v administraci WordPressu
3) Otevrite `Nastaveni -> FAPI Signals`

== Usage ==
1) Zapnete pozadovane pixely a vyplnte jejich ID
2) Zapnete konverze pro vybrane nastroje
3) Volitelne zapnete server-side PageView a vyplnite tokeny
4) FAPI Rewards script je vychozi zapnuty, lze jej vypnout
5) V Debug sekci muzete zapnout logovani do konzole

== Server-side PageView ==
Server-side mereni je pouze pro PageView. Konverze se odesilaji pres FAPI.
Podporovane platformy:
- Meta CAPI
- GA4 Measurement Protocol
- TikTok Events API
- Pinterest Conversions API
- LinkedIn CAPI

== Debug mode ==
Pokud je zapnuty, do konzole se loguje:
- injektovane pixely
- odeslane konverze
- server-side vysledky

== Pravni disclaimer ==
Plugin:
- technicky reaguje na souhlasy cookies
- neresi obsah cookies listy ani jeji texty
- neresi texty zasad cookies nebo dalsi pravni dokumenty

Odpovednost za spravne pravni nastaveni nese provozovatel webu.

== Changelog ==
= 0.1.0 =
- Prvni verze.

== Upgrade Notice ==
= 0.1.0 =
Prvni verejna verze.
