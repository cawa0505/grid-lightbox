<?php

namespace Encore\Grid\Lightbox;

use Encore\Admin\Admin;
use Encore\Admin\Show\AbstractField;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class LightboxField extends AbstractField
{
    public $escape = false;

    public $options = [
        'type' => 'image',
    ];

    protected function script()
    {
        $options = json_encode($this->options);

        return <<<SCRIPT
$('.field-show-gallery').each(function(){ 
    $(this).find('a').magnificPopup($options); 
});
SCRIPT;
    }

    public function zooming()
    {
        $this->options = array_merge($this->options, [
            'mainClass' => 'mfp-with-zoom',
            'zoom' => [
                'enabled' => true,
                'duration' => 300,
                'easing' => 'ease-in-out',
            ]
        ]);
    }

    public function render(array $options = [])
    {
        if (empty($this->value)) {
            return '';
        }

        if ($this->value instanceof Arrayable) {
            $this->value = $this->value->toArray();
        }

        $server = Arr::get($options, 'server');
        $width = Arr::get($options, 'width', 200);
        $height = Arr::get($options, 'height', 200);
        $class = Arr::get($options, 'class', 'thumbnail');
        $class = collect((array)$class)->map(function ($item) {
            return 'img-'. $item;
        })->implode(' ');

        if (Arr::get($options, 'zooming')) {
            $this->zooming();
        }

        Admin::script($this->script());

        return '<div class="field-show-gallery">' . collect((array)$this->value)->filter()->map(function ($path) use ($server, $width, $height, $class) {
            if (url()->isValidUrl($path) || strpos($path, 'data:image') === 0) {
                $src = $path;
            } elseif ($server) {
                $src = rtrim($server, '/') . '/' . ltrim($path, '/');
            } else {
                $src = Storage::disk(config('admin.upload.disk'))->url($path);
            }

            return <<<HTML
<a href="$src" class="show-field-popup-link">
    <img src="$src" style="max-width:{$width}px;max-height:{$height}px" class="img {$class}" />
</a>
HTML;
        })->implode('&nbsp;') . '</div>';
    }
}
