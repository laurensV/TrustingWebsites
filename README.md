Bachelor Thesis: Trusting websites using geograhpical consistency
================

by Laurens Verspeek

Universiteit van Amsterdam

## Abstract
With the growth of the Internet and its increasingly important role in our lives, there is
also an increase in the number of malicious websites on the web. There are already several
techniques to detect malicious websites, but none of these techniques take the geographical
features of the different technological components of a website into account. These features
can be used to determine the geographical consistency of a site, which might help the user
with correctly classifying websites. First, different technological components of a website
that may have a location are examined and different methods are researched to retrieve
the geographical locations of these components. Then the strategies for the presentation
of the retrieved information and good ways to combine that information are explained and
discussed. This knowledge results in an API and a tool for the user which might help the
user classify websites. An experiment where the participants had to classify websites with
and without the tool shows that the tool in its current form improves the classification
of both malicious and normal websites significally and also improved the certainty of the
classifications. This proves that geographical consistency is a relevant way to determine the
trustworthiness of websites.

## Thesis
[Download](trusting-website-using-geographical-consistency_laurens-verspeek.pdf)

## Overview implementation
Overview of the components of the server and chrome extension
![Overview implementation](paper/img/implementation.png?raw=true "Overview implementation")

## Screenshots
![Screenshot Chrome Extension](paper/img/tool.PNG?raw=true "Screenshot Chrome Extension")

## Setup server and chrome extension
#### Server requirements
* A webserver that supports PHP, such as Apache (Linux) or IIS (Windows)
* A webserver with at least 1 megabyte of available disk space.
* PHP 5.3.2+
* SQLite extension for PHP

#### Server setup
Download the server files from [server](server) and put them on your webserver.

#### Chrome extension setup
Download the extension files from [chromeextension](chromeextension).

* Open Google Chrome browser and navigate to extra ¿ extension.
* check the checkbox next to ”developer mode” and choose [Load extracted extension]
* Locate the previously downloaded extension files and enable the extension.
* click on the icon of the extension and fill in the domain name or ip-address of your server.