# Kirby Recently Modified

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-recently-modified?color=ae81ff)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-recently-modified?color=272822)
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)

Kirby Plugin to view recently modified pages by current User

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-recently-modified/archive/master.zip) as folder `site/plugins/kirby3-recently-modified` or
- `git submodule add https://github.com/bnomei/kirby3-recently-modified.git site/plugins/kirby3-recently-modified` or
- `composer require bnomei/kirby3-recently-modified`

## Screenshot

### Section
![recently-modified](https://raw.githubusercontent.com/bnomei/kirby3-recently-modified/master/screenshot.png)

### Field
![recently-modified](https://raw.githubusercontent.com/bnomei/kirby3-recently-modified/master/screenshot.gif)

## Usage

Add the section to your site or page blueprint to display a list of the most recently modified pages by the currently logged in user. The sections is not able to list the site itself since the section depends on a collection of pages for the query.

**site/blueprints/site.yml**
```yaml
sections:
  listPagesModifiedByUser:
    type: recentlymodified
    headline: Your Recently Modified Pages
    # query
```

Optionally you can add the field to the site or any page blueprint to show the time and user that modified given content most recently. In contrast to the section the field is able to show most recent modified information for the site (`content/site.txt`).

**site/blueprints/pages/default.yml**
```yaml
fields:
  showWhichUserModifiedPage:
    type: recentlymodified
    label: Recently Modified By
    # interval: 60
```

> ⚠️ This plugin has by default a 1 minute cache.

### Query for the Section (not Field)

The plugins section comes with a default query that shows the most recent changes made by the currently logged in user. But you can define any other query you like.

**Default Query**
```
site.index(true).sortBy('modified', 'desc').onlyModifiedByUser
```

> `onlyModifiedByUser` is a pagesmethod added by this plugin that filters the pages collection to only those pages that where modified by the currently logged in user. The plugin uses hooks to track what pages each user did edit.

**Example 1**
```yaml
sections:
  recentarticles:
    type: recentlymodified
    headline: Recently Modified Articles
    query: site.find('articles').children.listed.sortBy('modified', 'desc')
```

**Example 2**
```yaml
sections:
  mycollection:
    type: recentlymodified
    headline: My Collection
    query: kirby.collection('my-collection')
```
```php
return [
    'bnomei.recently-modified.limit' => 25, // default: 7
    'bnomei.recently-modified.info' => function ($page) {
        return $page->id();
    },
    // other options...
];
```

> TIP: You could use the `query` property, `info` and `limit` setting to show any list of pages you want. Just like simplified version of the pagetable plugin.

## Known Limitations

- You can not set multiple `text`/`link`/`info` settings. All instances of the `recentlymodified` section share the same.
- The `limit` setting is always applied. If you want some of your instances to have a smaller number of items then call `limit` inside your custom query.

## Settings

| bnomei.recently-modified. | Default                | Description                             |            
|---------------------------|------------------------|-----------------------------------------|
| query                     | `...`                  | see above                               |
| link                      | `function($page){...}` | callback to return the link             |
| text                      | `function($page){...}` | callback to return the text             |
| info                      | `function($page){...}` | callback to return the info             |
| format                    | `fn($datetime){...}`   | custom date format callback             |
| hooks                     | `true`                 | use hooks to track users modified pages |
| limit                     | `7`                    | limit list and cache items              |
| expire                    | `1`                    | cache will expire n-minutes             |

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-recently-modified/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
