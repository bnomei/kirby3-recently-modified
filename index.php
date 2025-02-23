<?php

use Kirby\Cms\App;
use Kirby\Toolkit\Str;

App::plugin('bnomei/recently-modified', [
    'options' => [
        'query' => "site.index(true).sortBy('modified', 'desc').onlyModifiedByUser",
        'format' => function($datetime): string {
            $handler = kirby()->option('date.handler') ?? 'date';
            $formats = [
                'date' => 'Y/m/d H:i:s',
                'intl' => 'yyyy/MM/dd HH:mm:ss', // https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
                'strftime' => '%Y/%m/%d %H:%M:%S'
            ];
            $format = $formats[$handler] ?? $formats['date'];

            return Str::date($datetime, $format, $handler);
        },
        'info' => function (\Kirby\Cms\Page $page) {
            return option('bnomei.recently-modified.format')($page->modified());
        },
        'link' => function (\Kirby\Cms\Page $page) {
            return $page->panel()->url();
        },
        'text' => function (\Kirby\Cms\Page $page) {
            return $page->title()->value();
        },
        'hooks' => true,
        'limit' => 7, // track only that many
        'expire' => 1, // minutes
        'cache' => true,
    ],
    'fields' => [
        'recentlymodified' => [
            'props' => [
                'auser' => function () {
                    $user = $this->model()->findRecentlyModifiedByUser();
                    return $user ? (string)$user->nameOrEmail() : '';
                },
                'datetime' => function () {
                    return option('bnomei.recently-modified.format')($this->model()->modified());
                },
            ],
        ],
    ],
    'sections' => [
        'recentlymodified' => [
            'props' => [
                'headline' => function (string $headline = 'Recently Modified') {
                    return t($headline);
                },
                'query' => function (?string $query = null) {
                    $query = $query ?? option('bnomei.recently-modified.query');
                    $parentId = is_a($this->model(), \Kirby\Cms\Page::class) ? $this->model()->id() : '';
                    $pages = site()->recentlyModified($query, $parentId);
                    return array_values($pages->toArray(function ($page) {
                        return [
                            'link' => option('bnomei.recently-modified.link')($page),
                            'text' => option('bnomei.recently-modified.text')($page),
                            'info' => option('bnomei.recently-modified.info')($page),
                        ];
                    }));
                }
            ]
        ],
    ],
    'siteMethods' => [
        'recentlyModified' => function (string $query, string $parentId = '') {
            $user = kirby()->user();
            $cacheKey = md5($parentId . $query);
            $keys = kirby()->cache('bnomei.recently-modified')->get($cacheKey);
            if (!$keys) {
                $page = !empty($parentId) ? page($parentId) : null;
                $collection = \Kirby\Query\Query::factory($query)->resolve([
                    'kirby' => kirby(),
                    'site' => kirby()->site(),
                    'page' => $page,
                    'pages' => kirby()->site()->pages(),
                    'user' => $user,
                ]);
                $keys = $collection
                    ->limit(intval(option('bnomei.recently-modified.limit')))
                    ->toArray(fn ($page) => $page->id());
                kirby()->cache('bnomei.recently-modified')
                    ->set($cacheKey, $keys, intval(option('bnomei.recently-modified.expire')));
            }
            return pages($keys ?? []);
        },
        'modifiedTimestamp' => function () {
            $t = filemtime(site()->root().
                (kirby()->multilang()?'/site.'.kirby()->defaultLanguage()->code().'.txt':'/site.txt')
            );
            return $t ?: time();
        },
        'trackModifiedByUser' => function (bool $add = true): bool {
            if (!kirby()->user() || option('bnomei.recently-modified.hooks') !== true) {
                return false;
            }
            $cacheKey = kirby()->user()->id();

            $listKey = $this->id();
            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            if ($add) {
                $list[$listKey] = $this->modifiedTimestamp();
            } elseif (array_key_exists($listKey, $list)) {
                unset($list[$listKey]);
            }
            arsort($list);
            // NOTE: do not limit list or field will not work beyond the limit
            // $list = array_slice($list, 0, intval(option('bnomei.recently-modified.limit')));
            kirby()->cache('bnomei.recently-modified')->set($cacheKey, $list);
            return true;
        },
        'findRecentlyModifiedByUser' => function (): ?\Kirby\Cms\User {
            $modifier = null;
            $modified = null;
            $id = 'site';
            foreach (kirby()->users() as $user) {
                $list = kirby()->cache('bnomei.recently-modified')->get($user->id(), []);
                if (array_key_exists($id, $list)) {
                    $modifiedTS = $list[$id];
                    if (!$modified || $modified < $modifiedTS) {
                        $modifier = $user;
                        $modified = $modifiedTS;
                    }
                }
            }
            return $modifier;
        },
    ],
    'pageMethods' => [
        'trackModifiedByUser' => function (bool $add = true): bool {
            if (!kirby()->user() || option('bnomei.recently-modified.hooks') !== true) {
                return false;
            }
            $cacheKey = kirby()->user()->id();

            $listKey = $this->id();
            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            if ($add) {
                $list[$listKey] = $this->modified();
            } elseif (array_key_exists($listKey, $list)) {
                unset($list[$listKey]);
            }
            arsort($list);
            // NOTE: do not limit list or field will not work beyond the limit
            // $list = array_slice($list, 0, intval(option('bnomei.recently-modified.limit')));
            kirby()->cache('bnomei.recently-modified')->set($cacheKey, $list);
            return true;
        },
        'findRecentlyModifiedByUser' => function (): ?\Kirby\Cms\User {
            $modifier = null;
            $modified = null;
            $id = $this->id();
            foreach (kirby()->users() as $user) {
                $list = kirby()->cache('bnomei.recently-modified')->get($user->id(), []);
                if (array_key_exists($id, $list)) {
                    $modifiedTS = $list[$id];
                    if (!$modified || $modified < $modifiedTS) {
                        $modifier = $user;
                        $modified = $modifiedTS;
                    }
                }
            }
            return $modifier;
        },
    ],
    'pagesMethods' => [
        'onlyModifiedByUser' => function (?\Kirby\Cms\User $user = null) {
            $user = $user ?? kirby()->user();
            $cacheKey = $user->id();
            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            return $this->filterBy(function ($page) use ($list) {
                return array_key_exists($page->id(), $list);
            });
        },
    ],
    'hooks' => [
        'page.create:after' => function (\Kirby\Cms\Page $page) {
            $page->trackModifiedByUser();
        },
        'page.changeSlug:after' => function (Kirby\Cms\Page $newPage, Kirby\Cms\Page $oldPage) {
            $newPage->trackModifiedByUser();
        },
        'page.changeStatus:after' => function (Kirby\Cms\Page $newPage, Kirby\Cms\Page $oldPage) {
            $newPage->trackModifiedByUser();
        },
        'page.update:after' => function (\Kirby\Cms\Page $newPage, \Kirby\Cms\Page $oldPage) {
            $newPage->trackModifiedByUser();
        },
        'page.delete:before' => function (\Kirby\Cms\Page $page, bool $force) {
            $page->trackModifiedByUser(false);
        },
        'site.update:after' => function (\Kirby\Cms\Site $newSite, \Kirby\Cms\Site $oldSite) {
            $newSite->trackModifiedByUser();
        },
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'recentlymodified/field',
                'action' => function () {
                    $id = urldecode(get('id'));
                    $id = explode('?', ltrim(str_replace(['/pages/', '/_drafts/', '+', ' '], ['/', '/', '/', '/'], $id), '/'))[0];
                    $kirby = App::instance(null, true);

                    if ($id === 'site') {
                        $user = site()->findRecentlyModifiedByUser();
                        $username = $user ? (string)$user->nameOrEmail() : '';
                        return [
                            'auser' => $username,
                            'datetime' => option('bnomei.recently-modified.format')(site()->modifiedTimestamp()),
                        ];
                    }
                    if ($page = $kirby->page($id)) {
                        $user = $page->findRecentlyModifiedByUser();
                        $username = $user ? (string)$user->nameOrEmail() : '';
                        return [
                            'auser' => $username,
                            'datetime' => option('bnomei.recently-modified.format')($page->modified()),
                        ];
                    }
                    return \Kirby\Http\Response::json([], 404);
                },
            ],
        ],
    ],
]);
