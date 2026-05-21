# tka navigation

A simple Navigation Plugin for all your Navigational needs

## Requirements

This plugin requires Craft CMS 5.10.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “tka navigation”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require thekitchen-agency/craft-tka-navigation

# tell Craft to install the plugin
./craft plugin/install tka-navigation
```
## Features

- **Dual Editor Interfaces**: Choose the workflow that fits your editors best:
  - **Slick Lean Interface** (Default): A modern, ultra-compact inline visual tree editor. Manage labels, paths, classes, and new-tab targets directly inside the nodes without opening modals or sidebars.
  - **Easy Interface**: A structured split-screen grid visual layout. Opens a premium sticky "Link Settings" sidebar inspector panel on the right for clean, focused node configuration.
- **Bulk Import**:
  - **Select Entries in Bulk**: Choose multiple entries from your catalog to instantly add them to the navigation structure as root-level links.
  - **Paste Plain Text Links**: Paste lists of raw text links formatted as `Label | /path` to quickly populate your navigation layout.
- **Nesting Restraints**: Set a maximum depth in the plugin settings to restrict editors from building menus that exceed your CSS design structure constraints.
- **Custom CSS Classes**: Easily toggle optional custom CSS inputs per node inside the editor.
- **Caching**: Performance-oriented database caching config with custom cache duration rules.

## Configuration

Navigate to **Settings -> Plugins -> tka navigation** to customize the plugin:
- **Max Nesting Depth**: Set the maximum allowed depth (e.g. 1 for root only, 0 for unlimited).
- **Enable Custom CSS Classes**: Toggle individual CSS fields per navigation node.
- **Default External Links to New Tab**: Automatically check 'Open in New Tab' for newly added custom/external nodes.
- **Use Slick Lean Editor Interface**: Toggle to switch between the inline editor and the sidebar inspector layouts.
- **Performance & Caching**: Enable/disable cache and configure the custom TTL.

## Template Usage

To render a navigation in your Twig templates, retrieve it using the `craft.tkaNavigation.get` variable helper by passing its handle, and loop through its resolved nodes:

```twig
{# 1. Retrieve the navigation by its handle #}
{% set navigation = craft.tkaNavigation.get('mainNavigation') %}

{# 2. Render the resolved navigation structure #}
{% if navigation %}
    <nav class="navigation">
        <ul>
            {% for node in navigation.getResolvedNodes() %}
                <li class="{{ node.cssClass }}">
                    <a href="{{ node.url }}" {% if node.newTab %}target="_blank" rel="noopener"{% endif %}>
                        {{ node.label }}
                    </a>

                    {# Render children recursively (supports unlimited nesting levels) #}
                    {% if node.children|length %}
                        <ul class="dropdown">
                            {% for child in node.children %}
                                <li class="{{ child.cssClass }}">
                                    <a href="{{ child.url }}" {% if child.newTab %}target="_blank" rel="noopener"{% endif %}>
                                        {{ child.label }}
                                    </a>
                                </li>
                            {% endfor %}
                        </ul>
                    {% endif %}
                </li>
            {% endfor %}
        </ul>
    </nav>
{% endif %}
```


