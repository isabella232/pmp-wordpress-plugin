#Installation and Settings for the WordPress PMP Plugin

## Installation

Follow the standard WordPress procedure for [automatic plugin installation](https://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation), and search for "PMP" or "Public Media Platform".  Using the [official plugin](https://wordpress.org/plugins/public-media-platform/) from the Wordpress plugin directory allows you to automatically get updates.

If you'd prefer the bleeding edge `master` version of the plugin, you'll have to install it manually, following the standard procedure for [manual plugin installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).  You can get the [latest code zip here](https://github.com/publicmediaplatform/phpsdk/archive/master.zip).

Once the plugin files are installed, activate the plugin via the WordPress dashboard.

## Settings

To use the PMP WordPress plugin, you'll need to specify a **Client ID** and **Client Secret** via the **Public Media Platform** > **Settings** page in the WordPress dashboard. In the PMP Environment dropdown select **Production** unless you are setting up a sandbox environment for testing. If you want to automatically pull updates if a story is revised in the PMP, check the Enable box.

![Settings](/assets/img/largo-PMP-settings-blank.png)

If you don't yet have a Client ID and Client Secret, you'll probably need to [request an account with the PMP](https://support.pmp.io/register).
