<?php

namespace Bnomei\RecentlyModified;

use Kirby\Cms\Page;
use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;

final class RecentlyModifiedListField extends DisplayField
{
    #[Prop(export: false)]
    protected string|null $query = null;

    public function props(): array
    {
        $model = $this->model();
        $pages = $this->kirby()->site()->recentlyModified(
            $this->query ?? option('bnomei.recently-modified.query'),
            $model instanceof Page ? $model->id() : '',
        );
        $link = option('bnomei.recently-modified.link');
        $text = option('bnomei.recently-modified.text');
        $info = option('bnomei.recently-modified.info');

        return [
            ...parent::props(),
            'items' => $pages->values(fn (Page $page) => [
                'link' => $link($page),
                'text' => $text($page),
                'info' => $info($page),
            ]),
        ];
    }
}
