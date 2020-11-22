# Tweeki, a Bootstrap based skin for MediaWiki

Tweeki is a skin for **[MediaWiki](http://mediawiki.org/)** (1.31+) based on
**[Bootstrap](http://getbootstrap.com/)** (v3.4.1 and v4.4.1). It tries to implement as 
much of Bootstrap's functionality as possible, allowing to use it very easily and with 
very reduced markup, and features many configuration options.

You can find an instance of Tweeki in action and the extended documentation at the 
project's website **[tweeki.kollabor.at](http://tweeki.kollabor.at/)**


## Get started

1. Change to the "skins" subdirectory of your MediaWiki installation:

   ```
   cd skins
   ```

2. Clone the repository:

   ```
   git clone https://github.com/thaider/Tweeki Tweeki
   ```

3. Add the following to `LocalSettings.php`: 

   ```php
   wfLoadSkin( 'Tweeki' );
   $wgDefaultSkin = "tweeki";
   ```

## Optional configuration

Please refer to the project's website **[tweeki.kollabor.at](http://tweeki.kollabor.at/)** 
for further information about configuration and customization options.


## Licensing, Copying, Usage

Tweeki is open source, and built on open source projects.

Please check out the [LICENSE file](https://github.com/thaider/Tweeki/blob/master/LICENSE) 
for details.
