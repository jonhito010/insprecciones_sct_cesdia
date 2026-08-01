<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;

/**
 * Vista base: paginación en enlaces planos (sin &lt;li&gt;) para alinear el pie de tablas.
 */
class AppView extends View
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Paginator', [
            'templates' => [
                'nextActive' => '<a class="pag-btn" rel="next" href="{{url}}">{{text}}</a>',
                'nextDisabled' => '<span class="pag-btn pag-btn--disabled">{{text}}</span>',
                'prevActive' => '<a class="pag-btn" rel="prev" href="{{url}}">{{text}}</a>',
                'prevDisabled' => '<span class="pag-btn pag-btn--disabled">{{text}}</span>',
                'first' => '<a class="pag-btn" href="{{url}}">{{text}}</a>',
                'last' => '<a class="pag-btn" href="{{url}}">{{text}}</a>',
                'number' => '<a class="pag-btn" href="{{url}}">{{text}}</a>',
                'current' => '<span class="pag-btn pag-btn--current" aria-current="page">{{text}}</span>',
                'ellipsis' => '<span class="pag-btn pag-btn--ellipsis" aria-hidden="true">&hellip;</span>',
            ],
        ]);
    }
}
