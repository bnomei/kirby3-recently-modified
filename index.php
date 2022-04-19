<?php

Kirby::plugin('bnomei/recently-modified', [
    'options' => [
        'query' => "site.index(true).sortBy('modified', 'desc').onlyModifiedByUser",
        'format' => 'Y/m/d H:i:s',
        'info' => function (\Kirby\Cms\Page $page) {
            return $page->modified(option('bnomei.recently-modified.format'));
        },
        'link' => function (\Kirby\Cms\Page $page) {
            return $page->panelUrl();
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
                    return $this->model()->modified(option('bnomei.recently-modified.format'));
                },
            ],
        ],
    ],
    'sections' => [
        'recentlymodified' => [
            'props' => [
                'headline' => function (string $headline = 'Recently Modified') {
                    return $headline;
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
                $collection = new \Kirby\Toolkit\Query($query, [
                    'kirby' => kirby(),
                    'site' => kirby()->site(),
                    'page' => $page,
                    'pages' => kirby()->site()->pages(),
                    'user' => $user,
                ]);
                $keys = $collection->result()
                    ->limit(intval(option('bnomei.recently-modified.limit')))
                    ->toArray(fn($page) => $page->id());
                kirby()->cache('bnomei.recently-modified')
                    ->set($cacheKey, $keys, intval(option('bnomei.recently-modified.expire')));
            }
            return pages($keys ?? []);
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
            $list = array_slice($list, 0, intval(option('bnomei.recently-modified.limit')));
            kirby()->cache('bnomei.recently-modified')->set($cacheKey, $list);
            return true;
        },
        'findRecentlyModifiedByUser' => function (): ?\Kirby\Cms\User {
            $modifier = null;
            $modified = null;
            foreach (kirby()->users() as $user) {
                $list = kirby()->cache('bnomei.recently-modified')->get($user->id(), []);
                if (array_key_exists($this->id(), $list)) {
                    $modifiedTS = $list[$this->id()];
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
            $cacheKey = kirby()->user()->id();
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
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'recentlymodified/field',
                'action' => function () {
                    $id = urldecode(get('id'));
                    $id = explode('?', ltrim(str_replace(['/pages/', '/_drafts/', '+', ' '], ['/', '/', '/', '/'], $id), '/'))[0];
                    if ($page = page($id)) {
                        $user = $page->findRecentlyModifiedByUser();
                        $username = $user ? (string)$user->nameOrEmail() : '';
                        return [
                            'auser' => $username,
                            'datetime' => $page->modified(option('bnomei.recently-modified.format')),
                        ];
                    }
                    return \Kirby\Http\Response::json([], 404);
                },
            ],
        ],
    ],
]);
