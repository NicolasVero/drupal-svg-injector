# Svg Injector 🧩

## Overview

**Svg Injector** is a lightweight and flexible Drupal module that allows you to render SVG icons directly from Twig using a dedicated function.  
It automatically loads SVG files from a configurable directory, applies custom attributes, and injects the final markup inline, ensuring full styling control and optimal front-end performance.

This module is ideal for theme developers who need clean, configurable, and reusable SVG rendering inside Drupal templates.

<br>

## Features

### ✨ Inline SVG rendering via Twig

Use the custom Twig function:

```twig
{{ svg_icon('icon_name', { fill: '#fff', width: 24 }) }}
```

The module loads the corresponding `.svg` file, injects your custom attributes, and outputs the final SVG inline.

---

### 🧰 Customizable SVG attributes

You can pass the following parameters to `svg_icon()`:

| Parameter      | Description                                 | Example                              |
|----------------|---------------------------------------------|---------------------------------------|
| `fill`         | Sets the SVG fill color                     | `fill: '#fff'`                        |
| `stroke`       | Sets the stroke color                       | `stroke: '#000'`                      |
| `stroke_width` | Stroke width (`stroke-width`)               | `stroke_width: 1.5`                   |
| `width`        | SVG width (with automatic unit)             | `width: 24`                           |
| `height`       | SVG height (with automatic unit)            | `height: 24`                          |
| `size`         | Sets both width and height                  | `size: 32`                            |
| `class`        | Adds CSS classes to the `<svg>`             | `class: 'icon-lg'`                    |
| `id`           | Sets the SVG `id`                           | `id: 'menu-icon'`                     |
| `aria_label`   | Accessibility label (`aria-label`)          | `aria_label: 'Menu'`                  |
| `role`         | ARIA role                                   | `role: 'img'`                         |

---

### 💻 Use cases

Here are a few practical examples of how to use the Twig function:
```twig
{{ svg_icon('arrow-left') }}
{{ svg_icon('check', { fill: '#00ff00' }) }}
{{ svg_icon('alert', { size: 2 }) }}
{{ svg_icon('clock', { stroke: '#333', stroke_width: 2 }) }}
{{ svg_icon('search', { class: 'icon icon--large' }) }}
```
---

### ⚙ Module Configuration

The configuration settings for the module are available at `/admin/config/media/svg-injector`.

The module provides two configuration options:

- **Path to SVG icons**  
  Defines the directory in which your SVG files are stored.  
  The module scans this folder and automatically indexes all `.svg` files so they can be used with the `svg_icon()` Twig function.

- **Unit for size, width, and height**  
  Determines the default CSS unit applied when numeric values are passed to `size`, `width`, or `height` in Twig.  
  Supported units include `px`, `em`, `rem`, `%`, `vw`, and `vh`, offering flexible sizing that adapts to your design system.

- **SVG cache duration**  
  Defines how long the generated SVG index should be cached.  
  Increasing the duration improves performance, while lowering it ensures faster updates when SVG files change.  
  The default value is **3600 seconds** (1 hour).

---

### 🧹 Drush Commands

The module provides a dedicated Drush command to clear the SVG index cache without flushing the entire Drupal cache:

**`svg-injector:cache-remove` /  `svg-injector:cr` / `si:cr`**  
Clears the cached SVG index so newly added or modified SVG files are detected immediately.
This allows you to refresh only the SVG-related cache quickly, similar to `drush cr` but without affecting the rest of the site.

<br>

## Compatibility

This module requires a minimum of **Drupal 9** and **PHP 8.2** to operate correctly.



