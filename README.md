# Tweeki, a friendly starter theme for MediaWiki

Tweeki is a fork of **[Strapping](https://github.com/OSAS/strapping-mediawiki)**, which
is an elegant, responsive, and friendly starter skin for MediaWiki.
Its purpose is to provide a good base to build upon,
and was primarily created to provide a great default for **wiki-as-a-website**
— but it works well for standard wikis too.

Tweeki is built on top of a modified Vector theme from **[MediaWiki](http://mediawiki.org/)**
and utilizes Twitter's **[Bootstrap](http://getbootstrap.com/)**
for base layout, typography, and additional widgets.

Because Tweeki uses Bootstrap with its responsive extension,
any site using this skin works well on desktop browsers
and scales down to display beautifully on hand-held devices
like tablets and smartphones.

You can see an instance of Tweeki in action at the project's homepage http://tweeki.thai-land.at/


## Get started

1. Change to the "skins" subdirectory of your MediaWiki installation:

   ```
   cd skins
   ```

2. Clone the repository:

   ```
   git clone https://github.com/thaider/tweeki tweeki
   ```

3. Add the following to `LocalSettings.php`: 

   ```php
   require_once( "$IP/skins/tweeki/tweeki.php" );
   $wgDefaultSkin = "tweeki";
   ```
   
   (You may safely remove or comment out other mentions of
   `$wgDefaultSkin`.)

## Optional configuration

Please refer to the projects website http://tweeki.thai-land.at/ for further information
about configuration and customization options.

## Caveats

Tweeki does not implement the SkinTemplateToolboxEnd hook. 
Please use [BaseTemplateToolbox](http://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox)
instead!


## Licensing, Copying, Usage

Tweeki is open source, and built on open source projects.

Please check out the [LICENSE file](https://github.com/thaider/tweeki/blob/master/LICENSE) for details.
