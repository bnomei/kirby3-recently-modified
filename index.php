<?php

use Bnomei\RecentlyModified\RecentlyModifiedField;
use Bnomei\RecentlyModified\RecentlyModifiedListField;
use Kirby\Cms\App;
use Kirby\Cms\Languages;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Query\Query;
use Kirby\Toolkit\Str;

require_once __DIR__ . '/fields/RecentlyModifiedField.php';
require_once __DIR__ . '/fields/RecentlyModifiedListField.php';

App::plugin('bnomei/recently-modified', [
    'options' => [
        'query' => "site.index(true).sortBy('modified', 'desc').onlyModifiedByUser",
        'format' => function ($datetime): string {
            $handler = kirby()->option('date.handler') ?? 'date';
            $formats = [
                'date' => 'Y/m/d H:i:s',
                'intl' => 'yyyy/MM/dd HH:mm:ss', // https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
                'strftime' => '%Y/%m/%d %H:%M:%S',
            ];
            $format = $formats[$handler] ?? $formats['date'];

            return Str::date($datetime, $format, $handler);
        },
        'info' => function (\Kirby\Cms\Page $page) {
            return option('bnomei.recently-modified.format')($page->modified());
        },
        'link' => function (\Kirby\Cms\Page $page) {
            return $page->panel()->url(true);
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
        'recentlymodified' => RecentlyModifiedField::class,
        'recentlymodifiedlist' => RecentlyModifiedListField::class,
    ],
    'siteMethods' => [
        'recentlyModified' => function (string $query, string $parentId = ''): Pages {
            $kirby = $this->kirby();
            $user = $kirby->user();
            $cache = $kirby->cache('bnomei.recently-modified');
            $cacheKey = md5(serialize([$user?->id(), $kirby->languageCode(), $parentId, $query]));
            $keys = $cache->get($cacheKey);

            if (is_array($keys) === false) {
                $page = $parentId !== '' ? $kirby->page($parentId) : null;
                $collection = Query::factory($query)->resolve([
                    'kirby' => $kirby,
                    'site' => $this,
                    'page' => $page,
                    'model' => $page ?? $this,
                    'pages' => $this->pages(),
                    'user' => $user,
                ]);
                $pages = $collection instanceof Pages ? $collection : new Pages();
                if ($user !== null) {
                    $pages = $pages->filter(fn (Page $page) => $page->isListable());
                }
                $pages = $pages->limit((int) option('bnomei.recently-modified.limit'));
                $cache->set(
                    $cacheKey,
                    $pages->values(fn (Page $page) => $page->id()),
                    (int) option('bnomei.recently-modified.expire'),
                );
                return $pages;
            }

            // Unlike pages($keys), App::page() resolves draft and nested draft IDs.
            $pages = new Pages();
            foreach ($keys as $id) {
                $page = $kirby->page($id);
                if ($page !== null && ($user === null || $page->isListable())) {
                    $pages->add($page);
                }
            }
            return $pages;
        },
        'modifiedTimestamp' => function (): int {
            // Site::modified() tracks the entire content tree, not just site content.
            $version = $this->version('latest');
            $timestamps = [];
            foreach (Languages::ensure() as $language) {
                if (($modified = $version->modified($language)) !== null) {
                    $timestamps[] = $modified;
                }
            }

            return empty($timestamps) ? time() : max($timestamps);
        },
        'trackModifiedByUser' => function (bool $add = true): bool {
            if (!kirby()->user() || option('bnomei.recently-modified.hooks') !== true) {
                return false;
            }
            $cacheKey = kirby()->user()->id();

            $listKey = (string) ($this->id() ?? 'site');
            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            if ($listKey === 'site' && array_key_exists('', $list)) {
                unset($list['']);
            }
            if ($add) {
                $list[$listKey] = microtime(true);
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
                if (array_key_exists('', $list)) {
                    if (!array_key_exists($id, $list) || $list[$id] < $list['']) {
                        $list[$id] = $list[''];
                    }
                    unset($list['']);
                    kirby()->cache('bnomei.recently-modified')->set($user->id(), $list);
                }
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

            $listKey = (string) ($this->id() ?? '');
            if ($listKey === '') {
                return false;
            }

            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            if ($add) {
                $list[$listKey] = microtime(true);
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
            $id = (string) ($this->id() ?? '');
            if ($id === '') {
                return null;
            }

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
            if (!$user) {
                return $this->filterBy(fn($page) => false);
            }

            $cacheKey = $user->id();
            $list = kirby()->cache('bnomei.recently-modified')->get($cacheKey, []);
            return $this->filterBy(function ($page) use ($list) {
                $pageId = (string) ($page->id() ?? '');
                return $pageId !== '' && array_key_exists($pageId, $list);
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
]);
