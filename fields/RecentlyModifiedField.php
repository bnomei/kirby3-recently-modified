<?php

namespace Bnomei\RecentlyModified;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;

final class RecentlyModifiedField extends DisplayField
{
    #[Prop]
    protected int $interval = 0;

    #[Prop]
    protected string $theme = 'info';

    public function modifier(): array
    {
        $model = $this->model();
        $modified = match (true) {
            $model instanceof Site => $model->modifiedTimestamp(),
            $model instanceof Page => $model->modified(),
            default => throw new InvalidArgumentException(message: 'The recentlymodified field requires a page or site.'),
        };

        return [
            'auser' => (string) $model->findRecentlyModifiedByUser()?->nameOrEmail(),
            'datetime' => option('bnomei.recently-modified.format')($modified),
        ];
    }

    public function props(): array
    {
        return [...parent::props(), 'modifier' => $this->modifier()];
    }

    public function api(): array
    {
        $field = $this;

        return [[
            'pattern' => '',
            'method' => 'GET',
            'action' => fn (): array => $field->modifier(),
        ]];
    }
}
