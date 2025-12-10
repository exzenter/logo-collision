=== Context-Aware Animation ===
Contributors: wpmitch
Tags: animation, scroll, gsap, header, logo
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Apply context-aware scroll animations to your WordPress header logo when it would collide with scrolling content.

== Description ==

Context-Aware Animation is a WordPress plugin that automatically detects when your header logo would collide with scrolling content and applies one of seven beautiful animation effects to move it out of the way.

**Features:**

* 7 different animation effects to choose from:
  * Scale - Scales down and hides, then scales up and shows
  * Blur - Applies blur effect and scales
  * Slide Text - Slides text up and down
  * Text Split - Splits text into characters and scatters them
  * Character Shuffle - Shuffles characters before revealing
  * Rotation - Rotates and moves simultaneously
  * Move Away - Moves horizontally off-screen

* Automatic content detection - Works with common WordPress content selectors
* Excluded elements - Specify elements that should not trigger animations
* Easy configuration through WordPress admin settings
* Powered by GSAP (GreenSock Animation Platform) for smooth, performant animations

== Installation ==

1. Upload the `context-aware-animation` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Context-Aware Animation to configure the plugin
4. Enter your header logo CSS selector (e.g., `#site-logo` or `.logo`)
5. Select your preferred animation effect
6. Optionally add excluded elements that should not trigger animations

== Frequently Asked Questions ==

= What CSS selector should I use for my logo? =

Use the same selector you would use in CSS to target your logo. Common examples:
* `#site-logo` - if your logo has an ID
* `.logo` - if your logo has a class
* `#header-logo` - another common ID
* `header .site-branding img` - for more specific targeting

= Which effect should I choose? =

All effects work well, but some are better for different scenarios:
* **Scale** - Subtle and professional
* **Blur** - Modern and smooth
* **Slide Text** - Works best with text logos
* **Text Split** - Dramatic effect for text logos
* **Character Shuffle** - Playful effect for text logos
* **Rotation** - Dynamic and eye-catching
* **Move Away** - Simple and clean

= How do I exclude elements? =

Enter comma-separated CSS selectors in the "Excluded Elements" field. For example:
`#sidebar, .widget, .navigation, footer`

= Does this work with all themes? =

The plugin automatically detects common WordPress content areas. If your theme uses non-standard selectors, you may need to add custom CSS or modify the plugin's content detection.

== Screenshots ==

1. Settings page with effect selection
2. Animation in action

== Changelog ==

= 1.0.0 =
* Initial release
* 7 animation effects
* Admin settings page
* Excluded elements support
* Automatic content detection

== Upgrade Notice ==

= 1.0.0 =
Initial release of Context-Aware Animation plugin.

